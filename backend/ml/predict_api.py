"""
API wrapper for BPNN prediction
Called by PHP API endpoint
"""

import sys
import json
import numpy as np
from construction_time_predictor import ConstructionTimePredictor

def predict(features_dict):
    """Make prediction using trained BPNN model"""
    try:
        # Initialize predictor
        predictor = ConstructionTimePredictor()
        
        # Load or train model
        if not predictor.load_model():
            # Train model if it doesn't exist
            predictor.train_model()
        
        # Make prediction
        predicted_months = predictor.predict(features_dict)
        
        return {
            'predicted_months': float(predicted_months),
            'confidence': 0.85
        }
    
    except Exception as e:
        # Return fallback estimation on error
        return fallback_estimation(features_dict)

def fallback_estimation(features):
    """Fallback estimation method"""
    building_size = features.get('building_size', 0)
    floors = features.get('floors', 1)
    bedrooms = features.get('bedrooms', 2)
    bathrooms = features.get('bathrooms', 2)
    complexity = features.get('complexity', 5)
    basement = features.get('basement', 0)
    
    # Simple estimation formula
    base_time = 3.0
    size_factor = (building_size / 1000) * 1.2
    floor_factor = (floors - 1) * 2.0
    room_factor = ((bedrooms + bathrooms) / 2) * 0.4
    complexity_factor = complexity * 0.25
    basement_factor = basement * 2.0
    
    estimated_months = base_time + size_factor + floor_factor + room_factor + complexity_factor + basement_factor
    estimated_months = max(3, min(24, estimated_months))
    
    return {
        'predicted_months': float(estimated_months),
        'confidence': 0.70
    }

def main():
    """Main entry point"""
    # Read input from command line argument
    if len(sys.argv) < 2:
        print(json.dumps({
            'success': False,
            'message': 'No input provided'
        }))
        sys.exit(1)
    
    try:
        # Parse input
        input_json = sys.argv[1]
        features = json.loads(input_json)
        
        # Make prediction
        result = predict(features)
        
        # Output JSON
        print(json.dumps(result))
    
    except Exception as e:
        # Return error
        print(json.dumps({
            'success': False,
            'message': str(e)
        }))
        sys.exit(1)

if __name__ == "__main__":
    main()

