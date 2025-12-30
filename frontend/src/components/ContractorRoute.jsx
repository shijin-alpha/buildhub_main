import React, { useEffect, useState } from 'react';
import { Navigate } from 'react-router-dom';

const ContractorRoute = ({ children }) => {
  const [checked, setChecked] = useState(false);
  const [ok, setOk] = useState(false);

  useEffect(() => {
    (async () => {
      const user = JSON.parse(sessionStorage.getItem('user') || '{}');
      let serverAuth = false;
      try {
        const res = await fetch('/buildhub/backend/api/session_check.php', { credentials: 'include' });
        const data = await res.json();
        serverAuth = !!data.authenticated;
      } catch {}
      setOk(!!user.id && user.role === 'contractor' && serverAuth);
      setChecked(true);
    })();
  }, []);

  if (!checked) return null;
  if (!ok) return <Navigate to="/login" replace />;
  return children;
};

export default ContractorRoute;