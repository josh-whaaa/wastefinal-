import serial
import requests
import json
import time
from datetime import datetime

try:
    # Optional: use the same geo-fence logic as logic_status.py if Shapely is available
    from shapely.geometry import Point, shape
    import os
    SHAPELY_AVAILABLE = True
except Exception:
    SHAPELY_AVAILABLE = False

# -------------------- CONFIG --------------------
PORT = "COM8"       # Change if your Arduino uses another port
BAUD = 9600

# Endpoints
# POST_URL = "http://localhost/CEMO_System/final/api/insert_lora.php"  # local combined insert
POST_URL = "https://bagowastetracker.bccbsis.com/api/insert_lora.php"  # online

# Single-endpoint flow: only use insert_lora.php

# Static IDs (adjust as needed)
DEFAULT_SENSOR_ID = 1
DEFAULT_BRGY_ID = 1


# -------------------- SETUP --------------------
print(f"🔌 Connecting to Arduino on {PORT} ...")
ser = serial.Serial(PORT, BAUD, timeout=2)
print("✅ Connected successfully!\n")
print("📡 Listening for sensor data from Arduino...\n")

# --- Optional geo-fence (replicates logic_status.py behavior) ---
geo_shape = None
if SHAPELY_AVAILABLE:
    try:
        geojson_path = os.path.join("barangay_api", "brgy.geojson")
        with open(geojson_path, "r", encoding="utf-8") as f:
            geojson_data = json.load(f)
        # Build map of polygons and names
        geo_features = geojson_data["features"]
        geo_polygons = []  # list of (name, shapely_shape)
        for feat in geo_features:
            try:
                name = feat.get("properties", {}).get("name") or feat.get("properties", {}).get("barangay")
                shp = shape(feat["geometry"]) if feat.get("geometry") else None
                if name and shp:
                    geo_polygons.append((name, shp))
            except Exception:
                continue
        print(f"🗺️ Geo-fence loaded: {len(geo_polygons)} barangay polygons.")
    except Exception as e:
        print("⚠️ Could not load geo-fence:", e)
        geo_polygons = []

# Track last GPS for movement detection
last_lat = None
last_lng = None
last_location_id = None
last_brgy_name = None
last_brgy_id = DEFAULT_BRGY_ID

# Per-barangay baseline for sensor counts
per_brgy_baseline = {}

# -------------------- LOOP --------------------
while True:
    try:
        if ser.in_waiting:
            line = ser.readline().decode('utf-8', errors='ignore').strip()
            if not line:
                continue

            print("📡 Raw Data:", line)

            # Example format from Arduino: NoGPS,NoGPS,206.65,11
            parts = line.split(',')
            if len(parts) == 4:
                lat_str, lng_str, distance_str, count_str = parts

                # Replace NoGPS with 0.0 for safety
                lat = 0.0 if "NoGPS" in lat_str else float(lat_str)
                lng = 0.0 if "NoGPS" in lng_str else float(lng_str)
                distance = float(distance_str)
                count = int(count_str)

                # --- Status logic (mirrors logic_status.py) ---
                gps_moving = False
                if last_lat is not None and last_lng is not None:
                    gps_moving = (abs(lat - last_lat) > 0.00005 or abs(lng - last_lng) > 0.00005)

                inside_fence = False
                current_brgy_name = None
                if SHAPELY_AVAILABLE and geo_polygons:
                    try:
                        pt = Point(lat, lng)
                        for name, shp in geo_polygons:
                            if shp.contains(pt):
                                inside_fence = True
                                current_brgy_name = name
                                break
                    except Exception:
                        inside_fence = False

                last_lat, last_lng = lat, lng
                # Generate a fresh location_id similar to logic_status.py
                last_location_id = int(datetime.now().timestamp())

                if inside_fence:
                    if gps_moving and count == 0:
                        status = "Ongoing"
                    elif gps_moving and count > 0:
                        status = "Collecting"
                    else:
                        status = "Idle"
                else:
                    status = "Collected"

                # --- Determine brgy_id via local API mapping ---
                # Cache barangay list for fast lookup
                def fetch_barangays_map():
                    try:
                        resp = requests.get("https://bagowastetracker.bccbsis.com/barangay_api/get_barangays.php", timeout=10)
                        data = resp.json()
                        name_to_id = {}
                        for b in data:
                            name_to_id[b.get("barangay")] = int(b.get("brgy_id", 0))
                        return name_to_id
                    except Exception as e:
                        print("⚠️ Failed to fetch barangays:", e)
                        return {}

                if not hasattr(fetch_barangays_map, "cache"):
                    fetch_barangays_map.cache = fetch_barangays_map()
                name_to_id = fetch_barangays_map.cache

                current_brgy_id = last_brgy_id
                if current_brgy_name and current_brgy_name in name_to_id:
                    current_brgy_id = name_to_id[current_brgy_name]
                elif current_brgy_id == 0 or current_brgy_id is None:
                    # Fallback to default if no valid barangay detected
                    current_brgy_id = DEFAULT_BRGY_ID

                # --- Per-barangay baseline reset logic ---
                if current_brgy_name != last_brgy_name and current_brgy_name is not None:
                    # Entered a new barangay: capture baseline for that barangay
                    per_brgy_baseline[current_brgy_id] = count
                    print(f"🔄 Baseline set for {current_brgy_name} (id={current_brgy_id}) at count {count}")
                    last_brgy_name = current_brgy_name
                    last_brgy_id = current_brgy_id

                # Compute per-barangay relative count
                baseline = per_brgy_baseline.get(current_brgy_id, 0)
                relative_count = max(0, count - baseline)

                # Prepare JSON payload
                json_data = {
                    "sensor_id": DEFAULT_SENSOR_ID,
                    "count": relative_count,
                    "brgy_id": current_brgy_id,
                    "location_id": last_location_id,
                    "distance": distance,
                    "latitude": lat,
                    "longitude": lng,
                    "status": status
                }

                print("📦 JSON Data to Send:", json.dumps(json_data))

                # Send combined insert to PHP
                response = requests.post(POST_URL, json=json_data, timeout=10)

                # Show response
                if response.status_code == 200:
                    print("✅ Server Response:", response.text)
                else:
                    print(f"❌ Server error {response.status_code}: {response.text}")

                print("-" * 50)
    except Exception as e:
        print("⚠️ Error:", e)
