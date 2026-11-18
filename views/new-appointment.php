<?php
$title = "Phone / walk-in";
$term = trim((string) ($_GET["q"] ?? ""));
$owners = rows(
    "SELECT id, full_name, phone FROM users WHERE role = 'owner' AND (full_name ILIKE ? OR phone ILIKE ?) ORDER BY full_name LIMIT 50",
    ["%" . $term . "%", "%" . $term . "%"],
);
$pets = rows(
    "SELECT p.id, p.name, p.species, u.full_name AS owner_name, u.phone,
    (SELECT extract(epoch FROM ph.updated_at)::bigint FROM pet_photos ph WHERE ph.pet_id = p.id) AS photo_version
    FROM pets p JOIN users u ON u.id = p.owner_id
    WHERE u.full_name ILIKE ? OR u.phone ILIKE ? OR p.name ILIKE ? ORDER BY u.full_name, p.name LIMIT 12",
    ["%" . $term . "%", "%" . $term . "%", "%" . $term . "%"],
);
// Walk-ins usually have no account, so typing a new visitor is the default.
$source = in_array($_POST["source"] ?? "", ["phone", "walk_in"], true) ? $_POST["source"] : "walk_in";
$mode = in_array($_POST["pet_mode"] ?? "", ["existing", "new_pet", "new_owner"], true)
    ? $_POST["pet_mode"]
    : ($term !== "" ? "existing" : "new_owner");
$old = fn(string $key): string => e($_POST[$key] ?? "");
$choice = fn(string $name, string $value, string $current, string $iconName, string $label, string $hint): string => '<label class="choice">' .
    '<input type="radio" name="' . $name . '" value="' . $value . '"' . ($current === $value ? " checked" : "") . " />" .
    '<span class="choice-icon">' . icon($iconName) . "</span>" .
    "<span><strong>" . e($label) . "</strong><small>" . e($hint) . "</small></span></label>";
page_heading("Phone / walk-in", "Book a visitor in seconds. No account needed.");
notices($error);
?>

<form method="get" class="walkin-find">
    <span><?= icon("eye") ?></span>
    <label class="search-field records-q">
        <span class="sr-only">Find a registered owner or pet</span>
        <input type="search" name="q" value="<?= e($term) ?>" placeholder="Visited before? Find owner or pet by name or phone" />
    </label>
    <button class="button button-secondary" type="submit">Find</button>
    <?php if ($term !== ""): ?><a class="button button-secondary" href="/admin/new-appointment.php">Clear</a><?php endif; ?>
</form>

<form method="post" class="walkin-form" data-slot-form data-booking-form>
    <?= csrf_field() . request_key() ?>
    <div class="walkin-grid">
        <section class="form-panel stack-form">
            <div class="walkin-step"><span>1</span><h2 class="section-title">How did they book?</h2></div>
            <div class="choice-grid" role="radiogroup" aria-label="Booking type">
                <?= $choice("source", "walk_in", $source, "user", "Walk-in", "At the clinic now") ?>
                <?= $choice("source", "phone", $source, "phone", "Phone call", "Pick a date and time") ?>
            </div>

            <div class="walkin-step"><span>2</span><h2 class="section-title">Who is visiting?</h2></div>
            <div class="choice-grid choice-grid-3" role="radiogroup" aria-label="Visitor">
                <?= $choice("pet_mode", "new_owner", $mode, "edit", "New visitor", "Type their name") ?>
                <?= $choice("pet_mode", "existing", $mode, "paw", "Registered pet", "Pick from the list") ?>
                <?= $choice("pet_mode", "new_pet", $mode, "plus", "New pet", "Of a known owner") ?>
            </div>

            <fieldset data-mode="new_owner" class="visitor-box">
                <label>
                    Owner's name
                    <input name="full_name" maxlength="100" placeholder="e.g. Juan Dela Cruz" autocomplete="off" value="<?= $old("full_name") ?>" required />
                </label>
                <div class="form-row">
                    <label>
                        Phone
                        <span class="optional" data-phone-optional>optional</span>
                        <input type="tel" name="phone" maxlength="30" placeholder="09XX XXX XXXX" value="<?= $old("phone") ?>" data-phone-required />
                    </label>
                    <label>
                        Email
                        <span class="optional">optional</span>
                        <input type="email" name="email" maxlength="150" placeholder="name@example.com" value="<?= $old("email") ?>" />
                    </label>
                </div>
            </fieldset>

            <fieldset data-mode="existing" class="visitor-box">
                <legend class="sr-only">Registered pet</legend>
                <p class="find-note"><?= $term !== ""
                    ? count($pets) . (count($pets) === 1 ? " match" : " matches") . " for “" . e($term) . "”"
                    : "Recent pets · use the search above to find others" ?></p>
<?php if (!$pets): ?>
                <p class="find-empty">No registered pet matches. Choose <strong>New visitor</strong> to type their name.</p>
<?php else: ?>
                <div class="pet-pick">
<?php foreach ($pets as $pet): ?>
                    <label class="pet-option">
                        <input type="radio" name="pet_id" value="<?= (int) $pet["id"] ?>" required<?= (string) ($_POST["pet_id"] ?? "") === (string) $pet["id"] || count($pets) === 1 ? " checked" : "" ?> />
                        <span class="pet-thumb" aria-hidden="true"><?php if ($pet["photo_version"]): ?><img src="/pet-photo.php?id=<?= (int) $pet["id"] ?>&amp;v=<?= (int) $pet["photo_version"] ?>" alt="" loading="lazy" /><?php else: ?><?= e(mb_strtoupper(mb_substr($pet["name"], 0, 1))) ?><?php endif; ?></span>
                        <span><strong><?= e($pet["name"]) ?> <small><?= e($pet["species"]) ?></small></strong><small><?= e($pet["owner_name"] . ($pet["phone"] ? " · " . $pet["phone"] : "")) ?></small></span>
                    </label>
<?php endforeach; ?>
                </div>
<?php endif; ?>
            </fieldset>

            <fieldset data-mode="new_pet" class="visitor-box">
                <label>
                    Owner
                    <select name="owner_id" required>
                        <option value="">Choose an owner</option>
                        <?php foreach ($owners as $owner): ?>
                        <option value="<?= (int) $owner["id"] ?>" <?= (string) ($_POST["owner_id"] ?? "") === (string) $owner["id"] ? "selected" : "" ?>><?= e($owner["full_name"] . ($owner["phone"] ? " · " . $owner["phone"] : "")) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </fieldset>

            <fieldset data-mode="new_pet new_owner" class="visitor-box">
                <div class="form-row">
                    <label>
                        Pet's name
                        <input name="pet_name" maxlength="60" placeholder="e.g. Bantay" value="<?= $old("pet_name") ?>" required />
                    </label>
                    <label>
                        Species
                        <input name="species" list="walkin-species" maxlength="30" placeholder="Dog, cat…" value="<?= $old("species") ?>" required />
                        <datalist id="walkin-species"><option>Dog</option><option>Cat</option><option>Rabbit</option><option>Bird</option></datalist>
                    </label>
                </div>
                <label>
                    Breed
                    <span class="optional">optional</span>
                    <input name="breed" maxlength="60" placeholder="e.g. Aspin" value="<?= $old("breed") ?>" />
                </label>
            </fieldset>
        </section>

        <section class="form-panel stack-form">
            <div class="walkin-step"><span>3</span><h2 class="section-title">Visit details</h2></div>
            <label>
                Service
                <select name="service_id" data-slot-service required>
                    <?php service_options(services(true), $_POST["service_id"] ?? ""); ?>
                </select>
            </label>
            <fieldset data-phone-schedule><?php time_fields(); ?></fieldset>
            <div class="walkin-now" data-walk-in-note hidden>
                <span><?= icon("clock") ?></span>
                <p><strong>Today, <?= e(date("g:i A")) ?></strong>Walk-ins are confirmed for right now.</p>
            </div>
            <label>
                Visit notes
                <span class="optional">optional</span>
                <textarea name="owner_notes" rows="3" maxlength="2000" placeholder="Reason for the visit, symptoms, anything the vet should know"><?= $old("owner_notes") ?></textarea>
            </label>
            <button class="button walkin-save" type="submit"><?= icon("check-circle") ?>Save confirmed appointment</button>
        </section>
    </div>
</form>
