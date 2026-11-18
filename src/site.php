<?php
declare(strict_types=1);

// The public pages behind the About, Services, Gallery and Contact tabs.
const SITE_PAGES = [
    "/about.php" => [
        "view" => "site/about",
        "description" => "Meet Rebadulla Animal Care, a neighborhood veterinary clinic in San Jose de Buenavista, Antique, caring for pets since 2007.",
    ],
    "/services.php" => [
        "view" => "site/services",
        "description" => "Consultations, vaccination, parasite prevention and grooming at Rebadulla Animal Care, with starting prices and online booking.",
    ],
    "/gallery.php" => [
        "view" => "site/gallery",
        "description" => "Photos of the pets, families and team at Rebadulla Animal Care in San Jose de Buenavista, Antique.",
    ],
    "/contact.php" => [
        "view" => "site/contact",
        "description" => "Clinic hours, phone number, email and directions for Rebadulla Animal Care in San Jose de Buenavista, Antique.",
    ],
];

// Shares the home page cache, so saving services or hours in the admin updates every page.
function clinic_data(): array
{
    if (!configured() || env("LANDING_PREVIEW", "false") === "true") {
        $hours = [];
        foreach (range(0, 6) as $day) {
            $hours[] = [
                "day_of_week" => $day,
                "open_time" => $day ? "08:00:00" : null,
                "close_time" => $day ? "17:00:00" : null,
                "is_closed" => $day === 0,
            ];
        }
        return [
            "services" => [
                ["id" => 1, "name" => "Routine & Walk-in Consultation", "description" => "", "price_from" => 400],
                ["id" => 2, "name" => "Dog & Cat Vaccination", "description" => "", "price_from" => 250],
                ["id" => 3, "name" => "Parasite Prevention", "description" => "", "price_from" => 300],
                ["id" => 4, "name" => "Professional Grooming", "description" => "", "price_from" => null],
            ],
            "hours" => $hours,
        ];
    }
    return remember(
        "landing",
        300,
        fn() => [
            "services" => services(true),
            "hours" => rows(
                "SELECT day_of_week, open_time, close_time, is_closed FROM clinic_hours ORDER BY day_of_week",
            ),
        ],
    );
}

function clock_label(string $time): string
{
    return (new DateTimeImmutable($time))->format("g:i A");
}

// Whether the clinic is open at $now, and a short line saying when that changes.
function clinic_status(array $hours, DateTimeImmutable $now): array
{
    $byDay = array_column($hours, null, "day_of_week");
    $today = $byDay[(int) $now->format("w")] ?? null;
    if ($today && !$today["is_closed"]) {
        $time = $now->format("H:i:s");
        if ($time >= $today["open_time"] && $time < $today["close_time"]) {
            return ["open" => true, "text" => "Open now · until " . clock_label($today["close_time"])];
        }
        if ($time < $today["open_time"]) {
            return ["open" => false, "text" => "Closed now · opens today at " . clock_label($today["open_time"])];
        }
    }
    for ($offset = 1; $offset <= 7; $offset++) {
        $day = $now->modify("+" . $offset . " day");
        $next = $byDay[(int) $day->format("w")] ?? null;
        if ($next && !$next["is_closed"]) {
            $name = $offset === 1 ? "tomorrow" : $day->format("l");
            return ["open" => false, "text" => "Closed now · opens " . $name . " at " . clock_label($next["open_time"])];
        }
    }
    return ["open" => false, "text" => "Call the clinic for today's hours"];
}

// Copy and a real clinic photo for each service, matched by a word in its name.
function service_detail(string $name, string $description = ""): array
{
    $details = [
        "consult" => [
            "label" => "Health & wellness",
            "image" => "svc-visit",
            "alt" => "A pet owner holding her dog during a visit with clinic staff",
            "text" => "A thorough physical exam, vital signs, and advice on your pet's behavior and nutrition. Early detection is the start of a long, happy life.",
            "points" => ["Physical examination", "Nutrition advice", "Behavior advice"],
        ],
        "vaccin" => [
            "label" => "Immunization",
            "image" => "svc-vaccine",
            "alt" => "A Rebadulla veterinarian vaccinating a dog",
            "text" => "Vaccination plans for cats and dogs, matched to their age and lifestyle, with every shot written in their record book.",
            "points" => ["Anti-rabies", "5-in-1 / 4-in-1 core", "Deworming", "Record book"],
        ],
        "parasite" => [
            "label" => "Protection",
            "image" => "svc-protect",
            "alt" => "Clinic staff checking a dog while its owner helps",
            "text" => "Oral and spot-on preventatives against fleas, ticks and heartworms, plus testing for blood-borne parasites.",
            "points" => ["Tick & flea control", "Heartworm", "Blood parasite test"],
        ],
        "groom" => [
            "label" => "Hygiene",
            "image" => "svc-grooming",
            "alt" => "A freshly groomed dog smiling on the grooming table",
            "text" => "A calm grooming session that keeps skin healthy and coats free from matting.",
            "points" => ["Bath & blow dry", "Nail trim", "Ear cleaning", "Sanitary trim"],
        ],
        "surg" => [
            "label" => "Surgical care",
            "image" => "svc-surgery",
            "alt" => "The Rebadulla team performing surgery on a dog",
            "text" => "Elective spay and neuter and urgent soft-tissue surgery, with modern anesthesia and constant monitoring.",
            "points" => ["Spay & neuter (kapon)", "Soft tissue surgery", "Recovery room"],
        ],
    ];
    foreach ($details as $word => $detail) {
        if (stripos($name, $word) !== false) {
            return $detail;
        }
    }
    return [
        "label" => "Clinic service",
        "image" => "site-team",
        "alt" => "Clinic staff with a pet owner and her dog",
        "text" => $description,
        "points" => [],
    ];
}

function page_intro(string $label, string $titleHtml, string $lead, string $asideHtml = ""): void
{
    ?>
<section class="pg-intro">
    <div class="pg-intro-inner<?= $asideHtml !== "" ? " has-aside" : "" ?>">
        <div class="pg-intro-copy">
            <span class="eyebrow"><?= e($label) ?></span>
            <h1><?= $titleHtml ?></h1>
            <p><?= e($lead) ?></p>
        </div>
        <?= $asideHtml ?>
    </div>
</section>
<?php
}

function page_closing(): void
{
    ?>
<section class="pg-section pg-closing">
    <div class="wrap">
        <div class="cta-banner">
            <div>
                <h2>Ready for their next visit?</h2>
                <p>Request a time in under a minute. We will confirm it or suggest another.</p>
            </div>
            <div class="cta-actions">
                <a class="button button-white" href="/owner/book.php">Book an appointment <?= icon("arrow") ?></a>
                <a class="button button-outline-white" href="tel:+639550295738">Call the clinic</a>
            </div>
        </div>
    </div>
</section>
<?php
}
