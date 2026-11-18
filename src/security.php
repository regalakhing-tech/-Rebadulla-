<?php
declare(strict_types=1);

class ValidationError extends RuntimeException {}

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8",
    );
}

function secure_cookie(): bool
{
    return env("APP_ENV") === "production" ||
        (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off");
}

function cookie_name(string $name): string
{
    return (secure_cookie() ? "__Host-" : "") . "rac_" . $name;
}

function put_cookie(string $name, string $value, int $expires): void
{
    setcookie(cookie_name($name), $value, [
        "expires" => $expires,
        "path" => "/",
        "secure" => secure_cookie(),
        "httponly" => true,
        "samesite" => "Lax",
    ]);
}

function csrf_token(): string
{
    $token = $_COOKIE[cookie_name("csrf")] ?? "";
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
        $token = bin2hex(random_bytes(32));
        put_cookie("csrf", $token, time() + 86400);
        $_COOKIE[cookie_name("csrf")] = $token;
    }
    return $token;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    $cookie = $_COOKIE[cookie_name("csrf")] ?? "";
    $submitted = $_POST["csrf"] ?? "";
    if (
        !is_string($submitted) ||
        strlen($cookie) !== 64 ||
        !hash_equals($cookie, $submitted)
    ) {
        throw new ValidationError(
            "Invalid form. Refresh the page and try again.",
        );
    }
    $origin = $_SERVER["HTTP_ORIGIN"] ?? "";
    $expectedHost = parse_url(env("APP_URL"), PHP_URL_HOST);
    if (
        $origin !== "" &&
        $expectedHost &&
        parse_url($origin, PHP_URL_HOST) !== $expectedHost
    ) {
        throw new ValidationError("Invalid form origin.");
    }
}

function field(string $key, int $maximum = 500, bool $required = false): string
{
    $value = $_POST[$key] ?? "";
    if (!is_string($value)) {
        throw new ValidationError("Invalid form field.");
    }
    $value = trim($value);
    if ($required && $value === "") {
        throw new ValidationError("Please complete all required fields.");
    }
    if (mb_strlen($value) > $maximum) {
        throw new ValidationError(
            "A field exceeds the allowed length of " .
                $maximum .
                " characters.",
        );
    }
    return $value;
}

function password_field(string $key): string
{
    $value = $_POST[$key] ?? "";
    if (!is_string($value) || $value === "" || strlen($value) > 72 || str_contains($value, "\0")) {
        throw new ValidationError(
            "Enter a valid password up to 72 bytes.",
        );
    }
    return $value;
}

// Checked pet photo upload (JPEG, PNG or WebP, up to 2 MB), or null when no file was chosen.
function uploaded_photo(string $key): ?array
{
    $file = $_FILES[$key] ?? null;
    if (!is_array($file) || ($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (
        $file["error"] !== UPLOAD_ERR_OK ||
        !is_uploaded_file((string) $file["tmp_name"]) ||
        $file["size"] > 2 * 1024 * 1024
    ) {
        throw new ValidationError("Choose a photo up to 2 MB.");
    }
    $mime = @getimagesize((string) $file["tmp_name"])["mime"] ?? "";
    if (!in_array($mime, ["image/jpeg", "image/png", "image/webp"], true)) {
        throw new ValidationError("Choose a JPG, PNG, or WebP photo.");
    }
    return ["mime" => $mime, "bytes" => (string) file_get_contents((string) $file["tmp_name"])];
}

function positive_id(mixed $value): int
{
    if (
        filter_var($value, FILTER_VALIDATE_INT, [
            "options" => ["min_range" => 1],
        ]) === false
    ) {
        throw new ValidationError("Choose a valid record.");
    }
    return (int) $value;
}

function valid_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat("!Y-m-d", $date);
    return $parsed !== false && $parsed->format("Y-m-d") === $date;
}

function request_key(): string
{
    return '<input type="hidden" name="request_key" value="' .
        bin2hex(random_bytes(16)) .
        '">';
}

function posted_request_key(): string
{
    $key = field("request_key", 32, true);
    if (!preg_match('/^[a-f0-9]{32}$/D', $key)) {
        throw new ValidationError(
            "Invalid request. Refresh the page and try again.",
        );
    }
    return $key;
}
