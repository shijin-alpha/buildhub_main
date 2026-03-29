# Quick Start Guide - FastAPI ML Service

Get the ML service running in 3 minutes.

## Step 1: Install Dependencies (1 minute)

### Windows
```bash
cd backend/ml_service
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

### Linux/Mac
```bash
cd backend/ml_service
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

## Step 2: Start the Service (30 seconds)

### Windows
```bash
start_service.bat
```

### Linux/Mac
```bash
chmod +x start_service.sh
./start_service.sh
```

You should see:
```
INFO:     Uvicorn running on http://0.0.0.0:8000
INFO:     Loading ML models...
INFO:     ✓ Models loaded successfully and ready for predictions
```

## Step 3: Test the Service (30 seconds)

Open a new terminal and run:

```bash
python test_service.py
```

Expected output:
```
✓ Service is healthy
✓ Prediction successful (50ms)
✓ Performance test completed
  Average: 75ms
🎉 All tests passed!
```

## Step 4: Test with Your Application

The PHP API (`backend/api/ml/predict_construction_risks.php`) now automatically uses the FastAPI service. No frontend changes needed!

Test from your React app:
1. Open the custom request form
2. Fill in project details
3. Click "Predict Risks"
4. You should see predictions in ~50-100ms (vs ~1600ms before)

## Troubleshooting

### "Cannot connect to service"
- Make sure the service is running: `curl http://localhost:8000/health`
- Check if port 8000 is available: `netstat -an | grep 8000`

### "Models not loaded"
- Verify model files exist: `ls -la ../ml/models/`
- Check for these files:
  - `cost_overrun_risk_model.pkl`
  - `time_delay_risk_model.pkl`
  - `model_metadata.json`

### "Module not found"
- Activate virtual environment first
- Reinstall dependencies: `pip install -r requirements.txt`

## What Changed?

### Before
```
React → PHP → exec(python script) → Load models → Predict
                     ↑_________________________________|
                     (~1600ms per request)
```

### After
```
React → PHP → HTTP → FastAPI (models in memory) → Predict
                            ↑___________________________|
                            (~50-100ms per request)
```

## Next Steps

1. ✓ Service is running
2. ✓ Tests pass
3. → Test with your React app
4. → Deploy to production (see README.md)

## Production Deployment

For production, use a process manager:

### systemd (Linux)
```bash
sudo systemctl enable ml-service
sudo systemctl start ml-service
```

### PM2 (Cross-platform)
```bash
pm2 start "uvicorn main:app --host 0.0.0.0 --port 8000" --name ml-service
pm2 save
```

### Docker
```bash
docker build -t ml-service .
docker run -d -p 8000:8000 ml-service
```

See `README.md` for detailed deployment instructions.
