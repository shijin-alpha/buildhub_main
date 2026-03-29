"""
Enhanced Rule Engine
Extends the existing rule-based expert system with spatial reasoning capabilities.

This module generates placement and improvement guidance using detected objects,
spatial zones, and interior design heuristics while maintaining explainable,
deterministic, and safe recommendations.
"""

from typing import List, Dict, Any
import logging

logger = logging.getLogger(__name__)

class EnhancedRuleEngine:
    """Rule-based spatial reasoning engine for interior design guidance"""
    
    def __init__(self):
        """Initialize the enhanced rule engine"""
        self.interior_design_rules = self._load_interior_design_rules()
        self.spatial_reasoning_rules = self._load_spatial_reasoning_rules()
        self.safety_guidelines = self._load_safety_guidelines()
    
    def generate_spatial_guidance(self, 
                                detected_objects: Dict[str, Any], 
                                spatial_zones: Dict[str, Any], 
                                room_type: str, 
                                improvement_notes: str) -> Dict[str, Any]:
        """
        Generate comprehensive spatial guidance using rule-based reasoning
        
        Args:
            detected_objects: Output from ObjectDetector
            spatial_zones: Output from SpatialAnalyzer
            room_type: Type of room being analyzed
            improvement_notes: User's improvement preferences
            
        Returns:
            Structured spatial guidance with explanations
        """
        try:
            objects = detected_objects.get('objects', [])
            zones = spatial_zones.get('spatial_zones', {})
            issues = spatial_zones.get('spatial_issues', [])
            insights = spatial_zones.get('spatial_insights', [])
            
            # Apply rule-based analysis
            placement_guidance = self._generate_placement_guidance(objects, zones, room_type)
            improvement_suggestions = self._generate_improvement_suggestions(objects, zones, issues, insights, room_type)
            layout_recommendations = self._generate_layout_recommendations(objects, zones, room_type)
            safety_considerations = self._apply_safety_guidelines(objects, zones, issues)
            
            # Customize based on user notes
            if improvement_notes:
                placement_guidance = self._customize_with_user_preferences(placement_guidance, improvement_notes)
                improvement_suggestions = self._customize_with_user_preferences(improvement_suggestions, improvement_notes)
            
            return {
                "placement_guidance": placement_guidance,
                "improvement_suggestions": improvement_suggestions,
                "layout_recommendations": layout_recommendations,
                "safety_considerations": safety_considerations,
                "reasoning_metadata": {
                    "rule_engine_version": "1.0",
                    "rules_applied": len(self.interior_design_rules) + len(self.spatial_reasoning_rules),
                    "room_type": room_type,
                    "objects_analyzed": len(objects),
                    "spatial_zones_analyzed": len([z for z in zones.keys() if isinstance(zones[z], dict) and 'objects' in zones[z]]),
                    "reasoning_approach": "deterministic_rule_based"
                }
            }
            
        except Exception as e:
            logger.error(f"Spatial guidance generation failed: {e}")
            return {
                "placement_guidance": [],
                "improvement_suggestions": [],
                "layout_recommendations": [],
                "safety_considerations": [],
                "reasoning_metadata": {
                    "error": str(e),
                    "fallback_mode": True
                }
            }
    
    def _generate_placement_guidance(self, objects: List[Dict[str, Any]], 
                                   zones: Dict[str, Any], 
                                   room_type: str) -> List[Dict[str, Any]]:
        """Generate object placement guidance using spatial rules"""
        guidance = []
        
        # Rule 1: Large furniture should be wall-aligned
        wall_aligned_objects = zones.get('wall_aligned', {}).get('objects', [])
        large_objects = [obj for obj in objects if obj['size_category'] == 'large']
        
        for obj in large_objects:
            if obj['object_id'] not in wall_aligned_objects:
                if obj['furniture_category'] in ['seating', 'sleeping']:
                    guidance.append({
                        "object": obj['class_name'],
                        "object_id": obj['object_id'],
                        "guidance_type": "wall_alignment",
                        "priority": "medium",
                        "recommendation": f"Consider relocating the {obj['class_name']} to align with a wall",
                        "reasoning": "Wall-aligned large furniture maximizes open floor space and improves traffic flow",
                        "confidence": "high",
                        "safety_note": "Ensure adequate clearance for safe movement around furniture"
                    })
        
        # Rule 2: Avoid blocking central pathways
        center_blocking_objects = zones.get('center_blocking', {}).get('objects', [])
        for obj_id in center_blocking_objects:
            obj = next((o for o in objects if o['object_id'] == obj_id), None)
            if obj:
                guidance.append({
                    "object": obj['class_name'],
                    "object_id": obj['object_id'],
                    "guidance_type": "pathway_clearance",
                    "priority": "high",
                    "recommendation": f"The {obj['class_name']} appears to be blocking central walking areas",
                    "reasoning": "Clear pathways through the center improve room functionality and safety",
                    "confidence": "high",
                    "safety_note": "Maintain clear walking paths to prevent accidents"
                })
        
        # Rule 3: Window proximity considerations
        near_window_objects = zones.get('near_window', {}).get('objects', [])
        for obj_id in near_window_objects:
            obj = next((o for o in objects if o['object_id'] == obj_id), None)
            if obj and obj['size_category'] == 'large':
                guidance.append({
                    "object": obj['class_name'],
                    "object_id": obj['object_id'],
                    "guidance_type": "natural_light_optimization",
                    "priority": "low",
                    "recommendation": f"Verify that the {obj['class_name']} placement doesn't obstruct natural light",
                    "reasoning": "Furniture blocking windows can reduce natural light and make spaces feel smaller",
                    "confidence": "moderate",
                    "safety_note": "Ensure furniture doesn't create dark areas that could be hazardous"
                })
        
        # Rule 4: Room-specific placement rules
        room_specific_guidance = self._apply_room_specific_rules(objects, zones, room_type)
        guidance.extend(room_specific_guidance)
        
        return guidance
    
    def _generate_improvement_suggestions(self, objects: List[Dict[str, Any]], 
                                        zones: Dict[str, Any], 
                                        issues: List[Dict[str, Any]], 
                                        insights: List[Dict[str, Any]], 
                                        room_type: str) -> List[Dict[str, Any]]:
        """Generate improvement suggestions based on spatial analysis"""
        suggestions = []
        
        # Convert spatial issues to improvement suggestions
        for issue in issues:
            suggestion = {
                "suggestion_type": issue['issue_type'],
                "priority": issue['severity'],
                "description": issue['description'],
                "recommendation": issue['suggestion'],
                "affected_objects": issue['affected_objects'],
                "reasoning": "Spatial analysis indicates potential improvement opportunity",
                "confidence": "moderate",
                "implementation_note": "Consider these suggestions as starting points for improvement"
            }
            suggestions.append(suggestion)
        
        # Convert spatial insights to improvement suggestions
        for insight in insights:
            suggestion = {
                "suggestion_type": insight['insight_type'],
                "priority": insight['priority'],
                "description": insight['description'],
                "recommendation": insight['suggestion'],
                "affected_objects": insight.get('affected_objects', []),
                "reasoning": insight['reasoning'],
                "confidence": "moderate",
                "implementation_note": "These suggestions may improve room functionality and aesthetics"
            }
            suggestions.append(suggestion)
        
        # Add furniture grouping suggestions
        seating_objects = [obj for obj in objects if obj['furniture_category'] == 'seating']
        if len(seating_objects) >= 2:
            suggestions.append({
                "suggestion_type": "seating_arrangement",
                "priority": "low",
                "description": "Multiple seating options detected - consider creating conversation areas",
                "recommendation": "Arrange seating to face each other or create intimate groupings",
                "affected_objects": [obj['class_name'] for obj in seating_objects],
                "reasoning": "Grouped seating creates more inviting and functional social spaces",
                "confidence": "moderate",
                "implementation_note": "Adjust based on room size and primary use patterns"
            })
        
        # Add balance and symmetry suggestions
        zone_stats = zones.get('zone_statistics', {})
        left_count = zone_stats.get('left_zone', {}).get('object_count', 0)
        right_count = zone_stats.get('right_zone', {}).get('object_count', 0)
        
        if abs(left_count - right_count) > 2:
            suggestions.append({
                "suggestion_type": "visual_balance",
                "priority": "low",
                "description": "Furniture distribution appears unbalanced between left and right sides",
                "recommendation": "Consider redistributing furniture or adding elements to balance the space",
                "affected_objects": [],
                "reasoning": "Balanced furniture distribution creates more visually pleasing and harmonious spaces",
                "confidence": "low",
                "implementation_note": "Balance doesn't require perfect symmetry - consider visual weight and scale"
            })
        
        return suggestions
    
    def _generate_layout_recommendations(self, objects: List[Dict[str, Any]], 
                                       zones: Dict[str, Any], 
                                       room_type: str) -> List[Dict[str, Any]]:
        """Generate overall layout recommendations"""
        recommendations = []
        
        # Analyze current layout patterns
        layout_analysis = self._analyze_current_layout(objects, zones)
        
        # Generate recommendations based on room type
        if room_type == 'living_room':
            recommendations.extend(self._living_room_layout_recommendations(objects, zones, layout_analysis))
        elif room_type == 'bedroom':
            recommendations.extend(self._bedroom_layout_recommendations(objects, zones, layout_analysis))
        elif room_type == 'dining_room':
            recommendations.extend(self._dining_room_layout_recommendations(objects, zones, layout_analysis))
        else:
            recommendations.extend(self._general_layout_recommendations(objects, zones, layout_analysis))
        
        return recommendations
    
    def _apply_safety_guidelines(self, objects: List[Dict[str, Any]], 
                               zones: Dict[str, Any], 
                               issues: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """Apply safety guidelines to spatial analysis"""
        safety_considerations = []
        
        # Safety Rule 1: Ensure clear pathways
        center_blocking = zones.get('center_blocking', {}).get('objects', [])
        if center_blocking:
            safety_considerations.append({
                "safety_type": "pathway_clearance",
                "priority": "high",
                "description": "Ensure clear pathways through the room",
                "recommendation": "Maintain at least 36 inches of clear walking space in main pathways",
                "reasoning": "Clear pathways prevent accidents and improve accessibility",
                "affected_areas": ["central_walking_area"]
            })
        
        # Safety Rule 2: Furniture stability
        large_objects = [obj for obj in objects if obj['size_category'] == 'large']
        if large_objects:
            safety_considerations.append({
                "safety_type": "furniture_stability",
                "priority": "medium",
                "description": "Ensure large furniture is properly secured",
                "recommendation": "Consider anchoring tall or heavy furniture to walls for safety",
                "reasoning": "Unsecured furniture can pose tipping hazards, especially in homes with children",
                "affected_areas": ["furniture_placement"]
            })
        
        # Safety Rule 3: Lighting and visibility
        safety_considerations.append({
            "safety_type": "lighting_safety",
            "priority": "medium",
            "description": "Ensure adequate lighting in all areas",
            "recommendation": "Verify that furniture placement doesn't create dark areas or shadows",
            "reasoning": "Well-lit spaces prevent accidents and improve overall safety",
            "affected_areas": ["lighting_coverage"]
        })
        
        return safety_considerations
    
    def _apply_room_specific_rules(self, objects: List[Dict[str, Any]], 
                                 zones: Dict[str, Any], 
                                 room_type: str) -> List[Dict[str, Any]]:
        """Apply room-specific placement rules"""
        guidance = []
        
        if room_type == 'bedroom':
            # Bedroom-specific rules
            beds = [obj for obj in objects if obj['class_name'] == 'bed']
            for bed in beds:
                if bed['relative_position']['center_x'] > 0.5:  # Bed on right side
                    guidance.append({
                        "object": "bed",
                        "object_id": bed['object_id'],
                        "guidance_type": "bedroom_layout",
                        "priority": "low",
                        "recommendation": "Consider bed placement for optimal room flow and access",
                        "reasoning": "Bed positioning affects room functionality and morning routines",
                        "confidence": "moderate",
                        "safety_note": "Ensure easy access to both sides of the bed when possible"
                    })
        
        elif room_type == 'living_room':
            # Living room-specific rules
            tvs = [obj for obj in objects if obj['class_name'] == 'tv']
            couches = [obj for obj in objects if obj['class_name'] == 'couch']
            
            if tvs and couches:
                # Check TV-seating relationship
                for tv in tvs:
                    for couch in couches:
                        # Calculate if they're facing each other (simplified)
                        tv_x, tv_y = tv['relative_position']['center_x'], tv['relative_position']['center_y']
                        couch_x, couch_y = couch['relative_position']['center_x'], couch['relative_position']['center_y']
                        
                        distance = ((tv_x - couch_x)**2 + (tv_y - couch_y)**2)**0.5
                        
                        if distance > 0.6:  # Too far apart
                            guidance.append({
                                "object": "entertainment_setup",
                                "object_id": f"{tv['object_id']}_{couch['object_id']}",
                                "guidance_type": "entertainment_layout",
                                "priority": "low",
                                "recommendation": "Consider optimizing the distance between seating and TV for comfortable viewing",
                                "reasoning": "Proper viewing distance enhances entertainment experience and reduces eye strain",
                                "confidence": "moderate",
                                "safety_note": "Maintain clear pathways between seating and entertainment areas"
                            })
        
        return guidance
    
    def _analyze_current_layout(self, objects: List[Dict[str, Any]], zones: Dict[str, Any]) -> Dict[str, Any]:
        """Analyze the current layout pattern"""
        return {
            "layout_density": len(objects) / 10,  # Rough density measure
            "wall_utilization": len(zones.get('wall_aligned', {}).get('objects', [])) / max(1, len(objects)),
            "center_usage": len(zones.get('center_blocking', {}).get('objects', [])) / max(1, len(objects)),
            "balance_score": self._calculate_balance_score(zones)
        }
    
    def _calculate_balance_score(self, zones: Dict[str, Any]) -> float:
        """Calculate visual balance score"""
        zone_stats = zones.get('zone_statistics', {})
        left_count = zone_stats.get('left_zone', {}).get('object_count', 0)
        right_count = zone_stats.get('right_zone', {}).get('object_count', 0)
        center_count = zone_stats.get('center_zone', {}).get('object_count', 0)
        
        total_objects = left_count + right_count + center_count
        if total_objects == 0:
            return 1.0
        
        # Calculate balance (1.0 = perfect balance, 0.0 = completely unbalanced)
        max_imbalance = max(abs(left_count - right_count), abs(left_count - center_count), abs(right_count - center_count))
        return max(0.0, 1.0 - (max_imbalance / total_objects))
    
    def _living_room_layout_recommendations(self, objects: List[Dict[str, Any]], 
                                          zones: Dict[str, Any], 
                                          layout_analysis: Dict[str, Any]) -> List[Dict[str, Any]]:
        """Generate living room specific layout recommendations"""
        recommendations = []
        
        if layout_analysis['center_usage'] > 0.3:
            recommendations.append({
                "recommendation_type": "traffic_flow",
                "priority": "medium",
                "description": "Create clear pathways through the living room",
                "recommendation": "Consider repositioning furniture to open up central walking areas",
                "reasoning": "Living rooms benefit from clear traffic flow for social interaction",
                "implementation_tips": ["Focus on creating conversation areas", "Maintain 3-foot pathways"]
            })
        
        seating_count = len([obj for obj in objects if obj['furniture_category'] == 'seating'])
        if seating_count >= 2:
            recommendations.append({
                "recommendation_type": "social_layout",
                "priority": "low",
                "description": "Optimize seating arrangement for conversation",
                "recommendation": "Arrange seating to encourage face-to-face interaction",
                "reasoning": "Conversation-friendly layouts make living rooms more inviting",
                "implementation_tips": ["Angle chairs toward each other", "Create intimate seating groups"]
            })
        
        return recommendations
    
    def _bedroom_layout_recommendations(self, objects: List[Dict[str, Any]], 
                                      zones: Dict[str, Any], 
                                      layout_analysis: Dict[str, Any]) -> List[Dict[str, Any]]:
        """Generate bedroom specific layout recommendations"""
        recommendations = []
        
        beds = [obj for obj in objects if obj['class_name'] == 'bed']
        if beds:
            recommendations.append({
                "recommendation_type": "sleep_optimization",
                "priority": "medium",
                "description": "Optimize bedroom layout for rest and relaxation",
                "recommendation": "Position bed to minimize disruptions and maximize comfort",
                "reasoning": "Bedroom layout significantly affects sleep quality and daily routines",
                "implementation_tips": ["Avoid placing bed directly opposite doors", "Ensure bedside access"]
            })
        
        return recommendations
    
    def _dining_room_layout_recommendations(self, objects: List[Dict[str, Any]], 
                                          zones: Dict[str, Any], 
                                          layout_analysis: Dict[str, Any]) -> List[Dict[str, Any]]:
        """Generate dining room specific layout recommendations"""
        recommendations = []
        
        tables = [obj for obj in objects if obj['class_name'] == 'dining_table']
        chairs = [obj for obj in objects if obj['class_name'] == 'chair']
        
        if tables and chairs:
            recommendations.append({
                "recommendation_type": "dining_functionality",
                "priority": "medium",
                "description": "Optimize dining area for comfort and accessibility",
                "recommendation": "Ensure adequate space around dining table for chair movement",
                "reasoning": "Proper dining layout improves meal experiences and accessibility",
                "implementation_tips": ["Allow 24-30 inches per person", "Maintain 36 inches behind chairs"]
            })
        
        return recommendations
    
    def _general_layout_recommendations(self, objects: List[Dict[str, Any]], 
                                      zones: Dict[str, Any], 
                                      layout_analysis: Dict[str, Any]) -> List[Dict[str, Any]]:
        """Generate general layout recommendations"""
        recommendations = []
        
        if layout_analysis['balance_score'] < 0.6:
            recommendations.append({
                "recommendation_type": "visual_balance",
                "priority": "low",
                "description": "Improve visual balance in the space",
                "recommendation": "Consider redistributing furniture for better visual harmony",
                "reasoning": "Balanced layouts create more pleasing and comfortable environments",
                "implementation_tips": ["Consider visual weight, not just quantity", "Use accessories to balance"]
            })
        
        return recommendations
    
    def _customize_with_user_preferences(self, guidance_list: List[Dict[str, Any]], 
                                       improvement_notes) -> List[Dict[str, Any]]:
        """Customize guidance based on user preferences"""
        # Safe string extraction for improvement_notes
        if isinstance(improvement_notes, dict):
            # Try to extract text from common dictionary keys
            notes_text = (improvement_notes.get('text', '') or 
                         improvement_notes.get('notes', '') or 
                         improvement_notes.get('description', '') or
                         str(improvement_notes))
        elif isinstance(improvement_notes, str):
            notes_text = improvement_notes
        else:
            notes_text = str(improvement_notes) if improvement_notes else ''
        
        notes_lower = notes_text.lower()
        
        # Add user preference context to relevant guidance
        for guidance in guidance_list:
            if 'storage' in notes_lower and 'storage' in guidance.get('recommendation', '').lower():
                guidance['user_preference_note'] = "Aligns with your storage improvement goals"
            elif 'space' in notes_lower and 'space' in guidance.get('recommendation', '').lower():
                guidance['user_preference_note'] = "Addresses your space optimization concerns"
            elif 'light' in notes_lower and 'light' in guidance.get('recommendation', '').lower():
                guidance['user_preference_note'] = "Supports your lighting improvement objectives"
        
        return guidance_list
    
    def _load_interior_design_rules(self) -> List[Dict[str, Any]]:
        """Load interior design heuristics"""
        return [
            {"rule": "wall_alignment", "priority": "high", "description": "Large furniture should align with walls"},
            {"rule": "pathway_clearance", "priority": "high", "description": "Maintain clear pathways"},
            {"rule": "natural_light", "priority": "medium", "description": "Avoid blocking natural light sources"},
            {"rule": "functional_grouping", "priority": "medium", "description": "Group related furniture"},
            {"rule": "visual_balance", "priority": "low", "description": "Distribute visual weight evenly"}
        ]
    
    def _load_spatial_reasoning_rules(self) -> List[Dict[str, Any]]:
        """Load spatial reasoning rules"""
        return [
            {"rule": "zone_optimization", "description": "Optimize furniture placement within spatial zones"},
            {"rule": "relationship_analysis", "description": "Analyze spatial relationships between objects"},
            {"rule": "traffic_flow", "description": "Ensure efficient traffic flow patterns"}
        ]
    
    def _load_safety_guidelines(self) -> List[Dict[str, Any]]:
        """Load safety guidelines"""
        return [
            {"guideline": "pathway_safety", "description": "Maintain safe walking pathways"},
            {"guideline": "furniture_stability", "description": "Ensure furniture stability and anchoring"},
            {"guideline": "lighting_safety", "description": "Maintain adequate lighting coverage"}
        ]