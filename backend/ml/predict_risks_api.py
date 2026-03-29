#!/usr/bin/env python3
"""
Risk Prediction API Script

This script is called by the PHP API to make risk predictions.
It loads the trained models and returns predictions in JSON format.
"""

import sys
import json
import os
from risk_predictor import get_predictor

def main():
    try:
        # Check if input file is provided
        if len(sys.argv) != 2:
            raise Exception("Usage: python predict_risks_api.py <input_file>")
        
        input_file = sys.argv[1]
        
        # Read input data
        if not os.path.exists(input_file):
            raise Exception(f"Input file not found: {input_file}")
        
        with open(input_file, 'r') as f:
            form_data = json.load(f)
        
        # Get predictor instance
        predictor = get_predictor()
        
        # Make prediction
        result = predictor.predict_risks(form_data)
        
        # Output result as JSON
        print(json.dumps(result))
        
    except Exception as e:
        # Output error as JSON
        error_result = {
            'error': str(e)
        }
        print(json.dumps(error_result))
        sys.exit(1)

if __name__ == "__main__":
    main()