#!/usr/bin/env python3
"""
Test script for Design Suggestion Classification
"""

import sys
import os
import json
import tempfile

# Add current directory to path
sys.path.append(os.path.dirname(__file__))

from ml_simple import SimpleMLEngine

def test_design_classification():
    """Test the design classification functionality"""
    
    print("=" * 80)
    print("Testing Design Suggestion Classification")
    print("=" * 80)
    
    # Initialize ML engine
    ml_engine = SimpleMLEngine()
    
    # Test Case 1: Small plot, low budget, simple requirements
    print("\n" + "=" * 80)
    print("TEST 1: Small Plot, Low Budget (Should suggest pre-designed)")
    print("=" * 80)
    test_data_1 = {
        'plot_size': 1500,
        'plot_unit': 'cents',
        'budget': 1500000,
        'num_floors': '1',
        'family_needs': [],
        'aesthetic': ''
    }
    
    result_1 = ml_engine.classify_design_suggestion(test_data_1)
    print(json.dumps(result_1, indent=2))
    
    # Test Case 2: Large plot, high budget, custom requirements
    print("\n" + "=" * 80)
    print("TEST 2: Large Plot, High Budget, Custom Requirements")
    print("=" * 80)
    test_data_2 = {
        'plot_size': 50,
        'plot_unit': 'cents',
        'budget': 10000000,
        'num_floors': '3',
        'family_needs': ['Large Family', 'Home Office', 'Elderly Care'],
        'aesthetic': 'Modern'
    }
    
    result_2 = ml_engine.classify_design_suggestion(test_data_2)
    print(json.dumps(result_2, indent=2))
    
    # Test Case 3: Medium plot, premium budget
    print("\n" + "=" * 80)
    print("TEST 3: Medium Plot, Premium Budget")
    print("=" * 80)
    test_data_3 = {
        'plot_size': 10,
        'plot_unit': 'cents',
        'budget': 7500000,
        'num_floors': '2',
        'family_needs': ['Family with Children'],
        'aesthetic': 'Luxury'
    }
    
    result_3 = ml_engine.classify_design_suggestion(test_data_3)
    print(json.dumps(result_3, indent=2))
    
    # Test Case 4: Very large plot, luxury budget
    print("\n" + "=" * 80)
    print("TEST 4: Very Large Plot, Luxury Budget")
    print("=" * 80)
    test_data_4 = {
        'plot_size': 100,
        'plot_unit': 'cents',
        'budget': 25000000,
        'num_floors': '4',
        'family_needs': ['Extended Family', 'Multiple Vehicles', 'Home Office'],
        'aesthetic': 'European'
    }
    
    result_4 = ml_engine.classify_design_suggestion(test_data_4)
    print(json.dumps(result_4, indent=2))
    
    # Test Case 5: Using ml_simple.py as a script
    print("\n" + "=" * 80)
    print("TEST 5: Testing via ml_simple.py script interface")
    print("=" * 80)
    
    # Create temporary input file
    input_data = {
        'action': 'classify_design',
        'plot_size': 30,
        'plot_unit': 'cents',
        'budget': 8000000,
        'num_floors': '2',
        'family_needs': ['Modern Living'],
        'aesthetic': 'Contemporary'
    }
    
    with tempfile.NamedTemporaryFile(mode='w', suffix='.json', delete=False) as f:
        json.dump(input_data, f)
        input_file = f.name
    
    output_file = input_file.replace('_input.json', '_output.json')
    
    try:
        # Call the script
        import subprocess
        result = subprocess.run(
            ['python', 'ml_simple.py', input_file, output_file],
            capture_output=True,
            text=True
        )
        
        if os.path.exists(output_file):
            with open(output_file, 'r') as f:
                script_result = json.load(f)
            print(json.dumps(script_result, indent=2))
        else:
            print("No output file generated")
            print("STDOUT:", result.stdout)
            print("STDERR:", result.stderr)
    finally:
        # Cleanup
        if os.path.exists(input_file):
            os.unlink(input_file)
        if os.path.exists(output_file):
            os.unlink(output_file)
    
    print("\n" + "=" * 80)
    print("✅ All tests completed!")
    print("=" * 80)

if __name__ == "__main__":
    test_design_classification()




