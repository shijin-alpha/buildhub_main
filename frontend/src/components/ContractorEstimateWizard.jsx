import React, { useState } from 'react';
import Stepper from './wizard/Stepper';
import WizardLayout from './wizard/WizardLayout';
import BuildHubSeal from './BuildHubSeal';

export default function ContractorEstimateWizard() {
  // Prefill layout_request_id from URL if provided (from dashboard CTA)
  React.useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const layout_request_id = params.get('layout_request_id');
    if (layout_request_id) setData(prev => ({ ...prev, layout_request_id }));
  }, []);
  const steps = ['Project', 'Materials', 'Costs', 'Timeline', 'Review', 'Submit'];
  const [step, setStep] = useState(0);
  const [data, setData] = useState({ layout_request_id: '', materials: '', cost_breakdown: '', total_cost: '', timeline: '', notes: '' });
  const [loading, setLoading] = useState(false);
  const next = () => setStep(s => Math.min(s + 1, steps.length - 1));
  const prev = () => setStep(s => Math.max(s - 1, 0));

  async function submit() {
    setLoading(true);
    try {
      const res = await fetch('/buildhub/backend/api/contractor/submit_proposal.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
      const json = await res.json();
      if (json.success) window.history.back(); else toast.error(json.message || 'Failed');
    } catch { toast.error('Network error'); } finally { setLoading(false); }
  }

  return (
    <WizardLayout
      title="Submit Cost Estimate"
      subtitle="Provide detailed materials and cost breakdown"
      stepper={<Stepper steps={steps} current={step} />}
      onBack={step > 0 ? prev : () => window.history.back()}
      onClose={() => window.history.back()}
    >
      {step === 0 && (
        <div className="section">
          <div className="section-header">Project</div>
          <div style={{ display: 'flex', justifyContent: 'center', marginBottom: '20px', padding: '20px 0' }}>
            <BuildHubSeal size="medium" />
          </div>
          <div className="section-body grid-2">
            <div className="field">
              <label>Layout Request ID</label>
              <input value={data.layout_request_id} onChange={e=>setData({...data, layout_request_id: e.target.value})} />
            </div>
            <div className="field">
              <label>Notes</label>
              <input value={data.notes} onChange={e=>setData({...data, notes: e.target.value})} />
            </div>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-primary" onClick={next}>Next</button>
          </div>
        </div>
      )}

      {step === 1 && (
        <div className="section">
          <div className="section-header">Materials</div>
          <div className="section-body">
            <div className="field">
              <label>Materials</label>
              <textarea rows={6} value={data.materials} onChange={e=>setData({...data, materials: e.target.value})} placeholder="List major materials and specs" />
            </div>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={next}>Next</button>
          </div>
        </div>
      )}

      {step === 2 && (
        <div className="section">
          <div className="section-header">Cost Breakdown</div>
          <div className="section-body">
            <div className="field">
              <label>Cost Breakdown</label>
              <textarea rows={6} value={data.cost_breakdown} onChange={e=>setData({...data, cost_breakdown: e.target.value})} placeholder="Labor, Materials, Misc, Profit, etc." />
            </div>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={next}>Next</button>
          </div>
        </div>
      )}

      {step === 3 && (
        <div className="section">
          <div className="section-header">Totals</div>
          <div className="section-body grid-2">
            <div className="field">
              <label>Total Cost (₹)</label>
              <input type="number" value={data.total_cost} onChange={e=>setData({...data, total_cost: e.target.value})} />
            </div>
            <div className="field">
              <label>Timeline</label>
              <input value={data.timeline} onChange={e=>setData({...data, timeline: e.target.value})} placeholder="e.g., 90 days" />
            </div>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={next}>Next</button>
          </div>
        </div>
      )}

      {step === 4 && (
        <div className="section">
          <div className="section-header">Review</div>
          <div className="section-body">
            <pre style={{background:'#f8fafc', padding:12, borderRadius:8, overflow:'auto'}}>{JSON.stringify(data, null, 2)}</pre>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" onClick={next}>Proceed</button>
          </div>
        </div>
      )}

      {step === 5 && (
        <div className="section">
          <div className="section-header">Submit</div>
          <div className="section-body">
            <p className="muted">Your estimate will be sent to the homeowner.</p>
          </div>
          <div className="wizard-footer">
            <button className="btn btn-secondary" onClick={prev}>Back</button>
            <button className="btn btn-primary" disabled={loading} onClick={submit}>{loading ? 'Submitting...' : 'Submit Estimate'}</button>
          </div>
        </div>
      )}
    </WizardLayout>
  );
}