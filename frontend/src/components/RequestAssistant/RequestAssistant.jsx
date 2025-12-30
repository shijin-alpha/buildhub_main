import React, { useEffect, useMemo, useRef, useState } from "react";
import { useLocation } from "react-router-dom";
import { matchInput } from "./matcher.js";
import "./styles.css";

const LOCAL_KEY = "request_assistant_consent";

const initialMessage = {
  from: "bot",
  text: "Hi there! 👋 I'm your intelligent BuildHub assistant! I can help you with construction planning, using our platform, and connecting with architects. I learn from our conversation to give you better answers. Just ask me anything! 😊",
  askFeedback: false,
  suggestions: ["Hi!", "How to create request?", "Budget help", "Dashboard navigation"]
};

function RequestAssistantContent({ userId, pageContext = "request" }) {
  const [open, setOpen] = useState(false);
  const [input, setInput] = useState("");
  const [messages, setMessages] = useState([initialMessage]);
  const [showEscalation, setShowEscalation] = useState(false);
  const [pendingCopy, setPendingCopy] = useState(null);
  const [userContext, setUserContext] = useState({
    askedAbout: [],
    preferences: {},
    sessionQuestions: 0,
    conversationHistory: [],
    lastTopics: [],
    userIntents: []
  });
  const messagesEndRef = useRef(null);

  const conversationId = useMemo(
    () => `chat-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    []
  );

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

  async function logInteraction({ message, response, feedback = null, confidence = null }) {
    try {
      await fetch("/backend/api/chatbot/log_interaction.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          user_id: userId || null,
          conversation_id: conversationId,
          message,
          response,
          feedback,
          confidence,
          context: userContext
        })
      });
    } catch (err) {
      // swallow logging errors
    }
  }

  function generateIntelligentSuggestions(currentTopic, userContext, userInput) {
    const suggestions = [];
    const { askedAbout, sessionQuestions, lastTopics } = userContext;
    
    // Context-aware suggestions based on current topic
    const contextualSuggestions = {
      'plot_size_basic': ["Convert feet to meters", "Irregular plot help", "Plot area calculation"],
      'budget_help': ["Per sqft cost", "Material cost breakdown", "Budget planning tips"],
      'rooms_bedrooms': ["Bathroom planning", "Kitchen design", "Storage planning"],
      'dashboard_main_navigation': ["Create new request", "View proposals", "Message architects"],
      'create_request_button': ["Fill request form", "Upload documents", "Submit request"],
      'my_requests_section': ["Check proposal status", "View architect quotes", "Edit request"],
      'view_proposals_help': ["Compare architects", "Select proposal", "Message architect"],
      'upload_documents_help': ["Photo requirements", "Document types", "File size limits"]
    };

    // Add contextual suggestions for current topic
    if (currentTopic && contextualSuggestions[currentTopic]) {
      suggestions.push(...contextualSuggestions[currentTopic].slice(0, 2));
    }

    // Intelligent follow-up based on conversation flow
    if (lastTopics.includes('plot_size_basic') && !lastTopics.includes('budget_help')) {
      suggestions.push("Budget planning help");
    }
    if (lastTopics.includes('budget_help') && !lastTopics.includes('rooms_bedrooms')) {
      suggestions.push("Room requirements");
    }
    if (lastTopics.includes('rooms_bedrooms') && !lastTopics.includes('dashboard_main_navigation')) {
      suggestions.push("How to create request");
    }

    // Add popular unasked topics
    const popularTopics = [
      { id: 'plot_size_basic', text: "Plot size help", priority: 1 },
      { id: 'budget_help', text: "Budget guidance", priority: 1 },
      { id: 'dashboard_main_navigation', text: "Dashboard help", priority: 2 },
      { id: 'create_request_button', text: "Create request", priority: 2 },
      { id: 'rooms_bedrooms', text: "Room planning", priority: 1 },
      { id: 'upload_documents_help', text: "Upload photos", priority: 3 }
    ];

    popularTopics
      .filter(topic => !askedAbout.includes(topic.id))
      .sort((a, b) => a.priority - b.priority)
      .slice(0, 3 - suggestions.length)
      .forEach(topic => suggestions.push(topic.text));

    // Escalation suggestion for complex queries
    if (sessionQuestions > 4 && !suggestions.includes("Talk to architect")) {
      suggestions.push("Talk to architect");
    }

    return suggestions.slice(0, 3);
  }

  function updateIntelligentContext(topic, input, confidence) {
    setUserContext(prev => {
      const newLastTopics = [...prev.lastTopics, topic].slice(-5); // Keep last 5 topics
      const newConversationHistory = [...prev.conversationHistory, { input, topic, confidence, timestamp: Date.now() }].slice(-10);
      
      return {
        ...prev,
        askedAbout: [...new Set([...prev.askedAbout, topic])],
        sessionQuestions: prev.sessionQuestions + 1,
        lastTopics: newLastTopics,
        conversationHistory: newConversationHistory,
        preferences: {
          ...prev.preferences,
          lastTopic: topic,
          lastInput: input,
          averageConfidence: newConversationHistory.reduce((sum, item) => sum + (item.confidence || 0), 0) / newConversationHistory.length
        }
      };
    });
  }

  function handleSend(text) {
    const content = (text || input).trim();
    if (!content) return;
    
    // Use the intelligent matcher
    const match = matchInput(content, userContext);
    const suggestions = generateIntelligentSuggestions(match.topicId, userContext, content);
    
    // Update context with intelligence
    if (match.topicId) {
      updateIntelligentContext(match.topicId, content, match.confidence || 0);
    }

    const botMessage = {
      from: "bot",
      text: match.reply,
      ts: Date.now(),
      meta: match,
      suggestions: suggestions,
      askFeedback: true,
      confidence: match.confidence
    };

    setMessages((prev) => [
      ...prev,
      { from: "user", text: content, ts: Date.now() },
      botMessage
    ]);
    
    setPendingCopy(match.normalized || null);
    setInput("");
    logInteraction({ 
      message: content, 
      response: match.reply, 
      confidence: match.confidence 
    });
  }

  function handleClear() {
    setMessages([initialMessage]);
    setPendingCopy(null);
    setUserContext({
      askedAbout: [],
      preferences: {},
      sessionQuestions: 0,
      conversationHistory: [],
      lastTopics: [],
      userIntents: []
    });
  }

  function handleFeedback(index, value) {
    const msg = messages[index];
    
    // Log feedback with context
    logInteraction({ 
      message: `feedback:${value}:${msg?.text}`, 
      response: "feedback",
      feedback: value,
      confidence: msg?.confidence
    });
    
    // Update message with feedback
    setMessages((prev) => {
      const updated = prev.map((m, i) => (i === index ? { ...m, feedback: value } : m));
      return updated;
    });

    // Intelligent response to negative feedback
    if (value === "down") {
      setTimeout(() => {
        const helpMessage = msg?.confidence && msg.confidence < 0.6 
          ? "I wasn't very confident about that answer. Let me try to help better! Could you rephrase your question or be more specific about what you need?"
          : "Sorry that didn't help! Would you like me to explain differently, or would you prefer to connect with an architect for personalized advice?";
          
        setMessages((prev) => [
          ...prev,
          {
            from: "bot",
            text: helpMessage,
            ts: Date.now(),
            suggestions: ["Explain differently", "Talk to architect", "Try new question"],
            askFeedback: false
          }
        ]);
      }, 1000);
    } else if (value === "up") {
      // Learn from positive feedback
      setTimeout(() => {
        const encouragement = [
          "Great! I'm learning to help you better. What else would you like to know?",
          "Awesome! I'm glad that was helpful. Any other questions about your construction project?",
          "Perfect! I love helping with construction planning. What's next on your mind?"
        ];
        
        setMessages((prev) => [
          ...prev,
          {
            from: "bot",
            text: encouragement[Math.floor(Math.random() * encouragement.length)],
            ts: Date.now(),
            suggestions: generateIntelligentSuggestions(msg?.meta?.topicId, userContext, ""),
            askFeedback: false
          }
        ]);
      }, 1500);
    }
  }

  async function copyNormalized() {
    if (!pendingCopy) return;
    let copyText = "";
    const { type, normalized } = pendingCopy;
    if (type === "budget") copyText = normalized.display || normalized.amount?.toString();
    if (type === "plot") {
      if (normalized.width && normalized.depth) {
        copyText = `${normalized.width} x ${normalized.depth} ${normalized.unit}`;
      } else if (normalized.area) {
        copyText = `${normalized.area} ${normalized.unit}`;
      }
    }
    if (type === "rooms") copyText = `${normalized.bhk} BHK`;
    if (type === "floors") copyText = `${normalized.floors} floors`;
    if (!copyText) return;
    try {
      await navigator.clipboard.writeText(copyText);
      setMessages((prev) => [
        ...prev,
        { from: "bot", text: "Copied! Paste it into the request form.", ts: Date.now() }
      ]);
    } catch (e) {
      setMessages((prev) => [
        ...prev,
        { from: "bot", text: "Copy failed. You can manually paste.", ts: Date.now() }
      ]);
    }
  }

  return (
    <>
      <div className="ra-floating-button" onClick={() => setOpen(!open)} title="Intelligent Assistant">
        🤖
      </div>

      {open && (
        <div className="ra-panel">
          <header className="ra-header">
            <div>
              <div className="ra-title">Intelligent Assistant</div>
              <div className="ra-subtitle">Smart answers that learn from our conversation</div>
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
              placeholder="Ask anything about construction or our platform..."
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
                
                {/* Confidence indicator for bot messages */}
                {msg.from === "bot" && msg.confidence && (
                  <div className="ra-confidence" title={`Confidence: ${Math.round(msg.confidence * 100)}%`}>
                    {msg.confidence > 0.8 ? "🎯" : msg.confidence > 0.6 ? "✅" : "🤔"}
                  </div>
                )}
                
                {/* Quick Options */}
                {msg.from === "bot" && msg.meta?.quickOptions && (
                  <div className="ra-quick-options">
                    {msg.meta.quickOptions.map((opt) => (
                      <button key={opt} onClick={() => handleSend(opt)}>
                        {opt}
                      </button>
                    ))}
                  </div>
                )}
                
                {/* Clarifier Options */}
                {msg.from === "bot" && msg.meta?.clarifier && (
                  <div className="ra-quick-options">
                    {msg.meta.options?.map((opt) => (
                      <button key={opt.label} onClick={() => handleSend(opt.label)}>
                        {opt.label}
                      </button>
                    ))}
                    {msg.meta.escalation && (
                      <button onClick={() => setShowEscalation(true)}>Connect to architect</button>
                    )}
                  </div>
                )}
                
                {/* Normalized Data Actions */}
                {msg.from === "bot" && msg.meta?.normalized && (
                  <div className="ra-quick-options">
                    <button onClick={copyNormalized}>Copy to form</button>
                    {msg.meta.normalized.type === "plot" && (
                      <>
                        <button onClick={() => handleSend("convert to meters")}>Convert</button>
                        <button onClick={() => handleSend("keep feet")}>Keep ft</button>
                      </>
                    )}
                    {msg.meta.normalized.type === "budget" && (
                      <>
                        <button onClick={() => handleSend("total budget")}>Total</button>
                        <button onClick={() => handleSend("per sqft")}>Per sqft</button>
                      </>
                    )}
                  </div>
                )}

                {/* Intelligent Follow-up Suggestions */}
                {msg.from === "bot" && msg.suggestions && msg.suggestions.length > 0 && (
                  <div className="ra-suggestions">
                    <div className="ra-suggestions-label">You might also ask:</div>
                    <div className="ra-quick-options">
                      {msg.suggestions.map((suggestion, i) => (
                        <button key={i} onClick={() => handleSend(suggestion)}>
                          {suggestion}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
                
                {/* Enhanced Feedback Buttons */}
                {msg.from === "bot" && msg.askFeedback && (
                  <div className="ra-feedback">
                    <span>Was this helpful?</span>
                    <button
                      className={msg.feedback === "up" ? "active" : ""}
                      onClick={() => handleFeedback(idx, "up")}
                      title="This was helpful"
                    >
                      👍
                    </button>
                    <button
                      className={msg.feedback === "down" ? "active" : ""}
                      onClick={() => handleFeedback(idx, "down")}
                      title="This wasn't helpful"
                    >
                      👎
                    </button>
                  </div>
                )}
              </div>
            ))}
            <div ref={messagesEndRef} />
          </div>
        </div>
      )}

      {showEscalation && (
        <div className="ra-modal-backdrop" onClick={() => setShowEscalation(false)}>
          <div className="ra-modal" onClick={(e) => e.stopPropagation()}>
            <div className="ra-modal-title">Connect to an architect?</div>
            <p>Share your contact and we'll arrange a personalized consultation. Or keep chatting with me - I'm getting smarter!</p>
            <div className="ra-modal-actions">
              <button onClick={() => setShowEscalation(false)}>Keep chatting</button>
              <button className="primary" onClick={() => setShowEscalation(false)}>
                Yes, connect me
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

export default function RequestAssistant(props) {
  const location = useLocation();
  // Only show on /homeowner-dashboard
  if (location.pathname !== "/homeowner-dashboard") {
    return null;
  }
  return <RequestAssistantContent {...props} />;
}