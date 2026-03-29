from flask import Flask, request, jsonify
from flask_cors import CORS
import json
import os
import sys
from architect_recommendation_engine import get_architect_recommendations, initialize_recommendation_engine
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Enable CORS for all routes
CORS(app, origins=['http://localhost:3000', 'http://127.0.0.1:3000'], 
     methods=['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
     allow_headers=['Content-Type', 'Authorization', 'Access-Control-Allow-Credentials'],
     supports_credentials=True)

@app.route('/api/architect-recommendations', methods=['POST', 'OPTIONS'])
def get_recommendations():
    """
    Get architect recommendations based on project data
    
    Expected JSON payload:
    {
        "budget_range": "30-50 Lakhs",
        "plot_size": "10",
        "plot_unit": "cents",
        "building_size": "2000",
        "num_floors": "2",
        "aesthetic": "Modern",
        "rooms": ["master_bedroom", "bedrooms", "kitchen", "living_room"],
        "location": "Kerala",
        "num_recommendations": 5
    }
    """
    # Handle preflight OPTIONS request
    if request.method == 'OPTIONS':
        response = jsonify({'status': 'OK'})
        response.headers.add('Access-Control-Allow-Origin', '*')
        response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization')
        response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
        return response
    
    try:
        # Get project data from request
        project_data = request.get_json()
        
        if not project_data:
            return jsonify({
                'success': False,
                'error': 'No project data provided'
            }), 400
        
        # More flexible validation like ml_simple.py - provide defaults for missing fields
        logger.info(f"Received project data: {project_data}")
        
        # Provide defaults for missing or empty fields (like ml_simple.py does)
        if not project_data.get('budget_range') or project_data.get('budget_range', '').strip() == '':
            project_data['budget_range'] = '20-30 Lakhs'  # Default budget
            logger.info("Using default budget_range: 20-30 Lakhs")
        
        if not project_data.get('plot_size') or project_data.get('plot_size', '').strip() == '':
            project_data['plot_size'] = '10'  # Default plot size
            logger.info("Using default plot_size: 10")
        
        if not project_data.get('aesthetic') or project_data.get('aesthetic', '').strip() == '':
            project_data['aesthetic'] = 'Modern'  # Default aesthetic
            logger.info("Using default aesthetic: Modern")
        
        # Ensure other fields have defaults
        project_data['plot_unit'] = project_data.get('plot_unit', 'cents')
        project_data['building_size'] = project_data.get('building_size', '2000')
        project_data['num_floors'] = project_data.get('num_floors', '1')
        project_data['rooms'] = project_data.get('rooms', [])
        project_data['location'] = project_data.get('location', 'Kerala')
        
        logger.info(f"Processed project data with defaults: {project_data}")
        
        # Get number of recommendations (default to 5)
        n_recommendations = project_data.get('num_recommendations', 5)
        
        # Ensure n_recommendations is within reasonable bounds
        n_recommendations = max(1, min(n_recommendations, 20))
        
        logger.info(f"Getting recommendations for project: {project_data.get('aesthetic')} style, {project_data.get('budget_range')} budget")
        
        # Get recommendations
        try:
            recommendations = get_architect_recommendations(project_data, n_recommendations)
            logger.info(f"KNN engine returned {len(recommendations) if recommendations else 0} recommendations")
        except Exception as e:
            logger.error(f"Error in KNN engine: {e}")
            response = jsonify({
                'success': False,
                'error': f'KNN engine error: {str(e)}'
            })
            response.status_code = 500
            response.headers.add('Access-Control-Allow-Origin', '*')
            response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization')
            response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
            return response
        
        if not recommendations:
            logger.warning("No recommendations returned from KNN engine")
            response = jsonify({
                'success': False,
                'error': 'No architects found matching your criteria. Please check if you have approved architects in your database.'
            })
            response.status_code = 404
            response.headers.add('Access-Control-Allow-Origin', '*')
            response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization')
            response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
            return response
        
        # Format response
        response_data = {
            'success': True,
            'recommendations': recommendations,
            'total_found': len(recommendations),
            'project_summary': {
                'style': project_data.get('aesthetic', ''),
                'budget': project_data.get('budget_range', ''),
                'plot_size': f"{project_data.get('plot_size', '')} {project_data.get('plot_unit', '')}",
                'floors': project_data.get('num_floors', '1'),
                'location': project_data.get('location', '')
            }
        }
        
        logger.info(f"Successfully generated {len(recommendations)} recommendations")
        response = jsonify(response_data)
        response.headers.add('Access-Control-Allow-Origin', '*')
        response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization')
        response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
        return response
        
    except Exception as e:
        logger.error(f"Error in get_recommendations: {e}")
        response = jsonify({
            'success': False,
            'error': 'Internal server error while generating recommendations'
        })
        response.headers.add('Access-Control-Allow-Origin', '*')
        response.headers.add('Access-Control-Allow-Headers', 'Content-Type,Authorization')
        response.headers.add('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,OPTIONS')
        return response, 500

@app.route('/api/architect-recommendations/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    try:
        # Try to initialize the engine
        engine = initialize_recommendation_engine()
        
        if engine and engine.is_trained:
            response = jsonify({
                'success': True,
                'status': 'healthy',
                'message': 'Architect recommendation engine is ready',
                'model_trained': True
            })
        else:
            response = jsonify({
                'success': False,
                'status': 'unhealthy',
                'message': 'Architect recommendation engine is not ready',
                'model_trained': False
            })
            response.status_code = 503
            
        response.headers.add('Access-Control-Allow-Origin', '*')
        return response
            
    except Exception as e:
        logger.error(f"Health check failed: {e}")
        response = jsonify({
            'success': False,
            'status': 'unhealthy',
            'message': 'Health check failed',
            'error': str(e)
        })
        response.status_code = 503
        response.headers.add('Access-Control-Allow-Origin', '*')
        return response

@app.route('/api/architect-recommendations/train', methods=['POST'])
def train_model():
    """Manually trigger model training"""
    try:
        logger.info("Manual model training requested")
        
        engine = initialize_recommendation_engine()
        
        if engine and engine.is_trained:
            return jsonify({
                'success': True,
                'message': 'Model trained successfully',
                'model_trained': True
            })
        else:
            return jsonify({
                'success': False,
                'message': 'Failed to train model',
                'model_trained': False
            }), 500
            
    except Exception as e:
        logger.error(f"Model training failed: {e}")
        return jsonify({
            'success': False,
            'message': 'Model training failed',
            'error': str(e)
        }), 500

@app.route('/api/architect-recommendations/sample', methods=['GET'])
def get_sample_recommendations():
    """Get sample recommendations for testing"""
    try:
        sample_project = {
            'budget_range': '30-50 Lakhs',
            'plot_size': '10',
            'plot_unit': 'cents',
            'building_size': '2000',
            'num_floors': '2',
            'aesthetic': 'Modern',
            'rooms': ['master_bedroom', 'bedrooms', 'kitchen', 'living_room'],
            'location': 'Kerala',
            'num_recommendations': 3
        }
        
        recommendations = get_architect_recommendations(sample_project, 3)
        
        return jsonify({
            'success': True,
            'sample_project': sample_project,
            'recommendations': recommendations,
            'total_found': len(recommendations)
        })
        
    except Exception as e:
        logger.error(f"Error in sample recommendations: {e}")
        return jsonify({
            'success': False,
            'error': 'Failed to generate sample recommendations'
        }), 500

if __name__ == '__main__':
    # Initialize the recommendation engine on startup
    logger.info("Initializing architect recommendation engine...")
    engine = initialize_recommendation_engine()
    
    if engine and engine.is_trained:
        logger.info("Architect recommendation engine ready!")
    else:
        logger.warning("Architect recommendation engine not ready - some features may not work")
    
    # Run the Flask app
    app.run(debug=True, host='0.0.0.0', port=5001)
