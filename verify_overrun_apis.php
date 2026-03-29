<?php
/**
 * Verify Cost & Time Overrun API Endpoints
 * 
 * This script checks if all required API endpoints exist and are accessible
 */

header('Content-Type: application/json');

class APIVerifier {
    private $base_path;
    private $results = [];
    
    public function __construct() {
        $this->base_path = __DIR__;
    }
    
    public function verifyAll() {
        $this->results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'verification' => 'Cost & Time Overrun System API Endpoints',
            'checks' => []
        ];
        
        // Check ML prediction endpoint
        $this->checkFile(
            'ML Risk Prediction API',
            'backend/api/ml/predict_construction_risks.php',
            'Predicts cost overrun and time delay risks using ML models'
        );
        
        // Check budget summary endpoint
        $this->checkFile(
            'Budget Summary API',
            'backend/api/contractor/get_project_budget_summary.php',
            'Calculates real-time budget overrun/underrun'
        );
        
        // Check schedule tracking endpoint
        $this->checkFile(
            'Schedule Tracking API',
            'backend/api/schedule_tracking.php',
            'Manages planned and actual dates, calculates time overrun'
        );
        
        // Check ML models
        $this->checkFile(
            'Cost Overrun ML Model',
            'backend/ml/models/cost_overrun_risk_model.pkl',
            'Trained model for cost overrun prediction (94.7% accuracy)'
        );
        
        $this->checkFile(
            'Time Delay ML Model',
            'backend/ml/models/time_delay_risk_model.pkl',
            'Trained model for time delay prediction (98.9% accuracy)'
        );
        
        // Check database schema
        $this->checkFile(
            'Schedule Tracking Schema',
            'backend/database/schedule_tracking_schema.sql',
            'Database schema for schedule tracking features'
        );
        
        // Check frontend components
        $this->checkFile(
            'Risk Assessment Component',
            'frontend/src/components/RiskAssessmentPreview.jsx',
            'React component for displaying AI risk assessment'
        );
        
        // Check Python scripts
        $this->checkFile(
            'Risk Prediction Script',
            'backend/ml/predict_risks_api.py',
            'Python script that runs ML predictions'
        );
        
        $this->checkFile(
            'ML Training Script',
            'backend/ml/run_training.py',
            'Script to train/retrain ML models'
        );
        
        // Check training data
        $this->checkFile(
            'Cost Overrun Dataset',
            'backend/ml/data/cost_overrun_risk_dataset.csv',
            'Training data for cost overrun model (1000 samples)'
        );
        
        $this->checkFile(
            'Time Delay Dataset',
            'backend/ml/data/time_delay_risk_dataset.csv',
            'Training data for time delay model (1000 samples)'
        );
        
        // Generate summary
        $this->generateSummary();
        
        return $this->results;
    }
    
    private function checkFile($name, $path, $description) {
        $full_path = $this->base_path . '/' . $path;
        $exists = file_exists($full_path);
        
        $check = [
            'name' => $name,
            'path' => $path,
            'description' => $description,
            'exists' => $exists,
            'status' => $exists ? 'found' : 'missing'
        ];
        
        if ($exists) {
            $check['size'] = filesize($full_path);
            $check['size_formatted'] = $this->formatBytes($check['size']);
            $check['modified'] = date('Y-m-d H:i:s', filemtime($full_path));
        }
        
        $this->results['checks'][] = $check;
    }
    
    private function formatBytes($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    private function generateSummary() {
        $total = count($this->results['checks']);
        $found = 0;
        $missing = 0;
        
        foreach ($this->results['checks'] as $check) {
            if ($check['exists']) {
                $found++;
            } else {
                $missing++;
            }
        }
        
        $this->results['summary'] = [
            'total_files' => $total,
            'found' => $found,
            'missing' => $missing,
            'completion_percentage' => round(($found / $total) * 100, 2) . '%',
            'status' => $missing === 0 ? 'All components present ✅' : "Missing {$missing} component(s) ⚠️"
        ];
    }
}

// Run verification
try {
    $verifier = new APIVerifier();
    $results = $verifier->verifyAll();
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Verification failed',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
