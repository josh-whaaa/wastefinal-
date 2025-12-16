#!/usr/bin/env python3
"""
Script to insert realistic test data into the CEMO System online database
Usage: python insert_test_data.py
"""

import mysql.connector
import json
import random
from datetime import datetime, timedelta
import time

# Database configuration for online
DB_CONFIG = {
    'host': 'localhost',
    'user': 'u520834156_userWT2025',
    'password': '^Lx|Aii1',
    'database': 'u520834156_DBWasteTracker',
    'charset': 'utf8mb4'
}

def connect_to_database():
    """Connect to the online database"""
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        print("✅ Connected to online database successfully!")
        return connection
    except mysql.connector.Error as err:
        print(f"❌ Database connection failed: {err}")
        return None

def insert_barangays_data(cursor):
    """Insert realistic barangay data for Bago City"""
    barangays = [
        (1, 'Abuanan', 10.5466, 122.9907, 'Bago City', 'https://facebook.com/abuanan', 'Abuanan FB Page', 1),
        (2, 'Alianza', 10.5389, 122.9856, 'Bago City', 'https://facebook.com/alianza', 'Alianza FB Page', 1),
        (3, 'Atipuluan', 10.5321, 122.9789, 'Bago City', 'https://facebook.com/atipuluan', 'Atipuluan FB Page', 2),
        (4, 'Bacong-Montilla', 10.5256, 122.9723, 'Bago City', 'https://facebook.com/bacongmontilla', 'Bacong-Montilla FB Page', 2),
        (5, 'Bagroy', 10.5189, 122.9656, 'Bago City', 'https://facebook.com/bagroy', 'Bagroy FB Page', 3),
        (6, 'Balingasag', 10.5123, 122.9589, 'Bago City', 'https://facebook.com/balingasag', 'Balingasag FB Page', 3),
        (7, 'Binubuhan', 10.5056, 122.9523, 'Bago City', 'https://facebook.com/binubuhan', 'Binubuhan FB Page', 4),
        (8, 'Busay', 10.4989, 122.9456, 'Bago City', 'https://facebook.com/busay', 'Busay FB Page', 4),
        (9, 'Calumangan', 10.4923, 122.9389, 'Bago City', 'https://facebook.com/calumangan', 'Calumangan FB Page', 5),
        (10, 'Caridad', 10.4856, 122.9323, 'Bago City', 'https://facebook.com/caridad', 'Caridad FB Page', 5),
        (11, 'Don Jorge L. Araneta', 10.4789, 122.9256, 'Bago City', 'https://facebook.com/donjorge', 'Don Jorge FB Page', 6),
        (12, 'Dulao', 10.4723, 122.9189, 'Bago City', 'https://facebook.com/dulao', 'Dulao FB Page', 6),
        (13, 'Ilijan', 10.4656, 122.9123, 'Bago City', 'https://facebook.com/ilijan', 'Ilijan FB Page', 7),
        (14, 'Lag-Asan', 10.4589, 122.9056, 'Bago City', 'https://facebook.com/lagasan', 'Lag-Asan FB Page', 7),
        (15, 'Mabini', 10.4523, 122.8989, 'Bago City', 'https://facebook.com/mabini', 'Mabini FB Page', 8),
        (16, 'Mailum', 10.4456, 122.8923, 'Bago City', 'https://facebook.com/mailum', 'Mailum FB Page', 8),
        (17, 'Malingin', 10.4389, 122.8856, 'Bago City', 'https://facebook.com/malingin', 'Malingin FB Page', 9),
        (18, 'Ma-ao', 10.4323, 122.8789, 'Bago City', 'https://facebook.com/maao', 'Ma-ao FB Page', 9),
        (19, 'Napoles', 10.4256, 122.8723, 'Bago City', 'https://facebook.com/napoles', 'Napoles FB Page', 10),
        (20, 'Pacol', 10.4189, 122.8656, 'Bago City', 'https://facebook.com/pacol', 'Pacol FB Page', 10)
    ]
    
    insert_query = """
    INSERT INTO barangays_table (brgy_id, barangay, latitude, longitude, city, facebook_link, link_text, schedule_id)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE
    barangay = VALUES(barangay),
    latitude = VALUES(latitude),
    longitude = VALUES(longitude),
    city = VALUES(city),
    facebook_link = VALUES(facebook_link),
    link_text = VALUES(link_text),
    schedule_id = VALUES(schedule_id)
    """
    
    cursor.executemany(insert_query, barangays)
    print(f"✅ Inserted {len(barangays)} barangays")

def generate_sensor_data(cursor, days=30):
    """Generate realistic sensor data for the past N days"""
    sensor_data = []
    gps_data = []
    agg_data = []
    
    # Base coordinates for each barangay
    barangay_coords = {
        1: (10.5466, 122.9907), 2: (10.5389, 122.9856), 3: (10.5321, 122.9789),
        4: (10.5256, 122.9723), 5: (10.5189, 122.9656), 6: (10.5123, 122.9589),
        7: (10.5056, 122.9523), 8: (10.4989, 122.9456), 9: (10.4923, 122.9389),
        10: (10.4856, 122.9323)
    }
    
    statuses = ['Collecting', 'Idle', 'Ongoing', 'Collected']
    
    for day in range(days):
        current_date = datetime.now() - timedelta(days=day)
        
        for brgy_id in range(1, 11):  # First 10 barangays
            # Generate 3-5 readings per day per barangay
            readings_per_day = random.randint(3, 5)
            
            for reading in range(readings_per_day):
                # Generate realistic sensor values
                count = random.randint(20, 80)
                distance = round(random.uniform(60, 120), 1)
                status = random.choice(statuses)
                
                # Generate timestamp
                hour = random.randint(6, 18)  # Working hours
                minute = random.randint(0, 59)
                timestamp = current_date.replace(hour=hour, minute=minute, second=0)
                
                # Generate location ID
                location_id = int(timestamp.timestamp())
                
                # Get coordinates with small random variation
                base_lat, base_lng = barangay_coords[brgy_id]
                lat = round(base_lat + random.uniform(-0.01, 0.01), 4)
                lng = round(base_lng + random.uniform(-0.01, 0.01), 4)
                
                # Sensor data
                sensor_data.append((1, count, brgy_id, location_id, timestamp, distance, status))
                
                # GPS data
                gps_data.append((location_id, lat, lng, timestamp))
            
            # Daily aggregate data
            daily_count = sum([data[1] for data in sensor_data[-readings_per_day:]])
            last_distance = sensor_data[-1][5]
            last_lat, last_lng = gps_data[-1][1], gps_data[-1][2]
            
            agg_data.append((current_date.date(), 1, brgy_id, location_id, daily_count, last_distance, last_lat, last_lng))
    
    # Insert sensor data
    sensor_query = """
    INSERT INTO sensor (sensor_id, count, brgy_id, location_id, timestamp, distance, status)
    VALUES (%s, %s, %s, %s, %s, %s, %s)
    """
    cursor.executemany(sensor_query, sensor_data)
    print(f"✅ Inserted {len(sensor_data)} sensor readings")
    
    # Insert GPS data
    gps_query = """
    INSERT INTO gps_location (location_id, latitude, longitude, timestamp)
    VALUES (%s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE
    latitude = VALUES(latitude),
    longitude = VALUES(longitude),
    timestamp = VALUES(timestamp)
    """
    cursor.executemany(gps_query, gps_data)
    print(f"✅ Inserted {len(gps_data)} GPS locations")
    
    # Insert aggregate data
    agg_query = """
    INSERT INTO sensor_agg_daily (date, sensor_id, brgy_id, location_id, total_count, last_distance, last_latitude, last_longitude)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE
    total_count = VALUES(total_count),
    last_distance = VALUES(last_distance),
    last_latitude = VALUES(last_latitude),
    last_longitude = VALUES(last_longitude),
    last_updated = CURRENT_TIMESTAMP
    """
    cursor.executemany(agg_query, agg_data)
    print(f"✅ Inserted {len(agg_data)} daily aggregates")

def insert_client_data(cursor):
    """Insert realistic client data"""
    clients = [
        (1, 'Maria', 'Santos', '09171234567', 'maria.santos@gmail.com', 'Abuanan', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS'),
        (2, 'Juan', 'Cruz', '09172345678', 'juan.cruz@yahoo.com', 'Alianza', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS'),
        (3, 'Ana', 'Reyes', '09173456789', 'ana.reyes@outlook.com', 'Atipuluan', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS'),
        (4, 'Pedro', 'Garcia', '09174567890', 'pedro.garcia@gmail.com', 'Bacong-Montilla', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS'),
        (5, 'Carmen', 'Lopez', '09175678901', 'carmen.lopez@yahoo.com', 'Bagroy', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS'),
        (6, 'Roberto', 'Martinez', '09176789012', 'roberto.martinez@gmail.com', 'Balingasag', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS'),
        (7, 'Elena', 'Rodriguez', '09177890123', 'elena.rodriguez@outlook.com', 'Binubuhan', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS'),
        (8, 'Miguel', 'Hernandez', '09178901234', 'miguel.hernandez@gmail.com', 'Busay', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS'),
        (9, 'Isabel', 'Gonzalez', '09179012345', 'isabel.gonzalez@yahoo.com', 'Calumangan', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS'),
        (10, 'Carlos', 'Perez', '09170123456', 'carlos.perez@gmail.com', 'Caridad', '$2y$10$9ytvFSOUOOLQRrrKBcXs3Occ3FWW.0XBy9mxYhHh8jfYakLeldLfS')
    ]
    
    client_query = """
    INSERT INTO client_table (client_id, first_name, last_name, contact, email, barangay, password)
    VALUES (%s, %s, %s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    contact = VALUES(contact),
    email = VALUES(email),
    barangay = VALUES(barangay),
    password = VALUES(password)
    """
    
    cursor.executemany(client_query, clients)
    print(f"✅ Inserted {len(clients)} clients")

def insert_driver_data(cursor):
    """Insert realistic driver data"""
    drivers = [
        (1, 'Angel', 'Adlaon', 'Malingin, Bago City', '09171234567', 35, 'Male', 'driver123', 1, 'NEG-123456'),
        (2, 'Ramon', 'Villanueva', 'Pacol, Bago City', '09172345678', 42, 'Male', 'driver123', 2, 'NEG-234567'),
        (3, 'Luz', 'Fernandez', 'Napoles, Bago City', '09173456789', 38, 'Female', 'driver123', 3, 'NEG-345678'),
        (4, 'Jose', 'Torres', 'Ma-ao, Bago City', '09174567890', 45, 'Male', 'driver123', 4, 'NEG-456789'),
        (5, 'Rosa', 'Mendoza', 'Mailum, Bago City', '09175678901', 33, 'Female', 'driver123', 5, 'NEG-567890')
    ]
    
    driver_query = """
    INSERT INTO driver_table (driver_id, first_name, last_name, address, contact, age, gender, password, location_id, license_no)
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    address = VALUES(address),
    contact = VALUES(contact),
    age = VALUES(age),
    gender = VALUES(gender),
    password = VALUES(password),
    location_id = VALUES(location_id),
    license_no = VALUES(license_no)
    """
    
    cursor.executemany(driver_query, drivers)
    print(f"✅ Inserted {len(drivers)} drivers")

def insert_monthly_waste_data(cursor):
    """Insert realistic monthly waste data"""
    waste_data = []
    
    for day in range(1, 16):  # First 15 days of January
        date = f'2025-01-{day:02d}'
        weight = round(random.uniform(1000, 1500), 2)
        status = 'collected'
        
        waste_data.append((date, weight, status))
    
    waste_query = """
    INSERT INTO monthly_waste_table (date, weight, status)
    VALUES (%s, %s, %s)
    ON DUPLICATE KEY UPDATE
    weight = VALUES(weight),
    status = VALUES(status)
    """
    
    cursor.executemany(waste_query, waste_data)
    print(f"✅ Inserted {len(waste_data)} monthly waste records")

def main():
    """Main function to insert all test data"""
    print("🚀 Starting realistic test data insertion...")
    print("=" * 50)
    
    # Connect to database
    connection = connect_to_database()
    if not connection:
        return
    
    cursor = connection.cursor()
    
    try:
        # Insert data in order (respecting foreign key constraints)
        print("\n📊 Inserting barangays data...")
        insert_barangays_data(cursor)
        
        print("\n📊 Inserting client data...")
        insert_client_data(cursor)
        
        print("\n📊 Inserting driver data...")
        insert_driver_data(cursor)
        
        print("\n📊 Generating sensor data (this may take a moment)...")
        generate_sensor_data(cursor, days=30)
        
        print("\n📊 Inserting monthly waste data...")
        insert_monthly_waste_data(cursor)
        
        # Commit all changes
        connection.commit()
        print("\n✅ All data inserted successfully!")
        
        # Display summary
        print("\n📈 Data Summary:")
        print("-" * 30)
        
        cursor.execute("SELECT COUNT(*) FROM barangays_table")
        print(f"Barangays: {cursor.fetchone()[0]}")
        
        cursor.execute("SELECT COUNT(*) FROM sensor")
        print(f"Sensor Readings: {cursor.fetchone()[0]}")
        
        cursor.execute("SELECT COUNT(*) FROM gps_location")
        print(f"GPS Locations: {cursor.fetchone()[0]}")
        
        cursor.execute("SELECT COUNT(*) FROM client_table")
        print(f"Clients: {cursor.fetchone()[0]}")
        
        cursor.execute("SELECT COUNT(*) FROM driver_table")
        print(f"Drivers: {cursor.fetchone()[0]}")
        
        cursor.execute("SELECT COUNT(*) FROM monthly_waste_table")
        print(f"Monthly Waste Records: {cursor.fetchone()[0]}")
        
    except mysql.connector.Error as err:
        print(f"❌ Error inserting data: {err}")
        connection.rollback()
    finally:
        cursor.close()
        connection.close()
        print("\n🔌 Database connection closed.")

if __name__ == "__main__":
    main()
