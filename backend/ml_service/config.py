"""
Configuration for FastAPI ML Service
"""

import os

# Service Configuration
SERVICE_HOST = os.getenv("ML_SERVICE_HOST", "0.0.0.0")
SERVICE_PORT = int(os.getenv("ML_SERVICE_PORT", "8000"))

# Model Configuration
MODELS_DIR = os.getenv(
    "MODELS_DIR",
    os.path.join(os.path.dirname(__file__), '..', 'ml', 'models')
)

# Logging Configuration
LOG_LEVEL = os.getenv("LOG_LEVEL", "INFO")

# CORS Configuration
CORS_ORIGINS = os.getenv("CORS_ORIGINS", "*").split(",")

# Timeout Configuration
REQUEST_TIMEOUT = int(os.getenv("REQUEST_TIMEOUT", "30"))

# Model Reloading (for development)
AUTO_RELOAD = os.getenv("AUTO_RELOAD", "false").lower() == "true"
