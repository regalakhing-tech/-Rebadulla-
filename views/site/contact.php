<?php
$title = "Contact and clinic hours";
$data = clinic_data();
$now = new DateTimeImmutable();
$status = clinic_status($data["hours"], $now);
$byDay = array_column($data["hours"], null, "day_of_week");
$days = [1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday", 6 => "Saturday", 0 => "Sunday"];
$directions = "https://www.google.com/maps/search/?api=1&query=Rebadulla%20Animal%20Clinic%20San%20Jose%20de%20Buenavista%20Antique";

ob_start();
?>
<div class="pg-reach">
    <p class="pg-status<?= $status["open"] ? " is-open" : "" ?>"><span aria-hidden="true"></span><?= e($status["text"]) ?></p>
    <a href="tel:+639550295738">
        <?= icon("phone") ?>
        <span><small>Call the clinic</small><strong>0955 029 5738</strong></span>
    </a>
    <a href="mailto:rac.antique2007@gmail.com">
        <?= icon("mail") ?>
        <span><small>Email</small><strong>rac.antique2007@gmail.com</strong></span>
    </a>
    <a href="/owner/book.php">
        <?= icon("calendar") ?>
        <span><small>Online</small><strong>Book an appointment</strong></span>
    </a>
</div>
<?php
page_intro(
    "Get in touch",
    "Come by, or <em>give us a call.</em>",
    "We are in the CHVH Building on Cerdeña Street, right beside Purple Yam.",
    (string) ob_get_clean(),
);
?>

<section class="pg-section pg-visit" id="map">
    <div class="wrap pg-map-grid">
        <div class="pg-map">
            <iframe
                src="https://www.google.com/maps?q=Rebadulla%20Animal%20Clinic%2C%20Cerde%C3%B1a%20Street%2C%20San%20Jose%20de%20Buenavista%2C%20Antique&amp;output=embed"
                title="Map to Rebadulla Animal Clinic"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            ></iframe>
        </div>
        <div class="pg-address">
            <img <?= img("entrance", "(max-width: 960px) calc(100vw - 48px), 400px") ?> alt="The entrance of Rebadulla Animal Clinic" loading="lazy" decoding="async" />
            <h2>Find the clinic</h2>
            <address>CHVH Bldg, Cerdeña St<br />San Jose de Buenavista, Antique</address>
            <p>Look for the blue Rebadulla sign, right beside Purple Yam.</p>
            <a class="button button-secondary" href="<?= e($directions) ?>" target="_blank" rel="noopener"><?= icon("pin") ?>Get directions</a>
        </div>
    </div>
</section>

<section class="pg-section pg-white" id="hours">
    <div class="wrap pg-hours-grid">
        <div>
            <h2>Clinic <em>hours.</em></h2>
            <dl class="pg-hours">
                <?php foreach ($days as $number => $name):
                    $day = $byDay[$number] ?? null;
                    $today = $number === (int) $now->format("w"); ?>
                <div<?= $today ? ' class="is-today" aria-current="date"' : "" ?>>
                    <dt><?= $name ?><?php if ($today): ?> <small>Today</small><?php endif; ?></dt>
                    <dd><?= !$day || $day["is_closed"] ? "Closed" : e(clock_label($day["open_time"]) . " – " . clock_label($day["close_time"])) ?></dd>
                </div>
                <?php endforeach; ?>
            </dl>
        </div>
        <figure class="pg-storefront">
            <img <?= img("about-clinic", "(max-width: 960px) calc(100vw - 48px), 560px") ?> alt="The front of Rebadulla Animal Care on Cerdeña Street" loading="lazy" decoding="async" />
            <figcaption>
                <strong>Urgent case?</strong>
                Call before you come, so the team can get ready for your pet.
                <a class="pg-link" href="tel:+639550295738"><?= icon("phone") ?> 0955 029 5738</a>
            </figcaption>
        </figure>
    </div>
</section>

<?php page_closing(); ?>
