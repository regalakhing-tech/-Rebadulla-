<?php
declare(strict_types=1);

define("ROOT", dirname(__DIR__));
if (is_file(ROOT . "/vendor/autoload.php")) {
    require ROOT . "/vendor/autoload.php";
    Dotenv\Dotenv::createImmutable(ROOT)->safeLoad();
}

function env(string $name, string $default = ""): string
{
    return (string) ($_ENV[$name] ?? getenv($name) ?: $default);
}

date_default_timezone_set(env("APP_TIMEZONE", "Asia/Manila"));
ini_set("display_errors", "0");
error_reporting(E_ALL);

require __DIR__ . "/database.php";
require __DIR__ . "/security.php";
require __DIR__ . "/auth.php";
require __DIR__ . "/appointments.php";
require __DIR__ . "/mail.php";
require __DIR__ . "/registration.php";
require __DIR__ . "/actions.php";
require __DIR__ . "/site.php";
require ROOT . "/views/layout.php";

function configured(): bool
{
    return env("DB_HOST") !== "" &&
        env("DB_USERNAME") !== "" &&
        strlen(env("APP_KEY")) >= 32;
}

function redirect(string $path): never
{
    header("Location: " . $path, true, 303);
    exit();
}

function money(mixed $amount): string
{
    return $amount === null
        ? "Ask the clinic"
        : "₱" . number_format((float) $amount, 2);
}

function schedule_label(string $date, string $time = ""): string
{
    return (new DateTimeImmutable(trim($date . " " . $time)))->format(
        $time ? "M j, Y · g:i A" : "M j, Y",
    );
}

function timestamp_label(string $timestamp): string
{
    return (new DateTimeImmutable($timestamp))
        ->setTimezone(new DateTimeZone(date_default_timezone_get()))
        ->format("M j, Y · g:i A");
}

// URL with a content hash, so browsers can keep the file for a year (see vercel.json).
function asset(string $path): string
{
    static $versions = [];
    return $path . "?v=" . ($versions[$path] ??= hash_file("crc32b", ROOT . $path));
}

// Lookup table written by scripts/optimize-images.py.
function images(): array
{
    static $images;
    return $images ??= require ROOT . "/src/images.php";
}

// src, srcset, sizes, width and height attributes for an optimized photo.
function img(string $name, string $sizes = "100vw"): string
{
    $image = images()[$name];
    $set = [];
    foreach ($image["src"] as $width => $url) {
        $set[] = $url . " " . $width . "w";
    }
    return sprintf(
        'src="%s" srcset="%s" sizes="%s" width="%d" height="%d"',
        end($image["src"]),
        implode(", ", $set),
        e($sizes),
        $image["w"],
        $image["h"],
    );
}

// Keeps public data in the temp folder for $ttl seconds so the home page can skip the database.
// shortcut: each server instance keeps its own copy, so an edit can take up to $ttl seconds to
// show on other instances; move to a shared cache such as Redis if that ever matters.
function remember(string $key, int $ttl, callable $load): mixed
{
    $file = sys_get_temp_dir() . "/rac-cache-" . $key . ".json";
    if (is_file($file) && filemtime($file) > time() - $ttl) {
        $cached = json_decode((string) @file_get_contents($file), true);
        if (is_array($cached) && array_key_exists("value", $cached)) {
            return $cached["value"];
        }
    }
    $value = $load();
    $temporary = $file . "." . bin2hex(random_bytes(4));
    if (@file_put_contents($temporary, json_encode(["value" => $value])) !== false && !@rename($temporary, $file)) {
        @unlink($temporary);
    }
    return $value;
}

function forget(string $key): void
{
    @unlink(sys_get_temp_dir() . "/rac-cache-" . $key . ".json");
}

function services(bool $activeOnly = false): array
{
    return rows(
        "SELECT * FROM services" .
            ($activeOnly ? " WHERE is_active = true" : "") .
            " ORDER BY id",
    );
}
