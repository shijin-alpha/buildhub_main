import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch
import numpy as np

plt.rcParams.update({
    'font.family': 'serif',
    'font.serif': ['Times New Roman', 'DejaVu Serif'],
    'font.size': 8,
})

# ══════════════════════════════════════════════════════════════
# FIGURE 1 — WORKFLOW DIAGRAM
# ══════════════════════════════════════════════════════════════

fig1, ax = plt.subplots(figsize=(6.5, 12.0))
ax.set_xlim(0, 10)
ax.set_ylim(0, 24)
ax.axis('off')

def draw_box(ax, x, y, w, h, title, lines=None,
             fc='white', ec='black', lw=1.1,
             title_fs=8.2, body_fs=7.1):
    rect = FancyBboxPatch((x, y), w, h,
                          boxstyle="square,pad=0",
                          facecolor=fc, edgecolor=ec,
                          linewidth=lw, zorder=2)
    ax.add_patch(rect)
    if lines:
        ax.text(x + w/2, y + h - 0.22, title,
                ha='center', va='top', fontsize=title_fs,
                fontweight='bold', zorder=3)
        ax.text(x + w/2, y + h - 0.52, '\n'.join(lines),
                ha='center', va='top', fontsize=body_fs,
                linespacing=1.5, zorder=3)
    else:
        ax.text(x + w/2, y + h/2, title,
                ha='center', va='center', fontsize=title_fs,
                fontweight='bold', zorder=3)

def draw_arrow(ax, x, y0, y1):
    ax.annotate('', xy=(x, y1), xytext=(x, y0),
                arrowprops=dict(arrowstyle='->', color='black',
                                lw=1.0, mutation_scale=10),
                zorder=4)

BX, BW, GAP = 0.6, 8.8, 0.28

blocks = [
    ("(1) Homeowner Input Data",
     ["plot size  |  building size  |  number of floors",
      "total budget  |  district  |  start month  |  planned duration"],
     1.45),
    ("(2) Feature Engineering",
     ["budget_per_sqft  |  design_complexity_score",
      "monsoon_exposure_score  |  effective_monsoon_score  |  district_risk_tier"],
     1.45),
    ("(3) Rule-Based Scoring",
     ["cost score  |  time score  |  complexity score",
      "Threshold-based heuristic rules (domain knowledge)"],
     1.35),
    ("(4) ML Prediction",
     ["Trained Random Forest classifier",
      "Output: cost probability  |  time probability"],
     1.35),
    ("(5) Adaptive Blending",
     ["Confidence-based weighting of rule score and ML probability",
      "ML weight: 0.40 (confident)  /  0.15 (uncertain)"],
     1.35),
    ("(6) Safety Overrides",
     ["Hard rules: extreme budget deficit, monsoon season, high complexity",
      "=> force High risk regardless of blend output"],
     1.35),
    ("(7) Final Risk Output",
     ["Cost Risk:  Low  /  Medium  /  High",
      "Time Risk:  Low  /  Medium  /  High"],
     1.35),
    ("(8) SHAP Explanation",
     ["Per-instance feature importance via TreeExplainer",
      "Top contributing factors identified for each prediction"],
     1.35),
    ("(9) Counterfactual Recommendations",
     ["Actionable suggestions to reduce predicted risk level",
      "What-if scenario output presented to homeowner"],
     1.35),
]

top = 23.6
positions = []
y = top
for _, _, h in blocks:
    positions.append(y - h)
    y -= (h + GAP)

shades = ['#efefef', 'white'] * 10
mid_x = BX + BW / 2

for i, (title, lines, h) in enumerate(blocks):
    draw_box(ax, BX, positions[i], BW, h, title, lines, fc=shades[i])

for i in range(len(blocks) - 1):
    y_bottom = positions[i]
    y_top_next = positions[i+1] + blocks[i+1][2]
    draw_arrow(ax, mid_x, y_bottom, y_top_next)

fig1.tight_layout(pad=0.2)
fig1.savefig('fig1_buildhub_workflow.png', dpi=300,
             bbox_inches='tight', facecolor='white')
fig1.savefig('fig1_buildhub_workflow.svg',
             bbox_inches='tight', facecolor='white')
print("Fig 1 saved.")
plt.close(fig1)


# ══════════════════════════════════════════════════════════════
# FIGURE 2 — ABLATION STUDY
# ══════════════════════════════════════════════════════════════

variants  = ['Rule-Only', 'ML-Only', 'Hybrid\nFixed', 'Hybrid\nAdaptive']
cost_vals = [0.3822, 0.7270, 0.2468, 0.2466]
time_vals = [0.2732, 0.7391, 0.5506, 0.5484]

x     = np.arange(len(variants))
width = 0.30

fig2, ax2 = plt.subplots(figsize=(5.5, 3.6))

b1 = ax2.bar(x - width/2, cost_vals, width,
             label='Cost Overrun',
             color='#222222', edgecolor='black', linewidth=0.7)
b2 = ax2.bar(x + width/2, time_vals, width,
             label='Time Delay',
             color='#bbbbbb', edgecolor='black', linewidth=0.7,
             hatch='///')

for bar in list(b1) + list(b2):
    h = bar.get_height()
    ax2.text(bar.get_x() + bar.get_width() / 2,
             h + 0.013, f'{h:.4f}',
             ha='center', va='bottom', fontsize=6.2)

ax2.set_ylabel('Weighted F1-Score', fontsize=8.5)
ax2.set_xlabel('System Variant', fontsize=8.5)
ax2.set_xticks(x)
ax2.set_xticklabels(variants, fontsize=8.0)
ax2.set_ylim(0, 0.92)
ax2.yaxis.set_tick_params(labelsize=7.5)
ax2.legend(fontsize=7.5, frameon=True, edgecolor='black', loc='upper right')
ax2.spines['top'].set_visible(False)
ax2.spines['right'].set_visible(False)
ax2.grid(axis='y', linestyle='--', linewidth=0.45, alpha=0.55)
ax2.set_axisbelow(True)

fig2.tight_layout(pad=0.4)
fig2.savefig('fig2_ablation_study.png', dpi=300,
             bbox_inches='tight', facecolor='white')
fig2.savefig('fig2_ablation_study.svg',
             bbox_inches='tight', facecolor='white')
print("Fig 2 saved.")
plt.close(fig2)

print("Done.")
