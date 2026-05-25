-- Order tracking events (parcel journey scans — safe to re-run)

CREATE TABLE IF NOT EXISTS order_tracking_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    step_code VARCHAR(40) NOT NULL,
    title VARCHAR(120) NOT NULL,
    description TEXT,
    location VARCHAR(160),
    activity_note TEXT,
    event_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by ENUM('system', 'admin', 'courier') NOT NULL DEFAULT 'system',
    CONSTRAINT fk_tracking_events_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_tracking_order (order_id),
    INDEX idx_tracking_step (step_code),
    INDEX idx_tracking_event_at (event_at)
) ENGINE=InnoDB;
