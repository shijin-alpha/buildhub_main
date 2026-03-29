#!/usr/bin/env python3
"""
Diagnostic script for Room Improvement AI Service
Checks system requirements and configuration
"""

import sys
import os
import subprocess
import importlib
from pathlib import Path

def print_header(title):
    """Print a formatted header"""
    print(f"\n{'='*50}")
    print(f" {title}")
    print(f"{'='*50}")

def print_check(description, status, details=""):
    """Print a check result"""
    symbol = "✓" if status else "✗"
    color = "\033[92m" if status else "\033[91m"  # Green or Red
    reset = "\033[0m"
    
    print(f"{color}{symbol}{reset} {description}")
    if details:
        print(f"   {details}")

def check_python_version():
    """Check Python version"""
    print_header("Python Environment")
    
    version = sys.version_info
    version_str = f"{version.major}.{version.minor}.{version.micro}"
    required = version.major >= 3 and version.minor >= 9
    
    print_check(f"Python version: {version_str}", required, 
                "Requires Python 3.9+" if not required else "")
    
    return required

def check_required_modules():
    """Check if required modules are available"""
    print_header("Required Modules")
    
    modules = {
        'fastapi': 'FastAPI web framework',
        'uvicorn': 'ASGI server',
        'cv2': 'OpenCV computer vision library',
        'numpy': 'NumPy numerical computing',
        'ultralytics': 'YOLOv8 object detection',
        'PIL': 'Pillow image processing',
        'pydantic': 'Data validation'
    }
    
    all_available = True
    
    for module, description in modules.items():
        try:
            importlib.import_module(module)
            print_check(f"{module}: {description}", True)
        except ImportError as e:
            print_check(f"{module}: {description}", False, f"Import error: {e}")
            all_available = False
    
    return all_available

def check_yolo_model():
    """Check if YOLOv8 model is available"""
    print_header("YOLOv8 Model")
    
    try:
        from ultralytics import YOLO
        model = YOLO('yolov8n.pt')
        print_check("YOLOv8 nano model", True, "Model loaded successfully")
        return True
    except Exception as e:
        print_check("YOLOv8 nano model", False, f"Error: {e}")
        return False

def check_opencv_functionality():
    """Check OpenCV functionality"""
    print_header("OpenCV Functionality")
    
    try:
        import cv2
        import numpy as np
        
        # Test basic OpenCV operations
        test_image = np.zeros((100, 100, 3), dtype=np.uint8)
        gray = cv2.cvtColor(test_image, cv2.COLOR_BGR2GRAY)
        print_check("Color space conversion", True)
        
        # Test image processing
        blurred = cv2.GaussianBlur(gray, (5, 5), 0)
        print_check("Image filtering", True)
        
        # Test edge detection
        edges = cv2.Canny(gray, 50, 150)
        print_check("Edge detection", True)
        
        return True
        
    except Exception as e:
        print_check("OpenCV functionality", False, f"Error: {e}")
        return False

def check_file_permissions():
    """Check file permissions and directories"""
    print_header("File System")
    
    current_dir = Path(__file__).parent
    
    # Check if we can create directories
    try:
        test_dir = current_dir / "test_temp"
        test_dir.mkdir(exist_ok=True)
        test_dir.rmdir()
        print_check("Directory creation", True)
    except Exception as e:
        print_check("Directory creation", False, f"Error: {e}")
        return False
    
    # Check if we can write files
    try:
        test_file = current_dir / "test_file.tmp"
        test_file.write_text("test")
        test_file.unlink()
        print_check("File writing", True)
    except Exception as e:
        print_check("File writing", False, f"Error: {e}")
        return False
    
    return True

def check_network_ports():
    """Check if required ports are available"""
    print_header("Network Configuration")
    
    import socket
    
    def is_port_available(port):
        with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
            try:
                s.bind(('127.0.0.1', port))
                return True
            except OSError:
                return False
    
    port_8000 = is_port_available(8000)
    print_check("Port 8000 available", port_8000, 
                "Port is in use" if not port_8000 else "Ready for AI service")
    
    return port_8000

def test_ai_components():
    """Test AI components individually"""
    print_header("AI Components Test")
    
    try:
        # Test object detector
        sys.path.append(str(Path(__file__).parent))
        from modules.object_detector import ObjectDetector
        detector = ObjectDetector()
        print_check("Object detector initialization", True)
        
        # Test spatial analyzer
        from modules.spatial_analyzer import SpatialAnalyzer
        analyzer = SpatialAnalyzer()
        print_check("Spatial analyzer initialization", True)
        
        # Test visual processor
        from modules.visual_processor import VisualProcessor
        processor = VisualProcessor()
        print_check("Visual processor initialization", True)
        
        # Test rule engine
        from modules.rule_engine import EnhancedRuleEngine
        engine = EnhancedRuleEngine()
        print_check("Rule engine initialization", True)
        
        return True
        
    except Exception as e:
        print_check("AI components", False, f"Error: {e}")
        return False

def generate_report():
    """Generate diagnostic report"""
    print_header("Diagnostic Report")
    
    checks = [
        ("Python Version", check_python_version()),
        ("Required Modules", check_required_modules()),
        ("YOLOv8 Model", check_yolo_model()),
        ("OpenCV Functionality", check_opencv_functionality()),
        ("File Permissions", check_file_permissions()),
        ("Network Ports", check_network_ports()),
        ("AI Components", test_ai_components())
    ]
    
    passed = sum(1 for _, status in checks if status)
    total = len(checks)
    
    print(f"\nDiagnostic Summary: {passed}/{total} checks passed")
    
    if passed == total:
        print("\n🎉 All checks passed! Your AI service is ready to run.")
        print("\nTo start the service:")
        print("  python main.py")
    else:
        print(f"\n⚠️  {total - passed} issues found. Please resolve them before starting the service.")
        
        failed_checks = [name for name, status in checks if not status]
        print("\nFailed checks:")
        for check in failed_checks:
            print(f"  - {check}")
        
        print("\nRecommended actions:")
        if not checks[1][1]:  # Required modules failed
            print("  1. Install requirements: pip install -r requirements.txt")
        if not checks[2][1]:  # YOLOv8 model failed
            print("  2. Download YOLOv8 model: python -c \"from ultralytics import YOLO; YOLO('yolov8n.pt')\"")
        if not checks[5][1]:  # Network ports failed
            print("  3. Check if port 8000 is in use: netstat -an | grep 8000")

def main():
    """Main diagnostic function"""
    print("Room Improvement AI Service Diagnostics")
    print("This script will check if your system is ready to run the AI service.")
    
    generate_report()
    
    print(f"\nDiagnostics completed at {__import__('datetime').datetime.now()}")
    print("For more help, see AI_ENHANCEMENT_README.md")

if __name__ == "__main__":
    main()