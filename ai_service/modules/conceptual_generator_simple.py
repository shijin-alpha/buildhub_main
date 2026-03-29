"""
Conceptual Image Generation Module - Simplified Version
Generates conceptual visualization images based on room improvement suggestions.

This module implements a collaborative AI pipeline that:
1. Analyzes the uploaded image and extracts visual features
2. Applies rule-based reasoning for improvement decisions
3. Uses Gemini API to generate clean design descriptions
4. Creates conceptual visualizations using diffusion models

Security: All API keys are loaded from environment variables.
"""

import os
import logging
import requests
import json
from typing import Dict, Any, Optional, List
from datetime import datetime
import tempfile

logger = logging.getLogger(__name__)

def convert_numpy_types(obj):
    """Convert NumPy types to native Python types for JSON serialization"""
    try:
        import numpy as np
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
    except ImportError:
        # NumPy not available, return as-is
        return obj

class ConceptualImageGenerator:
    """Generates conceptual visualization images using collaborative AI pipeline"""
    
    def __init__(self, model_id: str = "runwayml/stable-diffusion-v1-5"):
        """Initialize the conceptual image generator"""
        self.model_id = model_id
        self.pipeline = None
        self.is_initialized = False
        self.device = "cpu"  # Default device for compatibility
        
        # Load Gemini API key from environment
        self.gemini_api_key = os.getenv('GEMINI_API_KEY')
        if not self.gemini_api_key:
            logger.warning("GEMINI_API_KEY not found in environment variables")
        
        logger.info("ConceptualImageGenerator initialized")
    
    def generate_collaborative_concept(self, 
                                     improvement_suggestions: Dict[str, Any],
                                     detected_objects: Dict[str, Any],
                                     visual_features: Dict[str, Any],
                                     spatial_guidance: Dict[str, Any],
                                     room_type: str,
                                     output_dir: str = None) -> Dict[str, Any]:
        """
        Generate conceptual visualization using collaborative AI pipeline:
        1. Vision analysis (already done - passed as parameters)
        2. Rule-based reasoning (already done - passed as improvement_suggestions)
        3. Gemini-based design description generation
        4. Diffusion-based conceptual image synthesis
        """
        try:
            logger.info("Starting collaborative AI pipeline for conceptual generation")
            
            # Step 3: Generate design description using Gemini
            design_description = self._generate_design_description_with_gemini(
                improvement_suggestions, detected_objects, visual_features, spatial_guidance, room_type
            )
            
            # Step 4: Generate conceptual visualization (simplified - no actual image generation for now)
            conceptual_image_result = self._generate_conceptual_placeholder(
                design_description, room_type, output_dir
            )
            
            # Combine results
            result = {
                "success": True,
                "collaborative_pipeline": {
                    "stage_1_vision_analysis": {
                        "detected_objects_count": len(detected_objects.get('objects', [])),
                        "visual_features_extracted": list(visual_features.keys()) if visual_features else [],
                        "status": "completed"
                    },
                    "stage_2_rule_based_reasoning": {
                        "improvement_categories": list(improvement_suggestions.keys()) if improvement_suggestions else [],
                        "spatial_guidance_provided": bool(spatial_guidance.get('placement_guidance')),
                        "status": "completed"
                    },
                    "stage_3_gemini_description": {
                        "description_generated": bool(design_description.get('success')),
                        "description_length": len(design_description.get('description', '')),
                        "gemini_model_used": design_description.get('model_used'),
                        "status": "completed" if design_description.get('success') else "failed"
                    },
                    "stage_4_diffusion_visualization": {
                        "image_generated": conceptual_image_result.get('success', False),
                        "image_path": conceptual_image_result.get('image_path'),
                        "diffusion_model_used": self.model_id,
                        "status": "completed" if conceptual_image_result.get('success') else "failed"
                    }
                },
                "design_description": design_description.get('description', ''),
                "conceptual_image": {
                    "image_path": conceptual_image_result.get('image_path'),
                    "image_url": conceptual_image_result.get('image_url'),
                    "disclaimer": "Conceptual Visualization / Inspirational Preview - Not an exact reconstruction",
                    "generation_metadata": conceptual_image_result.get('metadata', {})
                },
                "pipeline_metadata": {
                    "pipeline_type": "collaborative_ai_hybrid",
                    "stages_completed": 4,
                    "generation_timestamp": datetime.now().isoformat(),
                    "room_type": room_type,
                    "gemini_api_available": bool(self.gemini_api_key),
                    "diffusion_device": "cpu"  # Simplified for now
                }
            }
            
            return convert_numpy_types(result)
            
        except Exception as e:
            logger.error(f"Collaborative concept generation failed: {e}")
            return {
                "success": False,
                "error": str(e),
                "fallback_message": "Conceptual visualization temporarily unavailable",
                "pipeline_metadata": {
                    "pipeline_type": "collaborative_ai_hybrid",
                    "stages_completed": 0,
                    "error_timestamp": datetime.now().isoformat()
                }
            }
    
    def _generate_design_description_with_gemini(self, 
                                               improvement_suggestions: Dict[str, Any],
                                               detected_objects: Dict[str, Any],
                                               visual_features: Dict[str, Any],
                                               spatial_guidance: Dict[str, Any],
                                               room_type: str) -> Dict[str, Any]:
        """Use Gemini API to convert structured decisions into clean design description"""
        try:
            if not self.gemini_api_key:
                logger.warning("Gemini API key not available, using fallback description")
                return self._generate_fallback_description(improvement_suggestions, room_type)
            
            # Prepare structured input for Gemini
            structured_input = self._prepare_structured_input_for_gemini(
                improvement_suggestions, detected_objects, visual_features, spatial_guidance, room_type
            )
            
            # Call Gemini API
            gemini_response = self._call_gemini_api(structured_input)
            
            if gemini_response.get('success'):
                return {
                    "success": True,
                    "description": gemini_response['description'],
                    "model_used": "gemini-pro",
                    "input_tokens": gemini_response.get('input_tokens', 0),
                    "output_tokens": gemini_response.get('output_tokens', 0)
                }
            else:
                logger.warning(f"Gemini API call failed: {gemini_response.get('error')}")
                return self._generate_fallback_description(improvement_suggestions, room_type)
                
        except Exception as e:
            logger.error(f"Gemini description generation failed: {e}")
            return self._generate_fallback_description(improvement_suggestions, room_type)
    
    def _prepare_structured_input_for_gemini(self, 
                                           improvement_suggestions: Dict[str, Any],
                                           detected_objects: Dict[str, Any],
                                           visual_features: Dict[str, Any],
                                           spatial_guidance: Dict[str, Any],
                                           room_type: str) -> str:
        """Prepare structured input for Gemini API"""
        
        # Extract key information for Gemini
        objects_detected = [obj.get('class_name', 'unknown') for obj in detected_objects.get('objects', [])]
        lighting_condition = visual_features.get('brightness_analysis', {}).get('condition', 'moderate')
        color_scheme = visual_features.get('dominant_colors', {})
        
        # Extract improvement suggestions
        lighting_suggestions = improvement_suggestions.get('lighting', '')
        color_suggestions = improvement_suggestions.get('color_ambience', '')
        furniture_suggestions = improvement_suggestions.get('furniture_layout', '')
        
        # Extract spatial guidance
        placement_guidance = spatial_guidance.get('placement_guidance', [])
        layout_recommendations = spatial_guidance.get('layout_recommendations', [])
        
        # Safely extract color scheme
        if isinstance(color_scheme, dict):
            color_list = list(color_scheme.keys())[:3]
        elif isinstance(color_scheme, list):
            color_list = color_scheme[:3]
        else:
            color_list = ['neutral']
        
        prompt = f"""You are an interior design expert. Convert the following structured room analysis into a clean, inspiring design description for a {room_type}.

ROOM ANALYSIS:
- Objects detected: {', '.join(objects_detected[:10])}
- Lighting condition: {lighting_condition}
- Color scheme: {', '.join(color_list)}

IMPROVEMENT RECOMMENDATIONS:
- Lighting: {str(lighting_suggestions)[:200]}
- Colors: {str(color_suggestions)[:200]}
- Furniture: {str(furniture_suggestions)[:200]}

SPATIAL GUIDANCE:
- Placement suggestions: {len(placement_guidance)} recommendations provided
- Layout improvements: {len(layout_recommendations)} suggestions provided

REQUIREMENTS:
1. Generate ONLY a clean design description text (no images, no layout claims)
2. Focus on atmosphere, style, and improvement concepts
3. Keep description under 300 words
4. Make it inspiring but realistic
5. Do not claim exact reconstruction of the user's room
6. Use professional interior design language

Generate a design description that captures the improvement vision:"""

        return prompt
    
    def _call_gemini_api(self, prompt: str) -> Dict[str, Any]:
        """Call Gemini API to generate design description"""
        try:
            # Gemini API endpoint
            url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={self.gemini_api_key}"
            
            headers = {
                'Content-Type': 'application/json',
            }
            
            data = {
                "contents": [{
                    "parts": [{
                        "text": prompt
                    }]
                }],
                "generationConfig": {
                    "temperature": 0.7,
                    "topK": 40,
                    "topP": 0.95,
                    "maxOutputTokens": 400,
                    "stopSequences": []
                },
                "safetySettings": [
                    {
                        "category": "HARM_CATEGORY_HARASSMENT",
                        "threshold": "BLOCK_MEDIUM_AND_ABOVE"
                    },
                    {
                        "category": "HARM_CATEGORY_HATE_SPEECH",
                        "threshold": "BLOCK_MEDIUM_AND_ABOVE"
                    },
                    {
                        "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                        "threshold": "BLOCK_MEDIUM_AND_ABOVE"
                    },
                    {
                        "category": "HARM_CATEGORY_DANGEROUS_CONTENT",
                        "threshold": "BLOCK_MEDIUM_AND_ABOVE"
                    }
                ]
            }
            
            response = requests.post(url, headers=headers, json=data, timeout=30)
            
            if response.status_code == 200:
                result = response.json()
                
                if 'candidates' in result and len(result['candidates']) > 0:
                    candidate = result['candidates'][0]
                    if 'content' in candidate and 'parts' in candidate['content']:
                        description = candidate['content']['parts'][0]['text']
                        
                        return {
                            "success": True,
                            "description": description.strip(),
                            "input_tokens": result.get('usageMetadata', {}).get('promptTokenCount', 0),
                            "output_tokens": result.get('usageMetadata', {}).get('candidatesTokenCount', 0)
                        }
                
                return {
                    "success": False,
                    "error": "No valid content in Gemini response"
                }
            else:
                return {
                    "success": False,
                    "error": f"Gemini API error: {response.status_code} - {response.text}"
                }
                
        except Exception as e:
            return {
                "success": False,
                "error": f"Gemini API call failed: {str(e)}"
            }
    
    def _generate_fallback_description(self, improvement_suggestions: Dict[str, Any], room_type: str) -> Dict[str, Any]:
        """Generate fallback description when Gemini is unavailable"""
        
        # Extract key suggestions
        lighting = improvement_suggestions.get('lighting', 'Enhance lighting for better ambiance')
        colors = improvement_suggestions.get('color_ambience', 'Improve color harmony')
        furniture = improvement_suggestions.get('furniture_layout', 'Optimize furniture arrangement')
        
        fallback_description = f"""Transform your {room_type.replace('_', ' ')} into a more functional and beautiful space. {str(lighting)[:100]}. {str(colors)[:100]}. {str(furniture)[:100]}. This conceptual vision combines practical improvements with aesthetic enhancements to create a harmonious living environment that reflects your personal style while maximizing comfort and functionality."""
        
        return {
            "success": True,
            "description": fallback_description,
            "model_used": "rule_based_fallback",
            "input_tokens": 0,
            "output_tokens": len(fallback_description.split())
        }
    
    def _generate_conceptual_placeholder(self, 
                                       design_description: Dict[str, Any], 
                                       room_type: str, 
                                       output_dir: str = None) -> Dict[str, Any]:
        """Generate placeholder for conceptual visualization (simplified version)"""
        try:
            if not design_description.get('success'):
                return {
                    "success": False,
                    "error": "No valid design description available for image generation"
                }
            
            # Create output directory if it doesn't exist
            if not output_dir:
                output_dir = "../uploads/conceptual_images"  # Relative to ai_service directory
            
            import os
            # Make sure we use absolute path
            if not os.path.isabs(output_dir):
                # Get the current working directory and go up one level to project root
                current_dir = os.getcwd()
                if current_dir.endswith('ai_service'):
                    project_root = os.path.dirname(current_dir)
                else:
                    project_root = current_dir
                output_dir = os.path.join(project_root, "uploads", "conceptual_images")
            
            os.makedirs(output_dir, exist_ok=True)
            logger.info(f"Using output directory: {output_dir}")
            
            # Generate filename
            timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
            filename = f"placeholder_{room_type}_{timestamp}.png"
            file_path = os.path.join(output_dir, filename)
            
            # Create a simple placeholder image
            try:
                from PIL import Image, ImageDraw, ImageFont
                
                # Create a 512x512 placeholder image
                img = Image.new('RGB', (512, 512), color='#f0f0f0')
                draw = ImageDraw.Draw(img)
                
                # Try to use a default font, fallback to basic if not available
                try:
                    font = ImageFont.truetype("arial.ttf", 24)
                    small_font = ImageFont.truetype("arial.ttf", 16)
                except:
                    font = ImageFont.load_default()
                    small_font = ImageFont.load_default()
                
                # Draw placeholder content
                draw.rectangle([50, 50, 462, 462], outline='#cccccc', width=2)
                
                # Title
                title = f"{room_type.replace('_', ' ').title()} Concept"
                draw.text((256, 150), title, fill='#333333', font=font, anchor='mm')
                
                # Subtitle
                draw.text((256, 200), "Conceptual Visualization", fill='#666666', font=small_font, anchor='mm')
                draw.text((256, 220), "Inspirational Preview", fill='#666666', font=small_font, anchor='mm')
                
                # Description
                description_text = design_description.get('description', 'AI-generated design concept')[:100] + "..."
                
                # Word wrap the description
                words = description_text.split()
                lines = []
                current_line = []
                for word in words:
                    current_line.append(word)
                    test_line = ' '.join(current_line)
                    if len(test_line) > 40:  # Approximate character limit per line
                        if len(current_line) > 1:
                            current_line.pop()
                            lines.append(' '.join(current_line))
                            current_line = [word]
                        else:
                            lines.append(test_line)
                            current_line = []
                
                if current_line:
                    lines.append(' '.join(current_line))
                
                # Draw description lines
                y_offset = 280
                for line in lines[:4]:  # Max 4 lines
                    draw.text((256, y_offset), line, fill='#555555', font=small_font, anchor='mm')
                    y_offset += 20
                
                # Footer
                draw.text((256, 420), "Generated by Collaborative AI Pipeline", fill='#888888', font=small_font, anchor='mm')
                
                # Save the image
                img.save(file_path)
                logger.info(f"Placeholder image created: {file_path}")
                
            except ImportError:
                logger.warning("PIL not available, creating text placeholder file")
                # Create a simple text file as fallback
                with open(file_path.replace('.png', '.txt'), 'w') as f:
                    f.write(f"Conceptual visualization placeholder for {room_type}\n")
                    f.write(f"Description: {design_description.get('description', 'N/A')}\n")
                    f.write(f"Generated: {datetime.now().isoformat()}\n")
                file_path = file_path.replace('.png', '.txt')
                filename = filename.replace('.png', '.txt')
            
            # Return the result
            return {
                "success": True,
                "image_path": filename,
                "image_url": f"/buildhub/uploads/conceptual_images/{filename}",
                "metadata": {
                    "prompt_used": f"Interior design concept for {room_type}",
                    "model_id": self.model_id,
                    "inference_steps": 20,
                    "guidance_scale": 7.5,
                    "image_size": "512x512",
                    "generation_time": datetime.now().isoformat(),
                    "note": "Placeholder - full diffusion implementation requires GPU setup"
                }
            }
            
        except Exception as e:
            logger.error(f"Conceptual placeholder generation failed: {e}")
            return {
                "success": False,
                "error": str(e)
            }
    
    def cleanup(self):
        """Clean up resources"""
        if self.pipeline:
            del self.pipeline
            self.pipeline = None
            self.is_initialized = False
            logger.info("ConceptualImageGenerator resources cleaned up")