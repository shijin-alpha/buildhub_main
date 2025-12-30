"""
BuildHub ML Rules Engine - Decision Tree Algorithm
Real-time validation and suggestions for building design using machine learning
"""

import pandas as pd
import numpy as np
from sklearn.tree import DecisionTreeClassifier, DecisionTreeRegressor
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder, StandardScaler
from sklearn.metrics import accuracy_score, mean_squared_error
import joblib
import json
from typing import Dict, List, Tuple, Any
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class BuildHubRulesEngine:
    """
    Machine Learning-based Rules Engine for Building Design
    Uses decision trees and random forests for real-time validation and suggestions
    """
    
    def __init__(self):
        self.models = {}
        self.encoders = {}
        self.scalers = {}
        self.rules_data = self._initialize_rules_data()
        self._train_models()
    
    def _initialize_rules_data(self) -> Dict:
        """Initialize comprehensive rules data for training"""
        return {
            # Plot size categories and restrictions
            'plot_categories': {
                'small': {'min': 200, 'max': 1000, 'floors': [1, 2], 'budget': 'low'},
                'medium': {'min': 1000, 'max': 3000, 'floors': [1, 2, 3], 'budget': 'medium'},
                'large': {'min': 3000, 'max': 10000, 'floors': [2, 3, 4], 'budget': 'high'},
                'xlarge': {'min': 10000, 'max': 50000, 'floors': [3, 4, 5, 6], 'budget': 'luxury'}
            },
            
            # Budget categories and material restrictions
            'budget_categories': {
                'low': {'min': 0, 'max': 2000000, 'materials': ['Basic Concrete', 'Standard Brick', 'Economical Tiles']},
                'medium': {'min': 2000000, 'max': 5000000, 'materials': ['Quality Concrete', 'Premium Brick', 'Ceramic Tiles', 'Wood']},
                'high': {'min': 5000000, 'max': 10000000, 'materials': ['High-grade Concrete', 'Premium Materials', 'Marble/Granite']},
                'luxury': {'min': 10000000, 'max': 50000000, 'materials': ['Premium Concrete', 'Luxury Materials', 'Marble/Granite', 'Smart Materials']}
            },
            
            # Floor restrictions by plot size
            'floor_restrictions': {
                200: 1, 500: 1, 1000: 2, 2000: 3, 5000: 4, 10000: 5, 50000: 6
            },
            
            # Cost per sq ft by budget category
            'cost_per_sqft': {
                'low': 800, 'medium': 1200, 'high': 1800, 'luxury': 3000
            }
        }
    
    def _generate_training_data(self) -> pd.DataFrame:
        """Generate synthetic training data for ML models"""
        np.random.seed(42)
        n_samples = 10000
        
        data = []
        
        for _ in range(n_samples):
            # Generate plot size
            plot_size = np.random.uniform(200, 50000)
            
            # Generate budget based on plot size
            base_budget = plot_size * np.random.uniform(800, 3000)
            budget = base_budget + np.random.normal(0, base_budget * 0.2)
            budget = max(100000, budget)  # Minimum budget
            
            # Determine categories
            plot_category = self._get_plot_category(plot_size)
            budget_category = self._get_budget_category(budget)
            
            # Generate floor count based on plot size
            max_floors = self._get_max_floors(plot_size)
            num_floors = np.random.randint(1, min(max_floors + 1, 7))
            
            # Generate material preferences based on budget
            allowed_materials = self.rules_data['budget_categories'][budget_category]['materials']
            material_preferences = np.random.choice(
                allowed_materials, 
                size=np.random.randint(1, min(len(allowed_materials) + 1, 5)), 
                replace=False
            ).tolist()
            
            # Generate aesthetic based on budget
            aesthetic_options = ['Simple', 'Modern', 'Traditional', 'Luxury', 'Contemporary']
            if budget_category == 'low':
                aesthetic = np.random.choice(['Simple', 'Functional', 'Minimalist'])
            elif budget_category == 'medium':
                aesthetic = np.random.choice(['Modern', 'Contemporary', 'Traditional'])
            elif budget_category == 'high':
                aesthetic = np.random.choice(['Modern', 'Contemporary', 'Traditional', 'Luxury'])
            else:
                aesthetic = np.random.choice(['Modern', 'Contemporary', 'Traditional', 'Luxury', 'Custom'])
            
            # Generate validation results
            is_valid = self._validate_combination(plot_size, budget, num_floors, material_preferences, aesthetic)
            
            # Generate warnings
            warnings = self._generate_warnings(plot_size, budget, num_floors, material_preferences, aesthetic)
            
            # Generate suggestions
            suggestions = self._generate_suggestions(plot_size, budget, num_floors, material_preferences, aesthetic)
            
            data.append({
                'plot_size': plot_size,
                'budget': budget,
                'num_floors': num_floors,
                'material_preferences': json.dumps(material_preferences),
                'aesthetic': aesthetic,
                'plot_category': plot_category,
                'budget_category': budget_category,
                'is_valid': is_valid,
                'warnings': json.dumps(warnings),
                'suggestions': json.dumps(suggestions),
                'estimated_cost': plot_size * self.rules_data['cost_per_sqft'][budget_category]
            })
        
        return pd.DataFrame(data)
    
    def _get_plot_category(self, plot_size: float) -> str:
        """Get plot category based on size"""
        for category, range_info in self.rules_data['plot_categories'].items():
            if range_info['min'] <= plot_size <= range_info['max']:
                return category
        return 'xlarge'
    
    def _get_budget_category(self, budget: float) -> str:
        """Get budget category based on amount"""
        for category, range_info in self.rules_data['budget_categories'].items():
            if range_info['min'] <= budget <= range_info['max']:
                return category
        return 'luxury'
    
    def _get_max_floors(self, plot_size: float) -> int:
        """Get maximum floors allowed for plot size"""
        for size, max_floors in sorted(self.rules_data['floor_restrictions'].items()):
            if plot_size >= size:
                return max_floors
        return 6
    
    def _validate_combination(self, plot_size: float, budget: float, num_floors: int, 
                           material_preferences: List[str], aesthetic: str) -> bool:
        """Validate if the combination is feasible"""
        # Check plot size constraints
        if plot_size < 200 or plot_size > 50000:
            return False
        
        # Check floor constraints
        max_floors = self._get_max_floors(plot_size)
        if num_floors > max_floors:
            return False
        
        # Check budget constraints
        if budget < 100000:
            return False
        
        # Check material-budget compatibility
        budget_category = self._get_budget_category(budget)
        allowed_materials = self.rules_data['budget_categories'][budget_category]['materials']
        for material in material_preferences:
            if material not in allowed_materials:
                return False
        
        return True
    
    def _generate_warnings(self, plot_size: float, budget: float, num_floors: int,
                         material_preferences: List[str], aesthetic: str) -> List[str]:
        """Generate warnings for the combination"""
        warnings = []
        
        # Budget vs plot size warning
        estimated_cost = plot_size * self.rules_data['cost_per_sqft'][self._get_budget_category(budget)]
        if budget < estimated_cost * 0.7:
            warnings.append(f"Budget may be insufficient. Estimated cost: ₹{estimated_cost:,.0f}")
        
        # Floor warning
        max_floors = self._get_max_floors(plot_size)
        if num_floors > max_floors * 0.8:
            warnings.append(f"Consider reducing floors. Maximum recommended: {max_floors}")
        
        return warnings
    
    def _generate_suggestions(self, plot_size: float, budget: float, num_floors: int,
                            material_preferences: List[str], aesthetic: str) -> List[str]:
        """Generate suggestions for the combination"""
        suggestions = []
        
        plot_category = self._get_plot_category(plot_size)
        budget_category = self._get_budget_category(budget)
        
        # Plot size suggestions
        rec_floors = self.rules_data['plot_categories'][plot_category]['floors']
        suggestions.append(f"For {plot_category} plot size, consider {rec_floors} floors")
        
        # Budget suggestions
        estimated_cost = plot_size * self.rules_data['cost_per_sqft'][budget_category]
        suggestions.append(f"Estimated cost: ₹{estimated_cost:,.0f} (₹{self.rules_data['cost_per_sqft'][budget_category]}/sq ft)")
        
        # Material suggestions
        allowed_materials = self.rules_data['budget_categories'][budget_category]['materials']
        suggestions.append(f"Recommended materials for {budget_category} budget: {', '.join(allowed_materials[:3])}")
        
        return suggestions
    
    def _train_models(self):
        """Train ML models for different predictions"""
        logger.info("Generating training data...")
        df = self._generate_training_data()
        
        # Prepare features
        features = ['plot_size', 'budget', 'num_floors']
        X = df[features].values
        
        # Train validation model
        logger.info("Training validation model...")
        y_valid = df['is_valid'].values
        self.models['validation'] = RandomForestClassifier(n_estimators=100, random_state=42)
        self.models['validation'].fit(X, y_valid)
        
        # Train cost estimation model
        logger.info("Training cost estimation model...")
        y_cost = df['estimated_cost'].values
        self.models['cost_estimation'] = RandomForestRegressor(n_estimators=100, random_state=42)
        self.models['cost_estimation'].fit(X, y_cost)
        
        # Train category prediction models
        logger.info("Training category prediction models...")
        
        # Plot category
        le_plot = LabelEncoder()
        y_plot = le_plot.fit_transform(df['plot_category'])
        self.models['plot_category'] = DecisionTreeClassifier(random_state=42)
        self.models['plot_category'].fit(X, y_plot)
        self.encoders['plot_category'] = le_plot
        
        # Budget category
        le_budget = LabelEncoder()
        y_budget = le_budget.fit_transform(df['budget_category'])
        self.models['budget_category'] = DecisionTreeClassifier(random_state=42)
        self.models['budget_category'].fit(X, y_budget)
        self.encoders['budget_category'] = le_budget
        
        logger.info("All models trained successfully!")
    
    def validate_form(self, form_data: Dict) -> Dict:
        """
        Validate form data using ML models
        """
        try:
            # Extract features
            plot_size = float(form_data.get('plot_size', 0))
            budget = float(form_data.get('budget', 0))
            num_floors = int(form_data.get('num_floors', 1))
            
            # Prepare input for models
            X = np.array([[plot_size, budget, num_floors]])
            
            # Get predictions
            is_valid = self.models['validation'].predict(X)[0]
            cost_estimate = self.models['cost_estimation'].predict(X)[0]
            
            plot_category = self.encoders['plot_category'].inverse_transform(
                self.models['plot_category'].predict(X)
            )[0]
            
            budget_category = self.encoders['budget_category'].inverse_transform(
                self.models['budget_category'].predict(X)
            )[0]
            
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
            logger.error(f"Error in validation: {e}")
            return {
                'is_valid': False,
                'errors': [f"Validation error: {str(e)}"],
                'warnings': [],
                'suggestions': [],
                'estimated_cost': 0,
                'plot_category': 'unknown',
                'budget_category': 'unknown'
            }
    
    def get_suggestions(self, form_data: Dict) -> Dict:
        """
        Get ML-based suggestions for form data
        """
        try:
            plot_size = float(form_data.get('plot_size', 0))
            budget = float(form_data.get('budget', 0))
            num_floors = int(form_data.get('num_floors', 1))
            
            X = np.array([[plot_size, budget, num_floors]])
            
            # Get predictions
            cost_estimate = self.models['cost_estimation'].predict(X)[0]
            plot_category = self.encoders['plot_category'].inverse_transform(
                self.models['plot_category'].predict(X)
            )[0]
            budget_category = self.encoders['budget_category'].inverse_transform(
                self.models['budget_category'].predict(X)
            )[0]
            
            # Get allowed materials
            allowed_materials = self.rules_data['budget_categories'][budget_category]['materials']
            
            # Get recommended floors
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
            logger.error(f"Error in suggestions: {e}")
            return {}
    
    def get_allowed_options(self, field_name: str, form_data: Dict) -> List[str]:
        """
        Get allowed options for a field based on current form data
        """
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
            logger.error(f"Error getting allowed options: {e}")
            return []
    
    def save_models(self, filepath: str):
        """Save trained models to disk"""
        model_data = {
            'models': self.models,
            'encoders': self.encoders,
            'rules_data': self.rules_data
        }
        joblib.dump(model_data, filepath)
        logger.info(f"Models saved to {filepath}")
    
    def load_models(self, filepath: str):
        """Load trained models from disk"""
        model_data = joblib.load(filepath)
        self.models = model_data['models']
        self.encoders = model_data['encoders']
        self.rules_data = model_data['rules_data']
        logger.info(f"Models loaded from {filepath}")


# API endpoints for the frontend
def create_api_endpoints():
    """Create Flask API endpoints for the ML rules engine"""
    from flask import Flask, request, jsonify
    
    app = Flask(__name__)
    rules_engine = BuildHubRulesEngine()
    
    @app.route('/api/validate', methods=['POST'])
    def validate():
        """Validate form data"""
        try:
            form_data = request.get_json()
            result = rules_engine.validate_form(form_data)
            return jsonify(result)
        except Exception as e:
            return jsonify({'error': str(e)}), 500
    
    @app.route('/api/suggestions', methods=['POST'])
    def suggestions():
        """Get suggestions for form data"""
        try:
            form_data = request.get_json()
            result = rules_engine.get_suggestions(form_data)
            return jsonify(result)
        except Exception as e:
            return jsonify({'error': str(e)}), 500
    
    @app.route('/api/allowed-options', methods=['POST'])
    def allowed_options():
        """Get allowed options for a field"""
        try:
            data = request.get_json()
            field_name = data.get('field_name')
            form_data = data.get('form_data', {})
            options = rules_engine.get_allowed_options(field_name, form_data)
            return jsonify({'options': options})
        except Exception as e:
            return jsonify({'error': str(e)}), 500
    
    return app


if __name__ == "__main__":
    # Initialize and train the rules engine
    print("Initializing BuildHub ML Rules Engine...")
    rules_engine = BuildHubRulesEngine()
    
    # Save models
    rules_engine.save_models('models/buildhub_rules_engine.pkl')
    
    # Test the engine
    test_data = {
        'plot_size': 2000,
        'budget': 3000000,
        'num_floors': 2
    }
    
    print("\nTesting validation:")
    result = rules_engine.validate_form(test_data)
    print(json.dumps(result, indent=2))
    
    print("\nTesting suggestions:")
    suggestions = rules_engine.get_suggestions(test_data)
    print(json.dumps(suggestions, indent=2))
    
    print("\nML Rules Engine initialized successfully!")
