const API_BASE = 'http://localhost/buildhub/backend/api';

export const validateForm = async (formData) => {
  console.log('🤖 ML Engine: Starting validation with Python ML...', formData);
  console.log('🌐 API URL:', `${API_BASE}/ml_working.php?action=validate`);
  
  try {
    const response = await fetch(`${API_BASE}/ml_working.php?action=validate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(formData)
    });
    
    console.log('📡 Response status:', response.status);
    console.log('📡 Response ok:', response.ok);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    console.log('🤖 ML Engine: Python ML response:', result);
    
    if (result.success && result.data) {
      console.log('✅ ML Engine: Using Python ML predictions!');
      return result.data;
    } else {
      console.log('⚠️ ML Engine: Python ML not available, using fallback rules');
      return fallbackValidation(formData);
    }
  } catch (error) {
    console.error('❌ ML Engine: Python ML error, using fallback:', error);
    console.log('🔄 Falling back to JavaScript rules...');
    return fallbackValidation(formData);
  }
};

export const getSuggestions = async (formData) => {
  console.log('💡 ML Engine: Getting suggestions...', formData);
  
  try {
    const response = await fetch(`${API_BASE}/ml_working.php?action=suggestions`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(formData)
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    console.log('💡 ML Engine: Suggestions response:', result);
    
    if (result.success && result.data) {
      return result.data;
    } else {
      return fallbackSuggestions(formData);
    }
  } catch (error) {
    console.error('❌ ML Engine: Suggestions error, using fallback:', error);
    return fallbackSuggestions(formData);
  }
};

export const getAllowedOptions = async (fieldName, formData) => {
  console.log('🎯 ML Engine: Getting allowed options for:', fieldName, formData);
  
  try {
    const response = await fetch(`${API_BASE}/ml_working.php?action=allowed_options`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ field: fieldName, ...formData })
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const result = await response.json();
    console.log('🎯 ML Engine: Allowed options response:', result);
    
    if (result.success && result.data) {
      return result.data;
    } else {
      return fallbackAllowedOptions(fieldName, formData);
    }
  } catch (error) {
    console.error('❌ ML Engine: Allowed options error, using fallback:', error);
    return fallbackAllowedOptions(fieldName, formData);
  }
};

// Helper function to convert plot size to sq ft
const convertToSqFt = (size, unit) => {
  if (!size || !unit) return 0;
  
  const plotSize = parseFloat(size);
  switch (unit) {
    case 'cents':
      return plotSize * 435.6; // 1 cent = 435.6 sq ft
    case 'acres':
      return plotSize * 43560; // 1 acre = 43560 sq ft
    case 'sqft':
      return plotSize;
    default:
      return plotSize;
  }
};

// Fallback JavaScript rules
const fallbackValidation = (formData) => {
  console.log('🔄 Using JavaScript fallback validation...');
  
  const errors = [];
  const warnings = [];
  const suggestions = [];
  
  // Convert plot size to sq ft for validation
  const plotSizeSqFt = convertToSqFt(formData.plot_size, formData.plot_unit);
  
  // Optimized cent-based validation logic
  if (plotSizeSqFt > 0) {
    // For cent-based plots, use different thresholds
    if (formData.plot_unit === 'cents') {
      const cents = parseFloat(formData.plot_size);
      
      // Cent-specific validation
      if (cents < 2) {
        errors.push('Plot size is too small for construction (minimum 2 cents required)');
      } else if (cents < 5) {
        warnings.push('Plot size is quite small for a house (consider 5+ cents for better planning)');
      } else if (cents >= 10) {
        // 10+ cents is good for multi-story
        if (formData.num_floors && parseInt(formData.num_floors) > 2) {
          // Good for multi-story
        }
      }
      
      // Floor validation for cent-based plots
      if (formData.num_floors && cents > 0) {
        const numFloors = parseInt(formData.num_floors);
        const minCentsPerFloor = 3; // Minimum 3 cents per floor for cent-based plots
        const requiredCents = numFloors * minCentsPerFloor;
        
        if (cents < requiredCents) {
          errors.push(`Plot size (${cents} cents) is too small for ${numFloors} floor(s). Minimum required: ${requiredCents} cents`);
        }
      }
    } else {
      // Standard sq ft validation for other units
      if (plotSizeSqFt < 500) {
        warnings.push('Plot size is quite small for a house');
      }
      
      if (plotSizeSqFt < 200) {
        errors.push('Plot size is too small for construction');
      }
      
      // Check if plot size allows for the number of floors
      if (formData.num_floors && plotSizeSqFt > 0) {
        const numFloors = parseInt(formData.num_floors);
        const minSqFtPerFloor = 800; // Minimum sq ft per floor
        const requiredSqFt = numFloors * minSqFtPerFloor;
        
        if (plotSizeSqFt < requiredSqFt) {
          errors.push(`Plot size (${plotSizeSqFt.toFixed(0)} sq ft) is too small for ${numFloors} floor(s). Minimum required: ${requiredSqFt} sq ft`);
        }
      }
    }
  }
  
  if (formData.budget && parseInt(formData.budget) < 500000) {
    warnings.push('Budget might be too low for construction');
  }
  
  // Generate suggestions based on input
  if (plotSizeSqFt > 0) {
    if (plotSizeSqFt > 2000) {
      suggestions.push('Large plot - consider multi-story design');
    } else if (plotSizeSqFt < 1000) {
      suggestions.push('Compact plot - optimize space usage');
    }
  }
  
  if (formData.budget) {
    const budget = parseInt(formData.budget);
    if (budget > 5000000) {
      suggestions.push('High budget - premium materials recommended');
    } else if (budget < 1000000) {
      suggestions.push('Budget-friendly - consider cost-effective materials');
    }
  }
  
  // Calculate estimated cost based on plot size if not provided
  let estimatedCost = formData.budget ? parseInt(formData.budget) : 0;
  if (!estimatedCost && plotSizeSqFt > 0) {
    // Optimized cost calculation based on plot size and unit
    if (formData.plot_unit === 'cents') {
      const cents = parseFloat(formData.plot_size);
      // Cent-based pricing: ₹50,000-₹1,00,000 per cent depending on location and complexity
      estimatedCost = cents * 75000; // ₹75,000 per cent average
    } else {
      estimatedCost = plotSizeSqFt * 1200; // ₹1200 per sq ft average
    }
  }
  
  return {
    is_valid: errors.length === 0,
    errors,
    warnings,
    suggestions,
    estimated_cost: estimatedCost,
    plot_category: plotSizeSqFt > 0 ? (plotSizeSqFt > 2000 ? 'large' : plotSizeSqFt > 1000 ? 'medium' : 'small') : 'unknown',
    budget_category: estimatedCost > 0 ? (estimatedCost > 3000000 ? 'high' : estimatedCost > 1000000 ? 'medium' : 'low') : 'unknown'
  };
};

const fallbackSuggestions = (formData) => {
  console.log('💡 Using JavaScript fallback suggestions...');
  
  const suggestions = [];
  
  if (formData.plot_size) {
    const plotSize = parseInt(formData.plot_size);
    if (plotSize > 2000) {
      suggestions.push('Large plot - consider multi-story design');
    } else if (plotSize < 1000) {
      suggestions.push('Compact plot - optimize space usage');
    }
  }
  
  if (formData.budget) {
    const budget = parseInt(formData.budget);
    if (budget > 5000000) {
      suggestions.push('High budget - premium materials recommended');
    } else if (budget < 1000000) {
      suggestions.push('Budget-friendly - consider cost-effective materials');
    }
  }
  
  return {
    is_valid: true,
    errors: [],
    warnings: [],
    suggestions,
    estimated_cost: formData.budget ? parseInt(formData.budget) : 0,
    plot_category: formData.plot_size ? (parseInt(formData.plot_size) > 2000 ? 'large' : 'medium') : 'unknown',
    budget_category: formData.budget ? (parseInt(formData.budget) > 3000000 ? 'high' : 'medium') : 'unknown'
  };
};

const fallbackAllowedOptions = (fieldName, formData) => {
  console.log('🎯 Using JavaScript fallback allowed options for:', fieldName);
  
  const options = {
    num_floors: ['1', '2', '3', '4', '5'],
    material_preferences: ['Brick', 'Concrete', 'Steel', 'Wood', 'Stone'],
    aesthetic: ['Modern', 'Traditional', 'Contemporary', 'Minimalist', 'Classical']
  };
  
  return options[fieldName] || [];
};

export const getRealTimeRecommendations = async (fieldName, value, formData) => {
  console.log('🔄 Getting real-time recommendations for:', fieldName, value);
  
  try {
    // Use the existing suggestions API
    const suggestions = await getSuggestions(formData);
    
    // Extract field-specific recommendations
    if (fieldName === 'plot_size') {
      return {
        category: suggestions.plot_category || 'unknown',
        message: `Plot size: ${value} sq ft - ${suggestions.plot_category || 'unknown'} category`,
        recommendations: suggestions.plot_size?.message || 'Consider optimizing space usage'
      };
    } else if (fieldName === 'budget_range') {
      return {
        category: suggestions.budget_category || 'unknown',
        message: `Budget: ${value} - ${suggestions.budget_category || 'unknown'} category`,
        recommendations: suggestions.budget?.message || 'Consider cost-effective materials'
      };
    } else if (fieldName === 'num_floors') {
      return {
        category: 'recommended',
        message: `Floors: ${value} - ${suggestions.plot_size?.recommended_floors || '1-3'} recommended`,
        recommendations: suggestions.plot_size?.message || 'Consider floor distribution'
      };
    }
    
    return {
      category: 'unknown',
      message: `Field: ${fieldName}`,
      recommendations: 'No specific recommendations available'
    };
    
  } catch (error) {
    console.error('❌ Real-time recommendations error:', error);
    return {
      category: 'unknown',
      message: `Field: ${fieldName}`,
      recommendations: 'Recommendations unavailable'
    };
  }
};

export default {
  validateForm,
  getSuggestions,
  getAllowedOptions,
  getRealTimeRecommendations
};
