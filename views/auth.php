<?php
$register = $path === "/register.php";
$title = $register ? "Create your account" : "Welcome back";
if ($user && $path !== "/logout.php") {
    redirect("/" . $user["role"] . "/dashboard.php");
}
?>

<section class="auth-page">
    <div class="auth-image">
        <img <?= img("auth", "(max-width: 900px) 92vw, 360px") ?> alt="Two veterinarians checking a beagle during a visit" fetchpriority="high" />
    </div>
    <div class="auth-form-wrap">
        <a class="auth-logo" href="/" aria-label="Rebadulla Animal Care home"><img <?= img("logo", "40px") ?> alt="" /><span>Rebadulla<small>Animal Care</small></span></a>
        <h1><?= $title ?></h1>
        <p><?= $register
    ? "A home for your pets and all their appointments."
    : "Sign in to manage your pets and clinic visits." ?></p>
        <?php
notices($error);
if (
    !configured()
): ?>
        <div class="notice">
            The portal is ready for setup. Configure the Supabase connection using
            <strong>SETUP.md</strong>
            to enable accounts and appointments.
        </div>
        <?php endif;
?>

        <form method="post" class="stack-form">
            <?= csrf_field() ?>
<?php if (
    $register
): ?>
            <label>
                Full name
                <input name="full_name" maxlength="100" autocomplete="name" placeholder="Your full name" value="<?= e(
    $_POST["full_name"] ?? "",
) ?>" required />
            </label>
            <?php endif; ?>

            <?= $register ? '<div class="form-row">' : "" ?>
            <label>
                Email address
                <input type="email" name="email" maxlength="150" autocomplete="email" placeholder="you@example.com" value="<?= e($_POST["email"] ?? "") ?>" required />
            </label>
            <?php if ($register): ?>
            <label>
                Phone number
                <input type="tel" name="phone" maxlength="30" autocomplete="tel" placeholder="09XX XXX XXXX" value="<?= e($_POST["phone"] ?? "") ?>" required />
            </label>
            </div>
            <div class="form-row">
            <?php endif; ?>
            <label>
                Password
                <span class="password-field"><input type="password" name="password" minlength="<?= $register ? 8 : 1 ?>" maxlength="72" autocomplete="<?= $register ? "new-password" : "current-password" ?>" placeholder="<?= $register ? "At least 8 characters" : "Enter your password" ?>" required /><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false"><?= icon("eye") ?><?= icon("eye-off") ?></button></span>
            </label>
            <?php if ($register): ?>
            <label>
                Confirm password
                <span class="password-field"><input type="password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" placeholder="Re-enter password" required /><button class="password-toggle" type="button" data-password-toggle aria-label="Show password" aria-pressed="false"><?= icon("eye") ?><?= icon("eye-off") ?></button></span>
            </label>
            </div>
            <p class="field-help">Use at least 8 characters. We'll email you a six-digit code to verify your account.</p>
            <?php endif; ?>

            <button class="button" type="submit"><?= $register
    ? "Send verification code"
    : "Sign in" ?> ↗</button>
        </form>
        <p class="auth-switch">
            <?= $register
    ? "Already have an account?"
    : "New to Rebadulla?" ?>
            <a href="<?= $register
     ? "/login.php"
     : "/register.php" ?>"><?= $register
    ? "Sign in"
    : "Create an account" ?></a>
        </p>
        <a class="staff-link" href="/admin-login.php"><?= icon("shield") ?>Clinic staff? <strong>Staff sign in</strong></a>
    </div>
</section>
