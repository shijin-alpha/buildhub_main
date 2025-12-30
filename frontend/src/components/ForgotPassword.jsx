import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';

export default function ForgotPassword() {
  // Step 1: enter email, Step 2: enter OTP, Step 3: set new password
  const [step, setStep] = useState(1);

  const [email, setEmail] = useState('');
  const [token, setToken] = useState(''); // 6-digit OTP
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');

  const [msg, setMsg] = useState('');
  const [loading, setLoading] = useState(false);
  const [verified, setVerified] = useState(false);

  const [showPwd, setShowPwd] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [pwdStrength, setPwdStrength] = useState(0);

  const navigate = useNavigate();

  useEffect(() => {
    setMsg('');
  }, [step]);

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

  const sendOtp = async (e) => {
    e.preventDefault();
    setMsg('');
    if (!email) { setMsg('Enter your email.'); return; }
    setLoading(true);
    try {
      const res = await fetch('/buildhub/backend/api/forgot_password_request.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });
      const result = await res.json();
      setMsg(result.message || 'If an account exists, an OTP has been sent.');
      // Move to OTP step regardless of whether the account exists (privacy)
      setStep(2);
    } catch (e) {
      setMsg('Server error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const verifyCode = async () => {
    setMsg('');
    if (!email || !token) { setMsg('Enter your email and OTP.'); return; }
    if (!/^\d{6}$/.test(token)) { setMsg('Enter a valid 6-digit OTP.'); return; }
    setLoading(true);
    try {
      const res = await fetch('/buildhub/backend/api/reset_password_verify.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, token })
      });
      const result = await res.json();
      if (result.success) {
        setVerified(true);
        setMsg('OTP verified. You can now set a new password.');
        setStep(3);
      } else {
        setVerified(false);
        setMsg(result.message || 'Invalid or expired OTP.');
      }
    } catch (e) {
      setVerified(false);
      setMsg('Server error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const updatePassword = async (e) => {
    e.preventDefault();
    setMsg('');

    if (!verified) { setMsg('Verify the OTP first.'); return; }
    if (!password || !confirm) { setMsg('Enter new password.'); return; }
    const perr = validatePassword(password);
    if (perr) { setMsg(perr); return; }
    if (password !== confirm) { setMsg('Passwords do not match!'); return; }

    setLoading(true);
    try {
      const res = await fetch('/buildhub/backend/api/reset_password_update.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, token, password })
      });
      const result = await res.json();
      if (result.success) {
        setMsg('Password updated successfully. Redirecting to login...');
        setTimeout(() => navigate('/login'), 1500);
      } else {
        setMsg(result.message || 'Failed to update password.');
      }
    } catch (e) {
      setMsg('Server error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="register-page" aria-label="Forgot password">
      <section className="register-form-section" style={{alignItems:'center'}}>
        {/* Back to login */}
        <div style={{ width: '100%', maxWidth: 520, marginBottom: 12 }}>
          <button
            type="button"
            onClick={() => navigate('/login')}
            aria-label="Back to login"
            className="back-link"
          >
            <ArrowLeft size={18} />
            <span>Back to Login</span>
          </button>
        </div>

        <h2>Forgot Password</h2>

        {/* Card */}
        <form className="glass-card" style={{maxWidth: 460, width:'100%'}} onSubmit={step === 1 ? sendOtp : step === 3 ? updatePassword : (e)=>e.preventDefault()}>
          {/* Step 1: Enter Email */}
          {step === 1 && (
            <>
              <div className="full-width">
                <label htmlFor="email">Your Email</label>
                <input 
                  id="email" 
                  type="email" 
                  value={email} 
                  onChange={e=>{
                    const value = e.target.value.replace(/\s+/g, ''); // Remove all whitespace
                    setEmail(value);
                  }} 
                  placeholder="Email address" 
                  required 
                  onKeyDown={(e)=>{ if (e.key === ' ' || (e.key === 'Spacebar')) e.preventDefault(); }}
                  onPaste={(e)=>{ const t=e.clipboardData.getData('text'); if (/\s/.test(t)) { e.preventDefault(); } }}
                />
              </div>
              {msg && <p className={/sent|success/i.test(msg)?'success':'error'}>{msg}</p>}
              <button className="btn-submit" disabled={loading} type="submit">{loading? 'Sending...' : 'Send OTP'}</button>
            </>
          )}

          {/* Step 2: Enter OTP */}
          {step === 2 && (
            <>
              <div className="full-width">
                <label>Email</label>
                <input type="email" value={email} disabled />
              </div>
              <div className="full-width">
                <label>Enter OTP</label>
                <input type="text" inputMode="numeric" pattern="\\d{6}" maxLength={6} value={token} onChange={e=>setToken(e.target.value.replace(/[^0-9]/g,''))} placeholder="6-digit OTP" required />
              </div>
              {msg && <p className={/verified/i.test(msg)?'success':'error'}>{msg}</p>}
              <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                <button type="button" className="btn-submit" disabled={loading} onClick={verifyCode}>{loading? 'Verifying...' : 'Verify OTP'}</button>
                <button type="button" className="btn-secondary" onClick={() => setStep(1)}>Change Email</button>
              </div>
            </>
          )}

          {/* Step 3: Set new password */}
          {step === 3 && (
            <>
              <div className="full-width">
                <label>Email</label>
                <input type="email" value={email} disabled />
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
                    onKeyDown={(e)=>{ if (e.key === ' ' || (e.key === 'Spacebar')) e.preventDefault(); }}
                    onPaste={(e)=>{ const t=e.clipboardData.getData('text'); if (/\s/.test(t)) { e.preventDefault(); } }}
                  />
                  <button type="button" aria-label={showConfirm ? 'Hide password' : 'Show password'} onClick={() => setShowConfirm(v=>!v)} style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', background: 'transparent', border: 'none', cursor: 'pointer' }}>
                    {showConfirm ? '🙈' : '👁️'}
                  </button>
                </div>
              </div>
              {msg && <p className={/updated|success/i.test(msg)?'success':'error'}>{msg}</p>}
              <button className="btn-submit" type="submit" disabled={loading}>{loading? 'Updating...' : 'Update Password'}</button>
            </>
          )}
        </form>
      </section>
    </main>
  );
}
