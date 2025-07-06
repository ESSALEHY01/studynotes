<?php
session_start();
require_once('../db_connection.php');
require_once('../includes/functions.php');
require_once('../includes/ai_functions.php');

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update AI service settings
    if (isset($_POST['ai_service'])) {
        $ai_config['service'] = $_POST['ai_service'];

        // Update the API key if provided
        if (!empty($_POST['api_key'])) {
            $ai_config['api_key'] = $_POST['api_key'];
        }

        // Update temperature if provided
        if (isset($_POST['temperature'])) {
            $ai_config['temperature'] = floatval($_POST['temperature']);
        }

        // Update max tokens if provided
        if (isset($_POST['max_tokens'])) {
            $ai_config['max_tokens'] = intval($_POST['max_tokens']);
        }

        // Update use_fallback setting
        $ai_config['use_fallback'] = isset($_POST['use_fallback']) && $_POST['use_fallback'] === 'on';

        // Save the updated configuration to a file
        $config_file = '../includes/ai_config.php';
        $config_content = "<?php\n// AI Configuration - Auto-generated file\n\$ai_config = " . var_export($ai_config, true) . ";\n?>";

        if (file_put_contents($config_file, $config_content)) {
            $success_message = "AI settings updated successfully!";
        } else {
            $error_message = "Failed to save AI settings. Please check file permissions.";
        }
    }
}

// Include header
$page_title = "AI Settings";
$active_page = "ai_settings";
include('../includes/admin_header.php');
?>

<div class="content-wrapper">
    <div class="content-header">
        <h1>AI Settings</h1>
        <p>Configure the AI service used for generating quizzes and summaries.</p>
    </div>

    <?php if (isset($success_message)): ?>
    <div class="ai-alert ai-alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $success_message; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="ai-alert ai-alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <div class="ai-settings-grid">
        <div class="ai-card">
            <div class="ai-card-header">
                <h2><i class="fas fa-cogs config-icon"></i>AI Service Configuration</h2>
                <div class="ai-status-indicator ai-status-active">
                    <i class="fas fa-circle"></i>
                    Active
                </div>
            </div>
            <div class="ai-card-body">
                <form method="POST" action="">
                    <div class="ai-form-group">
                        <label for="ai_service" class="ai-form-label">
                            <i class="fas fa-robot"></i>
                            AI Service
                        </label>
                        <select name="ai_service" id="ai_service" class="ai-form-control">
                            <option value="grok" selected>🤖 Grok 3 (xAI)</option>
                        </select>
                        <div class="ai-help-text">
                            <i class="fas fa-info-circle"></i>
                            Only Grok 3 is supported in this version
                        </div>
                        <div class="ai-current-value">
                            <i class="fas fa-check"></i>
                            Currently using: Grok 3 via GitHub AI Models
                        </div>
                    </div>

                    <div class="ai-form-group">
                        <label for="api_key" class="ai-form-label">
                            <i class="fas fa-key"></i>
                            API Key
                        </label>
                        <input type="text" name="api_key" id="api_key" class="ai-form-control" placeholder="Enter new API key (leave empty to keep current)" />
                        <div class="ai-help-text">
                            <i class="fas fa-shield-alt"></i>
                            GitHub Personal Access Token for Grok 3 API access
                        </div>
                        <div class="ai-current-value">
                            <i class="fas fa-eye-slash"></i>
                            Current key: <?php echo substr($ai_config['api_key'], 0, 5) . '...' . substr($ai_config['api_key'], -5); ?>
                        </div>
                    </div>

                    <div class="ai-form-group">
                        <label for="temperature" class="ai-form-label">
                            <i class="fas fa-thermometer-half"></i>
                            Temperature
                        </label>
                        <input type="number" name="temperature" id="temperature" class="ai-form-control" value="<?php echo $ai_config['temperature']; ?>" min="0" max="1" step="0.1" />
                        <div class="ai-help-text">
                            <i class="fas fa-balance-scale"></i>
                            Controls randomness: 0 = deterministic, 1 = maximum creativity
                        </div>
                        <div class="ai-current-value">
                            <i class="fas fa-chart-line"></i>
                            Current: <?php echo $ai_config['temperature']; ?> (<?php echo $ai_config['temperature'] < 0.3 ? 'Conservative' : ($ai_config['temperature'] > 0.7 ? 'Creative' : 'Balanced'); ?>)
                        </div>
                    </div>

                    <div class="ai-form-group">
                        <label for="max_tokens" class="ai-form-label">
                            <i class="fas fa-text-width"></i>
                            Max Tokens
                        </label>
                        <input type="number" name="max_tokens" id="max_tokens" class="ai-form-control" value="<?php echo $ai_config['max_tokens']; ?>" min="100" max="8000" step="100" />
                        <div class="ai-help-text">
                            <i class="fas fa-ruler"></i>
                            Maximum length of the AI response (affects cost and performance)
                        </div>
                        <div class="ai-current-value">
                            <i class="fas fa-calculator"></i>
                            Current: <?php echo $ai_config['max_tokens']; ?> tokens (~<?php echo round($ai_config['max_tokens'] * 0.75); ?> words)
                        </div>
                    </div>

                    <div class="ai-form-group">
                        <label class="ai-form-label">
                            <i class="fas fa-toggle-on"></i>
                            Fallback Mode
                        </label>
                        <div class="ai-checkbox-group">
                            <input type="checkbox" name="use_fallback" id="use_fallback" <?php echo $ai_config['use_fallback'] ? 'checked' : ''; ?> />
                            <label for="use_fallback" class="ai-checkbox-label">
                                <i class="fas fa-shield-alt"></i>
                                Use fallback mode (generate simple responses without calling the API)
                            </label>
                        </div>
                        <div class="ai-help-text">
                            <i class="fas fa-info-circle"></i>
                            Enable this for testing or when API is unavailable
                        </div>
                    </div>

                    <div class="ai-form-group">
                        <button type="submit" class="ai-btn ai-btn-primary">
                            <i class="fas fa-save"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="ai-card">
            <div class="ai-card-header">
                <h2><i class="fas fa-flask test-icon"></i>Test AI Service</h2>
                <div class="ai-status-indicator ai-status-testing">
                    <i class="fas fa-vial"></i>
                    Testing
                </div>
            </div>
            <div class="ai-card-body">
                <p style="color: var(--secondary-color); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle" style="color: var(--accent-color);"></i>
                    Use this form to test the current AI service configuration and verify connectivity.
                </p>

                <form id="testAiForm">
                    <div class="ai-form-group">
                        <label for="test_prompt" class="ai-form-label">
                            <i class="fas fa-comment-dots"></i>
                            Test Prompt
                        </label>
                        <textarea name="test_prompt" id="test_prompt" class="ai-form-control" rows="4" placeholder="Enter a prompt to test the AI service (e.g., 'Generate a simple math question')"></textarea>
                        <div class="ai-help-text">
                            <i class="fas fa-lightbulb"></i>
                            Try prompts like: "Create a quiz question about photosynthesis" or "Summarize the concept of gravity"
                        </div>
                    </div>

                    <div class="ai-form-group">
                        <button type="submit" class="ai-btn ai-btn-secondary">
                            <i class="fas fa-play"></i>
                            Test AI Service
                        </button>
                    </div>
                </form>

                <div id="testResults" class="ai-test-results">
                    <h3><i class="fas fa-chart-bar"></i>Test Results</h3>
                    <div class="ai-response-container">
                        <div id="testResultsContent"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testForm = document.getElementById('testAiForm');
    const testResults = document.getElementById('testResults');
    const testResultsContent = document.getElementById('testResultsContent');

    if (testForm) {
        testForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const prompt = document.getElementById('test_prompt').value;

            if (!prompt) {
                alert('Please enter a prompt to test.');
                return;
            }

            // Show loading indicator with enhanced styling
            testResults.classList.add('show');
            testResultsContent.innerHTML = `
                <div class="ai-loading">
                    <i class="fas fa-spinner"></i>
                    Testing AI service... Please wait.
                </div>
            `;

            // Send test request
            fetch('../api/test_ai.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ prompt: prompt })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    testResultsContent.innerHTML = `
                        <div style="margin-bottom: 15px; padding: 10px; background: rgba(40, 167, 69, 0.1); border: 1px solid #28a745; border-radius: 6px; color: #155724; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-check-circle"></i>
                            <strong>AI Response Received Successfully</strong>
                        </div>
                        <div style="background: var(--card-color); border: 1px solid rgba(212, 163, 115, 0.2); border-radius: 8px; padding: 15px; font-family: 'Segoe UI', sans-serif; line-height: 1.6; color: var(--text-color); white-space: pre-wrap; word-wrap: break-word;">
                            ${data.response}
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: var(--secondary-color); display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-clock"></i>
                            Response generated at ${new Date().toLocaleTimeString()}
                        </div>
                    `;
                } else {
                    testResultsContent.innerHTML = `
                        <div class="ai-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Error:</strong> ${data.error || 'Failed to get response from AI service'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                testResultsContent.innerHTML = `
                    <div class="ai-error">
                        <i class="fas fa-times-circle"></i>
                        <strong>Connection Error:</strong> Failed to communicate with the server.
                    </div>
                `;
            });
        });
    }
});
</script>

<style>
/* Enhanced AI Settings Styling */
.content-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.content-header {
    margin-bottom: 30px;
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, rgba(101, 53, 15, 0.05) 0%, rgba(212, 163, 115, 0.05) 100%);
    border-radius: 12px;
    border: 1px solid rgba(212, 163, 115, 0.2);
}

.content-header h1 {
    color: var(--primary-color);
    font-size: 2.2rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.content-header h1::before {
    content: '\f544';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    color: var(--highlight-color);
    font-size: 1.8rem;
}

.content-header p {
    color: var(--secondary-color);
    font-size: 1.1rem;
    margin: 0;
}

/* Enhanced Card Styling */
.ai-settings-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 25px;
    margin-top: 20px;
}

.ai-card {
    background: var(--card-color);
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(212, 163, 115, 0.15);
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.ai-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent-color), var(--highlight-color));
}

.ai-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
}

.ai-card-header {
    padding: 25px 30px 20px;
    background: linear-gradient(135deg, rgba(101, 53, 15, 0.02) 0%, rgba(212, 163, 115, 0.02) 100%);
    border-bottom: 1px solid rgba(212, 163, 115, 0.1);
}

.ai-card-header h2 {
    color: var(--primary-color);
    font-size: 1.4rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ai-card-header .config-icon {
    color: var(--highlight-color);
    font-size: 1.2rem;
}

.ai-card-header .test-icon {
    color: var(--accent-color);
    font-size: 1.2rem;
}

.ai-card-body {
    padding: 30px;
}

/* Enhanced Form Styling */
.ai-form-group {
    margin-bottom: 25px;
    position: relative;
}

.ai-form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 8px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ai-form-label i {
    color: var(--highlight-color);
    font-size: 12px;
}

.ai-form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid rgba(212, 163, 115, 0.2);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background-color: #fafafa;
    color: var(--text-color);
}

.ai-form-control:focus {
    outline: none;
    border-color: var(--highlight-color);
    background-color: #fff;
    box-shadow: 0 0 0 3px rgba(232, 135, 30, 0.1);
}

.ai-form-control:hover {
    border-color: var(--accent-color);
    background-color: #fff;
}

/* Status Indicators */
.ai-status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ai-status-active {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: 1px solid #28a745;
}

.ai-status-testing {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    color: #856404;
    border: 1px solid #ffc107;
}

.ai-status-inactive {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border: 1px solid #dc3545;
}

/* Enhanced Help Text */
.ai-help-text {
    font-size: 12px;
    color: var(--secondary-color);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ai-help-text i {
    color: var(--accent-color);
    font-size: 10px;
}

/* Current Value Display */
.ai-current-value {
    background: rgba(101, 53, 15, 0.05);
    border: 1px solid rgba(212, 163, 115, 0.2);
    border-radius: 6px;
    padding: 8px 12px;
    margin-top: 8px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    gap: 8px;
}

.ai-current-value i {
    color: var(--highlight-color);
}

/* Enhanced Checkbox Styling */
.ai-checkbox-group {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: rgba(101, 53, 15, 0.02);
    border: 1px solid rgba(212, 163, 115, 0.15);
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.ai-checkbox-group:hover {
    background: rgba(101, 53, 15, 0.05);
    border-color: var(--accent-color);
}

.ai-checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--highlight-color);
    cursor: pointer;
}

.ai-checkbox-label {
    font-weight: 500;
    color: var(--text-color);
    cursor: pointer;
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ai-checkbox-label i {
    color: var(--accent-color);
    font-size: 14px;
}

/* Enhanced Button Styling */
.ai-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.ai-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.ai-btn:hover::before {
    left: 100%;
}

.ai-btn-primary {
    background: linear-gradient(135deg, var(--highlight-color) 0%, var(--primary-color) 100%);
    color: var(--light-text);
    box-shadow: 0 4px 15px rgba(232, 135, 30, 0.3);
}

.ai-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(232, 135, 30, 0.4);
}

.ai-btn-secondary {
    background: linear-gradient(135deg, var(--accent-color) 0%, var(--secondary-color) 100%);
    color: var(--light-text);
    box-shadow: 0 4px 15px rgba(212, 163, 115, 0.3);
}

.ai-btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 163, 115, 0.4);
}

/* Test Results Styling */
.ai-test-results {
    margin-top: 20px;
    padding: 20px;
    background: rgba(101, 53, 15, 0.02);
    border: 1px solid rgba(212, 163, 115, 0.2);
    border-radius: 12px;
    display: none;
}

.ai-test-results.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ai-test-results h3 {
    color: var(--primary-color);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ai-test-results h3 i {
    color: var(--highlight-color);
}

.ai-response-container {
    background: var(--card-color);
    border: 1px solid rgba(212, 163, 115, 0.15);
    border-radius: 8px;
    padding: 15px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    white-space: pre-wrap;
    word-wrap: break-word;
}

.ai-loading {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--secondary-color);
    font-style: italic;
}

.ai-loading i {
    animation: spin 1s linear infinite;
    color: var(--highlight-color);
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.ai-error {
    color: var(--danger-color);
    background: rgba(220, 53, 69, 0.05);
    border: 1px solid rgba(220, 53, 69, 0.2);
    border-radius: 6px;
    padding: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.ai-error i {
    color: var(--danger-color);
}

/* Alert Styling */
.ai-alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 500;
}

.ai-alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border: 1px solid #28a745;
}

.ai-alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border: 1px solid #dc3545;
}

.ai-alert i {
    font-size: 16px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .content-wrapper {
        padding: 15px;
    }

    .content-header {
        padding: 15px;
        text-align: left;
    }

    .content-header h1 {
        font-size: 1.8rem;
        justify-content: flex-start;
    }

    .ai-card-header,
    .ai-card-body {
        padding: 20px;
    }

    .ai-form-control {
        padding: 10px 12px;
    }

    .ai-btn {
        padding: 10px 20px;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .content-header h1 {
        font-size: 1.5rem;
        flex-direction: column;
        gap: 8px;
    }

    .ai-checkbox-group {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .ai-btn {
        width: 100%;
        justify-content: center;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .content-header {
        background: linear-gradient(135deg, rgba(101, 53, 15, 0.1) 0%, rgba(212, 163, 115, 0.1) 100%);
        border-color: rgba(212, 163, 115, 0.3);
    }

    .ai-card {
        background: #2d2d2d;
        border-color: rgba(212, 163, 115, 0.2);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    .ai-card-header {
        background: linear-gradient(135deg, rgba(101, 53, 15, 0.05) 0%, rgba(212, 163, 115, 0.05) 100%);
        border-bottom-color: rgba(212, 163, 115, 0.2);
    }

    .ai-form-control {
        background-color: #3a3a3a;
        border-color: rgba(212, 163, 115, 0.3);
        color: #e0e0e0;
    }

    .ai-form-control:focus {
        background-color: #404040;
        border-color: var(--highlight-color);
        box-shadow: 0 0 0 3px rgba(232, 135, 30, 0.2);
    }

    .ai-form-control:hover {
        background-color: #404040;
        border-color: var(--accent-color);
    }

    .ai-current-value {
        background: rgba(101, 53, 15, 0.1);
        border-color: rgba(212, 163, 115, 0.3);
        color: #d4a574;
    }

    .ai-checkbox-group {
        background: rgba(101, 53, 15, 0.05);
        border-color: rgba(212, 163, 115, 0.2);
    }

    .ai-checkbox-group:hover {
        background: rgba(101, 53, 15, 0.1);
        border-color: var(--accent-color);
    }

    .ai-test-results {
        background: rgba(101, 53, 15, 0.05);
        border-color: rgba(212, 163, 115, 0.3);
    }

    .ai-response-container {
        background: #3a3a3a;
        border-color: rgba(212, 163, 115, 0.2);
        color: #e0e0e0;
    }

    .ai-help-text {
        color: #b0b0b0;
    }
}
</style>

<?php include('../includes/admin_footer.php'); ?>
