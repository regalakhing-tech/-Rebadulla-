# Email verification implementation plan

Goal: New owners verify a six-digit code sent by PHPMailer before an account or login session is created.

The verification screen keeps the existing veterinarian photograph, Sora typography, Rebadulla logo, and blue accent. A single accessible input supports mobile numeric keyboards, paste, and one-time-code autofill; six visual positions make it easy to read. The primary action verifies the code. Resend has a 60-second cooldown; changing the email returns to registration.

Pending registration details and password hashes live in a private `clinic.registration_challenges` table. Only a hashed random browser token is stored; codes use an application-key HMAC. Codes last ten minutes, permit five guesses, and are single use. The account is inserted and the challenge deleted in one locked transaction. Existing accounts keep working. Failed SMTP delivery never creates an account or a usable code.

The HTML email uses an embedded PNG logo, a large code, expiry guidance, and a plain-text alternative. SMTP secrets stay in ignored environment files and Vercel production settings. Internet is required to receive a new code; existing offline accounts still sign in normally.

- [x] Add `tests/email-verification.mjs`: exercise the actual HTTP app against a disposable local database and SMTP capture server. Establish that the current immediate signup fails the verification requirement.
- [x] Add the CLI-generated Supabase migration, update `database/schema.sql`, and apply the additive change locally. Preserve existing records and restricted application permissions.
- [x] Add `src/registration.php`, shared PHPMailer configuration, and `views/emails/verification.php`. Connect registration, verification, and resend routes with CSRF checks, throttling, expiry, and atomic consumption.
- [x] Add `views/verify-email.php`, scoped CSS and input/countdown behavior; keep verification functional without JavaScript.
- [x] Verify correct/incorrect/expired codes, attempt limits, resend rotation, browser binding, delivery failures, and single use. Run existing checks and inspect desktop/mobile screenshots.
- [x] Configure the authorized Gmail sender in local profiles and Vercel, apply the additive production migration, deploy, and verify production health, the new screen, and SMTP acceptance. Record remaining limitations honestly.

Validation: 30 HTTP/SMTP integration checks, four additional browser checks (mobile layout, visual digits, no-JavaScript input, and clock skew), 21 existing rules, and 13 production checks passed. Gmail accepted the local delivery test and the live Vercel registration email. SMTP acceptance is verified; inbox placement depends on the receiving mail service. Supabase's security advisor reported no issues. Offline sign-in remains available for existing accounts; new email verification requires internet.
