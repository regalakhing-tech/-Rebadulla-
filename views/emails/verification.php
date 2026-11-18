<?php
// Email clients ignore <style> blocks and modern CSS, so this layout uses tables and inline styles only.
$digits = str_split((string) $code);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light">
<title>Verify your email</title>
</head>
<body style="margin:0;padding:0;background-color:#eef2fb;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0b1533;-webkit-font-smoothing:antialiased">
<div style="display:none;max-height:0;overflow:hidden;opacity:0">Your Rebadulla verification code is <?= e($code) ?>. It expires in 10 minutes.</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#eef2fb" style="background-color:#eef2fb">
<tr><td align="center" style="padding:32px 12px">

<table role="presentation" width="560" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:560px">

<!-- Brand header -->
<tr><td bgcolor="#0a4bff" style="background-color:#0a4bff;border-radius:24px 24px 0 0;padding:26px 32px">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>
<td valign="middle">
<table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr>
<td bgcolor="#ffffff" style="background-color:#ffffff;border-radius:14px;padding:6px 8px"><img src="cid:rebadulla-logo" width="34" height="40" alt="Rebadulla" style="display:block;border:0"></td>
<td style="padding-left:12px;color:#ffffff;font-size:18px;font-weight:700;letter-spacing:-0.3px;line-height:1.2">Rebadulla<br><span style="font-size:10px;font-weight:700;letter-spacing:2.5px;color:#dbe7ff">ANIMAL CARE</span></td>
</tr></table>
</td>
<td align="right" valign="middle"><span style="display:inline-block;padding:6px 12px;border-radius:999px;background-color:rgba(255,255,255,0.18);color:#ffffff;font-size:11px;font-weight:700;letter-spacing:1px">VERIFICATION</span></td>
</tr></table>
</td></tr>

<!-- Body -->
<tr><td bgcolor="#ffffff" style="background-color:#ffffff;padding:36px 32px 12px" align="center">
<table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr><td align="center" width="64" height="64" bgcolor="#eef3ff" style="width:64px;height:64px;border-radius:20px;background-color:#eef3ff;font-size:30px;line-height:64px">&#128274;</td></tr></table>
<h1 style="margin:22px 0 8px;font-size:26px;line-height:1.25;font-weight:800;letter-spacing:-0.6px;color:#0b1533">Verify your email</h1>
<p style="margin:0;font-size:15px;line-height:1.6;color:#4b5770">Hi <?= e($name) ?>, enter this code to finish<br>creating your Rebadulla account.</p>
</td></tr>

<!-- Code -->
<tr><td bgcolor="#ffffff" style="background-color:#ffffff;padding:24px 20px 8px" align="center">
<table role="presentation" cellspacing="6" cellpadding="0" border="0"><tr>
<?php foreach ($digits as $digit): ?>
<td align="center" valign="middle" width="46" height="58" bgcolor="#f5f8ff" style="width:46px;height:58px;border:1px solid #d9e4ff;border-radius:14px;background-color:#f5f8ff;font-family:Consolas,'SF Mono',Menlo,monospace;font-size:28px;font-weight:700;color:#0a4bff;line-height:58px"><?= e($digit) ?></td>
<?php endforeach; ?>
</tr></table>
</td></tr>

<!-- Expiry -->
<tr><td bgcolor="#ffffff" style="background-color:#ffffff;padding:10px 32px 28px" align="center">
<span style="display:inline-block;padding:8px 14px;border-radius:999px;background-color:#fff3de;color:#a85f00;font-size:13px;font-weight:700">&#9201;&nbsp; Expires in 10 minutes &middot; one-time use</span>
</td></tr>

<!-- Security note -->
<tr><td bgcolor="#ffffff" style="background-color:#ffffff;padding:0 32px 32px">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>
<td bgcolor="#f6f8fc" style="background-color:#f6f8fc;border:1px solid #e6ebf4;border-radius:16px;padding:16px 18px">
<table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr>
<td valign="top" style="font-size:20px;line-height:22px;padding-right:12px">&#128737;&#65039;</td>
<td style="font-size:13px;line-height:1.6;color:#4b5770"><strong style="color:#0b1533">Keep this code private.</strong><br>Rebadulla staff will never ask for it. Didn't sign up? You can ignore this email.</td>
</tr></table>
</td>
</tr></table>
</td></tr>

<!-- Footer -->
<tr><td bgcolor="#0b1533" style="background-color:#0b1533;border-radius:0 0 24px 24px;padding:22px 32px" align="center">
<p style="margin:0;font-size:13px;font-weight:700;color:#ffffff">Rebadulla Animal Care</p>
<p style="margin:6px 0 0;font-size:12px;line-height:1.6;color:#9fb0d0">CHVH Bldg, Cerdeña St, San Jose, Antique &middot; 0955 029 5738</p>
</td></tr>

</table>
<p style="margin:18px 0 0;font-size:11px;color:#8792a8">Care that feels like family &middot; Since 2007</p>

</td></tr>
</table>
</body>
</html>
