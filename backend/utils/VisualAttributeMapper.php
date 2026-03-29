<?php

/**
 * Visual Attribute Mapper
 * Converts extracted visual features into design-relevant attributes
 * for rule-based AI reasoning
 * 
 * This module provides explainable and traceable mappings from
 * quantitative visual features to qualitative design attributes.
 */
class VisualAttributeMapper {
    
    /**
     * Map visual features to design-relevant attributes
     * 
     * @param array $visual_features Features from ImageFeatureExtractor
     * @return array Mapped design attributes
     */
    public static function mapToDesignAttributes($visual_features) {
        $attributes = [
            'lighting_condition' => self::mapLightingCondition($visual_features),
            'ambience_character' => self::mapAmbienceCharacter($visual_features),
            'color_harmony' => self::mapColorHarmony($visual_features),
            'visual_balance' => self::mapVisualBalance($visual_features),
            'space_perception' => self::mapSpacePerception($visual_features),
            'style_indicators' => self::mapStyleIndicators($visual_features)
        ];
        
        // Add traceability information
        $attributes['feature_mapping_log'] = self::generateMappingLog($visual_features, $attributes);
        
        return $attributes;
    }
    
    /**
     * Map brightness and contrast to lighting conditions
     */
    private static function mapLightingCondition($features) {
        $brightness = $features['brightness'];
        $contrast = $features['contrast'];
        
        $condition = [
            'primary_assessment' => '',
            'secondary_notes' => [],
            'confidence' => 0,
            'reasoning' => ''
        ];
        
        // Primary lighting assessment
        if ($brightness < 60) {
            $condition['primary_assessment'] = 'poor_lighting';
            $condition['reasoning'] = "Low brightness level ({$brightness}/100) indicates insufficient lighting";
            
            if ($contrast < 30) {
                $condition['secondary_notes'][] = 'flat_lighting';
            } else {
                $condition['secondary_notes'][] = 'harsh_shadows';
            }
        } elseif ($brightness > 180) {
            $condition['primary_assessment'] = 'bright_lighting';
            $condition['reasoning'] = "High brightness level ({$brightness}/100) indicates abundant lighting";
            
            if ($contrast > 60) {
                $condition['secondary_notes'][] = 'strong_directional_light';
            } else {
                $condition['secondary_notes'][] = 'even_distribution';
            }
        } else {
            $condition['primary_assessment'] = 'moderate_lighting';
            $condition['reasoning'] = "Moderate brightness level ({$brightness}/100) indicates balanced lighting";
            
            if ($contrast < 25) {
                $condition['secondary_notes'][] = 'soft_even_lighting';
            } elseif ($contrast > 50) {
                $condition['secondary_notes'][] = 'mixed_light_sources';
            }
        }
        
        // Calculate confidence based on how definitive the measurements are
        $brightness_confidence = min(100, abs($brightness - 127.5) / 127.5 * 100);
        $contrast_confidence = min(100, $contrast);
        $condition['confidence'] = round(($brightness_confidence + $contrast_confidence) / 2, 1);
        
        return $condition;
    }
    
    /**
     * Map color analysis to ambience character
     */
    private static function mapAmbienceCharacter($features) {
        $dominant_colors = $features['dominant_colors'];
        $color_temp = $features['color_temperature'];
        $saturation = $features['saturation_level'];
        
        $ambience = [
            'primary_character' => '',
            'mood_indicators' => [],
            'energy_level' => '',
            'reasoning' => ''
        ];
        
        // Determine primary character based on color temperature and saturation
        if ($color_temp['category'] === 'warm' && $saturation > 40) {
            $ambience['primary_character'] = 'cozy_inviting';
            $ambience['energy_level'] = 'moderate_to_high';
            $ambience['reasoning'] = "Warm colors ({$color_temp['score']}% warm bias) with good saturation ({$saturation}%) create inviting atmosphere";
        } elseif ($color_temp['category'] === 'cool' && $saturation > 30) {
            $ambience['primary_character'] = 'calm_modern';
            $ambience['energy_level'] = 'low_to_moderate';
            $ambience['reasoning'] = "Cool colors ({$color_temp['score']}% cool bias) with moderate saturation create calm atmosphere";
        } elseif ($saturation < 20) {
            $ambience['primary_character'] = 'neutral_subdued';
            $ambience['energy_level'] = 'low';
            $ambience['reasoning'] = "Low saturation ({$saturation}%) creates subdued, neutral atmosphere";
        } else {
            $ambience['primary_character'] = 'balanced_versatile';
            $ambience['energy_level'] = 'moderate';
            $ambience['reasoning'] = "Balanced color temperature with moderate saturation creates versatile atmosphere";
        }
        
        // Add mood indicators based on dominant colors
        foreach ($dominant_colors as $color => $percentage) {
            if ($percentage > 15) {
                switch ($color) {
                    case 'red':
                        $ambience['mood_indicators'][] = 'energetic';
                        break;
                    case 'blue':
                        $ambience['mood_indicators'][] = 'calming';
                        break;
                    case 'green':
                        $ambience['mood_indicators'][] = 'natural';
                        break;
                    case 'yellow':
                        $ambience['mood_indicators'][] = 'cheerful';
                        break;
                    case 'brown':
                        $ambience['mood_indicators'][] = 'earthy';
                        break;
                    case 'gray':
                        $ambience['mood_indicators'][] = 'sophisticated';
                        break;
                    case 'white':
                        $ambience['mood_indicators'][] = 'clean';
                        break;
                    case 'black':
                        $ambience['mood_indicators'][] = 'dramatic';
                        break;
                }
            }
        }
        
        return $ambience;
    }
    
    /**
     * Map color distribution to harmony assessment
     */
    private static function mapColorHarmony($features) {
        $dominant_colors = $features['dominant_colors'];
        $color_count = count($dominant_colors);
        
        $harmony = [
            'harmony_type' => '',
            'color_distribution' => '',
            'recommendations' => [],
            'reasoning' => ''
        ];
        
        if ($color_count <= 2) {
            $harmony['harmony_type'] = 'monochromatic_simple';
            $harmony['color_distribution'] = 'limited_palette';
            $harmony['recommendations'][] = 'consider_accent_colors';
            $harmony['reasoning'] = "Only {$color_count} dominant colors detected - simple palette";
        } elseif ($color_count === 3) {
            $harmony['harmony_type'] = 'balanced_triad';
            $harmony['color_distribution'] = 'well_balanced';
            $harmony['recommendations'][] = 'maintain_current_balance';
            $harmony['reasoning'] = "Three dominant colors suggest balanced color scheme";
        } else {
            $harmony['harmony_type'] = 'complex_varied';
            $harmony['color_distribution'] = 'diverse_palette';
            $harmony['recommendations'][] = 'consider_simplification';
            $harmony['reasoning'] = "Multiple colors ({$color_count}) may benefit from simplification";
        }
        
        // Check for color balance
        $color_values = array_values($dominant_colors);
        $max_percentage = max($color_values);
        $min_percentage = min($color_values);
        
        if ($max_percentage > 60) {
            $harmony['recommendations'][] = 'add_color_variety';
        } elseif ($max_percentage - $min_percentage < 10) {
            $harmony['recommendations'][] = 'create_focal_color';
        }
        
        return $harmony;
    }
    
    /**
     * Map contrast and brightness to visual balance
     */
    private static function mapVisualBalance($features) {
        $contrast = $features['contrast'];
        $brightness = $features['brightness'];
        $aspect_ratio = $features['aspect_ratio'];
        
        $balance = [
            'contrast_balance' => '',
            'tonal_balance' => '',
            'spatial_balance' => '',
            'overall_assessment' => '',
            'reasoning' => ''
        ];
        
        // Contrast balance
        if ($contrast < 20) {
            $balance['contrast_balance'] = 'low_contrast';
        } elseif ($contrast > 70) {
            $balance['contrast_balance'] = 'high_contrast';
        } else {
            $balance['contrast_balance'] = 'balanced_contrast';
        }
        
        // Tonal balance
        if ($brightness < 80) {
            $balance['tonal_balance'] = 'dark_dominant';
        } elseif ($brightness > 170) {
            $balance['tonal_balance'] = 'light_dominant';
        } else {
            $balance['tonal_balance'] = 'balanced_tones';
        }
        
        // Spatial balance (based on aspect ratio)
        if ($aspect_ratio < 0.8 || $aspect_ratio > 1.5) {
            $balance['spatial_balance'] = 'elongated_space';
        } else {
            $balance['spatial_balance'] = 'proportioned_space';
        }
        
        // Overall assessment
        $balanced_elements = 0;
        if ($balance['contrast_balance'] === 'balanced_contrast') $balanced_elements++;
        if ($balance['tonal_balance'] === 'balanced_tones') $balanced_elements++;
        if ($balance['spatial_balance'] === 'proportioned_space') $balanced_elements++;
        
        if ($balanced_elements >= 2) {
            $balance['overall_assessment'] = 'well_balanced';
        } elseif ($balanced_elements === 1) {
            $balance['overall_assessment'] = 'partially_balanced';
        } else {
            $balance['overall_assessment'] = 'needs_balancing';
        }
        
        $balance['reasoning'] = "Contrast: {$contrast}%, Brightness: {$brightness}, Aspect: {$aspect_ratio} - {$balanced_elements}/3 elements balanced";
        
        return $balance;
    }
    
    /**
     * Map visual features to space perception
     */
    private static function mapSpacePerception($features) {
        $brightness = $features['brightness'];
        $dominant_colors = $features['dominant_colors'];
        $dimensions = $features['image_dimensions'];
        
        $perception = [
            'spaciousness' => '',
            'depth_perception' => '',
            'openness_factor' => '',
            'recommendations' => [],
            'reasoning' => ''
        ];
        
        // Spaciousness based on brightness and light colors
        $light_color_percentage = 0;
        foreach (['white', 'gray'] as $light_color) {
            if (isset($dominant_colors[$light_color])) {
                $light_color_percentage += $dominant_colors[$light_color];
            }
        }
        
        if ($brightness > 150 && $light_color_percentage > 30) {
            $perception['spaciousness'] = 'appears_spacious';
            $perception['openness_factor'] = 'high';
        } elseif ($brightness < 100 || $light_color_percentage < 15) {
            $perception['spaciousness'] = 'appears_confined';
            $perception['openness_factor'] = 'low';
            $perception['recommendations'][] = 'increase_lighting';
            $perception['recommendations'][] = 'add_light_colors';
        } else {
            $perception['spaciousness'] = 'moderate_space_feel';
            $perception['openness_factor'] = 'moderate';
        }
        
        $perception['reasoning'] = "Brightness: {$brightness}, Light colors: {$light_color_percentage}%";
        
        return $perception;
    }
    
    /**
     * Map visual features to style indicators
     */
    private static function mapStyleIndicators($features) {
        $saturation = $features['saturation_level'];
        $contrast = $features['contrast'];
        $color_temp = $features['color_temperature'];
        $dominant_colors = $features['dominant_colors'];
        
        $style_scores = [
            'modern_minimalist' => 0,
            'traditional_classic' => 0,
            'rustic_natural' => 0,
            'contemporary_bold' => 0,
            'vintage_eclectic' => 0
        ];
        
        // Modern minimalist indicators
        if ($saturation < 30) $style_scores['modern_minimalist'] += 20;
        if (isset($dominant_colors['white']) && $dominant_colors['white'] > 25) $style_scores['modern_minimalist'] += 25;
        if (isset($dominant_colors['gray']) && $dominant_colors['gray'] > 20) $style_scores['modern_minimalist'] += 20;
        if ($contrast < 40) $style_scores['modern_minimalist'] += 15;
        
        // Traditional classic indicators
        if ($color_temp['category'] === 'warm') $style_scores['traditional_classic'] += 20;
        if (isset($dominant_colors['brown']) && $dominant_colors['brown'] > 15) $style_scores['traditional_classic'] += 25;
        if ($saturation > 20 && $saturation < 60) $style_scores['traditional_classic'] += 20;
        
        // Rustic natural indicators
        if (isset($dominant_colors['brown']) && $dominant_colors['brown'] > 20) $style_scores['rustic_natural'] += 30;
        if (isset($dominant_colors['green']) && $dominant_colors['green'] > 10) $style_scores['rustic_natural'] += 20;
        if ($color_temp['category'] === 'warm') $style_scores['rustic_natural'] += 15;
        
        // Contemporary bold indicators
        if ($saturation > 50) $style_scores['contemporary_bold'] += 25;
        if ($contrast > 60) $style_scores['contemporary_bold'] += 20;
        if (isset($dominant_colors['red']) || isset($dominant_colors['blue'])) $style_scores['contemporary_bold'] += 20;
        
        // Vintage eclectic indicators
        if (count($dominant_colors) > 3) $style_scores['vintage_eclectic'] += 20;
        if ($saturation > 30 && $saturation < 70) $style_scores['vintage_eclectic'] += 15;
        
        arsort($style_scores);
        
        return [
            'primary_style_lean' => array_key_first($style_scores),
            'style_confidence' => round(reset($style_scores), 1),
            'style_scores' => $style_scores,
            'reasoning' => "Based on saturation ({$saturation}%), contrast ({$contrast}%), and color analysis"
        ];
    }
    
    /**
     * Generate mapping log for traceability
     */
    private static function generateMappingLog($visual_features, $attributes) {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'input_features' => [
                'brightness' => $visual_features['brightness'],
                'contrast' => $visual_features['contrast'],
                'color_temperature' => $visual_features['color_temperature']['category'],
                'saturation' => $visual_features['saturation_level'],
                'dominant_colors_count' => count($visual_features['dominant_colors'])
            ],
            'mapping_decisions' => [
                'lighting_assessment' => $attributes['lighting_condition']['primary_assessment'],
                'ambience_character' => $attributes['ambience_character']['primary_character'],
                'visual_balance' => $attributes['visual_balance']['overall_assessment'],
                'style_indication' => $attributes['style_indicators']['primary_style_lean']
            ],
            'confidence_scores' => [
                'lighting' => $attributes['lighting_condition']['confidence'],
                'style' => $attributes['style_indicators']['style_confidence']
            ]
        ];
    }
}
