-- Run once after `php artisan migrate` on MySQL 8.0+.
-- The trigger, function, procedure, view, and index use the existing chat_logs table.

DROP TRIGGER IF EXISTS trg_chat_logs_validate_insert;
DELIMITER $$
CREATE TRIGGER trg_chat_logs_validate_insert
BEFORE INSERT ON chat_logs
FOR EACH ROW
BEGIN
    IF NEW.session_id IS NULL OR TRIM(NEW.session_id) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chat session id must not be empty';
    END IF;
    IF NEW.user_message IS NULL OR TRIM(NEW.user_message) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Chat message must not be empty';
    END IF;
    IF NEW.intent_detected IS NULL OR TRIM(NEW.intent_detected) = '' THEN
        SET NEW.intent_detected = 'unknown';
    END IF;
END$$
DELIMITER ;

DROP FUNCTION IF EXISTS fn_chat_count_by_session;
DELIMITER $$
CREATE FUNCTION fn_chat_count_by_session(p_session_id VARCHAR(255))
RETURNS INT UNSIGNED
READS SQL DATA
BEGIN
    DECLARE interaction_count INT UNSIGNED DEFAULT 0;
    SELECT COUNT(*) INTO interaction_count
    FROM chat_logs
    WHERE session_id = p_session_id;
    RETURN interaction_count;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_get_chat_history;
DELIMITER $$
CREATE PROCEDURE sp_get_chat_history(
    IN p_session_id VARCHAR(255),
    IN p_limit INT
)
READS SQL DATA
BEGIN
    IF p_limit IS NULL OR p_limit < 1 OR p_limit > 200 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'History limit must be between 1 and 200';
    END IF;

    SELECT id, user_message, bot_reply, intent_detected, created_at
    FROM chat_logs
    WHERE session_id = p_session_id
    ORDER BY created_at DESC, id DESC
    LIMIT p_limit;
END$$
DELIMITER ;

CREATE OR REPLACE VIEW vw_chat_intent_activity AS
SELECT
    DATE(created_at) AS activity_date,
    COALESCE(intent_detected, 'unknown') AS intent,
    COUNT(*) AS interaction_count,
    COUNT(DISTINCT session_id) AS session_count
FROM chat_logs
GROUP BY DATE(created_at), COALESCE(intent_detected, 'unknown');

SET @sumateramall_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'chat_logs'
      AND index_name = 'idx_chat_logs_session_created'
);
SET @sumateramall_index_sql = IF(
    @sumateramall_index_exists = 0,
    'CREATE INDEX idx_chat_logs_session_created ON chat_logs (session_id, created_at)',
    'SELECT 1'
);
PREPARE sumateramall_index_statement FROM @sumateramall_index_sql;
EXECUTE sumateramall_index_statement;
DEALLOCATE PREPARE sumateramall_index_statement;
