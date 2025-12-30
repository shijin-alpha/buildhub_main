import React from "react";

const AuthorizedRedirectURIs = () => (
  <div style={{ maxWidth: 600, margin: "40px auto", padding: 24, background: "#fff", borderRadius: 8, boxShadow: "0 2px 12px rgba(0,0,0,0.07)" }}>
    <h2>Authorized Redirect URIs for Google OAuth</h2>
    <p>
      Copy and paste these URIs into your Google Cloud Console under <b>Authorized redirect URIs</b> for your OAuth Client ID:
    </p>
    <pre style={{ background: "#f5f5f5", padding: 16, borderRadius: 6, fontSize: "1.05rem" }}>
{`http://localhost:3000
http://localhost`}
    </pre>
    <p>
      <b>Note:</b> For Google Identity Services (the button you use), you usually only need to set <b>Authorized JavaScript origins</b>. If you use OAuth redirect flow, add these as redirect URIs.
    </p>
  </div>
);

export default AuthorizedRedirectURIs;