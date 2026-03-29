<?php

/**
 * Room Image Relevance Validator
 * 
 * Validates that generated images are contextually relevant to the selected room improvement option.
 * Ensures logical correctness and prevents inappropriate or unrelated image suggestions.
 */
class RoomImageRelevanceValidator {
    
    /**
     * Room type specific validation rules
     */
    private static $room_validation_rules = [
        'bedroom' => [
            'required_elements' => ['bed', 'sleeping area', 'bedroom furniture'],
            'forbidden_elements' => ['kitchen appliances', 'stove', 'sink', 'toilet', 'shower', 'bathtub'],
            'expected_objects' => ['bed', 'nightstand', 'dresser', 'wardrobe', 'lamp', 'pillow', 'blanket'],
            'forbidden_objects' => ['refrigerator', 'oven', 'dishwasher', 'toilet', 'bathtub', 'shower'],
            'color_appropriateness' => ['calming', 'soft', 'neutral', 'warm'],
            'style_appropriateness' => ['cozy', 'restful', 'intimate', 'personal']
        ],
        'living_room' => [
            'required_elements' => ['seating area', 'living space', 'social area'],
            'forbidden_elements' => ['bed', 'toilet', 'shower', 'bathtub', 'stove', 'oven'],
            'expected_objects' => ['sofa', 'couch', 'chair', 'coffee table', 'tv', 'entertainment center'],
            'forbidden_objects' => ['bed', 'toilet', 'bathtub', 'shower', 'stove', 'oven', 'refrigerator'],
            'color_appropriateness' => ['welcoming', 'social', 'comfortable', 'inviting'],
            'style_appropriateness' => ['social', 'entertaining', 'comfortable', 'spacious']
        ],
        'kitchen' => [
            'required_elements' => ['cooking area', 'food preparation', 'kitchen space'],
            'forbidden_elements' => ['bed', 'toilet', 'shower', 'bathtub'],
            'expected_objects' => ['stove', 'refrigerator', 'sink', 'counter', 'cabinet', 'microwave'],
            'forbidden_objects' => ['bed', 'toilet', 'bathtub', 'shower'],
            'color_appropriateness' => ['clean', 'bright', 'functional', 'hygienic'],
            'style_appropriateness' => ['functional', 'efficient', 'clean', 'organized']
        ],
        'dining_room' => [
            'required_elements' => ['dining area', 'eating space', 'meal area'],
            'forbidden_elements' => ['bed', 'toilet', 'shower', 'bathtub', 'stove', 'oven'],
            'expected_objects' => ['dining table', 'chairs', 'chandelier', 'sideboard', 'cabinet'],
            'forbidden_objects' => ['bed', 'toilet', 'bathtub', 'shower', 'stove', 'oven'],
            'color_appropriateness' => ['elegant', 'formal', 'inviting', 'sophisticated'],
            'style_appropriateness' => ['formal', 'elegant', 'dining', 'entertaining']
        ],
        'bathroom' => [
            'required_elements' => ['bathroom fixtures', 'hygiene area', 'washing space'],
            'forbidden_elements' => ['bed', 'stove', 'oven', 'refrigerator'],
            'expected_objects' => ['toilet', 'sink', 'bathtub', 'shower', 'mirror', 'towel'],
            'forbidden_objects' => ['bed', 'stove', 'oven', 'refrigerator', 'sofa'],
            'color_appropriateness' => ['clean', 'fresh', 'hygienic', 'spa-like'],
            'style_appropriateness' => ['clean', 'hygienic', 'spa', 'functional']
        ],
        'office' => [
            'required_elements' => ['work area', 'office space', 'workspace'],
            'forbidden_elements' => ['bed', 'toilet', 'shower', 'bathtub', 'stove', 'oven'],
            'expected_objects' => ['desk', 'chair', 'computer', 'bookshelf', 'filing cabinet', 'lamp'],
            'forbidden_objects' => ['bed', 'toilet', 'bathtub', 'shower', 'stove', 'oven'],
            'color_appropriateness' => ['productive', 'focused', 'professional', 'energizing'],
            'style_appropriateness' => ['professional', 'organized', 'productive', 'functional']
        ]
    ];
    
    /**
     * Validate image relevance to room type and improvement option
     * 
     * @param string $room_type Selected room type
     * @param array $detected_objects Objects detected in generated image
     * @param array $image_analysis Analysis results from AI
     * @param string $improvement_notes User's improvement notes
     * @return array Validation results with pass/fail and detailed feedback
     */
    public static function validateImageRelevance($room_type, $detected_objects, $image_analysis, $improvement_notes = '') {
        try {
            $validation_result = [
                'is_relevant' => true,
                'confidence_score' => 100,
                'validation_details' => [],
                'issues_found' => [],
                'recommendations' => [],
                'validation_metadata' => [
                    'room_type' => $room_type,
                    'validation_timestamp' => date('Y-m-d H:i:s'),
                    'validator_version' => '1.0'
                ]
            ];
            
            // Get validation rules for room type
            $rules = self::getRoomValidationRules($room_type);
            
            // Validate detected objects
            $object_validation = self::validateDetectedObjects($detected_objects, $rules);
            $validation_result = self::mergeValidationResults($validation_result, $object_validation);
            
            // Validate style and design appropriateness
            $style_validation = self::validateStyleAppropriateness($image_analysis, $rules, $room_type);
            $validation_result = self::mergeValidationResults($validation_result, $style_validation);
            
            // Validate against user improvement notes
            if (!empty($improvement_notes)) {
                $notes_validation = self::validateAgainstUserNotes($image_analysis, $improvement_notes, $room_type);
                $validation_result = self::mergeValidationResults($validation_result, $notes_validation);
            }
            
            // Calculate final relevance score
            $validation_result['confidence_score'] = self::calculateRelevanceScore($validation_result);
            $validation_result['is_relevant'] = $validation_result['confidence_score'] >= 70; // 70% threshold
            
            // Add contextual recommendations
            $validation_result['recommendations'] = self::generateRecommendations($validation_result, $room_type);
            
            return $validation_result;
            
        } catch (Exception $e) {
            error_log("Image relevance validation error: " . $e->getMessage());
            
            return [
                'is_relevant' => false,
                'confidence_score' => 0,
                'validation_details' => [],
                'issues_found' => ['Validation system error: ' . $e->getMessage()],
                'recommendations' => ['Please regenerate the image with more specific room details'],
                'validation_metadata' => [
                    'room_type' => $room_type,
                    'validation_timestamp' => date('Y-m-d H:i:s'),
                    'validator_version' => '1.0',
                    'error' => $e->getMessage()
                ]
            ];
        }
    }
    
    /**
     * Validate detected objects against room type rules
     */
    private static function validateDetectedObjects($detected_objects, $rules) {
        $validation = [
            'object_validation_passed' => true,
            'object_issues' => [],
            'object_score' => 100
        ];
        
        $major_items = $detected_objects['major_items'] ?? [];
        $forbidden_objects = $rules['forbidden_objects'] ?? [];
        $expected_objects = $rules['expected_objects'] ?? [];
        
        // Check for forbidden objects
        $forbidden_found = [];
        foreach ($major_items as $object) {
            foreach ($forbidden_objects as $forbidden) {
                if (stripos($object, $forbidden) !== false || stripos($forbidden, $object) !== false) {
                    $forbidden_found[] = $object;
                }
            }
        }
        
        if (!empty($forbidden_found)) {
            $validation['object_validation_passed'] = false;
            $validation['object_issues'][] = "Inappropriate objects detected: " . implode(', ', $forbidden_found);
            $validation['object_score'] -= (count($forbidden_found) * 30); // Heavy penalty for wrong objects
        }
        
        // Check for expected objects (bonus points)
        $expected_found = [];
        foreach ($major_items as $object) {
            foreach ($expected_objects as $expected) {
                if (stripos($object, $expected) !== false || stripos($expected, $object) !== false) {
                    $expected_found[] = $object;
                }
            }
        }
        
        if (!empty($expected_found)) {
            $validation['object_score'] += (count($expected_found) * 5); // Bonus for appropriate objects
            $validation['validation_details'][] = "Appropriate objects found: " . implode(', ', $expected_found);
        }
        
        // Ensure score doesn't exceed 100
        $validation['object_score'] = min(100, max(0, $validation['object_score']));
        
        return $validation;
    }
    
    /**
     * Validate style and design appropriateness
     */
    private static function validateStyleAppropriateness($image_analysis, $rules, $room_type) {
        $validation = [
            'style_validation_passed' => true,
            'style_issues' => [],
            'style_score' => 80 // Base score for style
        ];
        
        $design_description = $image_analysis['design_description'] ?? '';
        $style_recommendation = $image_analysis['style_recommendation'] ?? [];
        
        $appropriate_styles = $rules['style_appropriateness'] ?? [];
        $appropriate_colors = $rules['color_appropriateness'] ?? [];
        
        // Check style appropriateness in design description
        $style_matches = 0;
        foreach ($appropriate_styles as $appropriate_style) {
            if (stripos($design_description, $appropriate_style) !== false) {
                $style_matches++;
            }
        }
        
        if ($style_matches > 0) {
            $validation['style_score'] += ($style_matches * 5);
            $validation['validation_details'][] = "Appropriate style elements detected: $style_matches matches";
        }
        
        // Check color appropriateness
        $color_matches = 0;
        foreach ($appropriate_colors as $appropriate_color) {
            if (stripos($design_description, $appropriate_color) !== false) {
                $color_matches++;
            }
        }
        
        if ($color_matches > 0) {
            $validation['style_score'] += ($color_matches * 3);
            $validation['validation_details'][] = "Appropriate color themes detected: $color_matches matches";
        }
        
        // Check for inappropriate style elements
        $inappropriate_styles = self::getInappropriateStyles($room_type);
        foreach ($inappropriate_styles as $inappropriate) {
            if (stripos($design_description, $inappropriate) !== false) {
                $validation['style_validation_passed'] = false;
                $validation['style_issues'][] = "Inappropriate style element: $inappropriate";
                $validation['style_score'] -= 20;
            }
        }
        
        $validation['style_score'] = min(100, max(0, $validation['style_score']));
        
        return $validation;
    }
    
    /**
     * Validate against user improvement notes
     */
    private static function validateAgainstUserNotes($image_analysis, $improvement_notes, $room_type) {
        $validation = [
            'notes_validation_passed' => true,
            'notes_issues' => [],
            'notes_score' => 85 // Base score for user notes alignment
        ];
        
        $design_description = strtolower($image_analysis['design_description'] ?? '');
        $notes_lower = strtolower($improvement_notes);
        
        // Extract key improvement themes from user notes
        $improvement_themes = self::extractImprovementThemes($notes_lower);
        
        // Check if generated image addresses user's specific concerns
        $addressed_themes = 0;
        foreach ($improvement_themes as $theme) {
            if (stripos($design_description, $theme) !== false) {
                $addressed_themes++;
            }
        }
        
        if ($addressed_themes > 0) {
            $validation['notes_score'] += ($addressed_themes * 5);
            $validation['validation_details'][] = "User improvement themes addressed: $addressed_themes out of " . count($improvement_themes);
        } else if (!empty($improvement_themes)) {
            $validation['notes_validation_passed'] = false;
            $validation['notes_issues'][] = "Generated image doesn't address user's specific improvement requests";
            $validation['notes_score'] -= 15;
        }
        
        $validation['notes_score'] = min(100, max(0, $validation['notes_score']));
        
        return $validation;
    }
    
    /**
     * Extract improvement themes from user notes
     */
    private static function extractImprovementThemes($notes_lower) {
        $themes = [];
        
        // Common improvement keywords
        $improvement_keywords = [
            'lighting' => ['light', 'bright', 'dark', 'lamp', 'illuminate'],
            'color' => ['color', 'paint', 'wall', 'bright', 'dark'],
            'furniture' => ['furniture', 'chair', 'table', 'sofa', 'bed'],
            'space' => ['space', 'room', 'area', 'layout', 'organize'],
            'storage' => ['storage', 'organize', 'clutter', 'shelf', 'cabinet'],
            'comfort' => ['comfort', 'cozy', 'relax', 'comfortable'],
            'modern' => ['modern', 'contemporary', 'update', 'new'],
            'traditional' => ['traditional', 'classic', 'vintage', 'antique']
        ];
        
        foreach ($improvement_keywords as $theme => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($notes_lower, $keyword) !== false) {
                    $themes[] = $theme;
                    break;
                }
            }
        }
        
        return array_unique($themes);
    }
    
    /**
     * Get inappropriate styles for room type
     */
    private static function getInappropriateStyles($room_type) {
        $inappropriate_map = [
            'bedroom' => ['industrial', 'clinical', 'sterile', 'commercial'],
            'living_room' => ['clinical', 'sterile', 'laboratory'],
            'kitchen' => ['bedroom', 'intimate', 'romantic'],
            'dining_room' => ['casual', 'messy', 'cluttered'],
            'bathroom' => ['cozy', 'fabric-heavy', 'carpeted'],
            'office' => ['romantic', 'intimate', 'playful']
        ];
        
        return $inappropriate_map[$room_type] ?? [];
    }
    
    /**
     * Get validation rules for room type
     */
    private static function getRoomValidationRules($room_type) {
        return self::$room_validation_rules[$room_type] ?? self::$room_validation_rules['other'] ?? [
            'required_elements' => [],
            'forbidden_elements' => [],
            'expected_objects' => [],
            'forbidden_objects' => [],
            'color_appropriateness' => [],
            'style_appropriateness' => []
        ];
    }
    
    /**
     * Merge validation results
     */
    private static function mergeValidationResults($main_result, $sub_result) {
        // Merge validation details
        if (isset($sub_result['validation_details'])) {
            $main_result['validation_details'] = array_merge(
                $main_result['validation_details'], 
                $sub_result['validation_details']
            );
        }
        
        // Merge issues
        $issue_keys = ['object_issues', 'style_issues', 'notes_issues'];
        foreach ($issue_keys as $key) {
            if (isset($sub_result[$key])) {
                $main_result['issues_found'] = array_merge(
                    $main_result['issues_found'], 
                    $sub_result[$key]
                );
            }
        }
        
        // Update overall validation status
        $validation_keys = ['object_validation_passed', 'style_validation_passed', 'notes_validation_passed'];
        foreach ($validation_keys as $key) {
            if (isset($sub_result[$key]) && !$sub_result[$key]) {
                $main_result['is_relevant'] = false;
            }
        }
        
        return $main_result;
    }
    
    /**
     * Calculate overall relevance score
     */
    private static function calculateRelevanceScore($validation_result) {
        $scores = [];
        
        // Extract individual scores
        if (isset($validation_result['object_score'])) {
            $scores[] = $validation_result['object_score'];
        }
        if (isset($validation_result['style_score'])) {
            $scores[] = $validation_result['style_score'];
        }
        if (isset($validation_result['notes_score'])) {
            $scores[] = $validation_result['notes_score'];
        }
        
        if (empty($scores)) {
            return 50; // Default neutral score
        }
        
        // Weighted average (object validation is most important)
        $weights = [0.5, 0.3, 0.2]; // object, style, notes
        $weighted_sum = 0;
        $total_weight = 0;
        
        for ($i = 0; $i < count($scores) && $i < count($weights); $i++) {
            $weighted_sum += $scores[$i] * $weights[$i];
            $total_weight += $weights[$i];
        }
        
        return round($weighted_sum / $total_weight);
    }
    
    /**
     * Generate contextual recommendations
     */
    private static function generateRecommendations($validation_result, $room_type) {
        $recommendations = [];
        
        if (!$validation_result['is_relevant']) {
            $recommendations[] = "The generated image doesn't appear to be appropriate for a $room_type. Consider regenerating with more specific room details.";
        }
        
        if (!empty($validation_result['issues_found'])) {
            $recommendations[] = "Address the following issues: " . implode('; ', array_slice($validation_result['issues_found'], 0, 3));
        }
        
        if ($validation_result['confidence_score'] < 80) {
            $recommendations[] = "Consider providing more specific improvement notes to get better targeted suggestions.";
        }
        
        // Room-specific recommendations
        $room_recommendations = [
            'bedroom' => "Focus on creating a restful, private space with appropriate bedroom furniture and calming colors.",
            'living_room' => "Emphasize social areas, comfortable seating, and entertainment spaces.",
            'kitchen' => "Highlight functional cooking areas, storage solutions, and food preparation spaces.",
            'dining_room' => "Focus on dining furniture, elegant lighting, and spaces for entertaining.",
            'bathroom' => "Emphasize cleanliness, functionality, and appropriate bathroom fixtures.",
            'office' => "Focus on productivity, organization, and professional workspace elements."
        ];
        
        if (isset($room_recommendations[$room_type])) {
            $recommendations[] = $room_recommendations[$room_type];
        }
        
        return array_unique($recommendations);
    }
    
    /**
     * Quick validation check for immediate feedback
     * 
     * @param string $room_type Selected room type
     * @param array $detected_objects Objects detected in image
     * @return bool True if image appears relevant, false otherwise
     */
    public static function quickRelevanceCheck($room_type, $detected_objects) {
        $rules = self::getRoomValidationRules($room_type);
        $major_items = $detected_objects['major_items'] ?? [];
        $forbidden_objects = $rules['forbidden_objects'] ?? [];
        
        // Quick check for obviously wrong objects
        foreach ($major_items as $object) {
            foreach ($forbidden_objects as $forbidden) {
                if (stripos($object, $forbidden) !== false || stripos($forbidden, $object) !== false) {
                    return false; // Immediate fail for inappropriate objects
                }
            }
        }
        
        return true; // Passed quick check
    }
}