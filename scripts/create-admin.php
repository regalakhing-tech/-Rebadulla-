<?php
declare(strict_types=1);

if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit();
}
require dirname(__DIR__) . "/src/bootstrap.php";
echo "Administrator full name: ";
$name = trim(fgets(STDIN));
echo "Administrator email: ";
$email = strtolower(trim(fgets(STDIN)));
echo "Password (at least 12 characters, input is visible): ";
$password = rtrim(fgets(STDIN), "\r\n");
if (
    $name === "" ||
    mb_strlen($name) > 100 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($password) < 12 ||
    strlen($password) > 72 ||
    str_contains($password, "\0")
) {
    fwrite(
        STDERR,
        "Use a valid name, email, and a password of 12–72 bytes.\n",
    );
    exit(1);
}
try {
    query(
        "INSERT INTO users (role, full_name, email, password_hash) VALUES ('admin', ?, ?, ?)",
        [$name, $email, password_hash($password, PASSWORD_DEFAULT)],
    );
    echo "Administrator created. Sign in through /admin-login.php.\n";
} catch (PDOException $error) {
    fwrite(
        STDERR,
        $error->getCode() === "23505"
            ? "That email already has an account.\n"
            : "Could not create administrator. Check the database configuration.\n",
    );
    exit(1);
}
