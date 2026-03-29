from risk_predictor import ConstructionRiskPredictor

p = ConstructionRiskPredictor()
p.load_models()

tests = [
    ("Previous: 750k, 3 floors, 2 months (should be HIGH/HIGH)", {
        "plot_size_sqft": 4356, "building_size_sqft": 2500, "num_floors": 3,
        "budget_amount": 750000, "num_bedrooms": 3, "num_bathrooms": 3,
        "planned_duration_months": 2
    }),
    ("Current: 25L, 3 floors, 6-12 months (user's new project)", {
        "plot_size_sqft": 4356, "building_size_sqft": 2500, "num_floors": 3,
        "budget_amount": 2500000, "num_bedrooms": 4, "num_bathrooms": 4,
        "planned_duration_months": 9
    }),
    ("Good project: 40L, 2 floors, 18 months", {
        "plot_size_sqft": 4356, "building_size_sqft": 2000, "num_floors": 2,
        "budget_amount": 4000000, "num_bedrooms": 3, "num_bathrooms": 2,
        "planned_duration_months": 18
    }),
]

for label, data in tests:
    r = p.predict_risks(data)
    cost = r["cost_overrun_risk"]
    time = r["time_delay_risk"]
    bps = data["budget_amount"] / data["building_size_sqft"]
    print(f"{label}")
    print(f"  budget/sqft = {bps:.0f}")
    print(f"  Cost: {cost['risk_level']}  probs={cost['probabilities']}")
    print(f"  Time: {time['risk_level']}  probs={time['probabilities']}")
    if cost.get("override"):
        print(f"  Cost override: {cost['override']}")
    if time.get("override"):
        print(f"  Time override: {time['override']}")
    print()
