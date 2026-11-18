<?php
$title = "Book a visit";
$pets = rows(
    "SELECT id, name, species FROM pets WHERE owner_id = ? ORDER BY name",
    [(int) $user["id"]],
);
page_heading(
    "Book a visit",
    "Pick a service and an open time.",
);
notices($error);
if (!$pets) {
    empty_state(
        "Add your pet first",
        "We need a few details about your pet before you book.",
        '<a class="button" href="/owner/pets.php">Add a pet ↗</a>',
    );
    return;
}
$catalogue = services(true);
?>

<div class="booking-layout">
    <section class="form-panel">
        <?= section_title("Visit details", "calendar") ?>
        <form method="post" class="stack-form" data-slot-form>
            <?=
csrf_field() . request_key()
?>

            <label>
                Who is coming?
                <select name="pet_id" required>
                    <?php foreach (
    $pets
    as $pet
): ?>
                    <option value="<?= (int) $pet["id"] ?>" <?= (string) ($_POST["pet_id"] ?? ($_GET["pet"] ?? "")) === (string) $pet["id"]
    ? "selected"
    : "" ?>><?= e(
    $pet["name"] . " · " . $pet["species"],
) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Service
                <select name="service_id" data-slot-service required>
                    <?php service_options(
    $catalogue,
    $_POST["service_id"] ?? ($_GET["service"] ?? ""),
); ?>
                </select>
            </label>
            <?php time_fields(); ?>

            <label>
                Anything we should know?
                <span class="optional">optional</span>
                <textarea
                    name="owner_notes"
                    maxlength="2000"
                    rows="4"
                    placeholder="Tell us what brings your pet in."
                >
<?= e(
    $_POST["owner_notes"] ?? "",
) ?></textarea>
            </label>
            <button class="button" type="submit">Send appointment request ↗</button>
        </form>
    </section>
    <aside class="booking-aside">
        <span class="eyebrow">A LITTLE NOTE BEFORE YOU BOOK</span>
        <h2>
            We’ll take it
            <br />
            from here.
        </h2>
        <p>Your appointment starts as <strong>Pending</strong>. The clinic will confirm your time or suggest another one.</p>
        <div class="booking-note">
            <span>01</span>
            <p>Send a request with your preferred service and time.</p>
        </div>
        <div class="booking-note">
            <span>02</span>
            <p>Follow the status in My Appointments.</p>
        </div>
        <div class="booking-note">
            <span>03</span>
            <p>If a new time is proposed, accept it or request a change.</p>
        </div>
        <img <?= img("booking-visit", "(max-width: 1100px) 92vw, 420px") ?> alt="A veterinarian checking a beagle during a clinic visit" loading="lazy" decoding="async" />
    </aside>
</div>
