import React, { useEffect, useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';

export default function ResetPassword() {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [token, setToken] = useState('');
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [msg, setMsg] = useState('');
  const [validating, setValidating] = useState(true);
  const [pwdStrength, setPwdStrength] = useState(0);
  const [manualMode, setManualMode] = useState(false); // allow entering email+code
  const [verified, setVerified] = useState(false);
  const [showPwd, setShowPwd] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  useEffect(() => {
    const e = params.get('email');
    const t = params.get('token');
    if (!e || !t) {
      // No link params: allow manual input of email + reset code
      setManualMode(true);
      setMsg('Enter your email and the reset code from your email to proceed.');
      setValidating(false);
      return;
    }
    setEmail(e); setToken(t);
    (async () => {
      try {
        const res = await fetch('/buildhub/backend/api/reset_password_verify.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: e, token: t })
        });
        const result = await res.json();
        if (!result.success) {
          setMsg(result.message || 'Invalid or expired link.');
          setVerified(false);
        } else {
          setVerified(true);
        }
      } catch {
        setMsg('Server error.');
      } finally { setValidating(false); }
    })();
  }, []);

  const computeStrength = (pwd) => {
    let score = 0;
    if (pwd.length >= 8) score++;
    if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    return score;
  };

  const validatePassword = (pwd) => {
    if (pwd.length < 8) return 'Password must be at least 8 characters long.';
    if (/\s/.test(pwd)) return 'Password cannot contain spaces.';
    if (!/[A-Za-z]/.test(pwd)) return 'Password must include at least one letter.';
    if (!/[0-9]/.test(pwd)) return 'Password must include at least one number.';
    if (!/[^A-Za-z0-9]/.test(pwd)) return 'Password must include at least one special character.';
    return '';
  };

  const verifyCode = async () => {
    setMsg('');
    if (!email || !token) { setMsg('Enter your email and reset code.'); return; }
    try {
      const res = await fetch('/buildhub/backend/api/reset_password_verify.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, token })
      });
      const result = await res.json();
      if (result.success) {
        setVerified(true);
        setMsg('Code verified. You can now set a new password.');
      } else {
        setVerified(false);
        setMsg(result.message || 'Invalid or expired code.');
      }
    } catch {
      setVerified(false);
      setMsg('Server error. Please try again.');
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMsg('');

    // If manual mode, ensure code is verified first
    if (!verified) {
      await verifyCode();
      if (!verified) return;
    }

    if (!password || !confirm) { setMsg('Enter new password.'); return; }
    const perr = validatePassword(password);
    if (perr) { setMsg(perr); return; }
    if (password !== confirm) { setMsg('Passwords do not match!'); return; }
    try {
      const res = await fetch('/buildhub/backend/api/reset_password_update.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, token, password })
      });
      const result = await res.json();
      if (result.success) {
        setMsg('Password updated successfully. Redirecting to login...');
        setTimeout(()=>navigate('/login'), 1500);
      } else {
        setMsg(result.message || 'Failed to update password.');
      }
    } catch {
      setMsg('Server error. Please try again.');
    }
  };

  if (validating) return <main className="register-page"><section className="register-form-section"><p>Validating link...</p></section></main>;

  return (
    <main className="register-page" aria-label="Reset password">
      <section className="register-form-section" style={{alignItems:'center'}}>
        <h2>Reset Password</h2>
        <form onSubmit={handleSubmit} className="glass-card" style={{maxWidth: 420, width:'100%'}}>
          <div className="full-width">
            <label>Email</label>
            <input 
              type="email" 
              value={email} 
              onChange={e=>{
                const value = e.target.value.replace(/\s+/g, ''); // Remove all whitespace
                setEmail(value);
              }} 
              placeholder="Enter your account email" 
              disabled={!manualMode} 
              required 
              onKeyDown={(e)=>{ if (e.key === ' ' || (e.key === 'Spacebar')) e.preventDefault(); }}
              onPaste={(e)=>{ const t=e.clipboardData.getData('text'); if (/\s/.test(t)) { e.preventDefault(); } }}
            />
          </div>

          <div className="full-width">
            <label>Reset Code</label>
            <input type="text" value={token} onChange={e=>setToken(e.target.value)} placeholder="Paste code from email" disabled={!manualMode && !!token} required />
            {manualMode && (
              <button type="button" className="btn-secondary" style={{marginTop:8}} onClick={verifyCode}>Verify Code</button>
            )}
          </div>

          <div className="full-width">
            <label>New Password</label>
            <div style={{ position: 'relative' }}>
              <input 
                type={showPwd ? 'text' : 'password'} 
                value={password} 
                onChange={e=>{ 
                  const value = e.target.value.replace(/\s+/g, ''); // Remove all whitespace
                  setPassword(value); 
                  setPwdStrength(computeStrength(value)); 
                }} 
                placeholder="Create a strong password" 
                required 
                disabled={!verified} 
                onKeyDown={(e)=>{ if (e.key === ' ' || (e.key === 'Spacebar')) e.preventDefault(); }}
                onPaste={(e)=>{ const t=e.clipboardData.getData('text'); if (/\s/.test(t)) { e.preventDefault(); } }}
              />
              <button type="button" aria-label={showPwd ? 'Hide password' : 'Show password'} onClick={() => setShowPwd(v=>!v)} style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 'none', cursor: 'pointer' }}>
                {showPwd ? '🙈' : '👁️'}
              </button>
            </div>
            <div className="pwd-meter" aria-hidden="true">
              <div className={`pwd-meter-bar s${pwdStrength}`}></div>
              <div className="pwd-meter-label">{pwdStrength <= 1 ? 'Weak' : pwdStrength === 2 ? 'Fair' : pwdStrength === 3 ? 'Good' : 'Strong'}</div>
            </div>
          </div>
          <div className="full-width">
            <label>Confirm Password</label>
            <div style={{ position: 'relative' }}>
              <input 
                type={showConfirm ? 'text' : 'password'} 
                value={confirm} 
                onChange={e=>{
                  const value = e.target.value.replace(/\s+/g, ''); // Remove all whitespace
                  setConfirm(value);
                }} 
                placeholder="Re-enter password" 
                required 
                disabled={!verified} 
                onKeyDown={(e)=>{ if (e.key === ' ' || (e.key === 'Spacebar')) e.preventDefault(); }}
                onPaste={(e)=>{ const t=e.clipboardData.getData('text'); if (/\s/.test(t)) { e.preventDefault(); } }}
              />
              <button type="button" aria-label={showConfirm ? 'Hide password' : 'Show password'} onClick={() => setShowConfirm(v=>!v)} style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 'none', cursor: 'pointer' }}>
                {showConfirm ? '🙈' : '👁️'}
              </button>
            </div>
          </div>
          {msg && <p className={/success|updated|verified/i.test(msg)?'success':'error'}>{msg}</p>}
          <button className="btn-submit" type="submit" disabled={!verified}>Update Password</button>
        </form>
      </section>
    </main>
  );
}