#!/usr/bin/env python3
"""
Test script for FastAPI ML Service
Tests both the service directly and through the PHP API
"""

import requests
import json
import time
from typing import Dict

# Test data
TEST_REQUEST = {
    "plot_size_sqft": 2000,
    "building_size_sqft": 1500,
    "num_floors": 2,
    "budget_amount": 5000000,
    "num_bedrooms": 3,
    "num_bathrooms": 2,
    "plot_shape": "rectangular",
    "topography": "flat",
    "design_style": "modern"
}

def test_health_check():
    """Test service health endpoint"""
    print("Testing health check...")
    try:
        response = requests.get('http://localhost:8000/health', timeout=5)
        if response.status_code == 200:
            data = response.json()
            print(f"✓ Service is healthy")
            print(f"  Models loaded: {data.get('models_loaded')}")
            print(f"  Model version: {data.get('model_version')}")
            return True
        else:
            print(f"✗ Health check failed: {response.status_code}")
            return False
    except requests.exceptions.ConnectionError:
        print("✗ Cannot connect to service. Is it running on port 8000?")
        return False
    except Exception as e:
        print(f"✗ Health check error: {e}")
        return False

def test_prediction():
    """Test prediction endpoint"""
    print("\nTesting prediction endpoint...")
    try:
        start_time = time.time()
        response = requests.post(
            'http://localhost:8000/predict',
            json=TEST_REQUEST,
            timeout=10
        )
        elapsed = (time.time() - start_time) * 1000  # Convert to ms
        
        if response.status_code == 200:
            data = response.json()
            print(f"✓ Prediction successful ({elapsed:.0f}ms)")
            
            # Validate response structure
            if 'cost_overrun_risk' in data and 'time_delay_risk' in data:
                cost_risk = data['cost_overrun_risk']
                time_risk = data['time_delay_risk']
                
                print(f"\n  Cost Overrun Risk:")
                print(f"    Risk Level: {cost_risk.get('risk_level')}")
                print(f"    Probability: {cost_risk.get('probabilities')}")
                
                print(f"\n  Time Delay Risk:")
                print(f"    Risk Level: {time_risk.get('risk_level')}")
                print(f"    Probability: {time_risk.get('probabilities')}")
                
                return True
            else:
                print("✗ Invalid response structure")
                print(f"  Response: {json.dumps(data, indent=2)}")
                return False
        else:
            print(f"✗ Prediction failed: {response.status_code}")
            print(f"  Response: {response.text}")
            return False
            
    except Exception as e:
        print(f"✗ Prediction error: {e}")
        return False

def test_performance():
    """Test prediction performance with multiple requests"""
    print("\nTesting performance (10 requests)...")
    try:
        times = []
        for i in range(10):
            start_time = time.time()
            response = requests.post(
                'http://localhost:8000/predict',
                json=TEST_REQUEST,
                timeout=10
            )
            elapsed = (time.time() - start_time) * 1000
            
            if response.status_code == 200:
                times.append(elapsed)
            else:
                print(f"✗ Request {i+1} failed")
                return False
        
        avg_time = sum(times) / len(times)
        min_time = min(times)
        max_time = max(times)
        
        print(f"✓ Performance test completed")
        print(f"  Average: {avg_time:.0f}ms")
        print(f"  Min: {min_time:.0f}ms")
        print(f"  Max: {max_time:.0f}ms")
        
        if avg_time < 200:
            print(f"  🚀 Excellent performance!")
        elif avg_time < 500:
            print(f"  ✓ Good performance")
        else:
            print(f"  ⚠ Performance could be better")
        
        return True
        
    except Exception as e:
        print(f"✗ Performance test error: {e}")
        return False

def test_validation():
    """Test input validation"""
    print("\nTesting input validation...")
    
    # Test missing required field
    invalid_request = TEST_REQUEST.copy()
    del invalid_request['budget_amount']
    
    try:
        response = requests.post(
            'http://localhost:8000/predict',
            json=invalid_request,
            timeout=10
        )
        
        if response.status_code == 422:  # Validation error
            print("✓ Input validation working correctly")
            return True
        else:
            print(f"✗ Expected validation error, got: {response.status_code}")
            return False
            
    except Exception as e:
        print(f"✗ Validation test error: {e}")
        return False

def main():
    """Run all tests"""
    print("=" * 60)
    print("FastAPI ML Service Test Suite")
    print("=" * 60)
    
    results = []
    
    # Run tests
    results.append(("Health Check", test_health_check()))
    
    if results[0][1]:  # Only continue if service is healthy
        results.append(("Prediction", test_prediction()))
        results.append(("Performance", test_performance()))
        results.append(("Validation", test_validation()))
    
    # Summary
    print("\n" + "=" * 60)
    print("Test Summary")
    print("=" * 60)
    
    passed = sum(1 for _, result in results if result)
    total = len(results)
    
    for test_name, result in results:
        status = "✓ PASS" if result else "✗ FAIL"
        print(f"{status}: {test_name}")
    
    print(f"\nTotal: {passed}/{total} tests passed")
    
    if passed == total:
        print("\n🎉 All tests passed! Service is working correctly.")
        return 0
    else:
        print("\n⚠ Some tests failed. Check the output above.")
        return 1

if __name__ == "__main__":
    exit(main())
