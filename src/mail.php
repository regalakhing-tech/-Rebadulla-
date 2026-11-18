<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

function mail_enabled(): bool
{
    return filter_var(env("MAIL_ENABLED", "false"), FILTER_VALIDATE_BOOLEAN);
}

function configured_mailer(): PHPMailer
{
    if (env("SMTP_HOST") === "" || !filter_var(env("MAIL_FROM_ADDRESS"), FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException("Email delivery is not configured.");
    }
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = env("SMTP_HOST");
    $mail->Port = (int) env("SMTP_PORT", "587");
    $mail->SMTPAuth = filter_var(env("SMTP_AUTH", "true"), FILTER_VALIDATE_BOOLEAN);
    $mail->Username = env("SMTP_USERNAME");
    $mail->Password = env("SMTP_PASSWORD");
    if ($mail->SMTPAuth && ($mail->Username === "" || $mail->Password === "")) {
        throw new RuntimeException("SMTP credentials are missing.");
    }
    $encryption = env("SMTP_ENCRYPTION", "tls");
    if ($encryption === "none" && env("APP_ENV") === "local" && in_array($mail->Host, ["127.0.0.1", "localhost", "::1"], true)) {
        $mail->SMTPSecure = "";
        $mail->SMTPAutoTLS = false;
    } elseif (in_array($encryption, ["tls", "ssl"], true)) {
        $mail->SMTPSecure = $encryption === "ssl" ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        throw new RuntimeException("SMTP encryption must be tls or ssl.");
    }
    $mail->Timeout = 8;
    $mail->getSMTPInstance()->Timelimit = 12;
    $mail->CharSet = "UTF-8";
    $mail->setFrom(env("MAIL_FROM_ADDRESS"), env("MAIL_FROM_NAME", "Rebadulla Animal Care"));
    return $mail;
}

function send_verification_email(string $email, string $name, string $code): void
{
    $mail = configured_mailer();
    $mail->addAddress($email, $name);
    $mail->Subject = "Verify your email | Rebadulla Animal Care";
    $mail->addEmbeddedImage(ROOT . "/assets/email-logo.png", "rebadulla-logo", "rebadulla-logo.png", PHPMailer::ENCODING_BASE64, "image/png");
    $mail->isHTML(true);
    ob_start();
    require ROOT . "/views/emails/verification.php";
    $mail->Body = (string) ob_get_clean();
    $mail->AltBody = "Hello {$name},\n\nYour verification code: {$code}\n\nEnter this code to verify your Rebadulla Animal Care account. It expires in 10 minutes and can only be used once. Never share this code.\n\nIf you did not request this email, you can ignore it.\n\nRebadulla Animal Care";
    $mail->send();
}

function queue_appointment_mail(
    int $appointmentId,
    int $historyId,
    string $action,
): void {
    if (!mail_enabled()) {
        return;
    }
    $record = row(
        'SELECT a.*, p.name AS pet_name, u.full_name, u.email, s.name AS service_name
        FROM appointments a JOIN pets p ON p.id = a.pet_id JOIN users u ON u.id = p.owner_id
        JOIN services s ON s.id = a.service_id WHERE a.id = ?',
        [$appointmentId],
    );
    if (!$record["email"]) {
        return;
    }
    $subject =
        "Rebadulla · " .
        $record["booking_ref"] .
        " · " .
        ucfirst(str_replace("_", " ", $action));
    $body =
        "Hello {$record["full_name"]},\n\nYour appointment has been updated.\n\n" .
        "Reference: {$record["booking_ref"]}\nPet: {$record["pet_name"]}\nService: {$record["service_name"]}\n" .
        "Schedule: " .
        schedule_label($record["appt_date"], $record["appt_time"]) .
        "\nStatus: " .
        ucfirst($record["status"]) .
        "\n\nView your appointment: " .
        rtrim(env("APP_URL"), "/") .
        "/owner/appointment.php?id=" .
        $appointmentId .
        "\n\nRebadulla Animal Care";
    query(
        "INSERT INTO mail_outbox (history_id, recipient, subject, body) VALUES (?, ?, ?, ?) ON CONFLICT (history_id) DO NOTHING",
        [$historyId, $record["email"], $subject, $body],
    );
}

function deliver_mail(int $limit = 5): array
{
    if (!mail_enabled()) {
        return ["sent" => 0, "failed" => 0];
    }
    $result = ["sent" => 0, "failed" => 0];
    // Stops early so a slow mail server cannot outlast the 30-second Vercel limit.
    $deadline = microtime(true) + 12;
    for ($i = 0; $i < $limit && microtime(true) < $deadline; $i++) {
        $message = transaction(function (): ?array {
            $message = row("SELECT * FROM mail_outbox WHERE attempts < 5 AND
                (status = 'pending' OR (status = 'sending' AND locked_at < now() - interval '5 minutes'))
                ORDER BY created_at FOR UPDATE SKIP LOCKED LIMIT 1");
            if ($message) {
                query(
                    "UPDATE mail_outbox SET status = 'sending', locked_at = now(), attempts = attempts + 1 WHERE id = ?",
                    [(int) $message["id"]],
                );
            }
            return $message;
        });
        if (!$message) {
            break;
        }
        try {
            $mail = configured_mailer();
            $mail->addAddress($message["recipient"]);
            $mail->Subject = $message["subject"];
            $mail->Body = $message["body"];
            $mail->MessageID =
                "<rac-" .
                $message["id"] .
                "-" .
                substr(hash("sha256", env("APP_KEY")), 0, 16) .
                "@" .
                (parse_url(env("APP_URL"), PHP_URL_HOST) ?: "localhost") .
                ">";
            $mail->send();
            query(
                "UPDATE mail_outbox SET status = 'sent', sent_at = now(), last_error = NULL WHERE id = ?",
                [(int) $message["id"]],
            );
            $result["sent"]++;
        } catch (Throwable $error) {
            // The booking was committed before SMTP. A mail failure preserves the clinic record.
            error_log(
                "Mail delivery failed for outbox " .
                    $message["id"] .
                    ": " .
                    $error->getMessage(),
            );
            query(
                "UPDATE mail_outbox SET status = 'pending', last_error = 'SMTP delivery failed. Check server logs and SMTP settings.' WHERE id = ?",
                [(int) $message["id"]],
            );
            $result["failed"]++;
            break;
        }
    }
    return $result;
}
