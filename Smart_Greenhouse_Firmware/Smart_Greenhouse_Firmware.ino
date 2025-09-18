#include <WiFi.h>
#include <WebServer.h>
#include <Wire.h>
#include <ClosedCube_HDC1080.h>
#include <HTTPClient.h>

// ====== WiFi Credentials ======
const char* ssid = "RYUU";
const char* password = "123456789";

// ====== MySQL Database API ======
// e.g. "http://192.168.246.168/greenhouse/insert.php"
const char* serverName = "http://192.168.137.1/greenhouse/insert.php"; 

// ====== HDC1080 Sensor Setup ======
ClosedCube_HDC1080 hdc1080;
bool hdc1080_connected = false;

// ====== Humidity Calibration for Greenhouse ======
float humidity_offset = 0.0;  // Calibration offset
float humidity_scale = 1.0;   // Calibration scale factor
const int humidity_samples = 5;  // Number of samples for averaging

// ====== Sensor Pins ======
#define SOIL_SENSOR_PIN 34
#define LDR_PIN 35

// ====== Relay Pins (Active LOW) ======
#define RELAY_PUMP 26
#define RELAY_LIGHT 27
#define RELAY_FAN 25

WebServer server(80);

// ====== Database Logging Interval ======
unsigned long lastSendTime = 0;
const unsigned long sendInterval = 5000; // 5 seconds

// ====== Function Declarations ======
void scanI2C();
bool initHDC1080();
float readTemperatureAccurate();
float readHumidityAccurate();
void calibrateHumidity(float reference_humidity);
void handleCalibrate();
void checkWiFi();

// ----------------------------------------------------------------------------
// Sends sensor data (humidity, temperature, soil, light) to your local PHP script for MySQL
// ----------------------------------------------------------------------------
void sendToDatabase(float humidity, float temperature, int soil, int light) {
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;

        // e.g. http://192.168.246.168/insert.php?humidity=45.2&temperature=25.3&soil=60&light=1234
        String url = String(serverName);
        url += "?humidity=" + String(humidity);
        url += "&temperature=" + String(temperature);
        url += "&soil=" + String(soil);
        url += "&light=" + String(light);

        http.begin(url);
        int httpCode = http.GET();

        if (httpCode > 0) {
           // Serial.println("[MySQL] Sent: " + url);
            String response = http.getString();
           // Serial.println("[MySQL] Response: " + response);
        } else {
            Serial.println("[MySQL] Failed to connect to server.");
        }
        http.end();
    }
}

// ----------------------------------------------------------------------------
// Simple Status Page (Redirects to PHP Dashboard)
// ----------------------------------------------------------------------------
void handleRoot() {
    String html = R"rawliteral(
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Smart Greenhouse - ESP32</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                text-align: center; 
                padding: 50px; 
                background: linear-gradient(135deg, #2d5a27, #4a7c59);
                color: white;
                margin: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background: rgba(255,255,255,0.1);
                padding: 40px;
                border-radius: 20px;
                backdrop-filter: blur(10px);
            }
            h1 { font-size: 2.5rem; margin-bottom: 20px; }
            .status { font-size: 1.2rem; margin: 20px 0; }
            .btn {
                display: inline-block;
                padding: 15px 30px;
                background: #27ae60;
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-size: 1.1rem;
                margin: 10px;
                transition: all 0.3s;
            }
            .btn:hover { background: #2ecc71; transform: translateY(-2px); }
            .info { margin: 20px 0; font-size: 0.9rem; opacity: 0.8; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🌱 Smart Greenhouse</h1>
            <div class="status">ESP32 System Online</div>
            <p>This is the ESP32 sensor interface. For the full dashboard experience, please use the web interface.</p>
            
            <a href="http://192.168.137.1/greenhouse/dashboard.php" class="btn">Open Dashboard</a>
            <a href="http://192.168.137.1/greenhouse/history.php" class="btn">View History</a>
            
            <div class="info">
                <p><strong>System Status:</strong> All sensors operational</p>
                <p><strong>Last Update:</strong> )rawliteral" + String(millis() / 1000) + R"rawliteral( seconds ago</p>
            </div>
        </div>
    </body>
    </html>
    )rawliteral";

    server.send(200, "text/html", html);
}

// ----------------------------------------------------------------------------
// Relay Controls
// ----------------------------------------------------------------------------
void handleRelayControl() {
    String relay = server.arg("relay");
    String state = server.arg("state");

    if (relay == "1") {
        digitalWrite(RELAY_PUMP, (state == "on") ? LOW : HIGH);
        server.send(200, "text/plain", "Pump " + state);
    } else if (relay == "2") {
        digitalWrite(RELAY_LIGHT, (state == "on") ? LOW : HIGH);
        server.send(200, "text/plain", "Light " + state);
    } else if (relay == "3") {
        digitalWrite(RELAY_FAN, (state == "on") ? LOW : HIGH);
        server.send(200, "text/plain", "Fan " + state);
    } else {
        server.send(400, "text/plain", "Invalid Relay");
    }
}

// ----------------------------------------------------------------------------
// Sensor Endpoints
// ----------------------------------------------------------------------------
void handleHumidity() {
    float humidity = readHumidityAccurate();
    if (humidity == -999.0) humidity = -1;
    server.send(200, "text/plain", String(humidity, 2));
}

void handleTemperature() {
    float temperature = readTemperatureAccurate();
    if (temperature == -999.0) temperature = -1;
    server.send(200, "text/plain", String(temperature, 2));
}

void handleSoilMoisture() {
    // Take multiple readings for better accuracy
    int soil_sum = 0;
    const int soil_samples = 3;
    
    for (int i = 0; i < soil_samples; i++) {
        soil_sum += analogRead(SOIL_SENSOR_PIN);
        delay(10);
    }
    
    int soilRaw = soil_sum / soil_samples;
    
    // Greenhouse-optimized soil moisture calibration
    // Adjust these values based on your specific soil sensor
    int wet = 1000, dry = 3200;
    int percent = map(soilRaw, dry, wet, 0, 100);
    percent = constrain(percent, 0, 100);

    String label;
    if (percent < 15) label = "Very Dry";
    else if (percent < 30) label = "Dry";
    else if (percent < 60) label = "Moist";
    else if (percent < 80) label = "Wet";
    else label = "Saturated";

    server.send(200, "text/plain", String(percent) + "|" + label);
}

void handleLightIntensity() {
    int light = analogRead(LDR_PIN);
    server.send(200, "text/plain", String(light));
}

// ----------------------------------------------------------------------------
// JSON Status Endpoint
// ----------------------------------------------------------------------------
void handleStatus() {
    float humidity = readHumidityAccurate();
    if (humidity == -999.0) humidity = -1;
    
    float temperature = readTemperatureAccurate();
    if (temperature == -999.0) temperature = -1;
    
    int soilRaw = analogRead(SOIL_SENSOR_PIN);
    int soilPercent = map(soilRaw, 3200, 1000, 0, 100);
    soilPercent = constrain(soilPercent, 0, 100);
    
    int light = analogRead(LDR_PIN);
    
    String json = "{";
    json += "\"status\":\"online\",";
    json += "\"uptime\":" + String(millis() / 1000) + ",";
    json += "\"sensors\":{";
    json += "\"humidity\":" + String(humidity, 2) + ",";
    json += "\"temperature\":" + String(temperature, 2) + ",";
    json += "\"soil\":" + String(soilPercent) + ",";
    json += "\"light\":" + String(light) + ",";
    json += "\"hdc1080_connected\":" + String(hdc1080_connected ? "true" : "false");
    json += "},";
    json += "\"relays\":{";
    json += "\"pump\":" + String(digitalRead(RELAY_PUMP) == LOW ? "on" : "off") + ",";
    json += "\"light\":" + String(digitalRead(RELAY_LIGHT) == LOW ? "on" : "off") + ",";
    json += "\"fan\":" + String(digitalRead(RELAY_FAN) == LOW ? "on" : "off");
    json += "},";
    json += "\"wifi\":{";
    json += "\"ssid\":\"" + String(WiFi.SSID()) + "\",";
    json += "\"rssi\":" + String(WiFi.RSSI()) + ",";
    json += "\"ip\":\"" + WiFi.localIP().toString() + "\"";
    json += "},";
    json += "\"timestamp\":" + String(millis());
    json += "}";
    
    server.send(200, "application/json", json);
}

// ----------------------------------------------------------------------------
// Calibration Handler
// ----------------------------------------------------------------------------
void handleCalibrate() {
    String ref_humidity_str = server.arg("humidity");
    
    if (ref_humidity_str.length() > 0) {
        float ref_humidity = ref_humidity_str.toFloat();
        if (ref_humidity >= 0 && ref_humidity <= 100) {
            calibrateHumidity(ref_humidity);
            server.send(200, "text/plain", "Calibration completed. Offset: " + String(humidity_offset, 2) + "%");
        } else {
            server.send(400, "text/plain", "Invalid humidity value. Must be 0-100");
        }
    } else {
        server.send(400, "text/plain", "Missing humidity parameter. Use: /calibrate?humidity=50.0");
    }
}

// ----------------------------------------------------------------------------
// I2C Scanner
// ----------------------------------------------------------------------------
void scanI2C() {
    Serial.println("Scanning I2C devices...");
    byte count = 0;
    for (byte i = 8; i < 120; i++) {
        Wire.beginTransmission(i);
        if (Wire.endTransmission() == 0) {
            Serial.print("Found I2C device at address 0x");
            if (i < 16) Serial.print("0");
            Serial.print(i, HEX);
            Serial.println(" !");
            count++;
        }
    }
    if (count == 0) {
        Serial.println("No I2C devices found");
    }
}

// ----------------------------------------------------------------------------
// HDC1080 Sensor Functions
// ----------------------------------------------------------------------------
bool initHDC1080() {
    Wire.begin();
    delay(100);
    
    // Try different I2C addresses for HDC1080
    uint8_t addresses[] = {0x40, 0x41, 0x42, 0x43};
    
    for (int i = 0; i < 4; i++) {
        hdc1080.begin(addresses[i]);
        delay(100);
        
        // Test if sensor responds
        float test_temp = hdc1080.readTemperature();
        if (!isnan(test_temp) && test_temp > -50 && test_temp < 100) {
            Serial.println("HDC1080 found at address 0x" + String(addresses[i], HEX));
            hdc1080_connected = true;
            return true;
        }
    }
    
    Serial.println("HDC1080 not found!");
    hdc1080_connected = false;
    return false;
}

float readTemperatureAccurate() {
    if (!hdc1080_connected) return -999.0;
    
    float temp = hdc1080.readTemperature();
    if (isnan(temp) || temp < -50 || temp > 100) {
        return -999.0;
    }
    return temp;
}

float readHumidityAccurate() {
    if (!hdc1080_connected) return -999.0;
    
    float humidity_sum = 0.0;
    int valid_readings = 0;
    
    // Take multiple samples for better accuracy and stability
    for (int i = 0; i < humidity_samples; i++) {
        float raw_humidity = hdc1080.readHumidity();
        
        // Validate reading
        if (!isnan(raw_humidity) && raw_humidity >= 0.0 && raw_humidity <= 100.0) {
            humidity_sum += raw_humidity;
            valid_readings++;
        }
        delay(50); // Small delay between readings
    }
    
    if (valid_readings == 0) return -999.0;
    
    // Calculate average
    float avg_humidity = humidity_sum / valid_readings;
    
    // Apply calibration for greenhouse conditions
    float calibrated_humidity = (avg_humidity * humidity_scale) + humidity_offset;
    
    // Ensure within valid range
    calibrated_humidity = constrain(calibrated_humidity, 0.0, 100.0);
    
    return calibrated_humidity;
}

// ----------------------------------------------------------------------------
// Humidity Calibration Function
// ----------------------------------------------------------------------------
void calibrateHumidity(float reference_humidity) {
    if (!hdc1080_connected) return;
    
    // Take multiple readings for calibration
    float raw_sum = 0.0;
    int valid_readings = 0;
    
    for (int i = 0; i < 10; i++) {
        float raw_humidity = hdc1080.readHumidity();
        if (!isnan(raw_humidity) && raw_humidity >= 0.0 && raw_humidity <= 100.0) {
            raw_sum += raw_humidity;
            valid_readings++;
        }
        delay(100);
    }
    
    if (valid_readings > 0) {
        float avg_raw = raw_sum / valid_readings;
        humidity_offset = reference_humidity - avg_raw;
        Serial.println("Humidity calibrated: Offset = " + String(humidity_offset, 2) + "%");
    }
}

// ----------------------------------------------------------------------------
// WiFi Reconnect
// ----------------------------------------------------------------------------
void checkWiFi() {
    if (WiFi.status() != WL_CONNECTED) {
        WiFi.disconnect();
        WiFi.reconnect();
    }
}

// ----------------------------------------------------------------------------
// Setup
// ----------------------------------------------------------------------------
void setup() {
    Serial.begin(115200);
    delay(1000);
    
    Serial.println("Starting Smart Greenhouse System...");
    
    // Initialize I2C and scan for devices
    scanI2C();
    
    // Initialize HDC1080 sensor
    if (initHDC1080()) {
        Serial.println("HDC1080 sensor initialized successfully");
    } else {
        Serial.println("Warning: HDC1080 sensor not found - using fallback values");
    }

    pinMode(RELAY_PUMP, OUTPUT);
    pinMode(RELAY_LIGHT, OUTPUT);
    pinMode(RELAY_FAN, OUTPUT);

    // Default relay states = OFF
    digitalWrite(RELAY_PUMP, HIGH);
    digitalWrite(RELAY_LIGHT, HIGH);
    digitalWrite(RELAY_FAN, HIGH);

    // WiFi Connect
    WiFi.begin(ssid, password);
    Serial.print("Connecting to WiFi...");
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println("\nWiFi connected!");
    Serial.println(WiFi.localIP());

    // Routes
    server.on("/", handleRoot);
    server.on("/control", handleRelayControl);
    server.on("/humidity", handleHumidity);
    server.on("/temperature", handleTemperature);
    server.on("/soil", handleSoilMoisture);
    server.on("/light", handleLightIntensity);
    server.on("/status", handleStatus);
    server.on("/calibrate", handleCalibrate);

    server.begin();
}

// ----------------------------------------------------------------------------
// Main Loop
// ----------------------------------------------------------------------------
void loop() {
    server.handleClient();
    checkWiFi();

    // Every 5 seconds, insert data to MySQL
    if (millis() - lastSendTime > sendInterval) {
        float humidity = readHumidityAccurate();
        float temperature = readTemperatureAccurate();
        if (humidity != -999.0 && temperature != -999.0) {
            int soilRaw = analogRead(SOIL_SENSOR_PIN);
            int soilPercent = map(soilRaw, 3200, 1000, 0, 100);
            soilPercent = constrain(soilPercent, 0, 100);

            int lightValue = analogRead(LDR_PIN);

            // Save to MySQL
            sendToDatabase(humidity, temperature, soilPercent, lightValue);
        }
        lastSendTime = millis();
    }
}