<?php
/**
 * Maya AI Learning Engine Initialization Script
 * Sets up learning tables and initial data patterns
 */

require_once __DIR__ . '/../../admin/db.php';
require_once __DIR__ . '/../components/maya_learning_engine.php';

try {
    echo "<h2>🧠 Initializing Maya AI Learning Engine...</h2>";
    
    // Initialize learning engine
    $learningEngine = new MayaLearningEngine($con);
    echo "<p>✅ Learning engine initialized successfully!</p>";
    
    // Run initial data analysis
    echo "<p>📊 Analyzing current hotel data...</p>";
    $insights = $learningEngine->analyzeCurrentData();
    echo "<p>✅ Data analysis completed! Generated " . count($insights) . " insight categories.</p>";
    
    // Display insights summary
    echo "<h3>📈 Initial Learning Insights:</h3>";
    echo "<ul>";
    foreach ($insights as $type => $data) {
        echo "<li><strong>" . ucwords(str_replace('_', ' ', $type)) . ":</strong> " . 
             (is_array($data) ? count($data) . " patterns detected" : "Analysis completed") . "</li>";
    }
    echo "</ul>";
    
    echo "<h3>🎯 Learning Engine Features Activated:</h3>";
    echo "<ul>";
    echo "<li>✅ <strong>Pattern Recognition:</strong> Learns from user interactions</li>";
    echo "<li>✅ <strong>Data Analysis:</strong> Analyzes booking and pricing patterns</li>";
    echo "<li>✅ <strong>Intelligent Responses:</strong> Generates context-aware answers</li>";
    echo "<li>✅ <strong>Feedback Learning:</strong> Improves based on user feedback</li>";
    echo "<li>✅ <strong>Continuous Learning:</strong> Updates patterns in real-time</li>";
    echo "</ul>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>🚀 Maya Learning Engine Ready!</h3>";
    echo "<p style='color: #155724; margin-bottom: 0;'>Maya can now generate intelligent responses based on:</p>";
    echo "<ul style='color: #155724;'>";
    echo "<li>Hotel database analysis and patterns</li>";
    echo "<li>Previous conversation learning</li>";
    echo "<li>Real-time user feedback</li>";
    echo "<li>Booking trends and guest preferences</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p><a href='../index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Maya's Learning Engine</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24;'>";
    echo "<h3>❌ Error Initializing Learning Engine</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
}

h2 {
    color: #343a40;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

h3 {
    color: #495057;
    margin-top: 30px;
}

p {
    line-height: 1.6;
    color: #6c757d;
}

ul {
    line-height: 1.8;
}

li {
    margin-bottom: 5px;
}
</style>
