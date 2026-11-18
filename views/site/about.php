<?php
$title = "About the clinic";
$years = (int) date("Y") - 2007;
page_intro(
    "About the clinic",
    "A neighborhood clinic <em>since 2007.</em>",
    "Rebadulla Animal Care looks after pets and their families in San Jose de Buenavista, Antique.",
);
?>

<section class="pg-lead-photo">
    <div class="wrap">
        <figure>
            <img <?= img("site-interior", "(max-width: 1288px) calc(100vw - 48px), 1240px") ?> alt="Inside Rebadulla Animal Care, with shelves of pet food and supplies along blue walls" fetchpriority="high" />
        </figure>
        <dl class="pg-facts">
            <div><dt>Years of care</dt><dd><?= $years ?>+</dd></div>
            <div><dt>Happy pet owners</dt><dd>2k+</dd></div>
            <div><dt>Where to find us</dt><dd>San Jose, Antique</dd></div>
        </dl>
    </div>
</section>

<section class="pg-section">
    <div class="wrap pg-story">
        <div class="pg-story-copy">
            <h2>Good vet care should be <em>close to home.</em></h2>
            <p>Finding reliable veterinary care in rural areas shouldn’t be a challenge. Our clinic in San Jose de Buenavista brings city-level diagnostics to the warmth of a neighborhood clinic.</p>
            <p>Blood work, X-rays, ultrasound, vaccinations and grooming all happen under one roof, and you can pick up food and supplies during the same visit.</p>
            <a class="pg-link" href="/services.php">See our services <?= icon("arrow") ?></a>
        </div>
        <div class="pg-story-photos">
            <img <?= img("site-team", "(max-width: 960px) 70vw, 420px") ?> alt="Two clinic staff with an owner holding a fluffy white dog" loading="lazy" decoding="async" />
            <img <?= img("site-floor", "(max-width: 960px) 60vw, 340px") ?> alt="The clinic waiting area, with chairs and pet supplies on the wall" loading="lazy" decoding="async" />
        </div>
    </div>
</section>

<section class="pg-section pg-white">
    <div class="wrap pg-columns">
        <div class="pg-column-head">
            <h2>What we can do <em>here.</em></h2>
            <p>The tools and people your pet needs, without a long trip to the city.</p>
        </div>
        <ul class="pg-rows">
            <?php foreach (
                [
                    ["microscope", "CBC & blood chemistry", "Blood work that helps find what is wrong."],
                    ["scan", "Digital X-ray", "Clear images of bones, chest and abdomen."],
                    ["pulse", "Ultrasound", "A closer look at the organs, without surgery."],
                    ["heart", "Surgery", "Spay, neuter and soft-tissue procedures."],
                    ["syringe", "Vaccination", "Core vaccines, anti-rabies and deworming."],
                    ["scissors", "Grooming", "Bath, nail trim, ear cleaning and sanitary trim."],
                ]
                as [$name, $heading, $text]
            ): ?>
            <li><?= icon($name) ?><strong><?= e($heading) ?></strong><span><?= e($text) ?></span></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="pg-section">
    <div class="wrap">
        <figure class="pg-quote">
            <div>
                <blockquote>We provide premium care that feels like home for your furry family members.</blockquote>
                <figcaption>Dr. Pirmelou · Rebadulla Animal Care</figcaption>
            </div>
            <img <?= img("side-owner", "(max-width: 720px) calc(100vw - 80px), 280px") ?> alt="A pet owner holding her dog at the clinic" loading="lazy" decoding="async" />
        </figure>
        <ol class="pg-principles">
            <?php foreach (
                [
                    ["Personalized treatment", "Plans built for your pet’s age and health history."],
                    ["Safe environment", "A calm, sterilized space for your pet’s peace of mind."],
                    ["Trusted clinic", "Helping families since 2007 with modern diagnostic tools."],
                    ["Emergency support", "Here for you during critical, unexpected moments."],
                ]
                as [$heading, $text]
            ): ?>
            <li><h3><?= e($heading) ?></h3><p><?= e($text) ?></p></li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section class="pg-section pg-white">
    <div class="wrap">
        <div class="pg-heading-row">
            <h2>Out in the <em>community.</em></h2>
            <p>Some days the work happens away from the clinic, with families who bring their pets to meet us.</p>
        </div>
        <div class="pg-trio">
            <img <?= img("g-41", "(max-width: 720px) 80vw, 400px") ?> alt="Veterinary staff examining a pet at an outdoor clinic" loading="lazy" decoding="async" />
            <img <?= img("g-32", "(max-width: 720px) 80vw, 400px") ?> alt="The team treating a pet on a table during a community visit" loading="lazy" decoding="async" />
            <img <?= img("g-42", "(max-width: 720px) 80vw, 400px") ?> alt="The team and pet owners on a wooden porch during a community visit" loading="lazy" decoding="async" />
        </div>
        <a class="pg-link" href="/gallery.php">See more photos <?= icon("arrow") ?></a>
    </div>
</section>

<?php page_closing(); ?>
