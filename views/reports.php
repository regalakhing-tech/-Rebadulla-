<?php
$title = "Reports";
[$start, $end] = report_period();
["byStatus" => $byStatus, "byService" => $byService, "byDate" => $byDate, "total" => $total] = report_data($start, $end);
$counts = array_column($byStatus, "total", "status");
$query = fn(string $from, string $to): string => "?" . http_build_query(["start" => $from, "end" => $to]);
$today = new DateTimeImmutable("today");
$presets = [
    "This month" => [$today->format("Y-m-01"), $today->format("Y-m-t")],
    "Last month" => [$today->modify("first day of last month")->format("Y-m-d"), $today->modify("last day of last month")->format("Y-m-d")],
    "Last 7 days" => [$today->modify("-6 days")->format("Y-m-d"), $today->format("Y-m-d")],
    "This year" => [$today->format("Y-01-01"), $today->format("Y-12-31")],
];
page_heading(
    "Reports",
    schedule_label($start) . " – " . schedule_label($end),
    '<a class="button" href="/admin/reports-pdf.php' . e($query($start, $end)) . '">' . icon("download") . "Download PDF</a>",
);
$tones = ["pending" => "amber", "proposed" => "violet", "confirmed" => "blue", "completed" => "green", "missed" => "red", "cancelled" => "slate"];
$maxService = max([1, ...array_map(fn($r) => (int) $r["total"], $byService)]);
$maxDate = max([1, ...array_map(fn($r) => (int) $r["total"], $byDate)]);
?>

<section class="report-range">
    <nav class="report-presets" aria-label="Quick date ranges">
<?php foreach ($presets as $label => [$from, $to]): ?>
        <a href="<?= e($query($from, $to)) ?>"<?= $from === $start && $to === $end ? ' class="active" aria-current="true"' : "" ?>><?= e($label) ?></a>
<?php endforeach; ?>
    </nav>
    <form method="get" class="report-dates">
        <label><span class="sr-only">From</span><input type="date" name="start" value="<?= e($start) ?>" required /></label>
        <span class="report-arrow" aria-hidden="true">→</span>
        <label><span class="sr-only">To</span><input type="date" name="end" value="<?= e($end) ?>" required /></label>
        <button class="button button-small" type="submit"><?= icon("check") ?>Apply</button>
    </form>
</section>

<div class="metrics">
<?php foreach (
    [
        ["blue", "calendar", $total, "Appointments"],
        ["green", "check-circle", (int) ($counts["completed"] ?? 0), "Completed"],
        ["violet", "hourglass", (int) ($counts["pending"] ?? 0) + (int) ($counts["proposed"] ?? 0) + (int) ($counts["confirmed"] ?? 0), "Upcoming"],
        ["sky", "x-circle", (int) ($counts["missed"] ?? 0) + (int) ($counts["cancelled"] ?? 0), "Missed / cancelled"],
    ]
    as [$tone, $name, $value, $label]
): ?>
    <div class="stat stat-<?= $tone ?>">
        <span class="stat-icon"><?= icon($name) ?></span>
        <strong><?= $value ?></strong>
        <span><?= e($label) ?></span>
    </div>
<?php endforeach; ?>
</div>

<?php if (!$total):
    empty_state("No visits in this period", "Pick another date range to see totals.");
else: ?>
<div class="report-grid">
    <section class="data-section">
        <div class="section-top"><?= section_title("By service", "tag") ?></div>
        <ul class="bar-list">
<?php foreach ($byService as $item): ?>
            <li>
                <div class="bar-label">
                    <strong><?= e($item["name"]) ?></strong>
                    <span><?= (int) $item["total"] ?></span>
                </div>
                <svg class="bar-track" viewBox="0 0 100 10" preserveAspectRatio="none" aria-hidden="true"><rect width="<?= round((int) $item["total"] / $maxService * 100, 1) ?>" height="10" /></svg>
                <div class="bar-tags">
                    <span class="tag-green"><?= (int) $item["completed"] ?> completed</span>
                    <span class="tag-red"><?= (int) $item["missed"] ?> missed</span>
                    <span class="tag-slate"><?= (int) $item["cancelled"] ?> cancelled</span>
                </div>
            </li>
<?php endforeach; ?>
        </ul>
    </section>

    <section class="data-section">
        <div class="section-top"><?= section_title("By status", "check-circle") ?></div>
        <svg class="status-stack" viewBox="0 0 100 10" preserveAspectRatio="none" aria-hidden="true">
<?php $x = 0;
foreach ($byStatus as $item):
    $w = round((int) $item["total"] / $total * 100, 2); ?>
            <rect class="fill-<?= $tones[$item["status"]] ?? "slate" ?>" x="<?= $x ?>" width="<?= $w ?>" height="10" />
<?php $x += $w;
endforeach; ?>
        </svg>
        <ul class="status-legend">
<?php foreach ($byStatus as $item): ?>
            <li>
                <span class="dot fill-<?= $tones[$item["status"]] ?? "slate" ?>"></span>
                <?= e(ucfirst($item["status"])) ?>
                <strong><?= (int) $item["total"] ?></strong>
                <small><?= round((int) $item["total"] / $total * 100) ?>%</small>
            </li>
<?php endforeach; ?>
        </ul>
    </section>

    <section class="data-section report-wide">
        <div class="section-top">
            <?= section_title("By date", "calendar") ?>
            <span class="appt-count"><?= count($byDate) ?> <?= count($byDate) === 1 ? "day" : "days" ?> with visits</span>
        </div>
        <div class="day-chart">
<?php foreach ($byDate as $item):
    $day = new DateTimeImmutable($item["appt_date"]); ?>
            <a class="day-bar" href="/admin/schedule.php?date=<?= e($item["appt_date"]) ?>" title="<?= e($day->format("D, M j")) ?>: <?= (int) $item["total"] ?>">
                <b><?= (int) $item["total"] ?></b>
<?php $h = round((int) $item["total"] / $maxDate * 100, 1); ?>
                <svg viewBox="0 0 10 100" preserveAspectRatio="none" aria-hidden="true"><rect y="<?= 100 - $h ?>" width="10" height="<?= $h ?>" /></svg>
                <small><?= e($day->format("M j")) ?></small>
            </a>
<?php endforeach; ?>
        </div>
    </section>
</div>
<?php endif; ?>
