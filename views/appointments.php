<?php
$title = "My appointments";
$tab = in_array($_GET["tab"] ?? "", ["past", "proposed"], true)
    ? $_GET["tab"]
    : "upcoming";
$page = max(0, (int) ($_GET["page"] ?? 0));
$filter = match ($tab) {
    "past" => "a.status IN ('completed','missed','cancelled')",
    "proposed" => "a.status = 'proposed'",
    default => "a.status IN ('pending','proposed','confirmed')",
};
$records = appointment_list(
    $user,
    $filter,
    [],
    $page,
    $tab === "past" ? "recent" : "upcoming",
);
page_heading(
    "My appointments",
    "",
    '<a class="button" href="/owner/book.php">' . icon("plus") . "Book a visit</a>",
);
notices($error);
?>

<nav class="tabs" aria-label="Appointment filters">
    <?php foreach (
    [
        "upcoming" => "Upcoming",
        "proposed" => "Needs reply",
        "past" => "Past visits",
    ]
    as $key => $label
): ?>
    <a href="?tab=<?= $key ?>" <?= $tab === $key
    ? 'class="active" aria-current="page"'
    : "" ?>><?= $label ?></a>
    <?php endforeach; ?>
</nav>
<?php if (
    $tab === "proposed"
): ?>
<div class="notice">
    The clinic proposed a new time. Open the appointment to accept it or request a change.
</div>
<?php endif; ?>

<section class="data-section"><?php appointment_table(
    array_slice($records, 0, 50),
    "owner",
); ?></section>
<?php pagination($page, count($records) > 50); ?>

