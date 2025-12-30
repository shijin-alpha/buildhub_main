"""
Quick training script for Construction Time Prediction Model
Run this to train the BPNN model
"""

from construction_time_predictor import ConstructionTimePredictor

if __name__ == "__main__":
    print("=" * 60)
    print("Training Backpropagation Neural Network")
    print("=" * 60)
    print()
    
    predictor = ConstructionTimePredictor()
    predictor.train_model()
    
    print()
    print("=" * 60)
    print("Training Complete!")
    print("=" * 60)
    
    # Test the model
    print("\nTesting with sample project...")
    test_features = {
        'plot_size': 2800,
        'building_size': 2800,
        'floors': 2,
        'bedrooms': 3,
        'bathrooms': 2,
        'kitchen_rooms': 1,
        'parking': 2,
        'terrace': 1,
        'basement': 0,
        'complexity': 6
    }
    
    predicted_time = predictor.predict(test_features)
    
    print(f"\nSample Project Features:")
    for key, value in test_features.items():
        print(f"  {key}: {value}")
    
    print(f"\n✓ Predicted Completion Time: {predicted_time:.1f} months")
    print("\nModel is ready to use!")

