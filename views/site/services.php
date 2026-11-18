<?php
$title = "Services and prices";
$data = clinic_data();
$services = $data["services"];
$hasSurgery = (bool) array_filter($services, fn(array $service): bool => stripos($service["name"], "surg") !== false);

ob_start();
?>
<nav class="pg-prices" aria-label="Services and starting prices">
    <h2>Starting prices</h2>
    <?php foreach ($services as $service): ?>
    <a href="#service-<?= (int) $service["id"] ?>">
        <span><?= e($service["name"]) ?></span>
        <strong><?= e(money($service["price_from"])) ?></strong>
    </a>
    <?php endforeach; ?>
</nav>
<?php
page_intro(
    "Our services",
    "Care for every stage <em>of their life.</em>",
    "Pick a service and book an open time. Prices shown are starting prices.",
    (string) ob_get_clean(),
);
?>

<section class="pg-section pg-white pg-service-list">
    <div class="wrap">
        <?php foreach ($services as $index => $service):
            $detail = service_detail($service["name"], $service["description"]); ?>
        <article class="pg-service" id="service-<?= (int) $service["id"] ?>">
            <span class="pg-number"><?= sprintf("%02d", $index + 1) ?></span>
            <img <?= img($detail["image"], "(max-width: 720px) calc(100vw - 48px), 340px") ?> alt="<?= e($detail["alt"]) ?>" <?= $index ? 'loading="lazy" decoding="async"' : "" ?> />
            <div class="pg-service-copy">
                <small><?= e($detail["label"]) ?></small>
                <h2><?= e(str_replace("-", "\u{2011}", $service["name"])) ?></h2>
                <?php if ($detail["text"]): ?><p><?= e($detail["text"]) ?></p><?php endif; ?>
                <?php if ($detail["points"]): ?>
                <ul>
                    <?php foreach ($detail["points"] as $point): ?><li><?= e($point) ?></li><?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <div class="pg-service-book">
                <small><?= $service["price_from"] !== null ? "Starts at" : "Price" ?></small>
                <strong><?= e(money($service["price_from"])) ?></strong>
                <a class="button button-small" href="/owner/book.php?service=<?= (int) $service["id"] ?>">Book now <?= icon("arrow") ?></a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="pg-section">
    <div class="wrap pg-extra">
        <img <?= img("site-surgery", "(max-width: 960px) calc(100vw - 48px), 460px") ?> alt="The medical team working together on a dog in the treatment room" loading="lazy" decoding="async" />
        <div>
            <h2>Also at <em>the clinic.</em></h2>
            <p>Ask us about these during your visit, or call to check what your pet needs.</p>
            <ul class="pg-rows">
                <li><?= icon("microscope") ?><strong>CBC &amp; blood chemistry</strong><span>Blood work that helps find what is wrong.</span></li>
                <li><?= icon("scan") ?><strong>Digital X-ray</strong><span>Clear images of bones, chest and abdomen.</span></li>
                <li><?= icon("pulse") ?><strong>Ultrasound</strong><span>A closer look at the organs, without surgery.</span></li>
                <?php if (!$hasSurgery): ?>
                <li><?= icon("heart") ?><strong>Surgery</strong><span>Spay, neuter and soft-tissue procedures.</span></li>
                <?php endif; ?>
            </ul>
            <a class="pg-link" href="tel:+639550295738"><?= icon("phone") ?> 0955 029 5738</a>
        </div>
    </div>
</section>

<section class="pg-section pg-white">
    <div class="wrap">
        <div class="pg-heading-row">
            <h2>Booking takes <em>three steps.</em></h2>
            <p>Request a time online and always know where your appointment stands.</p>
        </div>
        <ol class="pg-principles pg-steps">
            <li><h3>Request a visit</h3><p>Choose your pet, a service and an open time. Your booking reference appears right away.</p></li>
            <li><h3>The clinic confirms</h3><p>Staff confirm your time or propose a new one. Accept it, ask for a change, or cancel online.</p></li>
            <li><h3>Visit and keep the record</h3><p>After the visit, it stays in your pet’s history, including every schedule change.</p></li>
        </ol>
    </div>
</section>

<section class="pg-section">
    <div class="wrap">
        <div class="pg-heading-row">
            <h2>Treats &amp; supplies <em>at the clinic.</em></h2>
            <p>Pick them up during your visit. Ask the front desk about sizes and stock.</p>
        </div>
        <div class="pg-products">
            <?php foreach (
                [
                    ["prod-harness", "Harnesses & Leashes", "Durable gear for safe, secure walks.", "₱350–600"],
                    ["prod-ecollar", "E-Collars (The Cone)", "Stops licking or biting while wounds heal.", "₱250–320"],
                    ["prod-calming", "Calming Collars", "Scented collars that ease stress and anxiety.", "₱250–300"],
                    ["prod-pedigree", "Pedigree (1–5 kg)", "Balanced daily kibble for healthy dogs.", "₱135–320"],
                    ["prod-cuties", "Cuties Catz (1–5 kg)", "Affordable daily dry food for cats.", "₱130–700"],
                    ["prod-kidney", "Kidney Care", "Prescription nutrition for renal health.", "₱260–380"],
                ]
                as [$image, $name, $text, $price]
            ): ?>
            <article>
                <img <?= img($image, "(max-width: 720px) 44vw, 180px") ?> alt="<?= e($name) ?>" loading="lazy" decoding="async" />
                <div>
                    <h3><?= e($name) ?></h3>
                    <p><?= e($text) ?></p>
                    <strong><?= e($price) ?></strong>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php page_closing(); ?>
