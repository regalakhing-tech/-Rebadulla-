<?php
declare(strict_types=1);

if (
    getenv("RAC_INTEGRATION_TEST") !== "1" ||
    getenv("DB_DATABASE") !== "clinic_test"
) {
    fwrite(
        STDERR,
        "Run only against a disposable PostgreSQL database named clinic_test with RAC_INTEGRATION_TEST=1.\n",
    );
    exit(1);
}
require dirname(__DIR__) . "/src/bootstrap.php";
$checks = 0;
function verify(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException("FAIL " . $message);
    }
    $checks++;
}
function denied(callable $work, string $message): void
{
    try {
        $work();
    } catch (ValidationError) {
        verify(true, $message);
        return;
    }
    verify(false, $message);
}
function database_denied(callable $work, string $message): void
{
    try {
        $work();
    } catch (PDOException $error) {
        verify($error->getCode() === "42501", $message);
        return;
    }
    verify(false, $message);
}
$prefix = "QA-" . bin2hex(random_bytes(4));
verify(timestamp_label('2026-11-17 01:00:00+00') === 'Nov 17, 2026 · 9:00 AM', 'UTC timestamps converted to Philippine time');
$owner = row(
    "INSERT INTO users (role, full_name, email, phone, password_hash) VALUES ('owner', ?, ?, '09000000000', ?) RETURNING *",
    [
        $prefix . " Owner",
        strtolower($prefix) . "@example.test",
        password_hash("qa-password-123", PASSWORD_DEFAULT),
    ],
);
$otherOwner = row(
    "INSERT INTO users (role, full_name, email) VALUES ('owner', ?, ?) RETURNING *",
    [$prefix . " Other", strtolower($prefix) . "-other@example.test"],
);
$admin = row(
    "INSERT INTO users (role, full_name, email, password_hash) VALUES ('admin', ?, ?, ?) RETURNING *",
    [
        $prefix . " Admin",
        strtolower($prefix) . "-admin@example.test",
        password_hash("qa-admin-password-123", PASSWORD_DEFAULT),
    ],
);
$pet = row(
    "INSERT INTO pets (owner_id, name, species) VALUES (?, ?, 'Dog') RETURNING *",
    [(int) $owner["id"], $prefix . " Pet"],
);
$service = row(
    "INSERT INTO services (name, max_per_slot, price_from) VALUES (?, 2, 0) RETURNING *",
    [$prefix . " Service"],
);
$date = (new DateTimeImmutable("next Monday"))->format("Y-m-d");
$key = bin2hex(random_bytes(16));
$_ENV["MAIL_ENABLED"] = "true";
$book = fn(
    string $time,
    string $source = "online",
    ?array $actor = null,
    ?string $requestKey = null,
) => create_appointment(
    $actor ?? $owner,
    (int) $pet["id"],
    (int) $service["id"],
    $date,
    $time,
    $source,
    "QA notes",
    $requestKey ?? bin2hex(random_bytes(16)),
);
$record = $book("10:00", "online", $owner, $key);
verify(
    $record["status"] === "pending" &&
        str_starts_with($record["booking_ref"], "RAC-"),
    "pending booking and reference",
);
verify(
    (int) $book("10:00", "online", $owner, $key)["id"] === (int) $record["id"],
    "booking request idempotency",
);
verify(
    (int) row(
        "SELECT count(*) AS total FROM appointment_history WHERE appointment_id = ?",
        [(int) $record["id"]],
    )["total"] === 1,
    "single initial history event",
);
verify(
    (int) row(
        "SELECT count(*) AS total FROM mail_outbox m JOIN appointment_history h ON h.id = m.history_id WHERE h.appointment_id = ?",
        [(int) $record["id"]],
    )["total"] === 1,
    "durable email queued",
);
denied(
    fn() => appointment((int) $record["id"], $otherOwner),
    "other owner cannot load appointment",
);
denied(
    fn() => $book("11:00", "online", $otherOwner),
    "other owner cannot book another pet",
);
denied(
    fn() => $book("11:00", "phone", $owner),
    "owner cannot impersonate front desk",
);
denied(
    fn() => create_appointment(
        $owner,
        (int) $pet["id"],
        (int) $service["id"],
        "2026-02-30",
        "10:00",
        "online",
        null,
        bin2hex(random_bytes(16)),
    ),
    "impossible date rejected",
);
denied(
    fn() => create_appointment(
        $owner,
        (int) $pet["id"],
        (int) $service["id"],
        "2020-01-01",
        "10:00",
        "online",
        null,
        bin2hex(random_bytes(16)),
    ),
    "past date rejected",
);
$sunday = (new DateTimeImmutable("next Sunday"))->format("Y-m-d");
denied(
    fn() => create_appointment(
        $owner,
        (int) $pet["id"],
        (int) $service["id"],
        $sunday,
        "10:00",
        "online",
        null,
        bin2hex(random_bytes(16)),
    ),
    "closed weekday rejected",
);
$updated = change_appointment(
    $admin,
    (int) $record["id"],
    "propose",
    $date,
    "11:00",
    "New time",
    bin2hex(random_bytes(16)),
    $record["updated_at"],
);
verify(
    $updated["status"] === "proposed" && $updated["appt_time"] === "11:00:00",
    "admin proposal",
);
$changeKey = bin2hex(random_bytes(16));
$confirmed = change_appointment(
    $owner,
    (int) $record["id"],
    "accept",
    "",
    "",
    null,
    $changeKey,
    $updated["updated_at"],
);
verify($confirmed["status"] === "confirmed", "owner accepts");
verify(
    change_appointment(
        $owner,
        (int) $record["id"],
        "accept",
        "",
        "",
        null,
        $changeKey,
        $updated["updated_at"],
    )["status"] === "confirmed",
    "transition retry idempotency",
);
denied(
    fn() => change_appointment(
        $admin,
        (int) $record["id"],
        "reschedule",
        $date,
        "12:00",
        null,
        bin2hex(random_bytes(16)),
        $record["updated_at"],
    ),
    "stale browser version rejected",
);
denied(
    fn() => change_appointment(
        $admin,
        (int) $record["id"],
        "complete",
        "",
        "",
        null,
        bin2hex(random_bytes(16)),
        $confirmed["updated_at"],
    ),
    "future completion rejected",
);
$changed = change_appointment(
    $owner,
    (int) $record["id"],
    "change",
    $date,
    "12:00",
    "Owner request",
    bin2hex(random_bytes(16)),
    $confirmed["updated_at"],
);
verify(
    $changed["status"] === "pending" &&
        $changed["booking_ref"] === $record["booking_ref"],
    "reschedule retains original record and reference",
);
$lastHistory = row(
    "SELECT * FROM appointment_history WHERE appointment_id = ? ORDER BY id DESC LIMIT 1",
    [(int) $record["id"]],
);
verify(
    $lastHistory["old_time"] === "11:00:00" &&
        $lastHistory["new_time"] === "12:00:00" &&
        (int) $lastHistory["changed_by"] === (int) $owner["id"],
    "history captures old and new schedule and actor",
);
$cancelled = change_appointment(
    $owner,
    (int) $record["id"],
    "cancel",
    "",
    "",
    null,
    bin2hex(random_bytes(16)),
    $changed["updated_at"],
);
verify($cancelled["status"] === "cancelled", "owner cancellation");
denied(
    fn() => change_appointment(
        $admin,
        (int) $record["id"],
        "confirm",
        "",
        "",
        null,
        bin2hex(random_bytes(16)),
        $cancelled["updated_at"],
    ),
    "final record immutable",
);
database_denied(
    fn() => query(
        "UPDATE appointment_history SET note = ? WHERE appointment_id = ?",
        ["tampered", (int) $record["id"]],
    ),
    "PHP role cannot alter history",
);
database_denied(
    fn() => query("DELETE FROM appointments WHERE id = ?", [
        (int) $record["id"],
    ]),
    "PHP role cannot delete appointments",
);
verify(
    count(appointment_list($owner, "a.id = ?", [(int) $record["id"]])) === 1 &&
        appointment_list($otherOwner, "a.id = ?", [(int) $record["id"]]) === [],
    "owner list isolation",
);
$walkIn = $book("", "walk_in", $admin);
verify(
    $walkIn["status"] === "confirmed" && $walkIn["appt_date"] === date("Y-m-d"),
    "walk-in recorded now",
);
$walkComplete = change_appointment(
    $admin,
    (int) $walkIn["id"],
    "complete",
    "",
    "",
    null,
    bin2hex(random_bytes(16)),
    $walkIn["updated_at"],
);
verify($walkComplete["status"] === "completed", "past visit completion");
$book("09:00", "phone", $admin);
$pendingA = $book("09:00");
$pendingB = $book("09:00");
$start = (string) (microtime(true) + 0.6);
$processes = [];
foreach ([$pendingA, $pendingB] as $candidate) {
    $pipes = [];
    $process = proc_open(
        [
            PHP_BINARY,
            ROOT . "/tests/confirm-worker.php",
            (string) $admin["id"],
            (string) $candidate["id"],
            $start,
        ],
        [["pipe", "r"], ["pipe", "w"], ["pipe", "w"]],
        $pipes,
        ROOT,
    );
    fclose($pipes[0]);
    $processes[] = [$process, $pipes];
}
$results = [];
foreach ($processes as [$process, $pipes]) {
    $results[] = trim(stream_get_contents($pipes[1]));
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    verify(
        proc_close($process) === 0 && $errors === "",
        "confirmation worker exits cleanly",
    );
}
sort($results);
verify(
    $results === ["confirmed", "rejected"],
    "concurrent confirmation admits exactly one last place",
);
verify(
    (int) row(
        "SELECT count(*) AS total FROM appointments WHERE service_id = ? AND appt_date = ? AND appt_time = '09:00' AND status = 'confirmed'",
        [(int) $service["id"], $date],
    )["total"] === 2,
    "capacity remains two",
);
verify(
    !in_array("09:00:00", available_slots($date, (int) $service["id"]), true),
    "full time removed from picker",
);
query("INSERT INTO clinic_closures (date, reason) VALUES (?, ?)", [
    $date,
    $prefix . " Closure",
]);
verify(
    available_slots($date, (int) $service["id"]) === [],
    "closure removes all slots",
);
denied(fn() => $book("13:00"), "forced booking on closure rejected");
query("DELETE FROM clinic_closures WHERE date = ?", [$date]);
query("UPDATE services SET is_active = false WHERE id = ?", [
    (int) $service["id"],
]);
denied(fn() => $book("13:00"), "inactive service booking rejected");
verify(
    available_slots($date, (int) $service["id"]) === [],
    "inactive service has no slots",
);
try {
    db()->beginTransaction();
    db()->exec("SET LOCAL ROLE anon");
    db()->query("SELECT * FROM clinic.users");
    verify(false, "anon cannot access private schema");
} catch (PDOException $error) {
    verify($error->getCode() === "42501", "anon cannot access private schema");
} finally {
    db()->rollBack();
}
verify(
    (int) row(
        "SELECT count(*) AS total FROM appointments WHERE created_by = ?",
        [(int) $owner["id"]],
    )["total"] === 3,
    "failed writes leave no partial appointments",
);
echo "PASS $checks PostgreSQL integration checks\n";
