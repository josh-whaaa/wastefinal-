#!/usr/bin/env python3
"""
Setup script for Health Risk Classification Model
Installs required dependencies and tests the ML model
"""

import subprocess
import sys
import os
import json

def install_requirements():
    """Install required Python packages"""
    print("Installing Python dependencies...")
    try:
        subprocess.check_call([sys.executable, "-m", "pip", "install", "-r", "requirements.txt"])
        print("✅ Dependencies installed successfully!")
        return True
    except subprocess.CalledProcessError as e:
        print(f"❌ Error installing dependencies: {e}")
        return False

def test_ml_model():
    """Test the ML model with sample data"""
    print("\nTesting ML model...")
    
    # Sample test data
    test_data = {
        "lookback_days": 14,
        "high_tons": 3.0,
        "med_tons": 1.0,
        "barangay_data": {
            "1": {
                "brgy_id": 1,
                "barangay": "Test Barangay",
                "latitude": 10.5379,
                "longitude": 122.8333,
                "daily_counts": {
                    "2024-01-01": 1000,
                    "2024-01-02": 1200,
                    "2024-01-03": 800,
                    "2024-01-04": 1500,
                    "2024-01-05": 1100,
                    "2024-01-06": 1300,
                    "2024-01-07": 900,
                    "2024-01-08": 1400,
                    "2024-01-09": 1000,
                    "2024-01-10": 1200,
                    "2024-01-11": 1600,
                    "2024-01-12": 1100,
                    "2024-01-13": 1300,
                    "2024-01-14": 1000
                }
            }
        }
    }
    
    try:
        # Test the ML classifier
        result = subprocess.run(
            [sys.executable, "ml_health_risk_classifier.py"],
            input=json.dumps(test_data),
            text=True,
            capture_output=True
        )
        
        if result.returncode == 0:
            output = json.loads(result.stdout)
            if output.get('success'):
                print("✅ ML model test successful!")
                print(f"   - Model type: {output['model_info']['type']}")
                print(f"   - Features: {len(output['model_info']['features'])}")
                print(f"   - Predictions: {len(output['predictions'])}")
                return True
            else:
                print(f"❌ ML model test failed: {output.get('error')}")
                return False
        else:
            print(f"❌ ML model test failed with return code: {result.returncode}")
            print(f"   Error: {result.stderr}")
            return False
            
    except Exception as e:
        print(f"❌ Error testing ML model: {e}")
        return False

def check_python_version():
    """Check if Python version is compatible"""
    print(f"Python version: {sys.version}")
    if sys.version_info < (3, 7):
        print("❌ Python 3.7 or higher is required!")
        return False
    print("✅ Python version is compatible!")
    return True

def main():
    """Main setup function"""
    print("🚀 Setting up Health Risk Classification Model")
    print("=" * 50)
    
    # Check Python version
    if not check_python_version():
        sys.exit(1)
    
    # Install dependencies
    if not install_requirements():
        print("\n⚠️  Dependencies installation failed. You may need to install them manually:")
        print("   pip install -r requirements.txt")
        sys.exit(1)
    
    # Test ML model
    if not test_ml_model():
        print("\n⚠️  ML model test failed. Check the error messages above.")
        sys.exit(1)
    
    print("\n🎉 Setup completed successfully!")
    print("\nNext steps:")
    print("1. The ML model is ready to use")
    print("2. Access the health risk map in your admin panel")
    print("3. The system will automatically use Python ML when available")
    print("4. If Python is not available, it will fallback to PHP-based predictions")

if __name__ == "__main__":
    main()
