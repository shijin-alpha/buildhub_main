#!/usr/bin/env python3
"""
Collaborative AI Pipeline Setup Script
Helps users set up the enhanced Room Improvement Assistant with Gemini integration.
"""

import os
import sys
import subprocess
import shutil
from pathlib import Path

def print_header():
    print("🤖 Collaborative AI Pipeline Setup")
    print("=" * 50)
    print("Setting up enhanced Room Improvement Assistant")
    print("with Gemini + Diffusion integration")
    print()

def check_python_version():
    """Check if Python version is compatible"""
    print("🐍 Checking Python version...")
    
    if sys.version_info < (3, 9):
        print("❌ Python 3.9+ is required")
        print(f"   Current version: {sys.version}")
        return False
    
    print(f"✅ Python {sys.version_info.major}.{sys.version_info.minor} detected")
    return True

def check_dependencies():
    """Check if required system dependencies are available"""
    print("\n🔧 Checking system dependencies...")
    
    dependencies = {
        'pip': 'pip --version',
        'git': 'git --version'
    }
    
    missing = []
    for dep, cmd in dependencies.items():
        try:
            subprocess.run(cmd.split(), capture_output=True, check=True)
            print(f"✅ {dep} is available")
        except (subprocess.CalledProcessError, FileNotFoundError):
            print(f"❌ {dep} is not available")
            missing.append(dep)
    
    if missing:
        print(f"\n❌ Missing dependencies: {', '.join(missing)}")
        print("Please install them and run this script again.")
        return False
    
    return True

def setup_environment():
    """Set up the AI service environment"""
    print("\n🌍 Setting up environment...")
    
    ai_service_dir = Path("ai_service")
    if not ai_service_dir.exists():
        print("❌ ai_service directory not found")
        print("Please run this script from the project root directory")
        return False
    
    # Create .env file if it doesn't exist
    env_file = ai_service_dir / ".env"
    env_example = ai_service_dir / ".env.example"
    
    if not env_file.exists() and env_example.exists():
        shutil.copy(env_example, env_file)
        print("✅ Created .env file from template")
        print("⚠️  Please edit ai_service/.env and add your GEMINI_API_KEY")
    elif env_file.exists():
        print("✅ .env file already exists")
    else:
        print("❌ .env.example not found")
        return False
    
    return True

def install_python_dependencies():
    """Install Python dependencies for the AI service"""
    print("\n📦 Installing Python dependencies...")
    
    requirements_file = Path("ai_service/requirements.txt")
    if not requirements_file.exists():
        print("❌ requirements.txt not found")
        return False
    
    try:
        # Install dependencies
        cmd = [sys.executable, "-m", "pip", "install", "-r", str(requirements_file)]
        print(f"Running: {' '.join(cmd)}")
        
        result = subprocess.run(cmd, capture_output=True, text=True)
        
        if result.returncode == 0:
            print("✅ Python dependencies installed successfully")
            return True
        else:
            print("❌ Failed to install Python dependencies")
            print(f"Error: {result.stderr}")
            return False
            
    except Exception as e:
        print(f"❌ Error installing dependencies: {e}")
        return False

def check_gemini_api_key():
    """Check if Gemini API key is configured"""
    print("\n🔑 Checking Gemini API key configuration...")
    
    env_file = Path("ai_service/.env")
    if not env_file.exists():
        print("❌ .env file not found")
        return False
    
    try:
        with open(env_file, 'r') as f:
            content = f.read()
        
        if "GEMINI_API_KEY=your_gemini_api_key_here" in content:
            print("⚠️  Gemini API key not configured")
            print("   Please edit ai_service/.env and add your actual API key")
            print("   Get your key from: https://makersuite.google.com/app/apikey")
            return False
        elif "GEMINI_API_KEY=" in content and len(content.split("GEMINI_API_KEY=")[1].split('\n')[0].strip()) > 10:
            print("✅ Gemini API key appears to be configured")
            return True
        else:
            print("⚠️  Gemini API key may not be properly configured")
            print("   Please check ai_service/.env file")
            return False
            
    except Exception as e:
        print(f"❌ Error checking API key: {e}")
        return False

def test_ai_service():
    """Test if the AI service can start"""
    print("\n🧪 Testing AI service startup...")
    
    try:
        # Try to import the main modules
        sys.path.insert(0, str(Path("ai_service").absolute()))
        
        from modules.object_detector import ObjectDetector
        from modules.conceptual_generator import ConceptualImageGenerator
        
        print("✅ AI modules can be imported")
        
        # Test basic initialization
        detector = ObjectDetector()
        generator = ConceptualImageGenerator()
        
        print("✅ AI components can be initialized")
        return True
        
    except ImportError as e:
        print(f"❌ Import error: {e}")
        print("   Some dependencies may not be installed correctly")
        return False
    except Exception as e:
        print(f"❌ Initialization error: {e}")
        return False

def create_startup_script():
    """Create a startup script for the AI service"""
    print("\n📝 Creating startup script...")
    
    if os.name == 'nt':  # Windows
        script_content = """@echo off
echo Starting Collaborative AI Pipeline...
cd ai_service
python main.py
pause
"""
        script_path = "start_collaborative_ai.bat"
    else:  # Unix/Linux/Mac
        script_content = """#!/bin/bash
echo "Starting Collaborative AI Pipeline..."
cd ai_service
python main.py
"""
        script_path = "start_collaborative_ai.sh"
    
    try:
        with open(script_path, 'w') as f:
            f.write(script_content)
        
        if os.name != 'nt':
            os.chmod(script_path, 0o755)
        
        print(f"✅ Created startup script: {script_path}")
        return True
        
    except Exception as e:
        print(f"❌ Error creating startup script: {e}")
        return False

def print_next_steps():
    """Print next steps for the user"""
    print("\n🎉 Setup Complete!")
    print("=" * 50)
    print()
    print("Next steps:")
    print("1. Configure your Gemini API key in ai_service/.env")
    print("2. Start the AI service:")
    
    if os.name == 'nt':
        print("   - Double-click start_collaborative_ai.bat")
        print("   - Or run: cd ai_service && python main.py")
    else:
        print("   - Run: ./start_collaborative_ai.sh")
        print("   - Or run: cd ai_service && python main.py")
    
    print("3. Test the pipeline:")
    print("   - Open test_collaborative_ai_pipeline.html in your browser")
    print("   - Upload a room image and test all 4 stages")
    print()
    print("📚 Documentation:")
    print("   - COLLABORATIVE_AI_PIPELINE_IMPLEMENTATION.md")
    print("   - AI_ENHANCEMENT_README.md")
    print()
    print("🔗 Get Gemini API key:")
    print("   https://makersuite.google.com/app/apikey")
    print()

def main():
    """Main setup function"""
    print_header()
    
    # Check prerequisites
    if not check_python_version():
        return False
    
    if not check_dependencies():
        return False
    
    # Setup environment
    if not setup_environment():
        return False
    
    # Install dependencies
    if not install_python_dependencies():
        return False
    
    # Check configuration
    gemini_configured = check_gemini_api_key()
    
    # Test service
    if not test_ai_service():
        print("\n⚠️  AI service test failed, but setup can continue")
        print("   This may be due to missing GPU drivers or model downloads")
    
    # Create startup script
    create_startup_script()
    
    # Print next steps
    print_next_steps()
    
    if not gemini_configured:
        print("⚠️  IMPORTANT: Configure your Gemini API key before testing!")
    
    return True

if __name__ == "__main__":
    try:
        success = main()
        sys.exit(0 if success else 1)
    except KeyboardInterrupt:
        print("\n\n❌ Setup interrupted by user")
        sys.exit(1)
    except Exception as e:
        print(f"\n❌ Unexpected error: {e}")
        sys.exit(1)