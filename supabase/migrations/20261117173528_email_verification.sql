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
