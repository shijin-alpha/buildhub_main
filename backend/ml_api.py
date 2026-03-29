#!/usr/bin/env python3
"""
BuildHub ML API - Python script for PHP integration
Handles ML model predictions for the rules engine
"""

import sys
import json
import os
import numpy as np
from sklearn.tree import DecisionTreeClassifier, DecisionTreeRegressor
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
from sklearn.preprocessing import LabelEncoder
import joblib

class BuildHubMLAPI:
    """Lightweight ML API for BuildHub rules engine"""
    
    def __init__(self):
        self.models = {}
        self.encoders = {}
        self.rules_data = self._initialize_rules_data()
        self._load_or_train_models()
    
    def _initialize_rules_data(self):
        """Initialize rules data"""
        return {
            'plot_categories': {
                'small': {'min': 200, 'max': 1000, 'floors': [1, 2], 'budget': 'low'},
                'medium': {'min': 1000, 'max': 3000, 'floors': [1, 2, 3], 'budget': 'medium'},
                'large': {'min': 3000, 'max': 10000, 'floors': [2, 3, 4], 'budget': 'high'},
                'xlarge': {'min': 10000, 'max': 50000, 'floors': [3, 4, 5, 6], 'budget': 'luxury'}
            },
            'budget_categories': {
                'low': {'min': 0, 'max': 2000000, 'materials': ['Basic Concrete', 'Standard Brick', 'Economical Tiles']},
                'medium': {'min': 2000000, 'max': 5000000, 'materials': ['Quality Concrete', 'Premium Brick', 'Ceramic Tiles', 'Wood']},
                'high': {'min': 5000000, 'max': 10000000, 'materials': ['High-grade Concrete', 'Premium Materials', 'Marble/Granite']},
                'luxury': {'min': 10000000, 'max': 50000000, 'materials': ['Premium Concrete', 'Luxury Materials', 'Marble/Granite', 'Smart Materials']}
            },
            'floor_restrictions': {200: 1, 500: 1, 1000: 2, 2000: 3, 5000: 4, 10000: 5, 50000: 6},
            'cost_per_sqft': {'low': 800, 'medium': 1200, 'high': 1800, 'luxury': 3000}
        }
    
    def _load_or_train_models(self):
        """Load existing models or train new ones"""
        model_path = os.path.join(os.path.dirname(__file__), 'models', 'buildhub_rules_engine.pkl')
        
        if os.path.exists(model_path):
            try:
                model_data = joblib.load(model_path)
                self.models = model_data['models']
                self.encoders = model_data['encoders']
                self.rules_data = model_data['rules_data']
                print("Models loaded successfully", file=sys.stderr)
            except Exception as e:
                print(f"Error loading models: {e}", file=sys.stderr)
                self._train_simple_models()
        else:
            self._train_simple_models()
    
    def _train_simple_models(self):
        """Train simple models for immediate use"""
        print("Training simple models...", file=sys.stderr)
        
        # Generate simple training data
        np.random.seed(42)
        n_samples = 1000
        
        X = []
        y_valid = []
        y_cost = []
        y_plot = []
        y_budget = []
        
        for _ in range(n_samples):
            plot_size = np.random.uniform(200, 50000)
            budget = np.random.uniform(100000, 50000000)
            num_floors = np.random.randint(1, 7)
            
            X.append([plot_size, budget, num_floors])
            
            # Simple validation logic
            is_valid = plot_size >= 200 and budget >= 100000 and num_floors <= 6
            y_valid.append(int(is_valid))
            
            # Cost estimation
            budget_category = self._get_budget_category(budget)
            cost = plot_size * self.rules_data['cost_per_sqft'][budget_category]
            y_cost.append(cost)
            
            # Categories
            y_plot.append(self._get_plot_category(plot_size))
            y_budget.append(budget_category)
        
        X = np.array(X)
        
        # Train models
        self.models['validation'] = RandomForestClassifier(n_estimators=10, random_state=42)
        self.models['validation'].fit(X, y_valid)
        
        self.models['cost_estimation'] = RandomForestRegressor(n_estimators=10, random_state=42)
        self.models['cost_estimation'].fit(X, y_cost)
        
        # Category encoders
        self.encoders['plot_category'] = LabelEncoder()
        self.encoders['plot_category'].fit(y_plot)
        
        self.encoders['budget_category'] = LabelEncoder()
        self.encoders['budget_category'].fit(y_budget)
        
        print("Simple models trained", file=sys.stderr)
    
    def _get_plot_category(self, plot_size):
        """Get plot category"""
        for category, range_info in self.rules_data['plot_categories'].items():
            if range_info['min'] <= plot_size <= range_info['max']:
                return category
        return 'xlarge'
    
    def _get_budget_category(self, budget):
        """Get budget category"""
        for category, range_info in self.rules_data['budget_categories'].items():
            if range_info['min'] <= budget <= range_info['max']:
                return category
        return 'luxury'
    
    def _get_max_floors(self, plot_size):
        """Get maximum floors"""
        for size, max_floors in sorted(self.rules_data['floor_restrictions'].items()):
            if plot_size >= size:
                return max_floors
        return 6
    
    def validate_form(self, form_data):
        """Validate form data"""
        try:
            plot_size = float(form_data.get('plot_size', 0))
            budget = float(form_data.get('budget', 0))
            num_floors = int(form_data.get('num_floors', 1))
            
            X = np.array([[plot_size, budget, num_floors]])
            
            # Get predictions
            is_valid = self.models['validation'].predict(X)[0]
            cost_estimate = self.models['cost_estimation'].predict(X)[0]
            
            plot_category = self.encoders['plot_category'].inverse_transform(
                self.models['plot_category'].predict(X)
            )[0] if 'plot_category' in self.models else self._get_plot_category(plot_size)
            
            budget_category = self.encoders['budget_category'].inverse_transform(
                self.models['budget_category'].predict(X)
            )[0] if 'budget_category' in self.models else self._get_budget_category(budget)
            
            # Generate warnings and suggestions
            warnings = []
            suggestions = []
            errors = []
            
            if not is_valid:
                errors.append("Invalid combination detected")
            
            # Budget vs cost warning
            if budget < cost_estimate * 0.7:
                warnings.append(f"Budget may be insufficient. Estimated cost: ₹{cost_estimate:,.0f}")
            
            # Floor warning
            max_floors = self._get_max_floors(plot_size)
            if num_floors > max_floors:
                errors.append(f"Too many floors for plot size. Maximum: {max_floors}")
            elif num_floors > max_floors * 0.8:
                warnings.append(f"Consider reducing floors. Maximum recommended: {max_floors}")
            
            # Generate suggestions
            suggestions.append(f"Plot category: {plot_category}")
            suggestions.append(f"Budget category: {budget_category}")
            suggestions.append(f"Estimated cost: ₹{cost_estimate:,.0f}")
            
            return {
                'is_valid': bool(is_valid),
                'errors': errors,
                'warnings': warnings,
                'suggestions': suggestions,
                'estimated_cost': cost_estimate,
                'plot_category': plot_category,
                'budget_category': budget_category
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
            num_floors = int(form_data.get('num_floors', 1))
            
            X = np.array([[plot_size, budget, num_floors]])
            cost_estimate = self.models['cost_estimation'].predict(X)[0]
            
            plot_category = self._get_plot_category(plot_size)
            budget_category = self._get_budget_category(budget)
            
            allowed_materials = self.rules_data['budget_categories'][budget_category]['materials']
            rec_floors = self.rules_data['plot_categories'][plot_category]['floors']
            
            return {
                'plot_size': {
                    'category': plot_category,
                    'recommended_floors': rec_floors,
                    'message': f"For {plot_category} plot size, consider {rec_floors} floors"
                },
                'budget': {
                    'category': budget_category,
                    'estimated_cost': cost_estimate,
                    'message': f"Estimated cost: ₹{cost_estimate:,.0f} (₹{self.rules_data['cost_per_sqft'][budget_category]}/sq ft)"
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
                max_floors = self._get_max_floors(plot_size)
                return [str(i) for i in range(1, max_floors + 1)]
            
            elif field_name == 'material_preferences':
                budget = float(form_data.get('budget', 0))
                budget_category = self._get_budget_category(budget)
                return self.rules_data['budget_categories'][budget_category]['materials']
            
            elif field_name == 'aesthetic':
                budget = float(form_data.get('budget', 0))
                budget_category = self._get_budget_category(budget)
                if budget_category == 'low':
                    return ['Simple', 'Functional', 'Minimalist']
                elif budget_category == 'medium':
                    return ['Modern', 'Contemporary', 'Traditional']
                elif budget_category == 'high':
                    return ['Modern', 'Contemporary', 'Traditional', 'Luxury']
                else:
                    return ['Modern', 'Contemporary', 'Traditional', 'Luxury', 'Custom']
            
            return []
            
        except Exception as e:
            return []

def main():
    """Main function to handle API calls"""
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No input file provided'}))
        return
    
    input_file = sys.argv[1]
    output_file = sys.argv[2] if len(sys.argv) > 2 else None
    
    try:
        # Read input data
        with open(input_file, 'r') as f:
            data = json.load(f)
        
        # Initialize API
        api = BuildHubMLAPI()
        
        # Process request
        action = data.get('action', 'validate')
        
        if action == 'validate':
            result = api.validate_form(data)
        elif action == 'suggestions':
            result = api.get_suggestions(data)
        elif action == 'get_allowed_options':
            field_name = data.get('field_name', '')
            form_data = data.get('form_data', {})
            result = api.get_allowed_options(field_name, form_data)
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









