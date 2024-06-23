<?php
/*
Plugin Name: RAG Chatbot with Chain of Thought
Description: A RAG based chatbot plugin using CoT.
Version: 1.0
Author: Ravi Tiwari
*/

// Enqueue frontend scripts and styles globally
function my_chatbot_enqueue_scripts() {
    wp_enqueue_style('my-chatbot-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('my-chatbot-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);

    // Localize script to pass AJAX URL and nonce
    wp_localize_script('my-chatbot-script', 'myChatbotAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_chatbot_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'my_chatbot_enqueue_scripts');

// Include chatbot HTML content in footer
function include_chatbot_html() {
    include plugin_dir_path(__FILE__) . 'index.html';
}
add_action('wp_footer', 'include_chatbot_html');

// Generate chat response using Flask API
function generate_chat_response( $question ) {
    $api_url = 'http://127.0.0.1:5000/ask'; // Replace with your Flask API endpoint URL

    // Prepare headers and request body
    $headers = array(
        'Content-Type' => 'application/json'
    );

    $body = array(
        'question' => $question
    );

    $args = array(
        'method' => 'POST',
        'headers' => $headers,
        'body' => wp_json_encode($body),
        'timeout' => 120
    );

    // Send request to Flask API
    $response = wp_remote_request($api_url, $args);

    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['result'])) {
        return array(
            'success' => false,
            'message' => 'API request failed. Response: ' . $response_body,
            'result' => ''
        );
    }

    $content = $data['result'];
    return array(
        'success' => true,
        'message' => 'Response Generated',
        'result' => $content
    );
}

// Handle chat bot request
function handle_chat_bot_request( WP_REST_Request $request ) {
    $question = $request->get_param('question');

    if (empty($question)) {
        return array(
            'success' => false,
            'message' => 'Missing question parameter',
            'result' => ''
        );
    }

    $response = generate_chat_response($question);
    return $response;
}

// Register REST API routes
add_action( 'rest_api_init', function () {
    register_rest_route( 'myapi/v1', '/chat-bot/', array(
        'methods' => 'POST',
        'callback' => 'handle_chat_bot_request',
        'permission_callback' => '__return_true'
    ));
});

// Add settings page to admin menu
function my_chatbot_add_admin_menu() {
    add_menu_page(
        'Chatbot Settings',       // Page title
        'Chatbot Settings',       // Menu title
        'manage_options',         // Capability
        'chatbot-settings',       // Menu slug
        'my_chatbot_settings_page', // Function to display the page content
        'dashicons-admin-generic' // Icon
    );
}
add_action('admin_menu', 'my_chatbot_add_admin_menu');

// Display the settings page content
function my_chatbot_settings_page() {
    ?>
    <div class="wrap">
        <h1>Chatbot Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('my_chatbot_settings_group');
            do_settings_sections('chatbot-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function my_chatbot_register_settings() {
    register_setting('my_chatbot_settings_group', 'my_chatbot_settings');

    add_settings_section(
        'my_chatbot_main_section',
        'Main Settings',
        'my_chatbot_section_callback',
        'chatbot-settings'
    );

    add_settings_field(
        'startup_message',
        'Startup Message',
        'my_chatbot_startup_message_render',
        'chatbot-settings',
        'my_chatbot_main_section'
    );
}
add_action('admin_init', 'my_chatbot_register_settings');

function my_chatbot_section_callback() {
    echo 'Configure the chatbot settings below:';
}

function my_chatbot_startup_message_render() {
    $options = get_option('my_chatbot_settings');
    ?>
    <input type="text" name="my_chatbot_settings[startup_message]" value="<?php echo esc_attr($options['startup_message']); ?>" />
    <?php
}
