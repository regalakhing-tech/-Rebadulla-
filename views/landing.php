<?php
$title = "Book pet clinic appointments online";
$data = configured() && env("LANDING_PREVIEW", "false") !== "true"
    ? remember(
        "landing",
        300,
        fn() => [
            "services" => services(true),
            "hours" => rows(
                "SELECT day_of_week, open_time, close_time, is_closed FROM clinic_hours ORDER BY day_of_week",
            ),
        ],
    )
    : [
        "services" => [
            ["id" => 1, "name" => "Routine & Walk-in Consultation", "description" => "", "price_from" => 400],
            ["id" => 2, "name" => "Dog & Cat Vaccination", "description" => "", "price_from" => 250],
            ["id" => 3, "name" => "Parasite Prevention", "description" => "", "price_from" => 300],
            ["id" => 4, "name" => "Professional Grooming", "description" => "", "price_from" => null],
        ],
        "hours" => [],
    ];
$years = (int) date("Y") - 2007;
$days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
$time = fn(string $value): string => (new DateTimeImmutable($value))->format("g:i A");
$todayHours = "Mon – Sat · 8:00 AM – 5:00 PM";
foreach ($data["hours"] as $day) {
    if ((int) $day["day_of_week"] === (int) date("w")) {
        $todayHours = $day["is_closed"]
            ? "Closed today"
            : "Open today · " . $time($day["open_time"]) . " – " . $time($day["close_time"]);
    }
}
$icons = [
    "microscope" => '<path d="M6 18h8M3 22h18M14 22a7 7 0 1 0 0-14h-1M9 14h2M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2zM12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3" />',
    "scan" => '<path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10" />',
    "pulse" => '<path d="M22 12h-4l-3 9L9 3l-3 9H2" />',
    "syringe" => '<path d="m18 2 4 4M17 7l3-3M19 9 8.7 19.3a2.4 2.4 0 0 1-3.4 0l-.6-.6a2.4 2.4 0 0 1 0-3.4L15 5M9 11l4 4M5 19l-3 3M14 4l6 6" />',
    "scissors" => '<circle cx="6" cy="6" r="3" /><circle cx="6" cy="18" r="3" /><path d="M20 4 8.1 15.9M14.5 14.5 20 20M8.1 8.1 12 12" />',
    "heart" => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7z" /><path d="M3.2 12h6.3l.5-1 2 4.5 2-7 1.5 3.5h5.3" />',
    "calendar" => '<rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18M8 15h3" />',
    "check" => '<circle cx="12" cy="12" r="9" /><path d="m8 12 3 3 5-6" />',
    "history" => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8" /><path d="M3 3v5h5M12 7v5l4 2" />',
    "shield" => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /><path d="m9 12 2 2 4-4" />',
    "smile" => '<circle cx="12" cy="12" r="9" /><path d="M8.5 14.5a4.5 4.5 0 0 0 7 0M9 9.5h.01M15 9.5h.01" />',
    "star" => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z" />',
    "clock" => '<circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" />',
    "phone" => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z" />',
    "mail" => '<rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 6L2 7" />',
    "pin" => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z" /><circle cx="12" cy="9.5" r="2.5" />',
    "arrow" => '<path d="M5 12h14M13 5l7 7-7 7" />',
];
$icon = fn(string $name): string => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
    $icons[$name] .
    "</svg>";
// Copy from the clinic's original website, matched to services by a word in their name.
$details = [
    "consult" => [
        "label" => "Health & wellness",
        "icon" => "heart",
        "image" => "svc-consult",
        "alt" => "A veterinarian listening to a bulldog's heart during a check-up",
        "text" => "Thorough physical exams, vital signs, and advice on your pet's behavior and nutrition. Early detection is the start of a long, happy life.",
        "points" => ["Physical examination", "Nutrition advice", "Behavior advice"],
    ],
    "vaccin" => [
        "label" => "Immunization",
        "icon" => "syringe",
        "image" => "svc-vaccine",
        "alt" => "A Rebadulla veterinarian vaccinating a dog",
        "text" => "Complete vaccination plans for cats and dogs, matched to their age and lifestyle, with every shot written in their record book.",
        "points" => ["Anti-rabies", "5-in-1 / 4-in-1 core", "Deworming", "Record book"],
    ],
    "parasite" => [
        "label" => "Protection",
        "icon" => "shield",
        "image" => "svc-parasite",
        "alt" => "A veterinarian removing a tick from a cat",
        "text" => "Modern oral and spot-on preventatives against fleas, ticks, and heartworms, plus testing for blood-borne parasites.",
        "points" => ["Tick & flea control", "Heartworm", "Blood parasite test"],
    ],
    "groom" => [
        "label" => "Hygiene",
        "icon" => "scissors",
        "image" => "svc-grooming",
        "alt" => "A freshly groomed dog smiling on the grooming table",
        "text" => "A calm, stress-free grooming session that keeps skin healthy and coats free from matting.",
        "points" => ["Bath & blow dry", "Nail trim", "Ear cleaning", "Sanitary trim"],
    ],
    "surg" => [
        "label" => "Surgical care",
        "icon" => "pulse",
        "image" => "svc-surgery",
        "alt" => "The Rebadulla team performing surgery on a dog",
        "text" => "Elective spay and neuter and urgent soft-tissue surgery, with modern anesthesia and constant monitoring.",
        "points" => ["Spay & neuter (kapon)", "Soft tissue surgery", "Recovery room"],
    ],
];
$products = [
    ["prod-harness", "Harnesses & Leashes", "Durable gear for safe, secure walks.", "₱350–600"],
    ["prod-ecollar", "E-Collars (The Cone)", "Stops licking or biting while wounds heal.", "₱250–320"],
    ["prod-calming", "Calming Collars", "Scented collars that ease stress and anxiety.", "₱250–300"],
    ["prod-pedigree", "Pedigree (1–5 kg)", "Balanced daily kibble for healthy dogs.", "₱135–320"],
    ["prod-cuties", "Cuties Catz (1–5 kg)", "Affordable daily dry food for cats.", "₱130–700"],
    ["prod-kidney", "Kidney Care", "Prescription nutrition for renal health.", "₱260–380"],
];
?>

<section class="hero" id="home">
    <div class="hero-inner">
        <div class="hero-copy">
            <span class="eyebrow">Rebadulla Animal Care · Since 2007</span>
            <h1>Healthy pets, <em>happy hearts.</em></h1>
            <p class="hero-lead">
                Book a clinic visit online, get your booking reference right away, and follow every appointment
                from request to confirmed — with your pet’s visit history saved in one place.
            </p>
            <div class="hero-actions">
                <a class="button" href="/owner/book.php">Book an appointment <?= $icon("arrow") ?></a>
                <a class="button button-secondary" href="/owner/appointments.php">Track my appointments</a>
            </div>
            <div class="trust">
                <div class="owners">
                    <span class="owners-faces">
                        <img <?= img("avatar-1", "44px") ?> alt="" />
                        <img <?= img("avatar-2", "44px") ?> alt="" />
                        <img <?= img("avatar-3", "44px") ?> alt="" />
                    </span>
                    <span><strong>2k+</strong> happy pet owners</span>
                </div>
                <div class="trust-stat"><strong><?= $years ?>+ years</strong><small>caring for Antique’s pets</small></div>
            </div>
            <p class="hero-hours"><span class="live-dot" aria-hidden="true"></span><?= e($todayHours) ?></p>
        </div>
        <div class="hero-visual">
            <span class="hero-plate hero-plate-back" aria-hidden="true"></span>
            <span class="hero-plate hero-plate-mid" aria-hidden="true"></span>
            <figure class="hero-photo">
                <img <?= img("hero-visit", "(max-width: 1100px) 80vw, 460px") ?> alt="Rebadulla veterinarians checking a cat during a clinic outreach" fetchpriority="high" />
            </figure>
        </div>
    </div>
</section>

<section class="capabilities" aria-label="Clinic capabilities">
    <ul>
        <li><?= $icon("microscope") ?>CBC &amp; Blood Chem</li>
        <li><?= $icon("scan") ?>Digital X-Ray</li>
        <li><?= $icon("pulse") ?>Ultrasound</li>
        <li><?= $icon("syringe") ?>Vaccination</li>
        <li><?= $icon("heart") ?>Surgery</li>
        <li><?= $icon("scissors") ?>Grooming</li>
    </ul>
</section>

<section class="section" id="how">
    <div class="wrap how">
        <div>
            <div class="section-head left">
                <span class="eyebrow">How booking works</span>
                <h2>From request to visit in <em>three steps.</em></h2>
                <p>No more waiting on the phone. Request a time online and always know where your appointment stands.</p>
            </div>
            <ol class="steps">
                <li>
                    <span class="step-icon"><?= $icon("calendar") ?></span>
                    <div>
                        <b>Step 1</b>
                        <h3>Request a visit</h3>
                        <p>Choose your pet, a service, and an open time. Your booking reference appears right away.</p>
                    </div>
                </li>
                <li>
                    <span class="step-icon"><?= $icon("check") ?></span>
                    <div>
                        <b>Step 2</b>
                        <h3>The clinic confirms</h3>
                        <p>Staff confirm your time or propose a new one. Accept it, ask for a change, or cancel online.</p>
                    </div>
                </li>
                <li>
                    <span class="step-icon"><?= $icon("history") ?></span>
                    <div>
                        <b>Step 3</b>
                        <h3>Visit and keep the record</h3>
                        <p>After the visit, it stays in your pet’s history — including every schedule change.</p>
                    </div>
                </li>
            </ol>
        </div>
        <div class="app-mock" aria-hidden="true">
            <div class="app-window">
                <div class="app-window-top">
                    <strong>My appointments</strong>
                    <span class="mock-dots"><i></i><i></i><i></i></span>
                </div>
                <div class="mock-row">
                    <span class="mock-pet">M</span>
                    <div><strong>Mochi · Vaccination</strong><small>Tue, Nov 24 · 9:30 AM</small></div>
                    <span class="status status-confirmed"><span></span>Confirmed</span>
                </div>
                <div class="mock-row">
                    <span class="mock-pet is-alt">B</span>
                    <div><strong>Bantay · Check-up</strong><small>New time proposed · 2:30 PM</small></div>
                    <span class="status status-proposed"><span></span>Proposed</span>
                </div>
                <div class="mock-row">
                    <span class="mock-pet is-soft">K</span>
                    <div><strong>Kitkat · Grooming</strong><small>Waiting for the clinic</small></div>
                    <span class="status status-pending"><span></span>Pending</span>
                </div>
                <div class="mock-history">
                    <span><?= $icon("history") ?> Rescheduled by clinic staff</span>
                    <p><s>Nov 20 · 10:00 AM</s> → <strong>Nov 24 · 9:30 AM</strong></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section-soft">
    <div class="wrap">
        <div class="section-head">
            <span class="eyebrow">One system, two sides</span>
            <h2>Built for pet owners <em>and</em> clinic staff.</h2>
            <p>Owners always know when to visit. Staff see the whole schedule in one place, avoid overbooking, and find any pet’s past visits.</p>
        </div>
        <div class="sides">
            <article class="side-card">
                <img <?= img("side-owner", "(max-width: 700px) 92vw, 280px") ?> alt="A pet owner holding her dog at the clinic" loading="lazy" decoding="async" />
                <div class="side-body">
                    <span class="side-label">For pet owners</span>
                    <h3>Book, track, and remember every visit.</h3>
                    <ul class="tick-list">
                        <li>Register your pets once</li>
                        <li>Request a service, date, and time</li>
                        <li>See if it’s pending or confirmed</li>
                        <li>Accept a new time, change, or cancel</li>
                        <li>View your pets’ past appointments</li>
                    </ul>
                    <a class="text-link" href="/register.php">Create a free owner account →</a>
                </div>
            </article>
            <article class="side-card side-staff">
                <img <?= img("side-staff", "(max-width: 700px) 92vw, 280px") ?> alt="Rebadulla staff treating a dog while its owner helps" loading="lazy" decoding="async" />
                <div class="side-body">
                    <span class="side-label">For clinic staff</span>
                    <h3>One schedule. Every record. No overbooking.</h3>
                    <ul class="tick-list">
                        <li>Review and confirm online requests</li>
                        <li>Record phone bookings and walk-ins</li>
                        <li>Set clinic hours and service slots</li>
                        <li>Mark visits completed or missed</li>
                        <li>Search records and see change history</li>
                    </ul>
                    <a class="text-link" href="/admin-login.php">Staff sign in →</a>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="section" id="about">
    <div class="wrap bento">
        <figure class="bento-photo">
            <img <?= img("about-clinic", "(max-width: 1100px) 92vw, 620px") ?> alt="The Rebadulla Animal Clinic storefront in San Jose de Buenavista" loading="lazy" decoding="async" />
        </figure>
        <div class="bento-text">
            <span class="eyebrow">About the clinic</span>
            <h2>Your partner in <em>rural furparenting.</em></h2>
            <p>
                Finding reliable veterinary care in rural areas shouldn’t be a challenge. Our clinic in
                <strong>San Jose de Buenavista</strong> brings city-level diagnostics to the warmth of a
                neighborhood clinic.
            </p>
        </div>
        <div class="bento-stat is-blue"><strong><?= $years ?>+</strong><span>Years of care since 2007</span></div>
        <div class="bento-stat"><strong>2k+</strong><span>Happy pet owners</span></div>
    </div>
</section>

<section class="section section-tight" id="services">
    <div class="wrap">
        <div class="section-head">
            <span class="eyebrow">Our services</span>
            <h2>Care for every stage of <em>their life.</em></h2>
            <p>Pick a service and book an open time. Prices shown are starting prices.</p>
        </div>
        <div class="service-grid">
            <?php foreach ($data["services"] as $index => $service):
                $detail = null;
                foreach ($details as $word => $candidate) {
                    if (stripos($service["name"], $word) !== false) {
                        $detail = $candidate;
                        break;
                    }
                }
                $detail ??= [
                    "label" => "Clinic service",
                    "icon" => "heart",
                    "image" => "svc-general",
                    "alt" => "A veterinarian examining a dog",
                    "text" => $service["description"],
                    "points" => [],
                ];
                ?>
            <article class="service-card">
                <div class="service-photo">
                    <img <?= img($detail["image"], "(max-width: 900px) 92vw, 600px") ?> alt="<?= e($detail["alt"]) ?>" loading="lazy" decoding="async" />
                    <span class="service-chip"><?= $icon($detail["icon"]) ?><?= e($detail["label"]) ?></span>
                </div>
                <div class="service-body">
                    <span class="service-number"><?= sprintf("%02d", $index + 1) ?></span>
                    <h3><?= e(str_replace("-", "\u{2011}", $service["name"])) ?></h3>
                    <?php if ($detail["text"]): ?>
                    <p><?= e($detail["text"]) ?></p>
                    <?php endif; ?>
                    <?php if ($detail["points"]): ?>
                    <ul class="service-points">
                        <?php foreach ($detail["points"] as $point): ?>
                        <li><?= e($point) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <div class="service-foot">
                        <div>
                            <small><?= $service["price_from"] !== null ? "Starts at" : "Price" ?></small>
                            <strong><?= e(money($service["price_from"])) ?></strong>
                        </div>
                        <a class="button button-small" href="/owner/book.php?service=<?= (int) $service["id"] ?>">Book now <?= $icon("arrow") ?></a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="why">
    <img class="why-bg" <?= img("why-bg", "100vw") ?> alt="" loading="lazy" decoding="async" />
    <div class="wrap">
        <div class="section-head">
            <span class="eyebrow eyebrow-light">Why Rebadulla</span>
            <h2>Exceptional care for your best friends.</h2>
        </div>
        <div class="why-grid">
            <?php foreach (
                [
                    ["check", "Personalized treatment", "Plans built for your pet’s age and health history.", ["Tailored care", "Recovery tracking"]],
                    ["smile", "Safe environment", "A calm, sterilized space for your pet’s peace of mind.", ["Calm atmosphere", "Safe handling"]],
                    ["star", "Trusted clinic", "Helping families since 2007 with modern diagnostic tools.", ["Local experience", "Modern labs"]],
                    ["clock", "Emergency support", "Here for you during critical, unexpected moments.", ["Rapid response", "24/7 availability"]],
                ]
                as [$name, $heading, $text, $points]
            ): ?>
            <article class="why-card">
                <span class="why-icon"><?= $icon($name) ?></span>
                <h3><?= $heading ?></h3>
                <p><?= $text ?></p>
                <ul>
                    <?php foreach ($points as $point): ?>
                    <li><?= $point ?></li>
                    <?php endforeach; ?>
                </ul>
            </article>
            <?php endforeach; ?>
        </div>
        <figure class="quote">
            <span class="quote-mark" aria-hidden="true">“</span>
            <div>
                <blockquote>We provide premium care that feels like home for your furry family members.</blockquote>
                <figcaption>Dr. Pirmelou · Rebadulla Animal Care</figcaption>
            </div>
        </figure>
    </div>
</section>

<section class="section" id="gallery">
    <div class="wrap">
        <div class="split-head">
            <div>
                <span class="eyebrow">Patient &amp; team moments</span>
                <h2>Life at <em>Rebadulla.</em></h2>
            </div>
            <p>Moments of healing, wagging tails, and the families who trust us with their pets.</p>
        </div>
        <div class="gallery-grid">
            <?php for ($photo = 1; $photo <= 7; $photo++): ?>
            <figure>
                <img <?= img("gallery-" . $photo, $photo === 1 ? "(max-width: 700px) 92vw, 50vw" : "(max-width: 700px) 92vw, 25vw") ?> alt="A pet and its family at Rebadulla Animal Care" loading="lazy" decoding="async" />
            </figure>
            <?php endfor; ?>
        </div>
    </div>
</section>

<section class="section section-tight" id="supplies">
    <div class="wrap">
        <div class="split-head">
            <div>
                <span class="eyebrow">Pet essentials</span>
                <h2>Treats &amp; supplies <em>at the clinic.</em></h2>
            </div>
            <p>Pick them up during your visit. Ask the front desk about sizes and stock.</p>
        </div>
        <div class="product-marquee" data-marquee tabindex="0" role="button" aria-pressed="false" aria-label="Supplies list. Scrolls by itself; drag or swipe to browse, click to stop or start.">
            <div class="product-track">
<?php // The list is drawn twice so the loop scrolls without a gap; screen readers get it once.
foreach ([false, true] as $copy):
    foreach ($products as [$image, $name, $text, $price]): ?>
                <article class="product"<?= $copy ? ' aria-hidden="true"' : "" ?>>
                    <img <?= img($image, "260px") ?> alt="<?= $copy ? "" : e($name) ?>" loading="lazy" decoding="async" draggable="false" />
                    <h3><?= e($name) ?></h3>
                    <p><?= e($text) ?></p>
                    <div class="product-foot">
                        <strong><?= e($price) ?></strong>
                        <span>In clinic</span>
                    </div>
                </article>
<?php endforeach;
endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="section section-tight" id="contact">
    <div class="wrap">
        <div class="cta-banner">
            <div>
                <h2>Ready for their next visit?</h2>
                <p>Request a time in under a minute. We will confirm it or suggest another.</p>
            </div>
            <div class="cta-actions">
                <a class="button button-white" href="/owner/book.php">Book an appointment <?= $icon("arrow") ?></a>
                <a class="button button-outline-white" href="tel:+639550295738">Call the clinic</a>
            </div>
        </div>
        <div class="contact-grid">
            <div class="map">
                <iframe
                    src="https://www.google.com/maps?q=Rebadulla%20Animal%20Clinic%2C%20Cerde%C3%B1a%20Street%2C%20San%20Jose%20de%20Buenavista%2C%20Antique&amp;output=embed"
                    title="Map to Rebadulla Animal Clinic"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                ></iframe>
                <a class="map-link" href="https://www.google.com/maps/search/?api=1&amp;query=Rebadulla%20Animal%20Clinic%20San%20Jose%20de%20Buenavista%20Antique" target="_blank" rel="noopener"><?= $icon("pin") ?>Open in Google Maps</a>
            </div>
            <div class="contact-card">
                <img class="entrance" <?= img("entrance", "(max-width: 1100px) 92vw, 460px") ?> alt="The entrance of Rebadulla Animal Clinic" loading="lazy" decoding="async" />
                <div>
                    <h3>Find us locally</h3>
                    <p>CHVH Bldg, Cerdeña St, San Jose, Antique — right beside Purple Yam.</p>
                </div>
                <div class="hours-table">
                    <?php if ($data["hours"]):
                        foreach ($data["hours"] as $day): ?>
                    <div>
                        <span><?= $days[(int) $day["day_of_week"]] ?></span>
                        <strong><?= $day["is_closed"] ? "Closed" : e($time($day["open_time"]) . " – " . $time($day["close_time"])) ?></strong>
                    </div>
                    <?php endforeach;
                    else: ?>
                    <div><span>Monday – Saturday</span><strong>8:00 AM – 5:00 PM</strong></div>
                    <div><span>Sunday</span><strong>Closed</strong></div>
                    <?php endif; ?>
                </div>
                <div class="contact-lines">
                    <a href="tel:+639550295738"><?= $icon("phone") ?>0955 029 5738</a>
                    <a href="mailto:rac.antique2007@gmail.com"><?= $icon("mail") ?>rac.antique2007@gmail.com</a>
                </div>
            </div>
        </div>
    </div>
</section>
