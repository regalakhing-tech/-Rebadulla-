-- Run once in the Supabase SQL Editor before starting the PHP application.
-- Application tables live outside the schemas exposed through the Supabase Data API.
BEGIN;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'clinic_app') THEN
        CREATE ROLE clinic_app NOLOGIN;
    END IF;
END $$;
GRANT clinic_app TO CURRENT_USER;
CREATE SCHEMA IF NOT EXISTS clinic;
REVOKE ALL ON SCHEMA clinic FROM PUBLIC;
GRANT USAGE ON SCHEMA clinic TO clinic_app;
SET LOCAL search_path TO clinic, pg_catalog;

CREATE TABLE users (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    role text NOT NULL DEFAULT 'owner' CHECK (role IN ('owner', 'admin')),
    full_name varchar(100) NOT NULL,
    email varchar(150) UNIQUE CHECK (email IS NULL OR email = lower(email)),
    phone varchar(30),
    password_hash varchar(255),
    created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX users_name_idx ON users (full_name);
CREATE INDEX users_phone_idx ON users (phone);

CREATE TABLE pets (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    owner_id bigint NOT NULL REFERENCES users(id),
    name varchar(60) NOT NULL,
    species varchar(30) NOT NULL,
    breed varchar(60),
    sex text NOT NULL DEFAULT 'unknown' CHECK (sex IN ('male', 'female', 'unknown')),
    birth_date date,
    notes text,
    created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX pets_owner_idx ON pets (owner_id);
CREATE INDEX pets_name_idx ON pets (name);

CREATE TABLE services (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name varchar(120) NOT NULL UNIQUE,
    description text NOT NULL DEFAULT '',
    price_from numeric(10,2) CHECK (price_from >= 0),
    max_per_slot integer NOT NULL DEFAULT 1 CHECK (max_per_slot BETWEEN 1 AND 100),
    is_active boolean NOT NULL DEFAULT true
);
CREATE TABLE clinic_hours (
    day_of_week smallint PRIMARY KEY CHECK (day_of_week BETWEEN 0 AND 6),
    open_time time,
    close_time time,
    is_closed boolean NOT NULL DEFAULT false,
    CHECK (is_closed OR (open_time IS NOT NULL AND close_time IS NOT NULL AND open_time < close_time))
);
CREATE TABLE clinic_closures (
    date date PRIMARY KEY,
    reason varchar(200) NOT NULL
);
CREATE TABLE appointments (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    booking_ref varchar(16) NOT NULL UNIQUE,
    pet_id bigint NOT NULL REFERENCES pets(id),
    service_id bigint NOT NULL REFERENCES services(id),
    appt_date date NOT NULL,
    appt_time time NOT NULL,
    status text NOT NULL CHECK (status IN ('pending','proposed','confirmed','completed','missed','cancelled')),
    source text NOT NULL CHECK (source IN ('online','phone','walk_in')),
    owner_notes text,
    created_by bigint NOT NULL REFERENCES users(id),
    request_key varchar(32) NOT NULL UNIQUE,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX appointments_pet_idx ON appointments (pet_id);
CREATE INDEX appointments_service_idx ON appointments (service_id);
CREATE INDEX appointments_created_by_idx ON appointments (created_by);
CREATE INDEX appointments_schedule_idx ON appointments (appt_date, appt_time);
CREATE INDEX appointments_status_idx ON appointments (status, updated_at);
CREATE INDEX appointments_capacity_idx ON appointments (service_id, appt_date, appt_time) WHERE status = 'confirmed';

CREATE TABLE appointment_history (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    appointment_id bigint NOT NULL REFERENCES appointments(id),
    action varchar(30) NOT NULL,
    old_status text,
    new_status text NOT NULL,
    old_date date,
    old_time time,
    new_date date NOT NULL,
    new_time time NOT NULL,
    note text,
    changed_by bigint NOT NULL REFERENCES users(id),
    request_key varchar(32) NOT NULL UNIQUE,
    changed_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX history_appointment_idx ON appointment_history (appointment_id, changed_at, id);
CREATE INDEX history_actor_idx ON appointment_history (changed_by);

CREATE TABLE login_sessions (
    token_hash char(64) PRIMARY KEY,
    user_id bigint NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    expires_at timestamptz NOT NULL
);
CREATE INDEX sessions_user_idx ON login_sessions (user_id);
CREATE INDEX sessions_expiry_idx ON login_sessions (expires_at);
CREATE TABLE login_attempts (
    key char(64) PRIMARY KEY,
    attempts integer NOT NULL,
    window_start timestamptz NOT NULL
);
CREATE TABLE mail_outbox (
    id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    history_id bigint NOT NULL UNIQUE REFERENCES appointment_history(id),
    recipient varchar(150) NOT NULL,
    subject varchar(200) NOT NULL,
    body text NOT NULL,
    status text NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'sending', 'sent')),
    attempts integer NOT NULL DEFAULT 0,
    last_error text,
    locked_at timestamptz,
    sent_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX mail_pending_idx ON mail_outbox (status, created_at);

GRANT SELECT, INSERT, UPDATE ON users, pets, services, clinic_hours, clinic_closures, appointments, login_sessions, login_attempts, mail_outbox TO clinic_app;
GRANT SELECT, INSERT ON appointment_history TO clinic_app;
GRANT DELETE ON clinic_closures, login_sessions, login_attempts TO clinic_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA clinic TO clinic_app;

-- PHP is the only data gateway. Browser-facing Supabase roles receive no schema access.
DO $$ DECLARE item text; client_role text; BEGIN
    FOREACH item IN ARRAY ARRAY['users','pets','services','clinic_hours','clinic_closures','appointments','appointment_history','login_sessions','login_attempts','mail_outbox'] LOOP
        EXECUTE format('ALTER TABLE clinic.%I ENABLE ROW LEVEL SECURITY', item);
        EXECUTE format('CREATE POLICY php_backend ON clinic.%I TO clinic_app USING (true) WITH CHECK (true)', item);
        EXECUTE format('REVOKE ALL ON clinic.%I FROM PUBLIC', item);
    END LOOP;
    FOREACH client_role IN ARRAY ARRAY['anon','authenticated'] LOOP
        IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = client_role) THEN
            EXECUTE format('REVOKE ALL ON SCHEMA clinic FROM %I', client_role);
            EXECUTE format('REVOKE ALL ON ALL TABLES IN SCHEMA clinic FROM %I', client_role);
        END IF;
    END LOOP;
END $$;

INSERT INTO services (name, description, price_from, max_per_slot) VALUES
('Routine & Walk-in Consultation', 'Physical examination, vital signs, nutrition and behavior advice.', 400, 2),
('Dog & Cat Vaccination', 'Core vaccines, anti-rabies, deworming, and vaccine record book.', 250, 2),
('Parasite Prevention', 'Tick and flea control, heartworm protection, and blood parasite testing.', 300, 2),
('Professional Grooming', 'Bath and blow dry, nail trimming, ear cleaning, and sanitary trim.', NULL, 1);
INSERT INTO clinic_hours (day_of_week, open_time, close_time, is_closed)
SELECT day, CASE WHEN day = 0 THEN NULL ELSE time '08:00' END,
    CASE WHEN day = 0 THEN NULL ELSE time '17:00' END, day = 0
FROM generate_series(0, 6) AS day;
COMMIT;

-- Email verification for new owner registrations.
BEGIN;
CREATE TABLE IF NOT EXISTS clinic.registration_challenges (
    token_hash char(64) PRIMARY KEY,
    full_name varchar(100) NOT NULL,
    email varchar(150) NOT NULL CHECK (email = lower(email)),
    phone varchar(30) NOT NULL,
    password_hash varchar(255) NOT NULL,
    code_hash char(64) NOT NULL,
    attempts smallint NOT NULL DEFAULT 0 CHECK (attempts BETWEEN 0 AND 5),
    expires_at timestamptz NOT NULL,
    sent_at timestamptz NOT NULL DEFAULT now(),
    delivered_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS registration_challenges_expiry_idx ON clinic.registration_challenges (created_at);
ALTER TABLE clinic.registration_challenges ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS php_backend ON clinic.registration_challenges;
CREATE POLICY php_backend ON clinic.registration_challenges TO clinic_app USING (true) WITH CHECK (true);
REVOKE ALL ON clinic.registration_challenges FROM PUBLIC;
GRANT SELECT, INSERT, UPDATE, DELETE ON clinic.registration_challenges TO clinic_app;
COMMIT;

-- Pet photos. Kept in their own table so pet lists never load image bytes.
-- Safe to run more than once.
BEGIN;
CREATE TABLE IF NOT EXISTS clinic.pet_photos (
    pet_id bigint PRIMARY KEY REFERENCES clinic.pets(id) ON DELETE CASCADE,
    mime text NOT NULL CHECK (mime IN ('image/jpeg', 'image/png', 'image/webp')),
    bytes bytea NOT NULL CHECK (octet_length(bytes) <= 2097152),
    updated_at timestamptz NOT NULL DEFAULT now()
);
ALTER TABLE clinic.pet_photos ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS php_backend ON clinic.pet_photos;
CREATE POLICY php_backend ON clinic.pet_photos TO clinic_app USING (true) WITH CHECK (true);
REVOKE ALL ON clinic.pet_photos FROM PUBLIC;
GRANT SELECT, INSERT, UPDATE, DELETE ON clinic.pet_photos TO clinic_app;
COMMIT;
