#!/usr/bin/env python3
"""
AI Service Setup Verification Script
Checks if all components are properly configured and ready
"""

import sys
import os
from pathlib import Path

def print_header(text):
    print("\n" + "="*60)
    print(f"  {text}")
    print("="*60)

def print_status(check_name, status, message=""):
    status_symbol = "✅" if status else "❌"
    print(f"{status_symbol} {check_name}")
    if message:
        print(f"   → {message}")

def check_python_version():
    """Check Python version"""
    version = sys.version_info
    is_valid = version.major == 3 and version.minor >= 8
    version_str = f"{version.major}.{version.minor}.{version.micro}"
    print_status(
        "Python Version", 
        is_valid, 
        f"Python {version_str} {'(OK)' if is_valid else '(Need 3.8+)'}"
    )
    return is_valid

def check_dependencies():
    """Check if required packages are installed"""
    required_packages = [
        'fastapi',
        'uvicorn',
        'torch',
        'diffusers',
        'transformers',
        'ultralytics',
        'PIL',
        'cv2',
        'numpy',
        'requests'
    ]
    
    all_installed = True
    for package in required_packages:
        try:
            if package == 'PIL':
                __import__('PIL')
            elif package == 'cv2':
                __import__('cv2')
            else:
                __import__(package)
            print_status(f"Package: {package}", True, "Installed")
        except ImportError:
            print_status(f"Package: {package}", False, "NOT INSTALLED")
            all_installed = False
    
    return all_installed

def check_env_file():
    """Check if .env file exists and has required keys"""
    env_path = Path("ai_service/.env")
    
    if not env_path.exists():
        print_status(".env file", False, "File not found")
        return False
    
    print_status(".env file", True, "File exists")
    
    # Check for Gemini API key
    with open(env_path, 'r') as f:
        content = f.read()
        has_gemini = 'GEMINI_API_KEY=' in content and not 'your_gemini_api_key_here' in content
        print_status("Gemini API Key", has_gemini, "Configured" if has_gemini else "Not configured")
    
    return True

def check_directories():
    """Check if required directories exist"""
    directories = [
        "ai_service",
        "ai_service/modules",
        "uploads/conceptual_images",
        "backend/api/homeowner"
    ]
    
    all_exist = True
    for directory in directories:
        exists = Path(directory).exists()
        print_status(f"Directory: {directory}", exists, "Exists" if exists else "Missing")
        if not exists:
            all_exist = False
    
    return all_exist

def check_key_files():
    """Check if key files exist"""
    files = [
        "ai_service/main.py",
        "ai_service/modules/conceptual_generator.py",
        "backend/utils/AIServiceConnector.php",
        "backend/utils/EnhancedRoomAnalyzer.php",
        "backend/api/homeowner/analyze_room_improvement.php",
        "backend/api/homeowner/check_image_status.php"
    ]
    
    all_exist = True
    for file in files:
        exists = Path(file).exists()
        print_status(f"File: {Path(file).name}", exists, file if exists else "Missing")
        if not exists:
            all_exist = False
    
    return all_exist

def check_generator_import():
    """Check if main.py imports the correct generator"""
    main_py = Path("ai_service/main.py")
    
    if not main_py.exists():
        print_status("Generator Import", False, "main.py not found")
        return False
    
    with open(main_py, 'r') as f:
        content = f.read()
        uses_full = 'from modules.conceptual_generator import ConceptualImageGenerator' in content
        uses_simple = 'from modules.conceptual_generator_simple import ConceptualImageGenerator' in content
        
        if uses_full:
            print_status("Generator Import", True, "Using FULL generator (correct)")
            return True
        elif uses_simple:
            print_status("Generator Import", False, "Using SIMPLE generator (wrong - will create placeholders)")
            return False
        else:
            print_status("Generator Import", False, "Import not found")
            return False

def check_service_running():
    """Check if AI service is running"""
    try:
        import requests
        response = requests.get("http://127.0.0.1:8000/health", timeout=2)
        if response.status_code == 200:
            data = response.json()
            is_healthy = data.get('status') == 'healthy'
            print_status("AI Service", is_healthy, "Running and healthy" if is_healthy else "Running but unhealthy")
            
            # Check components
            components = data.get('components', {})
            for comp_name, comp_status in components.items():
                print_status(f"  Component: {comp_name}", comp_status, "Loaded" if comp_status else "Failed")
            
            return is_healthy
        else:
            print_status("AI Service", False, f"HTTP {response.status_code}")
            return False
    except Exception as e:
        print_status("AI Service", False, f"Not running - {str(e)}")
        return False

def main():
    print_header("BuildHub AI Service Setup Verification")
    
    results = {}
    
    print_header("1. Python Environment")
    results['python'] = check_python_version()
    
    print_header("2. Dependencies")
    results['dependencies'] = check_dependencies()
    
    print_header("3. Configuration")
    results['env'] = check_env_file()
    
    print_header("4. Directory Structure")
    results['directories'] = check_directories()
    
    print_header("5. Key Files")
    results['files'] = check_key_files()
    
    print_header("6. Generator Configuration")
    results['generator'] = check_generator_import()
    
    print_header("7. Service Status")
    results['service'] = check_service_running()
    
    # Summary
    print_header("SUMMARY")
    
    all_passed = all(results.values())
    
    if all_passed:
        print("\n✅ ALL CHECKS PASSED!")
        print("\nYour system is ready to generate real AI images.")
        print("\nNext steps:")
        print("1. If service is not running, start it: start_ai_service.bat")
        print("2. Open test page: http://localhost/buildhub/test_real_ai_async_generation.html")
        print("3. Upload a room image and watch the magic happen!")
    else:
        print("\n❌ SOME CHECKS FAILED")
        print("\nIssues found:")
        
        if not results['python']:
            print("  • Upgrade Python to 3.8 or higher")
        
        if not results['dependencies']:
            print("  • Install dependencies: pip install -r ai_service/requirements.txt")
        
        if not results['env']:
            print("  • Configure .env file with Gemini API key")
        
        if not results['directories'] or not results['files']:
            print("  • Some files or directories are missing")
        
        if not results['generator']:
            print("  • Fix generator import in ai_service/main.py")
        
        if not results['service']:
            print("  • Start AI service: start_ai_service.bat")
        
        print("\nRefer to REAL_AI_IMAGE_GENERATION_SETUP.md for detailed instructions.")
    
    print("\n" + "="*60 + "\n")
    
    return 0 if all_passed else 1

if __name__ == "__main__":
    sys.exit(main())
