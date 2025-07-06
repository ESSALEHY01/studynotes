<?php
/**
 * AI Integration Functions for StudyNotes
 *
 * This file contains functions for interacting with AI services
 * like Claude or DeepSeek for generating quizzes and summaries.
 */

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define a debug function
function ai_debug_log($message, $data = null) {
    $log_file = __DIR__ . '/../logs/ai_debug.log';
    $log_dir = dirname($log_file);

    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    // Format the log message
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";

    if ($data !== null) {
        $log_message .= "\nData: " . print_r($data, true);
    }

    $log_message .= "\n" . str_repeat('-', 80) . "\n";

    // Write to log file
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Load AI configuration from config file
 * If the config file doesn't exist, use default values
 */
$ai_config_file = __DIR__ . '/ai_config.php';
if (file_exists($ai_config_file)) {
    require_once($ai_config_file);
} else {
    // Default configuration - Grok 3 only
    $ai_config = [
        'service' => 'grok', // Only Grok 3 supported
        'api_key' => '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', // GitHub Personal Access Token for Grok 3
        'github_username' => 'studynotes-ai', // GitHub username associated with the token
        'grok_endpoint' => 'https://models.github.ai/inference', // Grok 3 API endpoint
        'grok_model' => 'xai/grok-3', // Grok 3 model name
        'max_tokens' => 4000,
        'temperature' => 0.7,
        'use_fallback' => false // Set to false to use the actual API
    ];
}

/**
 * Generate a multiple-choice quiz based on note content
 *
 * @param string $note_content The content of the note
 * @param string $note_title The title of the note
 * @param int $num_questions Number of questions to generate (default: 5)
 * @param string $difficulty Difficulty level (easy, medium, hard)
 * @return array|false Array of questions with multiple-choice options, or false on failure
 */
function generate_quiz($note_content, $note_title, $num_questions = 5, $difficulty = 'medium') {
    global $ai_config;

    ai_debug_log("Starting generate_quiz function (MCQ format)", [
        'note_title' => $note_title,
        'num_questions' => $num_questions,
        'difficulty' => $difficulty
    ]);

    // Sanitize and prepare the content
    $content = strip_tags($note_content);

    // Create the prompt for the AI
    $prompt = "Based on the following study note titled '{$note_title}', generate {$num_questions} {$difficulty} difficulty multiple-choice quiz questions. ";
    $prompt .= "For each question, provide the question text, one correct answer, and three incorrect answers (distractors). ";
    $prompt .= "Make sure the distractors are plausible but clearly incorrect. ";
    $prompt .= "Format your response as a JSON array with objects containing 'question', 'correct_answer', and 'incorrect_answers' fields. ";
    $prompt .= "The 'incorrect_answers' field should be an array of three strings.\n\n";
    $prompt .= "Study Note Content:\n{$content}\n\n";
    $prompt .= "Return only valid JSON in this format: [{\"question\": \"Question text here?\", \"correct_answer\": \"Correct answer here\", \"incorrect_answers\": [\"Wrong answer 1\", \"Wrong answer 2\", \"Wrong answer 3\"]}]";

    // Add specific instructions based on the AI service
    if ($ai_config['service'] === 'deepseek') {
        $prompt .= "\n\nIMPORTANT: Your response must be valid JSON that can be parsed with json_decode(). Do not include any explanations, markdown formatting, or code blocks around the JSON. Just return the raw JSON array.";
    } else if ($ai_config['service'] === 'grok') {
        $prompt .= "\n\nIMPORTANT: Your response must be valid JSON that can be parsed with json_decode(). Return only the JSON array without any explanations, markdown formatting, or code blocks. The JSON should be directly parseable.";
    }

    ai_debug_log("Calling AI service: " . $ai_config['service']);

    // Call Grok 3 API
    if ($ai_config['service'] === 'grok') {
        $response = call_grok_api($prompt);
        ai_debug_log("Grok 3 API response", [
            'response_type' => gettype($response),
            'response_preview' => is_string($response) ? substr($response, 0, 100) : 'Not a string'
        ]);
    } else {
        ai_debug_log("Only Grok 3 is supported. Current service: " . $ai_config['service']);
        return false;
    }

    // Parse the response
    if ($response) {
        ai_debug_log("Received response, attempting to parse");

        // If response is already an array (from fallback), return it
        if (is_array($response)) {
            ai_debug_log("Response is already an array, returning directly");
            return $response;
        }

        // Try to parse the entire response as JSON first
        $questions = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($questions)) {
            ai_debug_log("Successfully parsed response as JSON");
            return $questions;
        }

        ai_debug_log("Failed to parse entire response as JSON: " . json_last_error_msg());

        // If that fails, try to extract JSON from the response
        $json_start = strpos($response, '[');
        $json_end = strrpos($response, ']') + 1;

        if ($json_start !== false && $json_end !== false) {
            $json_string = substr($response, $json_start, $json_end - $json_start);
            ai_debug_log("Extracted JSON substring: " . $json_string);

            $questions = json_decode($json_string, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($questions)) {
                ai_debug_log("Successfully parsed JSON substring");
                return $questions;
            }

            ai_debug_log("Failed to parse JSON substring: " . json_last_error_msg());
        }

        // If still no valid JSON, log the failure and use fallback
        ai_debug_log("Failed to parse response as valid MCQ JSON format");

        // Log the response for debugging
        ai_debug_log("Failed to parse AI response with any method", [
            'response' => $response
        ]);
        error_log("Failed to parse AI response as JSON: " . $response);
    } else {
        ai_debug_log("No response received from AI service");
    }

    // Use fallback as last resort
    ai_debug_log("Using fallback quiz generation as last resort");
    return generate_fallback_response("generate quiz questions for note titled '{$note_title}'");
}

/**
 * Generate a summary of note content
 *
 * @param string $note_content The content of the note
 * @param string $note_title The title of the note
 * @param int $max_length Maximum length of the summary in characters
 * @return string|false The generated summary, or false on failure
 */
function generate_summary($note_content, $note_title, $max_length = 500) {
    global $ai_config;

    ai_debug_log("Starting generate_summary function", [
        'note_title' => $note_title,
        'max_length' => $max_length
    ]);

    // Sanitize and prepare the content
    $content = strip_tags($note_content);

    // Create the prompt for the AI
    $prompt = "Create a concise summary of the following study note titled '{$note_title}'. ";
    $prompt .= "The summary should capture the key points and main ideas, and should be no more than {$max_length} characters. ";
    $prompt .= "Make the summary clear, informative, and useful for study revision.\n\n";
    $prompt .= "Study Note Content:\n{$content}\n\n";

    // Add specific instructions based on the AI service
    if ($ai_config['service'] === 'claude') {
        $prompt .= "Summary:";
    } else if ($ai_config['service'] === 'deepseek') {
        $prompt .= "IMPORTANT: Provide only the summary text without any additional explanations, introductions, or markdown formatting. The summary should be directly usable as a study aid.";
    } else if ($ai_config['service'] === 'grok') {
        $prompt .= "IMPORTANT: Provide only the summary text without any additional explanations, introductions, or markdown formatting. The summary should be concise, clear, and directly usable as a study aid.";
    }

    ai_debug_log("Calling AI service: " . $ai_config['service']);

    // Call Grok 3 API
    if ($ai_config['service'] === 'grok') {
        $response = call_grok_api($prompt);
        ai_debug_log("Grok 3 API response", [
            'response_type' => gettype($response),
            'response_preview' => is_string($response) ? substr($response, 0, 100) : 'Not a string'
        ]);
    } else {
        ai_debug_log("Only Grok 3 is supported. Current service: " . $ai_config['service']);
        return false;
    }

    // Process the response
    if ($response) {
        // Clean up the response
        $summary = trim($response);

        // Remove any markdown formatting or prefixes like "Summary:" that might be in the response
        $summary = preg_replace('/^(summary|summary:)\s*/i', '', $summary);

        // Remove any markdown code blocks
        $summary = preg_replace('/```.*?```/s', '', $summary);

        // Ensure the summary doesn't exceed the maximum length
        if (strlen($summary) > $max_length) {
            $summary = substr($summary, 0, $max_length);

            // Make sure we don't cut off in the middle of a word
            $last_space = strrpos($summary, ' ');
            if ($last_space !== false) {
                $summary = substr($summary, 0, $last_space);
            }

            $summary .= '...';
        }

        return $summary;
    }

    return false;
}





/**
 * Generate a fallback response when the AI service is unavailable
 *
 * @param string $prompt The original prompt
 * @return mixed The fallback response (string for summaries, array for quizzes)
 */
function generate_fallback_response($prompt) {
    ai_debug_log("Generating fallback response for prompt", [
        'prompt_preview' => substr($prompt, 0, 100) . '...'
    ]);

    // For quiz generation
    if (strpos($prompt, 'generate quiz questions') !== false || strpos($prompt, 'multiple-choice quiz questions') !== false) {
        // Extract the note title and content from the prompt
        preg_match('/titled \'(.*?)\'/', $prompt, $title_matches);
        $title = isset($title_matches[1]) ? $title_matches[1] : 'Study Note';

        // All quizzes are now multiple-choice format
        $fallback_quiz = [
            [
                'question' => 'What is the main topic of ' . $title . '?',
                'correct_answer' => 'The key subject discussed in the note',
                'incorrect_answers' => [
                    'A minor detail mentioned briefly',
                    'An unrelated concept not in the note',
                    'The author of the note'
                ]
            ],
            [
                'question' => 'Which of the following best describes a key concept from ' . $title . '?',
                'correct_answer' => 'One of the main ideas presented in the note',
                'incorrect_answers' => [
                    'A footnote or citation',
                    'The formatting style used',
                    'The date the note was created'
                ]
            ],
            [
                'question' => 'How would you best apply the knowledge from ' . $title . '?',
                'correct_answer' => 'Using the concepts in practical situations',
                'incorrect_answers' => [
                    'Memorizing the text word for word',
                    'Ignoring the information entirely',
                    'Only focusing on the headings'
                ]
            ],
            [
                'question' => 'What is the purpose of studying ' . $title . '?',
                'correct_answer' => 'To gain understanding of the subject matter',
                'incorrect_answers' => [
                    'To increase the page count of your notes',
                    'To practice handwriting skills',
                    'To fill time between other activities'
                ]
            ],
            [
                'question' => 'How does ' . $title . ' relate to other topics?',
                'correct_answer' => 'It connects to form a comprehensive understanding',
                'incorrect_answers' => [
                    'It has no relation to any other topics',
                    'Only through alphabetical ordering',
                    'The relation is purely coincidental'
                ]
            ]
        ];

        ai_debug_log("Generated fallback quiz", [
            'question_count' => count($fallback_quiz),
            'format' => 'multiple-choice'
        ]);

        return $fallback_quiz;
    }
    // For summary generation
    else if (strpos($prompt, 'Create a concise summary') !== false) {
        // Extract the note title from the prompt
        preg_match('/titled \'(.*?)\'/', $prompt, $title_matches);
        $title = isset($title_matches[1]) ? $title_matches[1] : 'Study Note';

        // Generate a simple fallback summary
        $fallback_summary = "This is an automatically generated placeholder summary for \"$title\". " .
                           "The AI service was unable to generate a proper summary at this time. " .
                           "Please try again later or review the original note for complete information. " .
                           "The note contains important information that would typically be summarized to highlight key points, " .
                           "main concepts, and essential details for study revision.";

        ai_debug_log("Generated fallback summary", [
            'summary_length' => strlen($fallback_summary)
        ]);

        return $fallback_summary;
    }

    return false;
}



/**
 * Call the Grok 3 API
 *
 * @param string $prompt The prompt to send to Grok 3
 * @return string|false The response from Grok 3, or false on failure
 */
function call_grok_api($prompt) {
    global $ai_config;

    // Check if we should use fallback mode
    if (isset($ai_config['use_fallback']) && $ai_config['use_fallback'] === true) {
        ai_debug_log("Using fallback mode directly as configured");
        return generate_fallback_response($prompt);
    }

    // Prepare the request data
    $data = [
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are an educational assistant that helps students with their studies.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => $ai_config['temperature'],
        'top_p' => 1,
        'model' => $ai_config['grok_model']
    ];

    // Initialize cURL
    $ch = curl_init($ai_config['grok_endpoint'] . '/chat/completions');

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $ai_config['api_key'],
        'User-Agent: StudyNotes-App'
    ]);

    // Log the request
    ai_debug_log("Sending request to Grok 3 API", [
        'endpoint' => $ai_config['grok_endpoint'],
        'model' => $ai_config['grok_model']
    ]);

    // Execute the request
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL
    curl_close($ch);

    // Check for errors
    if ($status_code !== 200) {
        ai_debug_log("Grok 3 API Error", [
            'status_code' => $status_code,
            'response' => $response
        ]);
        error_log("Grok 3 API Error: Status code $status_code, Response: $response");
        return false;
    }

    ai_debug_log("Successfully received Grok 3 API response", [
        'status_code' => $status_code
    ]);

    // Parse the response
    $response_data = json_decode($response, true);

    if (isset($response_data['choices'][0]['message']['content'])) {
        return $response_data['choices'][0]['message']['content'];
    }

    ai_debug_log("Unexpected Grok 3 API response format", [
        'response' => $response
    ]);
    return false;
}
?>
