
// Simplified Maya Response System (Backup)
function getSimplifiedMayaResponse(query) {
    const queryLower = query.toLowerCase();
    
    // Room-related queries
    if (queryLower.includes("room") || queryLower.includes("available")) {
        return `Great question about our rooms! 🏨<br><br>We have several excellent options:<br>• <strong>Eatonville Room</strong> - KES 3,500/night<br>• <strong>Merit Room</strong> - KES 4,000/night<br><br>Both include free WiFi, parking, and 24/7 service. Which interests you more?`;
    }
    
    // Pricing queries
    if (queryLower.includes("price") || queryLower.includes("cost") || queryLower.includes("rate")) {
        return `Our room rates are very competitive! 💰<br><br>• <strong>Eatonville Room:</strong> KES 3,500 per night<br>• <strong>Merit Room:</strong> KES 4,000 per night<br><br>Both rates include all amenities and no deposit required. Would you like to book one?`;
    }
    
    // Booking queries
    if (queryLower.includes("book") || queryLower.includes("reserve")) {
        return `I'd love to help you with your booking! 📅<br><br>Here's how easy it is:<br>1. Choose your preferred room<br>2. Select your dates<br>3. Confirm your details<br>4. Pay on arrival (no deposit needed)<br><br>Which room would you like to book?`;
    }
    
    // General greetings
    if (queryLower.includes("hello") || queryLower.includes("hi") || queryLower.includes("hey")) {
        return `Hello! Welcome to Orlando International Resorts! 👋<br><br>I'm Maya, your AI assistant. I'm here to help you with:<br>• Room bookings and availability<br>• Pricing information<br>• Hotel amenities and services<br>• Local recommendations<br><br>What can I help you with today?`;
    }
    
    // Default response
    return `I'm here to help you with anything about Orlando International Resorts. I can assist with:<br><br>🏨 <strong>Room Information</strong><br>💰 <strong>Pricing Details</strong><br>📅 <strong>Booking Assistance</strong><br>🌟 <strong>Hotel Services</strong><br><br>What would you like to know more about?`;
}
