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
