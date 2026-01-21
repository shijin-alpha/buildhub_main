// Construction Stage Progression Logic
// Each stage has 12.5% allocation (8 stages × 12.5% = 100%)

export const CONSTRUCTION_STAGES = [
  {
    id: 1,
    name: 'Foundation',
    icon: '🏗️',
    percentage: 12.5,
    description: 'Site preparation, excavation, and foundation work',
    typical_duration: '10-15 days'
  },
  {
    id: 2,
    name: 'Structure',
    icon: '🔨',
    percentage: 12.5,
    description: 'Structural framework, columns, and beams',
    typical_duration: '15-20 days'
  },
  {
    id: 3,
    name: 'Brickwork',
    icon: '🧱',
    percentage: 12.5,
    description: 'Wall construction and masonry work',
    typical_duration: '12-18 days'
  },
  {
    id: 4,
    name: 'Roofing',
    icon: '🏠',
    percentage: 12.5,
    description: 'Roof structure, waterproofing, and covering',
    typical_duration: '8-12 days'
  },
  {
    id: 5,
    name: 'Electrical',
    icon: '⚡',
    percentage: 12.5,
    description: 'Electrical wiring, fixtures, and connections',
    typical_duration: '10-14 days'
  },
  {
    id: 6,
    name: 'Plumbing',
    icon: '🚿',
    percentage: 12.5,
    description: 'Water supply, drainage, and sanitary work',
    typical_duration: '8-12 days'
  },
  {
    id: 7,
    name: 'Finishing',
    icon: '🎨',
    percentage: 12.5,
    description: 'Flooring, painting, and interior finishing',
    typical_duration: '15-20 days'
  },
  {
    id: 8,
    name: 'Final',
    icon: '✨',
    percentage: 12.5,
    description: 'Final touches, cleanup, and handover',
    typical_duration: '5-8 days'
  }
];

/**
 * Get the current project progress breakdown by stage
 * @param {Array} progressUpdates - Array of daily progress updates
 * @returns {Object} Stage-wise progress breakdown
 */
export const getStageProgressBreakdown = (progressUpdates) => {
  const stageProgress = {};
  
  // Initialize all stages
  CONSTRUCTION_STAGES.forEach(stage => {
    stageProgress[stage.name] = {
      ...stage,
      current_progress: 0,
      is_completed: false,
      is_active: false,
      start_date: null,
      completion_date: null,
      days_worked: 0,
      total_incremental: 0
    };
  });

  // Calculate progress for each stage
  progressUpdates.forEach(update => {
    const stageName = update.construction_stage;
    if (stageProgress[stageName]) {
      const incremental = parseFloat(update.incremental_completion_percentage || 0);
      stageProgress[stageName].total_incremental += incremental;
      stageProgress[stageName].days_worked++;
      
      if (!stageProgress[stageName].start_date) {
        stageProgress[stageName].start_date = update.update_date;
      }
      
      // Update latest date
      stageProgress[stageName].latest_date = update.update_date;
    }
  });

  // Determine completion status and current progress
  let cumulativeProgress = 0;
  CONSTRUCTION_STAGES.forEach(stage => {
    const stageData = stageProgress[stage.name];
    
    if (stageData.total_incremental >= stage.percentage * 0.95) { // 95% threshold for completion
      stageData.is_completed = true;
      stageData.completion_date = stageData.latest_date;
      stageData.current_progress = stage.percentage;
      cumulativeProgress += stage.percentage;
    } else if (stageData.total_incremental > 0) {
      stageData.is_active = true;
      stageData.current_progress = Math.min(stageData.total_incremental, stage.percentage);
      cumulativeProgress += stageData.current_progress;
    }
  });

  return {
    stages: stageProgress,
    total_progress: Math.min(cumulativeProgress, 100),
    completed_stages: Object.values(stageProgress).filter(s => s.is_completed).length,
    active_stage: Object.values(stageProgress).find(s => s.is_active && !s.is_completed)?.name || null
  };
};

/**
 * Get available stages for selection (not completed + current active)
 * @param {Object} stageBreakdown - Result from getStageProgressBreakdown
 * @returns {Array} Available stages for selection
 */
export const getAvailableStages = (stageBreakdown) => {
  const { stages } = stageBreakdown;
  const availableStages = [];
  
  let foundIncomplete = false;
  
  CONSTRUCTION_STAGES.forEach(stage => {
    const stageData = stages[stage.name];
    
    if (!stageData.is_completed) {
      if (!foundIncomplete) {
        // This is the first incomplete stage - it's available
        availableStages.push({
          ...stage,
          is_current: stageData.is_active,
          remaining_percentage: stage.percentage - stageData.current_progress
        });
        foundIncomplete = true;
      } else if (availableStages.length === 1) {
        // Allow the next stage if current is nearly complete (>80%)
        const currentStage = availableStages[0];
        const currentStageData = stages[currentStage.name];
        if (currentStageData.current_progress >= currentStage.percentage * 0.8) {
          availableStages.push({
            ...stage,
            is_current: false,
            remaining_percentage: stage.percentage
          });
        }
      }
    }
  });
  
  return availableStages;
};

/**
 * Validate daily progress input
 * @param {string} selectedStage - Selected construction stage
 * @param {number} incrementalPercentage - Daily progress percentage
 * @param {Object} stageBreakdown - Current stage breakdown
 * @returns {Object} Validation result
 */
export const validateDailyProgress = (selectedStage, incrementalPercentage, stageBreakdown) => {
  const errors = [];
  const warnings = [];
  
  if (!selectedStage) {
    errors.push('Please select a construction stage');
    return { isValid: false, errors, warnings };
  }
  
  if (!incrementalPercentage || incrementalPercentage <= 0) {
    errors.push('Daily progress must be greater than 0%');
    return { isValid: false, errors, warnings };
  }
  
  const stage = CONSTRUCTION_STAGES.find(s => s.name === selectedStage);
  if (!stage) {
    errors.push('Invalid construction stage selected');
    return { isValid: false, errors, warnings };
  }
  
  const stageData = stageBreakdown.stages[selectedStage];
  const newTotal = stageData.current_progress + incrementalPercentage;
  
  // Check if exceeds stage limit
  if (newTotal > stage.percentage) {
    const remaining = stage.percentage - stageData.current_progress;
    errors.push(`This stage can only accept ${remaining.toFixed(1)}% more progress (${stage.percentage}% total)`);
    return { isValid: false, errors, warnings };
  }
  
  // Check if stage is already completed
  if (stageData.is_completed) {
    errors.push(`${selectedStage} stage is already completed`);
    return { isValid: false, errors, warnings };
  }
  
  // Check if stage is available
  const availableStages = getAvailableStages(stageBreakdown);
  const isStageAvailable = availableStages.some(s => s.name === selectedStage);
  
  if (!isStageAvailable) {
    errors.push(`${selectedStage} stage is not available yet. Complete previous stages first.`);
    return { isValid: false, errors, warnings };
  }
  
  // Warnings for unusual progress
  if (incrementalPercentage > 5) {
    warnings.push('Daily progress above 5% is unusually high. Please verify.');
  }
  
  if (incrementalPercentage < 0.5) {
    warnings.push('Daily progress below 0.5% is quite low. Consider if more work was done.');
  }
  
  // Check if this will complete the stage
  if (newTotal >= stage.percentage * 0.95) {
    warnings.push(`This update will complete the ${selectedStage} stage!`);
  }
  
  return {
    isValid: true,
    errors,
    warnings,
    stage_completion_after_update: newTotal >= stage.percentage * 0.95,
    remaining_in_stage: stage.percentage - newTotal
  };
};

/**
 * Get stage progress summary for display
 * @param {Object} stageBreakdown - Stage breakdown data
 * @returns {Object} Summary for UI display
 */
export const getStageProgressSummary = (stageBreakdown) => {
  const { stages, total_progress, completed_stages } = stageBreakdown;
  
  const summary = {
    total_progress: Math.round(total_progress * 10) / 10,
    completed_stages,
    total_stages: CONSTRUCTION_STAGES.length,
    current_stage: null,
    next_stage: null,
    stage_details: []
  };
  
  CONSTRUCTION_STAGES.forEach(stage => {
    const stageData = stages[stage.name];
    const detail = {
      name: stage.name,
      icon: stage.icon,
      percentage: stage.percentage,
      current_progress: Math.round(stageData.current_progress * 10) / 10,
      is_completed: stageData.is_completed,
      is_active: stageData.is_active,
      progress_percent: Math.round((stageData.current_progress / stage.percentage) * 100),
      days_worked: stageData.days_worked
    };
    
    if (stageData.is_active && !stageData.is_completed) {
      summary.current_stage = detail;
    }
    
    summary.stage_details.push(detail);
  });
  
  // Find next stage
  const currentIndex = summary.current_stage ? 
    CONSTRUCTION_STAGES.findIndex(s => s.name === summary.current_stage.name) : -1;
  
  if (currentIndex >= 0 && currentIndex < CONSTRUCTION_STAGES.length - 1) {
    const nextStage = CONSTRUCTION_STAGES[currentIndex + 1];
    summary.next_stage = {
      name: nextStage.name,
      icon: nextStage.icon,
      percentage: nextStage.percentage
    };
  }
  
  return summary;
};

/**
 * Calculate expected timeline based on current progress
 * @param {Object} stageBreakdown - Stage breakdown data
 * @param {Array} progressUpdates - All progress updates
 * @returns {Object} Timeline projection
 */
export const calculateProjectTimeline = (stageBreakdown, progressUpdates) => {
  if (!progressUpdates.length) {
    return {
      estimated_completion: null,
      days_elapsed: 0,
      estimated_total_days: null,
      daily_average: 0
    };
  }
  
  // Calculate daily average progress
  const totalIncremental = progressUpdates.reduce((sum, update) => 
    sum + parseFloat(update.incremental_completion_percentage || 0), 0);
  
  const uniqueDays = new Set(progressUpdates.map(u => u.update_date)).size;
  const dailyAverage = uniqueDays > 0 ? totalIncremental / uniqueDays : 0;
  
  const remainingProgress = 100 - stageBreakdown.total_progress;
  const estimatedRemainingDays = dailyAverage > 0 ? Math.ceil(remainingProgress / dailyAverage) : null;
  
  // Get project start date
  const sortedUpdates = [...progressUpdates].sort((a, b) => 
    new Date(a.update_date) - new Date(b.update_date));
  const startDate = new Date(sortedUpdates[0].update_date);
  const currentDate = new Date();
  const daysElapsed = Math.ceil((currentDate - startDate) / (1000 * 60 * 60 * 24));
  
  let estimatedCompletion = null;
  if (estimatedRemainingDays) {
    estimatedCompletion = new Date();
    estimatedCompletion.setDate(estimatedCompletion.getDate() + estimatedRemainingDays);
  }
  
  return {
    estimated_completion: estimatedCompletion,
    days_elapsed: daysElapsed,
    estimated_total_days: estimatedRemainingDays ? daysElapsed + estimatedRemainingDays : null,
    daily_average: Math.round(dailyAverage * 100) / 100,
    remaining_progress: Math.round(remainingProgress * 10) / 10
  };
};