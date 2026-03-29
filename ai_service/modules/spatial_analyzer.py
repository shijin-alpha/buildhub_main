"""
Spatial Analysis Module
Converts detected object bounding boxes into coarse spatial zones and relationships.

This module provides spatial reasoning without exact measurements or depth estimation,
focusing on relative positioning and zone-based analysis.
"""

import cv2
import numpy as np
from typing import List, Dict, Any, Tuple
import logging

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

class SpatialAnalyzer:
    """Analyzes spatial relationships and zones in room images"""
    
    def __init__(self):
        """Initialize spatial analyzer"""
        pass
    
    def analyze_spatial_zones(self, image_path: str, detected_objects: Dict[str, Any]) -> Dict[str, Any]:
        """
        Analyze spatial zones and relationships between objects
        
        Args:
            image_path: Path to the room image
            detected_objects: Output from ObjectDetector
            
        Returns:
            Dictionary containing spatial analysis results
        """
        try:
            # Load image for spatial context
            image = cv2.imread(image_path)
            if image is None:
                raise ValueError(f"Could not load image from {image_path}")
            
            height, width = image.shape[:2]
            objects = detected_objects.get('objects', [])
            
            # Analyze spatial zones
            spatial_zones = self._analyze_zones(objects, width, height)
            
            # Analyze object relationships
            object_relationships = self._analyze_relationships(objects)
            
            # Detect potential spatial issues
            spatial_issues = self._detect_spatial_issues(objects, spatial_zones)
            
            # Generate spatial insights
            spatial_insights = self._generate_spatial_insights(objects, spatial_zones, object_relationships)
            
            result = {
                "spatial_zones": spatial_zones,
                "object_relationships": object_relationships,
                "spatial_issues": spatial_issues,
                "spatial_insights": spatial_insights,
                "zone_analysis_metadata": {
                    "image_dimensions": {"width": width, "height": height},
                    "total_objects_analyzed": len(objects),
                    "analysis_method": "coarse_zone_mapping"
                }
            }
            
            # Convert all NumPy types to native Python types
            return convert_numpy_types(result)
            
        except Exception as e:
            logger.error(f"Spatial analysis failed: {e}")
            result = {
                "spatial_zones": {},
                "object_relationships": [],
                "spatial_issues": [],
                "spatial_insights": [],
                "zone_analysis_metadata": {
                    "error": str(e)
                }
            }
            return convert_numpy_types(result)
    
    def _analyze_zones(self, objects: List[Dict[str, Any]], width: int, height: int) -> Dict[str, Any]:
        """Analyze objects by spatial zones"""
        
        # Define zone boundaries (relative coordinates)
        zones = {
            "left_zone": {"x_min": 0.0, "x_max": 0.33, "objects": []},
            "center_zone": {"x_min": 0.33, "x_max": 0.67, "objects": []},
            "right_zone": {"x_min": 0.67, "x_max": 1.0, "objects": []},
            "top_zone": {"y_min": 0.0, "y_max": 0.33, "objects": []},
            "middle_zone": {"y_min": 0.33, "y_max": 0.67, "objects": []},
            "bottom_zone": {"y_min": 0.67, "y_max": 1.0, "objects": []},
            "wall_aligned": {"objects": []},  # Objects near edges
            "center_blocking": {"objects": []},  # Objects in central area
            "near_window": {"objects": []}  # Objects that might be near windows
        }
        
        for obj in objects:
            center_x = obj['relative_position']['center_x']
            center_y = obj['relative_position']['center_y']
            
            # Horizontal zones
            if center_x <= 0.33:
                zones["left_zone"]["objects"].append(obj['object_id'])
            elif center_x >= 0.67:
                zones["right_zone"]["objects"].append(obj['object_id'])
            else:
                zones["center_zone"]["objects"].append(obj['object_id'])
            
            # Vertical zones
            if center_y <= 0.33:
                zones["top_zone"]["objects"].append(obj['object_id'])
            elif center_y >= 0.67:
                zones["bottom_zone"]["objects"].append(obj['object_id'])
            else:
                zones["middle_zone"]["objects"].append(obj['object_id'])
            
            # Special zones
            # Wall-aligned: objects near edges
            if (center_x < 0.15 or center_x > 0.85 or 
                center_y < 0.15 or center_y > 0.85):
                zones["wall_aligned"]["objects"].append(obj['object_id'])
            
            # Center-blocking: large objects in central area
            if (0.3 < center_x < 0.7 and 0.3 < center_y < 0.7 and 
                obj['size_category'] in ['large', 'medium']):
                zones["center_blocking"]["objects"].append(obj['object_id'])
            
            # Near-window heuristic: objects in top zone or near walls
            # (windows are often on walls or upper areas)
            if (center_y < 0.4 or center_x < 0.2 or center_x > 0.8):
                if obj['class_name'] in ['chair', 'couch', 'bed', 'dining_table']:
                    zones["near_window"]["objects"].append(obj['object_id'])
        
        # Add zone statistics
        zone_stats = {}
        for zone_name, zone_data in zones.items():
            if isinstance(zone_data, dict) and 'objects' in zone_data:
                zone_stats[zone_name] = {
                    "object_count": len(zone_data['objects']),
                    "density": self._calculate_zone_density(zone_name, zone_data['objects'], objects)
                }
        
        zones["zone_statistics"] = zone_stats
        
        return zones
    
    def _calculate_zone_density(self, zone_name: str, object_ids: List[str], all_objects: List[Dict[str, Any]]) -> str:
        """Calculate zone density category"""
        object_count = len(object_ids)
        
        # Calculate total area of objects in zone
        total_area = 0
        for obj in all_objects:
            if obj['object_id'] in object_ids:
                total_area += obj['relative_size']['area_ratio']
        
        if total_area > 0.3:
            return "high"
        elif total_area > 0.1:
            return "medium"
        else:
            return "low"
    
    def _analyze_relationships(self, objects: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Analyze spatial relationships between objects"""
        relationships = []
        
        for i, obj1 in enumerate(objects):
            for j, obj2 in enumerate(objects[i+1:], i+1):
                relationship = self._calculate_relationship(obj1, obj2)
                if relationship:
                    relationships.append(relationship)
        
        return relationships
    
    def _calculate_relationship(self, obj1: Dict[str, Any], obj2: Dict[str, Any]) -> Dict[str, Any]:
        """Calculate relationship between two objects"""
        x1, y1 = obj1['relative_position']['center_x'], obj1['relative_position']['center_y']
        x2, y2 = obj2['relative_position']['center_x'], obj2['relative_position']['center_y']
        
        # Calculate distance
        distance = np.sqrt((x2 - x1)**2 + (y2 - y1)**2)
        
        # Only analyze close relationships
        if distance > 0.5:
            return None
        
        # Determine relative position
        dx = x2 - x1
        dy = y2 - y1
        
        if abs(dx) > abs(dy):
            if dx > 0:
                relative_pos = "right_of"
            else:
                relative_pos = "left_of"
        else:
            if dy > 0:
                relative_pos = "below"
            else:
                relative_pos = "above"
        
        # Categorize distance
        if distance < 0.2:
            distance_category = "very_close"
        elif distance < 0.35:
            distance_category = "close"
        else:
            distance_category = "moderate"
        
        return {
            "object1": obj1['object_id'],
            "object2": obj2['object_id'],
            "object1_class": obj1['class_name'],
            "object2_class": obj2['class_name'],
            "relationship": relative_pos,
            "distance": round(distance, 3),
            "distance_category": distance_category,
            "potential_interaction": self._assess_interaction_potential(obj1, obj2, distance)
        }
    
    def _assess_interaction_potential(self, obj1: Dict[str, Any], obj2: Dict[str, Any], distance: float) -> str:
        """Assess if objects might interact functionally"""
        class1, class2 = obj1['class_name'], obj2['class_name']
        
        # Define functional pairs
        functional_pairs = {
            ('chair', 'dining_table'): 'seating_arrangement',
            ('couch', 'tv'): 'entertainment_setup',
            ('bed', 'chair'): 'bedroom_seating',
            ('chair', 'chair'): 'conversation_area',
            ('couch', 'chair'): 'seating_group'
        }
        
        pair_key = tuple(sorted([class1, class2]))
        
        if pair_key in functional_pairs and distance < 0.4:
            return functional_pairs[pair_key]
        elif distance < 0.15:
            return 'potentially_crowded'
        else:
            return 'independent'
    
    def _detect_spatial_issues(self, objects: List[Dict[str, Any]], spatial_zones: Dict[str, Any]) -> List[Dict[str, Any]]:
        """Detect potential spatial arrangement issues"""
        issues = []
        
        # Issue 1: Center blocking
        center_blocking_objects = spatial_zones.get('center_blocking', {}).get('objects', [])
        if center_blocking_objects:
            blocking_objects = [obj for obj in objects if obj['object_id'] in center_blocking_objects]
            issues.append({
                "issue_type": "center_blocking",
                "severity": "medium",
                "description": "Large furniture appears to be blocking central walking area",
                "affected_objects": [obj['class_name'] for obj in blocking_objects],
                "suggestion": "Consider relocating large furniture to wall-aligned positions"
            })
        
        # Issue 2: Potential window blocking
        near_window_objects = spatial_zones.get('near_window', {}).get('objects', [])
        if near_window_objects:
            window_blocking = [obj for obj in objects 
                             if obj['object_id'] in near_window_objects and 
                             obj['size_category'] == 'large']
            if window_blocking:
                issues.append({
                    "issue_type": "potential_window_blocking",
                    "severity": "low",
                    "description": "Large furniture may be blocking natural light sources",
                    "affected_objects": [obj['class_name'] for obj in window_blocking],
                    "suggestion": "Verify furniture placement doesn't obstruct windows or light sources"
                })
        
        # Issue 3: Unbalanced distribution
        left_count = len(spatial_zones.get('left_zone', {}).get('objects', []))
        right_count = len(spatial_zones.get('right_zone', {}).get('objects', []))
        center_count = len(spatial_zones.get('center_zone', {}).get('objects', []))
        
        if max(left_count, right_count, center_count) > 2 * min(left_count, right_count, center_count) + 1:
            issues.append({
                "issue_type": "unbalanced_distribution",
                "severity": "low",
                "description": "Furniture distribution appears unbalanced across the room",
                "affected_objects": [],
                "suggestion": "Consider redistributing furniture for better visual balance"
            })
        
        return issues
    
    def _generate_spatial_insights(self, objects: List[Dict[str, Any]], 
                                 spatial_zones: Dict[str, Any], 
                                 relationships: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Generate actionable spatial insights"""
        insights = []
        
        # Insight 1: Wall alignment opportunities
        wall_aligned = spatial_zones.get('wall_aligned', {}).get('objects', [])
        non_wall_large_objects = [obj for obj in objects 
                                if obj['object_id'] not in wall_aligned and 
                                obj['size_category'] == 'large' and
                                obj['furniture_category'] in ['seating', 'sleeping']]
        
        if non_wall_large_objects:
            insights.append({
                "insight_type": "wall_alignment_opportunity",
                "priority": "medium",
                "description": "Large furniture pieces could benefit from wall alignment",
                "affected_objects": [obj['class_name'] for obj in non_wall_large_objects],
                "reasoning": "Wall-aligned furniture maximizes open floor space and improves traffic flow",
                "suggestion": "Consider positioning large furniture against walls when possible"
            })
        
        # Insight 2: Functional grouping opportunities
        seating_objects = [obj for obj in objects if obj['furniture_category'] == 'seating']
        if len(seating_objects) >= 2:
            # Check if seating is grouped
            seating_relationships = [rel for rel in relationships 
                                   if rel['object1_class'] in ['chair', 'couch'] and 
                                   rel['object2_class'] in ['chair', 'couch']]
            
            if not seating_relationships:
                insights.append({
                    "insight_type": "functional_grouping_opportunity",
                    "priority": "low",
                    "description": "Seating furniture could be arranged to encourage conversation",
                    "affected_objects": [obj['class_name'] for obj in seating_objects],
                    "reasoning": "Grouped seating creates more inviting social spaces",
                    "suggestion": "Consider arranging seating to face each other or create conversation areas"
                })
        
        # Insight 3: Traffic flow optimization
        center_zone_objects = spatial_zones.get('center_zone', {}).get('objects', [])
        if len(center_zone_objects) > 2:
            insights.append({
                "insight_type": "traffic_flow_optimization",
                "priority": "high",
                "description": "Central area appears congested, which may impede movement",
                "affected_objects": [],
                "reasoning": "Clear pathways through the center improve room functionality",
                "suggestion": "Ensure clear walking paths through the central area of the room"
            })
        
        return insights