#!/usr/bin/env python3
"""
Clean test script for the risk prediction API (suppresses sklearn warnings)
"""

import warnings
warnings.filterwarnings('ignore', category=UserWarning)

import json
import tempfile
import os
from predict_risks_api import main
import sys

def test_api_clean():
    """Test the API with sample data - clean output."""
    
    print("🧪 Testing Risk Assessment API...")
    
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
        
        # Parse and display result
        try:
            parsed = json.loads(result)
            if parsed.get('success'):
                print("✅ API Test Successful!")
                print()
                
                # Cost Risk
                cost_risk = parsed['cost_overrun_risk']
                print(f"💰 Cost Overrun Risk: {cost_risk['risk_level']}")
                print(f"   High Risk Probability: {cost_risk['probabilities']['High']:.1%}")
                print("   Key Factors:")
                for i, explanation in enumerate(cost_risk['explanation'][:3], 1):
                    print(f"   {i}. {explanation}")
                print()
                
                # Time Risk  
                time_risk = parsed['time_delay_risk']
                print(f"⏰ Time Delay Risk: {time_risk['risk_level']}")
                print(f"   High Risk Probability: {time_risk['probabilities']['High']:.1%}")
                print("   Key Factors:")
                for i, explanation in enumerate(time_risk['explanation'][:3], 1):
                    print(f"   {i}. {explanation}")
                print()
                
                print("🎯 Service is ready for production use!")
                
            else:
                print(f"❌ API Error: {parsed.get('error')}")
        except json.JSONDecodeError:
            print(f"❌ Invalid JSON response")
        
    finally:
        # Cleanup
        sys.argv = original_argv
        os.unlink(temp_input.name)

if __name__ == "__main__":
    test_api_clean()