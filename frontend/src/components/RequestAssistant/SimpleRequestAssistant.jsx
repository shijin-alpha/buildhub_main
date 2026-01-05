import React, { useState, useRef, useEffect } from "react";
import { useLocation } from "react-router-dom";
import "./styles.css";

const LOCAL_KEY = "request_assistant_consent";

const initialMessage = {
  from: "bot",
  text: "Hi there! 👋 I'm your BuildHub assistant! I can help you with construction planning and using our platform. Just ask me anything! 😊",
  suggestions: ["Hi!", "How to create request?", "Budget help", "Dashboard navigation"]
};

// Simple FAQ responses without AI
const faqResponses = {
  "hi": "Hello! Welcome to BuildHub. How can I help you today?",
  "hello": "Hi there! I'm here to help with your construction needs. What would you like to know?",
  "help": "I can help you with:\n• Creating construction requests\n• Understanding budget planning\n• Navigating the dashboard\n• Connecting with architects\n\nWhat specific topic interests you?",
  "create request": "To create a request:\n1. Click 'Request Custom Design' button\n2. Fill in your plot size and budget\n3. Specify your house requirements\n4. Select architects\n5. Submit your request\n\nWould you like me to guide you through any specific step?",
  "budget": "For budget planning:\n• ₹5-10 Lakhs: Basic construction\n• ₹10-20 Lakhs: Standard features\n• ₹20-30 Lakhs: Good quality materials\n• ₹30-50 Lakhs: Premium construction\n• ₹50+ Lakhs: Luxury features\n\nConsider plot size, materials, and local rates when planning.",
  "dashboard": "Dashboard features:\n• View your requests and proposals\n• Track construction progress\n• Message architects and contractors\n• Manage payments\n• Access reports\n\nClick on any section to explore!",
  "architects": "To find architects:\n1. Browse available architects in your area\n2. Check their ratings and past projects\n3. Submit requests to multiple architects\n4. Compare their proposals\n5. Select the best fit for your project",
  "plot size": "Plot size tips:\n• Measure length × width in feet or meters\n• Consider setback requirements\n• Account for parking and garden space\n• Check local building regulations\n• Consult with architects for optimal utilization",
  "rooms": "Room planning:\n• Bedrooms: Consider family size and future needs\n• Bathrooms: Plan for convenience and guests\n• Kitchen: Choose open/closed based on preference\n• Living areas: Balance space and functionality\n• Storage: Don't forget utility and store rooms"
};

function SimpleRequestAssistantContent({ userId, pageContext = "request" }) {
  const [open, setOpen] = useState(false);
  const [input, setInput] = useState("");
  const [messages, setMessages] = useState([initialMessage]);
  const messagesEndRef = useRef(null);

  useEffect(() => {
    const saved = localStorage.getItem(LOCAL_KEY);
    if (saved === "yes") return;
    localStorage.setItem(LOCAL_KEY, "yes");
  }, []);

  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: "smooth", block: "end" });
    }
  }, [messages]);

  function findBestResponse(userInput) {
    const input = userInput.toLowerCase().trim();
    
    // Direct matches
    for (const [key, response] of Object.entries(faqResponses)) {
      if (input.includes(key)) {
        return response;
      }
    }
    
    // Keyword matching
    if (input.includes("request") || input.includes("submit")) {
      return faqResponses["create request"];
    }
    if (input.includes("money") || input.includes("cost") || input.includes("price")) {
      return faqResponses["budget"];
    }
    if (input.includes("navigate") || input.includes("menu") || input.includes("page")) {
      return faqResponses["dashboard"];
    }
    if (input.includes("architect") || input.includes("designer")) {
      return faqResponses["architects"];
    }
    if (input.includes("plot") || input.includes("land") || input.includes("size")) {
      return faqResponses["plot size"];
    }
    if (input.includes("room") || input.includes("bedroom") || input.includes("bathroom")) {
      return faqResponses["rooms"];
    }
    
    // Default response
    return "I can help you with construction planning, creating requests, budget guidance, and navigating BuildHub. Could you be more specific about what you need help with?";
  }

  function generateSuggestions(response) {
    if (response.includes("create request")) {
      return ["Fill request form", "Select architects", "Budget planning"];
    }
    if (response.includes("budget")) {
      return ["Plot size help", "Material costs", "Timeline planning"];
    }
    if (response.includes("dashboard")) {
      return ["View requests", "Check proposals", "Track progress"];
    }
    if (response.includes("architects")) {
      return ["Browse architects", "Compare proposals", "Send messages"];
    }
    
    return ["Create request", "Budget help", "Dashboard guide", "Find architects"];
  }

  function handleSend(text) {
    const content = (text || input).trim();
    if (!content) return;
    
    const response = findBestResponse(content);
    const suggestions = generateSuggestions(response);
    
    const botMessage = {
      from: "bot",
      text: response,
      ts: Date.now(),
      suggestions: suggestions
    };

    setMessages((prev) => [
      ...prev,
      { from: "user", text: content, ts: Date.now() },
      botMessage
    ]);
    
    setInput("");
  }

  function handleClear() {
    setMessages([initialMessage]);
  }

  return (
    <>
      <div className="ra-floating-button" onClick={() => setOpen(!open)} title="BuildHub Assistant">
        💬
      </div>

      {open && (
        <div className="ra-panel">
          <header className="ra-header">
            <div>
              <div className="ra-title">BuildHub Assistant</div>
              <div className="ra-subtitle">Get help with construction planning</div>
            </div>
            <div className="ra-actions">
              <button className="ra-clear" onClick={handleClear}>Clear</button>
              <button className="ra-close" onClick={() => setOpen(false)}>
                ×
              </button>
            </div>
          </header>

          <div className="ra-search">
            <input
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder="Ask about construction or our platform..."
              onKeyDown={(e) => {
                if (e.key === "Enter") handleSend();
              }}
            />
            <button onClick={() => handleSend()}>Ask</button>
          </div>

          <div className="ra-messages">
            {messages.map((msg, idx) => (
              <div key={idx} className={`ra-message ${msg.from}`}>
                <div className="ra-text">{msg.text}</div>
                
                {/* Suggestions */}
                {msg.from === "bot" && msg.suggestions && msg.suggestions.length > 0 && (
                  <div className="ra-suggestions">
                    <div className="ra-suggestions-label">Quick options:</div>
                    <div className="ra-quick-options">
                      {msg.suggestions.map((suggestion, i) => (
                        <button key={i} onClick={() => handleSend(suggestion)}>
                          {suggestion}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            ))}
            <div ref={messagesEndRef} />
          </div>
        </div>
      )}
    </>
  );
}

export default function SimpleRequestAssistant(props) {
  const location = useLocation();
  // Only show on /homeowner-dashboard
  if (location.pathname !== "/homeowner-dashboard") {
    return null;
  }
  return <SimpleRequestAssistantContent {...props} />;
}