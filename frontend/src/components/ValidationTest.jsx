import React, { useState } from 'react';
import { 
  validateLabourData, 
  validateProgressUpdate, 
  generateProductivityInsights,
  calculateOptimalWage,
  standardHourlyRates 
} from '../utils/progressValidation';

const ValidationTest = () => {
  const [testResults, setTestResults] = useState(null);
  const [loading, setLoading] = useState(false);

  // Sample test data
  const sampleLabourData = [
    {
      worker_type: 'Mason',
      worker_count: 5,
      hours_worked: 8,
      overtime_hours: 2,
      absent_count: 1,
      hourly_rate: 500,
      total_wages: 22500,
      productivity_rating: 4,
      safety_compliance: 'good',
      remarks: 'Good progress on foundation work'
    },
    {
      worker_type: 'Helper',
      worker_count: 8,
      hours_worked: 8,
      overtime_hours: 0,
      absent_count: 0,
      hourly_rate: 300,
      total_wages: 19200,
      productivity_rating: 5,
      safety_compliance: 'excellent',
      remarks: 'Excellent support work'
    },
    {
      worker_type: 'Electrician',
      worker_count: 2,
      hours_worked: 6,
      overtime_hours: 0,
      absent_count: 0,
      hourly_rate: 600,
      total_wages: 7200,
      productivity_rating: 2,
      safety_compliance: 'needs_improvement',
      remarks: '' // This should trigger a warning
    }
  ];

  const sampleProgressData = {
    construction_stage: 'Foundation',
    work_done_today: 'Completed foundation excavation and started concrete pouring',
    incremental_completion_percentage: 15,
    weather_condition: 'Sunny',
    materials_used: 'Cement, Steel bars, Aggregate',
    site_issues: '',
    labour_data: sampleLabourData
  };

  const runValidationTests = async () => {
    setLoading(true);
    
    try {
      // Test labour validation
      const labourValidation = validateLabourData(sampleLabourData);
      
      // Test progress validation
      const progressValidation = validateProgressUpdate(sampleProgressData);
      
      // Test productivity insights
      const insights = generateProductivityInsights(sampleLabourData);
      
      // Test wage calculations
      const wageCalculations = sampleLabourData.map(labour => ({
        workerType: labour.worker_type,
        calculated: calculateOptimalWage(
          labour.worker_type,
          labour.hours_worked,
          labour.overtime_hours,
          labour.worker_count,
          'urban'
        ),
        actual: {
          hourlyRate: labour.hourly_rate,
          totalWages: labour.total_wages
        }
      }));

      // Test standard rates
      const rateComparison = Object.entries(standardHourlyRates).map(([type, rate]) => ({
        workerType: type,
        standardRate: rate,
        category: rate >= 700 ? 'Skilled' : rate >= 400 ? 'Semi-skilled' : 'Unskilled'
      }));

      setTestResults({
        labourValidation,
        progressValidation,
        insights,
        wageCalculations,
        rateComparison,
        timestamp: new Date().toISOString()
      });
    } catch (error) {
      console.error('Validation test error:', error);
      setTestResults({
        error: error.message,
        timestamp: new Date().toISOString()
      });
    } finally {
      setLoading(false);
    }
  };

  const clearResults = () => {
    setTestResults(null);
  };

  return (
    <div style={{ padding: '20px', maxWidth: '1200px', margin: '0 auto' }}>
      <div style={{ 
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        color: 'white',
        padding: '20px',
        borderRadius: '12px',
        marginBottom: '20px'
      }}>
        <h1 style={{ margin: '0 0 10px 0' }}>🧪 Progress Validation System Test</h1>
        <p style={{ margin: 0, opacity: 0.9 }}>
          Test the enhanced labour tracking validation system with sample data
        </p>
      </div>

      <div style={{ display: 'flex', gap: '10px', marginBottom: '20px' }}>
        <button
          onClick={runValidationTests}
          disabled={loading}
          style={{
            background: loading ? '#6c757d' : 'linear-gradient(135deg, #28a745, #20c997)',
            color: 'white',
            border: 'none',
            padding: '12px 24px',
            borderRadius: '8px',
            cursor: loading ? 'not-allowed' : 'pointer',
            fontWeight: '600',
            fontSize: '14px'
          }}
        >
          {loading ? '🔄 Running Tests...' : '▶️ Run Validation Tests'}
        </button>
        
        {testResults && (
          <button
            onClick={clearResults}
            style={{
              background: '#dc3545',
              color: 'white',
              border: 'none',
              padding: '12px 24px',
              borderRadius: '8px',
              cursor: 'pointer',
              fontWeight: '600',
              fontSize: '14px'
            }}
          >
            🗑️ Clear Results
          </button>
        )}
      </div>

      {testResults && (
        <div style={{ display: 'grid', gap: '20px' }}>
          {testResults.error ? (
            <div style={{
              background: '#f8d7da',
              border: '1px solid #f5c6cb',
              color: '#721c24',
              padding: '15px',
              borderRadius: '8px'
            }}>
              <h3>❌ Test Error</h3>
              <p>{testResults.error}</p>
            </div>
          ) : (
            <>
              {/* Labour Validation Results */}
              <div style={{
                background: 'white',
                border: '1px solid #e9ecef',
                borderRadius: '12px',
                padding: '20px',
                boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
              }}>
                <h3 style={{ color: '#2c3e50', marginBottom: '15px' }}>
                  👷 Labour Validation Results
                </h3>
                
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                  <div>
                    <h4 style={{ color: '#dc3545', marginBottom: '10px' }}>
                      Errors ({testResults.labourValidation.errors.length})
                    </h4>
                    {testResults.labourValidation.errors.length > 0 ? (
                      <ul style={{ color: '#dc3545', margin: 0, paddingLeft: '20px' }}>
                        {testResults.labourValidation.errors.map((error, index) => (
                          <li key={index}>{error}</li>
                        ))}
                      </ul>
                    ) : (
                      <p style={{ color: '#28a745', margin: 0 }}>✅ No errors found</p>
                    )}
                  </div>
                  
                  <div>
                    <h4 style={{ color: '#ffc107', marginBottom: '10px' }}>
                      Warnings ({testResults.labourValidation.warnings.length})
                    </h4>
                    {testResults.labourValidation.warnings.length > 0 ? (
                      <ul style={{ color: '#856404', margin: 0, paddingLeft: '20px' }}>
                        {testResults.labourValidation.warnings.map((warning, index) => (
                          <li key={index}>{warning}</li>
                        ))}
                      </ul>
                    ) : (
                      <p style={{ color: '#28a745', margin: 0 }}>✅ No warnings</p>
                    )}
                  </div>
                </div>

                <div style={{ 
                  marginTop: '20px', 
                  padding: '15px', 
                  background: '#f8f9fa', 
                  borderRadius: '8px' 
                }}>
                  <h4 style={{ color: '#2c3e50', marginBottom: '10px' }}>📊 Summary</h4>
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: '10px' }}>
                    <div><strong>Total Workers:</strong> {testResults.labourValidation.summary.totalWorkers}</div>
                    <div><strong>Regular Hours:</strong> {testResults.labourValidation.summary.totalRegularHours}</div>
                    <div><strong>Overtime Hours:</strong> {testResults.labourValidation.summary.totalOvertimeHours}</div>
                    <div><strong>Total Wages:</strong> ₹{testResults.labourValidation.summary.totalWages}</div>
                    <div><strong>Avg Productivity:</strong> {testResults.labourValidation.summary.averageProductivity.toFixed(1)}/5</div>
                    <div><strong>Total Absent:</strong> {testResults.labourValidation.summary.totalAbsent}</div>
                  </div>
                </div>
              </div>

              {/* Progress Validation Results */}
              <div style={{
                background: 'white',
                border: '1px solid #e9ecef',
                borderRadius: '12px',
                padding: '20px',
                boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
              }}>
                <h3 style={{ color: '#2c3e50', marginBottom: '15px' }}>
                  📋 Progress Update Validation
                </h3>
                
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                  <div>
                    <h4 style={{ color: '#dc3545', marginBottom: '10px' }}>
                      Errors ({testResults.progressValidation.errors.length})
                    </h4>
                    {testResults.progressValidation.errors.length > 0 ? (
                      <ul style={{ color: '#dc3545', margin: 0, paddingLeft: '20px' }}>
                        {testResults.progressValidation.errors.map((error, index) => (
                          <li key={index}>{error}</li>
                        ))}
                      </ul>
                    ) : (
                      <p style={{ color: '#28a745', margin: 0 }}>✅ No errors found</p>
                    )}
                  </div>
                  
                  <div>
                    <h4 style={{ color: '#ffc107', marginBottom: '10px' }}>
                      Warnings ({testResults.progressValidation.warnings.length})
                    </h4>
                    {testResults.progressValidation.warnings.length > 0 ? (
                      <ul style={{ color: '#856404', margin: 0, paddingLeft: '20px' }}>
                        {testResults.progressValidation.warnings.map((warning, index) => (
                          <li key={index}>{warning}</li>
                        ))}
                      </ul>
                    ) : (
                      <p style={{ color: '#28a745', margin: 0 }}>✅ No warnings</p>
                    )}
                  </div>
                </div>
              </div>

              {/* Productivity Insights */}
              <div style={{
                background: 'white',
                border: '1px solid #e9ecef',
                borderRadius: '12px',
                padding: '20px',
                boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
              }}>
                <h3 style={{ color: '#2c3e50', marginBottom: '15px' }}>
                  💡 Productivity Insights
                </h3>
                
                {testResults.insights.length > 0 ? (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                    {testResults.insights.map((insight, index) => (
                      <div
                        key={index}
                        style={{
                          padding: '12px 16px',
                          borderRadius: '6px',
                          background: insight.type === 'positive' ? '#d4edda' :
                                     insight.type === 'warning' ? '#fff3cd' :
                                     insight.type === 'danger' ? '#f8d7da' : '#d1ecf1',
                          color: insight.type === 'positive' ? '#155724' :
                                 insight.type === 'warning' ? '#856404' :
                                 insight.type === 'danger' ? '#721c24' : '#0c5460',
                          border: `1px solid ${
                            insight.type === 'positive' ? '#c3e6cb' :
                            insight.type === 'warning' ? '#ffeaa7' :
                            insight.type === 'danger' ? '#f5c6cb' : '#bee5eb'
                          }`
                        }}
                      >
                        {insight.message}
                      </div>
                    ))}
                  </div>
                ) : (
                  <p style={{ color: '#6c757d', margin: 0 }}>No specific insights generated</p>
                )}
              </div>

              {/* Wage Calculations */}
              <div style={{
                background: 'white',
                border: '1px solid #e9ecef',
                borderRadius: '12px',
                padding: '20px',
                boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
              }}>
                <h3 style={{ color: '#2c3e50', marginBottom: '15px' }}>
                  💰 Wage Calculation Verification
                </h3>
                
                <div style={{ overflowX: 'auto' }}>
                  <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                      <tr style={{ background: '#f8f9fa' }}>
                        <th style={{ padding: '12px', textAlign: 'left', border: '1px solid #dee2e6' }}>Worker Type</th>
                        <th style={{ padding: '12px', textAlign: 'right', border: '1px solid #dee2e6' }}>Standard Rate</th>
                        <th style={{ padding: '12px', textAlign: 'right', border: '1px solid #dee2e6' }}>Actual Rate</th>
                        <th style={{ padding: '12px', textAlign: 'right', border: '1px solid #dee2e6' }}>Calculated Wages</th>
                        <th style={{ padding: '12px', textAlign: 'right', border: '1px solid #dee2e6' }}>Actual Wages</th>
                        <th style={{ padding: '12px', textAlign: 'center', border: '1px solid #dee2e6' }}>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {testResults.wageCalculations.map((calc, index) => {
                        const isAccurate = Math.abs(calc.calculated.totalWages - calc.actual.totalWages) < 100;
                        return (
                          <tr key={index}>
                            <td style={{ padding: '12px', border: '1px solid #dee2e6' }}>{calc.workerType}</td>
                            <td style={{ padding: '12px', textAlign: 'right', border: '1px solid #dee2e6' }}>₹{calc.calculated.hourlyRate}</td>
                            <td style={{ padding: '12px', textAlign: 'right', border: '1px solid #dee2e6' }}>₹{calc.actual.hourlyRate}</td>
                            <td style={{ padding: '12px', textAlign: 'right', border: '1px solid #dee2e6' }}>₹{calc.calculated.totalWages}</td>
                            <td style={{ padding: '12px', textAlign: 'right', border: '1px solid #dee2e6' }}>₹{calc.actual.totalWages}</td>
                            <td style={{ 
                              padding: '12px', 
                              textAlign: 'center', 
                              border: '1px solid #dee2e6',
                              color: isAccurate ? '#28a745' : '#dc3545',
                              fontWeight: '600'
                            }}>
                              {isAccurate ? '✅ Accurate' : '⚠️ Check'}
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>

              {/* Standard Rates Reference */}
              <div style={{
                background: 'white',
                border: '1px solid #e9ecef',
                borderRadius: '12px',
                padding: '20px',
                boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
              }}>
                <h3 style={{ color: '#2c3e50', marginBottom: '15px' }}>
                  📋 Standard Hourly Rates Reference
                </h3>
                
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '15px' }}>
                  {testResults.rateComparison.map((rate, index) => (
                    <div
                      key={index}
                      style={{
                        padding: '12px',
                        border: '1px solid #e9ecef',
                        borderRadius: '6px',
                        background: rate.category === 'Skilled' ? '#e8f5e8' :
                                   rate.category === 'Semi-skilled' ? '#fff3cd' : '#f8f9fa'
                      }}
                    >
                      <div style={{ fontWeight: '600', marginBottom: '4px' }}>{rate.workerType}</div>
                      <div style={{ fontSize: '14px', color: '#6c757d' }}>₹{rate.standardRate}/hour</div>
                      <div style={{ 
                        fontSize: '12px', 
                        color: rate.category === 'Skilled' ? '#28a745' :
                               rate.category === 'Semi-skilled' ? '#856404' : '#6c757d',
                        fontWeight: '500'
                      }}>
                        {rate.category}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div style={{ 
                textAlign: 'center', 
                padding: '15px', 
                background: '#f8f9fa', 
                borderRadius: '8px',
                color: '#6c757d',
                fontSize: '14px'
              }}>
                Test completed at: {new Date(testResults.timestamp).toLocaleString()}
              </div>
            </>
          )}
        </div>
      )}
    </div>
  );
};

export default ValidationTest;