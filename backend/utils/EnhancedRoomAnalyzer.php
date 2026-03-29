<?php

require_once __DIR__ . '/ImageFeatureExtractor.php';
require_once __DIR__ . '/VisualAttributeMapper.php';
require_once __DIR__ . '/BasicImageAnalyzer.php';
require_once __DIR__ . '/AIServiceConnector.php';
require_once __DIR__ . '/RoomImageRelevanceValidator.php';

/**
 * Enhanced Room Analyzer
 * Hybrid AI system combining image feature extraction with rule-based reasoning
 * 
 * This system maintains the existing rule-based expert system while adding
 * visual intelligence through image analysis, keeping the system explainable
 * and deterministic.
 */
class EnhancedRoomAnalyzer {
    
    /**
     * Analyze room with visual intelligence and rule-based reasoning
     * Enhanced with computer vision capabilities via AI service
     * 
     * @param string $room_type Type of room (bedroom, living_room, etc.)
     * @param string $improvement_notes User's improvement notes
     * @param string $image_path Path to uploaded room image
     * @return array Comprehensive room improvement concept
     */
    public static function analyzeRoom($room_type, $improvement_notes, $image_path) {
        try {
            // Stage 1: Extract visual features from image (existing PHP analysis)
            if (extension_loaded('gd')) {
                // Use advanced image processing if GD is available
                $visual_features = ImageFeatureExtractor::extractFeatures($image_path);
                $system_type = 'hybrid_ai_visual_rules';
            } else {
                // Use basic analysis if GD is not available
                error_log("GD extension not available, using basic image analysis");
                $visual_features = BasicImageAnalyzer::extractBasicFeatures($image_path);
                $system_type = 'hybrid_ai_basic_rules';
            }
            
            // Stage 2: Map visual features to design attributes (existing PHP analysis)
            $design_attributes = VisualAttributeMapper::mapToDesignAttributes($visual_features);
            
            // Stage 3: NEW - Enhance with AI service (computer vision + spatial reasoning + async real AI image generation)
            // NOTE: Real AI image generation is now enabled with Stable Diffusion (async to avoid timeouts)
            $ai_connector = new AIServiceConnector();
            $ai_enhancement = $ai_connector->enhanceRoomAnalysis(
                $image_path, 
                $room_type, 
                $improvement_notes, 
                $visual_features,
                false // Use async generation to avoid timeout - will be started separately
            );
            
            // Stage 4: Apply rule-based reasoning with visual intelligence (enhanced)
            $room_analysis = self::generateEnhancedRoomAnalysis(
                $room_type, 
                $improvement_notes, 
                $design_attributes, 
                $visual_features,
                $ai_enhancement
            );
            
            // Stage 5: Start ASYNC conceptual image generation (non-blocking)
            $async_image_job = null;
            if ($ai_enhancement['ai_enhancement_available']) {
                // Create improvement suggestions structure for async image generation
                $improvement_suggestions = [
                    'lighting' => $room_analysis['lighting_suggestion'] ?? '',
                    'color_ambience' => $room_analysis['color_suggestion'] ?? '',
                    'furniture_layout' => $room_analysis['furniture_suggestion'] ?? ''
                ];
                
                $async_image_job = self::startAsyncConceptualImageGeneration(
                    $ai_connector,
                    $improvement_suggestions,
                    $ai_enhancement['detected_objects'],
                    $visual_features,
                    $ai_enhancement['spatial_guidance'],
                    $room_type
                );
            }
            
            // Stage 6: Create structured output with AI enhancements
            $analysis = [
                'concept_name' => $room_analysis['concept_name'],
                'room_condition_summary' => $room_analysis['condition_summary'],
                'visual_observations' => $room_analysis['visual_observations'],
                'improvement_suggestions' => [
                    'lighting' => $room_analysis['lighting_suggestion'],
                    'color_ambience' => $room_analysis['color_suggestion'],
                    'furniture_layout' => $room_analysis['furniture_suggestion']
                ],
                'style_recommendation' => [
                    'style' => $room_analysis['recommended_style'],
                    'description' => $room_analysis['style_description'],
                    'key_elements' => $room_analysis['key_elements'],
                    'confidence' => $room_analysis['style_confidence']
                ],
                'visual_reference' => $room_analysis['visual_reference'],
                'analysis_metadata' => [
                    'room_type' => $room_type,
                    'user_notes' => $improvement_notes,
                    'image_dimensions' => $visual_features['image_dimensions']['width'] . 'x' . $visual_features['image_dimensions']['height'],
                    'analysis_timestamp' => date('Y-m-d H:i:s'),
                    'system_type' => $system_type,
                    'ai_enhancement_enabled' => $ai_enhancement['ai_enhancement_available']
                ],
                'visual_intelligence' => [
                    'extracted_features' => $visual_features,
                    'design_attributes' => $design_attributes,
                    'feature_influence' => $room_analysis['feature_influence'],
                    'analysis_method' => extension_loaded('gd') ? 'advanced_gd_processing' : 'basic_heuristic_analysis'
                ],
                // NEW: AI-powered enhancements
                'ai_enhancements' => [
                    'detected_objects' => $ai_enhancement['detected_objects'],
                    'spatial_analysis' => $ai_enhancement['spatial_zones'],
                    'enhanced_visual_features' => $ai_enhancement['enhanced_visual_features'],
                    'spatial_guidance' => $ai_enhancement['spatial_guidance'],
                    'conceptual_visualization' => $ai_enhancement['conceptual_visualization'],
                    'design_description' => $ai_enhancement['design_description'],
                    'ai_metadata' => $ai_enhancement['ai_metadata'],
                    'integration_status' => $ai_enhancement['integration_notes'],
                    'async_image_generation' => $async_image_job // Job ID for polling
                ]
            ];
            
            // Stage 7: NEW - Validate image relevance to room type and options
            if ($ai_enhancement['ai_enhancement_available'] && 
                isset($ai_enhancement['conceptual_visualization']['success']) && 
                $ai_enhancement['conceptual_visualization']['success']) {
                
                $relevance_validation = RoomImageRelevanceValidator::validateImageRelevance(
                    $room_type,
                    $ai_enhancement['detected_objects'],
                    $ai_enhancement,
                    $improvement_notes
                );
                
                $analysis['image_relevance_validation'] = $relevance_validation;
                
                // Add validation warnings if image is not relevant
                if (!$relevance_validation['is_relevant']) {
                    $analysis['validation_warnings'] = [
                        'image_relevance_issue' => true,
                        'confidence_score' => $relevance_validation['confidence_score'],
                        'issues' => $relevance_validation['issues_found'],
                        'recommendations' => $relevance_validation['recommendations']
                    ];
                    
                    // Log validation failure for monitoring
                    error_log("Room image relevance validation failed for room_type: $room_type, score: {$relevance_validation['confidence_score']}");
                }
            }
            
            return $analysis;
            
        } catch (Exception $e) {
            error_log("Enhanced room analysis error: " . $e->getMessage());
            
            // Fallback to basic analysis if AI enhancement fails
            return self::fallbackAnalysis($room_type, $improvement_notes, $image_path);
        }
    }
    
    /**
     * Generate enhanced room analysis using visual attributes and rules
     * Now includes AI-powered spatial reasoning
     */
    private static function generateEnhancedRoomAnalysis($room_type, $improvement_notes, $design_attributes, $visual_features, $ai_enhancement = null) {
        // Get base room template
        $base_template = self::getRoomTemplate($room_type);
        
        // Enhance template with visual intelligence
        $enhanced_analysis = self::applyVisualIntelligence($base_template, $design_attributes, $visual_features);
        
        // NEW: Integrate AI-powered spatial reasoning
        if ($ai_enhancement && $ai_enhancement['ai_enhancement_available']) {
            $enhanced_analysis = self::integrateAISpatialReasoning($enhanced_analysis, $ai_enhancement);
        }
        
        // Customize based on user notes
        if (!empty($improvement_notes)) {
            $enhanced_analysis = self::customizeWithUserNotes($enhanced_analysis, $improvement_notes);
        }
        
        // Add feature influence tracking
        $enhanced_analysis['feature_influence'] = self::trackFeatureInfluence($design_attributes, $ai_enhancement);
        
        return $enhanced_analysis;
    }
    
    /**
     * Apply visual intelligence to enhance base room templates
     */
    private static function applyVisualIntelligence($base_template, $design_attributes, $visual_features) {
        $enhanced = $base_template;
        
        // Enhance lighting suggestions based on visual analysis
        $lighting_condition = $design_attributes['lighting_condition'] ?? [];
        if (isset($lighting_condition['primary_assessment']) && $lighting_condition['primary_assessment'] === 'poor_lighting') {
            $enhanced['lighting_suggestion'] = "Visual analysis reveals insufficient lighting (brightness: {$visual_features['brightness']}/255). Priority should be adding multiple light sources. " . $enhanced['lighting_suggestion'];
        } elseif (isset($lighting_condition['primary_assessment']) && $lighting_condition['primary_assessment'] === 'bright_lighting') {
            $enhanced['lighting_suggestion'] = "Your room has abundant light (brightness: {$visual_features['brightness']}/255). Focus on controlling and diffusing this light. " . $enhanced['lighting_suggestion'];
        }
        
        // Enhance color suggestions based on color analysis
        $ambience = $design_attributes['ambience_character'] ?? [];
        $color_temp = $visual_features['color_temperature'] ?? [];
        if (isset($color_temp['category']) && $color_temp['category'] === 'warm') {
            $confidence = $color_temp['score'] ?? 0;
            $enhanced['color_suggestion'] = "Your room currently has a warm color palette ({$confidence}% warm bias). This creates a naturally cozy atmosphere. " . $enhanced['color_suggestion'];
        } elseif (isset($color_temp['category']) && $color_temp['category'] === 'cool') {
            $confidence = $color_temp['score'] ?? 0;
            $enhanced['color_suggestion'] = "Your room features a cool color scheme ({$confidence}% cool bias). Consider adding warm accents for comfort. " . $enhanced['color_suggestion'];
        }
        
        // Add visual observations
        $enhanced['visual_observations'] = [];
        
        if (isset($lighting_condition['primary_assessment'])) {
            $confidence = $lighting_condition['confidence'] ?? 0;
            $enhanced['visual_observations'][] = "Lighting condition: {$lighting_condition['primary_assessment']} (confidence: {$confidence}%)";
        }
        
        if (isset($visual_features['dominant_colors']) && is_array($visual_features['dominant_colors'])) {
            $enhanced['visual_observations'][] = "Dominant colors: " . implode(', ', array_keys($visual_features['dominant_colors']));
        }
        
        if (isset($color_temp['category'])) {
            $confidence = $color_temp['score'] ?? 0;
            $enhanced['visual_observations'][] = "Color temperature: {$color_temp['category']} bias ({$confidence}%)";
        }
        
        if (isset($visual_features['contrast'])) {
            $enhanced['visual_observations'][] = "Contrast level: {$visual_features['contrast']}%";
        }
        
        if (isset($visual_features['saturation_level'])) {
            $enhanced['visual_observations'][] = "Saturation level: {$visual_features['saturation_level']}%";
        }
        
        return $enhanced;
    }
    
    /**
     * NEW: Integrate AI-powered spatial reasoning and collaborative pipeline results
     */
    private static function integrateAISpatialReasoning($enhanced_analysis, $ai_enhancement) {
        if (!$ai_enhancement || !$ai_enhancement['ai_enhancement_available']) {
            return $enhanced_analysis;
        }
        
        // Extract collaborative AI pipeline results
        $detected_objects = $ai_enhancement['detected_objects'] ?? [];
        $spatial_guidance = $ai_enhancement['spatial_guidance'] ?? [];
        $conceptual_visualization = $ai_enhancement['conceptual_visualization'] ?? [];
        $design_description = $ai_enhancement['design_description'] ?? '';
        
        // Enhance lighting suggestions with object detection insights
        if (!empty($detected_objects['major_items'])) {
            $major_items = $detected_objects['major_items'];
            $lighting_enhancement = self::enhanceLightingWithObjects($enhanced_analysis['lighting_suggestion'], $major_items);
            $enhanced_analysis['lighting_suggestion'] = $lighting_enhancement;
        }
        
        // Enhance furniture suggestions with spatial guidance
        if (!empty($spatial_guidance['placement_recommendations'])) {
            $furniture_enhancement = self::enhanceFurnitureWithSpatialGuidance(
                $enhanced_analysis['furniture_suggestion'], 
                $spatial_guidance['placement_recommendations']
            );
            $enhanced_analysis['furniture_suggestion'] = $furniture_enhancement;
        }
        
        // Add AI-powered visual reference from collaborative pipeline
        if (!empty($design_description)) {
            $enhanced_analysis['visual_reference'] = $design_description . " " . $enhanced_analysis['visual_reference'];
        }
        
        // Enhance concept name with AI insights
        if (!empty($conceptual_visualization['success'])) {
            $enhanced_analysis['concept_name'] = "AI-Enhanced " . $enhanced_analysis['concept_name'];
        }
        
        // Add collaborative AI metadata
        $enhanced_analysis['collaborative_ai_integration'] = [
            'objects_detected' => count($detected_objects['major_items'] ?? []),
            'spatial_recommendations' => count($spatial_guidance['placement_recommendations'] ?? []),
            'design_description_generated' => !empty($design_description),
            'conceptual_image_generated' => $conceptual_visualization['success'] ?? false,
            'pipeline_stages_completed' => $ai_enhancement['ai_metadata']['stages_completed'] ?? 2
        ];
        
        return $enhanced_analysis;
    }
    
    /**
     * Enhance lighting suggestions with detected objects
     */
    private static function enhanceLightingWithObjects($base_lighting, $detected_objects) {
        $object_lighting_map = [
            'tv' => 'Consider bias lighting behind the TV to reduce eye strain and improve viewing comfort.',
            'bed' => 'Add bedside reading lights and consider dimmable overhead lighting for relaxation.',
            'desk' => 'Ensure adequate task lighting for the workspace area.',
            'dining_table' => 'A pendant light or chandelier above the dining table creates focused ambiance.',
            'couch' => 'Add floor lamps or table lamps near seating areas for comfortable reading light.',
            'chair' => 'Provide adequate lighting for seating areas to create inviting spaces.'
        ];
        
        $enhancements = [];
        foreach ($detected_objects as $object) {
            $object_lower = strtolower($object);
            if (isset($object_lighting_map[$object_lower])) {
                $enhancements[] = $object_lighting_map[$object_lower];
            }
        }
        
        if (!empty($enhancements)) {
            return $base_lighting . " AI-detected objects suggest: " . implode(' ', $enhancements);
        }
        
        return $base_lighting;
    }
    
    /**
     * Enhance furniture suggestions with spatial guidance
     */
    private static function enhanceFurnitureWithSpatialGuidance($base_furniture, $spatial_recommendations) {
        $spatial_insights = [];
        
        foreach ($spatial_recommendations as $recommendation) {
            if (is_array($recommendation) && isset($recommendation['suggestion'])) {
                $spatial_insights[] = $recommendation['suggestion'];
            } elseif (is_string($recommendation)) {
                $spatial_insights[] = $recommendation;
            }
        }
        
        if (!empty($spatial_insights)) {
            $spatial_text = implode(' ', array_slice($spatial_insights, 0, 3)); // Limit to avoid overwhelming
            return $base_furniture . " Spatial analysis suggests: " . $spatial_text;
        }
        
        return $base_furniture;
    }
    
    /**
     * Get room-specific template
     */
    private static function getRoomTemplate($room_type) {
        $templates = [
            'bedroom' => [
                'concept_name' => 'Restful Sleep Sanctuary',
                'condition_summary' => 'Your bedroom shows potential for creating a more restful and organized sleeping environment.',
                'lighting_suggestion' => 'Consider adding layered lighting with bedside lamps for reading and dimmable overhead lighting for ambiance.',
                'color_suggestion' => 'Soft, calming colors like muted blues, gentle greens, or warm neutrals can promote better sleep.',
                'furniture_suggestion' => 'Ensure your bed is the focal point, with adequate space for movement and storage solutions for organization.',
                'recommended_style' => 'Contemporary Comfort',
                'style_description' => 'A blend of modern functionality with cozy, personal touches that promote relaxation.',
                'key_elements' => ['Comfortable bedding', 'Adequate storage', 'Soft lighting', 'Calming colors'],
                'visual_reference' => 'Imagine a serene retreat with soft textures, organized storage, and gentle lighting that creates a peaceful atmosphere for rest and rejuvenation.',
                'style_confidence' => 75
            ],
            'living_room' => [
                'concept_name' => 'Social Gathering Hub',
                'condition_summary' => 'Your living room has good potential for creating a welcoming space for relaxation and entertainment.',
                'lighting_suggestion' => 'Layer different light sources including ambient ceiling lights, task lighting for reading, and accent lighting for atmosphere.',
                'color_suggestion' => 'Create visual interest with a cohesive color palette that reflects your personality while maintaining harmony.',
                'furniture_suggestion' => 'Arrange seating to encourage conversation, ensure clear pathways, and create distinct zones for different activities.',
                'recommended_style' => 'Modern Comfort',
                'style_description' => 'A contemporary approach that balances style with functionality for everyday living.',
                'key_elements' => ['Comfortable seating', 'Good lighting', 'Entertainment area', 'Storage solutions'],
                'visual_reference' => 'Envision a welcoming space where family and friends naturally gather, with comfortable seating arranged for conversation and entertainment.',
                'style_confidence' => 70
            ],
            'kitchen' => [
                'concept_name' => 'Culinary Workspace',
                'condition_summary' => 'Your kitchen shows potential for improved functionality and aesthetic appeal.',
                'lighting_suggestion' => 'Ensure adequate task lighting for food preparation areas and consider under-cabinet lighting for better visibility.',
                'color_suggestion' => 'Choose colors that are both practical and inviting, considering how they work with your cabinetry and countertops.',
                'furniture_suggestion' => 'Optimize the work triangle between sink, stove, and refrigerator, and ensure adequate counter space for food preparation.',
                'recommended_style' => 'Functional Modern',
                'style_description' => 'Clean lines and practical design that makes cooking and entertaining enjoyable.',
                'key_elements' => ['Efficient layout', 'Good lighting', 'Adequate storage', 'Easy-to-clean surfaces'],
                'visual_reference' => 'Picture a well-organized culinary space where cooking is a pleasure, with everything within easy reach and good lighting for all tasks.',
                'style_confidence' => 65
            ],
            'dining_room' => [
                'concept_name' => 'Elegant Dining Experience',
                'condition_summary' => 'Your dining area has potential for creating memorable meal experiences.',
                'lighting_suggestion' => 'A statement light fixture over the dining table creates ambiance, supplemented by ambient lighting around the room.',
                'color_suggestion' => 'Choose colors that create an inviting atmosphere for meals, considering both natural and artificial lighting.',
                'furniture_suggestion' => 'Ensure the dining table is appropriately sized for the space with comfortable seating and easy access.',
                'recommended_style' => 'Classic Elegance',
                'style_description' => 'Timeless design that creates an inviting atmosphere for dining and entertaining.',
                'key_elements' => ['Appropriate table size', 'Comfortable seating', 'Ambient lighting', 'Storage for dining items'],
                'visual_reference' => 'Imagine an inviting dining space where meals become special occasions, with beautiful lighting and comfortable seating.',
                'style_confidence' => 70
            ],
            'other' => [
                'concept_name' => 'Versatile Living Space',
                'condition_summary' => 'This space shows potential for optimization based on its primary function.',
                'lighting_suggestion' => 'Ensure lighting is appropriate for the room\'s main activities, with options for different moods and tasks.',
                'color_suggestion' => 'Choose colors that support the room\'s function while creating a pleasant atmosphere.',
                'furniture_suggestion' => 'Arrange furniture to support the room\'s primary purpose while maintaining good flow and accessibility.',
                'recommended_style' => 'Adaptive Contemporary',
                'style_description' => 'Flexible design that can adapt to various needs while maintaining visual appeal.',
                'key_elements' => ['Flexible layout', 'Appropriate lighting', 'Functional storage', 'Comfortable atmosphere'],
                'visual_reference' => 'Envision a adaptable space that serves its purpose beautifully while remaining comfortable and visually appealing.',
                'style_confidence' => 60
            ]
        ];
        
        return $templates[$room_type] ?? $templates['other'];
    }
    
    /**
     * Customize analysis with user notes
     */
    private static function customizeWithUserNotes($enhanced_analysis, $improvement_notes) {
        $notes_lower = strtolower($improvement_notes);
        
        // Lighting customization
        if (strpos($notes_lower, 'dark') !== false || strpos($notes_lower, 'lighting') !== false) {
            $enhanced_analysis['lighting_suggestion'] = 'Based on your notes about lighting, ' . $enhanced_analysis['lighting_suggestion'];
        }
        
        // Color customization
        if (strpos($notes_lower, 'color') !== false || strpos($notes_lower, 'paint') !== false) {
            $enhanced_analysis['color_suggestion'] = 'Considering your interest in color changes, ' . $enhanced_analysis['color_suggestion'];
        }
        
        // Furniture customization
        if (strpos($notes_lower, 'furniture') !== false || strpos($notes_lower, 'space') !== false) {
            $enhanced_analysis['furniture_suggestion'] = 'Taking into account your furniture concerns, ' . $enhanced_analysis['furniture_suggestion'];
        }
        
        return $enhanced_analysis;
    }
    
    /**
     * Track feature influence for explainability
     */
    private static function trackFeatureInfluence($design_attributes, $ai_enhancement = null) {
        $influence = [];
        
        // Visual feature influence
        $lighting_condition = $design_attributes['lighting_condition'] ?? [];
        if (isset($lighting_condition['confidence']) && $lighting_condition['confidence'] > 60) {
            $influence['lighting_influence'] = [
                'impact' => 'high',
                'confidence' => $lighting_condition['confidence'],
                'reasoning' => $lighting_condition['reasoning'] ?? 'Strong lighting assessment'
            ];
        }
        
        $ambience = $design_attributes['ambience_character'] ?? [];
        if (isset($ambience['confidence']) && $ambience['confidence'] > 50) {
            $influence['color_influence'] = [
                'impact' => 'medium',
                'confidence' => $ambience['confidence'],
                'reasoning' => 'Color analysis influenced recommendations'
            ];
        }
        
        // AI enhancement influence
        if ($ai_enhancement && $ai_enhancement['ai_enhancement_available']) {
            $detected_objects = $ai_enhancement['detected_objects'] ?? [];
            if (!empty($detected_objects['major_items'])) {
                $influence['ai_influence'] = [
                    'impact' => 'high',
                    'confidence' => 85,
                    'reasoning' => 'Object detection and spatial analysis enhanced recommendations'
                ];
            }
        }
        
        return $influence;
    }
    
    /**
     * Start asynchronous conceptual image generation (non-blocking)
     * Returns immediately with job_id for status polling
     */
    private static function startAsyncConceptualImageGeneration($ai_connector, $improvement_suggestions, $detected_objects, $visual_features, $spatial_guidance, $room_type) {
        try {
            $result = $ai_connector->startAsyncConceptualImageGeneration(
                $improvement_suggestions,
                $detected_objects,
                $visual_features,
                $spatial_guidance,
                $room_type
            );
            
            if ($result['success']) {
                return [
                    'job_id' => $result['job_id'],
                    'status' => 'pending',
                    'message' => 'Conceptual image generation started',
                    'estimated_completion_time' => $result['estimated_completion_time'] ?? '30-60 seconds'
                ];
            } else {
                return [
                    'job_id' => null,
                    'status' => 'failed',
                    'message' => 'Failed to start image generation',
                    'error' => $result['error'] ?? 'Unknown error'
                ];
            }
        } catch (Exception $e) {
            error_log("Async image generation start failed: " . $e->getMessage());
            return [
                'job_id' => null,
                'status' => 'failed',
                'message' => 'Image generation temporarily unavailable',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Fallback analysis when AI enhancement fails
     */
    private static function fallbackAnalysis($room_type, $improvement_notes, $image_path) {
        try {
            if (extension_loaded('gd')) {
                $visual_features = ImageFeatureExtractor::extractFeatures($image_path);
                $design_attributes = VisualAttributeMapper::mapToDesignAttributes($visual_features);
            } else {
                $visual_features = BasicImageAnalyzer::extractBasicFeatures($image_path);
                $design_attributes = [
                    'lighting_condition' => ['primary_assessment' => 'moderate_lighting', 'confidence' => 50],
                    'ambience_character' => ['primary_character' => 'neutral_balanced', 'confidence' => 50],
                    'style_indicators' => ['detected_style' => 'contemporary']
                ];
            }
            
            $base_template = self::getRoomTemplate($room_type);
            $enhanced_analysis = self::applyVisualIntelligence($base_template, $design_attributes, $visual_features);
            
            if (!empty($improvement_notes)) {
                $enhanced_analysis = self::customizeWithUserNotes($enhanced_analysis, $improvement_notes);
            }
            
            return [
                'concept_name' => $enhanced_analysis['concept_name'],
                'room_condition_summary' => $enhanced_analysis['condition_summary'],
                'visual_observations' => $enhanced_analysis['visual_observations'] ?? [],
                'improvement_suggestions' => [
                    'lighting' => $enhanced_analysis['lighting_suggestion'],
                    'color_ambience' => $enhanced_analysis['color_suggestion'],
                    'furniture_layout' => $enhanced_analysis['furniture_suggestion']
                ],
                'style_recommendation' => [
                    'style' => $enhanced_analysis['recommended_style'],
                    'description' => $enhanced_analysis['style_description'],
                    'key_elements' => $enhanced_analysis['key_elements'],
                    'confidence' => $enhanced_analysis['style_confidence']
                ],
                'visual_reference' => $enhanced_analysis['visual_reference'],
                'analysis_metadata' => [
                    'room_type' => $room_type,
                    'user_notes' => $improvement_notes,
                    'analysis_timestamp' => date('Y-m-d H:i:s'),
                    'system_type' => 'fallback_analysis',
                    'ai_enhancement_enabled' => false
                ],
                'visual_intelligence' => [
                    'extracted_features' => $visual_features,
                    'design_attributes' => $design_attributes,
                    'analysis_method' => extension_loaded('gd') ? 'basic_gd_processing' : 'heuristic_analysis'
                ],
                'ai_enhancements' => [
                    'ai_enhancement_available' => false,
                    'integration_notes' => [
                        'status' => 'fallback_mode',
                        'message' => 'AI service temporarily unavailable, using rule-based analysis'
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Fallback analysis error: " . $e->getMessage());
            
            $base_template = self::getRoomTemplate($room_type);
            
            return [
                'concept_name' => $base_template['concept_name'],
                'room_condition_summary' => $base_template['condition_summary'],
                'visual_observations' => ['Basic room analysis completed'],
                'improvement_suggestions' => [
                    'lighting' => $base_template['lighting_suggestion'],
                    'color_ambience' => $base_template['color_suggestion'],
                    'furniture_layout' => $base_template['furniture_suggestion']
                ],
                'style_recommendation' => [
                    'style' => $base_template['recommended_style'],
                    'description' => $base_template['style_description'],
                    'key_elements' => $base_template['key_elements'],
                    'confidence' => 50
                ],
                'visual_reference' => $base_template['visual_reference'],
                'analysis_metadata' => [
                    'room_type' => $room_type,
                    'user_notes' => $improvement_notes,
                    'analysis_timestamp' => date('Y-m-d H:i:s'),
                    'system_type' => 'basic_template',
                    'ai_enhancement_enabled' => false
                ],
                'ai_enhancements' => [
                    'ai_enhancement_available' => false,
                    'integration_notes' => [
                        'status' => 'basic_fallback',
                        'message' => 'Visual analysis temporarily unavailable, using rule-based recommendations'
                    ]
                ]
            ];
        }
    }
}