/**
 * Progress Update Validation Utilities
 * Comprehensive validation for enhanced labour tracking and progress updates
 */

// Validation rules for labour tracking
export const labourValidationRules = {
  workerCount: {
    min: 0,
    max: 100,
    required: true
  },
  hoursWorked: {
    min: 0,
    max: 12,
    step: 0.5,
    required: true
  },
  overtimeHours: {
    min: 0,
    max: 8,
    step: 0.5,
    required: false
  },
  absentCount: {
    min: 0,
    max: 50,
    required: false
  },
  hourlyRate: {
    min: 0,
    max: 2000,
    step: 10,
    required: false
  },
  productivityRating: {
    min: 1,
    max: 5,
    required: false
  }
};

// Standard hourly rates by worker type (in INR)
export const standardHourlyRates = {
  'Mason': 500,
  'Helper': 300,
  'Electrician': 600,
  'Plumber': 550,
  'Carpenter': 450,
  'Painter': 400,
  'Supervisor': 800,
  'Welder': 650,
  'Crane Operator': 900,
  'Excavator Operator': 850,
  'Steel Fixer': 520,
  'Tile Worker': 480,
  'Plasterer': 420,
  'Roofer': 580,
  'Security Guard': 250,
  'Site Engineer': 1000,
  'Quality Inspector': 700,
  'Safety Officer': 750,
  'Other': 350
};

// Productivity benchmarks by worker type
export const productivityBenchmarks = {
  'Mason': { hoursPerUnit: 0.5, unit: 'sq ft' },
  'Helper': { hoursPerUnit: 0.3, unit: 'support task' },
  'Electrician': { hoursPerUnit: 1.0, unit: 'point' },
  'Plumber': { hoursPerUnit: 1.2, unit: 'fixture' },
  'Carpenter': { hoursPerUnit: 0.8, unit: 'sq ft' },
  'Painter': { hoursPerUnit: 0.4, unit: 'sq ft' },
  'Supervisor': { hoursPerUnit: 8.0, unit: 'day' },
  'Other': { hoursPerUnit: 1.0, unit: 'task' }
};

/**
 * Validate individual labour entry
 */
export const validateLabourEntry = (labour, index) => {
  const errors = [];
  const warnings = [];

  // Required field validation
  if (!labour.worker_type || labour.worker_type.trim() === '') {
    errors.push(`Entry ${index + 1}: Worker type is required`);
  }

  if (!labour.worker_count || labour.worker_count < 1) {
    errors.push(`Entry ${index + 1}: Worker count must be at least 1`);
  }

  if (!labour.hours_worked || labour.hours_worked < 0.5) {
    errors.push(`Entry ${index + 1}: Hours worked must be at least 0.5`);
  }

  // Range validation
  if (labour.worker_count > labourValidationRules.workerCount.max) {
    errors.push(`Entry ${index + 1}: Worker count cannot exceed ${labourValidationRules.workerCount.max}`);
  }

  if (labour.hours_worked > labourValidationRules.hoursWorked.max) {
    errors.push(`Entry ${index + 1}: Regular hours cannot exceed ${labourValidationRules.hoursWorked.max}`);
  }

  if (labour.overtime_hours > labourValidationRules.overtimeHours.max) {
    errors.push(`Entry ${index + 1}: Overtime hours cannot exceed ${labourValidationRules.overtimeHours.max}`);
  }

  // Business logic validation
  const totalHours = (parseFloat(labour.hours_worked) || 0) + (parseFloat(labour.overtime_hours) || 0);
  if (totalHours > 16) {
    errors.push(`Entry ${index + 1}: Total hours (${totalHours}) cannot exceed 16 hours per day`);
  }

  if (labour.absent_count >= labour.worker_count) {
    warnings.push(`Entry ${index + 1}: Absent count (${labour.absent_count}) is equal to or greater than worker count (${labour.worker_count})`);
  }

  // Rate validation
  if (labour.hourly_rate && labour.hourly_rate > 0) {
    const standardRate = standardHourlyRates[labour.worker_type] || 350;
    const deviation = Math.abs(labour.hourly_rate - standardRate) / standardRate;
    
    if (deviation > 0.5) { // More than 50% deviation
      warnings.push(`Entry ${index + 1}: Hourly rate (₹${labour.hourly_rate}) deviates significantly from standard rate (₹${standardRate})`);
    }
  }

  // Productivity validation
  if (labour.productivity_rating < 3 && !labour.remarks) {
    warnings.push(`Entry ${index + 1}: Low productivity rating should include remarks explaining the reason`);
  }

  // Safety compliance validation
  if (labour.safety_compliance === 'poor' || labour.safety_compliance === 'needs_improvement') {
    if (!labour.remarks) {
      warnings.push(`Entry ${index + 1}: Poor safety compliance should include remarks with improvement actions`);
    }
  }

  return { errors, warnings };
};

/**
 * Validate entire labour data array
 */
export const validateLabourData = (labourData) => {
  const allErrors = [];
  const allWarnings = [];
  const summary = {
    totalWorkers: 0,
    totalRegularHours: 0,
    totalOvertimeHours: 0,
    totalWages: 0,
    averageProductivity: 0,
    totalAbsent: 0
  };

  if (!labourData || labourData.length === 0) {
    allWarnings.push('No labour entries added. Consider adding worker information for better tracking.');
    return { errors: allErrors, warnings: allWarnings, summary };
  }

  // Validate each entry
  labourData.forEach((labour, index) => {
    const { errors, warnings } = validateLabourEntry(labour, index);
    allErrors.push(...errors);
    allWarnings.push(...warnings);

    // Calculate summary
    summary.totalWorkers += parseInt(labour.worker_count) || 0;
    summary.totalRegularHours += (parseFloat(labour.hours_worked) || 0) * (parseInt(labour.worker_count) || 0);
    summary.totalOvertimeHours += (parseFloat(labour.overtime_hours) || 0) * (parseInt(labour.worker_count) || 0);
    summary.totalWages += parseFloat(labour.total_wages) || 0;
    summary.averageProductivity += parseInt(labour.productivity_rating) || 5;
    summary.totalAbsent += parseInt(labour.absent_count) || 0;
  });

  summary.averageProductivity = summary.averageProductivity / labourData.length;

  // Check for duplicate worker types
  const workerTypes = labourData.map(labour => labour.worker_type).filter(type => type);
  const duplicateTypes = workerTypes.filter((type, index) => workerTypes.indexOf(type) !== index);
  
  if (duplicateTypes.length > 0) {
    allWarnings.push(`Duplicate worker types found: ${[...new Set(duplicateTypes)].join(', ')}. Consider combining entries.`);
  }

  // Overall validation
  if (summary.totalWorkers > 50) {
    allWarnings.push(`Large workforce (${summary.totalWorkers} workers). Ensure adequate supervision and safety measures.`);
  }

  if (summary.totalOvertimeHours > summary.totalRegularHours * 0.3) {
    allWarnings.push('High overtime hours detected. Consider workforce planning optimization.');
  }

  if (summary.averageProductivity < 3) {
    allWarnings.push('Low average productivity rating. Consider reviewing work conditions and training needs.');
  }

  return { errors: allErrors, warnings: allWarnings, summary };
};

/**
 * Calculate optimal wage based on worker type and location
 */
export const calculateOptimalWage = (workerType, hours, overtimeHours, workerCount, location = 'general') => {
  const baseRate = standardHourlyRates[workerType] || 350;
  
  // Location multipliers
  const locationMultipliers = {
    'metro': 1.3,
    'urban': 1.1,
    'suburban': 1.0,
    'rural': 0.8,
    'general': 1.0
  };

  const multiplier = locationMultipliers[location] || 1.0;
  const adjustedRate = baseRate * multiplier;
  
  const regularWages = (hours || 0) * adjustedRate * (workerCount || 0);
  const overtimeWages = (overtimeHours || 0) * adjustedRate * 1.5 * (workerCount || 0);
  
  return {
    hourlyRate: Math.round(adjustedRate),
    regularWages: Math.round(regularWages),
    overtimeWages: Math.round(overtimeWages),
    totalWages: Math.round(regularWages + overtimeWages)
  };
};

/**
 * Generate productivity insights
 */
export const generateProductivityInsights = (labourData) => {
  if (!labourData || labourData.length === 0) {
    return [];
  }

  const insights = [];
  
  // High performers
  const highPerformers = labourData.filter(labour => labour.productivity_rating >= 4);
  if (highPerformers.length > 0) {
    insights.push({
      type: 'positive',
      message: `${highPerformers.length} worker type(s) showing excellent productivity (4+ rating)`
    });
  }

  // Low performers
  const lowPerformers = labourData.filter(labour => labour.productivity_rating <= 2);
  if (lowPerformers.length > 0) {
    insights.push({
      type: 'warning',
      message: `${lowPerformers.length} worker type(s) need attention (2 or below rating): ${lowPerformers.map(l => l.worker_type).join(', ')}`
    });
  }

  // Safety concerns
  const safetyIssues = labourData.filter(labour => 
    labour.safety_compliance === 'poor' || labour.safety_compliance === 'needs_improvement'
  );
  if (safetyIssues.length > 0) {
    insights.push({
      type: 'danger',
      message: `Safety compliance issues detected in ${safetyIssues.length} worker type(s). Immediate attention required.`
    });
  }

  // Overtime analysis
  const overtimeWorkers = labourData.filter(labour => (labour.overtime_hours || 0) > 2);
  if (overtimeWorkers.length > 0) {
    insights.push({
      type: 'info',
      message: `${overtimeWorkers.length} worker type(s) working significant overtime. Monitor for fatigue and safety.`
    });
  }

  return insights;
};

/**
 * Export labour data for reporting
 */
export const exportLabourSummary = (labourData, projectInfo = {}) => {
  const { summary } = validateLabourData(labourData);
  const insights = generateProductivityInsights(labourData);
  
  return {
    date: new Date().toISOString().split('T')[0],
    project: projectInfo.name || 'Unknown Project',
    summary,
    insights,
    entries: labourData.map(labour => ({
      workerType: labour.worker_type,
      count: labour.worker_count,
      totalHours: (parseFloat(labour.hours_worked) || 0) + (parseFloat(labour.overtime_hours) || 0),
      wages: labour.total_wages,
      productivity: labour.productivity_rating,
      safety: labour.safety_compliance,
      remarks: labour.remarks
    }))
  };
};

/**
 * Validate progress update form
 */
export const validateProgressUpdate = (formData) => {
  const errors = [];
  const warnings = [];

  // Required fields
  if (!formData.construction_stage) {
    errors.push('Construction stage is required');
  }

  if (!formData.work_done_today || formData.work_done_today.trim().length < 10) {
    errors.push('Work description must be at least 10 characters');
  }

  if (formData.incremental_completion_percentage === '' || 
      formData.incremental_completion_percentage < 0 || 
      formData.incremental_completion_percentage > 100) {
    errors.push('Completion percentage must be between 0 and 100');
  }

  if (!formData.weather_condition) {
    errors.push('Weather condition is required');
  }

  // Business logic validation
  if (formData.incremental_completion_percentage > 20 && (!formData.materials_used || formData.materials_used.trim().length < 5)) {
    warnings.push('Significant progress claims should include materials used');
  }

  if (formData.site_issues && formData.site_issues.trim().length > 0 && formData.incremental_completion_percentage > 10) {
    warnings.push('Site issues reported with significant progress - ensure issues are resolved');
  }

  // Labour validation
  if (formData.labour_data) {
    const labourValidation = validateLabourData(formData.labour_data);
    errors.push(...labourValidation.errors);
    warnings.push(...labourValidation.warnings);
  }

  return { errors, warnings };
};