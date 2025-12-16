#!/usr/bin/env python3
"""
Test script for the Health Risk Classification Flask API
This script tests the Flask API endpoints to ensure they work correctly
"""

import requests
import json
import sys

def test_health_endpoint(base_url):
    """Test the health check endpoint"""
    print("Testing health endpoint...")
    try:
        response = requests.get(f"{base_url}/health", timeout=10)
        if response.status_code == 200:
            data = response.json()
            print(f"✓ Health check passed: {data['status']}")
            return True
        else:
            print(f"✗ Health check failed: HTTP {response.status_code}")
            return False
    except Exception as e:
        print(f"✗ Health check failed: {e}")
        return False

def test_predict_endpoint(base_url):
    """Test the prediction endpoint"""
    print("Testing prediction endpoint...")
    
    # Sample test data
    test_data = {
        "lookback_days": 14,
        "barangay_data": {
            "brgy_1": {
                "barangay": "High Risk Barangay",
                "latitude": 10.0,
                "longitude": 122.0,
                "daily_counts": {
                    "2024-01-01": 50,
                    "2024-01-02": 60,
                    "2024-01-03": 55,
                    "2024-01-04": 45,
                    "2024-01-05": 70,
                    "2024-01-06": 65,
                    "2024-01-07": 58,
                    "2024-01-08": 62,
                    "2024-01-09": 55,
                    "2024-01-10": 48,
                    "2024-01-11": 68,
                    "2024-01-12": 72,
                    "2024-01-13": 60,
                    "2024-01-14": 55
                }
            },
            "brgy_2": {
                "barangay": "Low Risk Barangay",
                "latitude": 10.1,
                "longitude": 122.1,
                "daily_counts": {
                    "2024-01-01": 5,
                    "2024-01-02": 8,
                    "2024-01-03": 6,
                    "2024-01-04": 4,
                    "2024-01-05": 10,
                    "2024-01-06": 9,
                    "2024-01-07": 7,
                    "2024-01-08": 8,
                    "2024-01-09": 6,
                    "2024-01-10": 5,
                    "2024-01-11": 8,
                    "2024-01-12": 9,
                    "2024-01-13": 7,
                    "2024-01-14": 6
                }
            },
            "brgy_3": {
                "barangay": "Medium Risk Barangay",
                "latitude": 10.2,
                "longitude": 122.2,
                "daily_counts": {
                    "2024-01-01": 15,
                    "2024-01-02": 20,
                    "2024-01-03": 18,
                    "2024-01-04": 12,
                    "2024-01-05": 25,
                    "2024-01-06": 22,
                    "2024-01-07": 19,
                    "2024-01-08": 21,
                    "2024-01-09": 17,
                    "2024-01-10": 14,
                    "2024-01-11": 23,
                    "2024-01-12": 24,
                    "2024-01-13": 20,
                    "2024-01-14": 18
                }
            }
        }
    }
    
    try:
        response = requests.post(
            f"{base_url}/predict",
            json=test_data,
            headers={'Content-Type': 'application/json'},
            timeout=30
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get('success'):
                print("✓ Prediction endpoint passed")
                print(f"  - Barangays processed: {len(data['predictions'])}")
                print(f"  - Model used: {data['model_info']['algorithm']}")
                print(f"  - Training success: {data['model_info'].get('training_success', 'N/A')}")
                
                # Print predictions for each barangay
                for prediction in data['predictions']:
                    print(f"  - {prediction['barangay']}:")
                    print(f"    * Risk Level: {prediction['predicted_risk']}")
                    print(f"    * Predicted Tons: {prediction['predicted_tons']}")
                    print(f"    * Confidence: {prediction['confidence']}%")
                    print(f"    * Probabilities: {prediction['probabilities']}")
                
                return True
            else:
                print(f"✗ Prediction endpoint failed: {data.get('error', 'Unknown error')}")
                return False
        else:
            print(f"✗ Prediction endpoint failed: HTTP {response.status_code}")
            print(f"  Response: {response.text}")
            return False
    except Exception as e:
        print(f"✗ Prediction endpoint failed: {e}")
        return False

def test_documentation_endpoint(base_url):
    """Test the documentation endpoint"""
    print("Testing documentation endpoint...")
    try:
        response = requests.get(f"{base_url}/predict", timeout=10)
        if response.status_code == 200:
            data = response.json()
            if 'endpoints' in data:
                print("✓ Documentation endpoint passed")
                return True
            else:
                print("✗ Documentation endpoint failed: Invalid response format")
                return False
        else:
            print(f"✗ Documentation endpoint failed: HTTP {response.status_code}")
            return False
    except Exception as e:
        print(f"✗ Documentation endpoint failed: {e}")
        return False

def main():
    if len(sys.argv) != 2:
        print("Usage: python test_health_flask_api.py <base_url>")
        print("Example: python test_health_flask_api.py http://localhost:5001")
        sys.exit(1)
    
    base_url = sys.argv[1].rstrip('/')
    
    print(f"Testing Health Risk Classification Flask API at: {base_url}")
    print("=" * 60)
    
    tests = [
        test_health_endpoint,
        test_documentation_endpoint,
        test_predict_endpoint
    ]
    
    passed = 0
    total = len(tests)
    
    for test in tests:
        if test(base_url):
            passed += 1
        print()
    
    print("=" * 60)
    print(f"Test Results: {passed}/{total} tests passed")
    
    if passed == total:
        print("✓ All tests passed! The Health Risk Classification Flask API is working correctly.")
        sys.exit(0)
    else:
        print("✗ Some tests failed. Please check the API configuration.")
        sys.exit(1)

if __name__ == "__main__":
    main()
