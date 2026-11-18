<?php
// Applies one SQL migration file to the database named in an env file.
// Usage: php scripts/migrate.php database/migrations/<file>.sql [.env.supabase]
declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";

$file = $argv[1] ?? "";
$envFile = dirname(__DIR__) . "/" . ($argv[2] ?? ".env");
if (!is_file($file) || !is_file($envFile)) {
    fwrite(STDERR, "Usage: php scripts/migrate.php <file.sql> [env file]\n");
    exit(1);
}
$env = Dotenv\Dotenv::parse((string) file_get_contents($envFile));
$pdo = new PDO(
    sprintf(
        "pgsql:host=%s;port=%d;dbname=%s;sslmode=%s;connect_timeout=10",
        $env["DB_HOST"],
        (int) $env["DB_PORT"],
        $env["DB_DATABASE"] ?? "postgres",
        $env["DB_SSLMODE"] ?? "require",
    ),
    $env["DB_USERNAME"],
    $env["DB_PASSWORD"],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$pdo->exec((string) file_get_contents($file));
echo "Applied " . basename($file) . " to " . $env["DB_HOST"] . PHP_EOL;
