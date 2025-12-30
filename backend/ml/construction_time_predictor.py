"""
Backpropagation Neural Network (BPNN) for Construction Project Completion Time Prediction
"""

import numpy as np
import json
import os
import pickle
from datetime import datetime
import warnings
warnings.filterwarnings('ignore')


class BPNN:
    """
    Backpropagation Neural Network for predicting construction completion time
    """
    
    def __init__(self, input_size=10, hidden_size=15, output_size=1, learning_rate=0.01):
        self.input_size = input_size
        self.hidden_size = hidden_size
        self.output_size = output_size
        self.learning_rate = learning_rate
        
        # Initialize weights and biases with Xavier initialization
        self.W1 = np.random.randn(self.input_size, self.hidden_size) * np.sqrt(1.0 / self.input_size)
        self.b1 = np.zeros((1, self.hidden_size))
        
        self.W2 = np.random.randn(self.hidden_size, self.output_size) * np.sqrt(1.0 / self.hidden_size)
        self.b2 = np.zeros((1, self.output_size))
        
    def sigmoid(self, x):
        """Sigmoid activation function"""
        return 1 / (1 + np.exp(-np.clip(x, -500, 500)))
    
    def sigmoid_derivative(self, x):
        """Derivative of sigmoid function"""
        s = self.sigmoid(x)
        return s * (1 - s)
    
    def forward(self, X):
        """Forward propagation"""
        self.z1 = np.dot(X, self.W1) + self.b1
        self.a1 = self.sigmoid(self.z1)
        
        self.z2 = np.dot(self.a1, self.W2) + self.b2
        self.a2 = self.sigmoid(self.z2)
        
        return self.a2
    
    def backward(self, X, y, output):
        """Backward propagation"""
        m = X.shape[0]
        
        # Output layer error
        dz2 = output - y
        dW2 = (1/m) * np.dot(self.a1.T, dz2)
        db2 = (1/m) * np.sum(dz2, axis=0, keepdims=True)
        
        # Hidden layer error
        dz1 = np.dot(dz2, self.W2.T) * self.sigmoid_derivative(self.z1)
        dW1 = (1/m) * np.dot(X.T, dz1)
        db1 = (1/m) * np.sum(dz1, axis=0, keepdims=True)
        
        # Update weights
        self.W2 -= self.learning_rate * dW2
        self.b2 -= self.learning_rate * db2
        self.W1 -= self.learning_rate * dW1
        self.b1 -= self.learning_rate * db1
    
    def train(self, X, y, epochs=1000, batch_size=32, verbose=True):
        """Train the network"""
        m = len(X)
        
        for epoch in range(epochs):
            # Mini-batch training
            for i in range(0, m, batch_size):
                X_batch = X[i:i+batch_size]
                y_batch = y[i:i+batch_size]
                
                # Forward pass
                output = self.forward(X_batch)
                
                # Backward pass
                self.backward(X_batch, y_batch, output)
            
            # Print progress every 100 epochs
            if epoch % 100 == 0 or epoch == epochs - 1:
                cost = self.compute_cost(X, y)
                if verbose:
                    print(f"Epoch {epoch}, Cost: {cost:.4f}")
    
    def compute_cost(self, X, y):
        """Compute cost"""
        output = self.forward(X)
        m = y.shape[0]
        cost = (1/(2*m)) * np.sum(np.square(output - y))
        return cost
    
    def predict(self, X):
        """Predict output"""
        output = self.forward(X)
        return output
    
    def save_model(self, filepath):
        """Save model to file"""
        model_data = {
            'W1': self.W1,
            'b1': self.b1,
            'W2': self.W2,
            'b2': self.b2,
            'input_size': self.input_size,
            'hidden_size': self.hidden_size,
            'output_size': self.output_size,
            'learning_rate': self.learning_rate
        }
        
        with open(filepath, 'wb') as f:
            pickle.dump(model_data, f)
        print(f"Model saved to {filepath}")
    
    def load_model(self, filepath):
        """Load model from file"""
        with open(filepath, 'rb') as f:
            model_data = pickle.load(f)
        
        self.W1 = model_data['W1']
        self.b1 = model_data['b1']
        self.W2 = model_data['W2']
        self.b2 = model_data['b2']
        self.input_size = model_data['input_size']
        self.hidden_size = model_data['hidden_size']
        self.output_size = model_data['output_size']
        self.learning_rate = model_data['learning_rate']
        
        print(f"Model loaded from {filepath}")


class ConstructionTimePredictor:
    """
    Wrapper class for construction time prediction
    """
    
    def __init__(self, model_path='backend/ml/construction_model.pkl'):
        self.model_path = model_path
        self.model = None
        self.scaler = None
        
        # Training parameters (normalized features)
        self.feature_ranges = {
            'plot_size': (500, 5000),          # sq.ft
            'building_size': (400, 4500),       # sq.ft
            'floors': (1, 4),
            'bedrooms': (1, 6),
            'bathrooms': (1, 5),
            'kitchen_rooms': (1, 2),
            'parking': (0, 6),
            'terrace': (0, 1),                  # 0 or 1
            'basement': (0, 1),                 # 0 or 1
            'complexity': (0, 10)               # 0-10 scale
        }
        
        # Target range (months)
        self.target_range = (3, 24)  # 3 to 24 months
        
    def normalize_features(self, features):
        """Normalize features to 0-1 range"""
        normalized = []
        feature_keys = ['plot_size', 'building_size', 'floors', 'bedrooms', 'bathrooms', 
                       'kitchen_rooms', 'parking', 'terrace', 'basement', 'complexity']
        
        for i, key in enumerate(feature_keys):
            if key in self.feature_ranges:
                min_val, max_val = self.feature_ranges[key]
                val = features[i] if i < len(features) else min_val
                # Normalize to 0-1 range
                normalized_val = (val - min_val) / (max_val - min_val)
                # Clamp to 0-1 range
                normalized_val = max(0, min(1, normalized_val))
                normalized.append(normalized_val)
            else:
                normalized.append(0.5)  # Default middle value
        
        return np.array(normalized).reshape(1, -1)
    
    def denormalize_target(self, normalized_time):
        """Convert normalized time back to months"""
        min_time, max_time = self.target_range
        return normalized_time * (max_time - min_time) + min_time
    
    def prepare_training_data(self):
        """
        Generate synthetic training data based on construction project patterns
        This simulates real-world construction projects
        """
        np.random.seed(42)
        num_samples = 500
        
        # Generate training data
        X_train = []
        y_train = []
        
        for _ in range(num_samples):
            # Generate random features
            plot_size = np.random.uniform(500, 5000)
            building_size = np.random.uniform(400, 4500)
            floors = np.random.randint(1, 5)
            bedrooms = np.random.randint(1, 7)
            bathrooms = np.random.randint(1, 6)
            kitchen_rooms = np.random.randint(1, 3)
            parking = np.random.randint(0, 7)
            terrace = np.random.choice([0, 1])
            basement = np.random.choice([0, 1])
            complexity = np.random.uniform(0, 10)
            
            # Calculate expected completion time (months)
            # Factors affecting construction time:
            base_time = 3.0
            size_factor = (building_size / 1000) * 1.5
            floor_factor = (floors - 1) * 2.5
            room_factor = ((bedrooms + bathrooms) / 2) * 0.5
            complexity_factor = complexity * 0.3
            basement_factor = basement * 2.0
            
            completion_time = base_time + size_factor + floor_factor + room_factor + complexity_factor + basement_factor
            
            # Add some noise
            noise = np.random.normal(0, 0.5)
            completion_time += noise
            
            # Clamp to reasonable range
            completion_time = max(3, min(24, completion_time))
            
            # Create feature vector
            features = [plot_size, building_size, floors, bedrooms, bathrooms, 
                       kitchen_rooms, parking, terrace, basement, complexity]
            
            # Normalize features
            normalized_features = self.normalize_features(features)
            X_train.append(normalized_features.flatten())
            
            # Normalize target
            normalized_time = (completion_time - self.target_range[0]) / (self.target_range[1] - self.target_range[0])
            y_train.append(normalized_time)
        
        return np.array(X_train), np.array(y_train).reshape(-1, 1)
    
    def train_model(self):
        """Train the BPNN model"""
        print("Training Backpropagation Neural Network...")
        print("=" * 50)
        
        # Prepare training data
        X_train, y_train = self.prepare_training_data()
        print(f"Training samples: {len(X_train)}")
        print(f"Input features: {X_train.shape[1]}")
        
        # Create and train model
        self.model = BPNN(input_size=10, hidden_size=20, output_size=1, learning_rate=0.01)
        self.model.train(X_train, y_train, epochs=2000, batch_size=50, verbose=True)
        
        # Test accuracy
        predictions = self.model.predict(X_train)
        predictions_denorm = self.denormalize_target(predictions)
        y_actual = self.denormalize_target(y_train)
        
        mse = np.mean((predictions_denorm - y_actual) ** 2)
        mae = np.mean(np.abs(predictions_denorm - y_actual))
        
        print("\n" + "=" * 50)
        print("Training Complete!")
        print(f"Mean Squared Error: {mse:.2f} months²")
        print(f"Mean Absolute Error: {mae:.2f} months")
        print("=" * 50)
        
        # Save model
        if not os.path.exists('backend/ml'):
            os.makedirs('backend/ml')
        
        self.model.save_model(self.model_path)
        
        return self.model
    
    def load_model(self):
        """Load trained model"""
        if os.path.exists(self.model_path):
            self.model = BPNN()
            self.model.load_model(self.model_path)
            print(f"Model loaded from {self.model_path}")
            return True
        else:
            print(f"Model not found at {self.model_path}. Please train the model first.")
            return False
    
    def predict(self, features):
        """
        Predict construction completion time
        
        Args:
            features: dict with keys: plot_size, building_size, floors, bedrooms, 
                     bathrooms, kitchen_rooms, parking, terrace, basement, complexity
        
        Returns:
            Predicted completion time in months
        """
        if self.model is None:
            if not self.load_model():
                # Train model if not available
                print("Model not found, training new model...")
                self.train_model()
        
        # Extract features in correct order
        feature_list = [
            features.get('plot_size', 2800),
            features.get('building_size', 2800),
            features.get('floors', 1),
            features.get('bedrooms', 2),
            features.get('bathrooms', 2),
            features.get('kitchen_rooms', 1),
            features.get('parking', 2),
            features.get('terrace', 0),
            features.get('basement', 0),
            features.get('complexity', 5)
        ]
        
        # Normalize input
        normalized_input = self.normalize_features(feature_list)
        
        # Predict
        prediction = self.model.predict(normalized_input)
        
        # Denormalize output
        completion_months = self.denormalize_target(prediction[0][0])
        
        # Ensure reasonable bounds
        completion_months = max(3, min(24, completion_months))
        
        return completion_months


def main():
    """Main function to train and test the model"""
    predictor = ConstructionTimePredictor()
    
    # Train the model
    predictor.train_model()
    
    # Test prediction
    test_features = {
        'plot_size': 2800,
        'building_size': 2800,
        'floors': 1,
        'bedrooms': 3,
        'bathrooms': 2,
        'kitchen_rooms': 1,
        'parking': 2,
        'terrace': 1,
        'basement': 0,
        'complexity': 6
    }
    
    predicted_time = predictor.predict(test_features)
    print(f"\nTest Prediction:")
    print(f"Features: {test_features}")
    print(f"Predicted completion time: {predicted_time:.2f} months")


if __name__ == "__main__":
    main()


