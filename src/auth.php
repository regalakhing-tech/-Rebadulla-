<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $loaded = false;
    static $user;
    if (!$loaded) {
        $loaded = true;
        $token = $_COOKIE[cookie_name("session")] ?? "";
        if (configured() && preg_match('/^[a-f0-9]{64}$/D', $token)) {
            $user = row(
                'SELECT u.id, u.role, u.full_name, u.email, u.phone FROM login_sessions s
                JOIN users u ON u.id = s.user_id WHERE s.token_hash = ? AND s.expires_at > now()',
                [hash("sha256", $token)],
            );
        }
    }
    return $user;
}

function require_role(string $role): array
{
    $user = current_user();
    if (!$user || $user["role"] !== $role) {
        redirect($role === "admin" ? "/admin-login.php" : "/login.php");
    }
    return $user;
}

function login_user(array $user): void
{
    query("DELETE FROM login_sessions WHERE expires_at < now()");
    $old = $_COOKIE[cookie_name("session")] ?? "";
    if ($old !== "") {
        query("DELETE FROM login_sessions WHERE token_hash = ?", [
            hash("sha256", $old),
        ]);
    }
    $token = bin2hex(random_bytes(32));
    query(
        "INSERT INTO login_sessions (token_hash, user_id, expires_at) VALUES (?, ?, now() + interval '12 hours')",
        [hash("sha256", $token), (int) $user["id"]],
    );
    put_cookie("session", $token, time() + 43200);
    redirect("/" . $user["role"] . "/dashboard.php");
}

function throttle(string $scope, string $identity, int $limit = 8): void
{
    $key = hash_hmac(
        "sha256",
        $scope . "|" . strtolower($identity),
        env("APP_KEY"),
    );
    $attempt = row(
        "INSERT INTO login_attempts (key, attempts, window_start) VALUES (?, 1, now())
        ON CONFLICT (key) DO UPDATE SET
            attempts = CASE WHEN login_attempts.window_start < now() - interval '15 minutes' THEN 1 ELSE login_attempts.attempts + 1 END,
            window_start = CASE WHEN login_attempts.window_start < now() - interval '15 minutes' THEN now() ELSE login_attempts.window_start END
        RETURNING attempts",
        [$key],
    );
    if ((int) $attempt["attempts"] > $limit) {
        throw new ValidationError(
            "Too many attempts. Please try again in 15 minutes.",
        );
    }
}

function register_owner(): never
{
    $name = field("full_name", 100, true);
    $email = strtolower(field("email", 150, true));
    $phone = field("phone", 30, true);
    $password = password_field("password");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new ValidationError("Enter a valid email address.");
    }
    if (
        strlen($password) < 8 ||
        $password !== password_field("confirm_password")
    ) {
        throw new ValidationError(
            "Use at least 8 characters and make both passwords match.",
        );
    }
    throttle("register", $_SERVER["REMOTE_ADDR"] ?? "unknown", 12);
    if (row("SELECT id FROM users WHERE email = ?", [$email])) {
        throw new ValidationError("This email is already registered. Please sign in or contact the clinic.");
    }
    begin_registration($name, $email, $phone, $password);
}

// Owners and clinic staff sign in on separate pages; $role is the page's account type.
function authenticate(string $role): never
{
    $email = strtolower(field("email", 150, true));
    $password = password_field("password");
    throttle("login-ip", $_SERVER["REMOTE_ADDR"] ?? "unknown", 40);
    throttle("login-email", $email);
    $user = row("SELECT id, role, password_hash FROM users WHERE email = ?", [
        $email,
    ]);
    $hash =
        $user["password_hash"] ??
        '$2y$12$9yxJQChySDTkEJopLF6MRelKRvV5dx0kDSpCrVYS2F8zMXiYBwxgq';
    if (
        !password_verify($password, $hash) ||
        !$user ||
        !$user["password_hash"]
    ) {
        throw new ValidationError("Wrong email or password.");
    }
    if ($user["role"] !== $role) {
        throw new ValidationError(
            $role === "admin"
                ? "This is not a clinic staff account. Pet owners sign in on the owner page."
                : "Clinic staff sign in on the staff page.",
        );
    }
    login_user($user);
}
