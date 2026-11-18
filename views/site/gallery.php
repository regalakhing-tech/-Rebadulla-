<?php
$title = "Gallery";
$groups = [
    "families" => "Pets & families",
    "patients" => "Patients",
    "care" => "Care",
    "community" => "Community",
    "clinic" => "The clinic",
];
// image, group, shown wide, description
$photos = [
    ["g-10", "families", false, "A woman holding her white dog on the clinic bench"],
    ["g-4", "clinic", true, "The clinic supplies room, with blue walls and full shelves"],
    ["g-17", "patients", false, "A smiling brown dog on a rattan chair"],
    ["g-29", "care", false, "The medical team working together on a dog"],
    ["g-36", "families", true, "Two clinic staff with an owner holding a fluffy white dog"],
    ["g-2", "patients", false, "A white dog with its tongue out in the sun"],
    ["g-12", "families", false, "A golden retriever standing up to greet its owner"],
    ["g-24", "care", false, "A veterinarian vaccinating a dog"],
    ["g-39", "families", true, "Clinic staff and families with their dogs in the supplies room"],
    ["g-40", "patients", false, "Two fluffy dogs looking out of a travel crate"],
    ["g-16", "families", false, "A woman in glasses holding her tan dog"],
    ["g-41", "community", false, "Veterinary staff examining a pet at an outdoor clinic"],
    ["g-20", "clinic", true, "The front of Rebadulla Animal Care"],
    ["g-1", "patients", false, "A gray and white cat sitting in a cage"],
    ["g-11", "families", false, "A man in a cap holding a brown puppy"],
    ["g-7", "care", false, "A woman comforting her dog during treatment"],
    ["g-22", "care", true, "Two veterinarians in surgical gowns treating a cat"],
    ["g-30", "patients", false, "A freshly groomed dog smiling on the grooming table"],
    ["g-13", "families", false, "An owner sitting beside his dog’s crate with a bag of dog food"],
    ["g-32", "community", false, "The team treating a pet on a table during a community visit"],
    ["g-8", "clinic", true, "The clinic waiting area, with chairs and pet supplies on the wall"],
    ["g-18", "families", false, "Three young people on the clinic sofa with their dog"],
    ["g-6", "patients", false, "A cream-colored dog resting during treatment with an IV line"],
    ["g-35", "families", false, "Clinic staff and an owner with a small brown dog"],
    ["g-100", "care", true, "A veterinarian in blue scrubs treating a gray dog"],
    ["g-33", "families", false, "A pet owner holding her dog beside a staff member"],
    ["g-42", "community", false, "The team and pet owners on a wooden porch"],
    ["g-5", "clinic", true, "Pets resting in glass kennels below shelves of pet food"],
    ["g-43", "families", false, "A family outdoors holding their two dogs beside a pickup truck"],
    ["g-38", "care", false, "Clinic staff checking a dog while its owner helps"],
    ["g-19", "clinic", true, "The entrance of Rebadulla Animal Clinic"],
];
page_intro(
    "Patient & team moments",
    "Life at <em>Rebadulla.</em>",
    "Moments of healing, wagging tails, and the families who trust us with their pets.",
);
?>

<section class="pg-section pg-gallery" data-gallery>
    <div class="wrap">
        <div class="pg-filters" role="group" aria-label="Filter photos">
            <button type="button" data-filter="all" aria-pressed="true">All photos</button>
            <?php foreach ($groups as $key => $label): ?>
            <button type="button" data-filter="<?= $key ?>" aria-pressed="false"><?= e($label) ?></button>
            <?php endforeach; ?>
            <span class="pg-count" data-count aria-live="polite"><?= count($photos) ?> photos</span>
        </div>
        <div class="pg-photos">
            <?php foreach ($photos as $index => [$image, $group, $wide, $alt]):
                $sources = images()[$image]["src"]; ?>
            <a class="pg-photo<?= $wide ? " is-wide" : "" ?>" href="<?= e(end($sources)) ?>" data-photo data-group="<?= $group ?>" aria-label="View photo: <?= e($alt) ?>">
                <img <?= img($image, $wide ? "(max-width: 720px) calc(100vw - 48px), (max-width: 960px) 62vw, 620px" : "(max-width: 720px) 45vw, (max-width: 960px) 31vw, 300px") ?> alt="<?= e($alt) ?>" <?= $index > 3 ? 'loading="lazy" decoding="async"' : "" ?> />
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<dialog class="pg-viewer" data-viewer aria-label="Photo viewer">
    <button class="pg-viewer-close" type="button" data-viewer-close aria-label="Close photo"><?= icon("x") ?></button>
    <button class="pg-viewer-step" type="button" data-viewer-prev aria-label="Previous photo"><?= icon("arrow-left") ?></button>
    <figure>
        <img alt="" data-viewer-image />
        <figcaption><span data-viewer-caption></span><small data-viewer-position></small></figcaption>
    </figure>
    <button class="pg-viewer-step" type="button" data-viewer-next aria-label="Next photo"><?= icon("arrow") ?></button>
</dialog>

<?php page_closing(); ?>
