#!/usr/bin/env python3
"""
BuildHub ML Rules Engine Setup Script
Installs dependencies and tests the ML integration
"""

import subprocess
import sys
import os
import json

def install_requirements():
    """Install Python requirements"""
    print("Installing Python ML dependencies...")
    try:
        subprocess.check_call([sys.executable, "-m", "pip", "install", "-r", "requirements.txt"])
        print("✅ Dependencies installed successfully!")
        return True
    except subprocess.CalledProcessError as e:
        print(f"❌ Error installing dependencies: {e}")
        return False

def test_ml_engine():
    """Test the ML rules engine"""
    print("\nTesting ML Rules Engine...")
    try:
        from ml_rules_engine import BuildHubRulesEngine
        
        # Initialize the engine
        print("Initializing ML Rules Engine...")
        rules_engine = BuildHubRulesEngine()
        
        # Test data
        test_data = {
            'plot_size': 2000,
            'budget': 3000000,
            'num_floors': 2
        }
        
        print("Testing validation...")
        validation_result = rules_engine.validate_form(test_data)
        print(f"Validation result: {json.dumps(validation_result, indent=2)}")
        
        print("Testing suggestions...")
        suggestions = rules_engine.get_suggestions(test_data)
        print(f"Suggestions: {json.dumps(suggestions, indent=2)}")
        
        print("Testing allowed options...")
        allowed_floors = rules_engine.get_allowed_options('num_floors', test_data)
        print(f"Allowed floors: {allowed_floors}")
        
        print("✅ ML Rules Engine test completed successfully!")
        return True
        
    except Exception as e:
        print(f"❌ Error testing ML engine: {e}")
        return False

def test_api_integration():
    """Test the API integration"""
    print("\nTesting API integration...")
    try:
        from ml_api import BuildHubMLAPI
        
        # Initialize the API
        api = BuildHubMLAPI()
        
        # Test data
        test_data = {
            'plot_size': 2000,
            'budget': 3000000,
            'num_floors': 2
        }
        
        print("Testing API validation...")
        result = api.validate_form(test_data)
        print(f"API validation result: {json.dumps(result, indent=2)}")
        
        print("✅ API integration test completed successfully!")
        return True
        
    except Exception as e:
        print(f"❌ Error testing API integration: {e}")
        return False

def create_models_directory():
    """Create models directory"""
    models_dir = os.path.join(os.path.dirname(__file__), 'models')
    if not os.path.exists(models_dir):
        os.makedirs(models_dir)
        print(f"✅ Created models directory: {models_dir}")
    else:
        print(f"✅ Models directory already exists: {models_dir}")

def main():
    """Main setup function"""
    print("🚀 BuildHub ML Rules Engine Setup")
    print("=" * 50)
    
    # Create models directory
    create_models_directory()
    
    # Install requirements
    if not install_requirements():
        print("❌ Setup failed at dependency installation")
        return False
    
    # Test ML engine
    if not test_ml_engine():
        print("❌ Setup failed at ML engine test")
        return False
    
    # Test API integration
    if not test_api_integration():
        print("❌ Setup failed at API integration test")
        return False
    
    print("\n🎉 BuildHub ML Rules Engine setup completed successfully!")
    print("\nNext steps:")
    print("1. The Python ML models are ready to use")
    print("2. The PHP API endpoints are configured")
    print("3. The frontend will automatically use the ML-powered rules engine")
    print("4. Real-time validation and suggestions are now powered by decision trees!")
    
    return True

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)









