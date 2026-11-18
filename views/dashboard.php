<?php
$title = "Overview";
$admin = $user["role"] === "admin";
$today = date("Y-m-d");
if ($admin) {
    $metrics = row(
        "SELECT count(*) FILTER (WHERE status = 'pending') AS pending,
        count(*) FILTER (WHERE status = 'proposed') AS proposed,
        count(*) FILTER (WHERE appt_date = ? AND status = 'confirmed') AS today,
        count(*) FILTER (WHERE appt_date = ? AND status = 'completed') AS completed FROM appointments",
        [$today, $today],
    );
    $records = appointment_list(
        $user,
        "a.appt_date = ? AND a.status IN ('confirmed','completed','missed')",
        [$today],
        0,
        "schedule",
    );
    page_heading(
        "Today at the clinic",
        date("l, F j"),
        '<a class="button" href="/admin/new-appointment.php">' . icon("plus") . "New booking</a>",
    );
} else {
    $metrics = row(
        "SELECT count(*) FILTER (WHERE a.status IN ('pending','proposed','confirmed')) AS upcoming,
        count(*) FILTER (WHERE a.status = 'proposed') AS proposed, count(*) FILTER (WHERE a.status = 'completed') AS completed
        FROM appointments a JOIN pets p ON p.id = a.pet_id WHERE p.owner_id = ?",
        [(int) $user["id"]],
    );
    $pets = rows("SELECT * FROM pets WHERE owner_id = ? ORDER BY name", [
        (int) $user["id"],
    ]);
    $records = appointment_list(
        $user,
        "a.status IN ('pending','proposed','confirmed')",
        [],
        0,
        "upcoming",
    );
    page_heading(
        "Hi, " . explode(" ", $user["full_name"])[0],
        "Your pets and their next visits.",
        '<a class="button" href="/owner/book.php">' . icon("plus") . "Book a visit</a>",
    );
}
notices($error);
?>

<div class="metrics">
<?php
$cards = $admin
    ? [
        ["/admin/requests.php", "blue", "inbox", (int) $metrics["pending"], "Pending requests"],
        ["/admin/records.php?status=proposed", "violet", "repeat", (int) $metrics["proposed"], "Waiting for owner"],
        ["/admin/schedule.php", "sky", "calendar-check", (int) $metrics["today"], "Confirmed today"],
        ["/admin/reports.php", "green", "check-circle", (int) $metrics["completed"], "Completed today"],
    ]
    : [
        ["/owner/pets.php", "blue", "paw", count($pets), "My pets"],
        ["/owner/appointments.php", "sky", "calendar", (int) $metrics["upcoming"], "Upcoming visits"],
        ["/owner/appointments.php?tab=proposed", "violet", "repeat", (int) $metrics["proposed"], "Needs your reply"],
        ["/owner/appointments.php?tab=past", "green", "check-circle", (int) $metrics["completed"], "Completed visits"],
    ];
foreach ($cards as [$href, $tone, $name, $value, $label]): ?>
    <a class="stat stat-<?= $tone ?>" href="<?= e($href) ?>">
        <span class="stat-icon"><?= icon($name) ?></span>
        <strong><?= $value ?></strong>
        <span><?= e($label) ?></span>
    </a>
<?php endforeach; ?>
</div>
<?php if (
    !$admin &&
    (int) $metrics["proposed"] > 0
): ?>
<div class="notice">
    The clinic proposed a new appointment time. Open the visit to accept it or request a change.
</div>
<?php endif; ?>

<section class="data-section">
    <div class="section-top">
        <?= section_title($admin ? "Today’s schedule" : "Upcoming visits", "calendar") ?>
        <a class="text-link" href="/<?= $user[
    "role"
] ?>/<?= $admin
    ? "schedule"
    : "appointments" ?>.php">View all ↗</a>
    </div>
    <?php appointment_table(
    array_slice($records, 0, 8),
    $user["role"],
); ?>
</section>
<?php if (
    !$admin &&
    !$pets
): ?>
<section class="getting-started">
    <div>
        <span class="eyebrow">BEFORE THEIR FIRST VISIT</span>
        <h2>Let’s meet your pet.</h2>
        <p>Add your pet’s details to start booking appointments.</p>
    </div>
    <a class="button button-secondary" href="/owner/pets.php">Add your first pet ↗</a>
</section>
<?php endif; ?>

