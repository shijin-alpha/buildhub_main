#!/usr/bin/env python3
"""
Setup script for Room Improvement AI Service
Installs dependencies and downloads YOLOv8 model
"""

import subprocess
import sys
import os
from pathlib import Path

def run_command(command, description):
    """Run a command and handle errors"""
    print(f"\n{description}...")
    try:
        result = subprocess.run(command, shell=True, check=True, capture_output=True, text=True)
        print(f"✓ {description} completed successfully")
        return True
    except subprocess.CalledProcessError as e:
        print(f"✗ {description} failed:")
        print(f"Error: {e.stderr}")
        return False

def check_python_version():
    """Check if Python version is 3.9 or higher"""
    version = sys.version_info
    if version.major < 3 or (version.major == 3 and version.minor < 9):
        print(f"✗ Python 3.9+ required. Current version: {version.major}.{version.minor}")
        return False
    print(f"✓ Python version {version.major}.{version.minor} is compatible")
    return True

def install_requirements():
    """Install Python requirements"""
    requirements_file = Path(__file__).parent / "requirements.txt"
    if not requirements_file.exists():
        print("✗ requirements.txt not found")
        return False
    
    # Use quoted paths to handle spaces in Windows paths
    command = f'"{sys.executable}" -m pip install -r "{requirements_file}"'
    return run_command(command, "Installing Python dependencies")

def download_yolo_model():
    """Download YOLOv8 model"""
    print("\nDownloading YOLOv8 model...")
    try:
        from ultralytics import YOLO
        # This will automatically download the model if not present
        model = YOLO('yolov8n.pt')
        print("✓ YOLOv8 model downloaded successfully")
        return True
    except Exception as e:
        print(f"✗ Failed to download YOLOv8 model: {e}")
        return False

def test_imports():
    """Test if all required modules can be imported"""
    print("\nTesting module imports...")
    modules = [
        'fastapi',
        'uvicorn',
        'cv2',
        'numpy',
        'ultralytics',
        'PIL'
    ]
    
    failed_imports = []
    for module in modules:
        try:
            __import__(module)
            print(f"✓ {module}")
        except ImportError as e:
            print(f"✗ {module}: {e}")
            failed_imports.append(module)
    
    return len(failed_imports) == 0

def create_directories():
    """Create necessary directories"""
    directories = [
        "logs",
        "temp",
        "models"
    ]
    
    for directory in directories:
        dir_path = Path(__file__).parent / directory
        dir_path.mkdir(exist_ok=True)
        print(f"✓ Created directory: {directory}")
    
    return True

def main():
    """Main setup function"""
    print("Room Improvement AI Service Setup")
    print("=" * 40)
    
    # Check Python version
    if not check_python_version():
        sys.exit(1)
    
    # Create directories
    if not create_directories():
        print("✗ Failed to create directories")
        sys.exit(1)
    
    # Install requirements
    if not install_requirements():
        print("✗ Failed to install requirements")
        sys.exit(1)
    
    # Test imports
    if not test_imports():
        print("✗ Some modules failed to import")
        sys.exit(1)
    
    # Download YOLO model
    if not download_yolo_model():
        print("✗ Failed to download YOLO model")
        sys.exit(1)
    
    print("\n" + "=" * 40)
    print("✓ Setup completed successfully!")
    print("\nTo start the AI service, run:")
    print("  python main.py")
    print("\nOr with uvicorn:")
    print("  uvicorn main:app --host 127.0.0.1 --port 8000 --reload")

if __name__ == "__main__":
    main()