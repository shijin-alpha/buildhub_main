// Utilities to standardize status display across the app

// Map raw status to a consistent badge class token
export function badgeClass(status) {
  const s = String(status || '').toLowerCase();
  switch (s) {
    case 'approved':
    case 'accepted':
    case 'active':
    case 'success':
      return 'accepted';
    case 'rejected':
    case 'declined':
    case 'error':
    case 'suspended':
      return 'rejected';
    case 'pending':
    case 'awaiting':
    case 'in_review':
    case 'processing':
    default:
      return 'pending';
  }
}

// Human-friendly label from raw status
export function formatStatus(status) {
  if (!status) return 'Pending';
  const s = String(status).toLowerCase();
  const map = {
    pending: 'Pending',
    awaiting: 'Awaiting',
    in_review: 'In Review',
    processing: 'Processing',
    approved: 'Approved',
    accepted: 'Accepted',
    rejected: 'Rejected',
    declined: 'Declined',
    suspended: 'Suspended',
    finalized: 'Finalized',
    shortlisted: 'Shortlisted',
    completed: 'Completed',
    active: 'Active',
    inactive: 'Inactive',
    success: 'Success',
    error: 'Error',
  };
  return map[s] || (s.charAt(0).toUpperCase() + s.slice(1));
}