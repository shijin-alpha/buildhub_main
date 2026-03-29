<?php

/**
 * Payment Request Validator
 * 
 * Comprehensive validation system for all payment-related fields and requests
 * Ensures logical correctness, business rules compliance, and data integrity
 */
class PaymentRequestValidator {
    
    // Payment limits and constraints
    private static $payment_limits = [
        'min_amount' => 0.01,
        'max_single_payment' => 2000000, // ₹20 lakhs
        'max_daily_limit' => 5000000,    // ₹50 lakhs per day
        'max_project_percentage' => 100, // 100% of project cost
        'min_stage_percentage' => 1,     // 1% minimum for stage payments
        'max_stage_percentage' => 50     // 50% maximum for single stage
    ];
    
    // Valid payment methods and their constraints
    private static $payment_methods = [
        'razorpay' => [
            'min_amount' => 1.00,
            'max_amount' => 2000000,
            'currencies' => ['INR'],
            'requires_verification' => false
        ],
        'bank_transfer' => [
            'min_amount' => 100.00,
            'max_amount' => 10000000,
            'currencies' => ['INR', 'USD', 'EUR'],
            'requires_verification' => true
        ],
        'upi' => [
            'min_amount' => 1.00,
            'max_amount' => 100000,
            'currencies' => ['INR'],
            'requires_verification' => false
        ],
        'cash' => [
            'min_amount' => 1.00,
            'max_amount' => 200000,
            'currencies' => ['INR'],
            'requires_verification' => true
        ],
        'cheque' => [
            'min_amount' => 500.00,
            'max_amount' => 5000000,
            'currencies' => ['INR'],
            'requires_verification' => true
        ]
    ];
    
    // Construction stage payment rules
    private static $stage_payment_rules = [
        'Foundation' => [
            'typical_percentage' => [15, 25],
            'min_amount' => 50000,
            'requires_quality_check' => true,
            'requires_materials_list' => true,
            'typical_materials' => ['cement', 'steel', 'sand', 'aggregate']
        ],
        'Structure' => [
            'typical_percentage' => [20, 35],
            'min_amount' => 100000,
            'requires_quality_check' => true,
            'requires_safety_compliance' => true,
            'typical_materials' => ['cement', 'steel', 'bricks', 'sand']
        ],
        'Brickwork' => [
            'typical_percentage' => [10, 20],
            'min_amount' => 30000,
            'requires_quality_check' => false,
            'typical_materials' => ['bricks', 'cement', 'sand', 'mortar']
        ],
        'Roofing' => [
            'typical_percentage' => [15, 25],
            'min_amount' => 75000,
            'requires_quality_check' => true,
            'weather_dependent' => true,
            'typical_materials' => ['roofing_sheets', 'tiles', 'waterproofing']
        ],
        'Electrical' => [
            'typical_percentage' => [8, 15],
            'min_amount' => 25000,
            'requires_safety_compliance' => true,
            'requires_certification' => true,
            'typical_materials' => ['wires', 'switches', 'sockets', 'mcb']
        ],
        'Plumbing' => [
            'typical_percentage' => [8, 15],
            'min_amount' => 20000,
            'requires_quality_check' => true,
            'typical_materials' => ['pipes', 'fittings', 'valves', 'fixtures']
        ],
        'Finishing' => [
            'typical_percentage' => [10, 20],
            'min_amount' => 40000,
            'requires_quality_check' => true,
            'typical_materials' => ['paint', 'tiles', 'fixtures', 'hardware']
        ]
    ];
    
    /**
     * Validate stage payment request
     * 
     * @param array $request_data Payment request data
     * @param array $project_data Project information
     * @return array Validation result
     */
    public static function validateStagePaymentRequest($request_data, $project_data = []) {
        $validation_result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
            'validation_score' => 100,
            'validation_details' => []
        ];
        
        try {
            // Basic field validation
            $basic_validation = self::validateBasicFields($request_data, 'stage_payment');
            $validation_result = self::mergeValidationResults($validation_result, $basic_validation);
            
            // Amount validation
            $amount_validation = self::validatePaymentAmount(
                $request_data['requested_amount'] ?? 0,
                $project_data['total_cost'] ?? 0,
                'stage_payment'
            );
            $validation_result = self::mergeValidationResults($validation_result, $amount_validation);
            
            // Stage-specific validation
            if (!empty($request_data['stage_name'])) {
                $stage_validation = self::validateStageSpecificRules(
                    $request_data['stage_name'],
                    $request_data,
                    $project_data
                );
                $validation_result = self::mergeValidationResults($validation_result, $stage_validation);
            }
            
            // Cost breakdown validation
            if (isset($request_data['labor_cost']) || isset($request_data['material_cost'])) {
                $breakdown_validation = self::validateCostBreakdown($request_data);
                $validation_result = self::mergeValidationResults($validation_result, $breakdown_validation);
            }
            
            // Timeline validation
            $timeline_validation = self::validateTimeline($request_data);
            $validation_result = self::mergeValidationResults($validation_result, $timeline_validation);
            
            // Business logic validation
            $business_validation = self::validateBusinessLogic($request_data, $project_data, 'stage_payment');
            $validation_result = self::mergeValidationResults($validation_result, $business_validation);
            
        } catch (Exception $e) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = 'Validation system error: ' . $e->getMessage();
            $validation_result['validation_score'] = 0;
        }
        
        return $validation_result;
    }
    
    /**
     * Validate custom payment request
     * 
     * @param array $request_data Payment request data
     * @param array $project_data Project information
     * @return array Validation result
     */
    public static function validateCustomPaymentRequest($request_data, $project_data = []) {
        $validation_result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
            'validation_score' => 100,
            'validation_details' => []
        ];
        
        try {
            // Basic field validation
            $basic_validation = self::validateBasicFields($request_data, 'custom_payment');
            $validation_result = self::mergeValidationResults($validation_result, $basic_validation);
            
            // Amount validation
            $amount_validation = self::validatePaymentAmount(
                $request_data['requested_amount'] ?? 0,
                $project_data['total_cost'] ?? 0,
                'custom_payment'
            );
            $validation_result = self::mergeValidationResults($validation_result, $amount_validation);
            
            // Custom payment specific validation
            $custom_validation = self::validateCustomPaymentSpecific($request_data);
            $validation_result = self::mergeValidationResults($validation_result, $custom_validation);
            
            // Business logic validation
            $business_validation = self::validateBusinessLogic($request_data, $project_data, 'custom_payment');
            $validation_result = self::mergeValidationResults($validation_result, $business_validation);
            
        } catch (Exception $e) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = 'Validation system error: ' . $e->getMessage();
            $validation_result['validation_score'] = 0;
        }
        
        return $validation_result;
    }
    
    /**
     * Validate payment method and amount compatibility
     * 
     * @param string $payment_method Payment method
     * @param float $amount Payment amount
     * @param string $currency Currency code
     * @return array Validation result
     */
    public static function validatePaymentMethod($payment_method, $amount, $currency = 'INR') {
        $validation_result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'method_details' => []
        ];
        
        if (!isset(self::$payment_methods[$payment_method])) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = "Invalid payment method: $payment_method";
            return $validation_result;
        }
        
        $method_config = self::$payment_methods[$payment_method];
        $validation_result['method_details'] = $method_config;
        
        // Validate amount range
        if ($amount < $method_config['min_amount']) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = sprintf(
                "%s requires minimum amount of ₹%.2f (requested: ₹%.2f)",
                ucfirst($payment_method),
                $method_config['min_amount'],
                $amount
            );
        }
        
        if ($amount > $method_config['max_amount']) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = sprintf(
                "%s has maximum limit of ₹%.2f (requested: ₹%.2f)",
                ucfirst($payment_method),
                $method_config['max_amount'],
                $amount
            );
        }
        
        // Validate currency
        if (!in_array($currency, $method_config['currencies'])) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = sprintf(
                "%s does not support %s currency",
                ucfirst($payment_method),
                $currency
            );
        }
        
        // Add verification requirement warning
        if ($method_config['requires_verification']) {
            $validation_result['warnings'][] = sprintf(
                "%s payments require manual verification and may take 1-2 business days",
                ucfirst($payment_method)
            );
        }
        
        return $validation_result;
    }
    
    /**
     * Validate basic required fields
     */
    private static function validateBasicFields($request_data, $payment_type) {
        $validation_result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        // Common required fields
        $required_fields = [
            'project_id' => 'Project ID',
            'contractor_id' => 'Contractor ID',
            'homeowner_id' => 'Homeowner ID',
            'requested_amount' => 'Requested Amount'
        ];
        
        // Payment type specific required fields
        if ($payment_type === 'stage_payment') {
            $required_fields['stage_name'] = 'Stage Name';
            $required_fields['work_description'] = 'Work Description';
            $required_fields['completion_percentage'] = 'Completion Percentage';
        } elseif ($payment_type === 'custom_payment') {
            $required_fields['request_title'] = 'Request Title';
            $required_fields['request_reason'] = 'Request Reason';
            $required_fields['work_description'] = 'Work Description';
            $required_fields['urgency_level'] = 'Urgency Level';
        }
        
        // Check required fields
        foreach ($required_fields as $field => $label) {
            if (!isset($request_data[$field]) || $request_data[$field] === '' || $request_data[$field] === null) {
                $validation_result['is_valid'] = false;
                $validation_result['errors'][] = "$label is required";
            }
        }
        
        // Validate field formats and constraints
        if (isset($request_data['project_id']) && (!is_numeric($request_data['project_id']) || $request_data['project_id'] <= 0)) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = 'Project ID must be a positive number';
        }
        
        if (isset($request_data['contractor_id']) && (!is_numeric($request_data['contractor_id']) || $request_data['contractor_id'] <= 0)) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = 'Contractor ID must be a positive number';
        }
        
        if (isset($request_data['homeowner_id']) && (!is_numeric($request_data['homeowner_id']) || $request_data['homeowner_id'] <= 0)) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = 'Homeowner ID must be a positive number';
        }
        
        // Validate text field lengths
        if (isset($request_data['work_description']) && strlen($request_data['work_description']) < 50) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = 'Work description must be at least 50 characters long';
        }
        
        if (isset($request_data['work_description']) && strlen($request_data['work_description']) > 2000) {
            $validation_result['warnings'][] = 'Work description is very long. Consider summarizing key points.';
        }
        
        return $validation_result;
    }
    
    /**
     * Validate payment amount
     */
    private static function validatePaymentAmount($amount, $project_total_cost = 0, $payment_type = 'general') {
        $validation_result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'amount_analysis' => []
        ];
        
        // Convert to float and validate
        $amount = (float)$amount;
        
        if ($amount <= 0) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = 'Payment amount must be greater than zero';
            return $validation_result;
        }
        
        // Check minimum amount
        if ($amount < self::$payment_limits['min_amount']) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = sprintf(
                'Payment amount must be at least ₹%.2f (requested: ₹%.2f)',
                self::$payment_limits['min_amount'],
                $amount
            );
        }
        
        // Check maximum single payment
        if ($amount > self::$payment_limits['max_single_payment']) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = sprintf(
                'Payment amount exceeds maximum limit of ₹%.2f (requested: ₹%.2f)',
                self::$payment_limits['max_single_payment'],
                $amount
            );
            $validation_result['recommendations'][] = 'Consider using split payment for amounts above ₹20 lakhs';
        }
        
        // Project cost validation
        if ($project_total_cost > 0) {
            $percentage_of_project = ($amount / $project_total_cost) * 100;
            $validation_result['amount_analysis']['percentage_of_project'] = $percentage_of_project;
            
            if ($percentage_of_project > self::$payment_limits['max_project_percentage']) {
                $validation_result['is_valid'] = false;
                $validation_result['errors'][] = sprintf(
                    'Payment amount (₹%.2f) exceeds total project cost (₹%.2f)',
                    $amount,
                    $project_total_cost
                );
            }
            
            // Stage payment specific validation
            if ($payment_type === 'stage_payment') {
                if ($percentage_of_project < self::$payment_limits['min_stage_percentage']) {
                    $validation_result['warnings'][] = sprintf(
                        'Payment amount is very small (%.1f%% of project cost). Consider combining with other work.',
                        $percentage_of_project
                    );
                }
                
                if ($percentage_of_project > self::$payment_limits['max_stage_percentage']) {
                    $validation_result['warnings'][] = sprintf(
                        'Payment amount is large (%.1f%% of project cost). Ensure work justifies this amount.',
                        $percentage_of_project
                    );
                }
            }
        }
        
        // Amount reasonableness checks
        if ($amount >= 1000000) { // ₹10 lakhs or more
            $validation_result['warnings'][] = 'Large payment amount detected. Ensure proper documentation and verification.';
        }
        
        if ($amount < 1000) { // Less than ₹1000
            $validation_result['warnings'][] = 'Very small payment amount. Consider if this is cost-effective.';
        }
        
        return $validation_result;
    }
    
    /**
     * Validate stage-specific rules
     */
    private static function validateStageSpecificRules($stage_name, $request_data, $project_data) {
        $validation_result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'stage_analysis' => []
        ];
        
        if (!isset(self::$stage_payment_rules[$stage_name])) {
            $validation_result['warnings'][] = "No specific validation rules found for stage: $stage_name";
            return $validation_result;
        }
        
        $stage_rules = self::$stage_payment_rules[$stage_name];
        $amount = (float)($request_data['requested_amount'] ?? 0);
        $project_cost = (float)($project_data['total_cost'] ?? 0);
        
        // Check minimum amount for stage
        if (isset($stage_rules['min_amount']) && $amount < $stage_rules['min_amount']) {
            $validation_result['warnings'][] = sprintf(
                '%s stage typically requires minimum ₹%.2f (requested: ₹%.2f)',
                $stage_name,
                $stage_rules['min_amount'],
                $amount
            );
        }
        
        // Check typical percentage range
        if (isset($stage_rules['typical_percentage']) && $project_cost > 0) {
            $percentage = ($amount / $project_cost) * 100;
            $min_typical = $stage_rules['typical_percentage'][0];
            $max_typical = $stage_rules['typical_percentage'][1];
            
            $validation_result['stage_analysis']['percentage_of_project'] = $percentage;
            $validation_result['stage_analysis']['typical_range'] = $stage_rules['typical_percentage'];
            
            if ($percentage < $min_typical) {
                $validation_result['warnings'][] = sprintf(
                    '%s stage typically costs %d-%d%% of project (requested: %.1f%%)',
                    $stage_name,
                    $min_typical,
                    $max_typical,
                    $percentage
                );
            } elseif ($percentage > $max_typical) {
                $validation_result['warnings'][] = sprintf(
                    '%s stage amount seems high (%.1f%% vs typical %d-%d%%)',
                    $stage_name,
                    $percentage,
                    $min_typical,
                    $max_typical
                );
            }
        }
        
        // Check required fields for stage
        if (isset($stage_rules['requires_quality_check']) && $stage_rules['requires_quality_check']) {
            if (empty($request_data['quality_check_status']) || $request_data['quality_check_status'] === 'pending') {
                $validation_result['warnings'][] = "$stage_name stage requires quality check completion";
            }
        }
        
        if (isset($stage_rules['requires_safety_compliance']) && $stage_rules['requires_safety_compliance']) {
            if (empty($request_data['safety_compliance']) || !$request_data['safety_compliance']) {
                $validation_result['is_valid'] = false;
                $validation_result['errors'][] = "$stage_name stage requires safety compliance confirmation";
            }
        }
        
        if (isset($stage_rules['requires_materials_list']) && $stage_rules['requires_materials_list']) {
            if (empty($request_data['materials_used'])) {
                $validation_result['warnings'][] = "$stage_name stage should include detailed materials list";
            } else {
                // Check if typical materials are mentioned
                $materials_text = strtolower($request_data['materials_used']);
                $typical_materials = $stage_rules['typical_materials'] ?? [];
                $mentioned_materials = 0;
                
                foreach ($typical_materials as $material) {
                    if (strpos($materials_text, strtolower($material)) !== false) {
                        $mentioned_materials++;
                    }
                }
                
                if ($mentioned_materials === 0 && !empty($typical_materials)) {
                    $validation_result['warnings'][] = sprintf(
                        '%s stage typically uses: %s. Verify materials list is accurate.',
                        $stage_name,
                        implode(', ', $typical_materials)
                    );
                }
            }
        }
        
        // Weather-dependent stages
        if (isset($stage_rules['weather_dependent']) && $stage_rules['weather_dependent']) {
            if (!isset($request_data['weather_delays'])) {
                $validation_result['warnings'][] = "$stage_name stage is weather-dependent. Consider mentioning any weather delays.";
            }
        }
        
        return $validation_result;
    }
    
    /**
     * Validate cost breakdown
     */
    private static function validateCostBreakdown($request_data) {
        $validation_result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'breakdown_analysis' => []
        ];
        
        $requested_amount = (float)($request_data['requested_amount'] ?? 0);
        $labor_cost = (float)($request_data['labor_cost'] ?? 0);
        $material_cost = (float)($request_data['material_cost'] ?? 0);
        $equipment_cost = (float)($request_data['equipment_cost'] ?? 0);
        $other_expenses = (float)($request_data['other_expenses'] ?? 0);
        
        $total_breakdown = $labor_cost + $material_cost + $equipment_cost + $other_expenses;
        
        if ($total_breakdown > 0) {
            $difference = abs($total_breakdown - $requested_amount);
            $tolerance = max(1.0, $requested_amount * 0.01); // 1% tolerance or ₹1, whichever is higher
            
            if ($difference > $tolerance) {
                $validation_result['is_valid'] = false;
                $validation_result['errors'][] = sprintf(
                    'Cost breakdown (₹%.2f) does not match requested amount (₹%.2f). Difference: ₹%.2f',
                    $total_breakdown,
                    $requested_amount,
                    $difference
                );
            }
            
            // Analyze cost distribution
            if ($requested_amount > 0) {
                $labor_percentage = ($labor_cost / $requested_amount) * 100;
                $material_percentage = ($material_cost / $requested_amount) * 100;
                
                $validation_result['breakdown_analysis'] = [
                    'labor_percentage' => $labor_percentage,
                    'material_percentage' => $material_percentage,
                    'equipment_percentage' => ($equipment_cost / $requested_amount) * 100,
                    'other_percentage' => ($other_expenses / $requested_amount) * 100
                ];
                
                // Validate reasonable cost distribution
                if ($labor_percentage > 80) {
                    $validation_result['warnings'][] = 'Labor cost is very high (>80%). Verify this is accurate.';
                }
                
                if ($material_percentage > 70) {
                    $validation_result['warnings'][] = 'Material cost is very high (>70%). Ensure proper documentation.';
                }
                
                if ($labor_percentage < 10 && $material_percentage < 10) {
                    $validation_result['warnings'][] = 'Both labor and material costs are very low. Verify breakdown is complete.';
                }
            }
        }
        
        // Individual cost validation
        if ($labor_cost < 0 || $material_cost < 0 || $equipment_cost < 0 || $other_expenses < 0) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = 'Cost breakdown items cannot be negative';
        }
        
        return $validation_result;
    }
    
    /**
     * Validate timeline
     */
    private static function validateTimeline($request_data) {
        $validation_result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        $work_start_date = $request_data['work_start_date'] ?? '';
        $work_end_date = $request_data['work_end_date'] ?? '';
        
        if (!empty($work_start_date) && !empty($work_end_date)) {
            $start_timestamp = strtotime($work_start_date);
            $end_timestamp = strtotime($work_end_date);
            $current_timestamp = time();
            
            if ($start_timestamp === false || $end_timestamp === false) {
                $validation_result['is_valid'] = false;
                $validation_result['errors'][] = 'Invalid date format in work timeline';
                return $validation_result;
            }
            
            // Check if end date is after start date
            if ($end_timestamp <= $start_timestamp) {
                $validation_result['is_valid'] = false;
                $validation_result['errors'][] = 'Work end date must be after start date';
            }
            
            // Check if dates are reasonable
            if ($start_timestamp > $current_timestamp + (30 * 24 * 60 * 60)) { // More than 30 days in future
                $validation_result['warnings'][] = 'Work start date is far in the future. Verify this is correct.';
            }
            
            if ($end_timestamp < $current_timestamp - (365 * 24 * 60 * 60)) { // More than 1 year in past
                $validation_result['warnings'][] = 'Work end date is very old. Verify this is correct.';
            }
            
            // Check work duration
            $duration_days = ($end_timestamp - $start_timestamp) / (24 * 60 * 60);
            
            if ($duration_days < 1) {
                $validation_result['warnings'][] = 'Work duration is less than 1 day. Verify timeline is accurate.';
            }
            
            if ($duration_days > 365) {
                $validation_result['warnings'][] = 'Work duration is more than 1 year. Verify timeline is accurate.';
            }
        }
        
        return $validation_result;
    }
    
    /**
     * Validate custom payment specific fields
     */
    private static function validateCustomPaymentSpecific($request_data) {
        $validation_result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        // Validate urgency level
        $valid_urgency_levels = ['low', 'medium', 'high', 'urgent'];
        $urgency_level = $request_data['urgency_level'] ?? '';
        
        if (!in_array($urgency_level, $valid_urgency_levels)) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = 'Invalid urgency level. Must be: ' . implode(', ', $valid_urgency_levels);
        }
        
        // Validate category if provided
        if (!empty($request_data['category'])) {
            $valid_categories = [
                'additional_work', 'material_upgrade', 'design_change', 
                'emergency_repair', 'scope_expansion', 'other'
            ];
            
            if (!in_array($request_data['category'], $valid_categories)) {
                $validation_result['warnings'][] = 'Unusual payment category. Verify this is correct.';
            }
        }
        
        // Validate request reason length and content
        $request_reason = $request_data['request_reason'] ?? '';
        if (strlen($request_reason) < 20) {
            $validation_result['is_valid'] = false;
            $validation_result['errors'][] = 'Request reason must be at least 20 characters long';
        }
        
        if (strlen($request_reason) > 1000) {
            $validation_result['warnings'][] = 'Request reason is very long. Consider summarizing key points.';
        }
        
        return $validation_result;
    }
    
    /**
     * Validate business logic and rules
     */
    private static function validateBusinessLogic($request_data, $project_data, $payment_type) {
        $validation_result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'business_analysis' => []
        ];
        
        // Check completion percentage logic
        if (isset($request_data['completion_percentage'])) {
            $completion = (float)$request_data['completion_percentage'];
            
            if ($completion < 0 || $completion > 100) {
                $validation_result['is_valid'] = false;
                $validation_result['errors'][] = 'Completion percentage must be between 0 and 100';
            }
            
            // Business logic: payment should be proportional to completion
            if ($completion < 50 && isset($project_data['total_cost'])) {
                $amount = (float)($request_data['requested_amount'] ?? 0);
                $project_cost = (float)$project_data['total_cost'];
                
                if ($project_cost > 0) {
                    $payment_percentage = ($amount / $project_cost) * 100;
                    
                    if ($payment_percentage > $completion * 1.5) { // Allow some flexibility
                        $validation_result['warnings'][] = sprintf(
                            'Payment amount (%.1f%% of project) seems high for completion level (%.1f%%)',
                            $payment_percentage,
                            $completion
                        );
                    }
                }
            }
        }
        
        // Check worker count logic
        if (isset($request_data['workers_count'])) {
            $workers = (int)$request_data['workers_count'];
            $amount = (float)($request_data['requested_amount'] ?? 0);
            
            if ($workers > 0 && $amount > 0) {
                $cost_per_worker = $amount / $workers;
                
                if ($cost_per_worker < 5000) {
                    $validation_result['warnings'][] = 'Cost per worker seems very low. Verify calculation.';
                }
                
                if ($cost_per_worker > 100000) {
                    $validation_result['warnings'][] = 'Cost per worker seems very high. Verify calculation.';
                }
            }
        }
        
        // Check weather delays logic
        if (isset($request_data['weather_delays'])) {
            $delays = (int)$request_data['weather_delays'];
            
            if ($delays > 30) {
                $validation_result['warnings'][] = 'Weather delays exceed 30 days. Consider project timeline impact.';
            }
            
            if ($delays > 0 && empty($request_data['work_end_date'])) {
                $validation_result['warnings'][] = 'Weather delays mentioned but no end date provided.';
            }
        }
        
        return $validation_result;
    }
    
    /**
     * Merge validation results
     */
    private static function mergeValidationResults($main_result, $sub_result) {
        if (isset($sub_result['is_valid']) && !$sub_result['is_valid']) {
            $main_result['is_valid'] = false;
        }
        
        if (isset($sub_result['errors'])) {
            $main_result['errors'] = array_merge($main_result['errors'], $sub_result['errors']);
        }
        
        if (isset($sub_result['warnings'])) {
            $main_result['warnings'] = array_merge($main_result['warnings'], $sub_result['warnings']);
        }
        
        if (isset($sub_result['recommendations'])) {
            $main_result['recommendations'] = array_merge(
                $main_result['recommendations'] ?? [], 
                $sub_result['recommendations']
            );
        }
        
        // Merge analysis data
        $analysis_keys = ['amount_analysis', 'stage_analysis', 'breakdown_analysis', 'business_analysis'];
        foreach ($analysis_keys as $key) {
            if (isset($sub_result[$key])) {
                $main_result['validation_details'][$key] = $sub_result[$key];
            }
        }
        
        // Adjust validation score based on errors and warnings
        if (!empty($sub_result['errors'])) {
            $main_result['validation_score'] -= count($sub_result['errors']) * 20;
        }
        
        if (!empty($sub_result['warnings'])) {
            $main_result['validation_score'] -= count($sub_result['warnings']) * 5;
        }
        
        $main_result['validation_score'] = max(0, $main_result['validation_score']);
        
        return $main_result;
    }
    
    /**
     * Get validation summary for display
     */
    public static function getValidationSummary($validation_result) {
        $summary = [
            'status' => $validation_result['is_valid'] ? 'valid' : 'invalid',
            'score' => $validation_result['validation_score'] ?? 0,
            'error_count' => count($validation_result['errors'] ?? []),
            'warning_count' => count($validation_result['warnings'] ?? []),
            'recommendation_count' => count($validation_result['recommendations'] ?? [])
        ];
        
        if ($summary['score'] >= 90) {
            $summary['grade'] = 'A';
            $summary['message'] = 'Excellent - Payment request meets all requirements';
        } elseif ($summary['score'] >= 80) {
            $summary['grade'] = 'B';
            $summary['message'] = 'Good - Minor issues to address';
        } elseif ($summary['score'] >= 70) {
            $summary['grade'] = 'C';
            $summary['message'] = 'Acceptable - Several improvements needed';
        } elseif ($summary['score'] >= 60) {
            $summary['grade'] = 'D';
            $summary['message'] = 'Poor - Significant issues need attention';
        } else {
            $summary['grade'] = 'F';
            $summary['message'] = 'Failed - Critical errors must be fixed';
        }
        
        return $summary;
    }
}