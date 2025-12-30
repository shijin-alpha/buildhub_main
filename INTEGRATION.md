# Request Assistant Chatbot Integration Guide

## Frontend
1) Install component
   - Files already placed under `frontend/src/components/RequestAssistant/`.
   - Exports: `RequestAssistant` from `frontend/src/components/RequestAssistant/index.js`.

2) Add to the app shell so it appears on all pages
   - In `frontend/src/App.jsx` (or your layout wrapper), import and render once:
     ```jsx
     import RequestAssistant from "./components/RequestAssistant";

     function App() {
       return (
         <>
           {/* your existing routers/components */}
           <RequestAssistant />
         </>
       );
     }
     export default App;
     ```
   - Styles are contained in `RequestAssistant/styles.css`. If your bundler doesn’t pick up component-level CSS automatically, import it once in `App.jsx` or `main.jsx`:
     ```jsx
     import "./components/RequestAssistant/styles.css";
     ```

3) Knowledge base edits
   - Update `frontend/src/components/RequestAssistant/kb.json` to add or edit Q→A pairs. Each entry: `{ "id", "question_variants": [...], "answer": "..." }`.
   - Keep answers short (<40 words) and friendly.

4) Logging
   - The component posts to `/backend/api/chatbot/log_interaction.php`.
   - On first open, users see a consent prompt. If they skip, logs are not sent.
   - To pass a known user id, optionally render `<RequestAssistant userId={sessionUser?.id} />`.

## Backend
1) Endpoint
   - `backend/api/chatbot/log_interaction.php` accepts POST JSON:
     ```json
     { "user_id": 123, "conversation_id": "chat-xyz", "message": "...", "response": "..." }
     ```
   - Inserts into `chatbot_logs`.

2) Table (MySQL)
   ```sql
   CREATE TABLE IF NOT EXISTS chatbot_logs (
     id INT AUTO_INCREMENT PRIMARY KEY,
     user_id INT NULL,
     conversation_id VARCHAR(64) NOT NULL,
     message TEXT NOT NULL,
     response TEXT NOT NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     INDEX idx_chatbot_logs_conv (conversation_id),
     INDEX idx_chatbot_logs_user (user_id)
   );
   ```

## Behavior notes
- The assistant keeps answers short, asks for clarifiers when uncertain, and offers escalation (“Connect to Architect”).
- Numeric inputs get normalized (lakhs/crores, feet/meters, BHK, floors) with confirm/convert actions and quick copy-to-form.
- Feedback (👍/👎) is captured via the same logging endpoint when consented.



