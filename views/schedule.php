<?php
$title = "Schedule";
$date = (string) ($_GET["date"] ?? date("Y-m-d"));
if (!valid_date($date)) {
    throw new ValidationError("Choose a valid schedule date.");
}
$records = rows(
    "SELECT a.*, p.name AS pet_name, p.species, u.full_name AS owner_name, u.phone, s.name AS service_name, s.max_per_slot,
    (SELECT extract(epoch FROM ph.updated_at)::bigint FROM pet_photos ph WHERE ph.pet_id = a.pet_id) AS photo_version
    FROM appointments a JOIN pets p ON p.id = a.pet_id JOIN users u ON u.id = p.owner_id
    JOIN services s ON s.id = a.service_id WHERE a.appt_date = ? AND a.status IN ('confirmed','completed','missed') ORDER BY a.appt_time, a.id",
    [$date],
);
$groups = [];
foreach ($records as $record) {
    $groups[$record["appt_time"]][] = $record;
}
$closure = row("SELECT reason FROM clinic_closures WHERE date = ?", [$date]);
$day = new DateTimeImmutable($date);
$isToday = $date === date("Y-m-d");
$done = count(array_filter($records, fn($r) => $r["status"] === "completed"));
page_heading(
    "Schedule",
    "",
    '<a class="button" href="/admin/new-appointment.php">' . icon("plus") . "New booking</a>",
);
notices($error);
?>

<section class="day-nav">
    <a class="day-step" href="?date=<?= $day->modify("-1 day")->format("Y-m-d") ?>" aria-label="Previous day"><?= icon("arrow-left") ?></a>
    <div class="day-title">
        <span class="appt-date" aria-hidden="true"><span><?= $day->format("M") ?></span><strong><?= $day->format("j") ?></strong></span>
        <div>
            <h2><?= e($day->format("l")) ?><?= $isToday ? ' <span class="today-pill">Today</span>' : "" ?></h2>
            <p><?= e($day->format("F j, Y")) ?> · <?= count($records) ?> <?= count($records) === 1 ? "visit" : "visits" ?><?= $records ? " · " . $done . " done" : "" ?></p>
        </div>
    </div>
    <form method="get" class="day-pick">
        <label><span class="sr-only">Go to date</span><input type="date" name="date" value="<?= e($date) ?>" required /></label>
        <button class="button button-secondary button-small" type="submit">Go</button>
        <?php if (!$isToday): ?><a class="button button-secondary button-small" href="/admin/schedule.php">Today</a><?php endif; ?>
    </form>
    <a class="day-step" href="?date=<?= $day->modify("+1 day")->format("Y-m-d") ?>" aria-label="Next day"><?= icon("arrow-left", "flip") ?></a>
</section>

<?php if ($closure): ?>
<div class="appt-guide appt-guide-cancelled"><span><?= icon("x-circle") ?></span><p>Clinic closed: <?= e($closure["reason"]) ?>. Reschedule any visits below.</p></div>
<?php endif; ?>

<?php if (!$groups):
    empty_state("A free day", "No confirmed visits on this date.", '<a class="button" href="/admin/new-appointment.php">' . icon("plus") . "Add a booking</a>");
endif; ?>

<div class="schedule-list">
<?php foreach ($groups as $time => $visits):
    $counts = [];
    foreach ($visits as $visit) {
        if ($visit["status"] === "confirmed") {
            $counts[$visit["service_id"]] = ($counts[$visit["service_id"]] ?? 0) + 1;
        }
    }
    ?>
    <section class="slot">
        <div class="slot-time"><?= e((new DateTimeImmutable($time))->format("g:i")) ?><small><?= e((new DateTimeImmutable($time))->format("A")) ?></small></div>
        <div class="slot-visits">
<?php foreach ($visits as $visit):
    $past = new DateTimeImmutable($visit["appt_date"] . " " . $visit["appt_time"]) < new DateTimeImmutable(); ?>
            <article class="visit-card visit-<?= e($visit["status"]) ?>">
                <span class="pet-thumb" aria-hidden="true"><?php if ($visit["photo_version"]): ?><img src="/pet-photo.php?id=<?= (int) $visit["pet_id"] ?>&amp;v=<?= (int) $visit["photo_version"] ?>" alt="" loading="lazy" /><?php else: ?><?= e(mb_strtoupper(mb_substr($visit["pet_name"], 0, 1))) ?><?php endif; ?></span>
                <div class="visit-info">
                    <div class="visit-top">
                        <h3><?= e($visit["pet_name"]) ?> <small><?= e($visit["species"]) ?></small></h3>
                        <?= status_badge($visit["status"]) ?>
                    </div>
                    <p class="visit-service"><?= icon("tag") ?><?= e($visit["service_name"]) ?></p>
                    <p class="visit-owner"><?= icon("user") ?><?= e($visit["owner_name"]) ?><?php if ($visit["phone"]): ?> · <a href="tel:<?= e(preg_replace('/[^0-9+]/', "", $visit["phone"])) ?>"><?= e($visit["phone"]) ?></a><?php endif; ?></p>
                    <p class="visit-slots"><?= (int) ($counts[$visit["service_id"]] ?? 0) ?> of <?= (int) $visit["max_per_slot"] ?> places used · <span class="mono"><?= e($visit["booking_ref"]) ?></span></p>
                </div>
                <div class="visit-actions">
<?php if ($visit["status"] === "confirmed" && $past): ?>
<?php foreach (["complete" => ["Completed", "check-circle", ""], "miss" => ["Missed", "x-circle", "button-secondary"]] as $action => [$label, $name, $class]): ?>
                    <form method="post" action="/admin/appointment.php?id=<?= (int) $visit["id"] ?>">
                        <?= csrf_field() . request_key() ?>
                        <input type="hidden" name="action" value="<?= $action ?>" />
                        <input type="hidden" name="version" value="<?= e($visit["updated_at"]) ?>" />
                        <button class="button button-small <?= $class ?>" type="submit"><?= icon($name) ?><?= $label ?></button>
                    </form>
<?php endforeach; ?>
<?php endif; ?>
                    <a class="button button-secondary button-small" href="/admin/appointment.php?id=<?= (int) $visit["id"] ?>"><?= icon("eye") ?>Open</a>
                </div>
            </article>
<?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
</div>
