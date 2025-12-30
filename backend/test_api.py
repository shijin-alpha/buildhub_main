#!/usr/bin/env python3
"""
Test script for Architect Recommendation API
Run this to verify the API is working correctly
"""

import requests
import json

def test_api():
    """Test the architect recommendation API"""
    
    # API endpoint
    url = "http://localhost:5001/api/architect-recommendations"
    
    # Test data with valid values
    test_data = {
        "budget_range": "30-50 Lakhs",
        "plot_size": "10",
        "plot_unit": "cents",
        "building_size": "2000",
        "num_floors": "2",
        "aesthetic": "Modern",
        "rooms": ["master_bedroom", "bedrooms", "kitchen", "living_room"],
        "location": "Kerala",
        "num_recommendations": 3
    }
    
    # Test data with empty values (should fail)
    empty_test_data = {
        "budget_range": "",
        "plot_size": "",
        "plot_unit": "cents",
        "building_size": "",
        "num_floors": "1",
        "aesthetic": "",
        "rooms": [],
        "location": "",
        "num_recommendations": 3
    }
    
    print("🧪 Testing Architect Recommendation API...")
    print(f"📡 URL: {url}")
    print(f"📊 Test Data: {json.dumps(test_data, indent=2)}")
    print("-" * 50)
    
    try:
        # Test CORS preflight
        print("🔄 Testing CORS preflight...")
        preflight_response = requests.options(url)
        print(f"✅ Preflight Status: {preflight_response.status_code}")
        print(f"✅ CORS Headers: {dict(preflight_response.headers)}")
        print()
        
        # Test actual request
        print("🚀 Testing recommendation request...")
        response = requests.post(
            url,
            json=test_data,
            headers={'Content-Type': 'application/json'},
            timeout=30
        )
        
        print(f"📈 Response Status: {response.status_code}")
        print(f"📋 Response Headers: {dict(response.headers)}")
        
        if response.status_code == 200:
            result = response.json()
            print("✅ SUCCESS!")
            print(f"📊 Recommendations Found: {result.get('total_found', 0)}")
            print(f"🎯 Project Summary: {result.get('project_summary', {})}")
            
            if result.get('recommendations'):
                print("\n🏆 Top Recommendations:")
                for i, rec in enumerate(result['recommendations'][:3], 1):
                    print(f"  {i}. {rec.get('name', 'Unknown')} - {rec.get('specialty', 'N/A')} - Score: {rec.get('similarity_score', 0):.2f}")
        else:
            print("❌ FAILED!")
            print(f"Error: {response.text}")
            
    except requests.exceptions.ConnectionError:
        print("❌ CONNECTION ERROR!")
        print("🔧 Make sure the Flask API is running on port 5001")
        print("💡 Run: python architect_recommendation_api.py")
        
    except requests.exceptions.Timeout:
        print("❌ TIMEOUT ERROR!")
        print("⏰ The API took too long to respond")
        
    except Exception as e:
        print(f"❌ UNEXPECTED ERROR: {e}")

def test_empty_data():
    """Test API with empty data (should return 400)"""
    url = "http://localhost:5001/api/architect-recommendations"
    
    empty_test_data = {
        "budget_range": "",
        "plot_size": "",
        "plot_unit": "cents",
        "building_size": "",
        "num_floors": "1",
        "aesthetic": "",
        "rooms": [],
        "location": "",
        "num_recommendations": 3
    }
    
    print("\n🧪 Testing API with Empty Data (should fail)...")
    print(f"📊 Empty Data: {json.dumps(empty_test_data, indent=2)}")
    
    try:
        response = requests.post(
            url,
            json=empty_test_data,
            headers={'Content-Type': 'application/json'},
            timeout=30
        )
        
        print(f"📈 Response Status: {response.status_code}")
        
        if response.status_code == 400:
            result = response.json()
            print("✅ CORRECTLY REJECTED EMPTY DATA!")
            print(f"📋 Error: {result.get('error', 'Unknown error')}")
            print(f"📋 Missing Fields: {result.get('missing_fields', [])}")
        else:
            print("❌ UNEXPECTED SUCCESS!")
            print(f"Response: {response.text}")
            
    except Exception as e:
        print(f"❌ ERROR: {e}")

def test_health():
    """Test the health check endpoint"""
    url = "http://localhost:5001/api/architect-recommendations/health"
    
    print("\n🏥 Testing Health Check...")
    try:
        response = requests.get(url, timeout=10)
        print(f"📈 Health Status: {response.status_code}")
        
        if response.status_code == 200:
            result = response.json()
            print(f"✅ Health: {result.get('status', 'unknown')}")
            print(f"🤖 Model Trained: {result.get('model_trained', False)}")
        else:
            print(f"❌ Health Check Failed: {response.text}")
            
    except Exception as e:
        print(f"❌ Health Check Error: {e}")

def check_database():
    """Check if architects exist in database"""
    print("\n🗄️ Checking Database for Architects...")
    try:
        import subprocess
        result = subprocess.run(['python', 'check_architects.py'], 
                              capture_output=True, text=True, timeout=30)
        print(result.stdout)
        if result.stderr:
            print(f"⚠️ Warnings: {result.stderr}")
    except Exception as e:
        print(f"❌ Database check error: {e}")

if __name__ == "__main__":
    print("🏗️ Architect Recommendation API Test Suite")
    print("=" * 50)
    
    check_database()
    test_health()
    test_api()
    test_empty_data()
    
    print("\n" + "=" * 50)
    print("🏁 Test Complete!")
