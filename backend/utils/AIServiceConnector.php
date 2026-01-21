<?php

/**
 * AI Service Connector
 * Connects the existing PHP backend to the Python AI service for enhanced room analysis
 * 
 * This connector maintains backward compatibility while adding computer vision capabilities
 * through the FastAPI service.
 */
class AIServiceConnector {
    
    private $ai_service_url;
    private $timeout;
    private $fallback_enabled;
    
    public function __construct($ai_service_url = 'http://127.0.0.1:8000', $timeout = 300, $fallback_enabled = true) {
        $this->ai_service_url = rtrim($ai_service_url, '/');
        $this->timeout = $timeout;
        $this->fallback_enabled = $fallback_enabled;
    }
    
    /**
     * Enhance room analysis with collaborative AI pipeline
     * 
     * @param string $image_path Path to the room image
     * @param string $room_type Type of room
     * @param string $improvement_notes User's improvement notes
     * @param array $existing_visual_features Visual features from PHP analysis
     * @param bool $generate_concept Whether to generate conceptual visualization
     * @return array Enhanced analysis with collaborative AI pipeline results
     */
    public function enhanceRoomAnalysis($image_path, $room_type, $improvement_notes, $existing_visual_features = [], $generate_concept = true) {
        try {
            // Check if AI service is available
            if (!$this->isServiceAvailable()) {
                if ($this->fallback_enabled) {
                    return $this->getFallbackAnalysis($room_type, $improvement_notes);
                } else {
                    throw new Exception("AI service is not available");
                }
            }
            
            // Prepare request data for collaborative pipeline
            $post_data = [
                'room_type' => $room_type,
                'improvement_notes' => $improvement_notes,
                'existing_features' => json_encode($existing_visual_features),
                'generate_concept' => $generate_concept ? 'true' : 'false'
            ];
            
            // Prepare file data
            if (!file_exists($image_path)) {
                throw new Exception("Image file not found: " . $image_path);
            }
            
            $file_data = [
                'image' => new CURLFile($image_path, mime_content_type($image_path), basename($image_path))
            ];
            
            // Make request to collaborative AI pipeline
            $response = $this->makeRequest('/analyze-room', array_merge($post_data, $file_data));
            
            if ($response && isset($response['success']) && $response['success']) {
                return $this->processCollaborativeAIResponse($response);
            } else {
                $error_message = isset($response['detail']) ? $response['detail'] : 'Unknown AI service error';
                throw new Exception("Collaborative AI pipeline error: " . $error_message);
            }
            
        } catch (Exception $e) {
            error_log("Collaborative AI enhancement failed: " . $e->getMessage());
            
            if ($this->fallback_enabled) {
                return $this->getFallbackAnalysis($room_type, $improvement_notes, $e->getMessage());
            } else {
                throw $e;
            }
        }
    }
    
    /**
     * Check if AI service is available
     */
    public function isServiceAvailable() {
        try {
            $response = $this->makeRequest('/health', [], 'GET');
            return $response && isset($response['status']) && $response['status'] === 'healthy';
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Start asynchronous real AI conceptual image generation
     * 
     * @param array $improvement_suggestions Improvement suggestions from analysis
     * @param array $detected_objects Detected objects from vision analysis
     * @param array $visual_features Visual features from image analysis
     * @param array $spatial_guidance Spatial guidance from reasoning
     * @param string $room_type Type of room
     * @return array Job initiation results with job_id
     */
    public function startAsyncConceptualImageGeneration($improvement_suggestions, $detected_objects, $visual_features, $spatial_guidance, $room_type, $job_id = null) {
        try {
            // Check if AI service is available
            if (!$this->isServiceAvailable()) {
                throw new Exception("AI service is not available for asynchronous image generation");
            }
            
            // Prepare request data for asynchronous conceptual generation
            $post_data = [
                'improvement_suggestions' => json_encode($improvement_suggestions),
                'detected_objects' => json_encode($detected_objects),
                'visual_features' => json_encode($visual_features),
                'spatial_guidance' => json_encode($spatial_guidance),
                'room_type' => $room_type,
                'save_image' => 'true'
            ];
            
            // Add job_id if provided
            if ($job_id) {
                $post_data['job_id'] = $job_id;
            }
            
            // Make request to start asynchronous conceptual generation
            $response = $this->makeRequest('/generate-concept', $post_data);
            
            if ($response && isset($response['success']) && $response['success']) {
                return [
                    'success' => true,
                    'job_id' => $response['job_id'],
                    'status' => $response['status'],
                    'message' => $response['message'],
                    'estimated_completion_time' => $response['estimated_completion_time'] ?? '30-60 seconds',
                    'endpoint_metadata' => $response['endpoint_metadata'] ?? []
                ];
            } else {
                $error_message = isset($response['detail']) ? $response['detail'] : 'Unknown AI service error';
                throw new Exception("Async conceptual generation start error: " . $error_message);
            }
            
        } catch (Exception $e) {
            error_log("Async conceptual image generation start failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback_message' => 'Asynchronous image generation temporarily unavailable'
            ];
        }
    }
    
    /**
     * Check status of asynchronous image generation job
     * 
     * @param string $job_id Job ID from startAsyncConceptualImageGeneration
     * @return array Job status and results
     */
    public function checkImageGenerationStatus($job_id) {
        try {
            // Check if AI service is available
            if (!$this->isServiceAvailable()) {
                throw new Exception("AI service is not available for status check");
            }
            
            // Make request to check job status
            $response = $this->makeRequest("/image-status/{$job_id}", [], 'GET');
            
            if ($response && isset($response['job_id'])) {
                $result = [
                    'success' => true,
                    'job_id' => $response['job_id'],
                    'status' => $response['status'],
                    'created_at' => $response['created_at']
                ];
                
                if ($response['status'] === 'completed') {
                    $result['completed_at'] = $response['completed_at'];
                    $result['image_url'] = $response['image_url'];
                    $result['image_path'] = $response['image_path'];
                    $result['disclaimer'] = $response['disclaimer'];
                    $result['generation_metadata'] = $response['generation_metadata'] ?? [];
                    
                    // Verify file exists
                    if (isset($response['image_path']) && !empty($response['image_path'])) {
                        if (file_exists($response['image_path'])) {
                            $result['file_verification'] = 'Image file verified';
                            $result['file_size'] = filesize($response['image_path']);
                        } else {
                            $result['file_verification'] = 'Image generated but file not accessible';
                        }
                    }
                    
                } elseif ($response['status'] === 'failed') {
                    $result['completed_at'] = $response['completed_at'] ?? null;
                    $result['error_message'] = $response['error_message'];
                    $result['fallback_message'] = $response['fallback_message'] ?? 'Image generation failed';
                    
                } elseif ($response['status'] === 'processing') {
                    $result['estimated_remaining_seconds'] = $response['estimated_remaining_seconds'] ?? 30;
                    $result['progress_message'] = $response['progress_message'] ?? 'Generating image...';
                    
                } else { // pending
                    $result['progress_message'] = $response['progress_message'] ?? 'Image generation queued...';
                }
                
                return $result;
            } else {
                throw new Exception("Invalid response from status endpoint");
            }
            
        } catch (Exception $e) {
            error_log("Image generation status check failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 'unknown'
            ];
        }
    }
    
    /**
     * Call Gemini API for text generation and refinement
     * 
     * @param string $prompt The prompt to send to Gemini
     * @param array $options Additional options for the API call
     * @return string The generated text response
     */
    public function callGeminiAPI($prompt, $options = []) {
        try {
            // Prepare request data for Gemini API
            $post_data = [
                'prompt' => $prompt,
                'max_tokens' => $options['max_tokens'] ?? 500,
                'temperature' => $options['temperature'] ?? 0.7,
                'model' => $options['model'] ?? 'gemini-pro'
            ];
            
            // Make request to AI service Gemini endpoint
            $response = $this->makeRequest('/gemini-generate', $post_data);
            
            if ($response && isset($response['success']) && $response['success']) {
                return $response['generated_text'] ?? '';
            } else {
                $error_message = isset($response['detail']) ? $response['detail'] : 'Gemini API error';
                throw new Exception("Gemini API error: " . $error_message);
            }
            
        } catch (Exception $e) {
            error_log("Gemini API call failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Make HTTP request to AI service
     */
    private function makeRequest($endpoint, $data = [], $method = 'POST') {
        $url = $this->ai_service_url . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout); // Increased to 5 minutes for AI image generation
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: " . $error);
        }
        
        if ($http_code !== 200) {
            throw new Exception("HTTP error: " . $http_code);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON decode error: " . json_last_error_msg());
        }
        
        return $decoded;
    }
    
    /**
     * Process collaborative AI pipeline response
     * 
     * @param array $response Response from AI service
     * @return array Processed collaborative AI results
     */
    private function processCollaborativeAIResponse($response) {
        $pipeline_results = $response['collaborative_pipeline_results'] ?? [];
        $metadata = $response['analysis_metadata'] ?? [];
        
        // Extract stage results
        $vision_analysis = $pipeline_results['stage_1_vision_analysis'] ?? [];
        $reasoning_results = $pipeline_results['stage_2_rule_based_reasoning'] ?? [];
        $conceptual_results = $pipeline_results['stage_3_4_conceptual_generation'] ?? [];
        
        // Process detected objects
        $detected_objects = $vision_analysis['detected_objects'] ?? [];
        $objects_summary = $this->summarizeDetectedObjects($detected_objects);
        
        // Process spatial analysis
        $spatial_zones = $vision_analysis['spatial_zones'] ?? [];
        $spatial_summary = $this->summarizeSpatialAnalysis($spatial_zones);
        
        // Process enhanced visual features
        $enhanced_visual_features = $vision_analysis['enhanced_visual_features'] ?? [];
        
        // Process spatial guidance
        $spatial_guidance = $reasoning_results['spatial_guidance'] ?? [];
        $guidance_summary = $this->summarizeSpatialGuidance($spatial_guidance);
        
        // Process conceptual visualization
        $conceptual_visualization = $this->processConceptualVisualization($conceptual_results);
        
        return [
            'ai_enhancement_available' => true,
            'pipeline_type' => 'collaborative_ai_hybrid',
            'stages_completed' => $metadata['stages_completed'] ?? 2,
            
            // Stage 1: Vision Analysis Results
            'detected_objects' => $objects_summary,
            'spatial_zones' => $spatial_summary,
            'enhanced_visual_features' => $enhanced_visual_features,
            
            // Stage 2: Rule-Based Reasoning Results
            'spatial_guidance' => $guidance_summary,
            'improvement_suggestions' => $reasoning_results['improvement_suggestions'] ?? [],
            
            // Stage 3 & 4: Collaborative Conceptual Generation Results
            'conceptual_visualization' => $conceptual_visualization,
            'design_description' => $conceptual_results['design_description'] ?? '',
            
            // Metadata
            'ai_metadata' => [
                'pipeline_type' => $metadata['pipeline_type'] ?? 'collaborative_ai_hybrid',
                'gemini_api_available' => $metadata['gemini_api_available'] ?? false,
                'diffusion_device' => $metadata['diffusion_device'] ?? 'unknown',
                'analysis_timestamp' => $metadata['analysis_timestamp'] ?? date('c'),
                'room_type' => $metadata['room_type'] ?? '',
                'stages_completed' => $metadata['stages_completed'] ?? 2
            ],
            
            'integration_notes' => [
                'vision_analysis_enhanced' => !empty($detected_objects),
                'spatial_reasoning_applied' => !empty($spatial_guidance),
                'gemini_description_generated' => !empty($conceptual_results['design_description']),
                'conceptual_image_generated' => $conceptual_visualization['success'] ?? false,
                'collaborative_pipeline_status' => 'operational'
            ]
        ];
    }
    
    /**
     * Process conceptual visualization results
     */
    private function processConceptualVisualization($conceptual_results) {
        if (!$conceptual_results || !isset($conceptual_results['success']) || !$conceptual_results['success']) {
            return [
                'success' => false,
                'error' => $conceptual_results['error'] ?? 'Conceptual visualization failed',
                'fallback_message' => $conceptual_results['fallback_message'] ?? 'Conceptual visualization temporarily unavailable',
                'disclaimer' => 'Conceptual Visualization / Inspirational Preview - Not an exact reconstruction'
            ];
        }
        
        $collaborative_pipeline = $conceptual_results['collaborative_pipeline'] ?? [];
        $conceptual_image = $conceptual_results['conceptual_image'] ?? [];
        
        return [
            'success' => true,
            'image_path' => $conceptual_image['image_path'] ?? '',
            'image_url' => $conceptual_image['image_url'] ?? '',
            'disclaimer' => $conceptual_image['disclaimer'] ?? 'Conceptual Visualization / Inspirational Preview',
            'design_description' => $conceptual_results['design_description'] ?? '',
            'pipeline_stages' => [
                'vision_analysis' => $collaborative_pipeline['stage_1_vision_analysis']['status'] ?? 'unknown',
                'rule_based_reasoning' => $collaborative_pipeline['stage_2_rule_based_reasoning']['status'] ?? 'unknown',
                'gemini_description' => $collaborative_pipeline['stage_3_gemini_description']['status'] ?? 'unknown',
                'diffusion_visualization' => $collaborative_pipeline['stage_4_diffusion_visualization']['status'] ?? 'unknown'
            ],
            'generation_metadata' => $conceptual_image['generation_metadata'] ?? [],
            'pipeline_metadata' => $conceptual_results['pipeline_metadata'] ?? []
        ];
    }
    
    /**
     * Summarize detected objects for PHP integration
     */
    private function summarizeDetectedObjects($detected_objects) {
        if (empty($detected_objects) || !isset($detected_objects['objects'])) {
            return [
                'total_objects' => 0,
                'major_items' => [],
                'furniture_categories' => [],
                'detection_summary' => 'No objects detected'
            ];
        }
        
        $objects = $detected_objects['objects'];
        $major_items = [];
        $furniture_categories = [];
        
        foreach ($objects as $obj) {
            if (isset($obj['confidence']) && $obj['confidence'] > 0.5) {
                $major_items[] = $obj['class_name'] ?? 'unknown';
                
                if (isset($obj['furniture_category'])) {
                    $furniture_categories[] = $obj['furniture_category'];
                }
            }
        }
        
        return [
            'total_objects' => count($objects),
            'major_items' => array_unique($major_items),
            'furniture_categories' => array_unique($furniture_categories),
            'detection_summary' => $detected_objects['detection_summary'] ?? 'Objects detected successfully',
            'detection_confidence' => $detected_objects['average_confidence'] ?? 0.0
        ];
    }
    
    /**
     * Summarize spatial analysis for PHP integration
     */
    private function summarizeSpatialAnalysis($spatial_zones) {
        if (empty($spatial_zones)) {
            return [
                'zones_analyzed' => 0,
                'spatial_insights' => [],
                'layout_assessment' => 'No spatial analysis available'
            ];
        }
        
        $zones = $spatial_zones['spatial_zones'] ?? [];
        $insights = $spatial_zones['spatial_insights'] ?? [];
        $issues = $spatial_zones['spatial_issues'] ?? [];
        
        return [
            'zones_analyzed' => count($zones),
            'spatial_insights' => $insights,
            'spatial_issues' => $issues,
            'layout_assessment' => count($issues) > 0 ? 'Layout improvements recommended' : 'Layout appears well-organized',
            'zone_distribution' => $this->analyzeZoneDistribution($zones)
        ];
    }
    
    /**
     * Summarize spatial guidance for PHP integration
     */
    private function summarizeSpatialGuidance($spatial_guidance) {
        if (empty($spatial_guidance)) {
            return [
                'placement_recommendations' => [],
                'layout_improvements' => [],
                'safety_considerations' => [],
                'guidance_summary' => 'No spatial guidance available'
            ];
        }
        
        return [
            'placement_recommendations' => $spatial_guidance['placement_guidance'] ?? [],
            'layout_improvements' => $spatial_guidance['layout_recommendations'] ?? [],
            'safety_considerations' => $spatial_guidance['safety_considerations'] ?? [],
            'improvement_suggestions' => $spatial_guidance['improvement_suggestions'] ?? [],
            'guidance_summary' => count($spatial_guidance['placement_guidance'] ?? []) . ' placement recommendations provided',
            'reasoning_metadata' => $spatial_guidance['reasoning_metadata'] ?? []
        ];
    }
    
    /**
     * Analyze zone distribution for spatial summary
     */
    private function analyzeZoneDistribution($zones) {
        $distribution = [];
        
        foreach ($zones as $zone_name => $zone_data) {
            if (is_array($zone_data) && isset($zone_data['objects'])) {
                $distribution[$zone_name] = count($zone_data['objects']);
            }
        }
        
        return $distribution;
    }
    
    /**
     * Get fallback analysis when AI service is unavailable
     */
    private function getFallbackAnalysis($room_type, $improvement_notes, $error_message = '') {
        return [
            'success' => true,
            'message' => 'Fallback analysis generated (AI service unavailable)',
            'error_context' => $error_message,
            'pipeline_type' => 'fallback',
            'stages_completed' => 0,
            
            'detected_objects' => [
                'total_objects' => 0,
                'major_items' => [],
                'furniture_categories' => [],
                'detection_summary' => 'AI service unavailable'
            ],
            
            'spatial_zones' => [
                'zones_analyzed' => 0,
                'spatial_insights' => [],
                'layout_assessment' => 'AI service unavailable'
            ],
            
            'enhanced_visual_features' => [],
            
            'spatial_guidance' => [
                'placement_recommendations' => [],
                'layout_improvements' => [],
                'safety_considerations' => [],
                'guidance_summary' => 'AI service unavailable'
            ],
            
            'conceptual_visualization' => [
                'success' => false,
                'error' => $error_message,
                'fallback_message' => 'Conceptual visualization temporarily unavailable',
                'disclaimer' => 'AI service temporarily unavailable'
            ],
            
            'design_description' => '',
            
            'ai_metadata' => [
                'pipeline_type' => 'fallback',
                'gemini_api_available' => false,
                'diffusion_device' => 'unavailable',
                'analysis_timestamp' => date('c'),
                'room_type' => $room_type,
                'stages_completed' => 0
            ],
            
            'integration_notes' => [
                'status' => 'fallback',
                'message' => 'AI service temporarily unavailable, using rule-based analysis only',
                'error' => $error_message
            ]
        ];
    }
}