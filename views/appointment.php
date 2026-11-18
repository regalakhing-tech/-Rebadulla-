<?php
$record = appointment(positive_id($_GET["id"] ?? 0), $user);
$title = $record["booking_ref"];
$admin = $user["role"] === "admin";
$status = $record["status"];
$final = in_array($status, FINAL_STATUSES, true);
$history = rows(
    'SELECT h.*, u.full_name AS actor_name, u.role AS actor_role FROM appointment_history h
    JOIN users u ON u.id = h.changed_by WHERE appointment_id = ? ORDER BY h.changed_at, h.id',
    [(int) $record["id"]],
);
$photo = row("SELECT extract(epoch FROM updated_at)::bigint AS v FROM pet_photos WHERE pet_id = ?", [(int) $record["pet_id"]]);
$when = new DateTimeImmutable($record["appt_date"] . " " . $record["appt_time"]);
$source = [
    "online" => ["send", "Online request"],
    "phone" => ["phone", "Phone booking"],
    "walk_in" => ["user", "Walk-in"],
][$record["source"]] ?? ["file", ucfirst($record["source"])];

// One short line that says what happens next, for this status and this reader.
$guide = [
    "pending" => $admin
        ? ["inbox", "New request. Confirm it or propose another time."]
        : ["hourglass", "Waiting for the clinic to confirm your time."],
    "proposed" => $admin
        ? ["repeat", "New time sent. Waiting for the owner to accept."]
        : ["repeat", "The clinic suggested this new time. Accept it or ask for a change."],
    "confirmed" => $admin
        ? ["calendar-check", "Confirmed. Mark it completed or missed after the visit."]
        : ["calendar-check", "You're booked. See you on " . $when->format("D, M j") . " at " . $when->format("g:i A") . "."],
    "completed" => ["check-circle", "Visit completed. This record stays in the pet's history."],
    "missed" => ["x-circle", "This visit was marked as missed."],
    "cancelled" => ["x-circle", "This appointment was cancelled. The record is kept."],
][$status];

// Progress steps: requested, confirmed, visited.
$stage = ["pending" => 1, "proposed" => 1, "confirmed" => 2, "completed" => 3][$status] ?? null;
$steps = [
    ["send", "Requested"],
    [$status === "proposed" ? "repeat" : "check-circle", $status === "proposed" ? "New time proposed" : "Confirmed"],
    ["paw", "Visited"],
];

$eventIcons = [
    "created" => ["send", "blue"],
    "confirmed" => ["check-circle", "green"],
    "accepted" => ["check-circle", "green"],
    "proposed" => ["repeat", "violet"],
    "change_requested" => ["edit", "amber"],
    "rescheduled" => ["calendar", "violet"],
    "completed" => ["check-circle", "green"],
    "missed" => ["x-circle", "red"],
    "cancelled" => ["x-circle", "red"],
];
notices($error ?: ($admin && isset($_GET["failed"]) ? "Saved, but the email was not sent. Retry it in Email outbox." : null));
?>

<section class="appt-hero appt-<?= e($status) ?>">
    <div class="appt-date" aria-hidden="true">
        <span><?= $when->format("M") ?></span>
        <strong><?= $when->format("j") ?></strong>
        <small><?= $when->format("D") ?></small>
    </div>
    <div class="appt-main">
        <div class="appt-ref">
            <span class="mono"><?= e($record["booking_ref"]) ?></span>
            <?= status_badge($status) ?>
        </div>
        <h1><?= e($record["service_name"]) ?></h1>
        <ul class="appt-meta">
            <li><?= icon("clock") ?><?= e(schedule_label($record["appt_date"], $record["appt_time"])) ?></li>
            <li><?= icon("paw") ?><?= e($record["pet_name"]) ?> · <?= e($record["species"]) ?></li>
            <li><?= icon($source[0]) ?><?= e($source[1]) ?></li>
        </ul>
    </div>
    <span class="appt-pet" aria-hidden="true">
        <?php if ($photo): ?>
        <img src="/pet-photo.php?id=<?= (int) $record["pet_id"] ?>&amp;v=<?= (int) $photo["v"] ?>" alt="" />
        <?php else: ?>
        <?= e(mb_strtoupper(mb_substr($record["pet_name"], 0, 1))) ?>
        <?php endif; ?>
    </span>
</section>

<?php if ($stage): ?>
<ol class="appt-steps" aria-label="Appointment progress">
<?php foreach ($steps as $index => [$name, $label]):
    $done = $index < $stage;
    $current = $index === $stage;
    ?>
    <li class="<?= $done ? "is-done" : ($current ? "is-current" : "") ?><?= $status === "proposed" && $index === 1 ? " is-proposed" : "" ?>"<?= $current ? ' aria-current="step"' : "" ?>>
        <span><?= icon($done ? "check" : $name) ?></span>
        <?= e($label) ?>
    </li>
<?php endforeach; ?>
</ol>
<?php endif; ?>

<div class="appt-guide appt-guide-<?= e($status) ?>" role="status">
    <span><?= icon($guide[0]) ?></span>
    <p><?= e($guide[1]) ?></p>
</div>

<div class="record-layout appt-layout">
    <div>
        <section class="data-section">
            <div class="section-top"><?= section_title("Visit details", "file") ?></div>
            <dl class="appt-facts">
                <div><dt><span><?= icon("tag") ?></span>Service</dt><dd><?= e($record["service_name"]) ?></dd></div>
                <div><dt><span><?= icon("calendar") ?></span>Date</dt><dd><?= e($when->format("l, F j, Y")) ?></dd></div>
                <div><dt><span><?= icon("clock") ?></span>Time</dt><dd><?= e($when->format("g:i A")) ?> <small>30-minute slot</small></dd></div>
                <div><dt><span><?= icon("wallet") ?></span>Starting price</dt><dd><?= e(money($record["price_from"])) ?></dd></div>
                <div><dt><span><?= icon("paw") ?></span>Pet</dt><dd><?= e($record["pet_name"]) ?> <small><?= e($record["species"]) ?></small></dd></div>
                <div><dt><span><?= icon($source[0]) ?></span>Booked by</dt><dd><?= e($source[1]) ?></dd></div>
            </dl>
            <div class="appt-notes">
                <p class="appt-notes-label"><?= icon("file") ?>Booking notes</p>
                <p class="preserve-lines<?= $record["owner_notes"] ? "" : " muted" ?>"><?= e($record["owner_notes"] ?: "No notes added.") ?></p>
            </div>
        </section>

<?php if ($admin): ?>
        <section class="data-section appt-owner">
            <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($record["owner_name"], 0, 1))) ?></span>
            <div>
                <p class="appt-owner-label">Pet owner</p>
                <strong><?= e($record["owner_name"]) ?></strong>
                <small><?= e(implode(" · ", array_filter([$record["phone"], $record["owner_email"]]))) ?></small>
            </div>
            <div class="appt-owner-actions">
                <?php if ($record["phone"]): ?>
                <a class="button button-secondary button-small" href="tel:<?= e(preg_replace('/[^0-9+]/', "", $record["phone"])) ?>"><?= icon("phone") ?>Call</a>
                <?php endif; ?>
                <?php if ($record["owner_email"]): ?>
                <a class="button button-secondary button-small" href="mailto:<?= e($record["owner_email"]) ?>"><?= icon("mail") ?>Email</a>
                <?php endif; ?>
            </div>
        </section>
<?php endif; ?>

        <section class="data-section">
            <div class="section-top">
                <?= section_title("History", "history") ?>
                <span class="appt-count"><?= count($history) ?> <?= count($history) === 1 ? "event" : "events" ?></span>
            </div>
            <ol class="appt-timeline">
<?php foreach (array_reverse($history) as $event):
    [$name, $tone] = $eventIcons[$event["action"]] ?? ["history", "blue"];
    $moved = $event["old_date"] && ($event["old_date"] !== $event["new_date"] || $event["old_time"] !== $event["new_time"]);
    ?>
                <li>
                    <span class="appt-event-icon tone-<?= $tone ?>"><?= icon($name) ?></span>
                    <div>
                        <div class="appt-event-top">
                            <strong><?= e(ucfirst(str_replace("_", " ", $event["action"]))) ?></strong>
                            <time><?= e(timestamp_label($event["changed_at"])) ?></time>
                        </div>
                        <p class="appt-event-by">by <?= e($event["actor_name"]) ?> <span>· <?= $event["actor_role"] === "admin" ? "clinic" : "owner" ?></span></p>
<?php if ($moved): ?>
                        <div class="history-change">
                            <s><?= e(schedule_label($event["old_date"], $event["old_time"])) ?></s>
                            <span aria-hidden="true">→</span>
                            <strong><?= e(schedule_label($event["new_date"], $event["new_time"])) ?></strong>
                        </div>
<?php endif; ?>
<?php if ($event["note"]): ?>
                        <p class="event-note preserve-lines"><?= e($event["note"]) ?></p>
<?php endif; ?>
                    </div>
                </li>
<?php endforeach; ?>
            </ol>
        </section>

<?php if ($admin):
    $visits = appointment_list($user, "a.pet_id = ? AND a.id <> ?", [(int) $record["pet_id"], (int) $record["id"]]);
    if ($visits): ?>
        <section class="data-section">
            <div class="section-top"><?= section_title($record["pet_name"] . "’s other visits", "paw") ?></div>
            <?php appointment_table(array_slice($visits, 0, 10), "admin", false); ?>
        </section>
<?php endif;
endif; ?>
    </div>

    <aside class="form-panel appointment-actions appt-actions">
<?php if ($final): ?>
        <div class="appt-closed appt-closed-<?= e($status) ?>">
            <span><?= icon($status === "completed" ? "check-circle" : "x-circle") ?></span>
            <h2>Visit <?= e($status) ?></h2>
            <p>No more changes can be made. The record and its history are saved.</p>
        </div>
<?php else:
    $simple = [];
    if ($admin && in_array($status, ["pending", "proposed"], true)) {
        $simple["confirm"] = ["Confirm appointment", "check-circle", ""];
    }
    if (!$admin && $status === "proposed") {
        $simple["accept"] = ["Accept new time", "check-circle", ""];
    }
    if ($admin && $status === "confirmed" && $when < new DateTimeImmutable()) {
        $simple["complete"] = ["Mark completed", "check-circle", ""];
        $simple["miss"] = ["Mark missed", "x-circle", "button-secondary"];
    }
    $scheduledActions = $admin
        ? ["propose" => ["Propose a new time", "repeat"]]
        : ["change" => ["Request a change", "edit"]];
    if ($admin && $status === "confirmed") {
        $scheduledActions["reschedule"] = ["Reschedule visit", "calendar"];
    }
    ?>
        <h2 class="appt-actions-title">What's next?</h2>
<?php foreach ($simple as $action => [$label, $name, $class]): ?>
        <form method="post" class="action-form">
            <?= csrf_field() . request_key() ?>
            <input type="hidden" name="version" value="<?= e($record["updated_at"]) ?>" />
            <input type="hidden" name="action" value="<?= $action ?>" />
            <button class="button <?= $class ?>" type="submit"><?= icon($name) ?><?= $label ?></button>
        </form>
<?php endforeach; ?>
<?php foreach ($scheduledActions as $action => [$label, $name]): ?>
        <details class="appt-option" <?= $error && ($_POST["action"] ?? "") === $action ? "open" : "" ?>>
            <summary><span class="appt-option-icon"><?= icon($name) ?></span><?= $label ?></summary>
            <form method="post" class="stack-form" data-slot-form data-appointment-id="<?= (int) $record["id"] ?>">
                <?= csrf_field() . request_key() ?>
                <input type="hidden" name="version" value="<?= e($record["updated_at"]) ?>" />
                <input type="hidden" name="action" value="<?= $action ?>" />
                <input type="hidden" name="service_id" value="<?= (int) $record["service_id"] ?>" data-slot-service />
                <?php time_fields($record); ?>
                <label>
                    Note
                    <span class="optional">optional</span>
                    <textarea name="note" rows="3" maxlength="2000" placeholder="Add a short reason"><?= e(($_POST["action"] ?? "") === $action ? $_POST["note"] ?? "" : "") ?></textarea>
                </label>
                <button class="button" type="submit"><?= icon($name) ?><?= $label ?></button>
            </form>
        </details>
<?php endforeach; ?>
        <details class="appt-option cancel-details">
            <summary><span class="appt-option-icon"><?= icon("x-circle") ?></span>Cancel appointment</summary>
            <form
                method="post"
                class="stack-form"
                data-confirm="The record and its history will be kept."
                data-confirm-title="Cancel this appointment?"
                data-confirm-action="Cancel appointment"
            >
                <?= csrf_field() . request_key() ?>
                <input type="hidden" name="version" value="<?= e($record["updated_at"]) ?>" />
                <input type="hidden" name="action" value="cancel" />
                <label>
                    Reason
                    <span class="optional">optional</span>
                    <textarea name="note" rows="2" maxlength="2000" placeholder="Let the <?= $admin ? "owner" : "clinic" ?> know why"></textarea>
                </label>
                <button class="button button-danger" type="submit"><?= icon("x-circle") ?>Cancel appointment</button>
            </form>
        </details>
<?php endif; ?>
        <a class="appt-back" href="/<?= $user["role"] ?>/<?= $admin ? "records" : "appointments" ?>.php"><?= icon("arrow-left") ?>Back to appointments</a>
    </aside>
</div>
