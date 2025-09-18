# Smart Greenhouse System - Enhanced Dashboard

## Overview

This is an enhanced Smart Greenhouse monitoring and control system with a modern, greenhouse-inspired dashboard design. The system has been upgraded to separate concerns between the ESP32 firmware (sensor data collection and relay control) and PHP-based web interface (UI/UX and data management).

## System Architecture

### ESP32 Firmware (`Smart_Greenhouse_Firmware.ino`)
- **Purpose**: Sensor data collection and relay control
- **Responsibilities**:
  - Read DHT11 humidity sensor
  - Read soil moisture sensor
  - Read light intensity sensor (LDR)
  - Control water pump, grow lights, and exhaust fan relays
  - Provide REST API endpoints for sensor data
  - Send data to MySQL database via PHP API

### PHP Web Interface
- **`dashboard.php`**: Main greenhouse-inspired dashboard with real-time monitoring
- **`history.php`**: Enhanced history API with filtering and analytics
- **`insert.php`**: Improved data insertion API with validation and error handling
- **`api.php`**: Comprehensive REST API for system integration
- **`view.php`**: Simple history viewing interface

### Database (`greenhouse`)
- **`readings`**: Sensor data storage
- **`control_log`**: Relay control action logging
- **`alerts`**: System alerts and notifications
- **`system_config`**: Configuration settings

## Features

### 🌱 Greenhouse-Inspired Design
- Natural green color palette with earth tones
- Animated background elements and smooth transitions
- Glass-morphism effects and modern card-based layout
- Responsive design for mobile and desktop

### 📊 Real-Time Monitoring
- Live sensor data updates every 2 seconds
- Visual progress bars with color-coded status indicators
- Animated loading states and smooth transitions
- System status indicators

### 🎛️ System Controls
- Water pump control
- Grow lights control
- Exhaust fan control
- Real-time relay status feedback

### 📈 Data Analytics
- Historical data visualization
- Daily/weekly/monthly trends
- Alert system with configurable thresholds
- Performance metrics and system health monitoring

### 🔧 API Integration
- RESTful API endpoints
- JSON responses with proper error handling
- CORS support for cross-origin requests
- Comprehensive status and analytics endpoints

## Installation & Setup

### 1. Database Setup
```sql
-- Import the database schema
mysql -u root -p < database_schema.sql
```

### 2. ESP32 Configuration
1. Update WiFi credentials in `Smart_Greenhouse_Firmware.ino`:
   ```cpp
   const char* ssid = "YOUR_WIFI_SSID";
   const char* password = "YOUR_WIFI_PASSWORD";
   ```

2. Update server IP address:
   ```cpp
   const char* serverName = "http://YOUR_SERVER_IP/insert.php";
   ```

3. Upload firmware to ESP32

### 3. PHP Configuration
1. Place all PHP files in your web server directory
2. Update database credentials in PHP files if needed
3. Update ESP32 IP address in `dashboard.php` and `api.php`

### 4. Hardware Connections

#### Sensors
- **DHT11**: Pin 4 (humidity & temperature)
- **Soil Moisture**: Pin 34 (analog)
- **Light Sensor (LDR)**: Pin 35 (analog)

#### Relays (Active LOW)
- **Water Pump**: Pin 26
- **Grow Lights**: Pin 27
- **Exhaust Fan**: Pin 25

## API Endpoints

### ESP32 Endpoints
- `GET /` - Simple status page with dashboard links
- `GET /humidity` - Current humidity reading
- `GET /soil` - Current soil moisture reading
- `GET /light` - Current light intensity reading
- `GET /status` - Complete system status (JSON)
- `GET /control?relay=X&state=on|off` - Control relays

### PHP API Endpoints
- `GET /api.php/sensors` - Get current sensor readings
- `POST /api.php/control` - Control relays via API
- `GET /api.php/status` - Get comprehensive system status
- `GET /api.php/history` - Get sensor history with filtering
- `GET /api.php/analytics` - Get analytics and trends
- `GET /history.php` - Enhanced history API
- `GET /insert.php` - Insert sensor data (used by ESP32)

## Usage

### Accessing the Dashboard
1. Open your web browser
2. Navigate to `http://YOUR_SERVER_IP/dashboard.php`
3. The dashboard will automatically connect to your ESP32 and display real-time data

### Controlling the System
- Use the control buttons on the dashboard to turn relays on/off
- Monitor real-time sensor readings and system status
- View historical data and trends in the history section

### API Usage
```javascript
// Get current sensor data
fetch('/api.php/sensors')
  .then(response => response.json())
  .then(data => console.log(data));

// Control a relay
fetch('/api.php/control', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({relay: 1, state: 'on'})
})
  .then(response => response.json())
  .then(data => console.log(data));
```

## Configuration

### System Settings
Edit the `system_config` table to adjust:
- Sensor thresholds for alerts
- Data retention period
- Dashboard refresh intervals
- ESP32 IP address

### Alert Thresholds
- **Humidity**: 30-80% (optimal: 40-70%)
- **Soil Moisture**: 20-80% (optimal: 40-70%)
- **Light Intensity**: 500-3000 (optimal: 1000-2000)

## Maintenance

### Database Cleanup
```sql
-- Clean old data (keeps last 30 days by default)
CALL CleanOldData();
```

### Alert Management
```sql
-- Check for new alerts
CALL CheckAlerts();

-- View active alerts
SELECT * FROM active_alerts;

-- Resolve an alert
UPDATE alerts SET is_resolved = TRUE, resolved_at = NOW() WHERE id = 1;
```

## Troubleshooting

### ESP32 Connection Issues
1. Check WiFi credentials
2. Verify ESP32 IP address in PHP files
3. Ensure ESP32 and server are on same network

### Database Connection Issues
1. Verify MySQL credentials in PHP files
2. Check if MySQL service is running
3. Ensure database exists and has proper permissions

### Dashboard Not Updating
1. Check browser console for JavaScript errors
2. Verify ESP32 endpoints are accessible
3. Check network connectivity between browser and ESP32

## File Structure

```
Smart_Greenhouse_Firmware/
├── Smart_Greenhouse_Firmware.ino    # ESP32 firmware
├── dashboard.php                    # Main dashboard interface
├── history.php                      # History API
├── insert.php                       # Data insertion API
├── api.php                          # Comprehensive REST API
├── view.php                         # Simple history viewer
├── database_schema.sql              # Database structure
└── README.md                        # This file
```

## Version History

### v3.0 (Current)
- ✅ Greenhouse-inspired dashboard design
- ✅ Separated UI from firmware
- ✅ Enhanced PHP APIs with better error handling
- ✅ Real-time data updates
- ✅ Comprehensive system monitoring
- ✅ Alert system with configurable thresholds
- ✅ Mobile-responsive design

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
