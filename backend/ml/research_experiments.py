"""
BuildHub Research Experiments — IEEE Paper Strengthening
=========================================================
Implements all missing research-grade evaluations:
  1. 5-fold stratified cross-validation (cost + time)
  2. Full baseline comparison: LogReg, RandomForest, GradientBoosting
  3. Per-class precision, recall, F1, weighted F1, accuracy
  4. Confusion matrices (saved as PNG)
  5. Class imbalance experiments: class_weight='balanced' vs none
  6. Hyperparameter tuning: RandomizedSearchCV on RandomForest
  7. Ablation study: Rule-only / ML-only / Hybrid-fixed / Hybrid-adaptive
  8. Blend-weight sensitivity analysis (3 weight settings)
  9. All results exported to backend/ml/results/ for paper use

Run:  python backend/ml/research_experiments.py
Outputs: backend/ml/results/  (CSV tables + PNG figures)
"""

import os, json, warnings
import numpy as np
import pandas as pd
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import seaborn as sns

from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.model_selection import (
    StratifiedKFold, cross_validate, RandomizedSearchCV, train_test_split
)
from sklearn.metrics import (
    classification_report, confusion_matrix,
    f1_score, accuracy_score, precision_score, recall_score
)
from sklearn.preprocessing import StandardScaler
from sklearn.pipeline import Pipeline

warnings.filterwarnings('ignore')
np.random.seed(42)

# ── Paths ─────────────────────────────────────────────────────────────────────
BASE   = os.path.dirname(os.path.abspath(__file__))
DATA   = os.path.join(BASE, 'data')
OUT    = os.path.join(BASE, 'results')
os.makedirs(OUT, exist_ok=True)

COST_FEATURES = [
    'plot_size_sqft','building_size_sqft','num_floors','budget_amount',
    'budget_per_sqft','plot_shape_code','topography_code','num_bedrooms',
    'num_bathrooms','total_rooms','design_style_code','customization_level',
    'design_complexity_score','development_constraint_level',
    'kerala_district_code','construction_start_month',
    'monsoon_exposure_score','district_risk_tier',
    'terrain_code','rainfall_code','flood_risk_code','effective_monsoon_score',
]
TIME_FEATURES = [
    'plot_size_sqft','building_size_sqft','num_floors','planned_duration_months',
    'plot_shape_code','topography_code','design_complexity_score',
    'customization_level','site_difficulty_score',
    'kerala_district_code','construction_start_month',
    'monsoon_exposure_score','district_risk_tier',
    'terrain_code','rainfall_code','flood_risk_code','effective_monsoon_score',
]
CLASS_NAMES = ['Low', 'Medium', 'High']


# ── Data loading ──────────────────────────────────────────────────────────────
def load_data():
    cost_df = pd.read_csv(os.path.join(DATA, 'cost_overrun_risk_dataset.csv'))
    time_df = pd.read_csv(os.path.join(DATA, 'time_delay_risk_dataset.csv'))
    X_cost = cost_df[COST_FEATURES].values
    y_cost = cost_df['cost_overrun_risk'].values
    X_time = time_df[TIME_FEATURES].values
    y_time = time_df['time_delay_risk'].values
    return X_cost, y_cost, X_time, y_time


# ── Helpers ───────────────────────────────────────────────────────────────────
def metrics_dict(y_true, y_pred, label=''):
    """Return a flat dict of per-class + aggregate metrics.
    Handles subsets where not all 3 classes are present."""
    present = sorted(set(y_true) | set(y_pred))
    names   = [CLASS_NAMES[i] for i in present]
    report  = classification_report(y_true, y_pred, labels=present,
                                    target_names=names,
                                    output_dict=True, zero_division=0)
    row = {'model': label}
    for cls in CLASS_NAMES:
        row[f'P_{cls}']  = round(report.get(cls, {}).get('precision', 0.0), 4)
        row[f'R_{cls}']  = round(report.get(cls, {}).get('recall',    0.0), 4)
        row[f'F1_{cls}'] = round(report.get(cls, {}).get('f1-score',  0.0), 4)
    row['Weighted_F1'] = round(report['weighted avg']['f1-score'], 4)
    row['Accuracy']    = round(accuracy_score(y_true, y_pred), 4)
    return row


def save_confusion_matrix(y_true, y_pred, title, filename):
    cm = confusion_matrix(y_true, y_pred)
    fig, ax = plt.subplots(figsize=(5, 4))
    sns.heatmap(cm, annot=True, fmt='d', cmap='Blues',
                xticklabels=CLASS_NAMES, yticklabels=CLASS_NAMES, ax=ax)
    ax.set_xlabel('Predicted'); ax.set_ylabel('Actual')
    ax.set_title(title)
    plt.tight_layout()
    fig.savefig(os.path.join(OUT, filename), dpi=150)
    plt.close(fig)
    print(f"  Saved: {filename}")


def cv_summary(cv_results, label):
    """Summarise cross_validate output into a single-row dict."""
    return {
        'model': label,
        'CV_F1_mean':  round(np.mean(cv_results['test_f1_weighted']), 4),
        'CV_F1_std':   round(np.std( cv_results['test_f1_weighted']), 4),
        'CV_Acc_mean': round(np.mean(cv_results['test_accuracy']),    4),
        'CV_Acc_std':  round(np.std( cv_results['test_accuracy']),    4),
    }


# ═══════════════════════════════════════════════════════════════════════════════
# EXPERIMENT 1 — Baseline comparison + per-class metrics + confusion matrices
# ═══════════════════════════════════════════════════════════════════════════════
def experiment_baselines(X, y, task_name):
    """
    Train LogReg, RF, GB on 80/20 split.
    Returns per-class metrics table and saves confusion matrices.
    """
    print(f"\n[EXP 1] Baseline comparison — {task_name}")
    X_tr, X_te, y_tr, y_te = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y)

    scaler = StandardScaler()
    X_tr_s = scaler.fit_transform(X_tr)
    X_te_s = scaler.transform(X_te)

    models = {
        'LogisticRegression':  LogisticRegression(max_iter=1000, class_weight='balanced',
                                                   random_state=42),
        'RandomForest':        RandomForestClassifier(n_estimators=300, max_depth=10,
                                                      class_weight='balanced',
                                                      random_state=42, n_jobs=-1),
        'GradientBoosting':    GradientBoostingClassifier(n_estimators=200, max_depth=5,
                                                          random_state=42),
    }

    rows = []
    for name, clf in models.items():
        # LR needs scaled features; tree models work on raw
        if name == 'LogisticRegression':
            clf.fit(X_tr_s, y_tr)
            y_pred = clf.predict(X_te_s)
        else:
            clf.fit(X_tr, y_tr)
            y_pred = clf.predict(X_te)

        row = metrics_dict(y_te, y_pred, label=name)
        rows.append(row)
        save_confusion_matrix(y_te, y_pred,
                              f'{task_name} — {name}',
                              f'cm_{task_name.lower().replace(" ","_")}_{name.lower()}.png')
        print(f"  {name}: Acc={row['Accuracy']:.4f}  WF1={row['Weighted_F1']:.4f}  "
              f"F1_High={row['F1_High']:.4f}")

    df = pd.DataFrame(rows)
    out_path = os.path.join(OUT, f'baseline_metrics_{task_name.lower().replace(" ","_")}.csv')
    df.to_csv(out_path, index=False)
    print(f"  Saved: {os.path.basename(out_path)}")
    return df


# ═══════════════════════════════════════════════════════════════════════════════
# EXPERIMENT 2 — 5-fold stratified cross-validation
# ═══════════════════════════════════════════════════════════════════════════════
def experiment_crossval(X, y, task_name):
    """
    5-fold stratified CV for RF (primary model) and GB.
    Reports mean ± std for weighted F1 and accuracy.
    """
    print(f"\n[EXP 2] 5-fold stratified CV — {task_name}")
    skf = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)
    scoring = {'f1_weighted': 'f1_weighted', 'accuracy': 'accuracy'}

    models = {
        'RandomForest':     RandomForestClassifier(n_estimators=300, max_depth=10,
                                                   class_weight='balanced',
                                                   random_state=42, n_jobs=-1),
        'GradientBoosting': GradientBoostingClassifier(n_estimators=200, max_depth=5,
                                                       random_state=42),
        'LogisticRegression': Pipeline([
            ('scaler', StandardScaler()),
            ('clf',    LogisticRegression(max_iter=1000, class_weight='balanced',
                                          random_state=42))
        ]),
    }

    rows = []
    for name, clf in models.items():
        cv_res = cross_validate(clf, X, y, cv=skf, scoring=scoring, n_jobs=-1)
        row = cv_summary(cv_res, label=name)
        rows.append(row)
        print(f"  {name}: F1={row['CV_F1_mean']:.4f}±{row['CV_F1_std']:.4f}  "
              f"Acc={row['CV_Acc_mean']:.4f}±{row['CV_Acc_std']:.4f}")

    df = pd.DataFrame(rows)
    out_path = os.path.join(OUT, f'crossval_{task_name.lower().replace(" ","_")}.csv')
    df.to_csv(out_path, index=False)
    print(f"  Saved: {os.path.basename(out_path)}")
    return df


# ═══════════════════════════════════════════════════════════════════════════════
# EXPERIMENT 3 — Class imbalance: balanced vs unbalanced
# ═══════════════════════════════════════════════════════════════════════════════
def experiment_imbalance(X, y, task_name):
    """
    Compare RF with class_weight=None vs 'balanced'.
    Shows whether balancing helps the minority Low-risk class.
    """
    print(f"\n[EXP 3] Class imbalance experiment — {task_name}")
    X_tr, X_te, y_tr, y_te = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y)

    configs = {
        'RF_unbalanced': RandomForestClassifier(n_estimators=300, max_depth=10,
                                                random_state=42, n_jobs=-1),
        'RF_balanced':   RandomForestClassifier(n_estimators=300, max_depth=10,
                                                class_weight='balanced',
                                                random_state=42, n_jobs=-1),
    }

    rows = []
    for name, clf in configs.items():
        clf.fit(X_tr, y_tr)
        y_pred = clf.predict(X_te)
        row = metrics_dict(y_te, y_pred, label=name)
        rows.append(row)
        print(f"  {name}: F1_Low={row['F1_Low']:.4f}  F1_High={row['F1_High']:.4f}  "
              f"WF1={row['Weighted_F1']:.4f}")

    df = pd.DataFrame(rows)
    out_path = os.path.join(OUT, f'imbalance_{task_name.lower().replace(" ","_")}.csv')
    df.to_csv(out_path, index=False)
    print(f"  Saved: {os.path.basename(out_path)}")
    return df


# ═══════════════════════════════════════════════════════════════════════════════
# EXPERIMENT 4 — Hyperparameter tuning (RandomizedSearchCV on RF)
# ═══════════════════════════════════════════════════════════════════════════════
def experiment_hyperparameter_tuning(X, y, task_name):
    """
    RandomizedSearchCV over RF hyperparameters.
    Compares default RF vs tuned RF on held-out test set.
    """
    print(f"\n[EXP 4] Hyperparameter tuning — {task_name}")
    X_tr, X_te, y_tr, y_te = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y)

    param_dist = {
        'n_estimators': [100, 200, 300, 500],
        'max_depth':    [5, 8, 10, 15, None],
        'min_samples_split': [2, 5, 10],
        'min_samples_leaf':  [1, 2, 4],
        'max_features':      ['sqrt', 'log2', 0.5],
    }

    base_rf = RandomForestClassifier(class_weight='balanced', random_state=42, n_jobs=-1)
    search  = RandomizedSearchCV(
        base_rf, param_dist, n_iter=30, cv=3,
        scoring='f1_weighted', random_state=42, n_jobs=-1, verbose=0
    )
    search.fit(X_tr, y_tr)
    best_params = search.best_params_
    print(f"  Best params: {best_params}")

    # Default RF
    default_rf = RandomForestClassifier(n_estimators=300, max_depth=10,
                                        class_weight='balanced',
                                        random_state=42, n_jobs=-1)
    default_rf.fit(X_tr, y_tr)
    y_pred_default = default_rf.predict(X_te)
    y_pred_tuned   = search.best_estimator_.predict(X_te)

    rows = [
        metrics_dict(y_te, y_pred_default, label='RF_default'),
        metrics_dict(y_te, y_pred_tuned,   label='RF_tuned'),
    ]
    rows[1]['best_params'] = str(best_params)

    df = pd.DataFrame(rows)
    out_path = os.path.join(OUT, f'hyperparameter_tuning_{task_name.lower().replace(" ","_")}.csv')
    df.to_csv(out_path, index=False)
    print(f"  Default WF1={rows[0]['Weighted_F1']:.4f}  Tuned WF1={rows[1]['Weighted_F1']:.4f}")
    print(f"  Saved: {os.path.basename(out_path)}")
    return df, search.best_estimator_, best_params


# ═══════════════════════════════════════════════════════════════════════════════
# EXPERIMENT 5 — Ablation study
# ═══════════════════════════════════════════════════════════════════════════════
# The ablation tests four system variants on the same 800-sample test set.
# We need the rule engine scores, so we import the predictor.
# For the ML-only variant we use the trained RF directly.
# For rule-only we use the rule scores mapped to labels.
# ─────────────────────────────────────────────────────────────────────────────

def _rule_label_from_score(score):
    """Map a 0-100 rule score to 0/1/2 label."""
    if score <= 35:  return 0
    if score <= 65:  return 1
    return 2

def _probs_to_score(probs):
    """Accept either a dict {'Low':p0,'Medium':p1,'High':p2} or a numpy array [p0,p1,p2]."""
    if isinstance(probs, dict):
        return probs['Low']*15 + probs['Medium']*50 + probs['High']*85
    return float(probs[0])*15 + float(probs[1])*50 + float(probs[2])*85

def _score_to_label(s):
    if s <= 35: return 0
    if s <= 65: return 1
    return 2

def experiment_ablation(X_cost, y_cost, X_time, y_time):
    """
    Ablation study comparing four system variants.
    Uses the same 80/20 split as training.
    Variant definitions:
      A — Rule-only:           final label from rule score alone (no ML)
      B — ML-only:             final label from RF probabilities alone
      C — Hybrid-fixed:        blend with fixed weights (ML=0.40 always)
      D — Hybrid-adaptive:     blend with adaptive weights (current system)
    """
    print("\n[EXP 5] Ablation study")

    # Import rule engine
    import sys
    sys.path.insert(0, BASE)
    from risk_predictor import ConstructionRiskPredictor, WEIGHTS_ML_CONFIDENT, WEIGHTS_ML_UNCERTAIN

    predictor = ConstructionRiskPredictor()
    predictor.load_models()

    # We need the test-set indices — use same split as training
    _, X_cost_te, _, y_cost_te = train_test_split(
        X_cost, y_cost, test_size=0.2, random_state=42, stratify=y_cost)
    _, X_time_te, _, y_time_te = train_test_split(
        X_time, y_time, test_size=0.2, random_state=42, stratify=y_time)

    # Load the trained RF models directly for ML-only variant
    import joblib
    cost_model = joblib.load(os.path.join(BASE, 'models', 'cost_overrun_risk_model.pkl'))
    time_model = joblib.load(os.path.join(BASE, 'models', 'time_delay_risk_model.pkl'))

    # ── Build synthetic form_data rows from feature arrays ──
    # We reconstruct minimal form_data dicts from the feature arrays so the
    # rule engine can score them. This is the cleanest way to test rule-only.
    cost_feat_names = [
        'plot_size_sqft','building_size_sqft','num_floors','budget_amount',
        'budget_per_sqft','plot_shape_code','topography_code','num_bedrooms',
        'num_bathrooms','total_rooms','design_style_code','customization_level',
        'design_complexity_score','development_constraint_level',
        'kerala_district_code','construction_start_month',
        'monsoon_exposure_score','district_risk_tier',
        'terrain_code','rainfall_code','flood_risk_code','effective_monsoon_score',
    ]
    time_feat_names = [
        'plot_size_sqft','building_size_sqft','num_floors','planned_duration_months',
        'plot_shape_code','topography_code','design_complexity_score',
        'customization_level','site_difficulty_score',
        'kerala_district_code','construction_start_month',
        'monsoon_exposure_score','district_risk_tier',
        'terrain_code','rainfall_code','flood_risk_code','effective_monsoon_score',
    ]

    def arr_to_form(arr, feat_names):
        return {feat_names[i]: float(arr[i]) for i in range(len(feat_names))}

    # ── Cost ablation ──
    print("  Running cost ablation (800 samples)...")
    rule_cost, ml_cost, hybrid_fixed_cost, hybrid_adapt_cost = [], [], [], []

    for i, row in enumerate(X_cost_te):
        fd = arr_to_form(row, cost_feat_names)
        m  = predictor._compute_derived_metrics(fd)

        # Rule score
        rule_s, _ = predictor._evaluate_cost_risk(m)
        rule_cost.append(_rule_label_from_score(rule_s))

        # ML score
        cost_arr, _ = predictor._build_feature_arrays(m)
        ml_proba = cost_model.predict_proba(cost_arr)[0]
        ml_cost.append(int(np.argmax(ml_proba)))

        # Hybrid fixed (ML weight always 0.40)
        compl_s, _ = predictor._evaluate_complexity_risk(m)
        time_s, _  = predictor._evaluate_time_risk(m)
        ml_s = _probs_to_score(ml_proba)
        fixed_score = rule_s*0.20 + time_s*0.20 + compl_s*0.20 + ml_s*0.40
        hybrid_fixed_cost.append(_score_to_label(fixed_score))

        # Hybrid adaptive (current system)
        confident = bool(max(ml_proba) >= 0.55)
        w = WEIGHTS_ML_CONFIDENT if confident else WEIGHTS_ML_UNCERTAIN
        adapt_score = rule_s*w['cost'] + time_s*w['time'] + compl_s*w['complexity'] + ml_s*w['ml']
        hybrid_adapt_cost.append(_score_to_label(adapt_score))

    # ── Time ablation ──
    print("  Running time ablation (800 samples)...")
    rule_time, ml_time, hybrid_fixed_time, hybrid_adapt_time = [], [], [], []

    for i, row in enumerate(X_time_te):
        fd = arr_to_form(row, time_feat_names)
        m  = predictor._compute_derived_metrics(fd)

        rule_s, _ = predictor._evaluate_time_risk(m)
        rule_time.append(_rule_label_from_score(rule_s))

        _, time_arr = predictor._build_feature_arrays(m)
        ml_proba = time_model.predict_proba(time_arr)[0]
        ml_time.append(int(np.argmax(ml_proba)))

        compl_s, _ = predictor._evaluate_complexity_risk(m)
        cost_s, _  = predictor._evaluate_cost_risk(m)
        ml_s = _probs_to_score(ml_proba)
        fixed_score = cost_s*0.20 + rule_s*0.20 + compl_s*0.20 + ml_s*0.40
        hybrid_fixed_time.append(_score_to_label(fixed_score))

        confident = bool(max(ml_proba) >= 0.55)
        w = WEIGHTS_ML_CONFIDENT if confident else WEIGHTS_ML_UNCERTAIN
        adapt_score = cost_s*w['cost'] + rule_s*w['time'] + compl_s*w['complexity'] + ml_s*w['ml']
        hybrid_adapt_time.append(_score_to_label(adapt_score))

    # ── Collect results ──
    rows = []
    for task, y_true, preds_dict in [
        ('Cost', y_cost_te, {
            'A_Rule_only':       rule_cost,
            'B_ML_only':         ml_cost,
            'C_Hybrid_fixed':    hybrid_fixed_cost,
            'D_Hybrid_adaptive': hybrid_adapt_cost,
        }),
        ('Time', y_time_te, {
            'A_Rule_only':       rule_time,
            'B_ML_only':         ml_time,
            'C_Hybrid_fixed':    hybrid_fixed_time,
            'D_Hybrid_adaptive': hybrid_adapt_time,
        }),
    ]:
        for variant, y_pred in preds_dict.items():
            row = metrics_dict(y_true, y_pred, label=f'{task}_{variant}')
            rows.append(row)
            print(f"  {task} {variant}: Acc={row['Accuracy']:.4f}  "
                  f"WF1={row['Weighted_F1']:.4f}  F1_High={row['F1_High']:.4f}")

    df = pd.DataFrame(rows)
    out_path = os.path.join(OUT, 'ablation_study.csv')
    df.to_csv(out_path, index=False)
    print(f"  Saved: ablation_study.csv")
    return df


# ═══════════════════════════════════════════════════════════════════════════════
# EXPERIMENT 6 — Blend-weight sensitivity analysis
# ═══════════════════════════════════════════════════════════════════════════════
def experiment_blend_weights(X_cost, y_cost, X_time, y_time):
    """
    Test 3 different blend-weight settings to justify the current choice.
    Uses vectorised approximations of rule scores directly from feature arrays
    (avoids calling the slow per-sample rule engine 800 times per weight setting).

    Rule score approximations (match the rule engine logic):
      cost_rule  ≈ f(budget_per_sqft, terrain, rainfall, flood, monsoon)
      time_rule  ≈ f(planned/expected ratio, terrain, rainfall, flood, monsoon)
      complexity ≈ f(floors, design_complexity, constraints, site_difficulty)

    Settings:
      W1 — Current:    ML=0.40, cost=0.20, time=0.20, complexity=0.20
      W2 — ML-heavy:   ML=0.60, cost=0.15, time=0.15, complexity=0.10
      W3 — Rule-heavy: ML=0.20, cost=0.30, time=0.30, complexity=0.20
    """
    print("\n[EXP 6] Blend-weight sensitivity analysis")
    import joblib

    _, X_cost_te, _, y_cost_te = train_test_split(
        X_cost, y_cost, test_size=0.2, random_state=42, stratify=y_cost)
    _, X_time_te, _, y_time_te = train_test_split(
        X_time, y_time, test_size=0.2, random_state=42, stratify=y_time)

    cost_model = joblib.load(os.path.join(BASE, 'models', 'cost_overrun_risk_model.pkl'))
    time_model = joblib.load(os.path.join(BASE, 'models', 'time_delay_risk_model.pkl'))

    # ── Vectorised rule score approximations (no per-sample rule engine calls) ──
    # Mirrors the rule engine logic using direct numpy operations on feature arrays.
    # COST feature indices: bps=4, terrain=18, rain=19, flood=20, eff_monsoon=21,
    #                       floors=2, design_complexity=12, dev_constraint=13
    # TIME feature indices: planned=3, bsize=1, floors=2, terrain=13, rain=14,
    #                       flood=15, monsoon=11, eff_monsoon=16, complexity=6, site=8

    def vec_cost_rule(X):
        bps     = X[:, 4]
        terrain = X[:, 18].astype(int)
        rain    = X[:, 19].astype(int)
        flood   = X[:, 20].astype(int)
        monsoon = X[:, 21]
        t_pen = np.array([0,5,5,10,10,15])[np.clip(terrain,0,5)]
        r_pen = np.array([-5,0,5,10])[np.clip(rain,0,3)]
        f_pen = np.array([0,5,10])[np.clip(flood,0,2)]
        env   = t_pen + r_pen + f_pen
        base  = np.where(bps<500, 90., np.where(bps<800, 80.,
                np.where(bps<1500, 57.5, np.where(bps<2500, 27.5, 15.))))
        return np.clip(base + env + monsoon*12., 0, 100)

    def vec_time_rule(X):
        planned = X[:, 3]; bsize = X[:, 1]; floors = X[:, 2]
        terrain = X[:, 13].astype(int); rain = X[:, 14].astype(int)
        flood   = X[:, 15].astype(int); monsoon = X[:, 11]
        expected = np.maximum(3., bsize/400. + floors*1.5)
        ratio    = np.where(expected>0, planned/expected, 1.)
        t_pen = np.array([0,5,5,10,10,15])[np.clip(terrain,0,5)]
        r_pen = np.array([-5,0,5,10])[np.clip(rain,0,3)]
        f_pen = np.array([0,5,15])[np.clip(flood,0,2)]
        env   = t_pen + r_pen + f_pen
        base  = np.where(ratio<0.40, 90., np.where(ratio<0.65, 72.,
                np.where(ratio<0.85, 50., np.where(ratio<=1.20, 20., 25.))))
        return np.clip(base + env + monsoon*20., 0, 100)

    def vec_complexity_cost(X):
        floors = X[:, 2]; compl = X[:, 12]; constr = X[:, 13]
        return (np.clip((floors-1)*20,0,100)*0.35
                + (compl/15.)*100*0.30
                + (constr/3.)*100*0.20)

    def vec_complexity_time(X):
        floors = X[:, 2]; compl = X[:, 6]; site = X[:, 8]
        return (np.clip((floors-1)*20,0,100)*0.35
                + (compl/15.)*100*0.30
                + (site/10.)*100*0.15)

    print("  Computing vectorised scores (batch)...")
    cost_rule  = vec_cost_rule(X_cost_te)
    cost_compl = vec_complexity_cost(X_cost_te)
    cost_ml_p  = cost_model.predict_proba(X_cost_te)
    cost_ml_s  = cost_ml_p[:,0]*15 + cost_ml_p[:,1]*50 + cost_ml_p[:,2]*85
    # For cost task, "time" blend dimension: use neutral 50 (planned_duration not in cost dataset)
    cost_time_neutral = np.full(len(X_cost_te), 50.)

    time_rule  = vec_time_rule(X_time_te)
    time_compl = vec_complexity_time(X_time_te)
    time_ml_p  = time_model.predict_proba(X_time_te)
    time_ml_s  = time_ml_p[:,0]*15 + time_ml_p[:,1]*50 + time_ml_p[:,2]*85
    # For time task, "cost" blend dimension: use neutral 50 (budget not in time dataset)
    time_cost_neutral = np.full(len(X_time_te), 50.)
    print("  Done. Testing weight settings...")

    weight_settings = {
        'W1_current':    {'cost': 0.20, 'time': 0.20, 'complexity': 0.20, 'ml': 0.40},
        'W2_ml_heavy':   {'cost': 0.15, 'time': 0.15, 'complexity': 0.10, 'ml': 0.60},
        'W3_rule_heavy': {'cost': 0.30, 'time': 0.30, 'complexity': 0.20, 'ml': 0.20},
    }

    rows = []
    for wname, w in weight_settings.items():
        cost_scores = (cost_rule*w['cost'] + cost_time_neutral*w['time']
                       + cost_compl*w['complexity'] + cost_ml_s*w['ml'])
        time_scores = (time_cost_neutral*w['cost'] + time_rule*w['time']
                       + time_compl*w['complexity'] + time_ml_s*w['ml'])
        cost_preds = np.vectorize(_score_to_label)(cost_scores)
        time_preds = np.vectorize(_score_to_label)(time_scores)
        r_cost = metrics_dict(y_cost_te, cost_preds, label=f'Cost_{wname}')
        r_time = metrics_dict(y_time_te, time_preds, label=f'Time_{wname}')
        rows.extend([r_cost, r_time])
        print(f"  {wname}: Cost_WF1={r_cost['Weighted_F1']:.4f}  Time_WF1={r_time['Weighted_F1']:.4f}")

    df = pd.DataFrame(rows)
    out_path = os.path.join(OUT, 'blend_weight_sensitivity.csv')
    df.to_csv(out_path, index=False)
    print(f"  Saved: blend_weight_sensitivity.csv")
    return df


# ═══════════════════════════════════════════════════════════════════════════════
# EXPERIMENT 7 — Monsoon sensitivity analysis
# ═══════════════════════════════════════════════════════════════════════════════
def experiment_monsoon_sensitivity(X_cost, y_cost, X_time, y_time):
    """
    Evaluate model performance stratified by monsoon exposure quartile.
    Shows whether the model is more/less accurate during monsoon season.
    """
    print("\n[EXP 7] Monsoon sensitivity analysis")
    import joblib

    cost_model = joblib.load(os.path.join(BASE, 'models', 'cost_overrun_risk_model.pkl'))
    time_model = joblib.load(os.path.join(BASE, 'models', 'time_delay_risk_model.pkl'))

    # monsoon_exposure_score is index 16 in COST_FEATURES, index 11 in TIME_FEATURES
    COST_MONSOON_IDX = 16
    TIME_MONSOON_IDX = 11

    _, X_cost_te, _, y_cost_te = train_test_split(
        X_cost, y_cost, test_size=0.2, random_state=42, stratify=y_cost)
    _, X_time_te, _, y_time_te = train_test_split(
        X_time, y_time, test_size=0.2, random_state=42, stratify=y_time)

    rows = []
    for task, X_te, y_te, model, midx in [
        ('Cost', X_cost_te, y_cost_te, cost_model, COST_MONSOON_IDX),
        ('Time', X_time_te, y_time_te, time_model, TIME_MONSOON_IDX),
    ]:
        monsoon_vals = X_te[:, midx]
        quartiles = np.percentile(monsoon_vals, [25, 50, 75])
        labels_q = ['Q1_low', 'Q2_moderate', 'Q3_high', 'Q4_very_high']

        y_pred_all = model.predict(X_te)

        for qi, (lo, hi, ql) in enumerate(zip(
            [0, quartiles[0], quartiles[1], quartiles[2]],
            [quartiles[0], quartiles[1], quartiles[2], 1.01],
            labels_q
        )):
            mask = (monsoon_vals >= lo) & (monsoon_vals < hi)
            if mask.sum() < 5:
                continue
            row = metrics_dict(y_te[mask], y_pred_all[mask],
                               label=f'{task}_{ql}')
            row['n_samples'] = int(mask.sum())
            row['monsoon_range'] = f'{lo:.2f}-{hi:.2f}'
            rows.append(row)
            print(f"  {task} {ql} (n={mask.sum()}): "
                  f"WF1={row['Weighted_F1']:.4f}  Acc={row['Accuracy']:.4f}")

    df = pd.DataFrame(rows)
    out_path = os.path.join(OUT, 'monsoon_sensitivity.csv')
    df.to_csv(out_path, index=False)
    print(f"  Saved: monsoon_sensitivity.csv")
    return df


# ═══════════════════════════════════════════════════════════════════════════════
# EXPERIMENT 8 — District-level evaluation
# ═══════════════════════════════════════════════════════════════════════════════
def experiment_district_evaluation(X_cost, y_cost, X_time, y_time):
    """
    Per-district accuracy and F1 on the test set.
    Reveals whether the model performs consistently across Kerala districts.
    """
    print("\n[EXP 8] District-level evaluation")
    import joblib

    KERALA_DISTRICTS = [
        'Thiruvananthapuram','Kollam','Pathanamthitta','Alappuzha',
        'Kottayam','Idukki','Ernakulam','Thrissur',
        'Palakkad','Malappuram','Kozhikode','Wayanad','Kannur','Kasaragod',
    ]
    COST_DIST_IDX = 14   # kerala_district_code index in COST_FEATURES
    TIME_DIST_IDX = 9    # kerala_district_code index in TIME_FEATURES

    cost_model = joblib.load(os.path.join(BASE, 'models', 'cost_overrun_risk_model.pkl'))
    time_model = joblib.load(os.path.join(BASE, 'models', 'time_delay_risk_model.pkl'))

    _, X_cost_te, _, y_cost_te = train_test_split(
        X_cost, y_cost, test_size=0.2, random_state=42, stratify=y_cost)
    _, X_time_te, _, y_time_te = train_test_split(
        X_time, y_time, test_size=0.2, random_state=42, stratify=y_time)

    rows = []
    for task, X_te, y_te, model, didx in [
        ('Cost', X_cost_te, y_cost_te, cost_model, COST_DIST_IDX),
        ('Time', X_time_te, y_time_te, time_model, TIME_DIST_IDX),
    ]:
        dist_vals = X_te[:, didx].astype(int)
        y_pred_all = model.predict(X_te)

        for d in range(14):
            mask = dist_vals == d
            if mask.sum() < 3:
                continue
            acc = accuracy_score(y_te[mask], y_pred_all[mask])
            wf1 = f1_score(y_te[mask], y_pred_all[mask], average='weighted', zero_division=0)
            rows.append({
                'task': task,
                'district': KERALA_DISTRICTS[d],
                'district_code': d,
                'n_samples': int(mask.sum()),
                'Accuracy': round(acc, 4),
                'Weighted_F1': round(wf1, 4),
            })
            print(f"  {task} {KERALA_DISTRICTS[d]} (n={mask.sum()}): "
                  f"Acc={acc:.4f}  WF1={wf1:.4f}")

    df = pd.DataFrame(rows)
    out_path = os.path.join(OUT, 'district_evaluation.csv')
    df.to_csv(out_path, index=False)
    print(f"  Saved: district_evaluation.csv")
    return df


# ═══════════════════════════════════════════════════════════════════════════════
# FIGURE GENERATION — Paper-ready plots
# ═══════════════════════════════════════════════════════════════════════════════
def generate_figures(baseline_cost, baseline_time, cv_cost, cv_time,
                     ablation_df, blend_df):
    """Generate all paper-ready figures."""
    print("\n[FIGS] Generating paper figures...")

    # Fig 1 — Baseline comparison bar chart (F1 per class)
    fig, axes = plt.subplots(1, 2, figsize=(12, 5))
    for ax, df, title in [
        (axes[0], baseline_cost, 'Cost Overrun Risk'),
        (axes[1], baseline_time, 'Time Delay Risk'),
    ]:
        x = np.arange(len(df))
        w = 0.25
        ax.bar(x - w, df['F1_Low'],    w, label='F1 Low',    color='#4CAF50')
        ax.bar(x,     df['F1_Medium'], w, label='F1 Medium', color='#FF9800')
        ax.bar(x + w, df['F1_High'],   w, label='F1 High',   color='#F44336')
        ax.set_xticks(x)
        ax.set_xticklabels(df['model'], rotation=15, ha='right')
        ax.set_ylim(0, 1.05)
        ax.set_ylabel('F1-Score')
        ax.set_title(f'Per-Class F1 — {title}')
        ax.legend(fontsize=8)
        ax.grid(axis='y', alpha=0.3)
    plt.tight_layout()
    fig.savefig(os.path.join(OUT, 'fig1_baseline_comparison.png'), dpi=150)
    plt.close(fig)
    print("  Saved: fig1_baseline_comparison.png")

    # Fig 2 — Cross-validation results with error bars
    fig, axes = plt.subplots(1, 2, figsize=(10, 4))
    for ax, df, title in [
        (axes[0], cv_cost, 'Cost Overrun Risk'),
        (axes[1], cv_time, 'Time Delay Risk'),
    ]:
        ax.bar(df['model'], df['CV_F1_mean'],
               yerr=df['CV_F1_std'], capsize=5,
               color=['#2196F3','#FF5722','#9C27B0'])
        ax.set_ylim(0, 1.05)
        ax.set_ylabel('Weighted F1 (5-fold CV)')
        ax.set_title(f'5-Fold CV — {title}')
        ax.tick_params(axis='x', rotation=15)
        ax.grid(axis='y', alpha=0.3)
    plt.tight_layout()
    fig.savefig(os.path.join(OUT, 'fig2_crossval.png'), dpi=150)
    plt.close(fig)
    print("  Saved: fig2_crossval.png")

    # Fig 3 — Ablation study
    abl_cost = ablation_df[ablation_df['model'].str.startswith('Cost')]
    abl_time = ablation_df[ablation_df['model'].str.startswith('Time')]
    fig, axes = plt.subplots(1, 2, figsize=(12, 5))
    for ax, df, title in [
        (axes[0], abl_cost, 'Cost Overrun Risk'),
        (axes[1], abl_time, 'Time Delay Risk'),
    ]:
        short_labels = [m.split('_', 1)[1] for m in df['model']]
        x = np.arange(len(df))
        w = 0.3
        ax.bar(x - w/2, df['Weighted_F1'], w, label='Weighted F1', color='#2196F3')
        ax.bar(x + w/2, df['Accuracy'],    w, label='Accuracy',    color='#FF9800')
        ax.set_xticks(x)
        ax.set_xticklabels(short_labels, rotation=20, ha='right', fontsize=8)
        ax.set_ylim(0, 1.05)
        ax.set_ylabel('Score')
        ax.set_title(f'Ablation Study — {title}')
        ax.legend(fontsize=8)
        ax.grid(axis='y', alpha=0.3)
    plt.tight_layout()
    fig.savefig(os.path.join(OUT, 'fig3_ablation.png'), dpi=150)
    plt.close(fig)
    print("  Saved: fig3_ablation.png")

    # Fig 4 — Blend weight sensitivity
    blend_cost = blend_df[blend_df['model'].str.startswith('Cost')]
    blend_time = blend_df[blend_df['model'].str.startswith('Time')]
    fig, axes = plt.subplots(1, 2, figsize=(10, 4))
    for ax, df, title in [
        (axes[0], blend_cost, 'Cost Overrun Risk'),
        (axes[1], blend_time, 'Time Delay Risk'),
    ]:
        short_labels = [m.replace('Cost_','').replace('Time_','') for m in df['model']]
        ax.bar(short_labels, df['Weighted_F1'], color=['#4CAF50','#F44336','#2196F3'])
        ax.set_ylim(0.5, 1.0)
        ax.set_ylabel('Weighted F1')
        ax.set_title(f'Blend Weight Sensitivity — {title}')
        ax.tick_params(axis='x', rotation=15)
        ax.grid(axis='y', alpha=0.3)
        for i, v in enumerate(df['Weighted_F1']):
            ax.text(i, v + 0.005, f'{v:.4f}', ha='center', fontsize=9)
    plt.tight_layout()
    fig.savefig(os.path.join(OUT, 'fig4_blend_weights.png'), dpi=150)
    plt.close(fig)
    print("  Saved: fig4_blend_weights.png")


# ═══════════════════════════════════════════════════════════════════════════════
# MAIN
# ═══════════════════════════════════════════════════════════════════════════════
def main():
    print("=" * 65)
    print("BuildHub Research Experiments — IEEE Paper Strengthening")
    print("=" * 65)

    X_cost, y_cost, X_time, y_time = load_data()
    print(f"Loaded: cost {X_cost.shape}, time {X_time.shape}")
    print(f"Cost labels: {dict(zip(*np.unique(y_cost, return_counts=True)))}")
    print(f"Time labels: {dict(zip(*np.unique(y_time, return_counts=True)))}")

    # Run all experiments
    baseline_cost = experiment_baselines(X_cost, y_cost, 'Cost Overrun')
    baseline_time = experiment_baselines(X_time, y_time, 'Time Delay')

    cv_cost = experiment_crossval(X_cost, y_cost, 'Cost Overrun')
    cv_time = experiment_crossval(X_time, y_time, 'Time Delay')

    experiment_imbalance(X_cost, y_cost, 'Cost Overrun')
    experiment_imbalance(X_time, y_time, 'Time Delay')

    experiment_hyperparameter_tuning(X_cost, y_cost, 'Cost Overrun')
    experiment_hyperparameter_tuning(X_time, y_time, 'Time Delay')

    ablation_df = experiment_ablation(X_cost, y_cost, X_time, y_time)

    blend_df = experiment_blend_weights(X_cost, y_cost, X_time, y_time)

    experiment_monsoon_sensitivity(X_cost, y_cost, X_time, y_time)
    experiment_district_evaluation(X_cost, y_cost, X_time, y_time)

    generate_figures(baseline_cost, baseline_time, cv_cost, cv_time,
                     ablation_df, blend_df)

    print("\n" + "=" * 65)
    print(f"All results saved to: {OUT}")
    print("=" * 65)


if __name__ == '__main__':
    main()
