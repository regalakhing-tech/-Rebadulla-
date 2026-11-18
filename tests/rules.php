<?php
declare(strict_types=1);

require dirname(__DIR__) . "/src/security.php";
$module = dirname(__DIR__) . "/src/appointments.php";
if (!is_file($module)) {
    fwrite(STDERR, "FAIL appointment rules have not been implemented\n");
    exit(1);
}
require $module;
date_default_timezone_set("Asia/Manila");
$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException("FAIL " . $message);
    }
    $checks++;
}
function rejects(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (ValidationError) {
        check(true, $message);
        return;
    }
    check(false, $message);
}
$hours = [
    "open_time" => "08:00:00",
    "close_time" => "17:00:00",
    "is_closed" => false,
];
$now = new DateTimeImmutable("2026-11-16 07:00:00");
$slots = build_slots("2026-11-16", $hours, false, [], 2, $now);
check(count($slots) === 18, "eighteen half-hour slots");
check(
    $slots[0] === "08:00:00" && end($slots) === "16:30:00",
    "slot boundaries",
);
check(
    build_slots("2026-11-16", $hours, true, [], 2, $now) === [],
    "closed date",
);
check(
    build_slots(
        "2026-11-16",
        $hours + [],
        false,
        ["08:00:00" => 2],
        2,
        $now,
    )[0] === "08:30:00",
    "full slot removed",
);
check(
    build_slots(
        "2026-11-16",
        $hours,
        false,
        [],
        2,
        new DateTimeImmutable("2026-11-16 08:10"),
    )[0] === "08:30:00",
    "past slot removed",
);
check(
    build_slots("2026-02-30", $hours, false, [], 2, $now) === [],
    "impossible date",
);
check(
    build_slots(
        "2026-11-16",
        array_replace($hours, ["is_closed" => true]),
        false,
        [],
        2,
        $now,
    ) === [],
    "closed weekday",
);
check(
    next_status("owner", "proposed", "accept") === "confirmed",
    "owner accepts proposal",
);
check(
    next_status("admin", "pending", "confirm") === "confirmed",
    "admin confirms",
);
check(
    next_status("owner", "confirmed", "change") === "pending",
    "owner requests change",
);
check(
    next_status("admin", "confirmed", "reschedule") === "confirmed",
    "admin reschedules",
);
rejects(
    fn() => next_status("owner", "pending", "accept"),
    "cannot accept pending",
);
rejects(
    fn() => next_status("owner", "confirmed", "complete"),
    "owner cannot complete",
);
foreach (["completed", "missed", "cancelled"] as $status) {
    rejects(
        fn() => next_status("admin", $status, "confirm"),
        "terminal status",
    );
}
check(valid_date("2028-02-29") && !valid_date("2026-02-29"), "leap dates");
check(e("<script>") === "&lt;script&gt;", "HTML escaping");
$_POST['password'] = ' secret with spaces ';
check(password_field('password') === ' secret with spaces ', 'password bytes preserved');
$_POST['password'] = str_repeat('a', 73);
rejects(fn() => password_field('password'), 'reject passwords exceeding bcrypt byte limit');
$_POST['password'] = "secret\0value";
rejects(fn() => password_field('password'), 'reject null bytes before hashing');
$_POST = [];
require dirname(__DIR__) . "/src/site.php";
$week = [["day_of_week" => 0, "open_time" => null, "close_time" => null, "is_closed" => true]];
foreach (range(1, 6) as $day) {
    $week[] = ["day_of_week" => $day, "open_time" => "08:00:00", "close_time" => "17:00:00", "is_closed" => false];
}
$status = fn(string $when): array => clinic_status($week, new DateTimeImmutable($when));
check($status("2026-11-16 10:00:00") === ["open" => true, "text" => "Open now · until 5:00 PM"], "open during clinic hours");
check($status("2026-11-16 07:00:00")["text"] === "Closed now · opens today at 8:00 AM", "opens later today");
check($status("2026-11-16 17:00:00")["text"] === "Closed now · opens tomorrow at 8:00 AM", "closed at closing time");
check($status("2026-11-14 18:00:00")["text"] === "Closed now · opens Monday at 8:00 AM", "skips the closed Sunday");
check(clinic_status([], new DateTimeImmutable("2026-11-16 10:00:00"))["open"] === false, "no hours means not open");
echo "PASS $checks appointment and security rule checks\n";
