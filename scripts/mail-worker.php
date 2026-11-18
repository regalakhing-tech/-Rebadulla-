<?php
declare(strict_types=1);
if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit();
}
require dirname(__DIR__) . "/src/bootstrap.php";
if (!mail_enabled()) {
    fwrite(
        STDERR,
        "Email is disabled. Configure SMTP and set MAIL_ENABLED=true.\n",
    );
    exit(1);
}
echo json_encode(deliver_mail(20), JSON_PRETTY_PRINT) . PHP_EOL;
