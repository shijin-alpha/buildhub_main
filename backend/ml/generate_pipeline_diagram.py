"""
Simple clean pipeline diagram — actual system flow.
"""
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch
import os

BASE_DIR   = os.path.dirname(os.path.abspath(__file__))
CHARTS_DIR = os.path.join(BASE_DIR, 'charts')
os.makedirs(CHARTS_DIR, exist_ok=True)

fig, ax = plt.subplots(figsize=(16, 4.5))
ax.set_xlim(0, 16)
ax.set_ylim(0, 4.5)
ax.axis('off')
fig.patch.set_facecolor('#ffffff')

# Nodes: (x_center, y_center, label, color)
NODES = [
    (1.2,  2.25, 'User\nInput',           '#1e3a5f'),
    (3.2,  2.25, 'Feature\nEngineering',  '#1d4ed8'),
    (5.5,  3.30, 'Rule-Based\nEngine',    '#7c3aed'),
    (5.5,  1.20, 'GBM\nModels',           '#0369a1'),
    (8.2,  2.25, 'Confidence\nCheck',     '#b45309'),
    (10.4, 2.25, 'Adaptive\nBlending',    '#0f766e'),
    (12.6, 2.25, 'Safety\nOverride',      '#b91c1c'),
    (14.8, 2.25, 'SHAP +\nRisk Output',   '#15803d'),
]

W, H = 1.55, 1.10

for cx, cy, label, color in NODES:
    ax.add_patch(FancyBboxPatch(
        (cx - W/2, cy - H/2), W, H,
        boxstyle='round,pad=0.08',
        facecolor=color, edgecolor='white', linewidth=2, zorder=3))
    ax.text(cx, cy, label, ha='center', va='center',
            fontsize=10, fontweight='bold', color='white',
            zorder=4, linespacing=1.4)

def arr(x1, y1, x2, y2, rad=0.0):
    ax.annotate('', xy=(x2, y2), xytext=(x1, y1),
                arrowprops=dict(arrowstyle='->', color='#64748b', lw=2.0,
                                connectionstyle=f'arc3,rad={rad}',
                                mutation_scale=18), zorder=2)

# Input → FE
arr(1.2 + W/2, 2.25,  3.2 - W/2, 2.25)
# FE → Rule (up-left)
arr(3.2 + W/2, 2.25,  5.5 - W/2, 3.30, rad=-0.25)
# FE → GBM (down-left)
arr(3.2 + W/2, 2.25,  5.5 - W/2, 1.20, rad=0.25)
# Rule → Confidence
arr(5.5 + W/2, 3.30,  8.2 - W/2, 2.25, rad=0.25)
# GBM → Confidence
arr(5.5 + W/2, 1.20,  8.2 - W/2, 2.25, rad=-0.25)
# Confidence → Blending
arr(8.2 + W/2, 2.25, 10.4 - W/2, 2.25)
# Blending → Override
arr(10.4 + W/2, 2.25, 12.6 - W/2, 2.25)
# Override → Output
arr(12.6 + W/2, 2.25, 14.8 - W/2, 2.25)

# Title
ax.text(8.0, 4.20,
        'Hybrid AI Risk Prediction Pipeline — BuildHub',
        ha='center', va='center', fontsize=12,
        fontweight='bold', color='#0f172a')

plt.tight_layout(pad=0.3)
path = os.path.join(CHARTS_DIR, 'pipeline_diagram.png')
plt.savefig(path, dpi=200, facecolor='white')
plt.close()
print(f'Saved: {path}')
