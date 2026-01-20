"""
Conceptual Image Generation Module - Real Stable Diffusion Version
Generates real AI interior images using Stable Diffusion based on room improvement suggestions.

This module implements a collaborative AI pipeline that:
1. Analyzes the uploaded image and extracts visual features
2. Applies rule-based reasoning for improvement decisions
3. Uses Gemini API to generate clean design descriptions
4. Creates real conceptual visualizations using Stable Diffusion

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
    """Generates real AI conceptual visualization images using Stable Diffusion"""
    
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
        
        logger.info("ConceptualImageGenerator initialized for real AI image generation")
    
    def _initialize_diffusion_pipeline(self):
        """Initialize Stable Diffusion pipeline for real image generation"""
        if self.is_initialized:
            return
        
        try:
            logger.info("Loading Stable Diffusion pipeline for real AI image generation...")
            
            # Try to import diffusion libraries
            import torch
            from diffusers import StableDiffusionPipeline
            
            # Detect device
            self.device = "cuda" if torch.cuda.is_available() else "cpu"
            logger.info(f"Using device: {self.device}")
            
            # Load pipeline with optimizations
            if self.device == "cuda":
                self.pipeline = StableDiffusionPipeline.from_pretrained(
                    self.model_id,
                    torch_dtype=torch.float16,
                    safety_checker=None,
                    requires_safety_checker=False
                )
                self.pipeline = self.pipeline.to(self.device)
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
            logger.info("✅ Stable Diffusion pipeline loaded successfully for real AI image generation")
            
        except ImportError as e:
            logger.error(f"❌ Failed to import diffusion libraries: {e}")
            logger.info("Falling back to placeholder generation")
            self.is_initialized = False
        except Exception as e:
            logger.error(f"❌ Failed to initialize Stable Diffusion pipeline: {e}")
            logger.info("Falling back to placeholder generation")
            self.is_initialized = False
    
    def generate_collaborative_concept(self, 
                                     improvement_suggestions: Dict[str, Any],
                                     detected_objects: Dict[str, Any],
                                     visual_features: Dict[str, Any],
                                     spatial_guidance: Dict[str, Any],
                                     room_type: str,
                                     output_dir: str = None) -> Dict[str, Any]:
        """
        Generate real AI conceptual visualization using collaborative AI pipeline:
        1. Vision analysis (already done - passed as parameters)
        2. Rule-based reasoning (already done - passed as improvement_suggestions)
        3. Gemini-based design description generation
        4. Real Stable Diffusion-based conceptual image synthesis
        """
        try:
            logger.info("🎨 Starting collaborative AI pipeline for REAL AI image generation")
            
            # Step 3: Generate design description using Gemini
            design_description = self._generate_design_description_with_gemini(
                improvement_suggestions, detected_objects, visual_features, spatial_guidance, room_type
            )
            
            # Step 4: Generate REAL conceptual visualization using Stable Diffusion
            conceptual_image_result = self._generate_real_ai_image(
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
                        "generation_type": "real_stable_diffusion" if self.is_initialized else "placeholder_fallback",
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
                    "pipeline_type": "collaborative_ai_hybrid_real_diffusion",
                    "stages_completed": 4,
                    "generation_timestamp": datetime.now().isoformat(),
                    "room_type": room_type,
                    "gemini_api_available": bool(self.gemini_api_key),
                    "diffusion_device": self.device,
                    "real_ai_generation": self.is_initialized
                }
            }
            
            return convert_numpy_types(result)
            
        except Exception as e:
            logger.error(f"❌ Collaborative concept generation failed: {e}")
            return {
                "success": False,
                "error": str(e),
                "fallback_message": "Real AI image generation temporarily unavailable",
                "pipeline_metadata": {
                    "pipeline_type": "collaborative_ai_hybrid_real_diffusion",
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
        """Prepare structured input for Gemini API with detailed room analysis"""
        
        # Extract key information for Gemini
        objects_detected = [obj.get('class_name', 'unknown') for obj in detected_objects.get('objects', [])]
        
        # Extract visual features with better handling
        brightness_analysis = visual_features.get('brightness_analysis', {})
        lighting_condition = brightness_analysis.get('condition', 'moderate')
        brightness_level = brightness_analysis.get('level', 'unknown')
        
        color_scheme = visual_features.get('dominant_colors', {})
        color_temperature = visual_features.get('color_temperature', {})
        contrast_level = visual_features.get('contrast', 'moderate')
        
        # Extract improvement suggestions with better handling
        lighting_suggestions = improvement_suggestions.get('lighting', '')
        color_suggestions = improvement_suggestions.get('color_ambience', '')
        furniture_suggestions = improvement_suggestions.get('furniture_layout', '')
        
        # Extract spatial guidance
        placement_guidance = spatial_guidance.get('placement_guidance', [])
        layout_recommendations = spatial_guidance.get('layout_recommendations', [])
        improvement_priorities = spatial_guidance.get('improvement_suggestions', [])
        
        # Safely extract color scheme
        if isinstance(color_scheme, dict):
            color_list = list(color_scheme.keys())[:3]
        elif isinstance(color_scheme, list):
            color_list = color_scheme[:3]
        else:
            color_list = ['neutral tones']
        
        # Extract color temperature info
        color_temp_category = color_temperature.get('category', 'neutral') if isinstance(color_temperature, dict) else 'neutral'
        
        # Build comprehensive prompt for Gemini
        prompt = f"""You are an expert interior designer analyzing a {room_type} from an uploaded photo. Create a detailed design description that will be used to generate an AI visualization of the improved space.

CURRENT ROOM ANALYSIS FROM UPLOADED IMAGE:
- Room Type: {room_type.replace('_', ' ').title()}
- Objects Detected: {', '.join(objects_detected[:8]) if objects_detected else 'basic room elements'}
- Current Lighting: {lighting_condition} (brightness level: {brightness_level})
- Color Scheme: {', '.join(color_list)} with {color_temp_category} temperature
- Visual Contrast: {contrast_level}

SPECIFIC IMPROVEMENT RECOMMENDATIONS BASED ON ANALYSIS:
- Lighting Improvements: {str(lighting_suggestions)[:300]}
- Color & Ambience Changes: {str(color_suggestions)[:300]}
- Furniture & Layout Adjustments: {str(furniture_suggestions)[:300]}

SPATIAL ANALYSIS INSIGHTS:
- Placement Recommendations: {len(placement_guidance)} specific suggestions
- Layout Improvements: {len(layout_recommendations)} recommendations
- Priority Improvements: {len(improvement_priorities)} key areas identified

TASK: Create a comprehensive design description that:
1. Acknowledges the current state of the uploaded room
2. Incorporates the specific improvement suggestions from the analysis
3. Describes the visual transformation in detail
4. Focuses on atmosphere, lighting, colors, and spatial arrangement
5. Uses professional interior design terminology
6. Keeps description under 400 words
7. Makes it inspiring and achievable

The description will be used to generate an AI visualization, so include:
- Specific lighting improvements (natural light, fixtures, ambiance)
- Detailed color palette changes and material suggestions
- Furniture arrangement and spatial flow improvements
- Texture and material recommendations
- Overall style and atmosphere goals

Generate a design description that captures the complete transformation vision based on the uploaded room analysis:"""

        return prompt
    
    def _call_gemini_api(self, prompt: str) -> Dict[str, Any]:
        """Call Gemini API to generate design description"""
        try:
            # Gemini API endpoint - Updated to use correct model
            url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={self.gemini_api_key}"
            
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
    
    def _generate_real_ai_image(self, 
                               design_description: Dict[str, Any], 
                               room_type: str, 
                               output_dir: str = None) -> Dict[str, Any]:
        """Generate REAL AI conceptual visualization using Stable Diffusion"""
        try:
            if not design_description.get('success'):
                return {
                    "success": False,
                    "error": "No valid design description available for image generation"
                }
            
            # Try to initialize Stable Diffusion pipeline
            self._initialize_diffusion_pipeline()
            
            if not self.is_initialized:
                logger.warning("⚠️ Stable Diffusion not available, creating enhanced placeholder")
                return self._generate_enhanced_placeholder(design_description, room_type, output_dir)
            
            # Prepare prompt for REAL AI image generation
            description_text = design_description['description']
            image_prompt = self._prepare_optimized_prompt(description_text, room_type)
            
            # Generate REAL AI image using Stable Diffusion
            logger.info("🎨 Generating REAL AI conceptual visualization with Stable Diffusion...")
            
            import torch
            with torch.no_grad():
                image = self.pipeline(
                    prompt=image_prompt,
                    negative_prompt="blurry, low quality, distorted, unrealistic, cartoon, anime, sketch, drawing, text, watermark",
                    num_inference_steps=25,  # Good quality vs speed balance
                    guidance_scale=8.0,      # Strong prompt adherence
                    width=512,
                    height=512
                ).images[0]
            
            # Add disclaimer overlay
            labeled_image = self._add_disclaimer_overlay(image)
            
            # Save image to Apache document root
            if output_dir is None:
                # Use absolute path to Apache document root
                output_dir = os.path.abspath("C:/xampp/htdocs/buildhub/uploads/conceptual_images")
            
            # Ensure we're using absolute path
            if not os.path.isabs(output_dir):
                output_dir = os.path.abspath(os.path.join("C:/xampp/htdocs/buildhub", output_dir))
            
            os.makedirs(output_dir, exist_ok=True)
            
            logger.info(f"🔍 [DEBUG] Using absolute output directory: {output_dir}")
            
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            filename = f"real_ai_{room_type}_{timestamp}.png"
            image_path = os.path.join(output_dir, filename)
            
            # Save the REAL AI generated image
            labeled_image.save(image_path, "PNG", quality=95)
            
            # Verify file exists
            if not os.path.exists(image_path):
                raise Exception(f"Failed to save image to {image_path}")
            
            file_size = os.path.getsize(image_path)
            
            logger.info(f"✅ REAL AI image saved successfully:")
            logger.info(f"   - Path: {image_path}")
            logger.info(f"   - Size: {file_size} bytes")
            logger.info(f"   - URL: /buildhub/uploads/conceptual_images/{filename}")
            
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
                    "file_size_bytes": file_size,
                    "generation_type": "real_stable_diffusion",
                    "device": self.device
                }
            }
            
        except Exception as e:
            logger.error(f"❌ Real AI image generation failed: {e}")
            logger.info("🔄 Falling back to enhanced placeholder")
            return self._generate_enhanced_placeholder(design_description, room_type, output_dir)
    
    def _prepare_optimized_prompt(self, description_text: str, room_type: str) -> str:
        """Prepare optimized prompt for Stable Diffusion image generation using specific room analysis"""
        
        # Extract key concepts from the Gemini-generated description
        key_concepts = []
        description_lower = description_text.lower()
        
        # Style detection from analysis
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
        if 'rustic' in description_lower:
            key_concepts.append('rustic charm')
        if 'industrial' in description_lower:
            key_concepts.append('industrial style')
        if 'scandinavian' in description_lower:
            key_concepts.append('scandinavian design')
        
        # Lighting analysis from room analysis
        if 'bright' in description_lower or 'natural light' in description_lower:
            key_concepts.append('bright natural lighting')
        if 'warm light' in description_lower or 'soft light' in description_lower:
            key_concepts.append('warm ambient lighting')
        if 'dim' in description_lower or 'dark' in description_lower:
            key_concepts.append('enhanced lighting solutions')
        if 'layered light' in description_lower:
            key_concepts.append('layered lighting design')
        
        # Color analysis from room analysis
        if 'neutral' in description_lower:
            key_concepts.append('neutral color palette')
        if 'warm color' in description_lower or 'warm tone' in description_lower:
            key_concepts.append('warm color scheme')
        if 'cool color' in description_lower or 'cool tone' in description_lower:
            key_concepts.append('cool color palette')
        if 'bold color' in description_lower or 'vibrant' in description_lower:
            key_concepts.append('vibrant accent colors')
        
        # Furniture and layout analysis
        if 'spacious' in description_lower or 'open' in description_lower:
            key_concepts.append('spacious layout')
        if 'compact' in description_lower or 'small space' in description_lower:
            key_concepts.append('space-efficient design')
        if 'storage' in description_lower:
            key_concepts.append('organized storage solutions')
        if 'seating' in description_lower:
            key_concepts.append('comfortable seating arrangement')
        
        # Material and texture analysis
        if 'wood' in description_lower:
            key_concepts.append('natural wood elements')
        if 'metal' in description_lower:
            key_concepts.append('metal accents')
        if 'fabric' in description_lower or 'textile' in description_lower:
            key_concepts.append('soft textile elements')
        if 'stone' in description_lower:
            key_concepts.append('natural stone features')
        
        # Build room-specific base prompt
        room_name = room_type.replace('_', ' ')
        base_prompt = f"Beautiful {room_name} interior design based on room analysis, professional photography, high resolution, photorealistic"
        
        # Add the most relevant extracted concepts (limit to avoid overwhelming)
        if key_concepts:
            # Prioritize the first 5 most relevant concepts
            concept_text = ', '.join(key_concepts[:5])
            prompt = f"{base_prompt}, {concept_text}"
        else:
            # Fallback if no specific concepts detected
            prompt = f"{base_prompt}, elegant design, comfortable atmosphere"
        
        # Add room-specific enhancements based on analysis
        room_specific_enhancers = {
            'bedroom': ['peaceful atmosphere', 'comfortable bedding', 'soft textures'],
            'living_room': ['inviting seating area', 'entertainment space', 'social atmosphere'],
            'kitchen': ['functional workspace', 'clean surfaces', 'efficient layout'],
            'dining_room': ['elegant dining setup', 'ambient lighting', 'welcoming atmosphere'],
            'bathroom': ['clean modern fixtures', 'spa-like atmosphere', 'functional design'],
            'office': ['productive workspace', 'organized desk area', 'focused environment']
        }
        
        # Add room-specific elements
        if room_type in room_specific_enhancers:
            room_elements = room_specific_enhancers[room_type][:2]  # Limit to 2 elements
            prompt = f"{prompt}, {', '.join(room_elements)}"
        
        # Add quality enhancers
        quality_enhancers = [
            "architectural photography style",
            "realistic lighting and shadows",
            "detailed textures and materials",
            "well-organized space"
        ]
        
        final_prompt = f"{prompt}, {', '.join(quality_enhancers)}"
        
        # Ensure reasonable length for Stable Diffusion
        if len(final_prompt) > 300:
            final_prompt = final_prompt[:300].rsplit(',', 1)[0]
        
        logger.info(f"🎨 Generated AI image prompt: {final_prompt}")
        
        return final_prompt
    
    def _add_disclaimer_overlay(self, image):
        """Add disclaimer overlay to generated image"""
        try:
            from PIL import Image, ImageDraw, ImageFont
            
            # Create a copy
            labeled_image = image.copy()
            draw = ImageDraw.Draw(labeled_image)
            
            # Try to load font
            try:
                font = ImageFont.truetype("arial.ttf", 16)
            except:
                font = ImageFont.load_default()
            
            text = "Conceptual Visualization / Inspirational Preview"
            
            # Get text size
            bbox = draw.textbbox((0, 0), text, font=font)
            text_width = bbox[2] - bbox[0]
            text_height = bbox[3] - bbox[1]
            
            # Position at bottom center
            x = (labeled_image.width - text_width) // 2
            y = labeled_image.height - text_height - 10
            
            # Draw semi-transparent background
            overlay = Image.new('RGBA', labeled_image.size, (0, 0, 0, 0))
            overlay_draw = ImageDraw.Draw(overlay)
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
    
    def _generate_enhanced_placeholder(self, 
                                     design_description: Dict[str, Any], 
                                     room_type: str, 
                                     output_dir: str = None) -> Dict[str, Any]:
        """Generate enhanced placeholder when Stable Diffusion is not available"""
        try:
            # Create output directory
            if not output_dir:
                # Use absolute path to Apache document root
                output_dir = os.path.abspath("C:/xampp/htdocs/buildhub/uploads/conceptual_images")
            
            # Ensure we're using absolute path
            if not os.path.isabs(output_dir):
                output_dir = os.path.abspath(os.path.join("C:/xampp/htdocs/buildhub", output_dir))
            
            os.makedirs(output_dir, exist_ok=True)
            
            # Generate filename
            timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
            filename = f"enhanced_placeholder_{room_type}_{timestamp}.png"
            file_path = os.path.join(output_dir, filename)
            
            # Create enhanced placeholder image
            try:
                from PIL import Image, ImageDraw, ImageFont
                
                # Create a 512x512 image with gradient background
                img = Image.new('RGB', (512, 512), color='#f8f9fa')
                draw = ImageDraw.Draw(img)
                
                # Create gradient effect
                for y in range(512):
                    color_value = int(248 - (y * 0.1))  # Subtle gradient
                    color = (color_value, color_value + 1, color_value + 2)
                    draw.line([(0, y), (512, y)], fill=color)
                
                # Try to use fonts
                try:
                    title_font = ImageFont.truetype("arial.ttf", 28)
                    subtitle_font = ImageFont.truetype("arial.ttf", 18)
                    text_font = ImageFont.truetype("arial.ttf", 14)
                except:
                    title_font = ImageFont.load_default()
                    subtitle_font = ImageFont.load_default()
                    text_font = ImageFont.load_default()
                
                # Draw decorative border
                draw.rectangle([20, 20, 492, 492], outline='#dee2e6', width=3)
                draw.rectangle([30, 30, 482, 482], outline='#e9ecef', width=1)
                
                # Title
                title = f"{room_type.replace('_', ' ').title()} Concept"
                draw.text((256, 80), title, fill='#495057', font=title_font, anchor='mm')
                
                # Subtitle
                draw.text((256, 120), "AI-Enhanced Design Concept", fill='#6c757d', font=subtitle_font, anchor='mm')
                draw.text((256, 145), "Inspirational Preview", fill='#6c757d', font=subtitle_font, anchor='mm')
                
                # Description
                description_text = design_description.get('description', 'AI-generated design concept')[:150] + "..."
                
                # Word wrap
                words = description_text.split()
                lines = []
                current_line = []
                for word in words:
                    current_line.append(word)
                    test_line = ' '.join(current_line)
                    if len(test_line) > 45:
                        if len(current_line) > 1:
                            current_line.pop()
                            lines.append(' '.join(current_line))
                            current_line = [word]
                        else:
                            lines.append(test_line)
                            current_line = []
                
                if current_line:
                    lines.append(' '.join(current_line))
                
                # Draw description
                y_offset = 200
                for line in lines[:6]:
                    draw.text((256, y_offset), line, fill='#495057', font=text_font, anchor='mm')
                    y_offset += 25
                
                # Features box
                draw.rectangle([60, 350, 452, 420], fill='#e9ecef', outline='#ced4da')
                draw.text((256, 365), "Enhanced with AI Analysis:", fill='#495057', font=subtitle_font, anchor='mm')
                draw.text((256, 385), "• Computer Vision Object Detection", fill='#6c757d', font=text_font, anchor='mm')
                draw.text((256, 400), "• Spatial Reasoning & Layout Optimization", fill='#6c757d', font=text_font, anchor='mm')
                
                # Footer
                draw.text((256, 450), "Real AI Generation Available with GPU", fill='#868e96', font=text_font, anchor='mm')
                draw.text((256, 470), "Generated by Collaborative AI Pipeline", fill='#adb5bd', font=text_font, anchor='mm')
                
                # Save image
                img.save(file_path)
                logger.info(f"Enhanced placeholder created: {file_path}")
                
            except ImportError:
                # Create simple text file if PIL not available
                with open(file_path.replace('.png', '.txt'), 'w') as f:
                    f.write(f"Enhanced conceptual visualization for {room_type}\n")
                    f.write(f"Description: {design_description.get('description', 'N/A')}\n")
                    f.write(f"Generated: {datetime.now().isoformat()}\n")
                    f.write("Note: Real AI image generation requires GPU setup\n")
                file_path = file_path.replace('.png', '.txt')
                filename = filename.replace('.png', '.txt')
            
            return {
                "success": True,
                "image_path": file_path,
                "image_url": f"/buildhub/uploads/conceptual_images/{filename}",
                "metadata": {
                    "prompt_used": f"Enhanced placeholder for {room_type}",
                    "model_id": "enhanced_placeholder",
                    "generation_type": "enhanced_placeholder",
                    "image_size": "512x512",
                    "generation_time": datetime.now().isoformat(),
                    "note": "Real AI generation requires Stable Diffusion setup"
                }
            }
            
        except Exception as e:
            logger.error(f"Enhanced placeholder generation failed: {e}")
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
            
            # Clear GPU memory if using CUDA
            try:
                import torch
                if torch.cuda.is_available():
                    torch.cuda.empty_cache()
            except ImportError:
                pass
            
            logger.info("ConceptualImageGenerator resources cleaned up")