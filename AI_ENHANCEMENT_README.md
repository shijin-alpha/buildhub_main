# Room Improvement AI Enhancement

This enhancement adds computer vision capabilities to your existing Room Improvement Assistant system using a hybrid AI approach that combines pretrained object detection, image feature extraction, and rule-based spatial reasoning.

## 🎯 Overview

The enhancement integrates seamlessly with your existing PHP/React system by adding:

- **Object Detection**: YOLOv8 model detects furniture and interior items
- **Spatial Analysis**: Converts bounding boxes to coarse spatial zones
- **Rule-Based Reasoning**: Generates explainable placement guidance
- **Visual Enhancement**: OpenCV-based image feature extraction
- **Fallback Support**: System continues working if AI service is unavailable

## 🏗️ Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   React Frontend │    │   PHP Backend    │    │  Python AI      │
│                 │    │                  │    │  Service        │
│ - Upload Image  │───▶│ - Existing Rules │───▶│ - YOLOv8        │
│ - Display Results│    │ - Visual Analysis│    │ - OpenCV        │
│                 │    │ - AI Integration │    │ - Spatial Rules │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 🚀 Quick Start

### 1. Install Python Dependencies

```bash
cd ai_service
python -m pip install -r requirements.txt
```

Or use the setup script:
```bash
cd ai_service
python setup.py
```

### 2. Start the AI Service

**Windows:**
```bash
cd ai_service
start_service.bat
```

**Linux/Mac:**
```bash
cd ai_service
./start_service.sh
```

**Manual start:**
```bash
cd ai_service
python main.py
```

The service will start on `http://127.0.0.1:8000`

### 3. Test the Integration

Open in your browser:
```
http://localhost/buildhub/test_ai_integration.php
```

This will test all components and show integration status.

### 4. Use the Enhanced System

Your existing room improvement system now automatically uses AI enhancements when available. No changes needed to your frontend or existing APIs.

## 📋 Requirements

### Python Requirements (AI Service)
- Python 3.9+
- FastAPI
- OpenCV (cv2)
- NumPy
- Ultralytics (YOLOv8)
- Pillow

### PHP Requirements (Existing System)
- PHP 7.4+
- GD extension (recommended)
- cURL extension
- Your existing dependencies

## 🔧 Configuration

### AI Service Configuration

Edit `ai_service/main.py` to configure:

```python
# Service settings
HOST = "127.0.0.1"
PORT = 8000

# Object detection settings
CONFIDENCE_THRESHOLD = 0.5
```

### PHP Integration Configuration

Edit `backend/utils/AIServiceConnector.php`:

```php
// AI service URL
$ai_service_url = 'http://127.0.0.1:8000';

// Request timeout
$timeout = 30;

// Enable fallback mode
$fallback_enabled = true;
```

## 🧪 Testing

### Run Integration Tests

```bash
# Test AI service directly
curl http://127.0.0.1:8000/health

# Test full integration
php test_ai_integration.php
```

### Test with Sample Image

1. Place a room image in the project root as `test_room_image.jpg`
2. Run the integration test
3. Check results for object detection and spatial analysis

## 📊 API Endpoints

### AI Service Endpoints

- `GET /` - Service info
- `GET /health` - Health check
- `POST /analyze-room` - Full room analysis
- `POST /detect-objects` - Object detection only

### Enhanced PHP APIs

Your existing APIs now return additional data:

```json
{
  "analysis": {
    // ... existing analysis data
    "ai_enhancements": {
      "detected_objects": { /* object detection results */ },
      "spatial_analysis": { /* spatial zones and relationships */ },
      "spatial_guidance": { /* placement recommendations */ },
      "enhanced_visual_features": { /* OpenCV analysis */ }
    }
  }
}
```

## 🔄 Fallback Behavior

The system is designed to be resilient:

1. **AI Service Available**: Full computer vision enhancement
2. **AI Service Unavailable**: Graceful fallback to existing rule-based system
3. **Partial Failure**: Individual components fail safely

## 🛠️ Troubleshooting

### AI Service Won't Start

1. **Check Python version**: Must be 3.9+
   ```bash
   python --version
   ```

2. **Install dependencies**:
   ```bash
   cd ai_service
   pip install -r requirements.txt
   ```

3. **Check port availability**:
   ```bash
   netstat -an | grep 8000
   ```

### Object Detection Not Working

1. **Check model download**:
   ```python
   from ultralytics import YOLO
   model = YOLO('yolov8n.pt')  # Should download automatically
   ```

2. **Verify image format**: Only JPG and PNG supported

3. **Check confidence threshold**: Lower if no objects detected

### PHP Integration Issues

1. **Check cURL extension**:
   ```php
   var_dump(extension_loaded('curl'));
   ```

2. **Test AI service connection**:
   ```php
   $connector = new AIServiceConnector();
   $status = $connector->testConnection();
   var_dump($status);
   ```

3. **Check file permissions**: Ensure uploaded images are readable

## 📈 Performance Optimization

### AI Service Performance

- **Model Selection**: YOLOv8n (nano) for speed, YOLOv8s for accuracy
- **Image Resizing**: Resize large images before processing
- **Batch Processing**: Process multiple images together
- **Caching**: Cache results for identical images

### PHP Integration Performance

- **Timeout Settings**: Adjust based on image size and server performance
- **Async Processing**: Consider background processing for large images
- **Fallback Caching**: Cache fallback responses to reduce load

## 🔒 Security Considerations

### AI Service Security

- **Local Network Only**: Service runs on 127.0.0.1 by default
- **Input Validation**: All inputs are validated before processing
- **File Cleanup**: Temporary files are automatically cleaned up
- **Error Handling**: Sensitive information is not exposed in errors

### PHP Integration Security

- **File Upload Validation**: Existing validation is maintained
- **Request Sanitization**: All data sent to AI service is sanitized
- **Error Logging**: Errors are logged but not exposed to users

## 🚀 Advanced Usage

### Custom Object Detection

To detect custom objects, you would need to:

1. Train a custom YOLOv8 model (not included in this implementation)
2. Replace the model loading in `object_detector.py`
3. Update the class mappings

### Extended Spatial Rules

Add custom spatial reasoning rules in `rule_engine.py`:

```python
def _load_custom_rules(self):
    return [
        {"rule": "custom_rule", "description": "Your custom rule"}
    ]
```

### Visual Feature Extensions

Extend visual processing in `visual_processor.py`:

```python
def _analyze_custom_features(self, image):
    # Your custom visual analysis
    return custom_features
```

## 📝 Development Notes

### Code Structure

```
ai_service/
├── main.py                 # FastAPI application
├── modules/
│   ├── object_detector.py  # YOLOv8 object detection
│   ├── spatial_analyzer.py # Spatial zone analysis
│   ├── visual_processor.py # OpenCV visual processing
│   └── rule_engine.py      # Spatial reasoning rules
├── requirements.txt        # Python dependencies
└── setup.py               # Setup script

backend/utils/
├── AIServiceConnector.php  # PHP-Python integration
└── EnhancedRoomAnalyzer.php # Enhanced with AI integration
```

### Key Design Principles

1. **Backward Compatibility**: Existing system continues to work
2. **Graceful Degradation**: AI unavailability doesn't break the system
3. **Explainable AI**: All recommendations include reasoning
4. **Modular Design**: Components can be enhanced independently
5. **Safety First**: Conservative recommendations with safety notes

## 🤝 Contributing

To extend the AI capabilities:

1. **Object Detection**: Modify `object_detector.py` for new object types
2. **Spatial Rules**: Add rules in `rule_engine.py`
3. **Visual Features**: Extend `visual_processor.py`
4. **Integration**: Update `AIServiceConnector.php` for new features

## 📄 License

This enhancement maintains the same license as your existing system.

## 🆘 Support

For issues with the AI enhancement:

1. Check the integration test results
2. Review the troubleshooting section
3. Check service logs in the AI service console
4. Verify all requirements are installed

The system is designed to be self-diagnosing - the integration test will identify most common issues.