<?php
/**
 * Maya Natural Language Enhancement
 * Makes Maya's responses more conversational and human-like
 */

class MayaNaturalLanguage {
    private $conversationFillers = [
        'thinking' => [
            "Let me think about that for a moment...",
            "Hmm, interesting question...",
            "Give me just a second to check that...",
            "That's a great point, let me see...",
            "Oh, I like where you're going with this...",
            "Let me pull up the latest information for you...",
            "Just checking our current availability...",
            "Perfect timing - let me look into that..."
        ],
        'acknowledgment' => [
            "I hear you!",
            "Absolutely!",
            "I totally get that!",
            "That makes perfect sense!",
            "I'm with you on that!",
            "You're absolutely right!",
            "I completely understand!",
            "That's exactly what I would think too!",
            "You've got a great point there!"
        ],
        'transition' => [
            "So here's what I'm thinking...",
            "Actually, this is perfect because...",
            "You know what? This works out great...",
            "Here's something interesting...",
            "This is where it gets exciting...",
            "Here's what I found...",
            "So based on what you're looking for...",
            "Let me share something that might interest you..."
        ],
        'enthusiasm' => [
            "Oh, this is one of my favorites to talk about!",
            "I'm so excited to help with this!",
            "This is right up my alley!",
            "Perfect question!",
            "I love helping with this!",
            "This is exactly the kind of thing I enjoy helping with!",
            "Ooh, you've come to the right place!",
            "I'm thrilled to help you with this!"
        ],
        'empathy' => [
            "I can imagine how important this is to you...",
            "I understand that this might feel overwhelming...",
            "I know planning can be stressful, so let me help...",
            "I get it - you want everything to be perfect...",
            "I can see why you'd want to know more about this...",
            "That sounds like exactly what you need...",
            "I totally understand your concern..."
        ],
        'reassurance' => [
            "Don't worry, I've got you covered!",
            "No problem at all - this is easy to fix!",
            "You're in good hands!",
            "This happens all the time, and it's totally fine!",
            "I'll make sure everything works out perfectly!",
            "Trust me, we'll get this sorted out!",
            "I'm here to make this as smooth as possible for you!"
        ]
    ];
    
    private $personalityTraits = [
        'helpfulness' => 0.95,
        'enthusiasm' => 0.85,
        'friendliness' => 0.90,
        'professionalism' => 0.80,
        'empathy' => 0.88
    ];
    
    public function makeResponseNatural($response, $context = []) {
        $naturalResponse = "";
        
        // Add conversation opener based on context
        if ($this->shouldAddOpener($context)) {
            $naturalResponse .= $this->getConversationOpener($context) . " ";
        }
        
        // Add main response
        $naturalResponse .= $this->enhanceMainResponse($response, $context);
        
        // Add natural closure
        if ($this->shouldAddClosure($context)) {
            $naturalResponse .= " " . $this->getConversationClosure($context);
        }
        
        return $naturalResponse;
    }
    
    private function shouldAddOpener($context) {
        // Add opener for new conversations or topic changes
        return !isset($context['conversation_depth']) || $context['conversation_depth'] < 2;
    }
    
    private function getConversationOpener($context) {
        $intent = $context['intent'] ?? 'general';
        $sentiment = $context['sentiment'] ?? 'neutral';
        
        if ($sentiment === 'positive') {
            return $this->conversationFillers['enthusiasm'][array_rand($this->conversationFillers['enthusiasm'])];
        }
        
        if ($intent === 'pricing_inquiry') {
            return "Great question about pricing!";
        }
        
        if ($intent === 'booking_immediate') {
            return "Oh, last-minute booking? I love the spontaneous spirit!";
        }
        
        return $this->conversationFillers['acknowledgment'][array_rand($this->conversationFillers['acknowledgment'])];
    }
    
    private function enhanceMainResponse($response, $context) {
        // Remove excessive emojis and formal language
        $enhanced = $response;
        
        // Add natural connectors
        $enhanced = $this->addNaturalConnectors($enhanced);
        
        // Vary sentence structure
        $enhanced = $this->varySentenceStructure($enhanced);
        
        // Add personal touches
        $enhanced = $this->addPersonalTouches($enhanced, $context);
        
        return $enhanced;
    }
    
    private function addNaturalConnectors($text) {
        $connectors = [
            'Also,' => 'And actually,',
            'Additionally,' => 'Plus,',
            'Furthermore,' => 'What\'s more,',
            'Moreover,' => 'And here\'s the thing -'
        ];
        
        return str_replace(array_keys($connectors), array_values($connectors), $text);
    }
    
    private function varySentenceStructure($text) {
        // Add variety to sentence beginnings
        $variations = [
            'Our rooms' => ['We have some amazing rooms', 'I\'d love to show you our rooms', 'Let me tell you about our rooms'],
            'The price' => ['As for pricing', 'When it comes to cost', 'Price-wise'],
            'We offer' => ['You\'ll get', 'We include', 'What we provide is']
        ];
        
        foreach ($variations as $original => $replacements) {
            if (strpos($text, $original) !== false) {
                $replacement = $replacements[array_rand($replacements)];
                $text = str_replace($original, $replacement, $text);
                break; // Only replace one to maintain natural flow
            }
        }
        
        return $text;
    }
    
    private function addPersonalTouches($text, $context) {
        $userId = $context['user_id'] ?? null;
        $returning = $context['returning_user'] ?? false;
        
        if ($returning) {
            $text = "Welcome back! " . $text;
        }
        
        // Add personal observations
        if (isset($context['user_preferences']['budget_conscious']) && $context['user_preferences']['budget_conscious']) {
            $text = str_replace('KES', 'just KES', $text);
        }
        
        return $text;
    }
    
    private function getConversationClosure($context) {
        $closures = [
            "What do you think about that?",
            "Does that sound good to you?",
            "How does that work for you?",
            "Any questions about that?",
            "What would you like to know more about?"
        ];
        
        return $closures[array_rand($closures)];
    }
    
    public function generateThinkingResponse() {
        return $this->conversationFillers['thinking'][array_rand($this->conversationFillers['thinking'])];
    }
    
    public function shouldShowThinking($complexity) {
        return $complexity > 0.6 || rand(1, 100) <= 30; // 30% chance for variety
    }
}
?>
