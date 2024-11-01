<?php
/*
Plugin Name: SureForms Webhook Handler
Description: Intercepts SureForms submissions, checks settings, and simulates a response.
Version: 1.0
*/

// Include admin settings page
require_once plugin_dir_path(__FILE__) . 'admin/settings.php';


// Enqueue JavaScript for form interception
add_action('wp_enqueue_scripts', 'sureforms_enqueue_scripts');
function sureforms_enqueue_scripts() {
    wp_enqueue_script(
        'sureforms-dynamic-display',
        plugin_dir_url(__FILE__) . 'js/sureforms-webhook-handler.js',
        [],
        null,
        true
    );
    // Get selected forms and simulation options from plugin settings
    $selected_forms = get_option('sureforms_selected_forms', []);
    $simulate_clipboard = true; // Adjust as needed
    $simulate_attachment = true; // Adjust as needed


    // Enqueue the CSS file
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
        'simulateClipboard' => $simulate_clipboard,
        'simulateAttachment' => $simulate_attachment,
    ]);
}
