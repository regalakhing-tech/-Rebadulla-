<?php
$title = "Email outbox";
$page = max(0, (int) ($_GET["page"] ?? 0));
$messages = rows(
    'SELECT m.*, a.id AS appointment_id, a.booking_ref, a.pet_id, p.name AS pet_name, u.full_name AS owner_name, u.phone,
    (SELECT extract(epoch FROM ph.updated_at)::bigint FROM pet_photos ph WHERE ph.pet_id = a.pet_id) AS photo_version
    FROM mail_outbox m JOIN appointment_history h ON h.id = m.history_id
    JOIN appointments a ON a.id = h.appointment_id JOIN pets p ON p.id = a.pet_id JOIN users u ON u.id = p.owner_id
    ORDER BY m.created_at DESC LIMIT 51 OFFSET ' .
        $page * 50,
);
page_heading(
    "Email outbox",
    "Appointment emails sent to owners.",
);
notices(
    $error,
    isset($_GET["sent"])
        ? (int) $_GET["sent"] . " messages sent. " . (int) ($_GET["failed"] ?? 0) . " delivery attempts failed."
        : null,
);
?>

<?php $mailCounts = array_column(rows("SELECT status, count(*) AS n FROM mail_outbox GROUP BY status"), "n", "status"); ?>
<div class="metrics metrics-3">
    <div class="stat stat-violet"><span class="stat-icon"><?= icon("hourglass") ?></span><strong><?= (int) ($mailCounts["pending"] ?? 0) + (int) ($mailCounts["sending"] ?? 0) ?></strong><span>Waiting to send</span></div>
    <div class="stat stat-green"><span class="stat-icon"><?= icon("check-circle") ?></span><strong><?= (int) ($mailCounts["sent"] ?? 0) ?></strong><span>Sent</span></div>
    <div class="stat stat-blue"><span class="stat-icon"><?= icon("mail") ?></span><strong><?= array_sum(array_map("intval", $mailCounts)) ?></strong><span>All emails</span></div>
</div>
<section class="data-section">
    <?php if (!$messages):
    empty_state(
        "No notifications queued",
        "Email notifications will appear here after email is enabled and appointments are updated.",
    );
else:
     ?>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Recipient</th>
                    <th>Notification</th>
                    <th>Delivery</th>
                    <th>Sent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (
    array_slice($messages, 0, 50)
    as $message
): ?>
                <tr>
                    <td>
                        <div class="mail-person">
                            <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($message["owner_name"], 0, 1))) ?></span>
                            <span>
                                <strong><?= e($message["owner_name"]) ?></strong>
                                <small><?= e($message["recipient"]) ?></small>
                                <?php if ($message["phone"]): ?><small><?= e($message["phone"]) ?></small><?php endif; ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="pet-cell">
                            <span class="pet-thumb" aria-hidden="true"><?php if ($message["photo_version"]): ?><img src="/pet-photo.php?id=<?= (int) $message["pet_id"] ?>&amp;v=<?= (int) $message["photo_version"] ?>" alt="" loading="lazy" /><?php else: ?><?= e(mb_strtoupper(mb_substr($message["pet_name"], 0, 1))) ?><?php endif; ?></span>
                            <span>
                                <a class="mail-ref" href="/admin/appointment.php?id=<?= (int) $message["appointment_id"] ?>"><?= e($message["booking_ref"]) ?></a>
                                <small><?= e($message["pet_name"]) ?> · <?= e(preg_replace('/^.*· /', "", $message["subject"])) ?></small>
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="availability <?= $message["status"] === "sent" ? "is-active" : "" ?>"><?= e(ucfirst($message["status"])) ?></span>
                        <?php if ((int) $message["attempts"] > 1): ?><small><?= (int) $message["attempts"] ?> attempts</small><?php endif; ?>
                        <?php if ($message["last_error"]): ?><small class="error-text"><?= e($message["last_error"]) ?></small><?php endif; ?>
                    </td>
                    <td class="no-wrap"><?= e(
    timestamp_label($message["created_at"]),
) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
endif; ?>
</section>
<?php pagination($page, count($messages) > 50); ?>

