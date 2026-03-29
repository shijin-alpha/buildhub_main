#!/bin/bash
# Start FastAPI ML Service on Linux/Mac

echo "Starting Construction Risk Assessment ML Service..."
echo ""

# Check if Python is installed
if ! command -v python3 &> /dev/null; then
    echo "ERROR: Python 3 is not installed"
    exit 1
fi

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo "Creating virtual environment..."
    python3 -m venv venv
    if [ $? -ne 0 ]; then
        echo "ERROR: Failed to create virtual environment"
        exit 1
    fi
fi

# Activate virtual environment
source venv/bin/activate

# Install dependencies
echo "Installing dependencies..."
pip install -r requirements.txt

# Start the service
echo ""
echo "Starting FastAPI service on http://localhost:8000"
echo "Press Ctrl+C to stop the service"
echo ""
python -m uvicorn main:app --host 0.0.0.0 --port 8000 --reload
