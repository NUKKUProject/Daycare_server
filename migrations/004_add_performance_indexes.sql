-- Composite index for LATERAL join on student_qr_tokens
-- Speeds up: WHERE children_id = ? AND is_active = TRUE ORDER BY created_at DESC LIMIT 1
CREATE INDEX IF NOT EXISTS idx_tokens_lookup
    ON student_qr_tokens(children_id, is_active, created_at DESC);

-- Composite index for children listing query
-- Speeds up: WHERE status = 'กำลังศึกษา' ORDER BY child_group, classroom, firstname_th
CREATE INDEX IF NOT EXISTS idx_children_listing
    ON children(status, child_group, classroom, firstname_th);
