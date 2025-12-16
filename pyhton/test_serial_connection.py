import serial
import time
import json

# Configuration
SERIAL_PORT = "COM8"  # Change this to your port
BAUD_RATE = 9600

def test_serial_connection():
    print("🔍 Testing Serial Connection...")
    print(f"Port: {SERIAL_PORT}")
    print(f"Baud Rate: {BAUD_RATE}")
    print("-" * 40)
    
    try:
        # Open serial connection
        ser = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=2)
        print("✅ Serial connection established!")
        
        # Wait for initial data
        print("⏳ Waiting for Arduino to initialize...")
        time.sleep(3)
        
        # Read and display all incoming data for 10 seconds
        print("📡 Reading data for 10 seconds...")
        start_time = time.time()
        
        while time.time() - start_time < 10:
            if ser.in_waiting > 0:
                line = ser.readline().decode(errors="ignore").strip()
                if line:
                    print(f"📨 Received: {line}")
                    
                    # Try to parse as JSON
                    try:
                        data = json.loads(line)
                        print(f"✅ Valid JSON: {data}")
                    except json.JSONDecodeError:
                        print(f"⚠️ Not JSON: {line}")
            time.sleep(0.1)
        
        ser.close()
        print("✅ Test completed!")
        
    except serial.SerialException as e:
        print(f"❌ Serial connection failed: {e}")
        print("\n💡 Troubleshooting tips:")
        print("1. Check if Arduino is connected")
        print("2. Verify the COM port number")
        print("3. Make sure Arduino IDE is closed")
        print("4. Try unplugging and reconnecting Arduino")
        print("5. Run find_arduino_port.py to detect the correct port")

if __name__ == "__main__":
    test_serial_connection()
