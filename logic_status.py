import serial
import re
import json
import requests
from datetime import datetime
from shapely.geometry import Point, shape

# --- Config ---
GPS_PORT = "COM9"
SENSOR_PORT = "COM7"
BAUD_RATE = 9600
SENSOR_ID = 1
API_URL = "https://bagowastetracker.bccbsis.com/api/save_logic.php"

# Load Barangay GeoJSON
geojson_path = "barangay_api/brgy.geojson"
with open(geojson_path, "r", encoding="utf-8") as f:
    geojson_data = json.load(f)
barangay_polygon = shape(geojson_data["features"][0]["geometry"])

gps_pattern = re.compile(r'(-?\d+\.\d+),\s*(-?\d+\.\d+)')

gps_serial = serial.Serial(GPS_PORT, BAUD_RATE, timeout=1)
sensor_serial = serial.Serial(SENSOR_PORT, BAUD_RATE, timeout=1)

last_lat, last_lng = None, None
last_location_id = None
inside_fence = False

print("✅ Started GPS + Sensor tracking with SAME location_id using brgy.geojson fence...")

while True:
    try:
        # Read GPS data
        if gps_serial.in_waiting:
            gps_line = gps_serial.readline().decode('utf-8', errors='ignore').strip()
            gps_match = gps_pattern.search(gps_line)
            if gps_match:
                lat, lng = float(gps_match.group(1)), float(gps_match.group(2))
                gps_point = Point(lat, lng)
                inside_fence = barangay_polygon.contains(gps_point)

                gps_moving = False
                if last_lat is not None and last_lng is not None:
                    gps_moving = (abs(lat - last_lat) > 0.00005 or abs(lng - last_lng) > 0.00005)

                last_lat, last_lng = lat, lng
                last_location_id = int(datetime.now().timestamp())

                gps_payload = {
                    "location_id": last_location_id,
                    "latitude": lat,
                    "longitude": lng
                }
                r = requests.post(f"{API_URL}?action=gps", json=gps_payload)
                print("📍 GPS POST:", r.json())

        # Read sensor data
        if sensor_serial.in_waiting and last_location_id is not None:
            sensor_line = sensor_serial.readline().decode(errors='ignore').strip()
            if sensor_line:
                try:
                    count, _ = map(int, sensor_line.split(","))
                except ValueError:
                    continue

                # Determine status
                if inside_fence:
                    if gps_moving and count == 0:
                        status = "Ongoing"
                    elif gps_moving and count > 0:
                        status = "Collecting"
                    else:
                        status = "Idle"
                else:
                    status = "Collected"

                sensor_payload = {
                    "count": count,
                    "location_id": last_location_id,
                    "sensor_id": SENSOR_ID,
                    "status": status
                }
                r = requests.post(f"{API_URL}?action=sensor", json=sensor_payload)
                print("🛠 Sensor POST:", r.json())

    except Exception as e:
        print("❌ Error:", e)
