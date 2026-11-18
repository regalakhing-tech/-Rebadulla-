# Rebadulla Animal Care: Appointment and Records Management System

A website where pet owners book clinic visits online and clinic staff manage every appointment record in one place.

The implemented PHP application uses Supabase PostgreSQL and PHPMailer, with Vercel configuration included for later deployment. Follow [SETUP.md](SETUP.md) to install dependencies, configure Supabase, create an administrator, and run the application. The sections below retain the original project specification.

---

## 1. Overview

The Rebadulla Animal Care Appointment and Records Management System is a proposed website that helps pet owners schedule visits and clinic staff manage all appointment records in one place.

**Pet Owner side.** Owners register their pets, choose a service, and request a preferred date and time. They receive a booking reference and can check whether their request is pending or confirmed, accept a new time proposed by the clinic, request a change, or cancel. They can also view past appointments for their own pets.

**Admin side.** Clinic staff review online requests, confirm appointments based on availability, propose another schedule, and record bookings made by phone and visits made as walk-ins. Staff also manage clinic hours and service availability, mark visits as completed or missed, and view reports by date, service, or appointment status.

**All Appointment Records.** Every record stays searchable by owner name, pet name, or booking reference, including completed and cancelled appointments. When an appointment is rescheduled, the system updates the same record and keeps a history of the previous schedule, who changed it, and when.

**Why it matters.** Pet owners know when to visit. Staff get one place to check the clinic's schedule, avoid overbooking, and find a pet's previous visits.

---

## 2. Users and What They Can Do

### Pet Owner (registered online)

| Feature | Description |
|---|---|
| Register / log in | Create an account with name, email, phone, and password. |
| My Pets | Add and edit pets (name, species, breed, sex, birth date, notes). |
| Book appointment | Choose a pet, a service, a date, and an open time slot. Receive a booking reference (example: `RAC-7KQ2MX`). |
| My Appointments | See each appointment's status: Pending, Proposed, Confirmed, Completed, Missed, or Cancelled. |
| Respond to the clinic | Accept a new time proposed by the clinic, request a change, or cancel. |
| Past visits | See completed, missed, and cancelled appointments for their own pets. |

### Admin (clinic staff)

| Feature | Description |
|---|---|
| Dashboard | Today's schedule, number of pending requests, proposals waiting for the owner. |
| Online Requests | Review pending requests. Confirm (only if the slot has space) or propose another time. |
| Phone / Walk-in | Record a booking made by phone, or a walk-in visit. Create the owner and pet on the spot if they are new. |
| Schedule | View the clinic schedule for any day. Reschedule, mark completed, or mark missed. |
| Clinic Hours | Set opening and closing times for each day of the week. Add closed dates (holidays). |
| Services | Add or edit services, set how many bookings each time slot can take, turn a service on or off. |
| All Appointment Records | Search every appointment by owner name, pet name, or booking reference. Open a record to see its full change history. |
| Reports | Count appointments by date, by service, and by status for any date range. |

---

## 3. Appointment Statuses and Rules

| Status | Meaning |
|---|---|
| `pending` | The owner sent a request (or a change request). The clinic has not answered yet. |
| `proposed` | The clinic suggested a different date or time. Waiting for the owner. |
| `confirmed` | The appointment is booked. It takes up one place in its time slot. |
| `completed` | The visit happened. |
| `missed` | The owner did not come. |
| `cancelled` | The owner or the clinic cancelled it. |

`completed`, `missed`, and `cancelled` are final. Records are never deleted.

### Who can change what

| Current status | New status | Who | Button |
|---|---|---|---|
| pending | pending (new time) | Owner | Request change |
| pending | confirmed | Admin | Confirm |
| pending | proposed | Admin | Propose new time |
| pending | cancelled | Owner or Admin | Cancel |
| proposed | confirmed | Owner | Accept new time |
| proposed | confirmed | Admin | Confirm (owner agreed by phone) |
| proposed | proposed (new time) | Admin | Propose another time |
| proposed | pending (new time) | Owner | Request change |
| proposed | cancelled | Owner or Admin | Cancel |
| confirmed | confirmed (new time) | Admin | Reschedule |
| confirmed | proposed | Admin | Propose new time |
| confirmed | pending (new time) | Owner | Request change |
| confirmed | completed | Admin | Mark completed |
| confirmed | missed | Admin | Mark missed |
| confirmed | cancelled | Owner or Admin | Cancel |

### Business rules

1. **Clinic hours.** Times are offered in 30-minute slots inside the clinic hours for that day. Closed days and closed dates have no slots.
2. **No overbooking.** Each service has a `max_per_slot` value. A slot is full when it already has that many **confirmed** appointments for the service. A full slot cannot be confirmed or proposed.
3. **No past times.** A new or changed schedule must be in the future.
4. **Completed / missed** can only be set after the scheduled time.
5. **Phone bookings and walk-ins** are saved as `confirmed` right away. Walk-ins skip the slot check because the pet is already at the clinic.
6. **One record per appointment.** Rescheduling updates the same row. Every change adds one row to the history table with the old schedule, the new schedule, who made the change, and when.

---

## 4. Requirement-to-Implementation Map

Use this table during your defense to show where each requirement lives.

| Requirement | Page | Database |
|---|---|---|
| Owners register pets | `owner/pets.php` | `pets` |
| Choose service, preferred date and time | `owner/book.php`, `slots.php` | `services`, `clinic_hours`, `appointments` |
| Booking reference | `create_appointment()` in `includes/appointments.php` | `appointments.booking_ref` |
| Check pending / confirmed | `owner/appointments.php` | `appointments.status` |
| Accept proposed time, request change, cancel | `owner/appointment.php` | `appointments`, `appointment_history` |
| View past appointments of own pets | `owner/appointments.php` (Past tab) | `appointments` + `pets` |
| Review online requests, confirm, propose | `admin/requests.php`, `admin/appointment.php` | `appointments` |
| Confirm based on availability | `slot_is_full()` | `services.max_per_slot` |
| Record phone bookings and walk-ins | `admin/new-appointment.php` | `appointments.source` |
| Manage clinic hours | `admin/hours.php` | `clinic_hours`, `clinic_closures` |
| Manage service availability | `admin/services.php` | `services.is_active`, `services.max_per_slot` |
| Mark completed or missed | `admin/schedule.php` | `appointments.status` |
| Reports by date, service, status | `admin/reports.php` | `appointments` (GROUP BY queries) |
| Search all records | `admin/records.php` | `appointments` + `pets` + `users` |
| Reschedule history (old schedule, who, when) | `admin/appointment.php` (History) | `appointment_history` |

---

## 5. Technology Stack

| Part | Choice | Why |
|---|---|---|
| Front end | HTML, CSS, JavaScript (reuse the existing WS101 landing page design) | The site design already exists. |
| Back end | PHP 8 (plain PHP, no framework) | Runs on XAMPP and almost every cheap host. |
| Database | MySQL / MariaDB | Comes with XAMPP. Works with phpMyAdmin. |
| Database access | PDO with prepared statements | Built into PHP. Blocks SQL injection. |
| Local server | XAMPP (Apache + PHP + MariaDB) | One installer for everything. |

No extra libraries are needed. XAMPP ships MariaDB instead of MySQL. All SQL in this document works on both.

---

## 6. Database Design

### Tables

| Table | Holds |
|---|---|
| `users` | Pet owners and admins. Phone and walk-in owners are users with no password (they cannot log in). |
| `pets` | Each pet belongs to one owner. |
| `services` | Clinic services, starting price, places per slot, active flag. |
| `clinic_hours` | Open and close time for each day of the week. |
| `clinic_closures` | Specific closed dates (holidays). |
| `appointments` | One row per appointment. The current schedule and status. |
| `appointment_history` | One row per change: old and new status, old and new schedule, who, when. |

### Relationships

```
users (owner) ──< pets ──< appointments >── services
                                │
                                └──< appointment_history >── users (who made the change)

A ──< B  means one A has many B.
```

The owner of an appointment is found through its pet (`appointments.pet_id` → `pets.owner_id`).

### `database/schema.sql`

```sql
CREATE DATABASE IF NOT EXISTS rebadulla_care CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rebadulla_care;

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    role          ENUM('owner', 'admin') NOT NULL DEFAULT 'owner',
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NULL UNIQUE,
    phone         VARCHAR(20)  NULL,
    password_hash VARCHAR(255) NULL,          -- NULL = phone/walk-in owner with no online account
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_name (full_name)
);

CREATE TABLE pets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    owner_id   INT NOT NULL,
    name       VARCHAR(60) NOT NULL,
    species    VARCHAR(30) NOT NULL,          -- Dog, Cat, Rabbit, ...
    breed      VARCHAR(60) NULL,
    sex        ENUM('male', 'female', 'unknown') NOT NULL DEFAULT 'unknown',
    birth_date DATE NULL,
    notes      TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id),
    INDEX idx_pets_name (name)
);

CREATE TABLE services (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    description  TEXT NULL,
    price_from   DECIMAL(10, 2) NULL,
    max_per_slot INT NOT NULL DEFAULT 1,      -- confirmed bookings allowed in one 30-minute slot
    is_active    TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE clinic_hours (
    day_of_week TINYINT PRIMARY KEY,          -- 0 = Sunday ... 6 = Saturday (same as PHP date('w'))
    open_time   TIME NULL,
    close_time  TIME NULL,
    is_closed   TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE clinic_closures (
    closed_date DATE PRIMARY KEY,
    reason      VARCHAR(150) NULL
);

CREATE TABLE appointments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref CHAR(10) NOT NULL UNIQUE,     -- e.g. RAC-7KQ2MX
    pet_id      INT NOT NULL,
    service_id  INT NOT NULL,
    appt_date   DATE NOT NULL,
    appt_time   TIME NOT NULL,
    status      ENUM('pending', 'proposed', 'confirmed', 'completed', 'missed', 'cancelled')
                NOT NULL DEFAULT 'pending',
    source      ENUM('online', 'phone', 'walk_in') NOT NULL DEFAULT 'online',
    owner_notes TEXT NULL,
    staff_notes TEXT NULL,
    created_by  INT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id)     REFERENCES pets(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_slot (service_id, appt_date, appt_time, status),
    INDEX idx_status_date (status, appt_date)
);

CREATE TABLE appointment_history (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    action         VARCHAR(30) NOT NULL,      -- created, confirmed, proposed, accepted, change_requested,
                                              -- rescheduled, completed, missed, cancelled
    old_status     VARCHAR(20) NULL,
    old_date       DATE NULL,
    old_time       TIME NULL,
    new_status     VARCHAR(20) NOT NULL,
    new_date       DATE NOT NULL,
    new_time       TIME NOT NULL,
    note           VARCHAR(255) NULL,
    changed_by     INT NOT NULL,
    changed_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (changed_by)     REFERENCES users(id),
    INDEX idx_history_appt (appointment_id)
);
```

### `database/seed.sql`

Services and hours are taken from the current Rebadulla website (Mon–Sat, 8 AM–5 PM).

```sql
USE rebadulla_care;

INSERT INTO services (name, description, price_from, max_per_slot) VALUES
('Routine & Walk-in Consultation', 'Physical examination, vital signs, nutrition and behavior advice', 400.00, 2),
('Dog & Cat Vaccination', 'Anti-rabies, 5-in-1 / 4-in-1 core vaccines, deworming, vaccine record book', 250.00, 2),
('Parasite Prevention', 'Tick and flea control, heartworm protection, blood parasite testing', 300.00, 2),
('Professional Grooming', 'Full bath and blow dry, nail trimming, ear cleaning, sanitary trim', NULL, 1);

INSERT INTO clinic_hours (day_of_week, open_time, close_time, is_closed) VALUES
(0, NULL,    NULL,    1),   -- Sunday closed
(1, '08:00', '17:00', 0),
(2, '08:00', '17:00', 0),
(3, '08:00', '17:00', 0),
(4, '08:00', '17:00', 0),
(5, '08:00', '17:00', 0),
(6, '08:00', '17:00', 0);
```

Change the `max_per_slot` values to match how many pets the clinic can really handle at the same time.

---

## 7. Project Folder Structure

```
C:\xampp\htdocs\rebadulla\
├── index.html              ← existing WS101 landing page
├── css\  js\  image\       ← existing assets
├── slots.php               ← returns open time slots as JSON
├── login.php
├── register.php
├── logout.php
├── includes\
│   ├── db.php              ← database connection
│   ├── auth.php            ← sessions, role checks, CSRF, HTML escaping
│   ├── appointments.php    ← all appointment rules (slots, capacity, status changes, history)
│   ├── header.php
│   └── footer.php
├── owner\
│   ├── dashboard.php
│   ├── pets.php
│   ├── book.php
│   ├── appointments.php    ← list (Upcoming / Past)
│   └── appointment.php     ← one appointment: accept, request change, cancel
├── admin\
│   ├── dashboard.php
│   ├── requests.php
│   ├── appointment.php     ← one appointment: confirm, propose, reschedule, mark, history
│   ├── new-appointment.php ← phone booking / walk-in
│   ├── schedule.php
│   ├── records.php
│   ├── hours.php
│   ├── services.php
│   └── reports.php
└── database\
    ├── schema.sql
    └── seed.sql
```

---

## 8. Step-by-Step Implementation

Build in this order. Each step ends with a "Done when" check. Do not move on until the check passes.

### Step 1: Set up the environment

1. Install XAMPP (PHP 8.0 or newer).
2. Open the XAMPP Control Panel. Start **Apache** and **MySQL**.
3. Create the folder `C:\xampp\htdocs\rebadulla\`.
4. Copy the existing WS101 website files (HTML, CSS, JS, `image\` folder) into it.
5. Open `http://localhost/rebadulla/` in the browser.

**Done when:** the current landing page loads from localhost.

### Step 2: Create the database

1. Open `http://localhost/phpmyadmin`.
2. Go to the **SQL** tab. Paste and run `database/schema.sql`, then `database/seed.sql`.
3. Create the first admin account. Generate a password hash in a terminal:

   ```
   C:\xampp\php\php.exe -r "echo password_hash('ChangeThisPassword!', PASSWORD_DEFAULT);"
   ```

4. Insert the admin, pasting the hash from step 3:

   ```sql
   INSERT INTO users (role, full_name, email, password_hash)
   VALUES ('admin', 'Clinic Admin', 'admin@rebadulla.local', '<paste hash here>');
   ```

**Done when:** all seven tables exist, `services` has 4 rows, `clinic_hours` has 7 rows, and `users` has 1 admin.

### Step 3: Shared includes

**`includes/db.php`**

```php
<?php
date_default_timezone_set('Asia/Manila');

$pdo = new PDO('mysql:host=localhost;dbname=rebadulla_care;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
// Each query must see the latest saved rows; the overbooking check depends on it.
$pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
```

**`includes/auth.php`**

```php
<?php
const BASE_URL = '/rebadulla';   // change to '' if the site runs at the domain root

session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

// Call at the top of every owner or admin page.
function require_role(string $role): array
{
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    return $user;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

// Call first in every POST handler. Every form needs a hidden input named "csrf" holding csrf_token().
function check_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid form. Please go back and try again.');
    }
}

// Escape every value printed into HTML, for example: echo e($pet['name']);
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
```

**`includes/header.php` / `footer.php`:** copy the navbar and footer from the landing page. Show different links by role:

- Logged out: Home, Services, Login, Register.
- Owner: My Pets, Book, My Appointments, Logout.
- Admin: Dashboard, Requests, Schedule, New Booking, Records, Reports, Hours, Services, Logout.

**Done when:** a test page that requires `db.php` and `auth.php` loads with no errors.

### Step 4: Appointment rules module (the core of the system)

Every page that creates or changes an appointment calls these functions. The rules live in one place, so no page can skip them.

**`includes/appointments.php`**

```php
<?php
// Thrown for problems the user can fix (full slot, wrong status). Pages show its message.
class BookingError extends Exception {}

const SLOT_MINUTES = 30;

// Status changes each role may make: current status => allowed new statuses.
const TRANSITIONS = [
    'owner' => [
        'pending'   => ['pending', 'cancelled'],
        'proposed'  => ['confirmed', 'pending', 'cancelled'],
        'confirmed' => ['pending', 'cancelled'],
    ],
    'admin' => [
        'pending'   => ['confirmed', 'proposed', 'cancelled'],
        'proposed'  => ['confirmed', 'proposed', 'cancelled'],
        'confirmed' => ['confirmed', 'proposed', 'completed', 'missed', 'cancelled'],
    ],
];

function new_booking_ref(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // no 0/O or 1/I: easier to read over the phone
    $ref = 'RAC-';
    for ($i = 0; $i < 6; $i++) {
        $ref .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $ref;
}

// Every slot start time ('H:i:s') the clinic is open on $date. Empty when closed or $date is invalid.
function clinic_slots(PDO $pdo, string $date): array
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return [];
    }
    $q = $pdo->prepare('SELECT 1 FROM clinic_closures WHERE closed_date = ?');
    $q->execute([$date]);
    if ($q->fetch()) {
        return [];
    }
    $q = $pdo->prepare('SELECT open_time, close_time, is_closed FROM clinic_hours WHERE day_of_week = ?');
    $q->execute([$d->format('w')]);
    $h = $q->fetch();
    if (!$h || $h['is_closed']) {
        return [];
    }
    $slots = [];
    $end = strtotime("$date {$h['close_time']}");
    for ($t = strtotime("$date {$h['open_time']}"); $t + SLOT_MINUTES * 60 <= $end; $t += SLOT_MINUTES * 60) {
        $slots[] = date('H:i:s', $t);
    }
    return $slots;
}

// True when the slot already has max_per_slot confirmed bookings for this service.
// An inactive or unknown service counts as full, so it cannot be booked.
function slot_is_full(PDO $pdo, int $serviceId, string $date, string $time, int $ignoreId = 0): bool
{
    // FOR UPDATE locks the service row until the transaction ends, so two staff
    // confirming at the same moment cannot both take the last place.
    $q = $pdo->prepare('SELECT max_per_slot FROM services WHERE id = ? AND is_active = 1 FOR UPDATE');
    $q->execute([$serviceId]);
    $max = (int)$q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM appointments
        WHERE service_id = ? AND appt_date = ? AND appt_time = ? AND status = 'confirmed' AND id <> ?");
    $q->execute([$serviceId, $date, $time, $ignoreId]);
    return (int)$q->fetchColumn() >= $max;
}

// Slots a user can pick: clinic open, in the future, not full.
function open_slots(PDO $pdo, string $date, int $serviceId): array
{
    $open = [];
    foreach (clinic_slots($pdo, $date) as $time) {
        if (strtotime("$date $time") > time() && !slot_is_full($pdo, $serviceId, $date, $time)) {
            $open[] = $time;
        }
    }
    return $open;
}

// $old and $new are arrays with keys status, appt_date, appt_time.
function log_history(PDO $pdo, int $apptId, string $action, ?array $old, array $new, ?string $note, int $userId): void
{
    $pdo->prepare('INSERT INTO appointment_history
            (appointment_id, action, old_status, old_date, old_time, new_status, new_date, new_time, note, changed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            $apptId, $action,
            $old['status'] ?? null, $old['appt_date'] ?? null, $old['appt_time'] ?? null,
            $new['status'], $new['appt_date'], $new['appt_time'],
            $note, $userId,
        ]);
}

// $a keys: pet_id, service_id, appt_date, appt_time, status, source, owner_notes.
// Returns the new booking reference.
function create_appointment(PDO $pdo, array $a, int $userId): string
{
    $pdo->beginTransaction();
    try {
        // Walk-ins are already at the clinic, so they skip the open-slot check.
        if ($a['source'] !== 'walk_in'
            && !in_array($a['appt_time'], open_slots($pdo, $a['appt_date'], (int)$a['service_id']), true)) {
            throw new BookingError('That date and time is not available. Please pick another.');
        }
        $insert = $pdo->prepare('INSERT INTO appointments
            (booking_ref, pet_id, service_id, appt_date, appt_time, status, source, owner_notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        for ($try = 1; ; $try++) {
            $ref = new_booking_ref();
            try {
                $insert->execute([$ref, $a['pet_id'], $a['service_id'], $a['appt_date'], $a['appt_time'],
                                  $a['status'], $a['source'], $a['owner_notes'] ?? null, $userId]);
                break;
            } catch (PDOException $e) {
                // 1062 = duplicate key: this random reference is taken, draw another.
                if (($e->errorInfo[1] ?? 0) !== 1062 || $try === 5) {
                    throw $e;
                }
            }
        }
        $id = (int)$pdo->lastInsertId();
        log_history($pdo, $id, 'created', null, $a, ucfirst(str_replace('_', '-', $a['source'])) . ' booking', $userId);
        $pdo->commit();
        return $ref;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// The only way to change an appointment. Updates the same row and writes one history row.
function change_appointment(PDO $pdo, int $id, string $role, string $newStatus, string $action, int $userId,
                            ?string $newDate = null, ?string $newTime = null, ?string $note = null): void
{
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT * FROM appointments WHERE id = ? FOR UPDATE');
        $q->execute([$id]);
        $old = $q->fetch();
        if (!$old || !in_array($newStatus, TRANSITIONS[$role][$old['status']] ?? [], true)) {
            throw new BookingError("This change is not allowed for the appointment's current status.");
        }
        $date = $newDate ?? $old['appt_date'];
        $time = $newTime ?? $old['appt_time'];
        $when = strtotime("$date $time");

        if (($newDate !== null || $newTime !== null) && !in_array($time, clinic_slots($pdo, $date), true)) {
            throw new BookingError('Please pick a time within clinic hours.');
        }
        if (in_array($newStatus, ['pending', 'proposed', 'confirmed'], true) && $when <= time()) {
            throw new BookingError('That date and time has already passed.');
        }
        if (in_array($newStatus, ['proposed', 'confirmed'], true)
            && slot_is_full($pdo, (int)$old['service_id'], $date, $time, $id)) {
            throw new BookingError('That time slot is already full. Please choose another time.');
        }
        if (in_array($newStatus, ['completed', 'missed'], true) && $when > time()) {
            throw new BookingError('You can mark an appointment completed or missed only after its scheduled time.');
        }

        $pdo->prepare('UPDATE appointments SET status = ?, appt_date = ?, appt_time = ? WHERE id = ?')
            ->execute([$newStatus, $date, $time, $id]);
        log_history($pdo, $id, $action, $old,
                    ['status' => $newStatus, 'appt_date' => $date, 'appt_time' => $time], $note, $userId);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
```

**`slots.php`** (used by the booking, propose, and phone-booking forms):

```php
<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/appointments.php';

if (!current_user()) {
    http_response_code(401);
    exit;
}
header('Content-Type: application/json');
echo json_encode(open_slots($pdo, $_GET['date'] ?? '', (int)($_GET['service_id'] ?? 0)));
```

**Done when:** after logging in (Step 5), `http://localhost/rebadulla/slots.php?date=<next Monday>&service_id=1` returns 18 times from `"08:00:00"` to `"16:30:00"`, and a Sunday date returns `[]`.

### Step 5: Register, log in, log out

**`register.php`**

1. Form fields: full name, email, phone, password, confirm password.
2. Validate on the server: all fields filled, valid email (`filter_var($email, FILTER_VALIDATE_EMAIL)`), password at least 8 characters, both passwords match.
3. If the email already exists, show: "This email is already registered. If you booked by phone before, please contact the clinic."
4. Insert with `role = 'owner'` and `password_hash(password, PASSWORD_DEFAULT)`.
5. Log the user in and redirect to `owner/dashboard.php`.

**`login.php`**

```php
$q = $pdo->prepare('SELECT id, role, full_name, password_hash FROM users WHERE email = ?');
$q->execute([trim($_POST['email'] ?? '')]);
$u = $q->fetch();

if ($u && $u['password_hash'] && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
    session_regenerate_id(true);   // new session id after login blocks session fixation
    $_SESSION['user'] = ['id' => (int)$u['id'], 'role' => $u['role'], 'name' => $u['full_name']];
    header('Location: ' . BASE_URL . ($u['role'] === 'admin' ? '/admin/dashboard.php' : '/owner/dashboard.php'));
    exit;
}
$error = 'Wrong email or password.';
```

**`logout.php`:** `session_destroy()`, then redirect to the landing page.

**Done when:** a new owner can register, log out, and log in again. The admin from Step 2 lands on the admin dashboard. Opening an `admin/` page as an owner sends you to the login page.

### Step 6: Owner – My Pets (`owner/pets.php`)

1. `$user = require_role('owner');`
2. List the owner's pets: `SELECT * FROM pets WHERE owner_id = ? ORDER BY name`.
3. Add form: name, species, breed, sex, birth date, notes.
4. Edit: load the pet with `WHERE id = ? AND owner_id = ?` so an owner can never edit another owner's pet.
5. Do not allow deleting a pet that has appointments. Its visit history must stay.

**Done when:** an owner can add and edit pets, and changing the `id` in the URL to another owner's pet shows "not found".

### Step 7: Owner – Book an appointment (`owner/book.php`)

1. If the owner has no pets, show a link to My Pets.
2. Form fields: pet (owner's pets only), service (`WHERE is_active = 1`), date, time, notes.
3. Load time options when the date or service changes:

   ```html
   <input type="date" name="appt_date" id="appt_date" min="<?= date('Y-m-d') ?>" required>
   <select name="appt_time" id="appt_time" required>
       <option value="">Pick a service and date first</option>
   </select>

   <script>
   const dateEl = document.getElementById('appt_date');
   const timeEl = document.getElementById('appt_time');
   const serviceEl = document.getElementById('service_id');

   async function loadSlots() {
       if (!dateEl.value || !serviceEl.value) return;
       const res = await fetch(`../slots.php?date=${dateEl.value}&service_id=${serviceEl.value}`);
       const slots = await res.json();
       timeEl.innerHTML = slots.length
           ? slots.map(t => `<option value="${t}">${t.slice(0, 5)}</option>`).join('')
           : '<option value="">No open times on this day</option>';
   }
   dateEl.addEventListener('change', loadSlots);
   serviceEl.addEventListener('change', loadSlots);
   </script>
   ```

4. On submit:

   ```php
   check_csrf();
   // The pet must belong to the logged-in owner. Never trust the form.
   $q = $pdo->prepare('SELECT id FROM pets WHERE id = ? AND owner_id = ?');
   $q->execute([(int)$_POST['pet_id'], $user['id']]);
   if (!$q->fetch()) {
       exit('Invalid pet.');
   }
   try {
       $ref = create_appointment($pdo, [
           'pet_id'      => (int)$_POST['pet_id'],
           'service_id'  => (int)$_POST['service_id'],
           'appt_date'   => $_POST['appt_date'] ?? '',
           'appt_time'   => $_POST['appt_time'] ?? '',
           'status'      => 'pending',
           'source'      => 'online',
           'owner_notes' => trim($_POST['owner_notes'] ?? '') ?: null,
       ], $user['id']);
       header('Location: appointments.php?booked=' . urlencode($ref));
       exit;
   } catch (BookingError $e) {
       $error = $e->getMessage();
   }
   ```

5. On the next page, show: "Request sent. Your booking reference is **RAC-XXXXXX**. Status: Pending."

**Done when:** booking creates one `appointments` row with status `pending` and one `appointment_history` row with action `created`. Sundays, past times, and full slots cannot be booked.

### Step 8: Owner – My Appointments

**`owner/appointments.php`** (list)

```sql
SELECT a.id, a.booking_ref, a.appt_date, a.appt_time, a.status, p.name AS pet_name, s.name AS service_name
FROM appointments a
JOIN pets p     ON p.id = a.pet_id
JOIN services s ON s.id = a.service_id
WHERE p.owner_id = ?
ORDER BY a.appt_date DESC, a.appt_time DESC;
```

- **Upcoming:** status `pending`, `proposed`, or `confirmed`.
- **Past:** status `completed`, `missed`, or `cancelled`.
- Show each status as a colored badge. Put `proposed` appointments at the top with the message "The clinic proposed a new time. Please accept or request a change."

**`owner/appointment.php?id=…`** (one appointment)

```php
$user = require_role('owner');
$id = (int)($_GET['id'] ?? 0);

// Load the appointment only if one of this owner's pets owns it.
$q = $pdo->prepare('SELECT a.*, p.name AS pet_name, s.name AS service_name
    FROM appointments a
    JOIN pets p     ON p.id = a.pet_id
    JOIN services s ON s.id = a.service_id
    WHERE a.id = ? AND p.owner_id = ?');
$q->execute([$id, $user['id']]);
$appt = $q->fetch();
if (!$appt) {
    http_response_code(404);
    exit('Appointment not found.');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        match ($_POST['action'] ?? '') {
            'accept' => change_appointment($pdo, $id, 'owner', 'confirmed', 'accepted', $user['id']),
            'change' => change_appointment($pdo, $id, 'owner', 'pending', 'change_requested', $user['id'],
                            $_POST['appt_date'] ?? '', $_POST['appt_time'] ?? '', trim($_POST['note'] ?? '') ?: null),
            'cancel' => change_appointment($pdo, $id, 'owner', 'cancelled', 'cancelled', $user['id']),
            default  => throw new BookingError('Unknown action.'),
        };
        header("Location: appointment.php?id=$id&saved=1");
        exit;
    } catch (BookingError $e) {
        $error = $e->getMessage();
    }
}
```

Show buttons only when the status allows them (see the table in Section 3):

- `proposed`: **Accept new time**, **Request change**, **Cancel**.
- `pending` or `confirmed`: **Request change**, **Cancel**.
- Final statuses: no buttons.

Ask "Are you sure?" before cancelling (`onclick="return confirm('Cancel this appointment?')"`).

**Done when:** an owner can accept a proposed time, request a change (status returns to `pending` with the new time), and cancel. Each action adds one history row.

### Step 9: Admin – Dashboard and Online Requests

**`admin/dashboard.php`**

- Count pending requests: `SELECT COUNT(*) FROM appointments WHERE status = 'pending'`.
- Count proposals waiting for owners: same query with `status = 'proposed'`.
- Today's confirmed appointments ordered by time, with pet, owner, and service.

**`admin/requests.php`**

- List all `pending` appointments, oldest first: `ORDER BY a.updated_at`.
- Show booking reference, owner, phone, pet, service, requested date and time, owner notes.
- Each row links to `admin/appointment.php?id=…`.

**`admin/appointment.php?id=…`**

- `$admin = require_role('admin');`
- Show the full appointment, the owner's contact details, and the pet's past visits.
- Actions, all through `change_appointment($pdo, $id, 'admin', ...)`:

| Button | Call |
|---|---|
| Confirm | `'confirmed', 'confirmed'` |
| Propose new time | `'proposed', 'proposed', $date, $time, $note` |
| Reschedule (confirmed only) | `'confirmed', 'rescheduled', $date, $time, $note` |
| Mark completed | `'completed', 'completed'` |
| Mark missed | `'missed', 'missed'` |
| Cancel | `'cancelled', 'cancelled', null, null, $reason` |

- Reuse the date + time picker from Step 7 for the propose and reschedule forms.
- Show the history timeline at the bottom (Step 13).

**Done when:** confirming a request in a full slot shows "That time slot is already full". Proposing a time makes the owner see it under Upcoming with the Accept button.

### Step 10: Admin – Phone bookings and walk-ins (`admin/new-appointment.php`)

1. **Find the owner.** Search users by name or phone (`WHERE role = 'owner' AND (full_name LIKE ? OR phone LIKE ?)`).
2. **Or add a new owner.** Name and phone are required, email is optional, `password_hash` stays NULL.
3. **Choose or add the pet** for that owner.
4. **Choose the type:**
   - **Phone booking:** pick service, date, and an open time (same picker as Step 7). Save with `status = 'confirmed'`, `source = 'phone'`.
   - **Walk-in:** pick the service. Date is today, time is now (`date('H:i:00')`). Save with `status = 'confirmed'`, `source = 'walk_in'`.
5. Call `create_appointment(...)` with `$admin['id']` as the user.
6. Show the booking reference so staff can read it to the caller.

**Done when:** a phone booking appears on the schedule for its date. A walk-in appears on today's schedule. Both show the correct `source` in Records.

### Step 11: Admin – Schedule (`admin/schedule.php`)

1. Date picker at the top. Default is today.
2. Query the confirmed, completed, and missed appointments for that date, ordered by time:

   ```sql
   SELECT a.*, p.name AS pet_name, u.full_name AS owner_name, u.phone, s.name AS service_name
   FROM appointments a
   JOIN pets p     ON p.id = a.pet_id
   JOIN users u    ON u.id = p.owner_id
   JOIN services s ON s.id = a.service_id
   WHERE a.appt_date = ? AND a.status IN ('confirmed', 'completed', 'missed')
   ORDER BY a.appt_time;
   ```

3. Group rows by time. Next to each slot, show "2 of 2 booked" per service so staff can see full slots.
4. For confirmed appointments whose time has passed, show **Completed** and **Missed** buttons.

**Done when:** staff can view any day, mark a past appointment completed or missed, and cannot mark a future one.

### Step 12: Admin – Clinic Hours and Services

**`admin/hours.php`**

1. Show 7 rows (Sunday to Saturday) with open time, close time, and a "Closed" checkbox.
2. Save with one `UPDATE clinic_hours SET open_time = ?, close_time = ?, is_closed = ? WHERE day_of_week = ?` per row.
3. Validate: open time is before close time when the day is not closed.
4. Closed dates: add a date + reason (`INSERT INTO clinic_closures`), remove a date (`DELETE`).
5. When adding a closed date, warn if confirmed appointments already exist on that date and list them so staff can reschedule.

**`admin/services.php`**

1. List services with name, starting price, places per slot, and Active / Inactive.
2. Add and edit form: name, description, price, `max_per_slot` (at least 1).
3. Toggle `is_active`. Inactive services disappear from the booking form. Do not delete services; old appointments point to them.

**Done when:** marking Saturday as closed removes Saturday slots from the booking form. Turning off Grooming hides it from owners.

### Step 13: All Appointment Records (`admin/records.php`)

1. One search box and optional filters (status, service, date range).
2. Search query:

   ```php
   $term = '%' . trim($_GET['q'] ?? '') . '%';
   $offset = max(0, (int)($_GET['page'] ?? 0)) * 50;

   $q = $pdo->prepare("SELECT a.id, a.booking_ref, a.appt_date, a.appt_time, a.status, a.source,
                              p.name AS pet_name, u.full_name AS owner_name, s.name AS service_name
       FROM appointments a
       JOIN pets p     ON p.id = a.pet_id
       JOIN users u    ON u.id = p.owner_id
       JOIN services s ON s.id = a.service_id
       WHERE u.full_name LIKE ? OR p.name LIKE ? OR a.booking_ref LIKE ?
       ORDER BY a.appt_date DESC, a.appt_time DESC
       LIMIT 50 OFFSET $offset");
   $q->execute([$term, $term, $term]);
   ```

3. Results include every status, including completed and cancelled.
4. Each row opens `admin/appointment.php?id=…`.
5. On that page, show the history timeline:

   ```sql
   SELECT h.*, u.full_name AS changed_by_name, u.role AS changed_by_role
   FROM appointment_history h
   JOIN users u ON u.id = h.changed_by
   WHERE h.appointment_id = ?
   ORDER BY h.changed_at, h.id;
   ```

   Display each row like this:

   > **Nov 18, 2026 9:14 AM** – Rescheduled by **Clinic Admin** (admin)
   > Nov 20, 2026 10:00 AM → Nov 21, 2026 2:30 PM. Note: "Vet unavailable in the morning."

6. Add a "Pet's previous visits" list: all appointments with the same `pet_id`.

**Done when:** searching a pet name, an owner name, or a booking reference finds the record. A rescheduled appointment shows its old and new schedule, who changed it, and when.

### Step 14: Reports (`admin/reports.php`)

Inputs: a start date and an end date (default: this month).

```sql
-- By status
SELECT status, COUNT(*) AS total
FROM appointments
WHERE appt_date BETWEEN ? AND ?
GROUP BY status;

-- By service
SELECT s.name,
       COUNT(*)                     AS total,
       SUM(a.status = 'completed')  AS completed,
       SUM(a.status = 'missed')     AS missed,
       SUM(a.status = 'cancelled')  AS cancelled
FROM appointments a
JOIN services s ON s.id = a.service_id
WHERE a.appt_date BETWEEN ? AND ?
GROUP BY s.id, s.name
ORDER BY total DESC;

-- By date
SELECT appt_date, COUNT(*) AS total
FROM appointments
WHERE appt_date BETWEEN ? AND ?
GROUP BY appt_date
ORDER BY appt_date;
```

Show each result as a simple table. Add a "Print" button (`window.print()`) for paper copies.

**Done when:** the totals in each report match a manual count in phpMyAdmin for the same date range.

### Step 15: Connect the landing page

1. Point **Appoint Now**, **Start Visit**, and each **Book …** button to `owner/book.php`.
2. `require_role('owner')` already sends logged-out visitors to the login page.
3. Add a "Register" link on the login page.
4. Optional: rename `index.html` to `index.php` so the navbar can show "My Appointments" when an owner is logged in.

**Done when:** a visitor can go from the landing page to a booking reference without typing any URL.

### Step 16: Test everything

Run every case in Section 9. Fix failures before the demo.

### Step 17: Deploy (optional)

1. Export the database from phpMyAdmin (**Export → SQL**).
2. Create a database on a PHP host (for example, InfinityFree or Hostinger). Import the SQL file.
3. Upload the `rebadulla` folder with the host's file manager or FTP.
4. Edit `includes/db.php` with the host's database name, user, and password.
5. Set `BASE_URL` in `includes/auth.php` to match the site's folder.
6. Turn off error display on the live site (`display_errors = Off`).
7. Change the admin password.

---

## 9. Testing Checklist

| # | Test | Expected result |
|---|---|---|
| 1 | Register with an email that already exists | Error message, no new user |
| 2 | Log in with a wrong password | "Wrong email or password." |
| 3 | Owner opens an `admin/` page | Redirected to login |
| 4 | Owner changes `?id=` to another owner's appointment | "Appointment not found." |
| 5 | Book on a Sunday or a closed date | No time slots offered; a forced POST is rejected |
| 6 | Book a time that has already passed today | Not offered; a forced POST is rejected |
| 7 | Book online | Status `pending`, reference `RAC-XXXXXX`, history row `created` |
| 8 | Admin confirms when the slot has space | Status `confirmed`, history row `confirmed` |
| 9 | Admin confirms when the slot is full | "That time slot is already full." Status unchanged |
| 10 | Admin proposes a new time | Status `proposed`; owner sees Accept button |
| 11 | Owner accepts the proposed time | Status `confirmed`, history row `accepted` |
| 12 | Owner requests a change on a confirmed appointment | Status `pending`, new time saved, history shows old and new time |
| 13 | Owner cancels | Status `cancelled`; record still appears in Records |
| 14 | Owner POSTs `accept` on a `pending` appointment | "This change is not allowed…" Status unchanged |
| 15 | Admin marks a future appointment completed | Error, status unchanged |
| 16 | Admin records a phone booking | Status `confirmed`, source `phone`, reference shown |
| 17 | Admin records a walk-in | Status `confirmed`, source `walk_in`, appears on today's schedule |
| 18 | Admin reschedules a confirmed appointment | Same booking reference, new time, history shows who and when |
| 19 | Search by owner name, pet name, and booking reference | Each finds the record, including completed and cancelled ones |
| 20 | Two browser windows confirm the last place in a slot at the same moment | Only one succeeds |
| 21 | Turn a service off | It disappears from the owner booking form |
| 22 | Type `<script>alert(1)</script>` as a pet name | Shown as text, no popup |
| 23 | Submit a form without the CSRF token | "Invalid form." |
| 24 | Reports for a date range | Totals match a manual count |

---

## 10. Security Checklist

- [ ] Passwords stored with `password_hash()` and checked with `password_verify()`.
- [ ] Every SQL query uses prepared statements. No user input is joined into SQL text. (The `LIMIT/OFFSET` value is cast to `int` first.)
- [ ] Every value printed into HTML goes through `e()`.
- [ ] Every POST form has a CSRF token, checked with `check_csrf()`.
- [ ] `session_regenerate_id(true)` runs after login.
- [ ] Every `admin/` page starts with `require_role('admin')`. Every `owner/` page starts with `require_role('owner')`.
- [ ] Owner pages always filter by `p.owner_id = ?` with the logged-in owner's id.
- [ ] Status changes go only through `change_appointment()`, which checks the role's allowed transitions.
- [ ] Error details are hidden on the live site.
- [ ] The default admin password is changed before going live.

---

## 11. Out of Scope (Possible Future Work)

These are not part of the current requirements:

- Email or SMS reminders and notifications.
- Online payment.
- Medical records (diagnosis, prescriptions, vaccine due dates).
- Separate staff accounts with different permission levels.
- Linking a phone/walk-in owner record to an online account the owner creates later.
- Selling products online (the "Add to cart" items on the current site).
- Services that need more than one 30-minute slot.
