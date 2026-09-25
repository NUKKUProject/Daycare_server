ALTER TABLE student_qr_tokens ADD COLUMN expires_at timestamp without time zone;

UPDATE student_qr_tokens SET expires_at = created_at + INTERVAL '3 years' WHERE expires_at IS NULL AND is_active = TRUE;
