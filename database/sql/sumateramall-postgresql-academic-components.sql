-- PostgreSQL 17 Academic DBMS Components for SumateraMall
-- Validated for both chat_logs and reservations modules

-- 1. TRIGGER & TRIGGER FUNCTION: Validasi data chat_logs & auto-log status reservations
CREATE OR REPLACE FUNCTION fn_trg_chat_logs_validate_insert()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.session_id IS NULL OR BTRIM(NEW.session_id) = '' THEN
        RAISE EXCEPTION 'Chat session id must not be empty' USING ERRCODE = 'P0001';
    END IF;
    IF NEW.user_message IS NULL OR BTRIM(NEW.user_message) = '' THEN
        RAISE EXCEPTION 'Chat message must not be empty' USING ERRCODE = 'P0001';
    END IF;
    IF NEW.intent_detected IS NULL OR BTRIM(NEW.intent_detected) = '' THEN
        NEW.intent_detected := 'unknown';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_chat_logs_validate_insert ON chat_logs;
CREATE TRIGGER trg_chat_logs_validate_insert
BEFORE INSERT ON chat_logs
FOR EACH ROW
EXECUTE FUNCTION fn_trg_chat_logs_validate_insert();

CREATE OR REPLACE FUNCTION fn_trg_reservations_status_audit()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'UPDATE' AND OLD.status IS DISTINCT FROM NEW.status THEN
        INSERT INTO reservation_status_logs (reservation_id, from_status, to_status, note, created_at)
        VALUES (NEW.id, OLD.status, NEW.status, 'Status updated via database trigger', NOW());
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_reservations_status_audit ON reservations;
CREATE TRIGGER trg_reservations_status_audit
AFTER UPDATE OF status ON reservations
FOR EACH ROW
EXECUTE FUNCTION fn_trg_reservations_status_audit();

-- 2. FUNCTION: Menghitung jumlah interaksi chat per session & total reservasi aktif per toko
CREATE OR REPLACE FUNCTION fn_chat_count_by_session(p_session_id VARCHAR)
RETURNS INTEGER AS $$
DECLARE
    interaction_count INTEGER := 0;
BEGIN
    SELECT COUNT(*) INTO interaction_count
    FROM chat_logs
    WHERE session_id = p_session_id;
    RETURN interaction_count;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION fn_store_active_reservation_count(p_store_id BIGINT)
RETURNS INTEGER AS $$
DECLARE
    total_active INTEGER := 0;
BEGIN
    SELECT COUNT(*) INTO total_active
    FROM reservations
    WHERE store_id = p_store_id
      AND status IN ('pending', 'confirmed');
    RETURN total_active;
END;
$$ LANGUAGE plpgsql;

-- 3. PROCEDURE: Validasi & pembatalan reservasi kedaluwarsa secara batch
CREATE OR REPLACE PROCEDURE sp_expire_pending_reservations(IN p_cutoff_date DATE)
LANGUAGE plpgsql AS $$
BEGIN
    IF p_cutoff_date IS NULL THEN
        RAISE EXCEPTION 'Cutoff date must not be null' USING ERRCODE = 'P0001';
    END IF;

    UPDATE reservations
    SET status = 'cancelled',
        updated_at = NOW()
    WHERE status = 'pending'
      AND reservation_date < p_cutoff_date;
END;
$$;

-- 4. VIEW: Ringkasan aktivitas intent chatbot & ringkasan reservasi per toko/mall
CREATE OR REPLACE VIEW vw_chat_intent_activity AS
SELECT
    DATE(created_at) AS activity_date,
    COALESCE(intent_detected, 'unknown') AS intent,
    COUNT(*) AS interaction_count,
    COUNT(DISTINCT session_id) AS session_count
FROM chat_logs
GROUP BY DATE(created_at), COALESCE(intent_detected, 'unknown');

CREATE OR REPLACE VIEW vw_store_reservation_summary AS
SELECT
    s.id AS store_id,
    s.name AS store_name,
    m.name AS mall_name,
    COUNT(r.id) AS total_reservations,
    COUNT(r.id) FILTER (WHERE r.status = 'confirmed') AS confirmed_count,
    COUNT(r.id) FILTER (WHERE r.status = 'pending') AS pending_count,
    COALESCE(SUM(r.guest_count) FILTER (WHERE r.status = 'confirmed'), 0) AS total_confirmed_guests
FROM stores s
LEFT JOIN malls m ON m.id = s.mall_id
LEFT JOIN reservations r ON r.store_id = s.id
GROUP BY s.id, s.name, m.name;

-- 5. INDEX: Optimasi pencarian riwayat chat & jadwal reservasi toko
CREATE INDEX IF NOT EXISTS idx_chat_logs_session_created
    ON chat_logs (session_id, created_at);

CREATE INDEX IF NOT EXISTS idx_reservations_store_date_status
    ON reservations (store_id, reservation_date, status);
