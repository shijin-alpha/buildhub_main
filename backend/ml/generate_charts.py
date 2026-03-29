"""
Generate updated research-paper quality charts for the construction risk ML models.
Outputs PNG files to backend/ml/charts/
"""

import pandas as pd
import numpy as np
import joblib
import json
import os
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import matplotlib.gridspec as gridspec
from matplotlib.colors import LinearSegmentedColormap
from sklearn.model_selection import train_test_split, StratifiedKFold, cross_val_score
from sklearn.metrics import (
    classification_report, confusion_matrix,
    roc_curve, auc, precision_recall_curve, average_precision_score
)
from sklearn.preprocessing import label_binarize
from sklearn.calibration import calibration_curve

# ── Setup ─────────────────────────────────────────────────────────────────────
BASE_DIR   = os.path.dirname(os.path.abspath(__file__))
CHARTS_DIR = os.path.join(BASE_DIR, 'charts')
os.makedirs(CHARTS_DIR, exist_ok=True)

CLASSES     = ['Low', 'Medium', 'High']
COLORS      = ['#10b981', '#f59e0b', '#ef4444']
GRID_COLOR  = '#e5e7eb'

plt.rcParams.update({
    'font.family':       'DejaVu Sans',
    'font.size':         11,
    'axes.titlesize':    13,
    'axes.labelsize':    11,
    'xtick.labelsize':   10,
    'ytick.labelsize':   10,
    'legend.fontsize':   10,
    'figure.dpi':        150,
    'savefig.dpi':       300,
    'savefig.bbox':      'tight',
    'axes.spines.top':   False,
    'axes.spines.right': False,
})

# ── Load data & models ────────────────────────────────────────────────────────
with open(os.path.join(BASE_DIR, 'models', 'model_metadata.json')) as f:
    meta = json.load(f)

cost_df = pd.read_csv(os.path.join(BASE_DIR, 'data', 'cost_overrun_risk_dataset.csv'))
time_df = pd.read_csv(os.path.join(BASE_DIR, 'data', 'time_delay_risk_dataset.csv'))

cost_model = joblib.load(os.path.join(BASE_DIR, 'models', 'cost_overrun_risk_model.pkl'))
time_model = joblib.load(os.path.join(BASE_DIR, 'models', 'time_delay_risk_model.pkl'))

Xc = cost_df[meta['cost_features']].values
yc = cost_df['cost_overrun_risk'].values
Xt = time_df[meta['time_features']].values
yt = time_df['time_delay_risk'].values

Xc_train, Xc_test, yc_train, yc_test = train_test_split(Xc, yc, test_size=0.2, random_state=42, stratify=yc)
Xt_train, Xt_test, yt_train, yt_test = train_test_split(Xt, yt, test_size=0.2, random_state=42, stratify=yt)

yc_pred = cost_model.predict(Xc_test)
yt_pred = time_model.predict(Xt_test)
yc_prob = cost_model.predict_proba(Xc_test)
yt_prob = time_model.predict_proba(Xt_test)

FEATURE_LABELS = {
    'budget_per_sqft':              'Budget per sqft',
    'budget_amount':                'Total Budget',
    'building_size_sqft':           'Building Size (sqft)',
    'plot_size_sqft':               'Plot Size (sqft)',
    'num_floors':                   'No. of Floors',
    'design_complexity_score':      'Design Complexity',
    'planned_duration_months':      'Planned Duration',
    'monsoon_exposure_score':       'Monsoon Exposure',
    'kerala_district_code':         'Kerala District',
    'construction_start_month':     'Start Month',
    'district_risk_tier':           'District Risk Tier',
    'customization_level':          'Customization Level',
    'development_constraint_level': 'Site Constraints',
    'site_difficulty_score':        'Site Difficulty',
    'topography_code':              'Topography',
    'num_bedrooms':                 'No. of Bedrooms',
    'num_bathrooms':                'No. of Bathrooms',
    'total_rooms':                  'Total Rooms',
    'plot_shape_code':              'Plot Shape',
    'design_style_code':            'Design Style',
}


# ── 1. Normalised Confusion Matrices ─────────────────────────────────────────
def plot_confusion_matrices():
    fig, axes = plt.subplots(1, 2, figsize=(13, 5))
    fig.suptitle('Normalised Confusion Matrices — Cost Overrun & Time Delay Models',
                 fontsize=14, fontweight='bold', y=1.02)

    cmap = LinearSegmentedColormap.from_list('risk', ['#f0fdf4', '#14532d'])

    for ax, y_true, y_pred, title in [
        (axes[0], yc_test, yc_pred, 'Cost Overrun Risk Model'),
        (axes[1], yt_test, yt_pred, 'Time Delay Risk Model'),
    ]:
        cm     = confusion_matrix(y_true, y_pred)
        cm_pct = cm.astype(float) / cm.sum(axis=1, keepdims=True) * 100

        im = ax.imshow(cm_pct, cmap=cmap, vmin=0, vmax=100, aspect='auto')

        for i in range(3):
            for j in range(3):
                color = 'white' if cm_pct[i, j] > 55 else '#111827'
                ax.text(j, i, f'{cm[i,j]}\n({cm_pct[i,j]:.1f}%)',
                        ha='center', va='center', fontsize=10,
                        fontweight='bold', color=color)

        ax.set_xticks(range(3)); ax.set_xticklabels(CLASSES)
        ax.set_yticks(range(3)); ax.set_yticklabels(CLASSES)
        ax.set_xlabel('Predicted Label', fontweight='bold')
        ax.set_ylabel('True Label', fontweight='bold')
        ax.set_title(title, fontweight='bold', pad=12)

        cb = plt.colorbar(im, ax=ax, shrink=0.85)
        cb.set_label('Row-normalised %', fontsize=9)

        # Diagonal accuracy annotation
        diag_acc = np.diag(cm_pct).mean()
        ax.text(0.02, 0.97, f'Mean Diag: {diag_acc:.1f}%',
                transform=ax.transAxes, va='top', fontsize=9,
                bbox=dict(boxstyle='round,pad=0.3', facecolor='#fffbeb', edgecolor='#f59e0b'))

    plt.tight_layout()
    path = os.path.join(CHARTS_DIR, 'confusion_matrices.png')
    plt.savefig(path); plt.close()
    print(f'Saved: {path}')


# ── 2. Per-class Precision / Recall / F1 ─────────────────────────────────────
def plot_per_class_metrics():
    fig, axes = plt.subplots(1, 2, figsize=(14, 5), sharey=True)
    fig.suptitle('Per-Class Precision, Recall & F1-Score',
                 fontsize=14, fontweight='bold', y=1.02)

    x = np.arange(3)
    width = 0.25

    for ax, y_true, y_pred, title in [
        (axes[0], yc_test, yc_pred, 'Cost Overrun Risk Model'),
        (axes[1], yt_test, yt_pred, 'Time Delay Risk Model'),
    ]:
        report    = classification_report(y_true, y_pred, output_dict=True)
        precision = [report[str(i)]['precision'] for i in range(3)]
        recall    = [report[str(i)]['recall']    for i in range(3)]
        f1        = [report[str(i)]['f1-score']  for i in range(3)]

        b1 = ax.bar(x - width, precision, width, label='Precision', color='#6366f1', alpha=0.88)
        b2 = ax.bar(x,         recall,    width, label='Recall',    color='#0891b2', alpha=0.88)
        b3 = ax.bar(x + width, f1,        width, label='F1-Score',  color='#10b981', alpha=0.88)

        for bars in [b1, b2, b3]:
            for bar in bars:
                h = bar.get_height()
                ax.text(bar.get_x() + bar.get_width() / 2, h + 0.006,
                        f'{h:.2f}', ha='center', va='bottom', fontsize=8.5, fontweight='bold')

        ax.set_xticks(x)
        ax.set_xticklabels([f'{c}\n(n={int(np.sum(y_true==i))})' for i, c in enumerate(CLASSES)])
        ax.set_ylim(0, 1.15)
        ax.set_ylabel('Score', fontweight='bold')
        ax.set_title(title, fontweight='bold', pad=10)
        ax.legend(loc='lower right')
        ax.yaxis.grid(True, color=GRID_COLOR, linewidth=0.8)
        ax.set_axisbelow(True)

        acc = report['accuracy']
        mf1 = report['macro avg']['f1-score']
        ax.text(0.98, 0.97, f'Accuracy: {acc:.3f}  |  Macro F1: {mf1:.3f}',
                transform=ax.transAxes, ha='right', va='top', fontsize=9, fontweight='bold',
                bbox=dict(boxstyle='round,pad=0.3', facecolor='#f0fdf4', edgecolor='#10b981'))

    plt.tight_layout()
    path = os.path.join(CHARTS_DIR, 'per_class_metrics.png')
    plt.savefig(path); plt.close()
    print(f'Saved: {path}')


# ── 3. ROC Curves ─────────────────────────────────────────────────────────────
def plot_roc_curves():
    fig, axes = plt.subplots(1, 2, figsize=(13, 5))
    fig.suptitle('ROC Curves (One-vs-Rest) — AUC per Risk Class',
                 fontsize=14, fontweight='bold', y=1.02)

    line_styles = ['-', '--', '-.']

    for ax, y_true, y_prob, title in [
        (axes[0], yc_test, yc_prob, 'Cost Overrun Risk Model'),
        (axes[1], yt_test, yt_prob, 'Time Delay Risk Model'),
    ]:
        y_bin = label_binarize(y_true, classes=[0, 1, 2])

        for i, (cls, color, ls) in enumerate(zip(CLASSES, COLORS, line_styles)):
            fpr, tpr, _ = roc_curve(y_bin[:, i], y_prob[:, i])
            roc_auc = auc(fpr, tpr)
            ax.plot(fpr, tpr, color=color, lw=2.2, linestyle=ls,
                    label=f'{cls} Risk  AUC={roc_auc:.3f}')
            ax.fill_between(fpr, tpr, alpha=0.06, color=color)

        ax.plot([0, 1], [0, 1], 'k--', lw=1, alpha=0.35, label='Random')
        ax.set_xlim([0, 1]); ax.set_ylim([0, 1.02])
        ax.set_xlabel('False Positive Rate', fontweight='bold')
        ax.set_ylabel('True Positive Rate', fontweight='bold')
        ax.set_title(title, fontweight='bold', pad=10)
        ax.legend(loc='lower right', framealpha=0.9)
        ax.yaxis.grid(True, color=GRID_COLOR, linewidth=0.8)
        ax.xaxis.grid(True, color=GRID_COLOR, linewidth=0.8)
        ax.set_axisbelow(True)

    plt.tight_layout()
    path = os.path.join(CHARTS_DIR, 'roc_curves.png')
    plt.savefig(path); plt.close()
    print(f'Saved: {path}')


# ── 4. Precision-Recall Curves ────────────────────────────────────────────────
def plot_precision_recall_curves():
    fig, axes = plt.subplots(1, 2, figsize=(13, 5))
    fig.suptitle('Precision-Recall Curves (One-vs-Rest) — AP per Risk Class',
                 fontsize=14, fontweight='bold', y=1.02)

    line_styles = ['-', '--', '-.']

    for ax, y_true, y_prob, title in [
        (axes[0], yc_test, yc_prob, 'Cost Overrun Risk Model'),
        (axes[1], yt_test, yt_prob, 'Time Delay Risk Model'),
    ]:
        y_bin = label_binarize(y_true, classes=[0, 1, 2])

        for i, (cls, color, ls) in enumerate(zip(CLASSES, COLORS, line_styles)):
            prec, rec, _ = precision_recall_curve(y_bin[:, i], y_prob[:, i])
            ap = average_precision_score(y_bin[:, i], y_prob[:, i])
            ax.plot(rec, prec, color=color, lw=2.2, linestyle=ls,
                    label=f'{cls} Risk  AP={ap:.3f}')
            ax.fill_between(rec, prec, alpha=0.06, color=color)

        ax.set_xlim([0, 1]); ax.set_ylim([0, 1.05])
        ax.set_xlabel('Recall', fontweight='bold')
        ax.set_ylabel('Precision', fontweight='bold')
        ax.set_title(title, fontweight='bold', pad=10)
        ax.legend(loc='lower left', framealpha=0.9)
        ax.yaxis.grid(True, color=GRID_COLOR, linewidth=0.8)
        ax.xaxis.grid(True, color=GRID_COLOR, linewidth=0.8)
        ax.set_axisbelow(True)

    plt.tight_layout()
    path = os.path.join(CHARTS_DIR, 'precision_recall_curves.png')
    plt.savefig(path); plt.close()
    print(f'Saved: {path}')


# ── 5. Feature Importance (top 12) ───────────────────────────────────────────
def plot_feature_importance():
    fig, axes = plt.subplots(1, 2, figsize=(15, 6))
    fig.suptitle('Top-12 Feature Importances — Gradient Boosting Models',
                 fontsize=14, fontweight='bold', y=1.02)

    for ax, model, features, title, cmap_name in [
        (axes[0], cost_model, meta['cost_features'], 'Cost Overrun Risk Model', 'Blues'),
        (axes[1], time_model, meta['time_features'],  'Time Delay Risk Model',  'Purples'),
    ]:
        importances = model.feature_importances_
        indices = np.argsort(importances)[::-1][:12][::-1]
        labels  = [FEATURE_LABELS.get(features[i], features[i]) for i in indices]
        values  = importances[indices]

        cmap = plt.get_cmap(cmap_name)
        bar_colors = [cmap(0.35 + 0.65 * v / values.max()) for v in values]

        bars = ax.barh(range(len(indices)), values, color=bar_colors,
                       edgecolor='white', linewidth=0.6, height=0.7)

        for bar, val in zip(bars, values):
            ax.text(val + 0.001, bar.get_y() + bar.get_height() / 2,
                    f'{val:.3f}', va='center', fontsize=9, fontweight='bold')

        ax.set_yticks(range(len(indices)))
        ax.set_yticklabels(labels, fontsize=9.5)
        ax.set_xlabel('Feature Importance (Gini)', fontweight='bold')
        ax.set_title(title, fontweight='bold', pad=10)
        ax.xaxis.grid(True, color=GRID_COLOR, linewidth=0.8)
        ax.set_axisbelow(True)
        ax.set_xlim(0, values.max() * 1.22)

    plt.tight_layout()
    path = os.path.join(CHARTS_DIR, 'feature_importance.png')
    plt.savefig(path); plt.close()
    print(f'Saved: {path}')


# ── 6. Cross-Validation Score Distribution ───────────────────────────────────
def plot_cv_scores():
    fig, axes = plt.subplots(1, 2, figsize=(13, 5))
    fig.suptitle('5-Fold Stratified Cross-Validation — Weighted F1 per Fold',
                 fontsize=14, fontweight='bold', y=1.02)

    skf = StratifiedKFold(n_splits=5, shuffle=True, random_state=42)

    for ax, X, y, model, title, color in [
        (axes[0], Xc, yc, cost_model, 'Cost Overrun Risk Model', '#6366f1'),
        (axes[1], Xt, yt, time_model, 'Time Delay Risk Model',   '#0891b2'),
    ]:
        scores = cross_val_score(model, X, y, cv=skf, scoring='f1_weighted', n_jobs=-1)
        folds  = [f'Fold {i+1}' for i in range(5)]

        bars = ax.bar(folds, scores, color=color, alpha=0.82, edgecolor='white', linewidth=0.8)
        ax.axhline(scores.mean(), color='#ef4444', lw=2, linestyle='--',
                   label=f'Mean = {scores.mean():.4f}')
        ax.fill_between(range(5),
                        scores.mean() - scores.std(),
                        scores.mean() + scores.std(),
                        alpha=0.12, color='#ef4444', label=f'±1 SD = {scores.std():.4f}')

        for bar, val in zip(bars, scores):
            ax.text(bar.get_x() + bar.get_width() / 2, val + 0.003,
                    f'{val:.4f}', ha='center', va='bottom', fontsize=9, fontweight='bold')

        ax.set_ylim(max(0, scores.min() - 0.05), min(1.05, scores.max() + 0.06))
        ax.set_ylabel('Weighted F1-Score', fontweight='bold')
        ax.set_title(title, fontweight='bold', pad=10)
        ax.legend(framealpha=0.9)
        ax.yaxis.grid(True, color=GRID_COLOR, linewidth=0.8)
        ax.set_axisbelow(True)

    plt.tight_layout()
    path = os.path.join(CHARTS_DIR, 'cv_scores.png')
    plt.savefig(path); plt.close()
    print(f'Saved: {path}')


# ── 7. Class Distribution ─────────────────────────────────────────────────────
def plot_class_distribution():
    fig, axes = plt.subplots(1, 2, figsize=(12, 5))
    fig.suptitle('Dataset Class Distribution — Train vs Test Split',
                 fontsize=14, fontweight='bold', y=1.02)

    for ax, y_train, y_test, title in [
        (axes[0], yc_train, yc_test, 'Cost Overrun Risk'),
        (axes[1], yt_train, yt_test, 'Time Delay Risk'),
    ]:
        x = np.arange(3)
        w = 0.35
        train_counts = [np.sum(y_train == i) for i in range(3)]
        test_counts  = [np.sum(y_test  == i) for i in range(3)]

        b1 = ax.bar(x - w/2, train_counts, w, label='Train', color='#6366f1', alpha=0.85)
        b2 = ax.bar(x + w/2, test_counts,  w, label='Test',  color='#10b981', alpha=0.85)

        for bars in [b1, b2]:
            for bar in bars:
                h = bar.get_height()
                ax.text(bar.get_x() + bar.get_width() / 2, h + 1,
                        str(int(h)), ha='center', va='bottom', fontsize=9, fontweight='bold')

        ax.set_xticks(x); ax.set_xticklabels(CLASSES)
        ax.set_ylabel('Sample Count', fontweight='bold')
        ax.set_title(title, fontweight='bold', pad=10)
        ax.legend()
        ax.yaxis.grid(True, color=GRID_COLOR, linewidth=0.8)
        ax.set_axisbelow(True)

    plt.tight_layout()
    path = os.path.join(CHARTS_DIR, 'class_distribution.png')
    plt.savefig(path); plt.close()
    print(f'Saved: {path}')


# ── 8. Model Calibration Curves ───────────────────────────────────────────────
def plot_calibration_curves():
    fig, axes = plt.subplots(1, 2, figsize=(13, 5))
    fig.suptitle('Probability Calibration Curves — Reliability Diagrams',
                 fontsize=14, fontweight='bold', y=1.02)

    for ax, y_true, y_prob, title in [
        (axes[0], yc_test, yc_prob, 'Cost Overrun Risk Model'),
        (axes[1], yt_test, yt_prob, 'Time Delay Risk Model'),
    ]:
        y_bin = label_binarize(y_true, classes=[0, 1, 2])

        for i, (cls, color) in enumerate(zip(CLASSES, COLORS)):
            frac_pos, mean_pred = calibration_curve(y_bin[:, i], y_prob[:, i], n_bins=8)
            ax.plot(mean_pred, frac_pos, marker='o', lw=2, color=color,
                    label=f'{cls} Risk', markersize=6)

        ax.plot([0, 1], [0, 1], 'k--', lw=1.2, alpha=0.5, label='Perfect Calibration')
        ax.set_xlim([0, 1]); ax.set_ylim([0, 1])
        ax.set_xlabel('Mean Predicted Probability', fontweight='bold')
        ax.set_ylabel('Fraction of Positives', fontweight='bold')
        ax.set_title(title, fontweight='bold', pad=10)
        ax.legend(loc='upper left', framealpha=0.9)
        ax.yaxis.grid(True, color=GRID_COLOR, linewidth=0.8)
        ax.xaxis.grid(True, color=GRID_COLOR, linewidth=0.8)
        ax.set_axisbelow(True)

    plt.tight_layout()
    path = os.path.join(CHARTS_DIR, 'calibration_curves.png')
    plt.savefig(path); plt.close()
    print(f'Saved: {path}')


# ── 9. Summary Dashboard ──────────────────────────────────────────────────────
def plot_summary_dashboard():
    fig = plt.figure(figsize=(18, 11))
    fig.suptitle(
        'Construction Risk Prediction System — Model Evaluation Summary\n'
        'Kerala-Specific Hybrid ML (Gradient Boosting + Rule Engine)',
        fontsize=14, fontweight='bold', y=1.01
    )

    gs = gridspec.GridSpec(2, 3, figure=fig, hspace=0.50, wspace=0.40)
    cmap = LinearSegmentedColormap.from_list('risk', ['#f0fdf4', '#14532d'])

    # Row 0: confusion matrices + accuracy bar
    for col, (y_true, y_pred, title) in enumerate([
        (yc_test, yc_pred, 'Cost Overrun Risk'),
        (yt_test, yt_pred, 'Time Delay Risk'),
    ]):
        ax = fig.add_subplot(gs[0, col])
        cm     = confusion_matrix(y_true, y_pred)
        cm_pct = cm.astype(float) / cm.sum(axis=1, keepdims=True) * 100
        im = ax.imshow(cm_pct, cmap=cmap, vmin=0, vmax=100)
        for i in range(3):
            for j in range(3):
                color = 'white' if cm_pct[i, j] > 55 else '#111827'
                ax.text(j, i, f'{cm[i,j]}\n({cm_pct[i,j]:.0f}%)',
                        ha='center', va='center', fontsize=9, fontweight='bold', color=color)
        ax.set_xticks(range(3)); ax.set_xticklabels(CLASSES, fontsize=9)
        ax.set_yticks(range(3)); ax.set_yticklabels(CLASSES, fontsize=9)
        ax.set_xlabel('Predicted', fontsize=9); ax.set_ylabel('Actual', fontsize=9)
        ax.set_title(f'Confusion Matrix\n{title}', fontweight='bold', fontsize=10)
        plt.colorbar(im, ax=ax, shrink=0.8, label='Row %')

    ax3 = fig.add_subplot(gs[0, 2])
    model_names = ['Cost Overrun\nModel', 'Time Delay\nModel']
    rc = classification_report(yc_test, yc_pred, output_dict=True)
    rt = classification_report(yt_test, yt_pred, output_dict=True)
    metrics = {
        'Accuracy':  [rc['accuracy'],                    rt['accuracy']],
        'Macro F1':  [rc['macro avg']['f1-score'],        rt['macro avg']['f1-score']],
        'Macro Prec':[rc['macro avg']['precision'],       rt['macro avg']['precision']],
        'Macro Rec': [rc['macro avg']['recall'],          rt['macro avg']['recall']],
    }
    x = np.arange(2)
    bar_colors = ['#6366f1', '#10b981', '#f59e0b', '#0891b2']
    w = 0.18
    for idx, (label, vals) in enumerate(metrics.items()):
        offset = (idx - 1.5) * w
        bars = ax3.bar(x + offset, vals, w, label=label, color=bar_colors[idx], alpha=0.85)
        for bar in bars:
            ax3.text(bar.get_x() + bar.get_width()/2, bar.get_height() + 0.005,
                     f'{bar.get_height():.3f}', ha='center', va='bottom', fontsize=7.5, fontweight='bold')
    ax3.set_xticks(x); ax3.set_xticklabels(model_names, fontsize=9)
    ax3.set_ylim(0, 1.12); ax3.set_ylabel('Score', fontsize=9)
    ax3.set_title('Overall Metrics Comparison', fontweight='bold', fontsize=10)
    ax3.legend(fontsize=7.5, ncol=2); ax3.yaxis.grid(True, color=GRID_COLOR); ax3.set_axisbelow(True)

    # Row 1: per-class F1 + top features
    for col, (y_true, y_pred, title, color) in enumerate([
        (yc_test, yc_pred, 'Cost Overrun — Per-Class F1', '#6366f1'),
        (yt_test, yt_pred, 'Time Delay — Per-Class F1',   '#0891b2'),
    ]):
        ax = fig.add_subplot(gs[1, col])
        report = classification_report(y_true, y_pred, output_dict=True)
        f1s = [report[str(i)]['f1-score'] for i in range(3)]
        bars = ax.bar(CLASSES, f1s, color=COLORS, alpha=0.85, edgecolor='white', linewidth=0.8)
        for bar, val in zip(bars, f1s):
            ax.text(bar.get_x() + bar.get_width()/2, val + 0.01,
                    f'{val:.3f}', ha='center', va='bottom', fontsize=9, fontweight='bold')
        ax.set_ylim(0, 1.15); ax.set_ylabel('F1-Score', fontsize=9)
        ax.set_title(title, fontweight='bold', fontsize=10)
        ax.yaxis.grid(True, color=GRID_COLOR); ax.set_axisbelow(True)

    ax6 = fig.add_subplot(gs[1, 2])
    cost_imp = dict(zip(meta['cost_features'], cost_model.feature_importances_))
    time_imp = dict(zip(meta['time_features'], time_model.feature_importances_))
    all_feats = set(cost_imp) | set(time_imp)
    combined  = {f: (cost_imp.get(f, 0) + time_imp.get(f, 0)) / 2 for f in all_feats}
    top6 = sorted(combined, key=combined.get, reverse=True)[:6][::-1]
    labels = [FEATURE_LABELS.get(f, f) for f in top6]
    vals   = [combined[f] for f in top6]
    ax6.barh(labels, vals, color='#f59e0b', alpha=0.85, edgecolor='white', linewidth=0.6)
    for i, v in enumerate(vals):
        ax6.text(v + 0.001, i, f'{v:.3f}', va='center', fontsize=9, fontweight='bold')
    ax6.set_xlabel('Avg Importance', fontsize=9)
    ax6.set_title('Top Features (Both Models)', fontweight='bold', fontsize=10)
    ax6.xaxis.grid(True, color=GRID_COLOR); ax6.set_axisbelow(True)

    path = os.path.join(CHARTS_DIR, 'summary_dashboard.png')
    plt.savefig(path); plt.close()
    print(f'Saved: {path}')


# ── Run all ───────────────────────────────────────────────────────────────────
if __name__ == '__main__':
    # Delete old charts first
    for f in os.listdir(CHARTS_DIR):
        if f.endswith('.png'):
            os.remove(os.path.join(CHARTS_DIR, f))
            print(f'Deleted old chart: {f}')

    print('\nGenerating updated charts...')
    plot_confusion_matrices()
    plot_per_class_metrics()
    plot_roc_curves()
    plot_precision_recall_curves()
    plot_feature_importance()
    plot_cv_scores()
    plot_class_distribution()
    plot_calibration_curves()
    plot_summary_dashboard()
    print(f'\nAll charts saved to: {CHARTS_DIR}')
