<?php
$title = "Services";
$catalogue = services();
$edit = null;
if (isset($_GET["edit"])) {
    $edit = row("SELECT * FROM services WHERE id = ?", [
        positive_id($_GET["edit"]),
    ]);
    if (!$edit) {
        throw new ValidationError("Service not found.");
    }
}
$values = $_SERVER["REQUEST_METHOD"] === "POST" ? $_POST : $edit ?? [];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $values["is_active"] = isset($_POST["is_active"]);
}
page_heading(
    "Services",
    "Prices, availability, and places per time slot.",
);
notices($error);
?>

<div class="split-workspace">
    <section class="data-section">
        <div class="section-top">
            <?= section_title("Service list", "tag") ?>
            <span class="muted"><?= count(
    $catalogue,
) ?> services</span>
        </div>
        <div class="service-admin-list">
<?php foreach ($catalogue as $service):
    $name = stripos($service["name"], "vaccin") !== false ? "check-circle" : (stripos($service["name"], "groom") !== false ? "paw" : (stripos($service["name"], "parasite") !== false ? "x-circle" : "tag")); ?>
            <article class="service-admin<?= $service["is_active"] ? "" : " is-off" ?><?= ($edit["id"] ?? null) === $service["id"] ? " is-editing" : "" ?>">
                <span class="service-admin-icon"><?= icon($name) ?></span>
                <div class="service-admin-info">
                    <div class="service-admin-top">
                        <h3><?= e($service["name"]) ?></h3>
                        <span class="availability <?= $service["is_active"] ? "is-active" : "" ?>"><?= $service["is_active"] ? "Active" : "Hidden" ?></span>
                    </div>
                    <?php if ($service["description"]): ?><p><?= e($service["description"]) ?></p><?php endif; ?>
                    <div class="service-admin-meta">
                        <span><?= icon("wallet") ?><?= e(money($service["price_from"])) ?></span>
                        <span><?= icon("user") ?><?= (int) $service["max_per_slot"] ?> per slot</span>
                    </div>
                </div>
                <a class="button button-secondary button-small" href="?edit=<?= (int) $service["id"] ?>"><?= icon("edit") ?>Edit</a>
            </article>
<?php endforeach; ?>
        </div>
    </section>
    <section class="form-panel">
        <?= section_title($edit ? "Edit service" : "Add a service", $edit ? "edit" : "plus") ?>
        <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e(
    $values["id"] ?? "",
) ?>" />
            <label>
                Service name
                <input name="name" maxlength="120" value="<?= e(
    $values["name"] ?? "",
) ?>" required />
            </label>
            <label>
                Description
                <textarea name="description" rows="4" maxlength="2000"><?= e(
    $values["description"] ?? "",
) ?></textarea>
            </label>
            <div class="form-row">
                <label>
                    Starting price ₱
                    <input
                        type="number"
                        name="price_from"
                        min="0"
                        max="99999999.99"
                        step="0.01"
                        value="<?= e(
    $values["price_from"] ?? "",
) ?>"
                    />
                </label>
                <label>
                    Places per slot
                    <input
                        type="number"
                        name="max_per_slot"
                        min="1"
                        max="100"
                        value="<?= e(
    $values["max_per_slot"] ?? 1,
) ?>"
                        required
                    />
                </label>
            </div>
            <p class="field-help">Leave the starting price blank when a quote is needed.</p>
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" <?= $values[
    "is_active"
] ?? !$edit
    ? "checked"
    : "" ?> />
                Available for booking
            </label>
            <div class="form-actions">
                <button class="button"><?= $edit
    ? "Save changes"
    : "Add service" ?></button>
                <?php if (
    $edit
): ?>
                <a href="/admin/services.php">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
</div>
