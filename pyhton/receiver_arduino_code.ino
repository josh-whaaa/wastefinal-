#include <SPI.h>
#include <LoRa.h>
#include <ArduinoJson.h>

// -------------------- PIN SETUP --------------------
#define LORA_CS    10
#define LORA_RST   9
#define LORA_DIO0  2

// -------------------- SETUP --------------------
void setup() {
  Serial.begin(9600);
  
  // Only send status to Serial Monitor, not to Python
  // Serial.println("Initializing LoRa Receiver...");
  LoRa.setPins(LORA_CS, LORA_RST, LORA_DIO0);
  
  if (!LoRa.begin(433E6)) {
    // Serial.println("❌ LoRa init failed. Check wiring or frequency.");
    while (1);
  }
  
  // Send ready signal to Python (this will be filtered out)
  Serial.println("✅ LoRa Receiver ready");
  Serial.println("📡 Listening for packets...");
}

// -------------------- LOOP --------------------
void loop() {
  // Check for incoming LoRa packets
  int packetSize = LoRa.parsePacket();
  
  if (packetSize) {
    // Read the packet
    String receivedData = "";
    while (LoRa.available()) {
      receivedData += (char)LoRa.read();
    }
    
    // Clean the received data (remove null characters, control characters)
    receivedData.trim();
    receivedData.replace("\0", ""); // Remove null characters
    
    // Skip if data is too short or contains only special characters
    if (receivedData.length() < 3 || receivedData == "\\" || receivedData == "/") {
      return; // Skip this packet
    }
    
    // Try to parse as JSON first
    JsonDocument doc;
    DeserializationError error = deserializeJson(doc, receivedData);
    
    if (error) {
      // If not JSON, try to create JSON from the raw data
      // Assume format: "lat,lng,distance,count" or similar
      createJsonFromRawData(receivedData);
    } else {
      // If it's already JSON, forward it directly
      Serial.println(receivedData);
    }
  }
  
  delay(100); // Small delay
}

// Function to create JSON from raw sensor data
void createJsonFromRawData(String rawData) {
  JsonDocument doc;
  
  // Try different parsing methods based on expected format
  if (rawData.indexOf(',') > 0) {
    // CSV format: "latitude,longitude,distance,count"
    int firstComma = rawData.indexOf(',');
    int secondComma = rawData.indexOf(',', firstComma + 1);
    int thirdComma = rawData.indexOf(',', secondComma + 1);
    
    if (thirdComma > 0) {
      doc["latitude"] = rawData.substring(0, firstComma).toFloat();
      doc["longitude"] = rawData.substring(firstComma + 1, secondComma).toFloat();
      doc["distance"] = rawData.substring(secondComma + 1, thirdComma).toFloat();
      doc["count"] = rawData.substring(thirdComma + 1).toInt();
    }
  } else {
    // Single value or other format - put in a generic field
    doc["raw_data"] = rawData;
    doc["latitude"] = 0.0;
    doc["longitude"] = 0.0;
    doc["distance"] = 0.0;
    doc["count"] = 0;
  }
  
  // Add timestamp
  doc["timestamp"] = millis();
  
  // Send as JSON
  String jsonString;
  serializeJson(doc, jsonString);
  Serial.println(jsonString);
}
