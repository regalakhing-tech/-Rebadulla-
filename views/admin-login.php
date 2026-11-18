<?php
$title = "Staff sign in";
if ($user) {
    redirect("/" . $user["role"] . "/dashboard.php");
}
$features = [
    ["inbox", "Online requests", "Confirm or propose a new time"],
    ["calendar", "Daily schedule", "See every visit, no overbooking"],
    ["folder", "All records", "Search pets, owners, and history"],
];
?>

<section class="staff-login">
    <div class="staff-panel">
        <a class="staff-brand" href="/" aria-label="Rebadulla Animal Care home">
            <img <?= img("logo", "44px") ?> alt="" />
            <span>Rebadulla<small>Clinic workspace</small></span>
        </a>
        <div class="staff-panel-copy">
            <span class="staff-chip"><?= icon("shield") ?>Staff only</span>
            <h2>Everything the clinic needs, <em>in one place.</em></h2>
        </div>
        <ul class="staff-features">
<?php foreach ($features as [$name, $label, $text]): ?>
            <li>
                <span><?= icon($name) ?></span>
                <div><strong><?= e($label) ?></strong><small><?= e($text) ?></small></div>
            </li>
<?php endforeach; ?>
        </ul>
    </div>
    <div class="staff-form">
        <span class="staff-lock"><?= icon("shield") ?></span>
        <h1>Staff sign in</h1>
        <p>Use your clinic administrator account.</p>
        <?php notices($error); ?>
        <form method="post" class="stack-form">
            <?= csrf_field() ?>
            <label>
                Work email
                <input type="email" name="email" maxlength="150" autocomplete="username" placeholder="admin@rebadulla.com" value="<?= e($_POST["email"] ?? "") ?>" required />
            </label>
            <label>
                Password
                <span class="password-field"><input type="password" name="password" maxlength="72" autocomplete="current-password" placeholder="Enter your password" required /><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false"><?= icon("eye") ?><?= icon("eye-off") ?></button></span>
            </label>
            <button class="button" type="submit">Sign in to workspace<?= icon("login") ?></button>
        </form>
        <p class="staff-note"><?= icon("shield") ?>Authorized clinic staff only. Every change is recorded.</p>
        <a class="staff-owner-link" href="/login.php">Pet owner? <strong>Sign in here</strong></a>
    </div>
</section>
