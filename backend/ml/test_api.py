#!/usr/bin/env python3
"""
Test script for the risk prediction API
"""

import json
import tempfile
import os
from predict_risks_api import main
import sys

def test_api():
    """Test the API with sample data."""
    
    # Sample form data
    test_data = {
        'plot_size_sqft': 2500,
        'building_size_sqft': 2000,
        'num_floors': 2,
        'budget_amount': 2500000,
        'num_bedrooms': 3,
        'num_bathrooms': 2,
        'plot_shape': 'rectangular',
        'topography': 'flat',
        'design_style': 'modern',
        'special_features': 'Swimming pool, Garden',
        'custom_requirements': 'Smart home automation',
        'basement': False,
        'terrace': True,
        'parking': True
    }
    
    # Create temporary input file
    temp_input = tempfile.NamedTemporaryFile(mode='w', delete=False, suffix='.json')
    json.dump(test_data, temp_input)
    temp_input.close()
    
    try:
        # Simulate command line arguments
        original_argv = sys.argv
        sys.argv = ['predict_risks_api.py', temp_input.name]
        
        # Capture output
        from io import StringIO
        import contextlib
        
        output = StringIO()
        with contextlib.redirect_stdout(output):
            main()
        
        result = output.getvalue()
        print("API Response:")
        print(result)
        
        # Parse and validate result
        try:
            parsed = json.loads(result)
            if parsed.get('success'):
                print("\n✅ API Test Successful!")
                print(f"Cost Risk: {parsed['cost_overrun_risk']['risk_level']}")
                print(f"Time Risk: {parsed['time_delay_risk']['risk_level']}")
            else:
                print(f"\n❌ API Error: {parsed.get('error')}")
        except json.JSONDecodeError:
            print(f"\n❌ Invalid JSON response: {result}")
        
    finally:
        # Cleanup
        sys.argv = original_argv
        os.unlink(temp_input.name)

if __name__ == "__main__":
    test_api()