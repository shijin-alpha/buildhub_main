#!/usr/bin/env python3
"""
Simple ML API for BuildHub - Direct execution from PHP
"""

import sys
import json
import os

class SimpleMLEngine:
    """Simple ML engine for immediate use"""
    
    def __init__(self):
        pass
    
    def _get_plot_category(self, plot_size):
        """Get plot category based on realistic plot sizes"""
        if plot_size < 2000:  # Less than 5 cents
            return 'small'
        elif plot_size < 5000:  # 5-12 cents
            return 'medium'
        elif plot_size < 10000:  # 12-25 cents
            return 'large'
        elif plot_size < 20000:  # 25-50 cents
            return 'xlarge'
        else:  # 50+ cents
            return 'mega'
    
    def _get_budget_category(self, budget):
        """Get budget category based on realistic budget ranges"""
        if budget < 1000000:  # Less than 10 lakhs
            return 'low'
        elif budget < 3000000:  # 10-30 lakhs
            return 'medium'
        elif budget < 7500000:  # 30-75 lakhs
            return 'high'
        elif budget < 15000000:  # 75 lakhs - 1.5 crores
            return 'premium'
        else:  # 1.5+ crores
            return 'luxury'
    
    def _get_cost_per_sqft(self, budget_category):
        """Get cost per sq ft based on budget category"""
        costs = {
            'low': 1200,      # Basic construction
            'medium': 1800,   # Standard construction
            'high': 2500,     # Premium construction
            'premium': 3500,  # High-end construction
            'luxury': 5000    # Luxury construction
        }
        return costs.get(budget_category, 1800)
    
    def validate_form(self, form_data):
        """Validate form data"""
        try:
            plot_size = float(form_data.get('plot_size', 0))
            plot_unit = form_data.get('plot_unit', 'cents')
            budget = float(form_data.get('budget', 0))
            num_floors_str = form_data.get('num_floors', '1')
            if isinstance(num_floors_str, str):
                num_floors = int(num_floors_str) if num_floors_str and num_floors_str.strip() else 1
            else:
                num_floors = int(num_floors_str) if num_floors_str else 1
            
            # Convert plot size to square feet for consistent calculations
            if plot_unit == 'cents':
                plot_size_sqft = plot_size * 435.6  # 1 cent = 435.6 sq ft
            elif plot_unit == 'acres':
                plot_size_sqft = plot_size * 43560  # 1 acre = 43560 sq ft
            else:  # sqft
                plot_size_sqft = plot_size
            
            # Get user's building size if provided
            building_size_str = form_data.get('building_size', '')
            user_building_size = building_size_str  # Keep original user value
            
            # For ML calculations, use user's value or calculate default
            if building_size_str and building_size_str.strip():
                try:
                    building_size_for_calc = float(building_size_str)
                except (ValueError, TypeError):
                    building_size_for_calc = plot_size_sqft * 0.6  # Default 60% of plot
            else:
                # Calculate default for ML calculations only
                building_size_for_calc = plot_size_sqft * 0.6 if plot_size_sqft > 0 else 0
            
            # Use calculated value for ML, but keep original for return
            building_size = building_size_for_calc
            
            # Simple validation logic (using square feet)
            is_valid = plot_size_sqft >= 200 and budget >= 100000 and num_floors <= 6
            
            # Determine budget category first based on user's budget
            budget_category = self._get_budget_category(budget)
            
            # Get cost per sq ft based on budget category
            base_cost_per_sqft = self._get_cost_per_sqft(budget_category)
            
            # Adjust cost based on plot size (larger plots = slightly higher cost per sq ft)
            if plot_size_sqft >= 20000:  # 50+ cents (20000+ sq ft)
                base_cost_per_sqft *= 1.1
            elif plot_size_sqft >= 10000:  # 25+ cents (10000+ sq ft)
                base_cost_per_sqft *= 1.05
            elif plot_size_sqft < 2000:  # Less than 5 cents (<2000 sq ft)
                base_cost_per_sqft *= 0.95
            
            # Adjust for building size ratio
            building_ratio = building_size / plot_size_sqft if plot_size_sqft > 0 else 0.6
            if building_ratio > 0.8:  # High density
                base_cost_per_sqft *= 1.15
            elif building_ratio < 0.4:  # Low density
                base_cost_per_sqft *= 0.9
            
            # Calculate final cost estimate
            cost_estimate = building_size * base_cost_per_sqft
            
            # Get plot category for suggestions (using square feet)
            plot_category = self._get_plot_category(plot_size_sqft)
            
            # Generate warnings and suggestions
            warnings = []
            suggestions = []
            errors = []
            
            if not is_valid:
                errors.append("Invalid combination detected")
            
            # Budget vs cost warning
            if budget < cost_estimate * 0.7:
                warnings.append(f"Budget may be insufficient. Estimated cost: ₹{cost_estimate:,.0f}")
            
            # Floor constraints based on plot size and budget
            plot_max_floors = min(6, max(1, int(plot_size_sqft / 1000)))  # More realistic: 1000 sq ft per floor
            budget_max_floors = self._get_max_floors_by_budget(budget)
            max_floors = min(plot_max_floors, budget_max_floors)
            
            if num_floors > max_floors:
                errors.append(f"Too many floors. Maximum allowed: {max_floors} (plot: {plot_max_floors}, budget: {budget_max_floors})")
            elif num_floors > max_floors * 0.8:
                warnings.append(f"Consider reducing floors. Maximum recommended: {max_floors}")
            
            # Predict timeline
            predicted_timeline = self._predict_timeline(plot_size_sqft, building_size, num_floors, budget_category)
            
            # Generate suggestions
            suggestions.append(f"Plot category: {plot_category}")
            suggestions.append(f"Budget category: {budget_category}")
            suggestions.append(f"Estimated cost: ₹{cost_estimate:,.0f}")
            suggestions.append(f"Predicted timeline: {predicted_timeline} months")
            
            # Return user's original building_size (not calculated value)
            return_building_size = float(user_building_size) if user_building_size and user_building_size.strip() else building_size
            
            return {
                'is_valid': bool(is_valid),
                'errors': errors,
                'warnings': warnings,
                'suggestions': suggestions,
                'estimated_cost': cost_estimate,
                'plot_category': plot_category,
                'budget_category': budget_category,
                'predicted_timeline': predicted_timeline,
                'building_size': return_building_size,
                'plot_size_sqft': plot_size_sqft,
                'cost_per_sqft': base_cost_per_sqft
            }
            
        except Exception as e:
            return {
                'is_valid': False,
                'errors': [f"Validation error: {str(e)}"],
                'warnings': [],
                'suggestions': [],
                'estimated_cost': 0,
                'plot_category': 'unknown',
                'budget_category': 'unknown'
            }
    
    def get_suggestions(self, form_data):
        """Get suggestions"""
        try:
            plot_size = float(form_data.get('plot_size', 0))
            budget = float(form_data.get('budget', 0))
            num_floors_str = form_data.get('num_floors', '1')
            if isinstance(num_floors_str, str):
                num_floors = int(num_floors_str) if num_floors_str and num_floors_str.strip() else 1
            else:
                num_floors = int(num_floors_str) if num_floors_str else 1
            
            # Simple cost estimation
            budget_category = self._get_budget_category(budget)
            cost_estimate = plot_size * self._get_cost_per_sqft(budget_category)
            
            plot_category = self._get_plot_category(plot_size)
            
            allowed_materials = self._get_allowed_materials(budget_category)
            rec_floors = self._get_recommended_floors(plot_category)
            
            return {
                'plot_size': {
                    'category': plot_category,
                    'recommended_floors': rec_floors,
                    'message': f"For {plot_category} plot size, consider {rec_floors} floors"
                },
                'budget': {
                    'category': budget_category,
                    'estimated_cost': cost_estimate,
                    'message': f"Estimated cost: ₹{cost_estimate:,.0f} (₹{self._get_cost_per_sqft(budget_category)}/sq ft)"
                },
                'materials': {
                    'recommended': allowed_materials,
                    'message': f"Recommended materials for {budget_category} budget"
                }
            }
            
        except Exception as e:
            return {'error': str(e)}
    
    def get_allowed_options(self, field_name, form_data):
        """Get allowed options for a field"""
        try:
            if field_name == 'num_floors':
                plot_size = float(form_data.get('plot_size', 0))
                plot_unit = form_data.get('plot_unit', 'cents')
                budget = float(form_data.get('budget', 0))
                
                # Convert plot size to square feet for consistent calculations
                if plot_unit == 'cents':
                    plot_size_sqft = plot_size * 435.6  # 1 cent = 435.6 sq ft
                elif plot_unit == 'acres':
                    plot_size_sqft = plot_size * 43560  # 1 acre = 43560 sq ft
                else:  # sqft
                    plot_size_sqft = plot_size
                
                # Calculate max floors based on both plot size and budget
                plot_max_floors = min(6, max(1, int(plot_size_sqft / 1000)))  # More realistic: 1000 sq ft per floor
                budget_max_floors = self._get_max_floors_by_budget(budget)
                
                # Debug output
                print(f"DEBUG: plot_size={plot_size} {plot_unit} = {plot_size_sqft} sqft, budget={budget}", file=sys.stderr)
                print(f"DEBUG: plot_max_floors={plot_max_floors}, budget_max_floors={budget_max_floors}", file=sys.stderr)
                
                # Use the minimum of both constraints
                max_floors = min(plot_max_floors, budget_max_floors)
                print(f"DEBUG: final max_floors={max_floors}", file=sys.stderr)
                
                return [str(i) for i in range(1, max_floors + 1)]
            
            elif field_name == 'material_preferences':
                budget = float(form_data.get('budget', 0))
                budget_category = self._get_budget_category(budget)
                return self._get_allowed_materials(budget_category)
            
            return []
            
        except Exception as e:
            print(f"DEBUG: Error in get_allowed_options: {e}", file=sys.stderr)
            return []
    
    def classify_design_suggestion(self, form_data):
        """
        Design Suggestion Classification
        Classifies and suggests appropriate design types based on user inputs
        """
        try:
            plot_size = float(form_data.get('plot_size', 0))
            plot_unit = form_data.get('plot_unit', 'cents')
            budget = float(form_data.get('budget', 0))
            num_floors_str = form_data.get('num_floors', '1')
            family_needs = form_data.get('family_needs', [])
            aesthetic = form_data.get('aesthetic', '')
            
            if isinstance(num_floors_str, str):
                num_floors = int(num_floors_str) if num_floors_str and num_floors_str.strip() else 1
            else:
                num_floors = int(num_floors_str) if num_floors_str else 1
            
            # Convert plot size to square feet
            if plot_unit == 'cents':
                plot_size_sqft = plot_size * 435.6
            elif plot_unit == 'acres':
                plot_size_sqft = plot_size * 43560
            else:
                plot_size_sqft = plot_size
            
            # Get plot and budget categories
            plot_category = self._get_plot_category(plot_size_sqft)
            budget_category = self._get_budget_category(budget)
            
            # Design type classification
            design_suggestion = self._classify_design_type(
                plot_category, budget_category, num_floors, family_needs, aesthetic
            )
            
            # Room configuration suggestion
            room_config = self._suggest_room_configuration(
                family_needs, num_floors, budget_category
            )
            
            # Material recommendation
            material_suggestion = self._get_allowed_materials(budget_category)
            
            # Style recommendation based on aesthetic and budget
            style_suggestion = self._suggest_style(budget_category, aesthetic)
            
            return {
                'success': True,
                'design_classification': design_suggestion,
                'room_configuration': room_config,
                'material_suggestion': material_suggestion,
                'style_suggestion': style_suggestion,
                'plot_category': plot_category,
                'budget_category': budget_category
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': str(e)
            }
    
    def _classify_design_type(self, plot_category, budget_category, num_floors, family_needs, aesthetic):
        """
        Classify design type based on input parameters
        """
        # Determine design complexity
        complexity = 'moderate'
        
        if budget_category in ['premium', 'luxury']:
            complexity = 'high'
        elif budget_category == 'low':
            complexity = 'basic'
        
        # Determine if it's custom or pre-designed
        is_custom = True
        
        # Auto-suggest pre-designed for simple requirements
        if (plot_category in ['small', 'medium'] and 
            budget_category in ['low', 'medium'] and 
            num_floors <= 2 and
            not aesthetic):
            is_custom = False
        
        # Family needs analysis
        family_type = 'standard'
        if isinstance(family_needs, list):
            if any('elderly' in str(need).lower() for need in family_needs):
                family_type = 'accessible'
            elif any('young' in str(need).lower() or 'child' in str(need).lower() for need in family_needs):
                family_type = 'family_friendly'
        
        design_type = {
            'complexity': complexity,
            'is_custom': is_custom,
            'family_type': family_type,
            'recommended_type': 'custom' if is_custom else 'pre-designed',
            'description': self._get_design_description(complexity, is_custom, family_type)
        }
        
        return design_type
    
    def _get_design_description(self, complexity, is_custom, family_type):
        """Get human-readable design description"""
        if not is_custom:
            return "Pre-designed layout recommended for quick and cost-effective construction"
        
        if complexity == 'high':
            desc = "High-end custom design"
        elif complexity == 'basic':
            desc = "Basic custom design"
        else:
            desc = "Standard custom design"
        
        if family_type == 'accessible':
            desc += " with accessibility features"
        elif family_type == 'family_friendly':
            desc += " optimized for families with children"
        
        return desc
    
    def _suggest_room_configuration(self, family_needs, num_floors, budget_category):
        """Suggest optimal room configuration"""
        # Base configuration
        config = {
            'bedrooms': 2,
            'bathrooms': 2,
            'kitchen': 1,
            'living_room': 1,
            'dining_room': 1,
            'parking': 1
        }
        
        # Adjust based on family needs
        if isinstance(family_needs, list):
            if any('elderly' in str(need).lower() for need in family_needs):
                config['bathrooms'] += 1  # More bathrooms for accessibility
            if any('large' in str(need).lower() or 'big' in str(need).lower() for need in family_needs):
                config['bedrooms'] += 1
                config['bathrooms'] += 1
            if any('work' in str(need).lower() or 'office' in str(need).lower() for need in family_needs):
                config['study_room'] = 1
            if any('car' in str(need).lower() or 'vehicle' in str(need).lower() for need in family_needs):
                config['parking'] = 2
        
        # Adjust based on floors
        if num_floors >= 3:
            config['bedrooms'] += 1
            config['bathrooms'] += 1
            if 'study_room' not in config:
                config['study_room'] = 1
        
        # Adjust based on budget
        if budget_category in ['premium', 'luxury']:
            config['bedrooms'] += 1
            config['powder_room'] = 1
            config['store_room'] = 1
        
        return config
    
    def _suggest_style(self, budget_category, aesthetic_preference):
        """Suggest architectural style based on budget and preference"""
        style_categories = {
            'low': ['Modern Minimalist', 'Contemporary'],
            'medium': ['Modern', 'Contemporary', 'Traditional Modern'],
            'high': ['Modern', 'Contemporary', 'Luxury', 'European'],
            'premium': ['Luxury', 'Modern', 'Contemporary', 'European', 'Mediterranean'],
            'luxury': ['Luxury', 'Modern Luxury', 'Contemporary', 'European', 'Classical']
        }
        
        base_styles = style_categories.get(budget_category, style_categories['medium'])
        
        # If user has an aesthetic preference, prioritize it
        if aesthetic_preference:
            if aesthetic_preference in base_styles:
                return {
                    'primary': aesthetic_preference,
                    'alternatives': base_styles
                }
        
        return {
            'primary': base_styles[0],
            'alternatives': base_styles[1:]
        }
    
    def _get_allowed_materials(self, budget_category):
        """Get allowed materials for budget category"""
        materials = {
            'low': ['Basic Concrete', 'Standard Brick', 'Economical Tiles', 'Basic Wood'],
            'medium': ['Quality Concrete', 'Premium Brick', 'Ceramic Tiles', 'Wood', 'Vitrified Tiles'],
            'high': ['High-grade Concrete', 'Premium Materials', 'Marble/Granite', 'Natural Stone'],
            'premium': ['Premium Concrete', 'Luxury Materials', 'Marble/Granite', 'Smart Materials', 'Glass'],
            'luxury': ['Premium Concrete', 'Luxury Materials', 'Marble/Granite', 'Smart Materials', 'Glass', 'Steel']
        }
        return materials.get(budget_category, materials['medium'])
    
    def _get_recommended_floors(self, plot_category):
        """Get recommended floors for plot category"""
        floors = {
            'small': [1, 2],           # Less than 5 cents
            'medium': [1, 2, 3],        # 5-12 cents
            'large': [2, 3, 4],         # 12-25 cents
            'xlarge': [3, 4, 5, 6],    # 25-50 cents
            'mega': [4, 5, 6]          # 50+ cents
        }
        return floors.get(plot_category, [1, 2, 3])
    
    def _predict_timeline(self, plot_size_sqft, building_size, num_floors, budget_category):
        """Predict project timeline based on project parameters"""
        # Base timeline in months
        base_months = 6
        
        # Adjust based on plot size
        if plot_size_sqft >= 20000:  # 50+ cents
            base_months += 4
        elif plot_size_sqft >= 10000:  # 25+ cents
            base_months += 2
        elif plot_size_sqft >= 5000:  # 12+ cents
            base_months += 1
        elif plot_size_sqft < 2000:  # Less than 5 cents
            base_months -= 1
        
        # Adjust based on building size
        if building_size >= 5000:  # Large building
            base_months += 3
        elif building_size >= 3000:  # Medium building
            base_months += 2
        elif building_size >= 1500:  # Small building
            base_months += 1
        
        # Adjust based on floors
        if num_floors >= 4:
            base_months += 3
        elif num_floors >= 3:
            base_months += 2
        elif num_floors >= 2:
            base_months += 1
        
        # Adjust based on budget category (higher budget = more complex = longer time)
        if budget_category == 'luxury':
            base_months += 4
        elif budget_category == 'premium':
            base_months += 3
        elif budget_category == 'high':
            base_months += 2
        elif budget_category == 'medium':
            base_months += 1
        
        # Ensure minimum timeline
        return max(base_months, 4)
    
    def _get_max_floors_by_budget(self, budget):
        """Get maximum floors allowed by budget"""
        if budget < 1000000:  # Less than 10 lakhs
            return 1
        elif budget < 3000000:  # 10-30 lakhs
            return 2
        elif budget < 6000000:  # 30-60 lakhs
            return 3
        elif budget < 10000000:  # 60 lakhs - 1 crore
            return 4
        elif budget < 20000000:  # 1-2 crores
            return 5
        else:  # 2+ crores
            return 6

def main():
    """Main function"""
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No input file provided'}))
        return
    
    input_file = sys.argv[1]
    output_file = sys.argv[2] if len(sys.argv) > 2 else None
    
    try:
        # Read input data
        with open(input_file, 'r') as f:
            data = json.load(f)
        
        # Initialize ML engine
        ml_engine = SimpleMLEngine()
        
        # Process request
        action = data.get('action', 'validate')
        
        if action == 'validate':
            result = ml_engine.validate_form(data)
        elif action == 'suggestions':
            result = ml_engine.get_suggestions(data)
        elif action == 'get_allowed_options':
            field_name = data.get('field_name', '')
            form_data = data  # The data itself is the form data
            result = ml_engine.get_allowed_options(field_name, form_data)
        elif action == 'classify_design':
            result = ml_engine.classify_design_suggestion(data)
        else:
            result = {'error': 'Invalid action'}
        
        # Write output
        if output_file:
            with open(output_file, 'w') as f:
                json.dump(result, f)
        else:
            print(json.dumps(result))
            
    except Exception as e:
        error_result = {'error': str(e)}
        if output_file:
            with open(output_file, 'w') as f:
                json.dump(error_result, f)
        else:
            print(json.dumps(error_result))

if __name__ == "__main__":
    main()

