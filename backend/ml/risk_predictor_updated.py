"""
risk_predictor_updated.py — thin re-export shim.

The hybrid evaluation system now lives entirely in risk_predictor.py.
This file is kept for backward compatibility with any imports that
reference risk_predictor_updated directly.
"""
from risk_predictor import ConstructionRiskPredictor, get_predictor  # noqa: F401
