<?php
/*
Plugin Name: SureForms Webhook Handler
Description: Intercepts SureForms submissions, checks settings, and simulates a response.
Version: 1.0
*/

// Include admin settings page
require_once plugin_dir_path(__FILE__) . 'admin/settings.php';

// Enqueue JavaScript and CSS for form interception
add_action('wp_enqueue_scripts', 'sureforms_enqueue_assets');
function sureforms_enqueue_assets() {
    // Enqueue JavaScript
    wp_enqueue_script(
        'sureforms-dynamic-display',
        plugin_dir_url(__FILE__) . 'js/sureforms-webhook-handler.js',
        [],
        null,
        true
    );

    // Enqueue CSS
    wp_enqueue_style(
        'sureforms-style',
        plugin_dir_url(__FILE__) . 'css/style.css'
    );

    // Pass selected forms and session ID to JavaScript
    $selected_forms = get_option('sureforms_selected_forms', []);
    $session_id = 'dummy_session_id'; // Replace with dynamic session ID retrieval if available
    wp_localize_script('sureforms-dynamic-display', 'sureformsData', [
        'selectedForms' => $selected_forms,
        'sessionId' => $session_id,
    ]);
}

// Trigger hook or action upon form submission and wait for response
add_action('rest_api_init', function() {
    add_filter('rest_pre_dispatch', 'handle_form_submission_and_wait', 10, 3);
});

function handle_form_submission_and_wait($result, $server, $request) {
    // Debugging: Log the start of the function
    error_log("Function handle_form_submission_and_wait triggered.");

    if ($request->get_route() === '/sureforms/v1/submit-form' && $request->get_method() === 'POST') {
        $form_data = $request->get_params();

        // Debugging: Log the received form data
        error_log("Form Data: " . print_r($form_data, true));

        $stored_data = get_option('sureforms_selected_forms', []);
        $submitted_form_id = $form_data['form_id'] ?? $form_data['_form_id'] ?? $form_data['form-id'] ?? null;

        // Debugging: Log form ID extraction
        if (!$submitted_form_id) {
            error_log("No valid form_id found in form data.");
            return new WP_REST_Response(['status' => 'error', 'message' => 'No valid form ID found.'], 400);
        }
        error_log("Form ID detected: " . $submitted_form_id);

        // Verify if the form ID is enabled in stored settings
        if (!isset($stored_data[$submitted_form_id]) || !$stored_data[$submitted_form_id]['enabled']) {
            error_log("Form ID $submitted_form_id is not enabled or not found in settings.");
            return new WP_REST_Response(['status' => 'error', 'message' => 'Form ID not enabled in settings.'], 400);
        }

        $form_settings = $stored_data[$submitted_form_id];
        $webhook_url = $form_settings['webhook_url'] ?? '';
        $workflow_id = $form_settings['function_name'] ?? ''; // Treat as FlowMattic Workflow ID

        // Trigger FlowMattic workflow if a workflow ID is provided
        $response_data = null;
        if (!empty($webhook_url)) {
            error_log("Sending data to webhook URL: " . $webhook_url);

            function send_data_to_webhook($url, $data) {
                // Prepare arguments for the request
                $args = [
                    'body' => $data,
                    'timeout' => 15,
                    'blocking' => true,
                ];

                // Send the request and capture response
                $response = wp_remote_post($url, $args);

                // Check for errors
                if (is_wp_error($response)) {
                    error_log('Webhook Error: ' . $response->get_error_message());
                    return ['status' => 'error', 'message' => $response->get_error_message()];
                }

                // Return the response body
                return json_decode(wp_remote_retrieve_body($response), true);
            }

            $response_data = send_data_to_webhook($webhook_url, $form_data);
        } elseif (!empty($workflow_id)) {
            // Trigger FlowMattic workflow using workflow ID
            error_log("Triggering FlowMattic workflow ID: " . $workflow_id);
            do_action('flowmattic_trigger_workflow', $workflow_id, $form_data);
            $response_data = ['data' => ['body' => 'FlowMattic workflow triggered successfully.']]; // Simulated response
        } else {
            error_log("No webhook or FlowMattic workflow ID found for Form ID $submitted_form_id.");
            return new WP_REST_Response(['status' => 'error', 'message' => 'No webhook or workflow found for form.'], 400);
        }

        // Process the response data and display custom output below form
        if ($response_data && isset($response_data['data'])) {
            $output = "<div class='form-response'>";
            $output .= "<p>Thank you for your submission. Here is your response data:</p>";
            $output .= "<div>" . esc_html($response_data['data']['body'] ?? '') . "</div>";
            if (isset($response_data['data']['attachment']) && $response_data['data']['attachment']['status'] === 200) {
                $output .= "<div class='attachments-content'>" . $response_data['data']['attachment']['html'] . "</div>";
            }
            $output .= "</div>";

            echo $output;
            return new WP_REST_Response(['status' => 'success', 'message' => 'Processed with custom output'], 200);
        } else {
            error_log("No response data received from webhook or workflow.");
            return new WP_REST_Response(['status' => 'error', 'message' => 'Failed to process form response.'], 400);
        }
    } else {
        error_log("Invalid request route or method.");
    }

    return new WP_REST_Response(['status' => 'error', 'message' => 'Request not processed.'], 400);
}

function build_flowmattic_response($body = "Thank you for your submission. Here is your response data.", $include_clipboard = false, $attachment_url = null) {
    // Log the received data
    error_log("Received data:");
    error_log("Body: " . print_r($body, true));
    error_log("Include Clipboard: " . ($include_clipboard ? 'true' : 'false'));
    error_log("Attachment URL: " . print_r($attachment_url, true));

    // Initialize the response with the main body content
    $response_content = "<div><p>{$body}</p></div>";

    // Add the clipboard button if requested
    if ($include_clipboard) {
        $clipboard_text = strip_tags($body); // Strip HTML for clipboard text
        $response_content .= "
            <button class='srfm-copy-button' onclick=\"navigator.clipboard.writeText('{$clipboard_text}')\">
                Copy to Clipboard
            </button>
        ";
    }

    // Add the attachment link if URL is provided
    if ($attachment_url) {
        $response_content .= "
            <div class='attachments-content'>
                <a href='{$attachment_url}' class='srfm-download-link' target='_blank'>Download PDF</a>
            </div>
        ";
    }

    // Log the compiled response content
    error_log("Compiled response content:");
    error_log($response_content);

    return $response_content;
}

