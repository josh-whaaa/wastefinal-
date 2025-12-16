import serial
import mysql.connector
from datetime import datetime

# Arduino COM port (change COM3 if needed)
arduino = serial.Serial('COM9', 9600, timeout=1)

# MySQL connection
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",  # Add password if your MySQL root has one
    database="cemo_db"
)

cursor = db.cursor()

while True:
    line = arduino.readline().decode(errors='ignore').strip()
    if line:
        try:
            # Expecting "count,location_id" from Arduino
            parts = line.split(",")
            if len(parts) == 2:
                count = int(parts[0])
                location_id = int(parts[1])

                sql = "INSERT INTO sensor (count, location_id, timestamp) VALUES (%s, %s, %s)"
                values = (count, location_id, datetime.now())
                cursor.execute(sql, values)
                db.commit()

                print(f"Inserted -> Count: {count}, Location: {location_id}")

        except Exception as e:
            print(f"Error: {e}")
