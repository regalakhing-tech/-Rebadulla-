<?php
$title = "Online requests";
$page = max(0, (int) ($_GET["page"] ?? 0));
$records = rows(
    "SELECT a.*, p.name AS pet_name, u.full_name AS owner_name, u.phone, s.name AS service_name
    FROM appointments a JOIN pets p ON p.id = a.pet_id JOIN users u ON u.id = p.owner_id
    JOIN services s ON s.id = a.service_id WHERE a.status = 'pending'
    ORDER BY a.updated_at, a.id LIMIT 51 OFFSET " .
        $page * 50,
);
page_heading(
    "Online requests",
    "Oldest first. Confirm or propose a new time.",
);
notices($error);
?>
<section class="data-section"><?php appointment_table(
    array_slice($records, 0, 50),
    "admin",
); ?></section>
<?php pagination($page, count($records) > 50); ?>

