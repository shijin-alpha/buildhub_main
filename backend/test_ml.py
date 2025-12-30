#!/usr/bin/env python3
"""
Test script for BuildHub ML Rules Engine
"""

import sys
import os

# Add current directory to path
sys.path.append(os.path.dirname(__file__))

try:
    print("Testing BuildHub ML Rules Engine...")
    
    # Import the ML engine
    from ml_rules_engine import BuildHubRulesEngine
    
    print("✅ ML Rules Engine imported successfully!")
    
    # Initialize the engine
    print("Initializing ML Rules Engine...")
    rules_engine = BuildHubRulesEngine()
    print("✅ ML Rules Engine initialized!")
    
    # Test data
    test_data = {
        'plot_size': 2000,
        'budget': 3000000,
        'num_floors': 2
    }
    
    print("Testing validation...")
    validation_result = rules_engine.validate_form(test_data)
    print(f"✅ Validation result: {validation_result}")
    
    print("Testing suggestions...")
    suggestions = rules_engine.get_suggestions(test_data)
    print(f"✅ Suggestions: {suggestions}")
    
    print("Testing allowed options...")
    allowed_floors = rules_engine.get_allowed_options('num_floors', test_data)
    print(f"✅ Allowed floors: {allowed_floors}")
    
    print("🎉 All tests passed! ML Rules Engine is working correctly!")
    
except Exception as e:
    print(f"❌ Error: {e}")
    import traceback
    traceback.print_exc()









