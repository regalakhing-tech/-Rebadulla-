<?php
declare(strict_types=1);

// Line icons for the portal (24px grid, drawn with the current text color).
function icon(string $name, string $class = ""): string
{
    static $paths = [
        "grid" => '<rect x="3" y="3" width="7" height="7" rx="2" /><rect x="14" y="3" width="7" height="7" rx="2" /><rect x="3" y="14" width="7" height="7" rx="2" /><rect x="14" y="14" width="7" height="7" rx="2" />',
        "inbox" => '<path d="M22 12h-6l-2 3h-4l-2-3H2" /><path d="M5.5 5h13L22 12v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6z" />',
        "calendar" => '<rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18M8 15h3" />',
        "calendar-check" => '<rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18M9 16l2 2 4-4" />',
        "plus" => '<path d="M12 5v14M5 12h14" />',
        "folder" => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />',
        "chart" => '<path d="M4 21V11M10 21V3M16 21V8M22 21H2" />',
        "tag" => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z" /><circle cx="7.5" cy="7.5" r="1.5" />',
        "clock" => '<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />',
        "mail" => '<rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 6L2 7" />',
        "paw" => '<circle cx="5.5" cy="10" r="2" /><circle cx="9.5" cy="5.5" r="2" /><circle cx="14.5" cy="5.5" r="2" /><circle cx="18.5" cy="10" r="2" /><path d="M12 12c-3 0-6 3.5-6 6 0 1.7 1.3 2.5 2.8 2.5 1.2 0 2-.5 3.2-.5s2 .5 3.2.5c1.5 0 2.8-.8 2.8-2.5 0-2.5-3-6-6-6z" />',
        "file" => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6M8 13h8M8 17h5" />',
        "check" => '<path d="m5 12 4 4L19 6" />',
        "check-circle" => '<circle cx="12" cy="12" r="9" /><path d="m8 12 3 3 5-6" />',
        "hourglass" => '<path d="M6 2h12M6 22h12M7 2v4a5 5 0 0 0 10 0V2M7 22v-4a5 5 0 0 1 10 0v4" />',
        "repeat" => '<path d="m17 2 4 4-4 4" /><path d="M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4" /><path d="M21 13v2a3 3 0 0 1-3 3H3" />',
        "history" => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8" /><path d="M3 3v5h5M12 7v5l4 2" />',
        "settings" => '<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z" />',
        "home" => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z" />',
        "logout" => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />',
        "menu" => '<path d="M4 6h16M4 12h16M4 18h16" />',
        "shield" => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /><path d="m9 12 2 2 4-4" />',
        "login" => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3" />',
        "wallet" => '<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v3M21 12v8a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V5" /><path d="M16 14h.01" />',
        "arrow-left" => '<path d="M19 12H5M12 19l-7-7 7-7" />',
        "download" => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />',
        "eye-off" => '<path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 4.2A10.4 10.4 0 0 1 12 4c6 0 10 8 10 8a17.6 17.6 0 0 1-3.2 4.2M6.6 6.6C3.9 8.4 2 12 2 12s4 8 10 8a9.9 9.9 0 0 0 5.4-1.6" />',
        "camera" => '<path d="M14.5 4h-5L7.5 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3.5z" /><circle cx="12" cy="13" r="3.5" />',
        "eye" => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" />',
        "edit" => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4z" />',
        "phone" => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z" />',
        "user" => '<circle cx="12" cy="8" r="4" /><path d="M4 21v-1a7 7 0 0 1 14 0v1" />',
        "printer" => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" /><rect x="6" y="14" width="12" height="8" />',
        "send" => '<path d="m22 2-7 20-4-9-9-4z" /><path d="M22 2 11 13" />',
        "x-circle" => '<circle cx="12" cy="12" r="9" /><path d="m15 9-6 6M9 9l6 6" />',
    ];
    return '<svg class="icon' .
        ($class !== "" ? " " . $class : "") .
        '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        ($paths[$name] ?? $paths["grid"]) .
        "</svg>";
}

// Portal menu: group => [path => [label, short label for the phone tab bar, icon]].
function portal_nav(string $role): array
{
    return $role === "admin"
        ? [
            "Appointments" => [
                "/admin/dashboard.php" => ["Overview", "Home", "grid"],
                "/admin/requests.php" => ["Online requests", "Requests", "inbox"],
                "/admin/schedule.php" => ["Schedule", "Schedule", "calendar"],
                "/admin/new-appointment.php" => ["Phone / walk-in", "New", "plus"],
                "/admin/records.php" => ["All records", "Records", "folder"],
            ],
            "Clinic" => [
                "/admin/reports.php" => ["Reports", "Reports", "chart"],
                "/admin/services.php" => ["Services", "Services", "tag"],
                "/admin/hours.php" => ["Clinic hours", "Hours", "clock"],
                "/admin/mail.php" => ["Email outbox", "Email", "mail"],
            ],
        ]
        : [
            "My account" => [
                "/owner/dashboard.php" => ["Overview", "Home", "grid"],
                "/owner/book.php" => ["Book a visit", "Book", "plus"],
                "/owner/appointments.php" => ["My appointments", "Visits", "calendar"],
                "/owner/pets.php" => ["My pets", "Pets", "paw"],
            ],
        ];
}

function render_layout(
    string $title,
    string $content,
    ?array $user,
    string $path,
): void {
    $portal =
        $user &&
        (str_starts_with($path, "/admin/") ||
            str_starts_with($path, "/owner/"));
    $admin = $user && $user["role"] === "admin";
    // Sign-in and register pages show only the form and a back button.
    $auth = !$portal && in_array($path, ["/login.php", "/admin-login.php", "/register.php", "/verify-email.php", "/logout.php"], true);
    $logo = images()["logo"]["src"];
    ?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
        <meta
            name="description"
            content="Book pet appointments and manage clinic records with Rebadulla Animal Care in San Jose de Buenavista, Antique."
        />
        <meta name="theme-color" content="#0a4bff" />
        <title><?= e($title) ?> · Rebadulla Animal Care</title>
        <link rel="icon" href="<?= e(end($logo)) ?>" type="image/webp" />
        <link rel="preload" href="/assets/fonts/sora-latin.woff2" as="font" type="font/woff2" crossorigin />
        <link rel="preload" href="/assets/fonts/playfair-latin.woff2" as="font" type="font/woff2" crossorigin />
        <link rel="stylesheet" href="<?= e(asset("/assets/app.css")) ?>" />
        <script src="<?= e(asset("/assets/app.js")) ?>" defer></script>
    </head>
    <body id="top" class="<?= $portal ? "portal" : ($auth ? "auth-site" : "public-site") ?>">
        <a class="skip-link" href="#main">Skip to content</a>
<?php if (!$auth && !$portal): ?>
        <header class="site-header" data-header>
            <div class="nav">
                <a class="brand" href="/">
                    <img <?= img("logo", "48px") ?> alt="" />
                    <span class="brand-name">Rebadulla Animal Care<small>Appointment &amp; Records System</small></span>
                </a>
                <nav class="nav-links" id="nav-links" aria-label="Main navigation">
<?php
    foreach (
        [
            "/#home" => "Home",
            "/#about" => "About",
            "/#services" => "Services",
            "/#gallery" => "Gallery",
            "/#contact" => "Contact",
        ]
        as $href => $label
    ) {
        echo '<a href="' .
            $href .
            '"' .
            ($path === "/" && $href === "/#home" ? ' class="active"' : "") .
            ">" .
            $label .
            "</a>";
    }
    if (!$user) {
        echo '<a class="mobile-only" href="/login.php">Sign in</a>';
    }
?>
                </nav>
                <div class="nav-actions">
<?php if ($user): ?>
                    <a class="button button-pill nav-cta" href="/<?= e($user["role"]) ?>/dashboard.php">My portal</a>
<?php else: ?>
                    <a class="nav-signin" href="/login.php">Sign in</a>
                    <a class="button button-pill nav-cta" href="/owner/book.php">Appoint Now</a>
<?php endif; ?>
                </div>
                <button class="menu-toggle" type="button" aria-controls="nav-links" aria-expanded="false">
                    <span class="menu-icon" aria-hidden="true"></span>
                    <span class="sr-only">Menu</span>
                </button>
            </div>
        </header>
<?php endif; ?>
<?php if ($portal):
    $nav = portal_nav($user["role"]);
    $flat = array_merge(...array_values($nav));
    $tabs = $admin
        ? ["/admin/dashboard.php", "/admin/requests.php", "/admin/schedule.php", "/admin/records.php"]
        : ["/owner/dashboard.php", "/owner/book.php", "/owner/appointments.php", "/owner/pets.php"];
    $counts = [];
    try {
        // Small red badges: requests waiting for the clinic, or new times waiting for the owner.
        $counts = $admin
            ? ["/admin/requests.php" => (int) row("SELECT count(*) AS n FROM appointments WHERE status = 'pending'")["n"]]
            : [
                "/owner/appointments.php" => (int) row(
                    "SELECT count(*) AS n FROM appointments a JOIN pets p ON p.id = a.pet_id WHERE p.owner_id = ? AND a.status = 'proposed'",
                    [(int) $user["id"]],
                )["n"],
            ];
    } catch (Throwable) {
        $counts = [];
    }
    // Owners see their pets (with photos) on the account card.
    $myPets = [];
    if (!$admin) {
        try {
            $myPets = rows(
                "SELECT p.id, p.name, extract(epoch FROM ph.updated_at)::bigint AS photo_version FROM pets p
                LEFT JOIN pet_photos ph ON ph.pet_id = p.id WHERE p.owner_id = ? ORDER BY p.name",
                [(int) $user["id"]],
            );
        } catch (Throwable) {
            $myPets = [];
        }
    }
    $badge = fn(string $href): string => empty($counts[$href]) ? "" : '<b class="nav-count">' . $counts[$href] . "</b>";
    $initial = e(mb_strtoupper(mb_substr($user["full_name"], 0, 1)));
    ?>
        <aside class="sidebar" id="sidebar" aria-label="Portal menu">
            <a class="sidebar-brand" href="/<?= e($user["role"]) ?>/dashboard.php">
                <img <?= img("logo", "40px") ?> alt="" />
                <span>Rebadulla<small><?= $admin ? "Clinic workspace" : "Owner portal" ?></small></span>
            </a>
            <nav class="sidebar-nav" aria-label="Portal navigation">
<?php foreach ($nav as $group => $items): ?>
                <p class="sidebar-label"><?= e($group) ?></p>
<?php foreach ($items as $href => [$label, , $name]): ?>
                <a href="<?= $href ?>"<?= $path === $href ? ' class="active" aria-current="page"' : "" ?>><?= icon($name) ?><span><?= e($label) ?></span><?= $badge($href) ?></a>
<?php endforeach;
endforeach; ?>
            </nav>
            <div class="account-card">
                <div class="sidebar-user">
                    <span class="avatar" aria-hidden="true"><?= $initial ?></span>
                    <span><strong><?= e($user["full_name"]) ?></strong><small><?= $admin
                        ? "Clinic admin"
                        : "Pet owner · " . count($myPets) . (count($myPets) === 1 ? " pet" : " pets") ?></small></span>
                </div>
<?php if ($myPets): ?>
                <a class="account-pets" href="/owner/pets.php" aria-label="My pets">
<?php foreach (array_slice($myPets, 0, 4) as $pet): ?>
                    <span class="account-pet" title="<?= e($pet["name"]) ?>"><?php if ($pet["photo_version"]): ?><img src="/pet-photo.php?id=<?= (int) $pet["id"] ?>&amp;v=<?= (int) $pet["photo_version"] ?>" alt="" /><?php else: ?><?= e(mb_strtoupper(mb_substr($pet["name"], 0, 1))) ?><?php endif; ?></span>
<?php endforeach; ?>
                    <span class="account-pet-names"><?= e(implode(", ", array_column(array_slice($myPets, 0, 2), "name")) . (count($myPets) > 2 ? " +" . (count($myPets) - 2) : "")) ?></span>
                </a>
<?php endif; ?>
            </div>
            <form class="sidebar-logout" action="/logout.php" method="post" data-confirm="You will need to sign in again to see your appointments." data-confirm-title="Log out?" data-confirm-action="Log out">
                <?= csrf_field() ?>
                <button type="submit"><?= icon("logout") ?>Log out</button>
            </form>
        </aside>
        <div class="app">
            <header class="app-topbar">
                <a class="topbar-brand" href="/<?= e($user["role"]) ?>/dashboard.php"><img <?= img("logo", "34px") ?> alt="" />Rebadulla</a>
                <details class="dropdown topbar-menu" data-dropdown>
                    <summary aria-label="Account menu"><span class="avatar"><?= $initial ?></span></summary>
                    <div class="dropdown-panel">
                        <p class="dropdown-user"><strong><?= e($user["full_name"]) ?></strong><small><?= $admin ? "Clinic admin" : "Pet owner" ?></small></p>
<?php foreach (array_diff_key($flat, array_flip($tabs)) as $href => [$label, , $name]): ?>
                        <a href="<?= $href ?>"<?= $path === $href ? ' aria-current="page"' : "" ?>><?= icon($name) ?><?= e($label) ?></a>
<?php endforeach; ?>
                        <form action="/logout.php" method="post" data-confirm="You will need to sign in again to see your appointments." data-confirm-title="Log out?" data-confirm-action="Log out">
                            <?= csrf_field() ?>
                            <button class="menu-logout" type="submit"><?= icon("logout") ?>Log out</button>
                        </form>
                    </div>
                </details>
            </header>
            <main id="main" class="workspace"><?= $content ?></main>
            <footer class="portal-footer">Rebadulla Animal Care · <?= date("Y") ?></footer>
        </div>
        <nav class="tabbar" aria-label="Quick navigation">
<?php foreach ($tabs as $href): ?>
            <a href="<?= $href ?>"<?= $path === $href ? ' class="active" aria-current="page"' : "" ?>><span><?= icon($flat[$href][2]) ?></span><?= e($flat[$href][1]) ?><?= $badge($href) ?></a>
<?php endforeach; ?>
        </nav>
<?php elseif ($auth): ?>
        <main id="main" class="auth-main">
            <a class="auth-back" href="/">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 19l-7-7 7-7" /></svg>
                Back to home
            </a>
            <?= $content ?>
        </main>
<?php else: ?>
        <main id="main"><?= $content ?></main>
        <footer class="site-footer">
            <div class="footer-grid">
                <div class="footer-about">
                    <a class="footer-brand" href="/">REBADULLA <span>ANIMAL CARE.</span></a>
                    <p>Dedicated to providing compassionate, world-class veterinary care in the heart of Antique since 2007.</p>
                    <div class="footer-visit">
                        <span class="footer-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z" /><circle cx="12" cy="9.5" r="2.5" /></svg>
                        </span>
                        <div>
                            <strong>Visit Our Clinic</strong>
                            <p>CHVH Bldg, Cerdeña St, San Jose, Antique.</p>
                            <em>Right beside Purple Yam.</em>
                            <a class="footer-map" href="/#contact">(Click to view map)</a>
                        </div>
                    </div>
                </div>
                <div>
                    <h2 class="footer-title">Explore</h2>
                    <nav class="footer-links" aria-label="Footer navigation">
                        <a href="/#home">Home</a>
                        <a href="/#about">About Us</a>
                        <a href="/#services">Services</a>
                        <a href="/#gallery">Gallery</a>
                        <a href="/#contact">Get In Touch</a>
                    </nav>
                </div>
                <div>
                    <h2 class="footer-title">Get in touch</h2>
                    <div class="footer-contact">
                        <a href="tel:+639550295738"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z" /></svg>0955 029 5738</a>
                        <a href="mailto:rac.antique2007@gmail.com"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 6L2 7" /></svg>rac.antique2007@gmail.com</a>
                        <a href="/#contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>See this week’s clinic hours</a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <span>© <?= date("Y") ?> Rebadulla Animal Care.</span>
                <a href="/owner/book.php">Book an appointment ↗</a>
            </div>
        </footer>
<?php endif; ?>
<?php if (!$auth): ?>
        <a class="to-top" href="#top" aria-label="Back to top" data-to-top>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" /></svg>
        </a>
<?php endif; ?>
    </body>
</html>
<?php
}

function notices(?string $error = null, ?string $success = null): void
{
    $items = [];
    if ($error) {
        $items[] = ["error", $error, 6000, "x-circle", "alert"];
    }
    if ($success ?? isset($_GET["saved"])) {
        $items[] = ["success", $success ?? "Your changes have been saved.", 2000, "check-circle", "status"];
    }
    if (!$items) {
        return;
    }
    echo '<div class="toast-stack">';
    foreach ($items as [$tone, $text, $time, $name, $role]) {
        echo '<div class="toast toast-' . $tone . '" role="' . $role . '" data-toast="' . $time . '">' .
            '<span class="toast-icon">' . icon($name) . "</span><p>" . e($text) . "</p>" .
            '<button type="button" class="toast-close" aria-label="Dismiss" data-toast-close>' . icon("x-circle") . "</button>" .
            '<i class="toast-timer" aria-hidden="true"></i></div>';
    }
    echo "</div>";
}

function page_heading(
    string $heading,
    string $description,
    string $action = "",
): void {
    $path = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH) ?: "";
    $name = str_ends_with($path, "/appointment.php") ? "file" : "folder";
    foreach (portal_nav(current_user()["role"] ?? "owner") as $items) {
        $name = $items[$path][2] ?? $name;
    }
    echo '<div class="page-heading"><div class="page-title"><span class="title-icon title-icon-lg">' .
        icon($name) .
        "</span><div><h1>" .
        e($heading) .
        "</h1>" .
        ($description !== "" ? "<p>" . e($description) . "</p>" : "") .
        "</div></div>" .
        ($action !== "" ? '<div class="page-actions">' . $action . "</div>" : "") .
        "</div>";
}

// Section heading with a small icon tile, e.g. section_title("Today’s schedule", "calendar").
function section_title(string $text, string $name): string
{
    return '<h2 class="section-title"><span class="title-icon">' . icon($name) . "</span>" . e($text) . "</h2>";
}

function status_badge(string $status): string
{
    return '<span class="status status-' .
        e($status) .
        '"><span aria-hidden="true"></span>' .
        e(ucfirst($status)) .
        "</span>";
}

function empty_state(
    string $heading,
    string $description,
    string $action = "",
): void {
    echo '<div class="empty-state"><div class="empty-symbol" aria-hidden="true"></div><h2>' .
        e($heading) .
        "</h2><p>" .
        e($description) .
        "</p>" .
        $action .
        "</div>";
}

function appointment_table(
    array $records,
    string $role,
    bool $ownerColumn = true,
): void {
    if (!$records) {
        empty_state(
            "No appointments here yet",
            "Appointments will appear here as visits are booked.",
        );
        return;
    }
    // Pet photos for every row in one query (pet id => photo version).
    $petIds = array_values(array_unique(array_map(fn($r) => (int) ($r["pet_id"] ?? 0), $records)));
    $photos = [];
    if (array_filter($petIds)) {
        try {
            foreach (
                rows(
                    "SELECT pet_id, extract(epoch FROM updated_at)::bigint AS v FROM pet_photos WHERE pet_id IN (" .
                        implode(",", array_fill(0, count($petIds), "?")) .
                        ")",
                    $petIds,
                )
                as $photo
            ) {
                $photos[(int) $photo["pet_id"]] = (int) $photo["v"];
            }
        } catch (Throwable) {
            $photos = [];
        }
    } ?>
<div class="table-scroll">
    <table class="responsive-table">
        <thead>
            <tr>
                <th>Pet & reference</th>
                <?php if (
    $role === "admin" &&
    $ownerColumn
): ?>
                <th>Owner</th>
                <?php endif; ?>
                <th>Service</th>
                <th>Schedule</th>
                <th>Status</th>
                <th><span class="sr-only">Open record</span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $record): ?>
            <tr>
                <td class="cell-pet">
                    <div class="pet-cell">
                        <span class="pet-thumb" aria-hidden="true"><?php if (isset($photos[(int) ($record["pet_id"] ?? 0)])): ?><img src="/pet-photo.php?id=<?= (int) $record["pet_id"] ?>&amp;v=<?= $photos[(int) $record["pet_id"]] ?>" alt="" loading="lazy" /><?php else: ?><?= e(mb_strtoupper(mb_substr($record["pet_name"], 0, 1))) ?><?php endif; ?></span>
                        <span>
                            <strong><?= e($record["pet_name"]) ?></strong>
                            <small class="mono"><?= e($record["booking_ref"]) ?></small>
                        </span>
                    </div>
                </td>
                <?php if ($role === "admin" && $ownerColumn): ?>
                <td data-label="Owner">
                    <?= e(
    $record["owner_name"],
) ?>
                    <small><?= e($record["phone"]) ?></small>
                </td>
                <?php endif; ?>

                <td data-label="Service"><?= e($record["service_name"]) ?></td>
                <td class="no-wrap" data-label="Schedule">
                    <?= e(
    schedule_label($record["appt_date"]),
) ?>
                    <small><?= e(
    (new DateTimeImmutable($record["appt_time"]))->format("g:i A"),
) ?></small>
                </td>
                <td class="cell-status"><?= status_badge(
            $record["status"],
        ) ?></td>
                <td class="cell-link">
                    <a
                        class="record-link"
                        href="/<?= $role ?>/appointment.php?id=<?= (int) $record[
    "id"
] ?>"
                        aria-label="Open <?= e($record["booking_ref"]) ?>"
                    >
                        <?= icon("eye") ?>View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>

        </tbody>
    </table>
</div>
<?php
}

function service_options(array $services, mixed $selected = ""): void
{
    echo '<option value="">Choose a service</option>';
    foreach ($services as $service) {
        echo '<option value="' .
            (int) $service["id"] .
            '" ' .
            ((string) $selected === (string) $service["id"] ? "selected" : "") .
            ">" .
            e($service["name"]) .
            " · " .
            e(money($service["price_from"])) .
            "</option>";
    }
}

function time_fields(?array $record = null): void
{
    ?>
<div class="form-row">
    <label>
        Preferred date
        <input
            type="date"
            name="appt_date"
            data-slot-date
            min="<?= date(
    "Y-m-d",
) ?>"
            value="<?= e($_POST["appt_date"] ?? ($record["appt_date"] ?? "")) ?>"
            required
        />
    </label>
    <label>
        Available time
        <select name="appt_time" data-slot-time data-selected="<?= e(
        $_POST["appt_time"] ?? "",
    ) ?>" required>
            <option value="">Choose a date first</option>
        </select>
    </label>
</div>
<p class="field-help" data-slot-feedback role="status">
    Times are shown in Philippine time. Each visit uses a 30-minute slot.
</p>
<?php
}

function pagination(int $page, bool $hasNext): void
{
    if ($page === 0 && !$hasNext) {
        return; // Only one page: nothing to page through.
    }
    $parameters = $_GET;
    echo '<nav class="pagination" aria-label="Results pages">';
    if ($page > 0) {
        $parameters["page"] = $page - 1;
        echo '<a class="button button-secondary button-small" href="?' .
            e(http_build_query($parameters)) .
            '">← Previous</a>';
    }
    echo "<span>Page " . ($page + 1) . "</span>";
    if ($hasNext) {
        $parameters["page"] = $page + 1;
        echo '<a class="button button-secondary button-small" href="?' .
            e(http_build_query($parameters)) .
            '">Next →</a>';
    }
    echo "</nav>";
}

