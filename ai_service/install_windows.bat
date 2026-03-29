@echo off
echo Installing Room Improvement AI Service Dependencies...
echo.

REM Check if Python is available
python --version >nul 2>&1
if errorlevel 1 (
    echo Error: Python is not installed or not in PATH
    echo Please install Python 3.9+ from https://python.org
    pause
    exit /b 1
)

echo Python found. Installing dependencies...
echo.

REM Install each package individually to handle any issues
echo Installing FastAPI...
python -m pip install "fastapi==0.104.1"
if errorlevel 1 goto :error

echo Installing Uvicorn...
python -m pip install "uvicorn==0.24.0"
if errorlevel 1 goto :error

echo Installing OpenCV...
python -m pip install "opencv-python==4.8.1.78"
if errorlevel 1 goto :error

echo Installing NumPy...
python -m pip install "numpy==1.24.4"
if errorlevel 1 goto :error

echo Installing Ultralytics (YOLOv8)...
python -m pip install "ultralytics==8.0.196"
if errorlevel 1 goto :error

echo Installing Pillow...
python -m pip install "Pillow==10.0.1"
if errorlevel 1 goto :error

echo Installing Python Multipart...
python -m pip install "python-multipart==0.0.6"
if errorlevel 1 goto :error

echo Installing Pydantic...
python -m pip install "pydantic==2.4.2"
if errorlevel 1 goto :error

echo.
echo ✓ All dependencies installed successfully!
echo.
echo Testing YOLOv8 model download...
python -c "from ultralytics import YOLO; model = YOLO('yolov8n.pt'); print('✓ YOLOv8 model ready')"
if errorlevel 1 (
    echo Warning: YOLOv8 model download may have failed
    echo This will be attempted again when the service starts
)

echo.
echo Installation complete! You can now start the service with:
echo   python main.py
echo.
pause
exit /b 0

:error
echo.
echo ✗ Installation failed. Please check the error messages above.
echo.
echo Common solutions:
echo 1. Make sure you have internet connection
echo 2. Try running as administrator
echo 3. Update pip: python -m pip install --upgrade pip
echo 4. Check if antivirus is blocking the installation
echo.
pause
exit /b 1