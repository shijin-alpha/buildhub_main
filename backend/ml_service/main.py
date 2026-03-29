"""
FastAPI ML Service for Construction Risk Assessment
Persistent service that keeps models loaded in memory for fast predictions
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import Dict, Optional
import sys
import os
import logging

# Add parent directory to path to import risk_predictor
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'ml'))
from risk_predictor import ConstructionRiskPredictor

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Initialize FastAPI app
app = FastAPI(
    title="Construction Risk Assessment ML Service",
    description="Persistent ML service for real-time construction risk predictions",
    version="1.0.0"
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Global predictor instance (loaded once at startup)
predictor: Optional[ConstructionRiskPredictor] = None


class PredictionRequest(BaseModel):
    """Request model for risk prediction"""
    plot_size_sqft: float = Field(..., gt=0, description="Plot size in square feet")
    building_size_sqft: float = Field(..., gt=0, description="Building size in square feet")
    num_floors: int = Field(..., ge=1, description="Number of floors")
    budget_amount: float = Field(..., gt=0, description="Budget amount in rupees")
    num_bedrooms: int = Field(..., ge=1, description="Number of bedrooms")
    num_bathrooms: int = Field(..., ge=1, description="Number of bathrooms")
    
    # Optional fields
    plot_shape: Optional[str] = "rectangular"
    topography: Optional[str] = "flat"
    design_style: Optional[str] = "modern"
    special_features: Optional[str] = ""
    custom_requirements: Optional[str] = ""
    architectural_preferences: Optional[str] = ""
    basement: Optional[bool] = False
    terrace: Optional[bool] = False
    parking: Optional[bool] = False
    site_access_difficult: Optional[bool] = False
    utility_connections_needed: Optional[bool] = False
    soil_issues: Optional[bool] = False
    remote_location: Optional[bool] = False

    # Kerala-specific fields
    kerala_district: Optional[str] = "Ernakulam"          # district name
    construction_start_month: Optional[int] = Field(1, ge=1, le=12)  # 1=Jan … 12=Dec
    location: Optional[str] = ""
    climate_modifiers: Optional[dict] = None
    planned_duration_months: Optional[float] = 0


@app.on_event("startup")
async def load_models():
    """Load ML models at startup - runs once when service starts"""
    global predictor
    try:
        logger.info("Loading ML models...")
        
        # Get absolute path to models directory
        current_dir = os.path.dirname(os.path.abspath(__file__))
        models_dir = os.path.abspath(os.path.join(current_dir, '..', 'ml', 'models'))
        
        logger.info(f"Models directory: {models_dir}")
        logger.info(f"Models directory exists: {os.path.exists(models_dir)}")
        
        if not os.path.exists(models_dir):
            raise Exception(f"Models directory not found: {models_dir}")
        
        # List files in models directory
        if os.path.exists(models_dir):
            files = os.listdir(models_dir)
            logger.info(f"Files in models directory: {files}")
        
        predictor = ConstructionRiskPredictor(models_dir=models_dir)
        
        if not predictor.load_models():
            raise Exception("Failed to load models - check model files and metadata")
        
        logger.info("✓ Models loaded successfully and ready for predictions")
    except Exception as e:
        logger.error(f"Failed to load models: {e}", exc_info=True)
        raise


@app.get("/")
async def root():
    """Health check endpoint"""
    return {
        "service": "Construction Risk Assessment ML Service",
        "status": "running",
        "models_loaded": predictor is not None and predictor.is_loaded
    }


@app.get("/health")
async def health_check():
    """Detailed health check"""
    if predictor is None or not predictor.is_loaded:
        raise HTTPException(status_code=503, detail="Models not loaded")
    
    return {
        "status": "healthy",
        "models_loaded": True,
        "model_version": predictor.model_config.get('version', 'unknown') if predictor.model_config else 'unknown'
    }


@app.post("/predict")
async def predict_risks(request: PredictionRequest):
    """
    Predict construction risks for a project
    
    Returns cost overrun and time delay risk predictions with explanations
    """
    if predictor is None or not predictor.is_loaded:
        raise HTTPException(status_code=503, detail="Models not loaded")
    
    try:
        # Convert request to dict for predictor
        form_data = request.model_dump()
        
        # Make prediction
        result = predictor.predict_risks(form_data)
        
        # Check for errors in prediction
        if 'error' in result:
            raise HTTPException(status_code=400, detail=result['error'])
        
        return result
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Prediction error: {e}")
        raise HTTPException(status_code=500, detail=f"Prediction failed: {str(e)}")


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
