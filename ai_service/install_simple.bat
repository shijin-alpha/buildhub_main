@echo off
echo Simple Installation for Room Improvement AI Service
echo.

REM Update pip first
echo Updating pip...
python -m pip install --upgrade pip

echo.
echo Installing compatible versions...

REM Install with more flexible versions
python -m pip install fastapi uvicorn opencv-python numpy ultralytics Pillow python-multipart pydantic

echo.
echo Testing installation...
python -c "import fastapi, uvicorn, cv2, numpy, ultralytics; print('✓ All modules imported successfully')"

if errorlevel 1 (
    echo.
    echo Some modules failed to import. Let's try individual installation:
    echo.
    python -m pip install --upgrade fastapi
    python -m pip install --upgrade uvicorn
    python -m pip install --upgrade opencv-python
    python -m pip install --upgrade numpy
    python -m pip install --upgrade ultralytics
    python -m pip install --upgrade Pillow
    python -m pip install --upgrade python-multipart
    python -m pip install --upgrade pydantic
)

echo.
echo Installation complete! Testing YOLOv8...
python -c "from ultralytics import YOLO; model = YOLO('yolov8n.pt'); print('✓ YOLOv8 ready')" 2>nul

echo.
echo Ready to start! Run: python main.py
pause