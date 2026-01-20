"""
Visual Processing Module
Enhances existing visual feature extraction with computer vision techniques.

This module complements the existing PHP-based visual analysis with additional
OpenCV-based processing for brightness, contrast, and color analysis.
"""

import cv2
import numpy as np
from typing import Dict, Any, Tuple, List
import logging
from datetime import datetime

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

class VisualProcessor:
    """Enhanced visual processing using OpenCV"""
    
    def __init__(self):
        """Initialize visual processor"""
        pass
    
    def enhance_visual_analysis(self, image_path: str, existing_features: Dict[str, Any]) -> Dict[str, Any]:
        """
        Enhance existing visual features with computer vision analysis
        
        Args:
            image_path: Path to the room image
            existing_features: Visual features from PHP system
            
        Returns:
            Enhanced visual features combining PHP and CV analysis
        """
        try:
            # Ensure existing_features is a dictionary
            if not isinstance(existing_features, dict):
                logger.warning(f"existing_features is not a dict, got {type(existing_features)}: {existing_features}")
                existing_features = {}
            
            # Load image
            image = cv2.imread(image_path)
            if image is None:
                raise ValueError(f"Could not load image from {image_path}")
            
            # Extract CV-based features
            cv_features = self._extract_cv_features(image)
            
            # Combine with existing features
            enhanced_features = self._combine_features(existing_features, cv_features)
            
            # Add enhancement metadata
            enhanced_features['enhancement_metadata'] = {
                "enhancement_method": "opencv_cv2",
                "original_system": "php_gd_analysis",
                "enhancement_timestamp": self.get_timestamp(),
                "features_enhanced": list(cv_features.keys())
            }
            
            # Convert all NumPy types to native Python types
            return convert_numpy_types(enhanced_features)
            
        except Exception as e:
            logger.error(f"Visual enhancement failed: {e}")
            # Return existing features with error note
            result = {
                **existing_features,
                "enhancement_metadata": {
                    "enhancement_method": "fallback",
                    "error": str(e),
                    "enhancement_timestamp": self.get_timestamp()
                }
            }
            return convert_numpy_types(result)
    
    def _extract_cv_features(self, image: np.ndarray) -> Dict[str, Any]:
        """Extract visual features using OpenCV"""
        
        # Convert to different color spaces for analysis
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
        hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)
        lab = cv2.cvtColor(image, cv2.COLOR_BGR2LAB)
        
        features = {}
        
        # Enhanced brightness analysis
        features['brightness_analysis'] = self._analyze_brightness_cv(gray, lab)
        
        # Enhanced contrast analysis
        features['contrast_analysis'] = self._analyze_contrast_cv(gray)
        
        # Enhanced color analysis
        features['color_analysis'] = self._analyze_colors_cv(image, hsv)
        
        # Texture analysis
        features['texture_analysis'] = self._analyze_texture(gray)
        
        # Edge density (indicates detail level)
        features['edge_analysis'] = self._analyze_edges(gray)
        
        return features
    
    def _analyze_brightness_cv(self, gray: np.ndarray, lab: np.ndarray) -> Dict[str, Any]:
        """Enhanced brightness analysis using multiple methods"""
        
        # Method 1: Mean brightness
        mean_brightness = np.mean(gray)
        
        # Method 2: L channel from LAB color space (perceptually uniform)
        l_channel = lab[:, :, 0]
        perceptual_brightness = np.mean(l_channel)
        
        # Method 3: Histogram analysis
        hist = cv2.calcHist([gray], [0], None, [256], [0, 256])
        hist_normalized = hist.flatten() / hist.sum()
        
        # Calculate weighted brightness (emphasizes brighter pixels)
        weighted_brightness = np.sum(hist_normalized * np.arange(256))
        
        # Brightness distribution
        dark_pixels = np.sum(hist_normalized[:85])  # 0-84 (dark)
        mid_pixels = np.sum(hist_normalized[85:170])  # 85-169 (mid)
        bright_pixels = np.sum(hist_normalized[170:])  # 170-255 (bright)
        
        return {
            "mean_brightness": round(float(mean_brightness), 2),
            "perceptual_brightness": round(float(perceptual_brightness * 255 / 100), 2),  # Convert to 0-255
            "weighted_brightness": round(float(weighted_brightness), 2),
            "brightness_distribution": {
                "dark_ratio": round(float(dark_pixels), 3),
                "mid_ratio": round(float(mid_pixels), 3),
                "bright_ratio": round(float(bright_pixels), 3)
            },
            "brightness_category": self._categorize_brightness(mean_brightness),
            "lighting_quality": self._assess_lighting_quality(dark_pixels, mid_pixels, bright_pixels)
        }
    
    def _analyze_contrast_cv(self, gray: np.ndarray) -> Dict[str, Any]:
        """Enhanced contrast analysis"""
        
        # Method 1: Standard deviation (global contrast)
        std_contrast = np.std(gray)
        
        # Method 2: RMS contrast
        mean_val = np.mean(gray)
        rms_contrast = np.sqrt(np.mean((gray - mean_val) ** 2))
        
        # Method 3: Michelson contrast (for periodic patterns)
        max_val = np.max(gray)
        min_val = np.min(gray)
        michelson_contrast = (max_val - min_val) / (max_val + min_val) if (max_val + min_val) > 0 else 0
        
        # Method 4: Local contrast using Laplacian
        laplacian = cv2.Laplacian(gray, cv2.CV_64F)
        local_contrast = np.var(laplacian)
        
        return {
            "std_contrast": round(float(std_contrast), 2),
            "rms_contrast": round(float(rms_contrast), 2),
            "michelson_contrast": round(float(michelson_contrast), 3),
            "local_contrast": round(float(local_contrast), 2),
            "contrast_category": self._categorize_contrast(std_contrast),
            "contrast_quality": self._assess_contrast_quality(std_contrast, local_contrast)
        }
    
    def _analyze_colors_cv(self, image: np.ndarray, hsv: np.ndarray) -> Dict[str, Any]:
        """Enhanced color analysis using HSV color space"""
        
        # Extract HSV channels
        h_channel = hsv[:, :, 0]
        s_channel = hsv[:, :, 1]
        v_channel = hsv[:, :, 2]
        
        # Color temperature analysis
        color_temp_analysis = self._analyze_color_temperature_cv(image)
        
        # Saturation analysis
        mean_saturation = np.mean(s_channel)
        saturation_std = np.std(s_channel)
        
        # Hue distribution
        hue_hist = cv2.calcHist([h_channel], [0], None, [180], [0, 180])
        dominant_hues = self._find_dominant_hues(hue_hist)
        
        # Color harmony analysis
        color_harmony = self._analyze_color_harmony(dominant_hues, mean_saturation)
        
        return {
            "color_temperature_analysis": color_temp_analysis,
            "saturation_analysis": {
                "mean_saturation": round(float(mean_saturation), 2),
                "saturation_std": round(float(saturation_std), 2),
                "saturation_category": self._categorize_saturation(mean_saturation)
            },
            "hue_analysis": {
                "dominant_hues": dominant_hues,
                "hue_diversity": len([h for h in dominant_hues if h['percentage'] > 5])
            },
            "color_harmony": color_harmony
        }
    
    def _analyze_color_temperature_cv(self, image: np.ndarray) -> Dict[str, Any]:
        """Analyze color temperature using RGB channel analysis"""
        
        # Calculate mean RGB values
        b_mean = np.mean(image[:, :, 0])  # Blue
        g_mean = np.mean(image[:, :, 1])  # Green
        r_mean = np.mean(image[:, :, 2])  # Red
        
        # Color temperature indicators
        warm_indicator = (r_mean + g_mean) / 2 - b_mean
        cool_indicator = b_mean - (r_mean + g_mean) / 2
        
        # Determine temperature category
        if warm_indicator > 10:
            temp_category = "warm"
            temp_strength = min(100, warm_indicator / 50 * 100)
        elif cool_indicator > 10:
            temp_category = "cool"
            temp_strength = min(100, cool_indicator / 50 * 100)
        else:
            temp_category = "neutral"
            temp_strength = abs(warm_indicator - cool_indicator)
        
        return {
            "rgb_means": {
                "red": round(float(r_mean), 2),
                "green": round(float(g_mean), 2),
                "blue": round(float(b_mean), 2)
            },
            "temperature_category": temp_category,
            "temperature_strength": round(float(temp_strength), 2),
            "warm_indicator": round(float(warm_indicator), 2),
            "cool_indicator": round(float(cool_indicator), 2)
        }
    
    def _analyze_texture(self, gray: np.ndarray) -> Dict[str, Any]:
        """Analyze image texture using various methods"""
        
        # Method 1: Local Binary Pattern approximation
        # Calculate gradient magnitude
        grad_x = cv2.Sobel(gray, cv2.CV_64F, 1, 0, ksize=3)
        grad_y = cv2.Sobel(gray, cv2.CV_64F, 0, 1, ksize=3)
        gradient_magnitude = np.sqrt(grad_x**2 + grad_y**2)
        
        texture_strength = np.mean(gradient_magnitude)
        texture_std = np.std(gradient_magnitude)
        
        # Method 2: Gray Level Co-occurrence Matrix approximation
        # Calculate local variance as texture measure
        kernel = np.ones((5, 5), np.float32) / 25
        local_mean = cv2.filter2D(gray.astype(np.float32), -1, kernel)
        local_variance = cv2.filter2D((gray.astype(np.float32) - local_mean)**2, -1, kernel)
        texture_variance = np.mean(local_variance)
        
        return {
            "texture_strength": round(float(texture_strength), 2),
            "texture_std": round(float(texture_std), 2),
            "texture_variance": round(float(texture_variance), 2),
            "texture_category": self._categorize_texture(texture_strength),
            "surface_character": self._assess_surface_character(texture_strength, texture_variance)
        }
    
    def _analyze_edges(self, gray: np.ndarray) -> Dict[str, Any]:
        """Analyze edge density and characteristics"""
        
        # Canny edge detection
        edges = cv2.Canny(gray, 50, 150)
        edge_density = np.sum(edges > 0) / edges.size
        
        # Sobel edge detection for gradient analysis
        sobel_x = cv2.Sobel(gray, cv2.CV_64F, 1, 0, ksize=3)
        sobel_y = cv2.Sobel(gray, cv2.CV_64F, 0, 1, ksize=3)
        sobel_magnitude = np.sqrt(sobel_x**2 + sobel_y**2)
        
        edge_strength = np.mean(sobel_magnitude)
        
        return {
            "edge_density": round(float(edge_density), 4),
            "edge_strength": round(float(edge_strength), 2),
            "detail_level": self._categorize_detail_level(edge_density),
            "structural_complexity": self._assess_structural_complexity(edge_density, edge_strength)
        }
    
    def _combine_features(self, existing_features: Dict[str, Any], cv_features: Dict[str, Any]) -> Dict[str, Any]:
        """Combine existing PHP features with CV enhancements"""
        
        enhanced = existing_features.copy()
        
        # Enhance brightness information
        if 'brightness' in existing_features and 'brightness_analysis' in cv_features:
            enhanced['brightness_enhanced'] = {
                "php_brightness": existing_features['brightness'],
                "cv_brightness": cv_features['brightness_analysis']['mean_brightness'],
                "perceptual_brightness": cv_features['brightness_analysis']['perceptual_brightness'],
                "brightness_distribution": cv_features['brightness_analysis']['brightness_distribution'],
                "lighting_quality": cv_features['brightness_analysis']['lighting_quality'],
                "consensus_category": self._get_brightness_consensus(
                    existing_features['brightness'], 
                    cv_features['brightness_analysis']['mean_brightness']
                )
            }
        
        # Enhance contrast information
        if 'contrast' in existing_features and 'contrast_analysis' in cv_features:
            enhanced['contrast_enhanced'] = {
                "php_contrast": existing_features['contrast'],
                "cv_std_contrast": cv_features['contrast_analysis']['std_contrast'],
                "cv_rms_contrast": cv_features['contrast_analysis']['rms_contrast'],
                "local_contrast": cv_features['contrast_analysis']['local_contrast'],
                "contrast_quality": cv_features['contrast_analysis']['contrast_quality'],
                "consensus_category": self._get_contrast_consensus(
                    existing_features['contrast'],
                    cv_features['contrast_analysis']['std_contrast']
                )
            }
        
        # Add new CV-only features
        enhanced['texture_analysis'] = cv_features.get('texture_analysis', {})
        enhanced['edge_analysis'] = cv_features.get('edge_analysis', {})
        enhanced['color_analysis_enhanced'] = cv_features.get('color_analysis', {})
        
        return enhanced
    
    # Helper methods for categorization
    def _categorize_brightness(self, brightness: float) -> str:
        if brightness < 80:
            return "dark"
        elif brightness < 120:
            return "dim"
        elif brightness < 160:
            return "moderate"
        elif brightness < 200:
            return "bright"
        else:
            return "very_bright"
    
    def _categorize_contrast(self, contrast: float) -> str:
        if contrast < 20:
            return "low"
        elif contrast < 40:
            return "moderate"
        elif contrast < 60:
            return "high"
        else:
            return "very_high"
    
    def _categorize_saturation(self, saturation: float) -> str:
        if saturation < 50:
            return "low"
        elif saturation < 100:
            return "moderate"
        elif saturation < 150:
            return "high"
        else:
            return "very_high"
    
    def _categorize_texture(self, texture_strength: float) -> str:
        if texture_strength < 10:
            return "smooth"
        elif texture_strength < 25:
            return "moderate"
        elif texture_strength < 50:
            return "textured"
        else:
            return "highly_textured"
    
    def _categorize_detail_level(self, edge_density: float) -> str:
        if edge_density < 0.05:
            return "minimal"
        elif edge_density < 0.15:
            return "moderate"
        elif edge_density < 0.25:
            return "detailed"
        else:
            return "highly_detailed"
    
    def _assess_lighting_quality(self, dark_ratio: float, mid_ratio: float, bright_ratio: float) -> str:
        if mid_ratio > 0.6:
            return "balanced"
        elif dark_ratio > 0.6:
            return "underexposed"
        elif bright_ratio > 0.6:
            return "overexposed"
        else:
            return "mixed"
    
    def _assess_contrast_quality(self, std_contrast: float, local_contrast: float) -> str:
        if std_contrast > 40 and local_contrast > 1000:
            return "excellent"
        elif std_contrast > 25 and local_contrast > 500:
            return "good"
        elif std_contrast > 15:
            return "adequate"
        else:
            return "poor"
    
    def _assess_surface_character(self, texture_strength: float, texture_variance: float) -> str:
        if texture_strength < 15 and texture_variance < 100:
            return "smooth_uniform"
        elif texture_strength > 40 or texture_variance > 500:
            return "rough_varied"
        else:
            return "moderate_texture"
    
    def _assess_structural_complexity(self, edge_density: float, edge_strength: float) -> str:
        if edge_density > 0.2 and edge_strength > 30:
            return "complex"
        elif edge_density > 0.1 or edge_strength > 20:
            return "moderate"
        else:
            return "simple"
    
    def _find_dominant_hues(self, hue_hist: np.ndarray) -> List[Dict[str, Any]]:
        """Find dominant hues from histogram"""
        hue_hist_normalized = hue_hist.flatten() / hue_hist.sum()
        
        dominant_hues = []
        for i, percentage in enumerate(hue_hist_normalized):
            if percentage > 0.05:  # 5% threshold
                hue_name = self._hue_to_name(i)
                dominant_hues.append({
                    "hue_value": i,
                    "hue_name": hue_name,
                    "percentage": round(float(percentage * 100), 2)
                })
        
        return sorted(dominant_hues, key=lambda x: x['percentage'], reverse=True)[:5]
    
    def _hue_to_name(self, hue_value: int) -> str:
        """Convert HSV hue value to color name"""
        if hue_value < 10 or hue_value >= 170:
            return "red"
        elif hue_value < 25:
            return "orange"
        elif hue_value < 35:
            return "yellow"
        elif hue_value < 85:
            return "green"
        elif hue_value < 125:
            return "blue"
        else:
            return "purple"
    
    def _analyze_color_harmony(self, dominant_hues: List[Dict[str, Any]], mean_saturation: float) -> Dict[str, Any]:
        """Analyze color harmony based on hue relationships"""
        if len(dominant_hues) < 2:
            return {"harmony_type": "monochromatic", "harmony_strength": "strong"}
        
        hue_values = [h['hue_value'] for h in dominant_hues[:3]]
        
        # Check for complementary colors (opposite on color wheel)
        for i, hue1 in enumerate(hue_values):
            for hue2 in hue_values[i+1:]:
                hue_diff = abs(hue1 - hue2)
                if 80 <= hue_diff <= 100:  # Approximately opposite
                    return {"harmony_type": "complementary", "harmony_strength": "strong"}
        
        # Check for analogous colors (adjacent on color wheel)
        adjacent_count = 0
        for i, hue1 in enumerate(hue_values):
            for hue2 in hue_values[i+1:]:
                hue_diff = min(abs(hue1 - hue2), 180 - abs(hue1 - hue2))
                if hue_diff <= 30:
                    adjacent_count += 1
        
        if adjacent_count >= len(hue_values) - 1:
            return {"harmony_type": "analogous", "harmony_strength": "good"}
        
        return {"harmony_type": "varied", "harmony_strength": "moderate"}
    
    def _get_brightness_consensus(self, php_brightness: float, cv_brightness: float) -> str:
        """Get consensus brightness category from PHP and CV analysis"""
        php_cat = self._categorize_brightness(php_brightness)
        cv_cat = self._categorize_brightness(cv_brightness)
        
        if php_cat == cv_cat:
            return php_cat
        else:
            # Return average category
            avg_brightness = (php_brightness + cv_brightness) / 2
            return self._categorize_brightness(avg_brightness)
    
    def _get_contrast_consensus(self, php_contrast: float, cv_contrast: float) -> str:
        """Get consensus contrast category from PHP and CV analysis"""
        # PHP contrast is percentage, CV is standard deviation
        # Normalize CV contrast to percentage scale
        cv_contrast_normalized = min(100, cv_contrast / 64 * 100)
        
        php_cat = self._categorize_contrast(php_contrast)
        cv_cat = self._categorize_contrast(cv_contrast_normalized)
        
        if php_cat == cv_cat:
            return php_cat
        else:
            avg_contrast = (php_contrast + cv_contrast_normalized) / 2
            return self._categorize_contrast(avg_contrast)
    
    def get_timestamp(self) -> str:
        """Get current timestamp"""
        return datetime.now().strftime("%Y-%m-%d %H:%M:%S")