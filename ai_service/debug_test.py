#!/usr/bin/env python3
"""
Debug test for AI service components
"""

import sys
import os
import traceback
from pathlib import Path

# Add current directory to path
sys.path.append(str(Path(__file__).parent))

def test_object_detector():
    """Test object detector directly"""
    print("Testing Object Detector...")
    
    try:
        from modules.object_detector import ObjectDetector
        detector = ObjectDetector()
        print("✓ Object detector initialized")
        
        # Test with a sample image
        test_image = "../test_room_image.jpg"
        if os.path.exists(test_image):
            print(f"Testing with image: {test_image}")
            result = detector.detect_objects(test_image)
            print(f"✓ Detection completed. Found {len(result.get('objects', []))} objects")
            print(f"Summary: {result.get('summary', {})}")
        else:
            print(f"⚠ Test image not found: {test_image}")
            
    except Exception as e:
        print(f"✗ Object detector failed: {e}")
        traceback.print_exc()

def test_visual_processor():
    """Test visual processor directly"""
    print("\nTesting Visual Processor...")
    
    try:
        from modules.visual_processor import VisualProcessor
        processor = VisualProcessor()
        print("✓ Visual processor initialized")
        
        # Test with a sample image
        test_image = "../test_room_image.jpg"
        if os.path.exists(test_image):
            print(f"Testing with image: {test_image}")
            result = processor.enhance_visual_analysis(test_image, {})
            print("✓ Visual processing completed")
        else:
            print(f"⚠ Test image not found: {test_image}")
            
    except Exception as e:
        print(f"✗ Visual processor failed: {e}")
        traceback.print_exc()

def test_imports():
    """Test all imports"""
    print("\nTesting Imports...")
    
    modules = [
        'cv2',
        'numpy',
        'ultralytics',
        'fastapi',
        'PIL'
    ]
    
    for module in modules:
        try:
            __import__(module)
            print(f"✓ {module}")
        except ImportError as e:
            print(f"✗ {module}: {e}")

def main():
    print("AI Service Debug Test")
    print("=" * 30)
    
    test_imports()
    test_object_detector()
    test_visual_processor()
    
    print("\nDebug test completed.")

if __name__ == "__main__":
    main()