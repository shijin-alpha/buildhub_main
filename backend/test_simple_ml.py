#!/usr/bin/env python3
"""
Test the simple ML engine
"""

import json
import tempfile
import os
from ml_simple import SimpleMLEngine

def test_simple_ml():
    """Test the simple ML engine"""
    print("🧪 Testing Simple ML Engine...")
    
    # Initialize ML engine
    ml_engine = SimpleMLEngine()
    print("✅ ML Engine initialized!")
    
    # Test data
    test_data = {
        'plot_size': 2000,
        'budget': 3000000,
        'num_floors': 2
    }
    
    print("Testing validation...")
    validation_result = ml_engine.validate_form(test_data)
    print(f"✅ Validation result: {json.dumps(validation_result, indent=2)}")
    
    print("Testing suggestions...")
    suggestions = ml_engine.get_suggestions(test_data)
    print(f"✅ Suggestions: {json.dumps(suggestions, indent=2)}")
    
    print("Testing allowed options...")
    allowed_floors = ml_engine.get_allowed_options('num_floors', test_data)
    print(f"✅ Allowed floors: {allowed_floors}")
    
    print("🎉 Simple ML Engine test completed successfully!")

if __name__ == "__main__":
    test_simple_ml()









