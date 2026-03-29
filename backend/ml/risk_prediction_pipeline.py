"""
Machine Learning-Driven Decision Support Framework for Managing Cost and Time Overrun Risks
in Residential Construction Projects

This module implements a comprehensive ML pipeline for predicting cost overrun and time delay risks
at the planning stage of residential construction projects.
"""

import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split, StratifiedKFold, cross_val_score
from sklearn.linear_model import LogisticRegression
from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.metrics import classification_report, confusion_matrix, f1_score, recall_score
import joblib
import os
import json
from datetime import datetime
import warnings
warnings.filterwarnings('ignore')

try:
    import shap
    SHAP_AVAILABLE = True
except ImportError:
    SHAP_AVAILABLE = False
    print("⚠️  SHAP not installed. Run: pip install shap>=0.44.0")

class RiskPredictionPipeline:
    """
    A comprehensive ML pipeline for predicting cost overrun and time delay risks
    in residential construction projects.
    """
    
    def __init__(self, data_dir='data', models_dir='models'):
        self.data_dir = data_dir
        self.models_dir = models_dir
        
        # Ensure models directory exists
        os.makedirs(self.models_dir, exist_ok=True)
        
        # Risk level mappings
        self.risk_levels = {0: 'Low', 1: 'Medium', 2: 'High'}
        
        # Feature importance storage
        self.feature_importance = {}
        
        # Model performance metrics
        self.performance_metrics = {}
    
    def get_fresh_models(self):
        """Get fresh model instances for training."""
        return {
            'logistic_regression': LogisticRegression(random_state=42, max_iter=1000),
            'random_forest': RandomForestClassifier(
                n_estimators=100, 
                random_state=42, 
                max_depth=10,
                min_samples_split=5,
                min_samples_leaf=2
            ),
            'gradient_boosting': GradientBoostingClassifier(
                n_estimators=100,
                random_state=42,
                max_depth=6,
                learning_rate=0.1
            )
        }
    
    def load_datasets(self):
        """Load the frozen datasets for cost overrun and time delay risks."""
        try:
            # Load cost overrun dataset
            cost_path = os.path.join(self.data_dir, 'cost_overrun_risk_dataset.csv')
            self.cost_data = pd.read_csv(cost_path)
            print(f"✓ Loaded cost overrun dataset: {self.cost_data.shape}")
            
            # Load time delay dataset
            time_path = os.path.join(self.data_dir, 'time_delay_risk_dataset.csv')
            self.time_data = pd.read_csv(time_path)
            print(f"✓ Loaded time delay dataset: {self.time_data.shape}")
            
            return True
            
        except Exception as e:
            print(f"❌ Error loading datasets: {str(e)}")
            return False
    
    def prepare_features(self, data, target_column):
        """Prepare features and target variables for training."""
        # Separate features and target
        X = data.drop(columns=[target_column])
        y = data[target_column]
        
        # Store feature names
        feature_names = X.columns.tolist()
        
        return X, y, feature_names
    
    def train_models(self, X, y, risk_type, feature_names):
        """Train multiple models and select the best performing one."""
        print(f"\n🔄 Training models for {risk_type} risk prediction...")
        
        # Stratified train-test split
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42, stratify=y
        )
        
        # Scale features for logistic regression
        scaler = StandardScaler()
        X_train_scaled = scaler.fit_transform(X_train)
        X_test_scaled = scaler.transform(X_test)
        
        best_model = None
        best_score = 0
        best_model_name = ""
        model_results = {}
        
        # Get fresh model instances for this training session
        models = self.get_fresh_models()
        
        for model_name, model in models.items():
            print(f"  Training {model_name}...")
            
            # Use scaled data for logistic regression, original for tree-based models
            if model_name == 'logistic_regression':
                X_train_model = X_train_scaled
                X_test_model = X_test_scaled
            else:
                X_train_model = X_train
                X_test_model = X_test
            
            # Train model
            model.fit(X_train_model, y_train)
            
            # Predict on test set
            y_pred = model.predict(X_test_model)
            
            # Calculate metrics (focus on high-risk class recall and F1-score)
            high_risk_class = 2  # High risk is class 2
            f1_high = f1_score(y_test, y_pred, labels=[high_risk_class], average='macro')
            recall_high = recall_score(y_test, y_pred, labels=[high_risk_class], average='macro')
            
            # Overall F1 score
            f1_overall = f1_score(y_test, y_pred, average='weighted')
            
            # Store results
            model_results[model_name] = {
                'f1_high_risk': f1_high,
                'recall_high_risk': recall_high,
                'f1_overall': f1_overall,
                'classification_report': classification_report(y_test, y_pred, output_dict=True)
            }
            
            print(f"    F1-score (High Risk): {f1_high:.3f}")
            print(f"    Recall (High Risk): {recall_high:.3f}")
            print(f"    F1-score (Overall): {f1_overall:.3f}")
            
            # Select best model based on high-risk F1 score
            if f1_high > best_score:
                best_score = f1_high
                best_model = model
                best_model_name = model_name
        
        print(f"\n✓ Best model for {risk_type}: {best_model_name} (F1-High: {best_score:.3f})")
        
        # Store performance metrics
        self.performance_metrics[risk_type] = {
            'best_model': best_model_name,
            'best_score': best_score,
            'all_results': model_results
        }
        
        # Extract sklearn feature importance (fallback / comparison)
        if hasattr(best_model, 'feature_importances_'):
            importance = best_model.feature_importances_
        elif hasattr(best_model, 'coef_'):
            importance = np.abs(best_model.coef_[0]) if len(best_model.coef_.shape) > 1 else np.abs(best_model.coef_)
        else:
            importance = np.zeros(len(feature_names))

        feature_importance = dict(zip(feature_names, importance))
        feature_importance = dict(sorted(feature_importance.items(), key=lambda x: x[1], reverse=True))
        self.feature_importance[risk_type] = feature_importance

        # ── SHAP global explanation ───────────────────────────────────────────
        if SHAP_AVAILABLE:
            X_bg = X_train_scaled if best_model_name == 'logistic_regression' else X_train
            shap_importance = self._compute_shap_importance(best_model, best_model_name, X_bg, feature_names)
            if shap_importance:
                self.feature_importance[f"{risk_type}_shap"] = shap_importance
                print(f"  ✓ SHAP global importance computed for {risk_type}")

        return best_model, scaler if best_model_name == 'logistic_regression' else None

    def _compute_shap_importance(self, model, model_name, X_background, feature_names):
        """
        Compute mean |SHAP| values as global feature importance.
        Uses TreeExplainer for tree models, LinearExplainer for logistic regression.
        Returns a sorted dict {feature: mean_abs_shap}.
        """
        try:
            bg = X_background[:500] if not hasattr(X_background, 'iloc') else X_background.iloc[:500]
            if model_name in ('random_forest', 'gradient_boosting'):
                explainer = shap.TreeExplainer(model)
                shap_values = explainer.shap_values(bg)
            else:
                explainer = shap.LinearExplainer(model, bg)
                shap_values = explainer.shap_values(bg)

            # Multi-class: shap_values is list[n_classes]; average across classes
            if isinstance(shap_values, list):
                mean_abs = np.mean([np.abs(sv).mean(axis=0) for sv in shap_values], axis=0)
            else:
                mean_abs = np.abs(shap_values).mean(axis=0)

            result = dict(zip(feature_names, mean_abs.tolist()))
            return dict(sorted(result.items(), key=lambda x: x[1], reverse=True))
        except Exception as e:
            print(f"  ⚠️  SHAP computation failed: {e}")
            return {}
    
    def save_models(self, cost_model, cost_scaler, time_model, time_scaler):
        """Save the best performing models and associated components."""
        try:
            # Save cost overrun model
            cost_model_path = os.path.join(self.models_dir, 'cost_overrun_risk_model.pkl')
            joblib.dump(cost_model, cost_model_path)
            print(f"✓ Saved cost overrun model: {cost_model_path}")
            
            # Save cost scaler if exists
            if cost_scaler is not None:
                cost_scaler_path = os.path.join(self.models_dir, 'cost_overrun_scaler.pkl')
                joblib.dump(cost_scaler, cost_scaler_path)
                print(f"✓ Saved cost overrun scaler: {cost_scaler_path}")
            
            # Save time delay model
            time_model_path = os.path.join(self.models_dir, 'time_delay_risk_model.pkl')
            joblib.dump(time_model, time_model_path)
            print(f"✓ Saved time delay model: {time_model_path}")
            
            # Save time scaler if exists
            if time_scaler is not None:
                time_scaler_path = os.path.join(self.models_dir, 'time_delay_scaler.pkl')
                joblib.dump(time_scaler, time_scaler_path)
                print(f"✓ Saved time delay scaler: {time_scaler_path}")
            
            # Save feature importance and metadata
            metadata = {
                'timestamp': datetime.now().isoformat(),
                'feature_importance': self.feature_importance,
                'performance_metrics': self.performance_metrics,
                'risk_levels': self.risk_levels,
                'cost_features': self.cost_features,
                'time_features': self.time_features
            }
            
            metadata_path = os.path.join(self.models_dir, 'model_metadata.json')
            with open(metadata_path, 'w') as f:
                json.dump(metadata, f, indent=2)
            print(f"✓ Saved model metadata: {metadata_path}")
            
            return True
            
        except Exception as e:
            print(f"❌ Error saving models: {str(e)}")
            return False
    
    def run_complete_pipeline(self):
        """Execute the complete ML pipeline for both risk types."""
        print("=" * 80)
        print("🚀 MACHINE LEARNING-DRIVEN DECISION SUPPORT FRAMEWORK")
        print("   Managing Cost and Time Overrun Risks in Residential Construction")
        print("=" * 80)
        
        # Step 1: Load datasets
        print("\n📊 Step 1: Loading Datasets")
        if not self.load_datasets():
            return False
        
        # Step 2: Prepare cost overrun features
        print("\n🔧 Step 2: Preparing Cost Overrun Features")
        X_cost, y_cost, self.cost_features = self.prepare_features(
            self.cost_data, 'cost_overrun_risk'
        )
        print(f"✓ Cost features: {self.cost_features}")
        print(f"✓ Cost risk distribution: {dict(y_cost.value_counts().sort_index())}")
        
        # Step 3: Prepare time delay features
        print("\n🔧 Step 3: Preparing Time Delay Features")
        X_time, y_time, self.time_features = self.prepare_features(
            self.time_data, 'time_delay_risk'
        )
        print(f"✓ Time features: {self.time_features}")
        print(f"✓ Time risk distribution: {dict(y_time.value_counts().sort_index())}")
        
        # Step 4: Train cost overrun models
        print("\n🤖 Step 4: Training Cost Overrun Risk Models")
        cost_model, cost_scaler = self.train_models(
            X_cost, y_cost, 'cost_overrun', self.cost_features
        )
        
        # Step 5: Train time delay models
        print("\n🤖 Step 5: Training Time Delay Risk Models")
        time_model, time_scaler = self.train_models(
            X_time, y_time, 'time_delay', self.time_features
        )
        
        # Step 6: Save models
        print("\n💾 Step 6: Saving Models")
        if self.save_models(cost_model, cost_scaler, time_model, time_scaler):
            print("\n✅ Pipeline completed successfully!")
            
            # Display feature importance summary
            self.display_feature_importance_summary()
            
            return True
        else:
            print("\n❌ Pipeline failed during model saving!")
            return False
    
    def display_feature_importance_summary(self):
        """Display a summary of feature importance for both risk types."""
        print("\n" + "=" * 60)
        print("📈 FEATURE IMPORTANCE ANALYSIS")
        print("=" * 60)
        
        for risk_type, importance in self.feature_importance.items():
            print(f"\n🔍 {risk_type.replace('_', ' ').title()} Risk - Top Contributing Factors:")
            
            # Get top 5 features
            top_features = list(importance.items())[:5]
            
            for i, (feature, score) in enumerate(top_features, 1):
                # Format feature name
                feature_display = feature.replace('_', ' ').title()
                print(f"  {i}. {feature_display}: {score:.3f}")
        
        print("\n" + "=" * 60)

if __name__ == "__main__":
    # Initialize and run the pipeline
    pipeline = RiskPredictionPipeline()
    success = pipeline.run_complete_pipeline()
    
    if success:
        print("\n🎉 ML models are ready for integration!")
        print("   Models saved in: backend/ml/models/")
        print("   Next: Integrate with Homeowner Custom Request flow")
    else:
        print("\n💥 Pipeline execution failed!")
        print("   Please check the error messages above and try again.")