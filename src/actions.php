<?php
declare(strict_types=1);

function handle_post(string $path): void
{
    check_csrf();
    if (!configured()) {
        throw new ValidationError(
            "Supabase is not configured yet. Complete the steps in SETUP.md first.",
        );
    }
    switch ($path) {
        case "/register.php":
            register_owner();
        case "/verify-email.php":
            if ($signedIn = current_user()) {
                redirect("/" . $signedIn["role"] . "/dashboard.php");
            }
            if (field("action", 12) === "resend") {
                resend_registration_code();
            }
            verify_registration_code();
        case "/login.php":
            authenticate("owner");
        case "/admin-login.php":
            authenticate("admin");
        case "/logout.php":
            $wasAdmin = (current_user()["role"] ?? "") === "admin";
            $token = $_COOKIE[cookie_name("session")] ?? "";
            query("DELETE FROM login_sessions WHERE token_hash = ?", [
                hash("sha256", $token),
            ]);
            put_cookie("session", "", time() - 3600);
            redirect($wasAdmin ? "/admin-login.php" : "/login.php");
        case "/owner/pets.php":
            save_pet(require_role("owner"));
            redirect("/owner/pets.php?saved=1");
        case "/owner/book.php":
            $owner = require_role("owner");
            $record = create_appointment(
                $owner,
                positive_id($_POST["pet_id"] ?? 0),
                positive_id($_POST["service_id"] ?? 0),
                field("appt_date", 10, true),
                field("appt_time", 8, true),
                "online",
                field("owner_notes", 2000) ?: null,
                posted_request_key(),
            );
            finish_booking($record, "owner");
        case "/owner/appointment.php":
        case "/admin/appointment.php":
            $role = str_starts_with($path, "/admin/") ? "admin" : "owner";
            $actor = require_role($role);
            $record = change_appointment(
                $actor,
                positive_id($_GET["id"] ?? 0),
                field("action", 20, true),
                field("appt_date", 10),
                field("appt_time", 8),
                field("note", 2000) ?: null,
                posted_request_key(),
                field("version", 60, true),
            );
            finish_booking($record, $role);
        case "/admin/new-appointment.php":
            $admin = require_role("admin");
            $source = field("source", 10, true);
            if (!in_array($source, ["phone", "walk_in"], true)) {
                throw new ValidationError("Choose phone booking or walk-in.");
            }
            $record = transaction(function () use ($admin, $source): array {
                $key = posted_request_key();
                // A retry must reuse the owner and pet created by the first request.
                query("SELECT pg_advisory_xact_lock(hashtextextended(?, 0))", [
                    $key,
                ]);
                $existing = row(
                    "SELECT * FROM appointments WHERE request_key = ? AND created_by = ?",
                    [$key, (int) $admin["id"]],
                );
                if ($existing) {
                    return $existing;
                }
                $petId = field("pet_id", 20);
                if ($petId === "") {
                    $ownerId = field("owner_id", 20);
                    if ($ownerId === "") {
                        $email = strtolower(field("email", 150));
                        if (
                            $email !== "" &&
                            !filter_var($email, FILTER_VALIDATE_EMAIL)
                        ) {
                            throw new ValidationError(
                                "Enter a valid email address.",
                            );
                        }
                        $owner = row(
                            "INSERT INTO users (role, full_name, email, phone) VALUES ('owner', ?, ?, ?) RETURNING id",
                            [
                                field("full_name", 100, true),
                                $email ?: null,
                                field("phone", 30, $source === "phone") ?: null,
                            ],
                        );
                        $ownerId = (string) $owner["id"];
                    }
                    $owner = row(
                        "SELECT id FROM users WHERE id = ? AND role = 'owner'",
                        [positive_id($ownerId)],
                    );
                    if (!$owner) {
                        throw new ValidationError("Owner not found.");
                    }
                    $pet = row(
                        "INSERT INTO pets (owner_id, name, species, breed) VALUES (?, ?, ?, ?) RETURNING id",
                        [
                            (int) $owner["id"],
                            field("pet_name", 60, true),
                            field("species", 30, true),
                            field("breed", 60) ?: null,
                        ],
                    );
                    $petId = (string) $pet["id"];
                }
                return create_appointment(
                    $admin,
                    positive_id($petId),
                    positive_id($_POST["service_id"] ?? 0),
                    field("appt_date", 10),
                    field("appt_time", 8),
                    $source,
                    field("owner_notes", 2000) ?: null,
                    $key,
                );
            });
            finish_booking($record, "admin");
        case "/admin/services.php":
            require_role("admin");
            save_service();
            forget("landing");
            redirect("/admin/services.php?saved=1");
        case "/admin/hours.php":
            require_role("admin");
            save_hours();
            forget("landing");
            redirect("/admin/hours.php?saved=1");
        case "/admin/mail.php":
            require_role("admin");
            if (field("action", 20) === "reset") {
                query("UPDATE mail_outbox SET attempts = 0, status = 'pending', locked_at = NULL
                    WHERE (status = 'pending' AND attempts >= 5)
                    OR (status = 'sending' AND locked_at < now() - interval '5 minutes')");
            }
            $result = deliver_mail(2);
            redirect(
                "/admin/mail.php?sent=" .
                    $result["sent"] .
                    "&failed=" .
                    $result["failed"],
            );
        default:
            http_response_code(405);
            throw new ValidationError(
                "This page does not accept form submissions.",
            );
    }
}

function finish_booking(array $record, string $role): never
{
    try {
        deliver_mail();
    } catch (Throwable $error) {
        error_log("Outbox processing failed: " . $error->getMessage());
    }
    redirect("/" . $role . "/appointment.php?id=" . $record["id"] . "&saved=1");
}

function save_pet(array $owner): void
{
    $sex = field("sex", 10, true);
    $birth = field("birth_date", 10);
    if (
        !in_array($sex, ["male", "female", "unknown"], true) ||
        ($birth !== "" && (!valid_date($birth) || $birth > date("Y-m-d")))
    ) {
        throw new ValidationError(
            "Choose a valid sex and a birth date up to today.",
        );
    }
    $values = [
        field("name", 60, true),
        field("species", 30, true),
        field("breed", 60) ?: null,
        $sex,
        $birth ?: null,
        field("notes", 2000) ?: null,
    ];
    $id = field("id", 20);
    $photo = uploaded_photo("photo");
    transaction(function () use ($values, $id, $photo, $owner): void {
        if ($id !== "") {
            $petId = positive_id($id);
            $changed = query(
                "UPDATE pets SET name = ?, species = ?, breed = ?, sex = ?, birth_date = ?, notes = ? WHERE id = ? AND owner_id = ?",
                [...$values, $petId, (int) $owner["id"]],
            );
            if (!$changed->rowCount()) {
                throw new ValidationError("Pet not found.");
            }
        } else {
            $petId = (int) row(
                "INSERT INTO pets (name, species, breed, sex, birth_date, notes, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id",
                [...$values, (int) $owner["id"]],
            )["id"];
        }
        if ($photo) {
            // Bytes travel as base64 text because the pooled connection uses emulated prepares.
            query(
                "INSERT INTO pet_photos (pet_id, mime, bytes) VALUES (?, ?, decode(?, 'base64'))
                ON CONFLICT (pet_id) DO UPDATE SET mime = excluded.mime, bytes = excluded.bytes, updated_at = now()",
                [$petId, $photo["mime"], base64_encode($photo["bytes"])],
            );
        } elseif (isset($_POST["remove_photo"])) {
            query("DELETE FROM pet_photos WHERE pet_id = ?", [$petId]);
        }
    });
}

function save_service(): void
{
    $price = field("price_from", 20);
    if (
        $price !== "" &&
        (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $price) ||
            (float) $price < 0)
    ) {
        throw new ValidationError("Enter a valid starting price.");
    }
    $capacity = filter_var(
        field("max_per_slot", 3, true),
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1, "max_range" => 100]],
    );
    if ($capacity === false) {
        throw new ValidationError("Places per slot must be between 1 and 100.");
    }
    $values = [
        field("name", 120, true),
        field("description", 2000),
        $price !== "" ? $price : null,
        $capacity,
        isset($_POST["is_active"]),
    ];
    $id = field("id", 20);
    if ($id !== "") {
        query(
            "UPDATE services SET name = ?, description = ?, price_from = ?, max_per_slot = ?, is_active = ? WHERE id = ?",
            [...$values, positive_id($id)],
        );
    } else {
        query(
            "INSERT INTO services (name, description, price_from, max_per_slot, is_active) VALUES (?, ?, ?, ?, ?)",
            $values,
        );
    }
}

function save_hours(): void
{
    $action = field("action", 20, true);
    if ($action === "closure_add" || $action === "closure_remove") {
        $date = field("date", 10, true);
        if (!valid_date($date)) {
            throw new ValidationError("Enter a valid closure date.");
        }
        if ($action === "closure_remove") {
            query("DELETE FROM clinic_closures WHERE date = ?", [$date]);
        } else {
            query(
                "INSERT INTO clinic_closures (date, reason) VALUES (?, ?) ON CONFLICT (date) DO UPDATE SET reason = excluded.reason",
                [$date, field("reason", 200, true)],
            );
        }
        return;
    }
    if ($action !== "weekly") {
        throw new ValidationError("Invalid hours action.");
    }
    transaction(function (): void {
        for ($day = 0; $day <= 6; $day++) {
            $closed = isset($_POST["closed_" . $day]);
            $open = field("open_" . $day, 5);
            $close = field("close_" . $day, 5);
            if (
                !$closed &&
                (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $open) ||
                    !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $close) ||
                    $open >= $close)
            ) {
                throw new ValidationError(
                    "Opening time must be before closing time for each open day.",
                );
            }
            query(
                "UPDATE clinic_hours SET open_time = ?, close_time = ?, is_closed = ? WHERE day_of_week = ?",
                [
                    $closed ? null : $open,
                    $closed ? null : $close,
                    $closed,
                    $day,
                ],
            );
        }
    });
}
