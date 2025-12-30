import React from 'react';

const starRow = (rating) => (
  <span title={typeof rating === 'number' ? `${rating} / 5` : 'No ratings yet'}>
    {[1,2,3,4,5].map(star => (
      <span key={star} style={{color: (rating || 0) >= star ? '#f5a623' : '#ddd'}}>★</span>
    ))}
  </span>
);

const ArchitectDetailsModal = ({ open, onClose, architect, reviews, loading }) => {
  if (!open || !architect) return null;
  const name = `${architect.first_name || ''} ${architect.last_name || ''}`.trim() || 'Architect';
  return (
    <div className="form-modal" onClick={onClose}>
      <div className="form-content" style={{maxWidth:'720px'}} onClick={(e)=>e.stopPropagation()}>
        <div className="form-header" style={{display:'flex', justifyContent:'space-between', alignItems:'center'}}>
          <div>
            <h3 style={{margin:0}}>{name}</h3>
            <p style={{margin:'4px 0 0 0', color:'#64748b'}}>{architect.company_name || architect.email || ''}</p>
          </div>
          <button className="btn btn-secondary" onClick={onClose}>Close</button>
        </div>

        <div className="details-panel" style={{marginTop:8}}>
          <div className="details-grid" style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:12}}>
            <div><strong>Specialization:</strong> {architect.specialization || 'General'}</div>
            <div><strong>Experience:</strong> {architect.experience_years ?? 'N/A'} years</div>
            <div><strong>City:</strong> {architect.city || 'N/A'}</div>
            <div><strong>Email:</strong> {architect.email || 'N/A'}</div>
            {architect.phone && <div><strong>Phone:</strong> {architect.phone}</div>}
            <div><strong>Rating:</strong> <span style={{marginLeft:6}}>{starRow(architect.avg_rating)} <span style={{marginLeft:6, color:'#666', fontSize:'0.9rem'}}>({architect.review_count || 0})</span></span></div>
          </div>
        </div>

        <div className="section-card" style={{marginTop:16}}>
          <div className="section-header">
            <h4>Recent Reviews</h4>
            <p>Feedback from homeowners</p>
          </div>
          <div className="section-content">
            {loading ? (
              <div className="loading">Loading reviews...</div>
            ) : Array.isArray(reviews) && reviews.length > 0 ? (
              <div className="item-list">
                {reviews.slice(0,5).map(r => (
                  <div key={r.id} className="list-item">
                    <div className="item-icon">💬</div>
                    <div className="item-content">
                      <h4 className="item-title" style={{display:'flex', justifyContent:'space-between', alignItems:'center'}}>
                        <span>{r.author || 'Homeowner'}</span>
                        <span style={{color:'#f5a623'}}>{'★'.repeat(r.rating)}{'☆'.repeat(5 - r.rating)}</span>
                      </h4>
                      <p className="item-meta">{new Date(r.created_at).toLocaleString()}</p>
                      {r.comment && <p className="item-description" style={{whiteSpace:'pre-wrap'}}>{r.comment}</p>}
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="empty-state">
                <div className="empty-icon">🧑‍🎨</div>
                <h3>No reviews yet</h3>
                <p>Be the first to work with this architect.</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default ArchitectDetailsModal;













