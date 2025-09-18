# Smart Greenhouse System - Enhanced Dashboard

## Overview

This is an enhanced Smart Greenhouse monitoring and control system with a modern, greenhouse-inspired dashboard design. The system has been upgraded to separate concerns between the ESP32 firmware (sensor data collection and relay control) and PHP-based web interface (UI/UX and data management).

## System Architecture

### ESP32 Firmware (`Smart_Greenhouse_Firmware/Smart_Greenhouse_Firmware.ino`)
- **Purpose**: Sensor data collection and relay control
- **Responsibilities**:
  - Read HDC1080 humidity and temperature sensor (I2C) with 2 decimal precision
  - Read soil moisture sensor (analog pin 34)
  - Read light intensity sensor (LDR analog pin 35)
  - Control water pump (Relay 1), grow lights (Relay 2), and exhaust fan (Relay 3)
  - Provide REST API endpoints for sensor data and control
  - Send data to MySQL database via PHP API every 5 seconds
  - Humidity calibration functionality with reference input
  - I2C device scanning and multiple address support for HDC1080
  - WiFi reconnection handling

### PHP Web Interface
- **`dashboard.php`**: Main greenhouse-inspired dashboard with real-time monitoring (2-second refresh)
- **`history.php`**: Enhanced history API with filtering, analytics, and status indicators
- **`insert.php`**: Improved data insertion API with validation, error handling, and duplicate detection
- **`api.php`**: Comprehensive REST API for system integration with multiple endpoints
- **`index.php`**: System information and navigation page with URL routing
- **`error.php`**: Custom error handling page with user-friendly interface


### Database (`greenhouse`)
- **`readings`**: Sensor data storage (humidity, temperature, soil, light) with proper indexing
- **`control_log`**: Relay control action logging with timestamps
- **`alerts`**: System alerts and notifications with severity levels
- **`system_config`**: Configuration settings with default values
- **Views**: `latest_readings`, `daily_averages`, `active_alerts`
- **Stored Procedures**: `CleanOldData()`, `CheckAlerts()`
- **Migration Support**: Automatic schema updates for existing databases

## Features

### 🌱 Greenhouse-Inspired Design
- Natural green color palette with earth tones
- Animated background elements and smooth transitions
- Glass-morphism effects and modern card-based layout
- Responsive design for mobile and desktop
- Modern CSS animations and hover effects

### 📊 Real-Time Monitoring
- Live sensor data updates every 2 seconds
- Visual progress bars with color-coded status indicators
- Animated loading states and smooth transitions
- System status indicators with HDC1080 sensor status
- Humidity calibration functionality with reference input
- HDC1080 sensor error detection and fallback handling
- Mobile-responsive design with glassmorphism effects

### 🎛️ System Controls
- Water pump control (Relay 1 - Pin 26)
- Grow lights control (Relay 2 - Pin 27)
- Exhaust fan control (Relay 3 - Pin 25)
- Real-time relay status feedback with visual indicators
- API-based control with fallback to direct ESP32 connection
- Active LOW relay control with proper pin configuration

### 📈 Data Analytics
- Historical data visualization with status indicators
- Recent sensor data table with color-coded status and icons
- Alert system with configurable thresholds
- Performance metrics and system health monitoring
- Time-based filtering and pagination
- Status analysis with recommendations
- Duplicate data detection and prevention

### 🔧 API Integration
- RESTful API endpoints with comprehensive error handling
- JSON responses with proper HTTP status codes
- CORS support for cross-origin requests
- Database fallback when ESP32 is offline
- Data validation and duplicate detection
- URL rewriting for clean API endpoints
- Multiple data input methods (GET, POST, JSON)

## Installation & Setup

### 1. Database Setup
```sql
-- Import the database schema
mysql -u root -p < database/greenhouse.sql
```

### 2. ESP32 Configuration
1. Update WiFi credentials in `Smart_Greenhouse_Firmware.ino`:
   ```cpp
   const char* ssid = "YOUR_WIFI_SSID";
   const char* password = "YOUR_WIFI_PASSWORD";
   ```

2. Update server IP address:
   ```cpp
   const char* serverName = "http://YOUR_SERVER_IP/greenhouse/insert.php";
   ```

3. Upload firmware to ESP32

### 3. PHP Configuration
1. Place all PHP files in your web server directory (e.g., `/greenhouse/`)
2. Update database credentials in PHP files if needed (default: localhost, root, no password)
3. Update ESP32 IP address in `dashboard.php` and `api.php` (default: 192.168.137.105)
4. Ensure `.htaccess` file is in place for URL rewriting

### 4. Hardware Connections

#### Sensors
- **HDC1080**: I2C (SDA/SCL) - humidity & temperature sensor
- **Soil Moisture**: Pin 34 (analog)
- **Light Sensor (LDR)**: Pin 35 (analog)

#### Relays (Active LOW)
- **Water Pump**: Pin 26
- **Grow Lights**: Pin 27
- **Exhaust Fan**: Pin 25

#### I2C Connections
- **SDA**: GPIO 21 (default ESP32)
- **SCL**: GPIO 22 (default ESP32)
- **VCC**: 3.3V
- **GND**: Ground

## API Endpoints

### ESP32 Endpoints
- `GET /` - Simple status page with dashboard links
- `GET /humidity` - Current humidity reading (HDC1080, 2 decimal places)
- `GET /temperature` - Current temperature reading (HDC1080, 2 decimal places)
- `GET /soil` - Current soil moisture reading with label (format: "value|label")
- `GET /light` - Current light intensity reading (0-4095)
- `GET /status` - Complete system status (JSON with sensors, relays, WiFi info)
- `GET /control?relay=X&state=on|off` - Control relays (1=pump, 2=light, 3=fan)
- `GET /calibrate?humidity=X` - Calibrate humidity sensor with reference value

### PHP API Endpoints
- `GET /api.php/sensors` - Get current sensor readings with analysis and status
- `POST /api.php/control` - Control relays via API (JSON body: {relay, state})
- `GET /api.php/status` - Get comprehensive system status
- `GET /api.php/history` - Get sensor history with filtering and pagination
- `GET /api.php/analytics` - Get analytics and trends data
- `GET /history.php?limit=X&from=DATE&to=DATE` - Enhanced history API with status indicators
- `POST /insert.php` - Insert sensor data (used by ESP32, supports GET/POST/JSON)
- `GET /index.php` - System information and navigation page
- `GET /error.php` - Custom error handling page

## Usage

### Accessing the Dashboard
1. Open your web browser
2. Navigate to `http://YOUR_SERVER_IP/greenhouse/dashboard.php`
3. The dashboard will automatically connect to your ESP32 and display real-time data
4. Alternative: Use `http://YOUR_SERVER_IP/greenhouse/` for system information

### Controlling the System
- Use the control buttons on the dashboard to turn relays on/off
- Monitor real-time sensor readings and system status
- View historical data and trends in the history section
- Use the humidity calibration feature for accurate readings

### API Usage
```javascript
// Get current sensor data
fetch('/greenhouse/api.php/sensors')
  .then(response => response.json())
  .then(data => console.log(data));

// Control a relay
fetch('/greenhouse/api.php/control', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({relay: 1, state: 'on'})
})
  .then(response => response.json())
  .then(data => console.log(data));

// Get history with filtering
fetch('/greenhouse/history.php?limit=10&from=2024-01-01&to=2024-01-31')
  .then(response => response.json())
  .then(data => console.log(data));
```

## Configuration

### System Settings
Edit the `system_config` table to adjust:
- Sensor thresholds for alerts
- Data retention period (default: 30 days)
- Dashboard refresh intervals (default: 2000ms)
- ESP32 IP address (default: 192.168.137.105)
- Alert check intervals

### Alert Thresholds (Configurable)
- **Humidity**: 30-80% (optimal: 40-70%)
- **Temperature**: 15-35°C (optimal: 20-30°C)
- **Soil Moisture**: 20-80% (optimal: 40-70%)
- **Light Intensity**: 500-3000 (optimal: 1000-2000)

### Default Configuration Values
```sql
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
('esp32_ip', '192.168.137.105', 'ESP32 IP address');
```

## Maintenance

### Database Cleanup
```sql
-- Clean old data (keeps last 30 days by default)
CALL CleanOldData();

-- Manual cleanup of old readings
DELETE FROM readings WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Alert Management
```sql
-- Check for new alerts
CALL CheckAlerts();

-- View active alerts
SELECT * FROM active_alerts;

-- View all alerts with status
SELECT * FROM alerts ORDER BY created_at DESC;

-- Resolve an alert
UPDATE alerts SET is_resolved = TRUE, resolved_at = NOW() WHERE id = 1;

-- View latest readings with analysis
SELECT * FROM latest_readings;
```

### System Monitoring
```sql
-- Check system performance
SELECT 
    COUNT(*) as total_readings,
    MIN(created_at) as first_reading,
    MAX(created_at) as last_reading,
    AVG(humidity) as avg_humidity,
    AVG(temperature) as avg_temperature
FROM readings;

-- View daily averages
SELECT * FROM daily_averages LIMIT 7;
```

## Troubleshooting

### ESP32 Connection Issues
1. Check WiFi credentials in firmware
2. Verify ESP32 IP address in PHP files (default: 192.168.137.105)
3. Ensure ESP32 and server are on same network
4. Check ESP32 serial monitor for connection status
5. Verify HDC1080 sensor connection (I2C addresses: 0x40-0x43)

### Database Connection Issues
1. Verify MySQL credentials in PHP files (default: localhost, root, no password)
2. Check if MySQL service is running
3. Ensure database exists and has proper permissions
4. Import database schema: `mysql -u root -p < database/greenhouse.sql`
5. Check database connection in PHP files

### Dashboard Not Updating
1. Check browser console for JavaScript errors
2. Verify ESP32 endpoints are accessible
3. Check network connectivity between browser and ESP32
4. Ensure `.htaccess` file is in place for URL rewriting
5. Check if ESP32 is sending data every 5 seconds
6. Verify API endpoints are working: `/greenhouse/api.php/sensors`

### Sensor Issues
1. **HDC1080 not detected**: Check I2C connections (SDA/SCL)
2. **Humidity readings -999**: HDC1080 sensor error, check connections
3. **Soil sensor readings**: Verify analog pin 34 connection
4. **Light sensor readings**: Verify LDR on analog pin 35
5. **Calibration issues**: Use reference hygrometer for humidity calibration

## File Structure

```
greenhouse/
├── Smart_Greenhouse_Firmware/
│   └── Smart_Greenhouse_Firmware.ino    # ESP32 firmware (473 lines)
├── database/
│   └── greenhouse.sql                   # Database schema with migration support
├── assets/
│   ├── css/                            # CSS assets (if any)
│   ├── images/                         # Image assets (if any)
│   └── js/                             # JavaScript assets (if any)
├── docs/
│   └── schematics/                     # Documentation (if any)
├── dashboard.php                       # Main dashboard interface (2026 lines)
├── history.php                         # History API with filtering (179 lines)
├── insert.php                          # Data insertion API with validation (296 lines)
├── api.php                             # Comprehensive REST API (602 lines)
├── index.php                           # System information page (142 lines)
├── error.php                           # Error handling page (91 lines)
├── .htaccess                           # URL rewriting rules and security (58 lines)
├── robots.txt                          # Search engine directives (1 line)
└── README.md                           # This file (283 lines)
```

## Version History

### v3.0 (Current)
- ✅ Greenhouse-inspired dashboard design with glassmorphism effects
- ✅ Separated UI from firmware for better maintainability
- ✅ Enhanced PHP APIs with comprehensive error handling
- ✅ Real-time data updates every 2 seconds
- ✅ Comprehensive system monitoring with status indicators
- ✅ Alert system with configurable thresholds
- ✅ Mobile-responsive design with modern CSS animations
- ✅ HDC1080 sensor support with calibration functionality
- ✅ Database fallback when ESP32 is offline
- ✅ URL rewriting and clean API endpoints
- ✅ Duplicate data detection and validation
- ✅ Historical data analysis with status indicators
- ✅ Custom error handling and user-friendly interfaces

### v2.0 (Previous)
- Basic HTML dashboard in firmware
- Simple sensor monitoring
- Basic relay control

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source. Feel free to use and modify as needed.

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review the API documentation
3. Check system logs and error messages
4. Create an issue with detailed information

---

**Smart Greenhouse System v3.0** - Professional monitoring and control for modern greenhouses.

---

## Technical Specifications

### Hardware Requirements
- **ESP32 Development Board** (WiFi enabled)
- **HDC1080 Temperature & Humidity Sensor** (I2C)
- **Soil Moisture Sensor** (Analog)
- **Light Dependent Resistor (LDR)** (Analog)
- **3x Relay Modules** (Active LOW, 5V)
- **Water Pump, Grow Lights, Exhaust Fan** (as controlled devices)

### Software Requirements
- **Arduino IDE** with ESP32 board support
- **PHP 7.4+** with MySQL support
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Web Server** (Apache/Nginx) with mod_rewrite support

### Network Configuration
- **ESP32 IP**: 192.168.137.105 (configurable)
- **Server IP**: 192.168.137.1 (configurable)
- **Database**: localhost:3306 (configurable)
- **Dashboard URL**: `/greenhouse/dashboard.php`

### Performance Metrics
- **Data Collection**: Every 5 seconds
- **Dashboard Refresh**: Every 2 seconds
- **Database Retention**: 30 days (configurable)
- **API Response Time**: < 500ms average
- **Concurrent Users**: Supports multiple simultaneous connections
