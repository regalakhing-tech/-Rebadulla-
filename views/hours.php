<?php
$title = "Clinic hours";
$weekly = rows("SELECT * FROM clinic_hours ORDER BY day_of_week");
$closures = rows("SELECT * FROM clinic_closures ORDER BY date");
$affected = rows(
    "SELECT a.*, p.name AS pet_name, u.full_name AS owner_name, u.phone, s.name AS service_name
    FROM appointments a JOIN clinic_closures c ON c.date = a.appt_date JOIN pets p ON p.id = a.pet_id
    JOIN users u ON u.id = p.owner_id JOIN services s ON s.id = a.service_id
    WHERE a.status = 'confirmed' AND a.appt_date >= ? ORDER BY a.appt_date, a.appt_time",
    [date("Y-m-d")],
);
$names = [
    "Sunday",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
];
page_heading(
    "Clinic hours",
    "Weekly hours and closed dates.",
);
notices($error);
?>
<?php if ($affected): ?>
<div class="notice notice-error">
    There are <?= count(
    $affected,
) ?> confirmed appointments on closed dates. Review these records and reschedule them.
</div>
<section class="data-section"><?php appointment_table(
     $affected,
     "admin",
 ); ?></section>
<?php endif; ?>

<div class="split-workspace">
    <section class="form-panel">
        <?= section_title("Weekly hours", "clock") ?>
        <p class="field-help">Appointment slots run in 30-minute intervals from the opening time.</p>
        <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="weekly" />
            <div class="hours-list">
                <?php foreach (
    $weekly
    as $day
):

    $index = (int) $day["day_of_week"];
    $posted = ($_POST["action"] ?? "") === "weekly";
    $closed = $posted ? isset($_POST["closed_" . $index]) : $day["is_closed"];
    ?>
                <div class="hours-row">
                    <strong><?= $names[
    $index
] ?></strong>
                    <label>
                        <span class="sr-only"><?= $names[
    $index
] ?> opening</span>
                        <input type="time" name="open_<?= $index ?>" value="<?= e(
    $posted
        ? $_POST["open_" . $index] ?? ""
        : substr($day["open_time"] ?? "08:00", 0, 5),
) ?>" />
                    </label>
                    <span>to</span>
                    <label>
                        <span class="sr-only"><?= $names[
    $index
] ?> closing</span>
                        <input type="time" name="close_<?= $index ?>" value="<?= e(
    $posted
        ? $_POST["close_" . $index] ?? ""
        : substr($day["close_time"] ?? "17:00", 0, 5),
) ?>" />
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="closed_<?= $index ?>" value="1" <?= $closed
    ? "checked"
    : "" ?> />
                        Closed
                    </label>
                </div>
                <?php
endforeach; ?>
            </div>
            <button class="button">Save weekly hours</button>
        </form>
    </section>
    <section class="form-panel">
        <?= section_title("Closed dates", "calendar") ?>
        <?php if (
    !$closures
): ?>
        <p class="muted">No special closures added.</p>
        <?php endif; ?>
        <div class="closure-list">
            <?php foreach (
    $closures
    as $closure
): ?>
            <div>
                <span>
                    <strong><?= e(
    schedule_label($closure["date"]),
) ?></strong>
                    <small><?= e(
    $closure["reason"],
) ?></small>
                </span>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="closure_remove" />
                    <input type="hidden" name="date" value="<?= e(
    $closure["date"],
) ?>" />
                    <button class="text-button" aria-label="Remove closure for <?= e(
    $closure["date"],
) ?>">
                        Remove
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="closure_add" />
            <label>
                Closure date
                <input type="date" name="date" required value="<?= e(
    ($_POST["action"] ?? "") === "closure_add" ? $_POST["date"] ?? "" : "",
) ?>" />
            </label>
            <label>
                Reason
                <input
                    name="reason"
                    maxlength="200"
                    placeholder="Holiday, clinic maintenance…"
                    required
                    value="<?= e(
    ($_POST["action"] ?? "") === "closure_add" ? $_POST["reason"] ?? "" : "",
) ?>"
                />
            </label>
            <p class="field-help">Existing confirmed visits will be listed for review after you save.</p>
            <button class="button button-secondary">Add closed date</button>
        </form>
    </section>
</div>
