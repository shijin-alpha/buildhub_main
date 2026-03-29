# Windows Installation Fix for Python 3.13

## Problem
Python 3.13 is very new and some packages (like NumPy) don't have pre-built wheels yet, causing build errors on Windows without a C compiler.

## Solution Options

### Option 1: Use Existing ML Environment (Recommended)

Since you already have the ML dependencies installed in `backend/ml/`, you can reuse that environment:

```bash
# Deactivate current venv
deactivate

# Go to ml_service directory
cd backend/ml_service

# Remove the problematic venv
rmdir /s venv

# Create a new venv but install only FastAPI-specific packages
python -m venv venv
venv\Scripts\activate

# Install only the web framework packages (these have wheels for Python 3.13)
pip install fastapi uvicorn[standard] pydantic

# Copy the ML packages from the existing environment
# The service will use the system-wide or existing ML packages
```

Then modify the startup script to use the existing ML packages.

### Option 2: Install Pre-built Wheels Manually

Download pre-built wheels from https://www.lfd.uci.edu/~gohlke/pythonlibs/ for:
- NumPy
- Pandas  
- scikit-learn

Then install:
```bash
pip install numpy-1.26.4+mkl-cp313-cp313-win_amd64.whl
pip install pandas-2.1.4-cp313-cp313-win_amd64.whl
pip install scikit_learn-1.4.0-cp313-cp313-win_amd64.whl
pip install -r requirements.txt
```

### Option 3: Use Python 3.11 (Easiest)

Python 3.11 has pre-built wheels for all packages:

```bash
# Install Python 3.11 from python.org
# Then create venv with Python 3.11
py -3.11 -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

### Option 4: Run Without Virtual Environment

Use the system Python that already has the ML packages:

```bash
# Deactivate venv
deactivate

# Install FastAPI packages globally (or in existing environment)
pip install fastapi uvicorn[standard] pydantic

# Run the service directly
python main.py
```

## Recommended: Option 1 (Modified Approach)

Let me create a simplified requirements file that works with your setup:
