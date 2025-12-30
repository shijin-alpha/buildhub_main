import React, { createContext, useCallback, useContext, useMemo, useRef, useState, useEffect } from 'react';
import '../styles/toast.css';

// Toast types: info | success | error | warning
const ToastContext = createContext(null);

let globalId = 0;

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);
  const timeoutsRef = useRef(new Map());

  // Remove toast and clear its timeout
  const remove = useCallback((id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
    const to = timeoutsRef.current.get(id);
    if (to) {
      clearTimeout(to);
      timeoutsRef.current.delete(id);
    }
  }, []);

  // Add toast and auto-dismiss
  const show = useCallback((message, opts = {}) => {
    const id = ++globalId;
    const toast = {
      id,
      message,
      type: opts.type || 'info',
      duration: typeof opts.duration === 'number' ? opts.duration : 3500,
    };
    setToasts((prev) => [...prev, toast]);

    if (toast.duration > 0) {
      const to = setTimeout(() => remove(id), toast.duration);
      timeoutsRef.current.set(id, to);
    }

    return id;
  }, [remove]);

  const api = useMemo(() => ({
    show,
    info: (msg, o) => show(msg, { ...(o || {}), type: 'info' }),
    success: (msg, o) => show(msg, { ...(o || {}), type: 'success' }),
    error: (msg, o) => show(msg, { ...(o || {}), type: 'error' }),
    warning: (msg, o) => show(msg, { ...(o || {}), type: 'warning' }),
    remove,
  }), [show, remove]);

  // Cleanup on unmount
  useEffect(() => () => {
    for (const to of timeoutsRef.current.values()) clearTimeout(to);
    timeoutsRef.current.clear();
  }, []);

  return (
    <ToastContext.Provider value={api}>
      {children}
      <div className="toast-container" role="status" aria-live="polite" aria-atomic="true">
        {toasts.map((t) => (
          <div key={t.id} className={`toast ${t.type}`}>
            <span className="toast-message">{t.message}</span>
            <button className="toast-close" aria-label="Close" onClick={() => remove(t.id)}>×</button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}

export function useToast() {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast must be used within a ToastProvider');
  return ctx;
}