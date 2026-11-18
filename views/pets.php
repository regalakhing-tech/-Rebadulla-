<?php
$title = "My pets";
$photoVersion = "(SELECT extract(epoch FROM ph.updated_at)::bigint FROM pet_photos ph WHERE ph.pet_id = p.id) AS photo_version";
$pets = rows("SELECT p.*, $photoVersion FROM pets p WHERE p.owner_id = ? ORDER BY p.name", [
    (int) $user["id"],
]);
$edit = null;
if (isset($_GET["edit"])) {
    $edit = row("SELECT p.*, $photoVersion FROM pets p WHERE p.id = ? AND p.owner_id = ?", [
        positive_id($_GET["edit"]),
        (int) $user["id"],
    ]);
    if (!$edit) {
        http_response_code(404);
        throw new ValidationError("Pet not found.");
    }
}
$values = $_SERVER["REQUEST_METHOD"] === "POST" ? $_POST : $edit ?? [];
// The form lives in a pop-up. It starts open when editing, after a failed save, or at ?add=1.
$open = $edit || $error || isset($_GET["add"]);
$photo = fn(array $pet): string => '/pet-photo.php?id=' . (int) $pet["id"] . "&amp;v=" . (int) $pet["photo_version"];
page_heading(
    "My pets",
    $pets ? count($pets) . (count($pets) === 1 ? " pet" : " pets") . " registered" : "",
    $pets ? '<a class="button" href="?add=1" data-pet-dialog-open>' . icon("plus") . "Add pet</a>" : "",
);
notices($open ? null : $error);
?>

<?php if (!$pets): ?>
<section class="data-section pet-empty">
    <span class="pet-empty-icon"><?= icon("paw") ?></span>
    <h2>Add your first pet</h2>
    <p>Save their details and a photo once. Booking a visit takes seconds after that.</p>
    <a class="button" href="?add=1" data-pet-dialog-open><?= icon("plus") ?>Add pet</a>
</section>
<?php else: ?>
<div class="pet-grid">
<?php foreach ($pets as $pet): ?>
    <article class="pet-card">
        <div class="pet-card-photo">
<?php if ($pet["photo_version"]): ?>
            <img src="<?= $photo($pet) ?>" alt="Photo of <?= e($pet["name"]) ?>" loading="lazy" />
<?php else: ?>
            <span class="pet-card-placeholder"><?= icon("paw") ?><b><?= e(mb_strtoupper(mb_substr($pet["name"], 0, 1))) ?></b></span>
<?php endif; ?>
        </div>
        <div class="pet-card-body">
            <h3><?= e($pet["name"]) ?></h3>
            <div class="pet-tags">
                <span><?= e($pet["species"]) ?></span>
<?php if ($pet["breed"]): ?>
                <span><?= e($pet["breed"]) ?></span>
<?php endif; ?>
<?php if ($pet["sex"] !== "unknown"): ?>
                <span><?= $pet["sex"] === "female" ? "♀ Female" : "♂ Male" ?></span>
<?php endif; ?>
            </div>
<?php if ($pet["birth_date"]): ?>
            <p class="pet-born"><?= icon("calendar") ?>Born <?= e(schedule_label($pet["birth_date"])) ?></p>
<?php endif; ?>
            <div class="pet-card-actions">
                <a class="button button-small" href="/owner/book.php?pet=<?= (int) $pet["id"] ?>"><?= icon("calendar-check") ?>Book visit</a>
                <a class="button button-secondary button-small" href="?edit=<?= (int) $pet["id"] ?>"><?= icon("edit") ?>Edit</a>
            </div>
        </div>
    </article>
<?php endforeach; ?>
    <a class="pet-card pet-card-add" href="?add=1" data-pet-dialog-open>
        <span><?= icon("plus") ?></span>
        Add another pet
    </a>
</div>
<?php endif; ?>

<dialog class="pet-dialog" id="pet-dialog" aria-labelledby="pet-dialog-title"<?= $open ? " open data-start-open" : "" ?> data-mode="<?= $edit ? "edit" : "add" ?>">
    <form method="post" class="stack-form" enctype="multipart/form-data">
        <div class="pet-dialog-head">
            <span class="title-icon"><?= icon($edit ? "edit" : "paw") ?></span>
            <h2 id="pet-dialog-title"><?= $edit ? "Edit " . e($edit["name"]) : "Add a pet" ?></h2>
            <a class="pet-dialog-close" href="/owner/pets.php" aria-label="Close" data-pet-dialog-close><?= icon("x-circle") ?></a>
        </div>
        <?php if ($error): ?>
        <div class="notice notice-error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($edit["id"] ?? ($values["id"] ?? "")) ?>" />
        <div class="photo-field" data-photo-field>
            <label class="photo-preview" for="pet-photo-input" data-photo-preview aria-label="Choose a photo">
                <?php if (!empty($edit["photo_version"])): ?>
                <img src="<?= $photo($edit) ?>" alt="" />
                <?php else: ?>
                <?= icon("camera") ?>
                <?php endif; ?>
            </label>
            <div>
                <label class="button button-secondary button-small photo-pick">
                    <?= icon("camera") ?><?= !empty($edit["photo_version"]) ? "Change photo" : "Add a photo" ?>
                    <input class="sr-only" id="pet-photo-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-photo-input />
                </label>
                <p class="field-help">Optional · JPG, PNG, or WebP</p>
                <?php if (!empty($edit["photo_version"])): ?>
                <label class="checkbox-label"><input type="checkbox" name="remove_photo" value="1" /> Remove photo</label>
                <?php endif; ?>
            </div>
        </div>
        <label>
            Pet name
            <input name="name" maxlength="60" placeholder="e.g. Mochi" value="<?= e($values["name"] ?? "") ?>" required />
        </label>
        <div class="form-row">
            <label>
                Species
                <input name="species" list="species" maxlength="30" placeholder="Dog, cat, rabbit…" value="<?= e($values["species"] ?? "") ?>" required />
                <datalist id="species">
                    <option>Dog</option>
                    <option>Cat</option>
                    <option>Rabbit</option>
                    <option>Bird</option>
                </datalist>
            </label>
            <label>
                Breed
                <span class="optional">optional</span>
                <input name="breed" maxlength="60" placeholder="e.g. Shih Tzu" value="<?= e($values["breed"] ?? "") ?>" />
            </label>
        </div>
        <div class="form-row">
            <label>
                Sex
                <select name="sex">
                    <?php foreach (["unknown", "male", "female"] as $sex): ?>
                    <option value="<?= $sex ?>" <?= ($values["sex"] ?? "unknown") === $sex ? "selected" : "" ?>><?= ucfirst($sex) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Birth date
                <span class="optional">optional</span>
                <input type="date" name="birth_date" max="<?= date("Y-m-d") ?>" value="<?= e($values["birth_date"] ?? "") ?>" />
            </label>
        </div>
        <label>
            Notes
            <span class="optional">optional</span>
            <textarea name="notes" rows="3" maxlength="2000" placeholder="Allergies, behavior, anything the vet should know"><?= e($values["notes"] ?? "") ?></textarea>
        </label>
        <div class="pet-dialog-actions">
            <a class="button button-secondary" href="/owner/pets.php" data-pet-dialog-close>Cancel</a>
            <button class="button" type="submit"><?= icon("check") ?><?= $edit ? "Save changes" : "Add pet" ?></button>
        </div>
    </form>
</dialog>
