<?php
// Add submenu page under 'Settings' to configure the selected forms
add_action('admin_menu', 'sureforms_override_settings_page');
function sureforms_override_settings_page() {
    add_submenu_page(
        'options-general.php',
        __('SureForms Dynamic Display Settings', 'sureforms-pro'),
        'SureForms Dynamic Display',
        'manage_options',
        'sureforms_dynamic_display_settings',
        'sureforms_dynamic_display_settings_callback'
    );

    register_setting('sureforms_dynamic_display_settings', 'sureforms_selected_forms');
}

// Render the settings page with checkboxes, webhook URLs, and function names for each available form
function sureforms_dynamic_display_settings_callback() {
    $forms = get_sureforms_available_forms();
    $stored_data = get_option('sureforms_selected_forms', []);

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('SureForms Dynamic Display Settings', 'sureforms-pro') . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('sureforms_dynamic_display_settings');

    foreach ($forms as $form) {
        $form_id = $form->ID;
        $form_title = esc_html($form->post_title);

        // Retrieve existing settings for each form
        $form_data = isset($stored_data[$form_id]) ? $stored_data[$form_id] : [];
        $checked = isset($form_data['enabled']) && $form_data['enabled'] ? 'checked' : '';
        $webhook_url = isset($form_data['webhook_url']) ? esc_attr($form_data['webhook_url']) : '';
        $function_name = isset($form_data['function_name']) ? esc_attr($form_data['function_name']) : '';

        echo "<h3>$form_title (ID: $form_id)</h3>";
        
        // Checkbox to enable settings for this form
        echo '<label>';
        echo '<input type="checkbox" name="sureforms_selected_forms[' . $form_id . '][enabled]" value="1" ' . $checked . '>';
        echo ' Enable for this form';
        echo '</label><br>';
        
        // Input field for webhook URL
        echo '<label>Webhook URL: </label>';
        echo '<input type="url" name="sureforms_selected_forms[' . $form_id . '][webhook_url]" value="' . $webhook_url . '" placeholder="Enter webhook URL"><br>';

        // Input field for function name
        echo '<label>Function Name: </label>';
        echo '<input type="text" name="sureforms_selected_forms[' . $form_id . '][function_name]" value="' . $function_name . '" placeholder="Enter function name"><br><br>';
    }

    submit_button();
    echo '</form>';
    echo '</div>';
}

// Helper function to retrieve SureForms forms (assuming they are stored as custom post types)
function get_sureforms_available_forms() {
    return get_posts([
        'post_type' => 'sureforms_form', // Adjust to the actual post type if different
        'posts_per_page' => -1
    ]);
}
