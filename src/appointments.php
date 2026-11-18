<?php
declare(strict_types=1);

const FINAL_STATUSES = ["completed", "missed", "cancelled"];

function next_status(string $role, string $current, string $action): string
{
    $transitions = [
        "owner" => [
            "accept" => ["proposed" => "confirmed"],
            "change" => [
                "pending" => "pending",
                "proposed" => "pending",
                "confirmed" => "pending",
            ],
            "cancel" => [
                "pending" => "cancelled",
                "proposed" => "cancelled",
                "confirmed" => "cancelled",
            ],
        ],
        "admin" => [
            "confirm" => ["pending" => "confirmed", "proposed" => "confirmed"],
            "propose" => [
                "pending" => "proposed",
                "proposed" => "proposed",
                "confirmed" => "proposed",
            ],
            "reschedule" => ["confirmed" => "confirmed"],
            "complete" => ["confirmed" => "completed"],
            "miss" => ["confirmed" => "missed"],
            "cancel" => [
                "pending" => "cancelled",
                "proposed" => "cancelled",
                "confirmed" => "cancelled",
            ],
        ],
    ];
    return $transitions[$role][$action][$current] ??
        throw new ValidationError(
            "This change is not allowed for the current appointment status.",
        );
}

function build_slots(
    string $date,
    ?array $hours,
    bool $closed,
    array $counts,
    int $capacity,
    ?DateTimeImmutable $now = null,
): array {
    if (!valid_date($date) || !$hours || $hours["is_closed"] || $closed) {
        return [];
    }
    $now ??= new DateTimeImmutable();
    $start = new DateTimeImmutable($date . " " . $hours["open_time"]);
    $end = new DateTimeImmutable($date . " " . $hours["close_time"]);
    $slots = [];
    for (
        $slot = $start;
        $slot->modify("+30 minutes") <= $end;
        $slot = $slot->modify("+30 minutes")
    ) {
        $time = $slot->format("H:i:s");
        if ($slot > $now && ($counts[$time] ?? 0) < $capacity) {
            $slots[] = $time;
        }
    }
    return $slots;
}

function available_slots(
    string $date,
    int $serviceId,
    ?int $excludeId = null,
): array {
    if (!valid_date($date)) {
        return [];
    }
    $service = row("SELECT * FROM services WHERE id = ? AND is_active = true", [
        $serviceId,
    ]);
    if (!$service) {
        return [];
    }
    $weekday = (int) (new DateTimeImmutable($date))->format("w");
    $hours = row("SELECT * FROM clinic_hours WHERE day_of_week = ?", [
        $weekday,
    ]);
    $closed =
        row("SELECT date FROM clinic_closures WHERE date = ?", [$date]) !==
        null;
    $bookings = rows(
        "SELECT appt_time, count(*) AS count FROM appointments
        WHERE service_id = ? AND appt_date = ? AND status = 'confirmed' AND id <> ? GROUP BY appt_time",
        [$serviceId, $date, $excludeId ?? 0],
    );
    return build_slots(
        $date,
        $hours,
        $closed,
        array_column($bookings, "count", "appt_time"),
        (int) $service["max_per_slot"],
    );
}

function validate_slot(
    string $date,
    string $time,
    int $serviceId,
    ?int $excludeId = null,
): string {
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::00)?$/D', $time)) {
        throw new ValidationError("Choose a valid appointment time.");
    }
    $time = substr($time, 0, 5) . ":00";
    if (
        !in_array($time, available_slots($date, $serviceId, $excludeId), true)
    ) {
        throw new ValidationError(
            "That time is unavailable. It may be full, closed, or in the past. Choose another open time.",
        );
    }
    return $time;
}

function appointment(int $id, array $actor, bool $lock = false): array
{
    $sql = 'SELECT a.*, p.name AS pet_name, p.species, p.owner_id, u.full_name AS owner_name,
        u.email AS owner_email, u.phone, s.name AS service_name, s.price_from
        FROM appointments a JOIN pets p ON p.id = a.pet_id
        JOIN users u ON u.id = p.owner_id JOIN services s ON s.id = a.service_id WHERE a.id = ?';
    $parameters = [$id];
    if ($actor["role"] === "owner") {
        $sql .= " AND p.owner_id = ?";
        $parameters[] = (int) $actor["id"];
    }
    $record = row($sql . ($lock ? " FOR UPDATE OF a" : ""), $parameters);
    if (!$record) {
        http_response_code(404);
        throw new ValidationError("Appointment not found.");
    }
    return $record;
}

function create_appointment(
    array $actor,
    int $petId,
    int $serviceId,
    string $date,
    string $time,
    string $source,
    ?string $note,
    string $key,
): array {
    return transaction(function () use (
        $actor,
        $petId,
        $serviceId,
        $date,
        $time,
        $source,
        $note,
        $key,
    ): array {
        if (
            !in_array($source, ["online", "phone", "walk_in"], true) ||
            ($actor["role"] === "owner" && $source !== "online")
        ) {
            throw new ValidationError("Invalid booking source.");
        }
        $pet = row(
            "SELECT p.*, u.email FROM pets p JOIN users u ON u.id = p.owner_id WHERE p.id = ? AND u.role = 'owner'",
            [$petId],
        );
        if (
            !$pet ||
            ($actor["role"] === "owner" &&
                (int) $pet["owner_id"] !== (int) $actor["id"])
        ) {
            throw new ValidationError("Pet not found.");
        }
        // Locking the service serializes capacity decisions across every PHP instance.
        if (
            !row(
                "SELECT id FROM services WHERE id = ? AND is_active = true FOR UPDATE",
                [$serviceId],
            )
        ) {
            throw new ValidationError("Choose an active service.");
        }
        $existing = row(
            "SELECT * FROM appointments WHERE request_key = ? AND created_by = ?",
            [$key, (int) $actor["id"]],
        );
        if ($existing) {
            return $existing;
        }
        $status = $source === "online" ? "pending" : "confirmed";
        if ($source === "walk_in") {
            $date = date("Y-m-d");
            $time = date("H:i:00");
        } else {
            $time = validate_slot($date, $time, $serviceId);
        }
        $reference = "RAC-" . strtoupper(bin2hex(random_bytes(4)));
        $record = row(
            'INSERT INTO appointments
            (booking_ref, pet_id, service_id, appt_date, appt_time, status, source, owner_notes, created_by, request_key)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING *',
            [
                $reference,
                $petId,
                $serviceId,
                $date,
                $time,
                $status,
                $source,
                $note,
                (int) $actor["id"],
                $key,
            ],
        );
        record_history($record, null, "created", $actor, $note, $key);
        return $record;
    });
}

function change_appointment(
    array $actor,
    int $id,
    string $action,
    string $date,
    string $time,
    ?string $note,
    string $key,
    string $version,
): array {
    return transaction(function () use (
        $actor,
        $id,
        $action,
        $date,
        $time,
        $note,
        $key,
        $version,
    ): array {
        $old = appointment($id, $actor, true);
        if (
            row(
                "SELECT id FROM appointment_history WHERE request_key = ? AND appointment_id = ? AND changed_by = ?",
                [$key, $id, (int) $actor["id"]],
            )
        ) {
            return $old;
        }
        if ($old["updated_at"] !== $version) {
            throw new ValidationError(
                "This appointment changed in another window. Refresh to see its latest status.",
            );
        }
        $status = next_status($actor["role"], $old["status"], $action);
        $newDate = $old["appt_date"];
        $newTime = $old["appt_time"];
        if (
            in_array(
                $action,
                ["change", "propose", "reschedule", "confirm", "accept"],
                true,
            )
        ) {
            row("SELECT id FROM services WHERE id = ? FOR UPDATE", [
                (int) $old["service_id"],
            ]);
            if (in_array($action, ["change", "propose", "reschedule"], true)) {
                $newDate = $date;
                $newTime = $time;
            }
            $newTime = validate_slot(
                $newDate,
                $newTime,
                (int) $old["service_id"],
                $id,
            );
        }
        if (
            in_array($action, ["complete", "miss"], true) &&
            new DateTimeImmutable(
                $old["appt_date"] . " " . $old["appt_time"],
            ) >= new DateTimeImmutable()
        ) {
            throw new ValidationError(
                "You can mark a visit completed or missed after its scheduled time.",
            );
        }
        $record = row(
            "UPDATE appointments SET status = ?, appt_date = ?, appt_time = ?, updated_at = clock_timestamp() WHERE id = ? RETURNING *",
            [$status, $newDate, $newTime, $id],
        );
        $historyAction = [
            "accept" => "accepted",
            "change" => "change_requested",
            "confirm" => "confirmed",
            "propose" => "proposed",
            "reschedule" => "rescheduled",
            "complete" => "completed",
            "miss" => "missed",
            "cancel" => "cancelled",
        ][$action];
        record_history($record, $old, $historyAction, $actor, $note, $key);
        return $record;
    });
}

function record_history(
    array $record,
    ?array $old,
    string $action,
    array $actor,
    ?string $note,
    string $key,
): void {
    $history = row(
        'INSERT INTO appointment_history
        (appointment_id, action, old_status, new_status, old_date, old_time, new_date, new_time, note, changed_by, request_key)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id',
        [
            (int) $record["id"],
            $action,
            $old["status"] ?? null,
            $record["status"],
            $old["appt_date"] ?? null,
            $old["appt_time"] ?? null,
            $record["appt_date"],
            $record["appt_time"],
            $note,
            (int) $actor["id"],
            $key,
        ],
    );
    queue_appointment_mail((int) $record["id"], (int) $history["id"], $action);
}

function appointment_list(
    array $actor,
    string $where = "",
    array $parameters = [],
    int $page = 0,
    string $order = "recent",
): array {
    $conditions = ["true"];
    if ($actor["role"] === "owner") {
        $conditions[] = "p.owner_id = ?";
        array_unshift($parameters, (int) $actor["id"]);
    }
    if ($where !== "") {
        $conditions[] = "(" . $where . ")";
    }
    $ordering = match ($order) {
        "upcoming"
            => "(a.status = 'proposed') DESC, a.appt_date, a.appt_time, a.id",
        "schedule" => "a.appt_date, a.appt_time, a.id",
        default => "a.appt_date DESC, a.appt_time DESC, a.id DESC",
    };
    return rows(
        'SELECT a.*, p.name AS pet_name, u.full_name AS owner_name, u.phone, s.name AS service_name
        FROM appointments a JOIN pets p ON p.id = a.pet_id JOIN users u ON u.id = p.owner_id
        JOIN services s ON s.id = a.service_id WHERE ' .
            implode(" AND ", $conditions) .
            " ORDER BY " .
            $ordering .
            " LIMIT 51 OFFSET " .
            max(0, $page) * 50,
        $parameters,
    );
}

// Report period from ?start=&end= (defaults to this month); rejects bad dates.
function report_period(): array
{
    $start = (string) ($_GET["start"] ?? date("Y-m-01"));
    $end = (string) ($_GET["end"] ?? date("Y-m-t"));
    if (!valid_date($start) || !valid_date($end) || $start > $end) {
        throw new ValidationError("Choose a valid date range with the start before the end.");
    }
    return [$start, $end];
}

function report_data(string $start, string $end): array
{
    $byStatus = rows(
        "SELECT status, count(*) AS total FROM appointments WHERE appt_date BETWEEN ? AND ? GROUP BY status ORDER BY status",
        [$start, $end],
    );
    return [
        "byStatus" => $byStatus,
        "byService" => rows(
            "SELECT s.name, count(*) AS total, count(*) FILTER (WHERE a.status = 'completed') AS completed,
            count(*) FILTER (WHERE a.status = 'missed') AS missed, count(*) FILTER (WHERE a.status = 'cancelled') AS cancelled
            FROM appointments a JOIN services s ON s.id = a.service_id WHERE a.appt_date BETWEEN ? AND ? GROUP BY s.id, s.name ORDER BY total DESC",
            [$start, $end],
        ),
        "byDate" => rows(
            "SELECT appt_date, count(*) AS total FROM appointments WHERE appt_date BETWEEN ? AND ? GROUP BY appt_date ORDER BY appt_date",
            [$start, $end],
        ),
        "total" => array_sum(array_column($byStatus, "total")),
    ];
}
