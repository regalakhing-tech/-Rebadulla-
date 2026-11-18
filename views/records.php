<?php
$title = "All records";
$term = trim((string) ($_GET["q"] ?? ""));
$status = (string) ($_GET["status"] ?? "");
$serviceId = (string) ($_GET["service"] ?? "");
$start = (string) ($_GET["start"] ?? "");
$end = (string) ($_GET["end"] ?? "");
$page = max(0, (int) ($_GET["page"] ?? 0));
$where = [];
$parameters = [];
if ($term !== "") {
    $where[] =
        "(u.full_name ILIKE ? OR p.name ILIKE ? OR a.booking_ref ILIKE ?)";
    array_push(
        $parameters,
        "%" . $term . "%",
        "%" . $term . "%",
        "%" . $term . "%",
    );
}
if (
    in_array(
        $status,
        [
            "pending",
            "proposed",
            "confirmed",
            "completed",
            "missed",
            "cancelled",
        ],
        true,
    )
) {
    $where[] = "a.status = ?";
    $parameters[] = $status;
}
if ($serviceId !== "") {
    $where[] = "a.service_id = ?";
    $parameters[] = positive_id($serviceId);
}
if ($start !== "" || $end !== "") {
    if (
        ($start !== "" && !valid_date($start)) ||
        ($end !== "" && !valid_date($end))
    ) {
        throw new ValidationError("Choose valid dates.");
    }
    if ($start !== "") {
        $where[] = "a.appt_date >= ?";
        $parameters[] = $start;
    }
    if ($end !== "") {
        $where[] = "a.appt_date <= ?";
        $parameters[] = $end;
    }
}
$records = appointment_list(
    $user,
    implode(" AND ", $where),
    $parameters,
    $page,
);
page_heading(
    "All records",
    "Search by pet, owner, or booking reference.",
);
notices($error);
?>

<form method="get" class="records-search">
    <label class="search-field records-q">
        <span class="sr-only">Search records</span>
        <input type="search" name="q" value="<?= e($term) ?>" placeholder="Search pet, owner, or reference…" />
    </label>
    <input type="hidden" name="status" value="<?= e($status) ?>" />
    <details class="records-more"<?= $serviceId !== "" || $start !== "" || $end !== "" ? " open" : "" ?>>
        <summary><?= icon("settings") ?>Filters</summary>
        <div class="records-more-panel">
            <label>
                Service
                <select name="service">
                    <option value="">All services</option>
                    <?php foreach (services() as $service): ?>
                    <option value="<?= (int) $service["id"] ?>" <?= (string) $service["id"] === $serviceId ? "selected" : "" ?>><?= e($service["name"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>From<input type="date" name="start" value="<?= e($start) ?>" /></label>
            <label>To<input type="date" name="end" value="<?= e($end) ?>" /></label>
        </div>
    </details>
    <button class="button" type="submit"><?= icon("eye") ?>Search</button>
    <?php if ($term !== "" || $status !== "" || $serviceId !== "" || $start !== "" || $end !== ""): ?>
    <a href="/admin/records.php" class="button button-secondary">Clear</a>
    <?php endif; ?>
</form>
<nav class="status-chips" aria-label="Filter by status">
<?php foreach (["" => "All", "pending" => "Pending", "proposed" => "Proposed", "confirmed" => "Confirmed", "completed" => "Completed", "missed" => "Missed", "cancelled" => "Cancelled"] as $value => $label):
    $link = array_filter(["q" => $term, "status" => $value, "service" => $serviceId, "start" => $start, "end" => $end], fn($v) => $v !== ""); ?>
    <a href="?<?= e(http_build_query($link)) ?>" class="chip-<?= $value ?: "all" ?><?= $status === $value ? " active" : "" ?>"<?= $status === $value ? ' aria-current="true"' : "" ?>><?= $label ?></a>
<?php endforeach; ?>
</nav>
<section class="data-section">
    <div class="section-top">
        <?= section_title($status !== "" ? ucfirst($status) . " appointments" : "All appointments", "folder") ?>
        <span class="appt-count"><?= count($records) > 50 ? "50+" : count($records) ?> found</span>
    </div>
    <?php appointment_table(array_slice($records, 0, 50), "admin"); ?>
</section>
<?php pagination($page, count($records) > 50); ?>
