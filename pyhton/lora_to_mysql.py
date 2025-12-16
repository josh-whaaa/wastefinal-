import serial
import requests
import json
import time
from datetime import datetime
import os

# ---------------- BARANGAY MAP SETUP ----------------
# Load barangay polygons from GeoJSON and map names to brgy_id via API

_geo_polygons = []  # list of tuples: (name, [ (lng, lat), ... ] for each ring) supports MultiPolygon
_name_to_id = None

def _load_barangay_geojson():
    global _geo_polygons
    try:
        geojson_path = os.path.join("..", "barangay_api", "brgy.geojson")
        with open(geojson_path, "r", encoding="utf-8") as f:
            gj = json.load(f)
        feats = gj.get("features", [])
        result = []
        for feat in feats:
            props = feat.get("properties", {})
            name = props.get("name") or props.get("barangay")
            geom = feat.get("geometry") or {}
            gtype = geom.get("type")
            coords = geom.get("coordinates")
            if not name or not coords:
                continue
            # GeoJSON coordinates are [lng, lat]
            if gtype == "Polygon":
                # coords: [ [ [lng, lat], ... ] outer ring, holes... ]
                rings = []
                for ring in coords:
                    rings.append([(pt[0], pt[1]) for pt in ring])
                result.append((name, rings))
            elif gtype == "MultiPolygon":
                # coords: [ [ [ [lng, lat], ... ] ], ... ]
                for poly in coords:
                    rings = []
                    for ring in poly:
                        rings.append([(pt[0], pt[1]) for pt in ring])
                    result.append((name, rings))
        _geo_polygons = result
        print(f"🗺️ Geo-fence loaded: {len(_geo_polygons)} barangay polygons.")
    except Exception as e:
        _geo_polygons = []
        print("⚠️ Could not load geo-fence:", e)

def _fetch_barangays_map():
    global _name_to_id
    if _name_to_id is not None:
        return _name_to_id
    try:
        resp = requests.get("https://bagowastetracker.bccbsis.com/barangay_api/get_barangays.php", timeout=10)
        data = resp.json()
        mapping = {}
        for b in data:
            nm = b.get("barangay")
            try:
                bid = int(b.get("brgy_id", 0))
            except Exception:
                bid = 0
            if nm and bid:
                mapping[nm] = bid
        _name_to_id = mapping
    except Exception as e:
        print("⚠️ Failed to fetch barangay map:", e)
        _name_to_id = {}
    return _name_to_id

def _point_in_ring(lng, lat, ring):
    # Ray casting algorithm for a single ring (closed polygon). ring: list of (lng, lat)
    inside = False
    n = len(ring)
    if n < 3:
        return False
    x, y = lng, lat
    for i in range(n):
        x1, y1 = ring[i]
        x2, y2 = ring[(i + 1) % n]
        # Check if point is between y1 and y2 in y-axis and to the left of edge
        if ((y1 > y) != (y2 > y)):
            xinters = (x2 - x1) * (y - y1) / (y2 - y1 + 1e-12) + x1
            if x < xinters:
                inside = not inside
    return inside

def _point_in_polygon(lng, lat, rings):
    # Outer ring determines inclusion; holes (if any) exclude
    if not rings:
        return False
    if not _point_in_ring(lng, lat, rings[0]):
        return False
    # If inside outer, ensure not inside any hole rings
    for hole in rings[1:]:
        if _point_in_ring(lng, lat, hole):
            return False
    return True

def compute_brgy_id(latitude, longitude, default_id=1):
    # Ensure geo and name map are loaded
    if not _geo_polygons:
        _load_barangay_geojson()
    name_to_id = _fetch_barangays_map()
    lng = float(longitude)
    lat = float(latitude)
    found_name = None
    for name, rings in _geo_polygons:
        try:
            if _point_in_polygon(lng, lat, rings):
                found_name = name
                break
        except Exception:
            continue
    if found_name and found_name in name_to_id:
        return name_to_id[found_name]
    return default_id

# ---------------- CONFIG ----------------
SERIAL_PORT = "COM8"   # Change this to your LoRa receiver port
BAUD_RATE = 9600
DB_URL = "https://bagowastetracker.bccbsis.com/api/insert_lora.php"  # Update with your local or hosted PHP API

# ---------------- FUNCTION ----------------
def send_to_database(latitude, longitude, distance, count):
    # Handle invalid GPS coordinates (0,0)
    if latitude == 0 and longitude == 0:
        print("📍 Using default location for invalid GPS coordinates")
        # You can set default coordinates here if needed
        # latitude = 14.5995  # Example: Manila coordinates
        # longitude = 120.9842
        brgy_id = 1  # Use default barangay ID
    else:
        brgy_id = compute_brgy_id(latitude, longitude, default_id=1)
    
    payload = {
        "sensor_id": 1,          # or dynamically assign if needed
        "brgy_id": brgy_id,
        "location_id": 1,
        "latitude": latitude,
        "longitude": longitude,
        "distance": distance,
        "count": count,
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    }

    # Retry logic for database connection
    max_retries = 3
    for attempt in range(max_retries):
        try:
            response = requests.post(DB_URL, data=payload, timeout=10)
            if response.status_code == 200:
                print(f"✅ Data sent successfully: {payload}")
                return  # Success, exit retry loop
            else:
                print(f"⚠️ Server error ({response.status_code}): {response.text}")
                if attempt < max_retries - 1:
                    print(f"🔄 Retrying in 2 seconds... (attempt {attempt + 2}/{max_retries})")
                    time.sleep(2)
        except requests.exceptions.ConnectionError as e:
            print(f"❌ Connection failed (attempt {attempt + 1}/{max_retries}): {e}")
            if attempt < max_retries - 1:
                print("💡 Make sure XAMPP is running and Apache is started")
                print(f"🔄 Retrying in 3 seconds...")
                time.sleep(3)
        except Exception as e:
            print(f"❌ Failed to send data: {e}")
            break  # Don't retry for other types of errors

# ---------------- MAIN ----------------
try:
    ser = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1)
    print("📡 Waiting for LoRa JSON data...")
    print(f"🔌 Connected to {SERIAL_PORT} at {BAUD_RATE} baud")
    print("📋 Status messages will be filtered out\n")

    while True:
        if ser.in_waiting > 0:
            line = ser.readline().decode(errors="ignore").strip()

            # Ignore empty lines and decorative separators
            if not line or line.startswith("-"):
                continue
            
            # Filter out Arduino status messages
            if any(status in line for status in [
                "✅ LoRa Receiver ready", 
                "📡 Listening for packets", 
                "Initializing LoRa Receiver",
                "RSSI:"
            ]):
                print(f"🔧 Arduino status: {line}")
                continue

            print(f"📨 Raw data received: {line}")

            try:
                data = json.loads(line)

                latitude = data.get("latitude", 0)
                longitude = data.get("longitude", 0)
                distance = data.get("distance", 0)
                count = data.get("count", 0)
                raw_data = data.get("raw_data", "")

                print(f"📥 Parsed JSON: {data}")

                # Skip corrupted or invalid data
                if raw_data in ["\\", "/", ""] and latitude == 0 and longitude == 0 and distance == 0 and count == 0:
                    print("⚠️ Skipping corrupted data packet")
                    continue

                # Always insert data, even with invalid GPS (0,0)
                # The database will handle the coordinates as provided
                print(f"📍 GPS Status: {'Valid' if latitude != 0 or longitude != 0 else 'Invalid (0,0) - will use default location'}")
                
                send_to_database(latitude, longitude, distance, count)

            except json.JSONDecodeError:
                print(f"⚠️ Non-JSON line ignored: {line}")
                print("💡 Make sure your LoRa transmitter is sending JSON format")
            except Exception as e:
                print(f"❌ Unexpected error: {e}")

        time.sleep(0.2)

except serial.SerialException as e:
    print(f"❌ Serial port error: {e}")
except KeyboardInterrupt:
    print("\n🛑 Exiting program.")
