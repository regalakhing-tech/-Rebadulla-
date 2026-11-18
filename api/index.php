<?php
declare(strict_types=1);

require dirname(__DIR__) . "/src/bootstrap.php";
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header(
    "Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self'; font-src 'self'; script-src 'self'; frame-src https://www.google.com https://maps.google.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
);
$path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
// Portal pages hold personal data and are never stored. Public pages are revalidated on every
// visit, which still lets the browser's back/forward cache restore them instantly.
header(
    str_starts_with($path, "/owner/") || str_starts_with($path, "/admin/") || in_array($path, ["/login.php", "/admin-login.php", "/register.php", "/verify-email.php"], true)
        ? "Cache-Control: no-store, private"
        : "Cache-Control: no-cache, private",
);
$error = null;
$page = null;
csrf_token();

try {
    if ($path === "/health") {
        header("Content-Type: application/json");
        if (!configured()) {
            http_response_code(503);
            echo json_encode(["status" => "setup_required"]);
        } else {
            row("SELECT 1 FROM clinic_hours LIMIT 1");
            echo json_encode(["status" => "ok"]);
        }
        exit();
    }
    if ($path === "/pet-photo.php") {
        // Owners see their own pets' photos; clinic staff see every pet's photo.
        $actor = current_user();
        $id = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        $photo = $actor && $id
            ? row(
                "SELECT ph.mime, encode(ph.bytes, 'base64') AS data FROM pet_photos ph JOIN pets p ON p.id = ph.pet_id
                WHERE ph.pet_id = ? AND (p.owner_id = ? OR ? = 'admin')",
                [$id, (int) $actor["id"], $actor["role"]],
            )
            : null;
        if (!$photo) {
            http_response_code(404);
            exit();
        }
        header("Content-Type: " . $photo["mime"]);
        header("Content-Security-Policy: default-src 'none'");
        // The link carries ?v=<last update>, so a new photo always gets a new URL.
        header("Cache-Control: private, max-age=31536000, immutable");
        echo base64_decode($photo["data"]);
        exit();
    }
    if ($path === "/slots.php") {
        header("Content-Type: application/json");
        $actor = current_user();
        if (!$actor) {
            http_response_code(401);
            echo json_encode(["error" => "Please sign in."]);
            exit();
        }
        $exclude = null;
        if (isset($_GET["appointment_id"])) {
            $record = appointment(positive_id($_GET["appointment_id"]), $actor);
            $exclude = (int) $record["id"];
        }
        echo json_encode(
            available_slots(
                (string) ($_GET["date"] ?? ""),
                positive_id($_GET["service_id"] ?? 0),
                $exclude,
            ),
        );
        exit();
    }
    // Short staff address: /admin opens the workspace, or the staff sign-in when signed out.
    if ($path === "/admin" || $path === "/admin/") {
        redirect((current_user()["role"] ?? "") === "admin" ? "/admin/dashboard.php" : "/admin-login.php");
    }
    $routes = [
        "/" => "landing",
        "/index.php" => "landing",
        "/index.html" => "landing",
        "/login.php" => "auth",
        "/admin-login.php" => "admin-login",
        "/register.php" => "auth",
        "/verify-email.php" => "verify-email",
        "/logout.php" => "auth",
        "/owner/dashboard.php" => "dashboard",
        "/owner/pets.php" => "pets",
        "/owner/book.php" => "book",
        "/owner/appointments.php" => "appointments",
        "/owner/appointment.php" => "appointment",
        "/admin/dashboard.php" => "dashboard",
        "/admin/requests.php" => "requests",
        "/admin/appointment.php" => "appointment",
        "/admin/new-appointment.php" => "new-appointment",
        "/admin/schedule.php" => "schedule",
        "/admin/hours.php" => "hours",
        "/admin/services.php" => "services",
        "/admin/records.php" => "records",
        "/admin/reports.php" => "reports",
        "/admin/reports-pdf.php" => "reports-pdf",
        "/admin/mail.php" => "mail",
    ];
    if (!isset($routes[$path])) {
        http_response_code(404);
        throw new ValidationError("Page not found.");
    }
    if (str_starts_with($path, "/owner/")) {
        $user = require_role("owner");
    } elseif (str_starts_with($path, "/admin/")) {
        $user = require_role("admin");
    } else {
        $user = $routes[$path] === "landing" && env("LANDING_PREVIEW", "false") === "true"
            ? null
            : current_user();
    }
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        try {
            handle_post($path);
        } catch (ValidationError $failure) {
            $error = $failure->getMessage();
        } catch (PDOException $failure) {
            if ($failure->getCode() === "23505") {
                $error =
                    "That record already exists. Check the email address or service name and try again.";
            } else {
                throw $failure;
            }
        }
    } elseif (
        $_SERVER["REQUEST_METHOD"] !== "GET" &&
        $_SERVER["REQUEST_METHOD"] !== "HEAD"
    ) {
        http_response_code(405);
        throw new ValidationError("Method not allowed.");
    }
    ob_start();
    require ROOT . "/views/" . $routes[$path] . ".php";
    $content = ob_get_clean();
    render_layout($title ?? "Rebadulla Animal Care", $content, $user, $path);
} catch (Throwable $failure) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    if ($path === "/slots.php" || $path === "/health") {
        http_response_code($failure instanceof ValidationError ? 400 : 503);
        header("Content-Type: application/json");
        echo json_encode([
            "error" =>
                $failure instanceof ValidationError
                    ? $failure->getMessage()
                    : "Service unavailable.",
        ]);
        exit();
    }
    if (!($failure instanceof ValidationError)) {
        error_log((string) $failure);
        http_response_code(503);
    }
    $message =
        $failure instanceof ValidationError
            ? $failure->getMessage()
            : "The clinic portal is temporarily unavailable. Please check the database configuration and try again.";
    render_layout(
        "Clinic portal",
        '<section class="empty-state"><h1>' .
            e($message) .
            '</h1><a class="button" href="/">Back to home</a></section>',
        null,
        $path,
    );
}
