"""
Conceptual Image Generation Module
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
import torch
from diffusers import StableDiffusionPipeline
from PIL import Image, ImageDraw, ImageFont
import tempfile

logger = logging.getLogger(__name__)

def convert_numpy_types(obj):
    """Convert NumPy types to native Python types for JSON serialization"""
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

class ConceptualImageGenerator:
    """Generates conceptual visualization images using collaborative AI pipeline"""
    
    def __init__(self, model_id: str = "runwayml/stable-diffusion-v1-5"):
        """
        Initialize the conceptual image generator
        
        Args:
            model_id: Hugging Face model ID for text-to-image generation
        """
        self.model_id = model_id
        self.pipeline = None
        self.device = "cuda" if torch.cuda.is_available() else "cpu"
        self.is_initialized = False
        
        # Load Gemini API key from environment
        self.gemini_api_key = os.getenv('GEMINI_API_KEY')
        if not self.gemini_api_key:
            logger.warning("GEMINI_API_KEY not found in environment variables")
        
        # Initialize pipeline lazily to avoid startup delays
        logger.info(f"ConceptualImageGenerator initialized with device: {self.device}")
    
    def _initialize_pipeline(self):
        """Initialize the Stable Diffusion pipeline"""
        if self.is_initialized:
            return
        
        try:
            logger.info("Loading Stable Diffusion pipeline...")
            
            # Load pipeline with optimizations for CPU/GPU
            if self.device == "cuda":
                self.pipeline = StableDiffusionPipeline.from_pretrained(
                    self.model_id,
                    torch_dtype=torch.float16,
                    safety_checker=None,
                    requires_safety_checker=False
                )
                self.pipeline = self.pipeline.to(self.device)
                # Enable memory efficient attention
                self.pipeline.enable_attention_slicing()
            else:
                # CPU optimization
                self.pipeline = StableDiffusionPipeline.from_pretrained(
                    self.model_id,
                    torch_dtype=torch.float32,
                    safety_checker=None,
                    requires_safety_checker=False
                )
                self.pipeline = self.pipeline.to(self.device)
            
            self.is_initialized = True
            logger.info("Stable Diffusion pipeline loaded successfully")
            
        except Exception as e:
            logger.error(f"Failed to initialize Stable Diffusion pipeline: {e}")
            raise
    
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
        
        Args:
            improvement_suggestions: Structured improvement suggestions from rule engine
            detected_objects: Objects detected in the room
            visual_features: Visual features extracted from image
            spatial_guidance: Spatial reasoning results
            room_type: Type of room being analyzed
            output_dir: Directory to save generated images
            
        Returns:
            Collaborative AI pipeline results with conceptual visualization
        """
        try:
            # Step 1 & 2: Vision analysis and reasoning already completed
            logger.info("Starting collaborative AI pipeline for conceptual generation")
            
            # Step 3: Generate design description using Gemini
            design_description = self._generate_design_description_with_gemini(
                improvement_suggestions, detected_objects, visual_features, spatial_guidance, room_type
            )
            
            # Step 4: Generate conceptual visualization using diffusion model
            conceptual_image_result = self._generate_conceptual_image_with_diffusion(
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
                    "diffusion_device": self.device
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
        """
        Use Gemini API to convert structured decisions into clean design description
        
        Args:
            improvement_suggestions: Rule-based improvement suggestions
            detected_objects: Detected objects in the room
            visual_features: Visual features from image analysis
            spatial_guidance: Spatial reasoning results
            room_type: Type of room
            
        Returns:
            Design description generated by Gemini
        """
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
        color_scheme = visual_features.get('dominant_colors', [])
        
        # Extract improvement suggestions
        lighting_suggestions = improvement_suggestions.get('lighting', '')
        color_suggestions = improvement_suggestions.get('color_ambience', '')
        furniture_suggestions = improvement_suggestions.get('furniture_layout', '')
        
        # Extract spatial guidance
        placement_guidance = spatial_guidance.get('placement_guidance', [])
        layout_recommendations = spatial_guidance.get('layout_recommendations', [])
        
        prompt = f"""You are an interior design expert. Convert the following structured room analysis into a clean, inspiring design description for a {room_type}.

ROOM ANALYSIS:
- Objects detected: {', '.join(objects_detected[:10])}  # Limit to avoid token overflow
- Lighting condition: {lighting_condition}
- Color scheme: {color_scheme[:3] if color_scheme else 'neutral'}

IMPROVEMENT RECOMMENDATIONS:
- Lighting: {lighting_suggestions[:200]}  # Limit length
- Colors: {color_suggestions[:200]}
- Furniture: {furniture_suggestions[:200]}

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
        
        fallback_description = f"""Transform your {room_type.replace('_', ' ')} into a more functional and beautiful space. {lighting[:100]}. {colors[:100]}. {furniture[:100]}. This conceptual vision combines practical improvements with aesthetic enhancements to create a harmonious living environment that reflects your personal style while maximizing comfort and functionality."""
        
        return {
            "success": True,
            "description": fallback_description,
            "model_used": "rule_based_fallback",
            "input_tokens": 0,
            "output_tokens": len(fallback_description.split())
        }
    
    def _generate_conceptual_image_with_diffusion(self, 
                                                design_description: Dict[str, Any], 
                                                room_type: str, 
                                                output_dir: str = None) -> Dict[str, Any]:
        """Generate conceptual visualization using Stable Diffusion"""
        try:
            if not design_description.get('success'):
                return {
                    "success": False,
                    "error": "No valid design description available for image generation"
                }
            
            # Initialize diffusion pipeline
            self._initialize_pipeline()
            
            # Prepare prompt for image generation
            description_text = design_description['description']
            image_prompt = self._prepare_image_prompt(description_text, room_type)
            
            # Generate image
            logger.info("Generating real AI conceptual visualization with Stable Diffusion...")
            
            with torch.no_grad():
                image = self.pipeline(
                    prompt=image_prompt,
                    negative_prompt="blurry, low quality, distorted, unrealistic, cartoon, anime, sketch, drawing",
                    num_inference_steps=25,  # Increased for better quality
                    guidance_scale=8.0,      # Increased for better prompt adherence
                    width=512,
                    height=512
                ).images[0]
            
            # Add disclaimer overlay
            labeled_image = self._add_disclaimer_overlay(image)
            
            # Save image to Apache document root
            if output_dir is None:
                # Default to Apache document root conceptual images directory
                output_dir = "C:/xampp/htdocs/buildhub/uploads/conceptual_images"
            
            os.makedirs(output_dir, exist_ok=True)
            
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            filename = f"conceptual_{room_type}_{timestamp}.png"
            image_path = os.path.join(output_dir, filename)
            
            # Save the generated image
            labeled_image.save(image_path, "PNG", quality=95)
            
            # Verify file exists and log details
            if not os.path.exists(image_path):
                raise Exception(f"Failed to save image to {image_path}")
            
            # Get file size for verification
            file_size = os.path.getsize(image_path)
            
            logger.info(f"🔍 [DEBUG] Real AI image saved successfully:")
            logger.info(f"🔍 [DEBUG] - Full absolute path: {image_path}")
            logger.info(f"🔍 [DEBUG] - File exists check: {os.path.exists(image_path)}")
            logger.info(f"🔍 [DEBUG] - File size: {file_size} bytes")
            logger.info(f"🔍 [DEBUG] - Image URL to return: /buildhub/uploads/conceptual_images/{filename}")
            
            return {
                "success": True,
                "image_path": image_path,
                "image_url": f"/buildhub/uploads/conceptual_images/{filename}",
                "disclaimer": "Conceptual Visualization / Inspirational Preview",
                "metadata": {
                    "prompt_used": image_prompt,
                    "model_id": self.model_id,
                    "inference_steps": 25,
                    "guidance_scale": 8.0,
                    "image_size": "512x512",
                    "generation_time": datetime.now().isoformat(),
                    "file_size_bytes": file_size
                }
            }
            
        except Exception as e:
            logger.error(f"Stable Diffusion image generation failed: {e}")
            return {
                "success": False,
                "error": str(e),
                "fallback_message": "Real AI image generation temporarily unavailable"
            }
    
    def _prepare_image_prompt(self, description_text: str, room_type: str) -> str:
        """Prepare optimized prompt for Stable Diffusion image generation"""
        
        # Extract key concepts from Gemini description
        key_concepts = []
        description_lower = description_text.lower()
        
        # Style detection
        if 'modern' in description_lower:
            key_concepts.append('modern interior design')
        if 'contemporary' in description_lower:
            key_concepts.append('contemporary style')
        if 'cozy' in description_lower:
            key_concepts.append('cozy atmosphere')
        if 'minimalist' in description_lower:
            key_concepts.append('minimalist design')
        if 'elegant' in description_lower:
            key_concepts.append('elegant decor')
        
        # Lighting detection
        if 'bright' in description_lower or 'light' in description_lower:
            key_concepts.append('bright natural lighting')
        if 'warm' in description_lower and 'light' in description_lower:
            key_concepts.append('warm lighting')
        if 'soft' in description_lower and 'light' in description_lower:
            key_concepts.append('soft ambient lighting')
        
        # Color detection
        if 'warm colors' in description_lower or 'warm tones' in description_lower:
            key_concepts.append('warm color palette')
        if 'cool colors' in description_lower or 'cool tones' in description_lower:
            key_concepts.append('cool color scheme')
        if 'neutral' in description_lower:
            key_concepts.append('neutral colors')
        
        # Build optimized prompt for Stable Diffusion
        room_name = room_type.replace('_', ' ')
        
        # Base prompt with high-quality modifiers
        base_prompt = f"Beautiful {room_name} interior design, professional photography, high resolution, photorealistic"
        
        # Add extracted concepts
        if key_concepts:
            concept_text = ', '.join(key_concepts[:4])  # Limit to avoid token overflow
            prompt = f"{base_prompt}, {concept_text}"
        else:
            prompt = f"{base_prompt}, elegant design, comfortable atmosphere"
        
        # Add quality enhancers
        quality_enhancers = [
            "architectural digest style",
            "realistic lighting and shadows",
            "detailed textures",
            "clean and organized",
            "professional interior photography"
        ]
        
        final_prompt = f"{prompt}, {', '.join(quality_enhancers[:3])}"
        
        # Ensure prompt is within reasonable length for Stable Diffusion
        if len(final_prompt) > 250:
            final_prompt = final_prompt[:250].rsplit(',', 1)[0]  # Cut at last comma
        
        return final_prompt
    
    def _add_disclaimer_overlay(self, image: Image.Image) -> Image.Image:
        """Add disclaimer overlay to generated image"""
        try:
            # Create a copy to avoid modifying original
            labeled_image = image.copy()
            draw = ImageDraw.Draw(labeled_image)
            
            # Try to load a font, fallback to default if not available
            try:
                font = ImageFont.truetype("arial.ttf", 16)
            except:
                font = ImageFont.load_default()
            
            # Add semi-transparent background for text
            overlay = Image.new('RGBA', labeled_image.size, (0, 0, 0, 0))
            overlay_draw = ImageDraw.Draw(overlay)
            
            text = "Conceptual Visualization / Inspirational Preview"
            
            # Get text size
            bbox = draw.textbbox((0, 0), text, font=font)
            text_width = bbox[2] - bbox[0]
            text_height = bbox[3] - bbox[1]
            
            # Position at bottom center
            x = (labeled_image.width - text_width) // 2
            y = labeled_image.height - text_height - 10
            
            # Draw semi-transparent background
            overlay_draw.rectangle([x-5, y-2, x+text_width+5, y+text_height+2], fill=(0, 0, 0, 128))
            
            # Composite overlay
            labeled_image = Image.alpha_composite(labeled_image.convert('RGBA'), overlay)
            
            # Draw text
            draw = ImageDraw.Draw(labeled_image)
            draw.text((x, y), text, fill=(255, 255, 255, 255), font=font)
            
            return labeled_image.convert('RGB')
            
        except Exception as e:
            logger.warning(f"Failed to add disclaimer overlay: {e}")
            return image
            detected_objects: Detected objects from image analysis
            visual_features: Visual features from image analysis
            room_type: Type of room being improved
            output_dir: Directory to save generated image
            
        Returns:
            Dictionary containing generation results and metadata
        """
        try:
            # Initialize pipeline if needed
            self._initialize_pipeline()
            
            # Convert suggestions to structured design description
            design_description = self._create_design_description(
                improvement_suggestions, detected_objects, visual_features, room_type
            )
            
            # Generate text prompt for image generation
            text_prompt = self._create_text_prompt(design_description, room_type)
            
            # Generate conceptual image
            generated_image = self._generate_image(text_prompt)
            
            # Save image if output directory provided
            image_path = None
            if output_dir and generated_image:
                image_path = self._save_generated_image(generated_image, output_dir, room_type)
            
            result = {
                "status": "success",
                "success": True,
                "concept_image": {
                    "type": "conceptual_visualization",
                    "image_path": image_path,
                    "description": "Conceptual visualization based on improvement suggestions"
                },
                "design_description": design_description,
                "text_prompt": text_prompt,
                "image_generated": generated_image is not None,
                "image_path": image_path,
                "generation_metadata": {
                    "model_id": self.model_id,
                    "device": self.device,
                    "generation_timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                    "room_type": room_type,
                    "prompt_length": len(text_prompt)
                },
                "disclaimer": {
                    "type": "conceptual_visualization",
                    "description": "This is an AI-generated conceptual visualization based on improvement suggestions",
                    "note": "The generated image is inspirational and may not exactly represent your actual room",
                    "recommendation": "Use this visualization as design inspiration and consult with professionals for implementation"
                }
            }
            
            return convert_numpy_types(result)
            
        except Exception as e:
            logger.error(f"Conceptual image generation failed: {e}")
            return {
                "status": "error",
                "success": False,
                "error": str(e),
                "concept_image": None,
                "design_description": {},
                "text_prompt": "",
                "image_generated": False,
                "image_path": None,
                "generation_metadata": {
                    "error": str(e),
                    "generation_timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                }
            }
    
    def _create_design_description(self, 
                                 improvement_suggestions: Dict[str, Any],
                                 detected_objects: Dict[str, Any],
                                 visual_features: Dict[str, Any],
                                 room_type: str) -> Dict[str, Any]:
        """Create structured design description from analysis results"""
        
        # Defensive handling: ensure improvement_suggestions is a dictionary
        if isinstance(improvement_suggestions, list):
            # Convert list to dictionary with default keys
            suggestions_dict = {}
            if len(improvement_suggestions) > 0:
                suggestions_dict['lighting'] = improvement_suggestions[0] if len(improvement_suggestions) > 0 else ''
            if len(improvement_suggestions) > 1:
                suggestions_dict['color_ambience'] = improvement_suggestions[1] if len(improvement_suggestions) > 1 else ''
            if len(improvement_suggestions) > 2:
                suggestions_dict['furniture_layout'] = improvement_suggestions[2] if len(improvement_suggestions) > 2 else ''
            improvement_suggestions = suggestions_dict
        elif not isinstance(improvement_suggestions, dict):
            improvement_suggestions = {}
        
        # Defensive handling: ensure detected_objects is a dictionary
        if not isinstance(detected_objects, dict):
            detected_objects = {'objects': [], 'summary': {}}
        
        # Defensive handling: ensure visual_features is a dictionary
        if not isinstance(visual_features, dict):
            visual_features = {}
        
        # Extract key information
        objects_summary = detected_objects.get('summary', {})
        major_items = objects_summary.get('major_items', [])
        
        # Extract visual characteristics
        brightness_info = visual_features.get('brightness_enhanced', {})
        color_info = visual_features.get('color_analysis_enhanced', {})
        
        # Extract improvement suggestions with safe access
        lighting_suggestions = improvement_suggestions.get('lighting', '')
        color_suggestions = improvement_suggestions.get('color_ambience', '')
        furniture_suggestions = improvement_suggestions.get('furniture_layout', '')
        style_info = improvement_suggestions.get('style_recommendation', {})
        
        design_description = {
            "room_characteristics": {
                "room_type": room_type,
                "detected_furniture": [item.get('class_name', '') for item in major_items if isinstance(item, dict)],
                "lighting_condition": self._extract_lighting_condition(brightness_info),
                "color_palette": self._extract_color_palette(color_info),
                "current_style": style_info.get('style', 'contemporary') if isinstance(style_info, dict) else 'contemporary'
            },
            "improvement_directions": {
                "lighting_mood": self._extract_lighting_mood(lighting_suggestions),
                "color_direction": self._extract_color_direction(color_suggestions),
                "layout_principles": self._extract_layout_principles(furniture_suggestions),
                "style_enhancement": self._extract_style_enhancement(style_info)
            },
            "design_goals": {
                "primary_focus": self._determine_primary_focus(improvement_suggestions),
                "ambience_target": self._determine_ambience_target(color_suggestions, lighting_suggestions),
                "functionality_goals": self._extract_functionality_goals(furniture_suggestions)
            }
        }
        
        return design_description
    
    def _create_text_prompt(self, design_description: Dict[str, Any], room_type: str) -> str:
        """Create text prompt for image generation from design description"""
        
        room_char = design_description.get('room_characteristics', {})
        improvements = design_description.get('improvement_directions', {})
        goals = design_description.get('design_goals', {})
        
        # Base room description
        prompt_parts = [
            f"A beautifully designed {room_type.replace('_', ' ')}",
            f"in {room_char.get('current_style', 'contemporary')} style"
        ]
        
        # Add lighting mood
        lighting_mood = improvements.get('lighting_mood', '')
        if lighting_mood:
            prompt_parts.append(f"with {lighting_mood} lighting")
        
        # Add color direction
        color_direction = improvements.get('color_direction', '')
        if color_direction:
            prompt_parts.append(f"featuring {color_direction} color scheme")
        
        # Add furniture elements
        furniture = room_char.get('detected_furniture', [])
        if furniture:
            furniture_str = ', '.join(furniture[:3])  # Limit to 3 items
            prompt_parts.append(f"including {furniture_str}")
        
        # Add style enhancement
        style_enhancement = improvements.get('style_enhancement', '')
        if style_enhancement:
            prompt_parts.append(style_enhancement)
        
        # Add ambience target
        ambience = goals.get('ambience_target', '')
        if ambience:
            prompt_parts.append(f"creating a {ambience} atmosphere")
        
        # Quality and style modifiers
        quality_modifiers = [
            "professional interior design",
            "high quality",
            "well-lit",
            "spacious",
            "clean and organized",
            "photorealistic"
        ]
        
        # Combine all parts
        main_prompt = ', '.join(prompt_parts)
        full_prompt = f"{main_prompt}, {', '.join(quality_modifiers)}"
        
        # Ensure prompt is not too long (Stable Diffusion has token limits)
        if len(full_prompt) > 200:
            full_prompt = full_prompt[:200] + "..."
        
        return full_prompt
    
    def _generate_image(self, text_prompt: str) -> Optional[Image.Image]:
        """Generate image using Stable Diffusion"""
        try:
            if not self.pipeline:
                raise Exception("Pipeline not initialized")
            
            logger.info(f"Generating image with prompt: {text_prompt}")
            
            # Generation parameters
            generation_params = {
                "prompt": text_prompt,
                "num_inference_steps": 20,  # Reduced for faster generation
                "guidance_scale": 7.5,
                "width": 512,
                "height": 512,
                "num_images_per_prompt": 1
            }
            
            # Add negative prompt to improve quality
            negative_prompt = "blurry, low quality, distorted, ugly, cluttered, messy, dark, poorly lit"
            generation_params["negative_prompt"] = negative_prompt
            
            # Generate image
            with torch.no_grad():
                result = self.pipeline(**generation_params)
                generated_image = result.images[0]
            
            logger.info("Image generated successfully")
            return generated_image
            
        except Exception as e:
            logger.error(f"Image generation failed: {e}")
            return None
    
    def _save_generated_image(self, image: Image.Image, output_dir: str, room_type: str) -> str:
        """Save generated image to file"""
        try:
            # Create output directory if it doesn't exist
            os.makedirs(output_dir, exist_ok=True)
            
            # Generate filename
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            filename = f"conceptual_{room_type}_{timestamp}.png"
            file_path = os.path.join(output_dir, filename)
            
            # Save image
            image.save(file_path, "PNG", quality=95)
            
            logger.info(f"Generated image saved to: {file_path}")
            return file_path
            
        except Exception as e:
            logger.error(f"Failed to save generated image: {e}")
            return None
    
    # Helper methods for extracting design elements
    def _extract_lighting_condition(self, brightness_info: Dict[str, Any]) -> str:
        """Extract lighting condition from brightness analysis"""
        consensus = brightness_info.get('consensus_category', 'moderate')
        lighting_quality = brightness_info.get('lighting_quality', 'balanced')
        
        if consensus in ['dark', 'dim']:
            return 'insufficient natural light'
        elif consensus in ['bright', 'very_bright']:
            return 'abundant natural light'
        else:
            return 'moderate natural light'
    
    def _extract_color_palette(self, color_info: Dict[str, Any]) -> str:
        """Extract color palette from color analysis"""
        temp_analysis = color_info.get('color_temperature_analysis', {})
        temp_category = temp_analysis.get('temperature_category', 'neutral')
        
        if temp_category == 'warm':
            return 'warm tones'
        elif temp_category == 'cool':
            return 'cool tones'
        else:
            return 'neutral tones'
    
    def _extract_lighting_mood(self, lighting_suggestions) -> str:
        """Extract lighting mood from suggestions"""
        # Safe string extraction
        if isinstance(lighting_suggestions, dict):
            # Try to extract text from common dictionary keys
            text = (lighting_suggestions.get('text', '') or 
                   lighting_suggestions.get('description', '') or 
                   lighting_suggestions.get('suggestion', '') or
                   str(lighting_suggestions.get('lighting', '')))
        elif isinstance(lighting_suggestions, str):
            text = lighting_suggestions
        else:
            text = str(lighting_suggestions) if lighting_suggestions else ''
        
        suggestions_lower = text.lower()
        
        if 'bright' in suggestions_lower or 'abundant' in suggestions_lower:
            return 'bright and airy'
        elif 'warm' in suggestions_lower or 'cozy' in suggestions_lower:
            return 'warm and inviting'
        elif 'soft' in suggestions_lower or 'diffused' in suggestions_lower:
            return 'soft and diffused'
        else:
            return 'well-balanced'
    
    def _extract_color_direction(self, color_suggestions) -> str:
        """Extract color direction from suggestions"""
        # Safe string extraction
        if isinstance(color_suggestions, dict):
            # Try to extract text from common dictionary keys
            text = (color_suggestions.get('text', '') or 
                   color_suggestions.get('description', '') or 
                   color_suggestions.get('suggestion', '') or
                   str(color_suggestions.get('color_ambience', '')))
        elif isinstance(color_suggestions, str):
            text = color_suggestions
        else:
            text = str(color_suggestions) if color_suggestions else ''
        
        suggestions_lower = text.lower()
        
        if 'warm' in suggestions_lower:
            return 'warm and inviting'
        elif 'cool' in suggestions_lower:
            return 'cool and calming'
        elif 'neutral' in suggestions_lower:
            return 'neutral and sophisticated'
        elif 'accent' in suggestions_lower:
            return 'with colorful accents'
        else:
            return 'harmonious'
    
    def _extract_layout_principles(self, furniture_suggestions) -> str:
        """Extract layout principles from furniture suggestions"""
        # Safe string extraction
        if isinstance(furniture_suggestions, dict):
            # Try to extract text from common dictionary keys
            text = (furniture_suggestions.get('text', '') or 
                   furniture_suggestions.get('description', '') or 
                   furniture_suggestions.get('suggestion', '') or
                   str(furniture_suggestions.get('furniture_layout', '')))
        elif isinstance(furniture_suggestions, str):
            text = furniture_suggestions
        else:
            text = str(furniture_suggestions) if furniture_suggestions else ''
        
        suggestions_lower = text.lower()
        
        principles = []
        if 'wall' in suggestions_lower:
            principles.append('wall-aligned furniture')
        if 'open' in suggestions_lower or 'space' in suggestions_lower:
            principles.append('open floor plan')
        if 'conversation' in suggestions_lower or 'group' in suggestions_lower:
            principles.append('conversation-friendly arrangement')
        
        return ', '.join(principles) if principles else 'functional layout'
    
    def _extract_style_enhancement(self, style_info) -> str:
        """Extract style enhancement from style information"""
        # Safe extraction of style and confidence
        if isinstance(style_info, dict):
            style = style_info.get('style', 'contemporary')
            confidence = style_info.get('confidence', 0)
        elif isinstance(style_info, str):
            style = style_info
            confidence = 50  # Default confidence for string input
        else:
            style = 'contemporary'
            confidence = 0
        
        # Ensure style is a string before calling .lower()
        if not isinstance(style, str):
            style = str(style) if style else 'contemporary'
        
        if confidence > 70:
            return f"strong {style.lower()} design elements"
        elif confidence > 40:
            return f"subtle {style.lower()} influences"
        else:
            return "versatile design approach"
    
    def _determine_primary_focus(self, improvement_suggestions: Dict[str, Any]) -> str:
        """Determine primary focus from improvement suggestions"""
        suggestions = improvement_suggestions
        
        if 'lighting' in suggestions and len(suggestions.get('lighting', '')) > 100:
            return 'lighting enhancement'
        elif 'color_ambience' in suggestions and len(suggestions.get('color_ambience', '')) > 100:
            return 'color and ambience'
        elif 'furniture_layout' in suggestions and len(suggestions.get('furniture_layout', '')) > 100:
            return 'furniture arrangement'
        else:
            return 'overall improvement'
    
    def _determine_ambience_target(self, color_suggestions, lighting_suggestions) -> str:
        """Determine target ambience from suggestions"""
        # Safe string extraction for color_suggestions
        if isinstance(color_suggestions, dict):
            color_text = (color_suggestions.get('text', '') or 
                         color_suggestions.get('description', '') or 
                         color_suggestions.get('suggestion', '') or
                         str(color_suggestions.get('color_ambience', '')))
        elif isinstance(color_suggestions, str):
            color_text = color_suggestions
        else:
            color_text = str(color_suggestions) if color_suggestions else ''
        
        # Safe string extraction for lighting_suggestions
        if isinstance(lighting_suggestions, dict):
            lighting_text = (lighting_suggestions.get('text', '') or 
                           lighting_suggestions.get('description', '') or 
                           lighting_suggestions.get('suggestion', '') or
                           str(lighting_suggestions.get('lighting', '')))
        elif isinstance(lighting_suggestions, str):
            lighting_text = lighting_suggestions
        else:
            lighting_text = str(lighting_suggestions) if lighting_suggestions else ''
        
        combined_text = (color_text + ' ' + lighting_text).lower()
        
        if 'cozy' in combined_text or 'warm' in combined_text:
            return 'cozy and welcoming'
        elif 'modern' in combined_text or 'contemporary' in combined_text:
            return 'modern and sophisticated'
        elif 'calm' in combined_text or 'peaceful' in combined_text:
            return 'calm and serene'
        elif 'bright' in combined_text or 'energetic' in combined_text:
            return 'bright and energetic'
        else:
            return 'comfortable and inviting'
    
    def _extract_functionality_goals(self, furniture_suggestions) -> List[str]:
        """Extract functionality goals from furniture suggestions"""
        # Safe string extraction
        if isinstance(furniture_suggestions, dict):
            # Try to extract text from common dictionary keys
            text = (furniture_suggestions.get('text', '') or 
                   furniture_suggestions.get('description', '') or 
                   furniture_suggestions.get('suggestion', '') or
                   str(furniture_suggestions.get('furniture_layout', '')))
        elif isinstance(furniture_suggestions, str):
            text = furniture_suggestions
        else:
            text = str(furniture_suggestions) if furniture_suggestions else ''
        
        suggestions_lower = text.lower()
        goals = []
        
        if 'traffic' in suggestions_lower or 'pathway' in suggestions_lower:
            goals.append('improved traffic flow')
        if 'storage' in suggestions_lower:
            goals.append('better storage solutions')
        if 'conversation' in suggestions_lower or 'social' in suggestions_lower:
            goals.append('enhanced social interaction')
        if 'space' in suggestions_lower:
            goals.append('optimized space utilization')
        
        return goals if goals else ['improved functionality']
    
    def cleanup(self):
        """Clean up resources"""
        if self.pipeline:
            del self.pipeline
            self.pipeline = None
            self.is_initialized = False
            
            # Clear GPU memory if using CUDA
            if torch.cuda.is_available():
                torch.cuda.empty_cache()
            
            logger.info("ConceptualImageGenerator resources cleaned up")