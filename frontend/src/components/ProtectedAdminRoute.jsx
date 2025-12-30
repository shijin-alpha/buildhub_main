import React, { useEffect, useState } from 'react';
import { Navigate } from 'react-router-dom';

const ProtectedAdminRoute = ({ children }) => {
  const [checked, setChecked] = useState(false);
  const [ok, setOk] = useState(false);

  useEffect(() => {
    (async () => {
      // Maintain current local auth check, and also rely on server session if available later
      const isAdminLoggedIn = localStorage.getItem('admin_logged_in') === 'true';
      let serverAuth = false;
      try {
        const res = await fetch('/buildhub/backend/api/session_check.php', { credentials: 'include' });
        const data = await res.json();
        // If you later set admin session, this will pass. For now, keep local flag too.
        serverAuth = !!data.authenticated && data.user?.role === 'admin';
      } catch {}
      setOk(isAdminLoggedIn || serverAuth);
      setChecked(true);
    })();
  }, []);

  if (!checked) return null;
  if (!ok) return <Navigate to="/login" replace />;
  return children;
};

export default ProtectedAdminRoute;