// Intelligent chatbot matcher with advanced NLP and context awareness
import kb from "./kb_enhanced.json";

// Advanced text normalization with better language support
function normalizeText(text) {
  return text
    .toLowerCase()
    .replace(/[^\w\s]/g, ' ') // Remove punctuation
    .replace(/\s+/g, ' ') // Normalize spaces
    .trim()
    // Enhanced misspelling corrections
    .replace(/\b(wat|wht|whats|wats)\b/g, 'what')
    .replace(/\b(kaise|kese|kse|kyse)\b/g, 'how')
    .replace(/\b(batao|bata|samjhao|btao|bataye)\b/g, 'tell')
    .replace(/\b(chahiye|chaiye|chaye|chahye|chayiye)\b/g, 'need')
    .replace(/\b(kitna|kitne|ktna|ktne)\b/g, 'how much')
    .replace(/\b(kya|kya hai|kya h|kya he)\b/g, 'what')
    .replace(/\b(help|halp|hlp|hepl)\b/g, 'help')
    .replace(/\b(dont no|dont know|not no|dont knw|dnt know)\b/g, 'dont know')
    .replace(/\b(bilding|bulding|buidling|buiding)\b/g, 'building')
    .replace(/\b(budjet|buget|budgt|bujget)\b/g, 'budget')
    .replace(/\b(bedrom|bed room|bedrm|bedrooms)\b/g, 'bedroom')
    .replace(/\b(bathrom|bath room|bathrm|washrom)\b/g, 'bathroom')
    .replace(/\b(kitchn|kichen|kithen)\b/g, 'kitchen')
    .replace(/\b(plz|pls|please)\b/g, 'please')
    .replace(/\b(u|ur|urs)\b/g, 'you')
    .replace(/\b(n|nd)\b/g, 'and')
    .replace(/\b(r|are)\b/g, 'are')
    .replace(/\b(2|to|too)\b/g, 'to')
    .replace(/\b(4|for|fr)\b/g, 'for')
    // Malayalam (Manglish) normalizations
    .replace(/\b(enthaanu|enthanu|enthu)\b/g, 'what')
    .replace(/\b(engane|engne|enganeyanu)\b/g, 'how')
    .replace(/\b(parayamo|parayam|parayan)\b/g, 'tell')
    .replace(/\b(venam|veno|venamoo)\b/g, 'need')
    .replace(/\b(ethrayanu|ethra|ethre)\b/g, 'how much')
    .replace(/\b(ariyilla|ariyila|ariyillallo)\b/g, 'dont know')
    .replace(/\b(tharo|tharumo|tharamo)\b/g, 'give')
    .replace(/\b(cheyyam|cheyyamo|cheyyaamo)\b/g, 'do')
    .replace(/\b(kittum|kittumoo|kittamo)\b/g, 'get')
    .replace(/\b(veedu|veed)\b/g, 'house')
    .replace(/\b(paniyan|paniyaan|pani)\b/g, 'construction')
    .replace(/\b(paisa|paisayanu|panam)\b/g, 'money')
    .replace(/\b(mazha|mazhakalam|mazhakaalam)\b/g, 'rain')
    .replace(/\b(kaalath|kaalam|samayam)\b/g, 'time')
    .replace(/\b(edukam|edukamo|edukkam)\b/g, 'take')
    .replace(/\b(select|choose|kaanam)\b/g, 'select')
    .replace(/\b(solve|theerkam|theerkkam)\b/g, 'solve')
    .replace(/\b(nadakum|nadakkum|sambhavikkum)\b/g, 'happen');
}

// Extract key intent words from user input
function extractIntents(text) {
  const normalized = normalizeText(text);
  const intents = [];
  
  // Construction-related intents
  if (/\b(plot|land|site|area|size|dimension|measure)\b/.test(normalized)) intents.push('plot');
  if (/\b(budget|cost|price|money|expense|paisa|rupee|lakh|crore)\b/.test(normalized)) intents.push('budget');
  if (/\b(room|bedroom|bhk|hall|kitchen|bathroom|toilet)\b/.test(normalized)) intents.push('rooms');
  if (/\b(floor|storey|story|level|ground|first)\b/.test(normalized)) intents.push('floors');
  if (/\b(parking|garage|car|vehicle)\b/.test(normalized)) intents.push('parking');
  if (/\b(material|cement|steel|brick|sand|construction)\b/.test(normalized)) intents.push('materials');
  if (/\b(design|style|architecture|plan|layout)\b/.test(normalized)) intents.push('design');
  if (/\b(vastu|direction|east|west|north|south)\b/.test(normalized)) intents.push('vastu');
  
  // Platform navigation intents
  if (/\b(dashboard|navigate|click|button|where|section|menu)\b/.test(normalized)) intents.push('navigation');
  if (/\b(request|create|submit|form|new)\b/.test(normalized)) intents.push('request');
  if (/\b(proposal|architect|quote|estimate)\b/.test(normalized)) intents.push('proposals');
  if (/\b(message|chat|talk|contact|communication)\b/.test(normalized)) intents.push('messaging');
  if (/\b(upload|document|photo|file|image)\b/.test(normalized)) intents.push('upload');
  if (/\b(payment|pay|bill|invoice|transaction)\b/.test(normalized)) intents.push('payment');
  
  // Action intents
  if (/\b(how|kaise|method|way|process)\b/.test(normalized)) intents.push('how_to');
  if (/\b(what|kya|meaning|definition|explain)\b/.test(normalized)) intents.push('what_is');
  if (/\b(where|kaha|location|find|search)\b/.test(normalized)) intents.push('where_is');
  if (/\b(help|assist|guide|support|madad)\b/.test(normalized)) intents.push('help');
  
  return intents;
}

// Advanced similarity calculation with multiple algorithms
function calculateAdvancedSimilarity(userInput, variant) {
  const input = normalizeText(userInput);
  const target = normalizeText(variant);
  
  // 1. Exact match (highest score)
  if (input === target) return 1.0;
  
  // 2. Substring match
  if (input.includes(target) || target.includes(input)) {
    const longer = input.length > target.length ? input : target;
    const shorter = input.length <= target.length ? input : target;
    return (shorter.length / longer.length) * 0.95;
  }
  
  // 3. Intent-based matching
  const inputIntents = extractIntents(input);
  const targetIntents = extractIntents(target);
  const commonIntents = inputIntents.filter(intent => targetIntents.includes(intent));
  
  if (commonIntents.length > 0) {
    const intentScore = (commonIntents.length / Math.max(inputIntents.length, targetIntents.length)) * 0.8;
    if (intentScore > 0.4) return intentScore;
  }
  
  // 4. Word overlap with importance weighting
  const inputWords = input.split(' ').filter(w => w.length > 2);
  const targetWords = target.split(' ').filter(w => w.length > 2);
  
  // Important words get higher weight
  const importantWords = ['plot', 'budget', 'room', 'bedroom', 'bathroom', 'kitchen', 'floor', 'parking', 'cost', 'price', 'size', 'area', 'design', 'material', 'dashboard', 'request', 'proposal', 'architect', 'upload', 'payment'];
  
  let weightedMatches = 0;
  let totalWeight = 0;
  
  inputWords.forEach(word => {
    const weight = importantWords.includes(word) ? 2 : 1;
    totalWeight += weight;
    
    if (targetWords.includes(word)) {
      weightedMatches += weight;
    } else {
      // Check for partial matches
      const partialMatch = targetWords.find(tw => tw.includes(word) || word.includes(tw));
      if (partialMatch) {
        weightedMatches += weight * 0.7;
      }
    }
  });
  
  if (totalWeight > 0) {
    const wordScore = (weightedMatches / totalWeight) * 0.7;
    if (wordScore > 0.3) return wordScore;
  }
  
  // 5. Fuzzy matching with Levenshtein distance
  return levenshteinSimilarity(input, target) * 0.6;
}

// Improved Levenshtein distance calculation
function levenshteinSimilarity(str1, str2) {
  if (str1.length === 0) return str2.length === 0 ? 1 : 0;
  if (str2.length === 0) return 0;
  
  const matrix = Array(str1.length + 1).fill().map(() => Array(str2.length + 1).fill(0));
  
  // Initialize first row and column
  for (let i = 0; i <= str1.length; i++) matrix[i][0] = i;
  for (let j = 0; j <= str2.length; j++) matrix[0][j] = j;
  
  // Fill the matrix
  for (let i = 1; i <= str1.length; i++) {
    for (let j = 1; j <= str2.length; j++) {
      const cost = str1[i - 1] === str2[j - 1] ? 0 : 1;
      matrix[i][j] = Math.min(
        matrix[i - 1][j] + 1,     // deletion
        matrix[i][j - 1] + 1,     // insertion
        matrix[i - 1][j - 1] + cost // substitution
      );
    }
  }
  
  const distance = matrix[str1.length][str2.length];
  const maxLen = Math.max(str1.length, str2.length);
  return maxLen === 0 ? 1 : Math.max(0, (maxLen - distance) / maxLen);
}

// Context-aware response selection
function selectBestResponse(matches, userContext, userInput) {
  if (matches.length === 0) return null;
  
  // Sort by confidence score
  matches.sort((a, b) => b.confidence - a.confidence);
  
  // If we have a very high confidence match, use it
  if (matches[0].confidence > 0.8) {
    return matches[0];
  }
  
  // Check if user has asked similar questions recently
  const recentTopics = userContext.askedAbout || [];
  const inputIntents = extractIntents(userInput);
  
  // Prefer matches that align with recent conversation context
  for (const match of matches) {
    const matchIntents = extractIntents(match.entry.answer);
    const contextRelevance = inputIntents.filter(intent => 
      matchIntents.some(mi => mi.includes(intent) || intent.includes(mi))
    ).length;
    
    if (contextRelevance > 0 && match.confidence > 0.5) {
      return match;
    }
  }
  
  // Return the highest confidence match
  return matches[0];
}

// Main intelligent matching function
function matchInput(userInput, userContext = {}) {
  const normalizedInput = normalizeText(userInput);
  
  // Handle very short or unclear inputs
  if (normalizedInput.length < 2) {
    return {
      reply: "I didn't catch that. Could you ask me something specific about construction? For example: 'plot size help', 'budget planning', 'room requirements', or 'dashboard navigation'.",
      askFeedback: false,
      topicId: "unclear"
    };
  }
  
  // Handle greetings with context awareness
  if (/\b(hi|hello|hey|namaste|helo|hii|good morning|good afternoon|good evening)\b/.test(normalizedInput)) {
    const greetings = [
      "Hello! 👋 I'm your BuildHub construction assistant. I can help with plot planning, budget estimation, room layouts, and using our platform. What would you like to know?",
      "Hi there! 😊 Ready to build your dream home? I can guide you through plot size, budget planning, room requirements, or help you navigate the dashboard. What interests you?",
      "Hey! Welcome to BuildHub! 🏠 I'm here to make your construction journey smooth. Ask me about anything - from technical details to using our platform!",
      "Namaste! 🙏 I'm your construction planning assistant. Whether you need help with measurements, costs, designs, or using our website, I'm here to help!"
    ];
    return {
      reply: greetings[Math.floor(Math.random() * greetings.length)],
      askFeedback: false,
      topicId: "greeting"
    };
  }
  
  // Handle thank you messages
  if (/\b(thank|thanks|thx|dhanyawad|shukriya|appreciate)\b/.test(normalizedInput)) {
    const thankResponses = [
      "You're very welcome! 😊 Happy to help with your construction project. Any other questions?",
      "My pleasure! 🌟 Building a home is exciting - I'm here whenever you need guidance!",
      "Glad I could help! 💪 Feel free to ask more about construction planning or using our platform.",
      "You're welcome! 🏗️ I love helping people build their dream homes. What else can I assist with?"
    ];
    return {
      reply: thankResponses[Math.floor(Math.random() * thankResponses.length)],
      askFeedback: false,
      topicId: "thanks"
    };
  }
  
  // Find all potential matches with confidence scores
  const matches = [];
  
  kb.forEach((entry) => {
    let bestVariantScore = 0;
    
    entry.question_variants.forEach((variant) => {
      const confidence = calculateAdvancedSimilarity(normalizedInput, variant);
      if (confidence > bestVariantScore) {
        bestVariantScore = confidence;
      }
    });
    
    if (bestVariantScore > 0.3) { // Lower threshold for consideration
      matches.push({
        entry,
        confidence: bestVariantScore
      });
    }
  });
  
  // Select the best response using context
  const bestMatch = selectBestResponse(matches, userContext, userInput);
  
  if (bestMatch && bestMatch.confidence > 0.4) {
    return {
      reply: bestMatch.entry.answer,
      askFeedback: true,
      topicId: bestMatch.entry.id,
      confidence: bestMatch.confidence
    };
  }
  
  // Intelligent fallback based on detected intents
  const intents = extractIntents(normalizedInput);
  
  if (intents.includes('help')) {
    return {
      reply: "I'm here to help! 🤝 I can assist with:\n\n🏠 **Construction Planning:**\n• Plot size and measurements\n• Budget estimation and costs\n• Room planning (bedrooms, bathrooms, kitchen)\n• House designs and styles\n• Materials and construction process\n\n💻 **Platform Navigation:**\n• Creating and managing requests\n• Viewing architect proposals\n• Dashboard navigation\n• Document uploads\n• Messaging architects\n\nWhat specific help do you need?",
      askFeedback: true,
      topicId: "help_comprehensive"
    };
  }
  
  if (intents.includes('navigation') || intents.includes('dashboard')) {
    return {
      reply: "I can help you navigate the BuildHub platform! 🧭\n\n**Quick Navigation Guide:**\n• **Create Request** - Big blue button on dashboard\n• **My Requests** - See all your submitted requests\n• **Proposals** - View architect quotes and designs\n• **Messages** - Chat with architects\n• **Profile** - Update your details\n\nWhat specific section do you need help finding?",
      askFeedback: true,
      topicId: "navigation_help"
    };
  }
  
  if (intents.includes('budget') || intents.includes('cost')) {
    return {
      reply: "I can help with budget planning! 💰\n\nConstruction costs typically range ₹1,500-₹3,000 per sqft depending on:\n• Location and local rates\n• Quality of materials\n• Design complexity\n• Number of floors\n\nFor accurate estimates, submit your request and architects will provide detailed quotes. What specific budget question do you have?",
      askFeedback: true,
      topicId: "budget_general"
    };
  }
  
  // Enhanced fallback with suggestions
  return {
    reply: "I want to help you, but I'm not sure what you're asking about. 🤔\n\n**Try asking me about:**\n• \"How to measure plot size\"\n• \"Construction budget help\"\n• \"Room planning guide\"\n• \"Dashboard navigation\"\n• \"Create new request\"\n• \"Upload documents\"\n\nOr just tell me what you're trying to do - I'll do my best to help! 😊",
    askFeedback: true,
    topicId: "clarification_needed"
  };
}

export { matchInput };
export default { matchInput };