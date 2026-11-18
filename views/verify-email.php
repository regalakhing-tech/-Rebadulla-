<?php
if ($user) redirect("/" . $user["role"] . "/dashboard.php");
$pending = pending_registration();
if (!$pending) redirect("/register.php");
$title = "Verify your email";
$resendAt = (int) $pending["sent_unix"] + 60;
$expiresAt = (int) $pending["expires_unix"];
$unusable = $pending["expired"] || (int) $pending["attempts"] >= 5 || !$pending["delivered_at"];
?>
<section class="auth-page otp-page">
    <div class="auth-image">
        <img <?= img("auth", "(max-width: 900px) 92vw, 380px") ?> alt="Veterinarians caring for a beagle" fetchpriority="high" />
    </div>
    <div class="auth-form-wrap otp-form-wrap" data-otp-clock="<?= time() ?>">
        <a class="auth-logo" href="/" aria-label="Rebadulla Animal Care home"><img <?= img("logo", "40px") ?> alt="" /><span>Rebadulla<small>Animal Care</small></span></a>
        <ol class="otp-steps" aria-label="Account creation progress"><li class="is-complete"><span aria-hidden="true">✓</span> Account details</li><li aria-current="step"><span>2</span> Verify email</li></ol>
        <div class="otp-mail-icon" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><rect x="4" y="7" width="24" height="19" rx="4" stroke="currentColor" stroke-width="1.7"/><path d="m5 9 9 7a3 3 0 0 0 4 0l9-7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="m21 4 1-2m4 4 2-1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></div>
        <h1>Check your inbox.</h1>
        <p>Enter the six-digit code we sent to<br><strong class="otp-email"><?= e($pending["email"]) ?></strong></p>
        <?php notices($error); ?>
        <?php if (isset($_GET["resent"])): ?><div class="notice otp-sent" role="status">A new code is on its way. Use the latest email.</div><?php endif; ?>
        <form method="post" class="stack-form" data-otp-form>
            <?= csrf_field() ?><input type="hidden" name="action" value="verify">
            <label for="verification-code">Verification code</label>
            <div class="otp-code" data-otp-code>
                <input class="otp-code-input" id="verification-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required aria-describedby="otp-code-help" autofocus>
                <div class="otp-digits" aria-hidden="true"><?php for ($digit = 0; $digit < 6; $digit++): ?><span data-otp-digit></span><?php endfor; ?></div>
            </div>
            <p class="otp-expiry" id="otp-code-help"><?php if ($unusable): ?>Request a new code to continue.<?php else: ?>Code expires in <span data-otp-expiry="<?= $expiresAt ?>"><?= gmdate("i:s", max(0, $expiresAt - time())) ?></span><?php endif; ?></p>
            <button class="button otp-verify" type="submit" data-otp-submit <?= $unusable ? "disabled" : "" ?>>Verify &amp; create account <span aria-hidden="true">↗</span></button>
        </form>
        <div class="otp-resend">
            <span>Didn't receive the email?</span>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="resend"><button class="otp-resend-button" type="submit" data-resend-at="<?= $resendAt ?>" <?= $resendAt > time() ? "disabled" : "" ?>><?= $resendAt > time() ? "Resend in " . ($resendAt - time()) . "s" : "Resend code" ?></button></form>
        </div>
        <p class="otp-help">Check your spam folder, too. Only the latest code will work.</p>
        <a class="otp-change-email" href="/register.php">Wrong email? Change it <span aria-hidden="true">→</span></a>
        <noscript><p class="field-help">Refresh this page after a minute to resend a code.</p></noscript>
        <p class="otp-security"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none"><path d="M8 1.5 13 4v4c0 3-5 6-5 6S3 11 3 8V4l5-2.5Z" stroke="currentColor"/><path d="m5.5 7.5 1.7 1.7 3.3-3.4" stroke="currentColor"/></svg> Your code is private. Never share it.</p>
    </div>
</section>
