-- Smart Greenhouse Database Schema
-- This file contains the complete database structure for the Smart Greenhouse system
-- Includes temperature support and migration for existing databases

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS greenhouse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greenhouse;

-- Main sensor readings table
CREATE TABLE IF NOT EXISTS readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    humidity DECIMAL(5,2) NOT NULL COMMENT 'Humidity percentage (0-100)',
    temperature DECIMAL(5,2) NOT NULL COMMENT 'Temperature in Celsius',
    soil INT NOT NULL COMMENT 'Soil moisture percentage (0-100)',
    light INT NOT NULL COMMENT 'Light intensity value (0-4095)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when reading was recorded',
    INDEX idx_created_at (created_at),
    INDEX idx_humidity (humidity),
    INDEX idx_temperature (temperature),
    INDEX idx_soil (soil),
    INDEX idx_light (light)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sensor readings from ESP32';

-- Control actions log table
CREATE TABLE IF NOT EXISTS control_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    relay INT NOT NULL COMMENT 'Relay number (1=pump, 2=light, 3=fan)',
    state ENUM('on', 'off') NOT NULL COMMENT 'Relay state',
    response TEXT COMMENT 'ESP32 response to control command',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When control action was performed',
    INDEX idx_relay (relay),
    INDEX idx_timestamp (timestamp),
    INDEX idx_state (state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log of relay control actions';

-- System alerts table
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('humidity', 'soil', 'light', 'system') NOT NULL COMMENT 'Type of alert',
    level ENUM('info', 'warning', 'critical') NOT NULL COMMENT 'Alert severity level',
    message TEXT NOT NULL COMMENT 'Alert message',
    sensor_value DECIMAL(10,2) COMMENT 'Sensor value that triggered alert',
    threshold DECIMAL(10,2) COMMENT 'Threshold value',
    is_resolved BOOLEAN DEFAULT FALSE COMMENT 'Whether alert has been resolved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When alert was created',
    resolved_at TIMESTAMP NULL COMMENT 'When alert was resolved',
    INDEX idx_type (type),
    INDEX idx_level (level),
    INDEX idx_is_resolved (is_resolved),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System alerts and notifications';

-- System configuration table
CREATE TABLE IF NOT EXISTS system_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE COMMENT 'Configuration key',
    config_value TEXT COMMENT 'Configuration value',
    description TEXT COMMENT 'Description of the configuration',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When config was last updated',
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System configuration settings';

-- Insert default configuration values
INSERT INTO system_config (config_key, config_value, description) VALUES
('humidity_min', '30', 'Minimum acceptable humidity percentage'),
('humidity_max', '80', 'Maximum acceptable humidity percentage'),
('temperature_min', '15', 'Minimum acceptable temperature in Celsius'),
('temperature_max', '35', 'Maximum acceptable temperature in Celsius'),
('soil_min', '20', 'Minimum acceptable soil moisture percentage'),
('soil_max', '80', 'Maximum acceptable soil moisture percentage'),
('light_min', '500', 'Minimum acceptable light intensity'),
('light_max', '3000', 'Maximum acceptable light intensity'),
('data_retention_days', '30', 'Number of days to keep sensor data'),
('alert_check_interval', '300', 'Alert check interval in seconds'),
('esp32_ip', '192.168.137.105', 'ESP32 IP address'),
('dashboard_refresh_interval', '2000', 'Dashboard refresh interval in milliseconds')
ON DUPLICATE KEY UPDATE 
config_value = VALUES(config_value),
description = VALUES(description);

-- Create views for easier data access

-- View for latest sensor readings with analysis
CREATE OR REPLACE VIEW latest_readings AS
SELECT 
    r.*,
    CASE 
        WHEN r.humidity < 30 THEN 'Low'
        WHEN r.humidity > 80 THEN 'High'
        WHEN r.humidity BETWEEN 40 AND 70 THEN 'Optimal'
        ELSE 'Moderate'
    END as humidity_status,
    CASE 
        WHEN r.temperature < 15 THEN 'Cold'
        WHEN r.temperature > 35 THEN 'Hot'
        WHEN r.temperature BETWEEN 20 AND 30 THEN 'Optimal'
        ELSE 'Moderate'
    END as temperature_status,
    CASE 
        WHEN r.soil < 20 THEN 'Very Dry'
        WHEN r.soil < 40 THEN 'Dry'
        WHEN r.soil BETWEEN 40 AND 70 THEN 'Optimal'
        ELSE 'Wet'
    END as soil_status,
    CASE 
        WHEN r.light > 3000 THEN 'Dark'
        WHEN r.light > 2000 THEN 'Dim'
        WHEN r.light > 1000 THEN 'Moderate'
        WHEN r.light > 500 THEN 'Bright'
        ELSE 'Very Bright'
    END as light_status
FROM readings r
ORDER BY r.created_at DESC
LIMIT 1;

-- View for daily averages
CREATE OR REPLACE VIEW daily_averages AS
SELECT 
    DATE(created_at) as date,
    AVG(humidity) as avg_humidity,
    AVG(temperature) as avg_temperature,
    AVG(soil) as avg_soil,
    AVG(light) as avg_light,
    COUNT(*) as reading_count,
    MIN(created_at) as first_reading,
    MAX(created_at) as last_reading
FROM readings
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- View for active alerts
CREATE OR REPLACE VIEW active_alerts AS
SELECT 
    a.*,
    TIMESTAMPDIFF(MINUTE, a.created_at, NOW()) as minutes_ago
FROM alerts a
WHERE a.is_resolved = FALSE
ORDER BY a.created_at DESC;

-- Stored procedures for common operations

DELIMITER //

-- Procedure to clean old data
CREATE PROCEDURE CleanOldData()
BEGIN
    DECLARE retention_days INT DEFAULT 30;
    
    -- Get retention period from config
    SELECT config_value INTO retention_days 
    FROM system_config 
    WHERE config_key = 'data_retention_days';
    
    -- Delete old readings
    DELETE FROM readings 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL retention_days DAY);
    
    -- Delete old control logs
    DELETE FROM control_log 
    WHERE timestamp < DATE_SUB(NOW(), INTERVAL retention_days DAY);
    
    -- Delete resolved alerts older than 7 days
    DELETE FROM alerts 
    WHERE is_resolved = TRUE 
    AND resolved_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    SELECT ROW_COUNT() as deleted_records;
END //

-- Procedure to check for alerts
CREATE PROCEDURE CheckAlerts()
BEGIN
    DECLARE humidity_min DECIMAL(5,2) DEFAULT 30;
    DECLARE humidity_max DECIMAL(5,2) DEFAULT 80;
    DECLARE soil_min INT DEFAULT 20;
    DECLARE soil_max INT DEFAULT 80;
    DECLARE light_min INT DEFAULT 500;
    DECLARE light_max INT DEFAULT 3000;
    
    -- Get thresholds from config
    SELECT config_value INTO humidity_min FROM system_config WHERE config_key = 'humidity_min';
    SELECT config_value INTO humidity_max FROM system_config WHERE config_key = 'humidity_max';
    SELECT config_value INTO soil_min FROM system_config WHERE config_key = 'soil_min';
    SELECT config_value INTO soil_max FROM system_config WHERE config_key = 'soil_max';
    SELECT config_value INTO light_min FROM system_config WHERE config_key = 'light_min';
    SELECT config_value INTO light_max FROM system_config WHERE config_key = 'light_max';
    
    -- Check for humidity alerts
    INSERT INTO alerts (type, level, message, sensor_value, threshold)
    SELECT 
        'humidity',
        CASE 
            WHEN humidity < humidity_min THEN 'critical'
            WHEN humidity > humidity_max THEN 'warning'
        END,
        CASE 
            WHEN humidity < humidity_min THEN CONCAT('Humidity critically low: ', humidity, '%')
            WHEN humidity > humidity_max THEN CONCAT('Humidity too high: ', humidity, '%')
        END,
        humidity,
        CASE 
            WHEN humidity < humidity_min THEN humidity_min
            WHEN humidity > humidity_max THEN humidity_max
        END
    FROM readings 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    AND ((humidity < humidity_min) OR (humidity > humidity_max))
    AND NOT EXISTS (
        SELECT 1 FROM alerts 
        WHERE type = 'humidity' 
        AND is_resolved = FALSE 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    );
    
    -- Check for soil alerts
    INSERT INTO alerts (type, level, message, sensor_value, threshold)
    SELECT 
        'soil',
        CASE 
            WHEN soil < soil_min THEN 'critical'
            WHEN soil > soil_max THEN 'warning'
        END,
        CASE 
            WHEN soil < soil_min THEN CONCAT('Soil critically dry: ', soil, '%')
            WHEN soil > soil_max THEN CONCAT('Soil too wet: ', soil, '%')
        END,
        soil,
        CASE 
            WHEN soil < soil_min THEN soil_min
            WHEN soil > soil_max THEN soil_max
        END
    FROM readings 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    AND ((soil < soil_min) OR (soil > soil_max))
    AND NOT EXISTS (
        SELECT 1 FROM alerts 
        WHERE type = 'soil' 
        AND is_resolved = FALSE 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    );
    
    -- Check for light alerts
    INSERT INTO alerts (type, level, message, sensor_value, threshold)
    SELECT 
        'light',
        CASE 
            WHEN light > light_max THEN 'warning'
            WHEN light < light_min THEN 'warning'
        END,
        CASE 
            WHEN light > light_max THEN CONCAT('Light too dim: ', light)
            WHEN light < light_min THEN CONCAT('Light too bright: ', light)
        END,
        light,
        CASE 
            WHEN light > light_max THEN light_max
            WHEN light < light_min THEN light_min
        END
    FROM readings 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    AND ((light > light_max) OR (light < light_min))
    AND NOT EXISTS (
        SELECT 1 FROM alerts 
        WHERE type = 'light' 
        AND is_resolved = FALSE 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    );
END //

DELIMITER ;

-- Create indexes for better performance
CREATE INDEX idx_readings_composite ON readings (created_at, humidity, temperature, soil, light);
CREATE INDEX idx_control_log_composite ON control_log (timestamp, relay, state);

-- ============================================================================
-- MIGRATION SECTION FOR EXISTING DATABASES
-- ============================================================================
-- The following section handles migration for existing databases that don't have temperature support
-- This section is safe to run multiple times and will only add missing columns/features

-- Check if temperature column exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'greenhouse' 
     AND TABLE_NAME = 'readings' 
     AND COLUMN_NAME = 'temperature') = 0,
    'ALTER TABLE readings ADD COLUMN temperature DECIMAL(5,2) NOT NULL DEFAULT 25.0 COMMENT ''Temperature in Celsius'' AFTER humidity',
    'SELECT ''Temperature column already exists'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if temperature index exists, if not add it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = 'greenhouse' 
     AND TABLE_NAME = 'readings' 
     AND INDEX_NAME = 'idx_temperature') = 0,
    'ALTER TABLE readings ADD INDEX idx_temperature (temperature)',
    'SELECT ''Temperature index already exists'' as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records with default temperature if they have 0.0 (optional)
-- This is commented out by default to avoid overwriting existing data
-- Uncomment the line below if you want to set a default temperature for existing records
-- UPDATE readings SET temperature = 25.0 WHERE temperature = 0.0;

-- Grant permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON greenhouse.* TO 'greenhouse_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE greenhouse.CleanOldData TO 'greenhouse_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE greenhouse.CheckAlerts TO 'greenhouse_user'@'localhost';

-- Insert sample data for testing (optional)
-- INSERT INTO readings (humidity, temperature, soil, light) VALUES 
-- (45.5, 25.3, 60, 1200),
-- (52.3, 26.1, 55, 1500),
-- (48.7, 24.8, 65, 1100);

COMMIT;
