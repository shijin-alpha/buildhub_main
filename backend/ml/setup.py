"""
Setup script for Construction Time Prediction Model
"""

import os
import sys

def check_dependencies():
    """Check if required packages are installed"""
    print("Checking dependencies...")
    
    required = ['numpy', 'sklearn']
    missing = []
    
    for package in required:
        try:
            __import__(package)
            print(f"✓ {package} installed")
        except ImportError:
            print(f"✗ {package} NOT installed")
            missing.append(package)
    
    if missing:
        print("\nPlease install missing packages:")
        print(f"pip install {' '.join(missing)}")
        return False
    
    print("\nAll dependencies are installed!")
    return True

def train_model():
    """Train the BPNN model"""
    print("\nTraining Backpropagation Neural Network...")
    print("=" * 50)
    
    try:
        from construction_time_predictor import ConstructionTimePredictor
        
        predictor = ConstructionTimePredictor()
        predictor.train_model()
        
        print("\n" + "=" * 50)
        print("Setup completed successfully!")
        print("Model saved to: backend/ml/construction_model.pkl")
        print("=" * 50)
        
        return True
    
    except Exception as e:
        print(f"\nError during training: {e}")
        return False

def test_model():
    """Test the trained model"""
    print("\nTesting model...")
    
    try:
        from construction_time_predictor import ConstructionTimePredictor
        
        predictor = ConstructionTimePredictor()
        
        if not predictor.load_model():
            print("Model not found. Please train the model first.")
            return False
        
        # Test with sample data
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
        print(f"✓ Test prediction: {predicted_time:.2f} months")
        print(f"✓ Model is working correctly!")
        
        return True
    
    except Exception as e:
        print(f"✗ Test failed: {e}")
        return False

def main():
    """Main setup function"""
    print("=" * 50)
    print("Construction Time Prediction Setup")
    print("=" * 50)
    
    # Check dependencies
    if not check_dependencies():
        print("\nPlease install missing dependencies and run again.")
        sys.exit(1)
    
    # Check if model exists
    model_path = 'construction_model.pkl'
    if os.path.exists(model_path):
        print(f"\nModel found at: {model_path}")
        response = input("Do you want to retrain? (y/n): ")
        if response.lower() != 'y':
            if test_model():
                print("\n✓ Setup completed successfully!")
                sys.exit(0)
            else:
                print("\nModel test failed. Retraining...")
    
    # Train model
    if not train_model():
        print("\n✗ Setup failed!")
        sys.exit(1)
    
    # Test model
    if test_model():
        print("\n✓ Setup completed successfully!")
    else:
        print("\n⚠ Setup completed but model test failed.")
        print("You can still use the model, but please check for errors.")

if __name__ == "__main__":
    main()


