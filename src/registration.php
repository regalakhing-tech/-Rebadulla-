<?php
declare(strict_types=1);

function registration_token(): string
{
    $token = $_COOKIE[cookie_name("registration")] ?? "";
    return is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token) ? $token : "";
}

function registration_code_hash(string $token, string $code): string
{
    return hash_hmac("sha256", "registration|" . $token . "|" . $code, env("APP_KEY"));
}

function pending_registration(bool $lock = false): ?array
{
    $token = registration_token();
    if ($token === "") return null;
    return row("SELECT *, extract(epoch FROM expires_at)::bigint AS expires_unix,
        extract(epoch FROM sent_at)::bigint AS sent_unix,
        expires_at <= now() AS expired FROM registration_challenges WHERE token_hash = ?" . ($lock ? " FOR UPDATE" : ""),
        [hash("sha256", $token)]);
}

function registration_mail_throttle(string $email): void
{
    throttle("otp-send-email", $email, 5);
    throttle("otp-send-ip", $_SERVER["REMOTE_ADDR"] ?? "unknown", 20);
}

function begin_registration(string $name, string $email, string $phone, string $password): never
{
    registration_mail_throttle($email);
    // Validate delivery configuration before storing any pending account.
    try { configured_mailer(); } catch (Throwable) {
        throw new ValidationError("We could not send your verification email. Please try again shortly or contact the clinic.");
    }
    query("DELETE FROM registration_challenges WHERE expires_at < now() - interval '1 hour'");
    $token = bin2hex(random_bytes(32));
    $code = sprintf("%06d", random_int(0, 999999));
    $tokenHash = hash("sha256", $token);
    query("INSERT INTO registration_challenges (token_hash,full_name,email,phone,password_hash,code_hash,expires_at)
        VALUES (?,?,?,?,?,?,now() + interval '10 minutes')",
        [$tokenHash, $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), registration_code_hash($token, $code)]);
    try {
        send_verification_email($email, $name, $code);
    } catch (Throwable) {
        query("DELETE FROM registration_challenges WHERE token_hash = ?", [$tokenHash]);
        // SMTP errors can contain private transport details. Keep them out of the page and logs.
        error_log("Registration verification email delivery failed.");
        throw new ValidationError("We could not send your verification email. Please try again shortly or contact the clinic.");
    }
    query("UPDATE registration_challenges SET delivered_at = now() WHERE token_hash = ?", [$tokenHash]);
    $oldToken = registration_token();
    if ($oldToken !== "") query("DELETE FROM registration_challenges WHERE token_hash = ?", [hash("sha256", $oldToken)]);
    put_cookie("registration", $token, time() + 3600);
    redirect("/verify-email.php");
}

function resend_registration_code(): never
{
    $pending = pending_registration();
    if (!$pending) redirect("/register.php");
    if (time() - (int) $pending["sent_unix"] < 60) {
        throw new ValidationError("Please wait 60 seconds before requesting another code.");
    }
    registration_mail_throttle($pending["email"]);
    $token = registration_token();
    do { $code = sprintf("%06d", random_int(0, 999999)); }
    while (hash_equals($pending["code_hash"], registration_code_hash($token, $code)));
    $hash = registration_code_hash($token, $code);
    $claimed = query("UPDATE registration_challenges SET code_hash=?, attempts=0, expires_at=now()+interval '10 minutes',
        sent_at=now(), delivered_at=NULL WHERE token_hash=? AND sent_at <= now()-interval '60 seconds'",
        [$hash, hash("sha256", $token)])->rowCount();
    if (!$claimed) throw new ValidationError("Please wait 60 seconds before requesting another code.");
    try { send_verification_email($pending["email"], $pending["full_name"], $code); }
    catch (Throwable) {
        error_log("Registration verification resend failed.");
        throw new ValidationError("We could not send a new code. Please wait a minute and try again.");
    }
    query("UPDATE registration_challenges SET delivered_at=now() WHERE token_hash=? AND code_hash=?",
        [hash("sha256", $token), $hash]);
    put_cookie("registration", $token, time() + 3600);
    redirect("/verify-email.php?resent=1");
}

function verify_registration_code(): never
{
    $pending = pending_registration();
    if (!$pending) redirect("/register.php");
    $code = field("code", 6);
    if (!preg_match('/^[0-9]{6}$/D', $code)) throw new ValidationError("Enter the six-digit code from your email.");
    throttle("otp-verify-email", $pending["email"], 20);
    throttle("otp-verify-ip", $_SERVER["REMOTE_ADDR"] ?? "unknown", 60);
    // Return errors from the transaction so failed-attempt increments are committed.
    $result = transaction(function () use ($code): array {
        $challenge = pending_registration(true);
        if (!$challenge) return ["error" => "This verification has already been used. Please sign in."];
        if ((int) $challenge["attempts"] >= 5) return ["error" => "Too many incorrect attempts. Request a new code to continue."];
        if ($challenge["expired"]) return ["error" => "This code has expired. Request a new code to continue."];
        if (!$challenge["delivered_at"]) return ["error" => "We could not send this code. Request a new code to continue."];
        if (!hash_equals($challenge["code_hash"], registration_code_hash(registration_token(), $code))) {
            query("UPDATE registration_challenges SET attempts=attempts+1 WHERE token_hash=?", [$challenge["token_hash"]]);
            return ["error" => (int) $challenge["attempts"] >= 4
                ? "Too many incorrect attempts. Request a new code to continue."
                : "That code does not match. Check your email and try again."];
        }
        $account = row("INSERT INTO users (role,full_name,email,phone,password_hash) VALUES ('owner',?,?,?,?)
            ON CONFLICT (email) DO NOTHING RETURNING id,role",
            [$challenge["full_name"], $challenge["email"], $challenge["phone"], $challenge["password_hash"]]);
        query("DELETE FROM registration_challenges WHERE token_hash=?", [$challenge["token_hash"]]);
        return $account ? ["user" => $account] : ["error" => "This email is already registered. Please sign in."];
    });
    if (isset($result["error"])) throw new ValidationError($result["error"]);
    put_cookie("registration", "", time() - 3600);
    login_user($result["user"]);
}
