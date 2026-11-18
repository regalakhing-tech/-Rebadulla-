<?php
declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?: "/");
$file = realpath(__DIR__ . $path);
foreach (["assets", "image"] as $directory) {
    $root = realpath(__DIR__ . "/" . $directory);
    if (
        $file &&
        $root &&
        str_starts_with($file, $root . DIRECTORY_SEPARATOR) &&
        is_file($file)
    ) {
        return false;
    }
}
require __DIR__ . "/api/index.php";
