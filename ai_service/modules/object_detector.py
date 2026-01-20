"""
Object Detection Module
Uses YOLOv8 with COCO weights to detect interior objects in room images.

This module focuses on detecting major interior items relevant to room improvement:
bed, sofa, chair, table, wardrobe, TV, window, door, etc.
"""

import cv2
import numpy as np
from ultralytics import YOLO
import logging
from typing import List, Dict, Any, Tuple

logger = logging.getLogger(__name__)

def convert_numpy_types(obj):
    """Convert NumPy types to native Python types for JSON serialization"""
    if isinstance(obj, np.integer):
        return int(obj)
    elif isinstance(obj, np.floating):
        return float(obj)
    elif isinstance(obj, np.ndarray):
        return obj.tolist()
    elif isinstance(obj, dict):
        return {key: convert_numpy_types(value) for key, value in obj.items()}
    elif isinstance(obj, list):
        return [convert_numpy_types(item) for item in obj]
    else:
        return obj

class ObjectDetector:
    """Object detector using YOLOv8 with COCO dataset weights"""
    
    # Interior-relevant COCO class names and IDs
    INTERIOR_CLASSES = {
        0: 'person',
        56: 'chair',
        57: 'couch',  # sofa
        58: 'potted_plant',
        59: 'bed',
        60: 'dining_table',
        61: 'toilet',
        62: 'tv',
        63: 'laptop',
        64: 'mouse',
        65: 'remote',
        66: 'keyboard',
        67: 'cell_phone',
        68: 'microwave',
        69: 'oven',
        70: 'toaster',
        71: 'sink',
        72: 'refrigerator',
        73: 'book',
        74: 'clock',
        75: 'vase',
        76: 'scissors',
        77: 'teddy_bear',
        78: 'hair_drier',
        79: 'toothbrush'
    }
    
    # Map COCO classes to room improvement categories
    FURNITURE_MAPPING = {
        'chair': 'seating',
        'couch': 'seating',
        'bed': 'sleeping',
        'dining_table': 'table',
        'tv': 'entertainment',
        'potted_plant': 'decoration',
        'clock': 'decoration',
        'vase': 'decoration',
        'book': 'storage_item',
        'laptop': 'electronics',
        'refrigerator': 'appliance',
        'microwave': 'appliance',
        'oven': 'appliance',
        'sink': 'fixture',
        'toilet': 'fixture'
    }
    
    def __init__(self, confidence_threshold: float = 0.5):
        """
        Initialize object detector
        
        Args:
            confidence_threshold: Minimum confidence for object detection
        """
        self.confidence_threshold = confidence_threshold
        self.model = None
        self._load_model()
    
    def _load_model(self):
        """Load YOLOv8 model with COCO weights"""
        try:
            logger.info("Loading YOLOv8 model...")
            # Use YOLOv8 nano for faster inference
            self.model = YOLO('yolov8n.pt')
            logger.info("YOLOv8 model loaded successfully")
        except Exception as e:
            logger.error(f"Failed to load YOLOv8 model: {e}")
            raise
    
    def detect_objects(self, image_path: str) -> Dict[str, Any]:
        """
        Detect objects in room image
        
        Args:
            image_path: Path to the room image
            
        Returns:
            Dictionary containing detected objects and metadata
        """
        try:
            # Load and validate image
            image = cv2.imread(image_path)
            if image is None:
                raise ValueError(f"Could not load image from {image_path}")
            
            height, width = image.shape[:2]
            
            # Run inference
            results = self.model(image, conf=self.confidence_threshold, verbose=False)
            
            # Process detections
            detected_objects = self._process_detections(results[0], width, height)
            
            # Generate summary
            detection_summary = self._generate_detection_summary(detected_objects)
            
            result = {
                "objects": detected_objects,
                "summary": detection_summary,
                "image_dimensions": {"width": width, "height": height},
                "detection_metadata": {
                    "total_objects": len(detected_objects),
                    "confidence_threshold": self.confidence_threshold,
                    "model": "yolov8n",
                    "dataset": "coco"
                }
            }
            
            # Convert all NumPy types to native Python types
            return convert_numpy_types(result)
            
        except Exception as e:
            logger.error(f"Object detection failed: {e}")
            result = {
                "objects": [],
                "summary": {"error": str(e)},
                "image_dimensions": {"width": 0, "height": 0},
                "detection_metadata": {
                    "total_objects": 0,
                    "confidence_threshold": self.confidence_threshold,
                    "error": str(e)
                }
            }
            return convert_numpy_types(result)
    
    def _process_detections(self, results, image_width: int, image_height: int) -> List[Dict[str, Any]]:
        """Process YOLO detection results into structured format"""
        detected_objects = []
        
        if results.boxes is None:
            return detected_objects
        
        boxes = results.boxes.xyxy.cpu().numpy()  # x1, y1, x2, y2
        confidences = results.boxes.conf.cpu().numpy()
        class_ids = results.boxes.cls.cpu().numpy().astype(int)
        
        for i, (box, confidence, class_id) in enumerate(zip(boxes, confidences, class_ids)):
            # Only process interior-relevant classes
            if class_id not in self.INTERIOR_CLASSES:
                continue
            
            class_name = self.INTERIOR_CLASSES[class_id]
            furniture_category = self.FURNITURE_MAPPING.get(class_name, 'other')
            
            x1, y1, x2, y2 = box
            
            # Calculate relative positions and dimensions
            center_x = (x1 + x2) / 2 / image_width
            center_y = (y1 + y2) / 2 / image_height
            width_ratio = (x2 - x1) / image_width
            height_ratio = (y2 - y1) / image_height
            
            # Calculate area
            area_ratio = width_ratio * height_ratio
            
            detected_object = {
                "object_id": f"obj_{i}",
                "class_name": class_name,
                "furniture_category": furniture_category,
                "confidence": round(float(confidence), 3),
                "bounding_box": {
                    "x1": int(x1), "y1": int(y1),
                    "x2": int(x2), "y2": int(y2)
                },
                "relative_position": {
                    "center_x": round(center_x, 3),
                    "center_y": round(center_y, 3)
                },
                "relative_size": {
                    "width_ratio": round(width_ratio, 3),
                    "height_ratio": round(height_ratio, 3),
                    "area_ratio": round(area_ratio, 3)
                },
                "size_category": self._categorize_size(area_ratio),
                "position_description": self._describe_position(center_x, center_y)
            }
            
            detected_objects.append(detected_object)
        
        # Sort by confidence (highest first)
        detected_objects.sort(key=lambda x: x['confidence'], reverse=True)
        
        return detected_objects
    
    def _categorize_size(self, area_ratio: float) -> str:
        """Categorize object size based on area ratio"""
        if area_ratio > 0.15:
            return "large"
        elif area_ratio > 0.05:
            return "medium"
        else:
            return "small"
    
    def _describe_position(self, center_x: float, center_y: float) -> str:
        """Generate human-readable position description"""
        # Horizontal position
        if center_x < 0.33:
            h_pos = "left"
        elif center_x > 0.67:
            h_pos = "right"
        else:
            h_pos = "center"
        
        # Vertical position
        if center_y < 0.33:
            v_pos = "top"
        elif center_y > 0.67:
            v_pos = "bottom"
        else:
            v_pos = "middle"
        
        return f"{v_pos}_{h_pos}"
    
    def _generate_detection_summary(self, detected_objects: List[Dict[str, Any]]) -> Dict[str, Any]:
        """Generate summary of detected objects"""
        if not detected_objects:
            return {
                "total_objects": 0,
                "furniture_categories": {},
                "size_distribution": {},
                "confidence_stats": {},
                "major_items": []
            }
        
        # Count by furniture category
        furniture_categories = {}
        size_distribution = {"large": 0, "medium": 0, "small": 0}
        confidences = []
        
        for obj in detected_objects:
            category = obj['furniture_category']
            furniture_categories[category] = furniture_categories.get(category, 0) + 1
            
            size_cat = obj['size_category']
            size_distribution[size_cat] += 1
            
            confidences.append(obj['confidence'])
        
        # Identify major items (large objects with high confidence)
        major_items = [
            {
                "class_name": obj['class_name'],
                "confidence": obj['confidence'],
                "position": obj['position_description'],
                "size": obj['size_category']
            }
            for obj in detected_objects
            if obj['size_category'] in ['large', 'medium'] and obj['confidence'] > 0.6
        ]
        
        return {
            "total_objects": len(detected_objects),
            "furniture_categories": furniture_categories,
            "size_distribution": size_distribution,
            "confidence_stats": {
                "average": round(np.mean(confidences), 3) if confidences else 0,
                "max": round(max(confidences), 3) if confidences else 0,
                "min": round(min(confidences), 3) if confidences else 0
            },
            "major_items": major_items[:5]  # Top 5 major items
        }