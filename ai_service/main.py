"""
Room Improvement AI Service
FastAPI service providing collaborative AI pipeline for room improvement analysis.

This service implements a 4-stage collaborative AI pipeline:
1. Vision analysis: Object detection + visual feature extraction
2. Rule-based reasoning: Spatial guidance + improvement suggestions  
3. Gemini description: AI-generated design descriptions
4. Diffusion visualization: Conceptual image generation

Security: All API keys are loaded from environment variables.
"""

from fastapi import FastAPI, File, UploadFile, Form, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
import uvicorn
import os
import tempfile
import logging
import json
import uuid
import asyncio
from typing import Dict, Any
from datetime import datetime
from dotenv import load_dotenv
from concurrent.futures import ThreadPoolExecutor

# Load environment variables
load_dotenv()

from modules.object_detector import ObjectDetector
from modules.spatial_analyzer import SpatialAnalyzer
from modules.visual_processor import VisualProcessor
from modules.rule_engine import EnhancedRuleEngine
from modules.conceptual_generator import ConceptualImageGenerator

# Configure logging
log_level = os.getenv('LOG_LEVEL', 'INFO')
logging.basicConfig(level=getattr(logging, log_level), format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# Validate critical environment variables
gemini_api_key = os.getenv('GEMINI_API_KEY')
if not gemini_api_key:
    logger.warning("GEMINI_API_KEY not found in environment variables. Stage 3 (Gemini description) will use fallback.")
else:
    logger.info("GEMINI_API_KEY found. Collaborative AI pipeline fully enabled.")

app = FastAPI(
    title="Room Improvement Collaborative AI Service",
    description="4-stage collaborative AI pipeline: Vision → Reasoning → Gemini → Diffusion",
    version="2.0.0"
)

# Configure CORS
allowed_origins = os.getenv('ALLOWED_ORIGINS', '*').split(',')
app.add_middleware(
    CORSMiddleware,
    allow_origins=allowed_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Initialize AI components
object_detector = None
spatial_analyzer = None
visual_processor = None
rule_engine = None
conceptual_generator = None

# Job management system for asynchronous image generation
image_generation_jobs = {}
executor = ThreadPoolExecutor(max_workers=2)  # Limit concurrent Stable Diffusion jobs

class ImageGenerationJob:
    def __init__(self, job_id: str):
        self.job_id = job_id
        self.status = "pending"  # pending, processing, completed, failed
        self.created_at = datetime.now()
        self.completed_at = None
        self.image_url = None
        self.image_path = None
        self.error_message = None
        self.generation_metadata = {}
        
    def to_dict(self):
        return {
            "job_id": self.job_id,
            "status": self.status,
            "created_at": self.created_at.isoformat(),
            "completed_at": self.completed_at.isoformat() if self.completed_at else None,
            "image_url": self.image_url,
            "image_path": self.image_path,
            "error_message": self.error_message,
            "generation_metadata": self.generation_metadata
        }

@app.on_event("startup")
async def startup_event():
    """Initialize AI components on startup"""
    global object_detector, spatial_analyzer, visual_processor, rule_engine, conceptual_generator
    
    try:
        logger.info("Initializing AI components...")
        
        # Initialize components
        object_detector = ObjectDetector()
        spatial_analyzer = SpatialAnalyzer()
        visual_processor = VisualProcessor()
        rule_engine = EnhancedRuleEngine()
        conceptual_generator = ConceptualImageGenerator()
        
        logger.info("AI service initialized successfully")
        
    except Exception as e:
        logger.error(f"Failed to initialize AI service: {e}")
        raise

@app.get("/")
async def root():
    """Health check endpoint"""
    return {
        "service": "Room Improvement AI Service",
        "status": "running",
        "version": "1.0.0"
    }

@app.get("/health")
async def health_check():
    """Detailed health check"""
    return {
        "status": "healthy",
        "components": {
            "object_detector": object_detector is not None,
            "spatial_analyzer": spatial_analyzer is not None,
            "visual_processor": visual_processor is not None,
            "rule_engine": rule_engine is not None,
            "conceptual_generator": conceptual_generator is not None
        }
    }

@app.post("/analyze-room")
async def analyze_room(
    image: UploadFile = File(...),
    room_type: str = Form(...),
    improvement_notes: str = Form(default=""),
    existing_features: str = Form(default="{}"),
    generate_concept: bool = Form(default=True)
):
    """
    Comprehensive room analysis with collaborative AI pipeline
    
    Pipeline stages:
    1. Vision analysis: Object detection + visual feature extraction
    2. Reasoning: Rule-based spatial guidance + improvement suggestions
    3. Language: Gemini-powered design description generation
    4. Visualization: Diffusion-based conceptual image synthesis
    """
    try:
        # Validate inputs
        if not image.content_type.startswith('image/'):
            raise HTTPException(status_code=400, detail="File must be an image")
        
        # Save uploaded image temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as temp_file:
            content = await image.read()
            temp_file.write(content)
            temp_image_path = temp_file.name
        
        try:
            # Parse existing features
            try:
                existing_visual_features = json.loads(existing_features) if existing_features else {}
            except json.JSONDecodeError:
                existing_visual_features = {}
            
            # Stage 1: Vision Analysis
            logger.info("Stage 1: Vision Analysis - Object detection and visual processing")
            
            # Object detection
            object_detection_result = object_detector.detect_objects(temp_image_path)
            
            # Spatial analysis
            spatial_analysis_result = spatial_analyzer.analyze_spatial_zones(
                temp_image_path, object_detection_result
            )
            
            # Visual processing enhancement
            enhanced_visual_features = visual_processor.enhance_visual_analysis(
                temp_image_path, existing_visual_features
            )
            
            # Stage 2: Rule-Based Reasoning
            logger.info("Stage 2: Rule-Based Reasoning - Spatial guidance generation")
            
            spatial_guidance_result = rule_engine.generate_spatial_guidance(
                object_detection_result,
                spatial_analysis_result,
                room_type,
                improvement_notes
            )
            
            # Prepare comprehensive improvement suggestions structure for Stage 3 & 4
            # Extract detailed suggestions from spatial guidance
            lighting_suggestions = []
            color_suggestions = []
            furniture_suggestions = []
            
            # Extract from improvement_suggestions
            for suggestion in spatial_guidance_result.get('improvement_suggestions', []):
                if isinstance(suggestion, dict):
                    suggestion_text = suggestion.get('suggestion', '')
                    category = suggestion.get('category', 'general')
                    
                    if 'light' in suggestion_text.lower() or category == 'lighting':
                        lighting_suggestions.append(suggestion_text)
                    elif 'color' in suggestion_text.lower() or 'paint' in suggestion_text.lower() or category == 'color':
                        color_suggestions.append(suggestion_text)
                    elif 'furniture' in suggestion_text.lower() or 'layout' in suggestion_text.lower() or category == 'furniture':
                        furniture_suggestions.append(suggestion_text)
                    else:
                        furniture_suggestions.append(suggestion_text)  # Default to furniture
                elif isinstance(suggestion, str):
                    # Handle string suggestions
                    if 'light' in suggestion.lower():
                        lighting_suggestions.append(suggestion)
                    elif 'color' in suggestion.lower() or 'paint' in suggestion.lower():
                        color_suggestions.append(suggestion)
                    else:
                        furniture_suggestions.append(suggestion)
            
            # Extract from placement_guidance
            for guidance in spatial_guidance_result.get('placement_guidance', []):
                if isinstance(guidance, dict):
                    recommendation = guidance.get('recommendation', '')
                    if recommendation:
                        furniture_suggestions.append(recommendation)
            
            # Extract from layout_recommendations
            for layout_rec in spatial_guidance_result.get('layout_recommendations', []):
                if isinstance(layout_rec, dict):
                    recommendation = layout_rec.get('recommendation', '')
                    if recommendation:
                        furniture_suggestions.append(recommendation)
                elif isinstance(layout_rec, str):
                    furniture_suggestions.append(layout_rec)
            
            # Create structured improvement suggestions for AI generation
            improvement_suggestions = {
                "lighting": ' '.join(lighting_suggestions[:3]) if lighting_suggestions else f"Optimize lighting for {room_type} functionality and ambiance",
                "color_ambience": ' '.join(color_suggestions[:3]) if color_suggestions else f"Enhance color scheme to improve {room_type} atmosphere",
                "furniture_layout": ' '.join(furniture_suggestions[:4]) if furniture_suggestions else f"Improve {room_type} layout for better functionality and flow"
            }
            
            # Stage 3 & 4: Collaborative Conceptual Generation
            conceptual_result = {"success": False, "error": "Conceptual generation disabled"}
            
            if generate_concept:
                logger.info("Stage 3 & 4: Collaborative AI Pipeline - Gemini + Diffusion")
                
                # Determine output directory based on analysis type
                # Room improvement analyses go to room_improvements
                # Exterior/architectural analyses go to conceptual_images
                if room_type in ['bedroom', 'living_room', 'kitchen', 'dining_room', 'bathroom', 'office', 'other']:
                    output_dir = "uploads/room_improvements"
                else:
                    output_dir = "uploads/conceptual_images"
                
                conceptual_result = conceptual_generator.generate_collaborative_concept(
                    improvement_suggestions,
                    object_detection_result,
                    enhanced_visual_features,
                    spatial_guidance_result,
                    room_type,
                    output_dir=output_dir
                )
            
            # Combine all results
            result = {
                "success": True,
                "collaborative_pipeline_results": {
                    "stage_1_vision_analysis": {
                        "detected_objects": object_detection_result,
                        "spatial_zones": spatial_analysis_result,
                        "enhanced_visual_features": enhanced_visual_features
                    },
                    "stage_2_rule_based_reasoning": {
                        "spatial_guidance": spatial_guidance_result,
                        "improvement_suggestions": improvement_suggestions
                    },
                    "stage_3_4_conceptual_generation": conceptual_result
                },
                "analysis_metadata": {
                    "pipeline_type": "collaborative_ai_hybrid",
                    "room_type": room_type,
                    "improvement_notes": improvement_notes,
                    "stages_completed": 4 if conceptual_result.get('success') else 2,
                    "analysis_timestamp": datetime.now().isoformat(),
                    "gemini_api_available": bool(os.getenv('GEMINI_API_KEY')),
                    "diffusion_device": conceptual_generator.device if conceptual_generator else "unknown"
                }
            }
            
            return result
            
        finally:
            # Clean up temporary file
            try:
                os.unlink(temp_image_path)
            except:
                pass
                
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Room analysis failed: {e}")
        raise HTTPException(status_code=500, detail=f"Analysis failed: {str(e)}")

@app.post("/generate-collaborative-concept")
async def generate_collaborative_concept(
    improvement_suggestions: dict,
    detected_objects: dict,
    visual_features: dict,
    spatial_guidance: dict,
    room_type: str,
    output_dir: str = None  # Will be determined based on room_type
):
    """
    Generate conceptual visualization using collaborative AI pipeline
    
    This endpoint specifically handles the Gemini + Diffusion stages of the pipeline
    """
    try:
        # Determine output directory based on analysis type
        if output_dir is None:
            if room_type in ['bedroom', 'living_room', 'kitchen', 'dining_room', 'bathroom', 'office', 'other']:
                output_dir = "uploads/room_improvements"
            else:
                output_dir = "uploads/conceptual_images"
        
        result = conceptual_generator.generate_collaborative_concept(
            improvement_suggestions,
            detected_objects,
            visual_features,
            spatial_guidance,
            room_type,
            output_dir
        )
        
        return result
        
    except Exception as e:
        logger.error(f"Collaborative concept generation failed: {e}")
        raise HTTPException(status_code=500, detail=f"Concept generation failed: {str(e)}")

# Legacy endpoint for backward compatibility
@app.post("/analyze-room-legacy")
async def analyze_room_legacy(
    image: UploadFile = File(...),
    room_type: str = Form(...),
    improvement_notes: str = Form(default=""),
    existing_features: str = Form(default="{}"),
    generate_concept: bool = Form(default=False)
):
    """
    Analyze room image with computer vision enhancements and optional conceptual image generation
    
    This endpoint provides object detection, spatial reasoning, and conceptual visualization
    to enhance the existing rule-based system.
    """
    
    if not all([object_detector, spatial_analyzer, visual_processor, rule_engine]):
        raise HTTPException(status_code=503, detail="AI service not properly initialized")
    
    # Validate image file
    if not image.content_type.startswith('image/'):
        raise HTTPException(status_code=400, detail="File must be an image")
    
    try:
        # Save uploaded image temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as temp_file:
            content = await image.read()
            temp_file.write(content)
            temp_path = temp_file.name
        
        try:
            # Parse existing features from PHP system
            import json
            try:
                existing_visual_features = json.loads(existing_features) if existing_features != "{}" else {}
            except json.JSONDecodeError:
                existing_visual_features = {}
            
            # Stage 1: Object Detection
            logger.info("Starting object detection...")
            detected_objects = object_detector.detect_objects(temp_path)
            
            # Stage 2: Spatial Analysis
            logger.info("Analyzing spatial relationships...")
            spatial_zones = spatial_analyzer.analyze_spatial_zones(temp_path, detected_objects)
            
            # Stage 3: Visual Processing Enhancement
            logger.info("Processing visual attributes...")
            enhanced_visual_features = visual_processor.enhance_visual_analysis(
                temp_path, existing_visual_features
            )
            
            # Stage 4: Rule-Based Spatial Reasoning
            logger.info("Applying spatial reasoning rules...")
            spatial_guidance = rule_engine.generate_spatial_guidance(
                detected_objects, spatial_zones, room_type, improvement_notes
            )
            
            # Stage 5: Conceptual Image Generation (if requested)
            conceptual_visualization = None
            if generate_concept and conceptual_generator:
                logger.info("Generating conceptual visualization...")
                try:
                    # Create output directory for generated images
                    output_dir = os.path.join(tempfile.gettempdir(), "conceptual_images")
                    
                    # Generate conceptual image based on all analysis results
                    conceptual_visualization = conceptual_generator.generate_conceptual_image(
                        improvement_suggestions=spatial_guidance.get('improvement_suggestions', {}),
                        detected_objects=detected_objects,
                        visual_features=enhanced_visual_features,
                        room_type=room_type,
                        output_dir=output_dir
                    )
                except Exception as e:
                    logger.warning(f"Conceptual image generation failed: {e}")
                    conceptual_visualization = {
                        "success": False,
                        "error": str(e),
                        "note": "Conceptual visualization temporarily unavailable"
                    }
            
            # Stage 6: Generate Structured Response
            response = {
                "success": True,
                "ai_analysis": {
                    "detected_objects": detected_objects,
                    "spatial_zones": spatial_zones,
                    "enhanced_visual_features": enhanced_visual_features,
                    "spatial_guidance": spatial_guidance,
                    "conceptual_visualization": conceptual_visualization,
                    "analysis_metadata": {
                        "room_type": room_type,
                        "improvement_notes": improvement_notes,
                        "processing_timestamp": visual_processor.get_timestamp(),
                        "ai_method": "hybrid_cv_rules_generation",
                        "model_version": "yolov8n_coco_sd1.5",
                        "confidence_threshold": object_detector.confidence_threshold,
                        "conceptual_generation_enabled": generate_concept and conceptual_generator is not None
                    }
                },
                "integration_notes": {
                    "system_type": "hybrid_enhancement_with_visualization",
                    "description": "Computer vision analysis with conceptual image generation to enhance existing rule-based system",
                    "compatibility": "designed_for_existing_php_backend",
                    "new_capabilities": [
                        "object_aware_image_analysis",
                        "relative_placement_reasoning", 
                        "conceptual_image_generation"
                    ]
                }
            }
            
            logger.info("Room analysis with conceptual generation completed successfully")
            return response
            
        finally:
            # Clean up temporary file
            if os.path.exists(temp_path):
                os.unlink(temp_path)
                
    except Exception as e:
        logger.error(f"Error during room analysis: {e}")
        raise HTTPException(status_code=500, detail=f"Analysis failed: {str(e)}")

@app.post("/test-upload")
async def test_upload(image: UploadFile = File(...)):
    """Simple test endpoint for file upload debugging"""
    try:
        logger.info(f"Received file: {image.filename}, content_type: {image.content_type}, size: {image.size}")
        
        # Read file content
        content = await image.read()
        logger.info(f"Read {len(content)} bytes")
        
        # Reset file pointer
        await image.seek(0)
        
        return {
            "success": True,
            "filename": image.filename,
            "content_type": image.content_type,
            "size": len(content)
        }
        
    except Exception as e:
        logger.error(f"Test upload error: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Upload test failed: {str(e)}")

@app.post("/detect-objects")
async def detect_objects_only(image: UploadFile = File(...)):
    """Endpoint for object detection only"""
    
    if not object_detector:
        raise HTTPException(status_code=503, detail="Object detector not initialized")
    
    if not image.content_type.startswith('image/'):
        raise HTTPException(status_code=400, detail="File must be an image")
    
    try:
        logger.info(f"Processing object detection for image: {image.filename}")
        
        with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as temp_file:
            content = await image.read()
            temp_file.write(content)
            temp_path = temp_file.name
        
        logger.info(f"Temporary file created: {temp_path}")
        
        try:
            detected_objects = object_detector.detect_objects(temp_path)
            logger.info(f"Object detection completed successfully")
            
            return {
                "success": True,
                "detected_objects": detected_objects,
                "metadata": {
                    "model": "yolov8n",
                    "confidence_threshold": object_detector.confidence_threshold
                }
            }
        finally:
            if os.path.exists(temp_path):
                os.unlink(temp_path)
                logger.info(f"Temporary file cleaned up: {temp_path}")
                
    except Exception as e:
        logger.error(f"Object detection error: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Detection failed: {str(e)}")

@app.post("/generate-concept")
async def start_conceptual_image_generation(
    background_tasks: BackgroundTasks,
    improvement_suggestions: str = Form(...),
    detected_objects: str = Form(default="{}"),
    visual_features: str = Form(default="{}"),
    spatial_guidance: str = Form(default="{}"),
    room_type: str = Form(...),
    save_image: bool = Form(default=True)
):
    """
    Start asynchronous real AI conceptual image generation using Stable Diffusion
    
    Returns immediately with a job_id for status polling.
    The actual image generation runs in the background.
    """
    
    if not conceptual_generator:
        raise HTTPException(status_code=503, detail="Conceptual generator not initialized")
    
    try:
        import json
        
        # Generate unique job ID
        job_id = str(uuid.uuid4())
        
        # Parse input data with defensive handling
        try:
            suggestions_data = json.loads(improvement_suggestions)
            objects_data = json.loads(detected_objects) if detected_objects != "{}" else {}
            features_data = json.loads(visual_features) if visual_features != "{}" else {}
            spatial_data = json.loads(spatial_guidance) if spatial_guidance != "{}" else {}
            
            # Ensure suggestions_data is a dictionary
            if isinstance(suggestions_data, list):
                # Convert list to dictionary with default keys
                suggestions_dict = {}
                if len(suggestions_data) > 0:
                    suggestions_dict['lighting'] = suggestions_data[0] if len(suggestions_data) > 0 else ''
                if len(suggestions_data) > 1:
                    suggestions_dict['color_ambience'] = suggestions_data[1] if len(suggestions_data) > 1 else ''
                if len(suggestions_data) > 2:
                    suggestions_dict['furniture_layout'] = suggestions_data[2] if len(suggestions_data) > 2 else ''
                suggestions_data = suggestions_dict
            elif not isinstance(suggestions_data, dict):
                suggestions_data = {}
            
            # Ensure other data structures
            if not isinstance(objects_data, dict):
                objects_data = {'objects': [], 'summary': {}}
            if not isinstance(features_data, dict):
                features_data = {}
            if not isinstance(spatial_data, dict):
                spatial_data = {}
                
        except json.JSONDecodeError as e:
            raise HTTPException(status_code=400, detail=f"Invalid JSON data: {str(e)}")
        
        # Create job record
        job = ImageGenerationJob(job_id)
        image_generation_jobs[job_id] = job
        
        # Start background image generation
        background_tasks.add_task(
            generate_image_background,
            job_id,
            suggestions_data,
            objects_data,
            features_data,
            spatial_data,
            room_type
        )
        
        logger.info(f"Started asynchronous image generation job: {job_id}")
        
        return {
            "success": True,
            "job_id": job_id,
            "status": "pending",
            "message": "Image generation started. Use /image-status/{job_id} to check progress.",
            "estimated_completion_time": "30-60 seconds",
            "endpoint_metadata": {
                "endpoint": "generate-concept",
                "room_type": room_type,
                "processing_timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                "pipeline_type": "asynchronous_collaborative_ai_stable_diffusion"
            }
        }
        
    except Exception as e:
        logger.error(f"Failed to start asynchronous image generation: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Failed to start image generation: {str(e)}")


async def generate_image_background(
    job_id: str,
    suggestions_data: dict,
    objects_data: dict,
    features_data: dict,
    spatial_data: dict,
    room_type: str
):
    """
    Background task for generating real AI images with Stable Diffusion
    """
    job = image_generation_jobs.get(job_id)
    if not job:
        logger.error(f"Job {job_id} not found in background task")
        return
    
    try:
        job.status = "processing"
        logger.info(f"Starting background image generation for job {job_id}")
        
        # Determine output directory based on analysis type
        # Room improvement analyses go to room_improvements
        # Exterior/architectural analyses go to conceptual_images
        if room_type in ['bedroom', 'living_room', 'kitchen', 'dining_room', 'bathroom', 'office', 'other']:
            output_dir = "C:/xampp/htdocs/buildhub/uploads/room_improvements"
        else:
            output_dir = "C:/xampp/htdocs/buildhub/uploads/conceptual_images"
        
        # Run the collaborative conceptual generation in thread pool
        loop = asyncio.get_event_loop()
        result = await loop.run_in_executor(
            executor,
            conceptual_generator.generate_collaborative_concept,
            suggestions_data,
            objects_data,
            features_data,
            spatial_data,
            room_type,
            output_dir
        )
        
        if result and result.get('success'):
            job.status = "completed"
            job.completed_at = datetime.now()
            
            # Extract image information
            conceptual_image = result.get('conceptual_image', {})
            job.image_url = conceptual_image.get('image_url')
            job.image_path = conceptual_image.get('image_path')
            job.generation_metadata = conceptual_image.get('generation_metadata', {})
            
            logger.info(f"🔍 [DEBUG] Background image generation completed for job {job_id}:")
            logger.info(f"🔍 [DEBUG] - Image path: {job.image_path}")
            logger.info(f"🔍 [DEBUG] - Image URL: {job.image_url}")
            logger.info(f"🔍 [DEBUG] - File exists: {os.path.exists(job.image_path) if job.image_path else 'No path'}")
            if job.image_path and os.path.exists(job.image_path):
                logger.info(f"🔍 [DEBUG] - File size: {os.path.getsize(job.image_path)} bytes")
            
        else:
            job.status = "failed"
            job.completed_at = datetime.now()
            job.error_message = result.get('error', 'Unknown error during image generation')
            logger.error(f"🔍 [DEBUG] Background image generation failed for job {job_id}: {job.error_message}")
            
    except Exception as e:
        job.status = "failed"
        job.completed_at = datetime.now()
        job.error_message = str(e)
        logger.error(f"Background image generation error for job {job_id}: {e}", exc_info=True)


@app.get("/image-status/{job_id}")
async def get_image_generation_status(job_id: str):
    """
    Get the status of an asynchronous image generation job
    
    Returns:
    - status: pending, processing, completed, failed
    - image_url: when completed
    - error_message: when failed
    """
    
    job = image_generation_jobs.get(job_id)
    if not job:
        raise HTTPException(status_code=404, detail=f"Job {job_id} not found")
    
    response = {
        "job_id": job_id,
        "status": job.status,
        "created_at": job.created_at.isoformat(),
    }
    
    if job.status == "completed":
        response.update({
            "completed_at": job.completed_at.isoformat(),
            "image_url": job.image_url,
            "image_path": job.image_path,
            "generation_metadata": job.generation_metadata,
            "disclaimer": "Conceptual Visualization / Inspirational Preview"
        })
        
        logger.info(f"🔍 [DEBUG] Status check for completed job {job_id}:")
        logger.info(f"🔍 [DEBUG] - Returning image_url: {job.image_url}")
        logger.info(f"🔍 [DEBUG] - Returning image_path: {job.image_path}")
        logger.info(f"🔍 [DEBUG] - File exists check: {os.path.exists(job.image_path) if job.image_path else 'No path'}")
        
    elif job.status == "failed":
        response.update({
            "completed_at": job.completed_at.isoformat() if job.completed_at else None,
            "error_message": job.error_message,
            "fallback_message": "Real AI image generation failed"
        })
    elif job.status == "processing":
        # Calculate estimated remaining time
        elapsed = (datetime.now() - job.created_at).total_seconds()
        estimated_remaining = max(0, 45 - elapsed)  # Estimate 45 seconds total
        response.update({
            "estimated_remaining_seconds": int(estimated_remaining),
            "progress_message": "Generating real AI image with Stable Diffusion..."
        })
    else:  # pending
        response.update({
            "progress_message": "Image generation queued..."
        })
    
    return response

if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host="127.0.0.1",
        port=8000,
        reload=True,
        log_level="info"
    )