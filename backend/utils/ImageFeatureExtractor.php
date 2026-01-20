<?php

/**
 * Image Feature Extraction Module
 * Extracts basic visual features from room images for rule-based AI reasoning
 * 
 * This module provides quantitative and categorical visual analysis
 * without using machine learning models, keeping the system explainable
 * and deterministic.
 */
class ImageFeatureExtractor {
    
    /**
     * Extract comprehensive visual features from an image
     * 
     * @param string $image_path Path to the image file
     * @return array Structured array of visual features
     */
    public static function extractFeatures($image_path) {
        try {
            if (!file_exists($image_path)) {
                throw new Exception("Image file not found: " . $image_path);
            }
            
            // Check if GD extension is loaded
            if (!extension_loaded('gd')) {
                throw new Exception("GD extension is not loaded");
            }
            
            // Get basic image information
            $image_info = @getimagesize($image_path);
            if (!$image_info) {
                throw new Exception("Unable to read image information - file may be corrupted");
            }
            
            // Load image based on type
            $image_resource = self::loadImageResource($image_path, $image_info[2]);
            if (!$image_resource) {
                throw new Exception("Unable to load image resource - unsupported format or corrupted file");
            }
            
            $width = $image_info[0];
            $height = $image_info[1];
            
            // Extract visual features
            $features = [
                'brightness' => self::calculateBrightness($image_resource, $width, $height),
                'contrast' => self::calculateContrast($image_resource, $width, $height),
                'dominant_colors' => self::analyzeDominantColors($image_resource, $width, $height),
                'color_temperature' => self::analyzeColorTemperature($image_resource, $width, $height),
                'saturation_level' => self::analyzeSaturation($image_resource, $width, $height),
                'image_dimensions' => ['width' => $width, 'height' => $height],
                'aspect_ratio' => round($width / $height, 2)
            ];
            
            // Clean up memory
            imagedestroy($image_resource);
            
            return $features;
            
        } catch (Exception $e) {
            // Log the error but don't expose internal details
            error_log("ImageFeatureExtractor error: " . $e->getMessage());
            throw new Exception("Image analysis failed: " . $e->getMessage());
        }
    }
    
    /**
     * Load image resource based on file type
     */
    private static function loadImageResource($image_path, $image_type) {
        try {
            switch ($image_type) {
                case IMAGETYPE_JPEG:
                    $resource = @imagecreatefromjpeg($image_path);
                    break;
                case IMAGETYPE_PNG:
                    $resource = @imagecreatefrompng($image_path);
                    break;
                default:
                    return false;
            }
            
            if (!$resource) {
                error_log("Failed to create image resource from: " . $image_path);
                return false;
            }
            
            return $resource;
            
        } catch (Exception $e) {
            error_log("Error loading image resource: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate overall brightness level (0-100)
     * Uses luminance formula: 0.299*R + 0.587*G + 0.114*B
     */
    private static function calculateBrightness($image_resource, $width, $height) {
        try {
            $total_brightness = 0;
            $pixel_count = 0;
            
            // Sample pixels for performance (every 10th pixel)
            $step = max(1, min($width, $height) / 50);
            
            for ($x = 0; $x < $width; $x += $step) {
                for ($y = 0; $y < $height; $y += $step) {
                    $rgb = @imagecolorat($image_resource, (int)$x, (int)$y);
                    if ($rgb === false) continue;
                    
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    
                    // Calculate luminance
                    $brightness = (0.299 * $r + 0.587 * $g + 0.114 * $b);
                    $total_brightness += $brightness;
                    $pixel_count++;
                }
            }
            
            return $pixel_count > 0 ? round($total_brightness / $pixel_count, 1) : 128; // Default to middle brightness
            
        } catch (Exception $e) {
            error_log("Error calculating brightness: " . $e->getMessage());
            return 128; // Default brightness
        }
    }
    
    /**
     * Calculate image contrast level (0-100)
     * Based on standard deviation of brightness values
     */
    private static function calculateContrast($image_resource, $width, $height) {
        try {
            $brightness_values = [];
            $step = max(1, min($width, $height) / 50);
            
            for ($x = 0; $x < $width; $x += $step) {
                for ($y = 0; $y < $height; $y += $step) {
                    $rgb = @imagecolorat($image_resource, (int)$x, (int)$y);
                    if ($rgb === false) continue;
                    
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    
                    $brightness = (0.299 * $r + 0.587 * $g + 0.114 * $b);
                    $brightness_values[] = $brightness;
                }
            }
            
            if (count($brightness_values) < 2) return 25; // Default contrast
            
            $mean = array_sum($brightness_values) / count($brightness_values);
            $variance = 0;
            
            foreach ($brightness_values as $value) {
                $variance += pow($value - $mean, 2);
            }
            
            $std_deviation = sqrt($variance / count($brightness_values));
            
            // Normalize to 0-100 scale
            return round(min(100, ($std_deviation / 128) * 100), 1);
            
        } catch (Exception $e) {
            error_log("Error calculating contrast: " . $e->getMessage());
            return 25; // Default contrast
        }
    }
    
    /**
     * Analyze dominant colors in the image
     * Returns the most prominent color categories
     */
    private static function analyzeDominantColors($image_resource, $width, $height) {
        try {
            $color_buckets = [
                'red' => 0, 'green' => 0, 'blue' => 0,
                'yellow' => 0, 'orange' => 0, 'purple' => 0,
                'brown' => 0, 'gray' => 0, 'white' => 0, 'black' => 0
            ];
            
            $total_pixels = 0;
            $step = max(1, min($width, $height) / 30);
            
            for ($x = 0; $x < $width; $x += $step) {
                for ($y = 0; $y < $height; $y += $step) {
                    $rgb = @imagecolorat($image_resource, (int)$x, (int)$y);
                    if ($rgb === false) continue;
                    
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    
                    $color_category = self::categorizeColor($r, $g, $b);
                    $color_buckets[$color_category]++;
                    $total_pixels++;
                }
            }
            
            // Calculate percentages and sort
            $color_percentages = [];
            foreach ($color_buckets as $color => $count) {
                if ($count > 0) {
                    $color_percentages[$color] = round(($count / max(1, $total_pixels)) * 100, 1);
                }
            }
            
            arsort($color_percentages);
            
            return array_slice($color_percentages, 0, 3, true); // Top 3 colors
            
        } catch (Exception $e) {
            error_log("Error analyzing dominant colors: " . $e->getMessage());
            return ['gray' => 50, 'white' => 30, 'black' => 20]; // Default colors
        }
    }
    
    /**
     * Categorize RGB values into color names
     */
    private static function categorizeColor($r, $g, $b) {
        // Convert to HSV for better color categorization
        $hsv = self::rgbToHsv($r, $g, $b);
        $h = $hsv['h'];
        $s = $hsv['s'];
        $v = $hsv['v'];
        
        // Low saturation = grayscale
        if ($s < 0.15) {
            if ($v < 0.2) return 'black';
            if ($v > 0.8) return 'white';
            return 'gray';
        }
        
        // Categorize by hue
        if ($h < 15 || $h >= 345) return 'red';
        if ($h < 45) return 'orange';
        if ($h < 75) return 'yellow';
        if ($h < 150) return 'green';
        if ($h < 210) return 'blue';
        if ($h < 270) return 'purple';
        if ($h < 330) return 'purple';
        
        // Brown detection (low saturation orange/red)
        if (($h < 60 || $h > 300) && $s < 0.6 && $v < 0.6) return 'brown';
        
        return 'gray'; // fallback
    }
    
    /**
     * Convert RGB to HSV
     */
    private static function rgbToHsv($r, $g, $b) {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $diff = $max - $min;
        
        // Value
        $v = $max;
        
        // Saturation
        $s = ($max == 0) ? 0 : $diff / $max;
        
        // Hue
        if ($diff == 0) {
            $h = 0;
        } elseif ($max == $r) {
            $h = 60 * (($g - $b) / $diff);
        } elseif ($max == $g) {
            $h = 60 * (2 + ($b - $r) / $diff);
        } else {
            $h = 60 * (4 + ($r - $g) / $diff);
        }
        
        if ($h < 0) $h += 360;
        
        return ['h' => $h, 's' => $s, 'v' => $v];
    }
    
    /**
     * Analyze color temperature (warm/neutral/cool)
     */
    private static function analyzeColorTemperature($image_resource, $width, $height) {
        try {
            $warm_score = 0;
            $cool_score = 0;
            $total_pixels = 0;
            
            $step = max(1, min($width, $height) / 40);
            
            for ($x = 0; $x < $width; $x += $step) {
                for ($y = 0; $y < $height; $y += $step) {
                    $rgb = @imagecolorat($image_resource, (int)$x, (int)$y);
                    if ($rgb === false) continue;
                    
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    
                    // Warm colors have more red/yellow
                    if ($r > $b + 20) $warm_score++;
                    // Cool colors have more blue
                    if ($b > $r + 20) $cool_score++;
                    
                    $total_pixels++;
                }
            }
            
            if ($total_pixels === 0) {
                return ['category' => 'neutral', 'score' => 0];
            }
            
            $warm_percentage = ($warm_score / $total_pixels) * 100;
            $cool_percentage = ($cool_score / $total_pixels) * 100;
            
            if ($warm_percentage > $cool_percentage + 10) {
                return ['category' => 'warm', 'score' => round($warm_percentage, 1)];
            } elseif ($cool_percentage > $warm_percentage + 10) {
                return ['category' => 'cool', 'score' => round($cool_percentage, 1)];
            } else {
                return ['category' => 'neutral', 'score' => round(abs($warm_percentage - $cool_percentage), 1)];
            }
            
        } catch (Exception $e) {
            error_log("Error analyzing color temperature: " . $e->getMessage());
            return ['category' => 'neutral', 'score' => 0];
        }
    }
    
    /**
     * Analyze overall saturation level
     */
    private static function analyzeSaturation($image_resource, $width, $height) {
        try {
            $total_saturation = 0;
            $pixel_count = 0;
            
            $step = max(1, min($width, $height) / 50);
            
            for ($x = 0; $x < $width; $x += $step) {
                for ($y = 0; $y < $height; $y += $step) {
                    $rgb = @imagecolorat($image_resource, (int)$x, (int)$y);
                    if ($rgb === false) continue;
                    
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    
                    $hsv = self::rgbToHsv($r, $g, $b);
                    $total_saturation += $hsv['s'];
                    $pixel_count++;
                }
            }
            
            $avg_saturation = $pixel_count > 0 ? $total_saturation / $pixel_count : 0;
            return round($avg_saturation * 100, 1);
            
        } catch (Exception $e) {
            error_log("Error analyzing saturation: " . $e->getMessage());
            return 30; // Default saturation
        }
    }
}
