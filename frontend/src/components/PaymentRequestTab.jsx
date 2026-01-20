import React from 'react';
import StagePaymentWithdrawals from './StagePaymentWithdrawals';
import { useToast } from './ToastProvider.jsx';

const PaymentRequestTab = ({ 
  projectId, 
  contractorId, 
  totalProjectCost = 0,
  showHeader = true 
}) => {
  const toast = useToast();

  const handleWithdrawalRequested = (data) => {
    toast.success(
      `Payment withdrawal request submitted successfully! ₹${data.requested_amount.toLocaleString()} for ${data.stage_name} stage`,
      { duration: 5000 }
    );
  };

  return (
    <div className="payment-request-tab">
      {showHeader && (
        <div className="tab-header">
          <h3>💰 Request Stage Payment</h3>
          <p>Submit payment requests for completed construction stages</p>
        </div>
      )}
      
      {projectId && contractorId ? (
        <StagePaymentWithdrawals 
          projectId={projectId}
          contractorId={contractorId}
          totalProjectCost={totalProjectCost}
          onWithdrawalRequested={handleWithdrawalRequested}
        />
      ) : (
        <div className="payment-request-loading">
          <div className="loading-message">
            <div className="loading-icon">💼</div>
            <h4>Select a Project</h4>
            <p>Please select a project to view and request stage payments</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default PaymentRequestTab;