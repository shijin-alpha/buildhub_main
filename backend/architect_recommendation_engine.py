import numpy as np
import pandas as pd
from sklearn.neighbors import NearestNeighbors
from sklearn.preprocessing import StandardScaler
from sklearn.metrics.pairwise import cosine_similarity
import json
import pymysql
import os
from typing import List, Dict, Any, Tuple
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class ArchitectRecommendationEngine:
    """
    KNN-based Architect Recommendation Engine
    
    Uses K-Nearest Neighbors algorithm to recommend architects based on:
    - Project requirements (budget, plot size, style, rooms)
    - Architect specialties and experience
    - Ratings and reviews
    - Location preferences
    """
    
    def __init__(self, db_config: Dict[str, str] = None):
        # Default MySQL connection config (matches your existing setup)
        self.db_config = db_config or {
            'host': 'localhost',
            'user': 'root',
            'password': '',
            'database': 'buildhub',
            'charset': 'utf8mb4'
        }
        self.scaler = StandardScaler()
        self.knn_model = None
        self.architect_features = None
        self.project_features = None
        self.is_trained = False
        
    def connect_db(self):
        """Connect to MySQL database"""
        try:
            conn = pymysql.connect(
                host=self.db_config['host'],
                user=self.db_config['user'],
                password=self.db_config['password'],
                database=self.db_config['database'],
                charset=self.db_config['charset']
            )
            return conn
        except Exception as e:
            logger.error(f"Database connection error: {e}")
            return None
    
    def get_architects_data(self) -> pd.DataFrame:
        """Fetch architects data from existing users table"""
        conn = self.connect_db()
        if not conn:
            return pd.DataFrame()
        
        try:
            query = """
            SELECT 
                u.id,
                CONCAT(u.first_name, ' ', u.last_name) as name,
                u.email,
                u.specialization as specialty,
                u.experience_years,
                u.city,
                u.state,
                u.location,
                u.company_name,
                u.portfolio,
                u.license,
                u.created_at,
                u.is_verified,
                u.status,
                COALESCE((SELECT ROUND(AVG(r.rating),2) FROM architect_reviews r WHERE r.architect_id = u.id), 0) as rating,
                COALESCE((SELECT COUNT(*) FROM architect_reviews r2 WHERE r2.architect_id = u.id), 0) as num_reviews,
                COALESCE((SELECT COUNT(*) FROM layout_request_assignments la WHERE la.architect_id = u.id), 0) as portfolio_count,
                95.0 as success_rate,
                24 as response_time_hours,
                0 as price_range_min,
                10000000 as price_range_max
            FROM users u
            WHERE u.role = 'architect' AND u.status = 'approved'
            """
            
            df = pd.read_sql(query, conn)
            conn.close()
            
            logger.info(f"Fetched {len(df)} architects from existing database")
            return df
            
        except Exception as e:
            logger.error(f"Error fetching architects data: {e}")
            conn.close()
            return pd.DataFrame()
    
    def preprocess_architect_features(self, df: pd.DataFrame) -> pd.DataFrame:
        """Preprocess architect features for ML model using existing data structure"""
        if df.empty:
            return df
        
        # Create a copy to avoid modifying original
        processed_df = df.copy()
        
        # Handle missing values
        processed_df['experience_years'] = processed_df['experience_years'].fillna(0)
        processed_df['rating'] = processed_df['rating'].fillna(0)
        processed_df['num_reviews'] = processed_df['num_reviews'].fillna(0)
        processed_df['portfolio_count'] = processed_df['portfolio_count'].fillna(0)
        processed_df['success_rate'] = processed_df['success_rate'].fillna(95.0)
        processed_df['response_time_hours'] = processed_df['response_time_hours'].fillna(24)
        
        # Handle price ranges (using defaults since not in existing table)
        processed_df['price_range_min'] = processed_df['price_range_min'].fillna(0)
        processed_df['price_range_max'] = processed_df['price_range_max'].fillna(10000000)
        processed_df['avg_price'] = (processed_df['price_range_min'] + processed_df['price_range_max']) / 2
        
        # Create specialty encoding based on existing specialization field
        specialty_mapping = {
            'residential': 1,
            'commercial': 2,
            'interior': 3,
            'landscape': 4,
            'sustainable': 5,
            'luxury': 6,
            'traditional': 7,
            'modern': 8,
            'mixed': 9,
            'interior design': 3,
            'residential design': 1,
            'commercial design': 2,
            'modern design': 8,
            'traditional design': 7
        }
        
        # Map specialization to encoded value
        processed_df['specialty_encoded'] = processed_df['specialty'].str.lower().map(specialty_mapping).fillna(0)
        
        # Create specializations features from existing specialization field
        specializations_features = self._create_specializations_features_from_existing(processed_df)
        processed_df = pd.concat([processed_df, specializations_features], axis=1)
        
        # Create location features from existing location data
        location_features = self._create_location_features_from_existing(processed_df)
        processed_df = pd.concat([processed_df, location_features], axis=1)
        
        # Create experience level features
        processed_df['experience_level'] = self._categorize_experience(processed_df['experience_years'])
        processed_df['rating_level'] = self._categorize_rating(processed_df['rating'])
        
        # Create composite score
        processed_df['composite_score'] = self._calculate_composite_score(processed_df)
        
        return processed_df
    
    def _create_specializations_features_from_existing(self, df: pd.DataFrame) -> pd.DataFrame:
        """Create binary features for specializations from existing specialization field"""
        specializations_list = [
            'modern', 'traditional', 'contemporary', 'minimalist', 'luxury', 'eco_friendly',
            'smart_home', 'interior_design', 'landscape', 'renovation', 'new_construction',
            'villa', 'apartment', 'commercial', 'residential', 'hospitality', 'educational'
        ]
        
        features_df = pd.DataFrame(index=df.index)
        
        # Use the existing specialization field instead of specializations
        for spec in specializations_list:
            features_df[f'has_{spec}'] = df['specialty'].str.contains(spec, case=False, na=False).astype(int)
        
        return features_df
    
    def _create_location_features_from_existing(self, df: pd.DataFrame) -> pd.DataFrame:
        """Create location-based features from existing location data"""
        features_df = pd.DataFrame(index=df.index)
        
        # Combine location, city, and state fields for analysis
        location_combined = df['location'].fillna('') + ' ' + df['city'].fillna('') + ' ' + df['state'].fillna('')
        
        # Kerala-specific features
        features_df['is_kerala'] = location_combined.str.contains('kerala', case=False, na=False).astype(int)
        features_df['is_urban'] = location_combined.str.contains('city|urban|metro|mumbai|delhi|bangalore|chennai|hyderabad|pune|kolkata', case=False, na=False).astype(int)
        features_df['is_rural'] = location_combined.str.contains('rural|village|town|district', case=False, na=False).astype(int)
        
        return features_df
    
    def _categorize_experience(self, experience_years: pd.Series) -> pd.Series:
        """Categorize experience into levels"""
        def categorize(exp):
            if exp < 2:
                return 1  # Beginner
            elif exp < 5:
                return 2  # Intermediate
            elif exp < 10:
                return 3  # Experienced
            elif exp < 15:
                return 4  # Senior
            else:
                return 5  # Expert
        
        return experience_years.apply(categorize)
    
    def _categorize_rating(self, rating: pd.Series) -> pd.Series:
        """Categorize rating into levels"""
        def categorize(rating):
            if rating < 3.0:
                return 1  # Poor
            elif rating < 4.0:
                return 2  # Average
            elif rating < 4.5:
                return 3  # Good
            elif rating < 4.8:
                return 4  # Very Good
            else:
                return 5  # Excellent
        
        return rating.apply(categorize)
    
    def _calculate_composite_score(self, df: pd.DataFrame) -> pd.Series:
        """Calculate composite score for architects"""
        # Weighted combination of different factors
        score = (
            df['rating'] * 0.3 +  # Rating weight
            (df['num_reviews'] / 100) * 0.2 +  # Review count weight (normalized)
            (df['experience_years'] / 20) * 0.2 +  # Experience weight (normalized)
            (df['success_rate'] / 100) * 0.15 +  # Success rate weight
            (df['portfolio_count'] / 50) * 0.1 +  # Portfolio weight (normalized)
            (5 - df['response_time_hours'] / 24) * 0.05  # Response time weight (inverted)
        )
        
        return score.clip(0, 5)  # Ensure score is between 0 and 5
    
    def preprocess_project_features(self, project_data: Dict[str, Any]) -> np.ndarray:
        """Preprocess project data for matching"""
        features = []
        
        # Budget features
        budget_range = project_data.get('budget_range', '')
        budget_value = self._parse_budget_range(budget_range)
        features.extend([
            budget_value,
            budget_value / 1000000,  # Normalized budget
            self._categorize_budget(budget_value)
        ])
        
        # Plot size features
        plot_size = float(project_data.get('plot_size', 0))
        plot_unit = project_data.get('plot_unit', 'cents')
        plot_size_sqft = self._convert_to_sqft(plot_size, plot_unit)
        features.extend([
            plot_size_sqft,
            plot_size_sqft / 1000,  # Normalized plot size
            self._categorize_plot_size(plot_size_sqft)
        ])
        
        # Building size features
        building_size = float(project_data.get('building_size', 0))
        features.extend([
            building_size,
            building_size / 1000,  # Normalized building size
            self._categorize_building_size(building_size)
        ])
        
        # Floor features
        num_floors = int(project_data.get('num_floors', 1))
        features.extend([
            num_floors,
            self._categorize_floors(num_floors)
        ])
        
        # Style features
        aesthetic = project_data.get('aesthetic', '')
        style_features = self._encode_style(aesthetic)
        features.extend(style_features)
        
        # Room features
        rooms = project_data.get('rooms', [])
        room_features = self._encode_rooms(rooms)
        features.extend(room_features)
        
        # Location features
        location = project_data.get('location', '')
        location_features = self._encode_location(location)
        features.extend(location_features)
        
        return np.array(features).reshape(1, -1)
    
    def _parse_budget_range(self, budget_range: str) -> float:
        """Parse budget range string to numeric value"""
        if not budget_range:
            return 0
        
        budget_range = budget_range.lower()
        if '5-10' in budget_range:
            return 7500000  # 7.5 lakhs
        elif '10-20' in budget_range:
            return 15000000  # 15 lakhs
        elif '20-30' in budget_range:
            return 25000000  # 25 lakhs
        elif '30-50' in budget_range:
            return 40000000  # 40 lakhs
        elif '50-75' in budget_range:
            return 62500000  # 62.5 lakhs
        elif '75 lakhs - 1 crore' in budget_range:
            return 87500000  # 87.5 lakhs
        elif '1-2 crores' in budget_range:
            return 150000000  # 1.5 crores
        elif '2-5 crores' in budget_range:
            return 350000000  # 3.5 crores
        elif '5+ crores' in budget_range:
            return 750000000  # 7.5 crores
        else:
            return 0
    
    def _convert_to_sqft(self, size: float, unit: str) -> float:
        """Convert plot size to square feet"""
        if unit == 'cents':
            return size * 435.6
        elif unit == 'acres':
            return size * 43560
        else:  # sqft
            return size
    
    def _categorize_budget(self, budget: float) -> int:
        """Categorize budget into levels"""
        if budget < 1000000:
            return 1  # Low
        elif budget < 5000000:
            return 2  # Medium
        elif budget < 20000000:
            return 3  # High
        elif budget < 100000000:
            return 4  # Premium
        else:
            return 5  # Luxury
    
    def _categorize_plot_size(self, plot_size_sqft: float) -> int:
        """Categorize plot size into levels"""
        if plot_size_sqft < 1000:
            return 1  # Small
        elif plot_size_sqft < 3000:
            return 2  # Medium
        elif plot_size_sqft < 5000:
            return 3  # Large
        elif plot_size_sqft < 10000:
            return 4  # Very Large
        else:
            return 5  # Extra Large
    
    def _categorize_building_size(self, building_size: float) -> int:
        """Categorize building size into levels"""
        if building_size < 500:
            return 1  # Small
        elif building_size < 1500:
            return 2  # Medium
        elif building_size < 3000:
            return 3  # Large
        elif building_size < 5000:
            return 4  # Very Large
        else:
            return 5  # Extra Large
    
    def _categorize_floors(self, num_floors: int) -> int:
        """Categorize number of floors"""
        if num_floors == 1:
            return 1  # Single story
        elif num_floors == 2:
            return 2  # Two story
        elif num_floors <= 4:
            return 3  # Multi-story
        else:
            return 4  # High-rise
    
    def _encode_style(self, aesthetic: str) -> List[int]:
        """Encode house style into binary features"""
        styles = ['modern', 'traditional', 'contemporary', 'minimalist', 'luxury', 'eco_friendly']
        features = []
        
        aesthetic_lower = aesthetic.lower()
        for style in styles:
            features.append(1 if style in aesthetic_lower else 0)
        
        return features
    
    def _encode_rooms(self, rooms: List[str]) -> List[int]:
        """Encode room requirements into binary features"""
        room_types = [
            'master_bedroom', 'bedrooms', 'bathrooms', 'kitchen', 'living_room',
            'dining_room', 'study_room', 'prayer_room', 'guest_room', 'store_room',
            'balcony', 'terrace', 'garage', 'attached_bathroom'
        ]
        
        features = []
        for room_type in room_types:
            features.append(1 if room_type in rooms else 0)
        
        return features
    
    def _encode_location(self, location: str) -> List[int]:
        """Encode location preferences"""
        features = [
            1 if 'kerala' in location.lower() else 0,  # Kerala
            1 if any(word in location.lower() for word in ['city', 'urban', 'metro']) else 0,  # Urban
            1 if any(word in location.lower() for word in ['rural', 'village', 'town']) else 0  # Rural
        ]
        
        return features
    
    def train_model(self, n_neighbors: int = 5):
        """Train the KNN model"""
        logger.info("Starting model training...")
        
        # Get architects data
        architects_df = self.get_architects_data()
        if architects_df.empty:
            logger.error("No architects data found")
            return False
        
        # Preprocess architect features
        processed_df = self.preprocess_architect_features(architects_df)
        
        # Select features for training
        feature_columns = [
            'experience_years', 'rating', 'num_reviews', 'portfolio_count',
            'success_rate', 'response_time_hours', 'avg_price', 'specialty_encoded',
            'experience_level', 'rating_level', 'composite_score'
        ]
        
        # Add specializations features
        spec_columns = [col for col in processed_df.columns if col.startswith('has_')]
        feature_columns.extend(spec_columns)
        
        # Add location features
        location_columns = ['is_kerala', 'is_urban', 'is_rural']
        feature_columns.extend(location_columns)
        
        # Extract features
        self.architect_features = processed_df[feature_columns].fillna(0)
        
        # Scale features
        self.architect_features_scaled = self.scaler.fit_transform(self.architect_features)
        
        # Train KNN model
        self.knn_model = NearestNeighbors(
            n_neighbors=min(n_neighbors, len(processed_df)),
            metric='cosine',
            algorithm='auto'
        )
        
        self.knn_model.fit(self.architect_features_scaled)
        
        # Store architect metadata
        self.architect_metadata = processed_df[['id', 'name', 'email', 'specialty', 'location', 'rating', 'num_reviews']].copy()
        
        self.is_trained = True
        logger.info(f"Model trained successfully with {len(processed_df)} architects")
        
        return True
    
    def recommend_architects(self, project_data: Dict[str, Any], n_recommendations: int = 5) -> List[Dict[str, Any]]:
        """Recommend architects based on project data"""
        if not self.is_trained:
            logger.error("Model not trained. Please train the model first.")
            return []
        
        try:
            # Preprocess project features
            project_features = self.preprocess_project_features(project_data)
            
            # Scale project features using the same scaler
            project_features_scaled = self.scaler.transform(project_features)
            
            # Find nearest neighbors
            distances, indices = self.knn_model.kneighbors(project_features_scaled)
            
            # Get recommendations
            recommendations = []
            for i, (distance, idx) in enumerate(zip(distances[0], indices[0])):
                if i >= n_recommendations:
                    break
                
                architect_id = self.architect_metadata.iloc[idx]['id']
                architect_info = self.architect_metadata.iloc[idx].to_dict()
                
                # Calculate similarity score (1 - distance)
                similarity_score = 1 - distance
                
                # Get additional architect details
                conn = self.connect_db()
                if conn:
                    try:
                        detail_query = """
                        SELECT * FROM architects WHERE id = ?
                        """
                        detail_df = pd.read_sql_query(detail_query, conn, params=[architect_id])
                        if not detail_df.empty:
                            architect_details = detail_df.iloc[0].to_dict()
                            architect_info.update(architect_details)
                    except Exception as e:
                        logger.error(f"Error fetching architect details: {e}")
                    finally:
                        conn.close()
                
                recommendation = {
                    'architect_id': architect_id,
                    'name': architect_info.get('name', ''),
                    'email': architect_info.get('email', ''),
                    'specialty': architect_info.get('specialty', ''),
                    'location': architect_info.get('location', ''),
                    'rating': architect_info.get('rating', 0),
                    'num_reviews': architect_info.get('num_reviews', 0),
                    'experience_years': architect_info.get('experience_years', 0),
                    'price_range_min': architect_info.get('price_range_min', 0),
                    'price_range_max': architect_info.get('price_range_max', 0),
                    'specializations': architect_info.get('specializations', ''),
                    'portfolio_count': architect_info.get('portfolio_count', 0),
                    'success_rate': architect_info.get('success_rate', 0),
                    'response_time_hours': architect_info.get('response_time_hours', 24),
                    'similarity_score': round(similarity_score, 3),
                    'match_reason': self._generate_match_reason(project_data, architect_info, similarity_score)
                }
                
                recommendations.append(recommendation)
            
            logger.info(f"Generated {len(recommendations)} architect recommendations")
            return recommendations
            
        except Exception as e:
            logger.error(f"Error generating recommendations: {e}")
            return []
    
    def _generate_match_reason(self, project_data: Dict[str, Any], architect_info: Dict[str, Any], similarity_score: float) -> str:
        """Generate human-readable match reason"""
        reasons = []
        
        # Budget match
        project_budget = self._parse_budget_range(project_data.get('budget_range', ''))
        architect_min_price = architect_info.get('price_range_min', 0)
        architect_max_price = architect_info.get('price_range_max', 0)
        
        if architect_min_price <= project_budget <= architect_max_price:
            reasons.append("Budget range matches perfectly")
        elif project_budget >= architect_min_price:
            reasons.append("Within architect's price range")
        
        # Specialty match
        project_style = project_data.get('aesthetic', '').lower()
        architect_specialty = architect_info.get('specialty', '').lower()
        architect_specializations = architect_info.get('specializations', '').lower()
        
        if project_style in architect_specialty or project_style in architect_specializations:
            reasons.append(f"Specializes in {project_style} style")
        
        # Experience match
        experience_years = architect_info.get('experience_years', 0)
        if experience_years >= 10:
            reasons.append("Highly experienced architect")
        elif experience_years >= 5:
            reasons.append("Experienced architect")
        
        # Rating match
        rating = architect_info.get('rating', 0)
        if rating >= 4.5:
            reasons.append("Excellent rating")
        elif rating >= 4.0:
            reasons.append("Good rating")
        
        # Location match
        project_location = project_data.get('location', '').lower()
        architect_location = architect_info.get('location', '').lower()
        if 'kerala' in project_location and 'kerala' in architect_location:
            reasons.append("Local Kerala architect")
        
        if not reasons:
            reasons.append("Good overall match based on project requirements")
        
        return "; ".join(reasons)
    
    def get_architect_details(self, architect_id: int) -> Dict[str, Any]:
        """Get detailed information about a specific architect from existing users table"""
        conn = self.connect_db()
        if not conn:
            return {}
        
        try:
            query = """
            SELECT 
                u.id,
                CONCAT(u.first_name, ' ', u.last_name) as name,
                u.email,
                u.specialization as specialty,
                u.experience_years,
                u.city,
                u.state,
                u.location,
                u.company_name,
                u.portfolio,
                u.license,
                u.created_at,
                u.is_verified,
                u.status,
                COALESCE((SELECT ROUND(AVG(r.rating),2) FROM architect_reviews r WHERE r.architect_id = u.id), 0) as rating,
                COALESCE((SELECT COUNT(*) FROM architect_reviews r2 WHERE r2.architect_id = u.id), 0) as num_reviews,
                COALESCE((SELECT COUNT(*) FROM layout_request_assignments la WHERE la.architect_id = u.id), 0) as portfolio_count
            FROM users u
            WHERE u.id = %s AND u.role = 'architect' AND u.status = 'approved'
            """
            
            df = pd.read_sql(query, conn, params=[architect_id])
            
            if not df.empty:
                return df.iloc[0].to_dict()
            else:
                return {}
                
        except Exception as e:
            logger.error(f"Error fetching architect details: {e}")
            return {}
        finally:
            conn.close()
    
    def save_model(self, filepath: str):
        """Save the trained model"""
        if not self.is_trained:
            logger.error("No trained model to save")
            return False
        
        try:
            import joblib
            model_data = {
                'scaler': self.scaler,
                'knn_model': self.knn_model,
                'architect_features': self.architect_features,
                'architect_metadata': self.architect_metadata,
                'is_trained': self.is_trained
            }
            
            joblib.dump(model_data, filepath)
            logger.info(f"Model saved to {filepath}")
            return True
            
        except Exception as e:
            logger.error(f"Error saving model: {e}")
            return False
    
    def load_model(self, filepath: str):
        """Load a pre-trained model"""
        try:
            import joblib
            model_data = joblib.load(filepath)
            
            self.scaler = model_data['scaler']
            self.knn_model = model_data['knn_model']
            self.architect_features = model_data['architect_features']
            self.architect_metadata = model_data['architect_metadata']
            self.is_trained = model_data['is_trained']
            
            logger.info(f"Model loaded from {filepath}")
            return True
            
        except Exception as e:
            logger.error(f"Error loading model: {e}")
            return False


# API Functions for Flask integration
def initialize_recommendation_engine():
    """Initialize the recommendation engine"""
    engine = ArchitectRecommendationEngine()
    
    # Try to load pre-trained model first
    model_path = 'backend/models/architect_recommendation_model.pkl'
    if os.path.exists(model_path):
        if engine.load_model(model_path):
            logger.info("Pre-trained model loaded successfully")
            return engine
    
    # Train new model if no pre-trained model exists
    if engine.train_model():
        # Save the trained model
        os.makedirs(os.path.dirname(model_path), exist_ok=True)
        engine.save_model(model_path)
        logger.info("New model trained and saved")
        return engine
    
    logger.error("Failed to initialize recommendation engine")
    return None


def get_architect_recommendations(project_data: Dict[str, Any], n_recommendations: int = 5) -> List[Dict[str, Any]]:
    """Get architect recommendations for a project"""
    engine = initialize_recommendation_engine()
    if not engine:
        return []
    
    return engine.recommend_architects(project_data, n_recommendations)


if __name__ == "__main__":
    # Test the recommendation engine
    engine = ArchitectRecommendationEngine()
    
    # Train the model
    if engine.train_model():
        print("Model trained successfully!")
        
        # Test with sample project data
        sample_project = {
            'budget_range': '30-50 Lakhs',
            'plot_size': '10',
            'plot_unit': 'cents',
            'building_size': '2000',
            'num_floors': '2',
            'aesthetic': 'Modern',
            'rooms': ['master_bedroom', 'bedrooms', 'kitchen', 'living_room'],
            'location': 'Kerala'
        }
        
        recommendations = engine.recommend_architects(sample_project, 5)
        
        print(f"\nGenerated {len(recommendations)} recommendations:")
        for i, rec in enumerate(recommendations, 1):
            print(f"\n{i}. {rec['name']}")
            print(f"   Specialty: {rec['specialty']}")
            print(f"   Rating: {rec['rating']} ({rec['num_reviews']} reviews)")
            print(f"   Experience: {rec['experience_years']} years")
            print(f"   Similarity: {rec['similarity_score']}")
            print(f"   Reason: {rec['match_reason']}")
    else:
        print("Failed to train model")
