<?php
declare(strict_types=1);
if (
    getenv("RAC_INTEGRATION_TEST") !== "1" ||
    getenv("DB_DATABASE") !== "clinic_test"
) {
    exit(90);
}
require dirname(__DIR__) . "/src/bootstrap.php";
$actor = row("SELECT id, role FROM users WHERE id = ?", [(int) $argv[1]]);
$record = appointment((int) $argv[2], $actor);
$wait = (int) (((float) $argv[3] - microtime(true)) * 1000000);
if ($wait > 0) {
    usleep($wait);
}
try {
    change_appointment(
        $actor,
        (int) $record["id"],
        "confirm",
        "",
        "",
        null,
        bin2hex(random_bytes(16)),
        $record["updated_at"],
    );
    echo "confirmed\n";
} catch (ValidationError $error) {
    echo "rejected\n";
}
