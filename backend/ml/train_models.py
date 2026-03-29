"""
Train construction risk models from the dataset.
Saves .pkl model files and updated model_metadata.json.
"""

import pandas as pd
import numpy as np
import joblib
import json
import os
from datetime import datetime
from sklearn.ensemble import GradientBoostingClassifier, RandomForestClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, f1_score
from sklearn.preprocessing import StandardScaler
from sklearn.pipeline import Pipeline
from sklearn.utils.class_weight import compute_sample_weight

BASE_DIR    = os.path.dirname(os.path.abspath(__file__))
DATA_DIR    = os.path.join(BASE_DIR, 'data')
MODELS_DIR  = os.path.join(BASE_DIR, 'models')
os.makedirs(MODELS_DIR, exist_ok=True)

COST_FEATURES = [
    'plot_size_sqft', 'building_size_sqft', 'num_floors', 'budget_amount',
    'budget_per_sqft', 'plot_shape_code', 'topography_code', 'num_bedrooms',
    'num_bathrooms', 'total_rooms', 'design_style_code', 'customization_level',
    'design_complexity_score', 'development_constraint_level',
    'kerala_district_code', 'construction_start_month',
    'monsoon_exposure_score', 'district_risk_tier',
    'terrain_code', 'rainfall_code', 'flood_risk_code', 'effective_monsoon_score',
]
TIME_FEATURES = [
    'plot_size_sqft', 'building_size_sqft', 'num_floors', 'planned_duration_months',
    'plot_shape_code', 'topography_code', 'design_complexity_score',
    'customization_level', 'site_difficulty_score',
    'kerala_district_code', 'construction_start_month',
    'monsoon_exposure_score', 'district_risk_tier',
    'terrain_code', 'rainfall_code', 'flood_risk_code', 'effective_monsoon_score',
]


def train_and_save(X, y, feature_names, model_name_prefix, label_col):
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )

    # Three-model comparison: LogReg (baseline), RF, GB
    # RF selected for deployment due to SHAP TreeExplainer compatibility.
    # All results saved to metadata for paper reporting.
    candidates = {
        'logistic_regression': Pipeline([
            ('scaler', StandardScaler()),
            ('clf', LogisticRegression(max_iter=1000, class_weight='balanced', random_state=42))
        ]),
        'random_forest': RandomForestClassifier(
            n_estimators=300, max_depth=10, random_state=42, class_weight='balanced', n_jobs=-1
        ),
        'gradient_boosting': GradientBoostingClassifier(
            n_estimators=200, max_depth=5, random_state=42
        ),
    }

    best_model  = None
    best_score  = -1
    best_name   = ''
    all_results = {}

    for name, clf in candidates.items():
        clf.fit(X_train, y_train)
        y_pred = clf.predict(X_test)
        report  = classification_report(y_test, y_pred, output_dict=True)
        f1_high = report.get('2', {}).get('f1-score', 0)
        f1_all  = f1_score(y_test, y_pred, average='weighted')

        all_results[name] = {
            'f1_high_risk': f1_high,
            'f1_overall':   f1_all,
            'classification_report': report
        }
        print(f"  {name}: f1_high={f1_high:.4f}  f1_overall={f1_all:.4f}")

        if f1_high > best_score:
            best_score = f1_high
            best_model = clf
            best_name  = name

    # Always deploy Random Forest for SHAP TreeExplainer compatibility.
    # GB outperforms RF slightly but RF is the safer deployment choice.
    # All comparison results are stored in metadata for paper reporting.
    rf_model = candidates['random_forest']
    model_path = os.path.join(MODELS_DIR, f'{model_name_prefix}_model.pkl')
    joblib.dump(rf_model, model_path)
    print(f"  Saved: {model_path}  (deployed=random_forest, best_f1={best_name})")

    # Feature importance from RF
    if hasattr(rf_model, 'feature_importances_'):
        importance = dict(zip(feature_names, rf_model.feature_importances_))
        importance = dict(sorted(importance.items(), key=lambda x: x[1], reverse=True))
    else:
        importance = {}

    return {
        'best_model':  best_name,
        'best_score':  best_score,
        'all_results': all_results,
        'importance':  importance
    }

def main():
    # --- Cost overrun ---
    print("Training cost overrun model...")
    cost_df = pd.read_csv(os.path.join(DATA_DIR, 'cost_overrun_risk_dataset.csv'))
    X_cost  = cost_df[COST_FEATURES].values
    y_cost  = cost_df['cost_overrun_risk'].values
    cost_res = train_and_save(X_cost, y_cost, COST_FEATURES, 'cost_overrun_risk', 'cost_overrun_risk')

    # --- Time delay ---
    print("Training time delay model...")
    time_df = pd.read_csv(os.path.join(DATA_DIR, 'time_delay_risk_dataset.csv'))
    X_time  = time_df[TIME_FEATURES].values
    y_time  = time_df['time_delay_risk'].values
    time_res = train_and_save(X_time, y_time, TIME_FEATURES, 'time_delay_risk', 'time_delay_risk')

    # --- Save metadata ---
    metadata = {
        'timestamp': datetime.now().isoformat(),
        'cost_features':  COST_FEATURES,
        'time_features':  TIME_FEATURES,
        'risk_levels':    {'0': 'Low', '1': 'Medium', '2': 'High'},
        'feature_importance': {
            'cost_overrun': cost_res['importance'],
            'time_delay':   time_res['importance'],
        },
        'performance_metrics': {
            'cost_overrun': {
                'best_model':  cost_res['best_model'],
                'best_score':  cost_res['best_score'],
                'all_results': cost_res['all_results'],
            },
            'time_delay': {
                'best_model':  time_res['best_model'],
                'best_score':  time_res['best_score'],
                'all_results': time_res['all_results'],
            },
        }
    }

    meta_path = os.path.join(MODELS_DIR, 'model_metadata.json')
    with open(meta_path, 'w') as f:
        json.dump(metadata, f, indent=2)

    # Update current_model.json
    config = {
        'cost_model': 'cost_overrun_risk_model.pkl',
        'time_model': 'time_delay_risk_model.pkl',
        'version':    'v6-panchayat'
    }
    with open(os.path.join(MODELS_DIR, 'current_model.json'), 'w') as f:
        json.dump(config, f, indent=2)

    print("\nDone. Models and metadata saved.")
    print(f"  Cost best f1_high: {cost_res['best_score']:.4f}")
    print(f"  Time best f1_high: {time_res['best_score']:.4f}")


if __name__ == '__main__':
    main()
