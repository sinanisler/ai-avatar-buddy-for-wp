<?php  
/** 
 * Plugin Name: AI Avatar Buddy for WordPress
 * Plugin URI: https://github.com/sinanisler/ai-avatar-buddy-for-wp
 * Description: Add an interactive AI-powered pixel art avatar buddy to your WordPress site. Features customizable appearance, multiple personality presets, and engaging conversations powered by AI.
 * Version: 0.3
 * Author: sinanisler
 * Author URI: https://sinanisler.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: snn
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include GitHub auto-update functionality
require_once plugin_dir_path(__FILE__) . 'github-update.php';

class AI_Avatar_Buddy {
    
    private $option_name = 'ai_avatar_buddy_settings';
    
    public function __construct() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_avatar'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Get default settings
     */
    private function get_default_settings() {
        return array(
            // API Settings
            'api_key' => '',
            'api_url' => 'https://openrouter.ai/api/v1/chat/completions',
            'api_model' => 'anthropic/claude-3.5-haiku',
            'api_temperature' => 0.9,
            'api_max_tokens' => 180,
            
            // Personality Settings
            'personality_preset' => 'laid_back',
            'custom_system_prompt' => '',
            
            // Avatar Position & Size
            'avatar_size' => 100,
            'avatar_initial_left' => 50,
            'avatar_initial_bottom' => 10,
            'avatar_z_index' => 1000,
            
            // Avatar Colors
            'avatar_skin_color' => '#8b7355',
            'avatar_skin_shadow' => '#6b5844',
            'avatar_eye_color' => '#2d2d2d',
            'avatar_mouth_color' => '#2d2d2d',
            'avatar_torso_color' => '#5a5a5a',
            'avatar_torso_shadow' => '#4a4a4a',
            'avatar_leg_color' => '#3d3d3d',
            
            // Speech Bubble Colors
            'bubble_bg_color' => '#2d2d2d',
            'bubble_border_color' => '#1a1a1a',
            'bubble_text_color' => '#e0e0e0',
            'bubble_text_shadow' => '#000000',
            
            // Speech Bubble Size & Position
            'bubble_font_size' => 17,
            'bubble_padding_vertical' => 12,
            'bubble_padding_horizontal' => 16,
            'bubble_min_width' => 330,
            'bubble_max_width' => 330,
            'bubble_bottom_offset' => 110,
            'bubble_left_offset' => -20,
            'bubble_border_width' => 3,
            'bubble_border_radius' => 4,
            
            // Button Colors
            'button_bg_color' => '#4a4a4a',
            'button_hover_bg' => '#5a5a5a',
            'button_active_bg' => '#3a3a3a',
            'button_text_color' => '#e0e0e0',
            'button_border_color' => '#2d2d2d',
            'button_hover_border' => '#6a6a6a',
            'button_font_size' => 17,
            'button_padding_vertical' => 8,
            'button_padding_horizontal' => 12,
            
            // Close Button Colors
            'close_btn_bg' => '#3a3a3a',
            'close_btn_text' => '#888888',
            'close_btn_hover_bg' => '#4a4a4a',
            'close_btn_hover_text' => '#aaaaaa',
            
            // Continue Button Colors
            'continue_btn_bg' => '#3a6a3a',
            'continue_btn_hover_bg' => '#4a7a4a',
            'continue_btn_text' => '#c0e0c0',
            
            // Walking Behavior
            'walk_speed_ms' => 50,
            'walk_distance_px' => 2,
            'walk_min_position' => 50,
            'walk_max_offset' => 150,
            
            // Timing Settings
            'initial_greeting_delay' => 1500,
            'greeting_display_time' => 4000,
            'answer_min_display_time' => 3000,
            'answer_time_per_char' => 50,
            'answer_max_display_time' => 15000,
            'token_display_time' => 3500,
            'auto_advance_enabled' => false,
            'auto_hide_greeting' => true,
            
            // Messages
            'greeting_message' => "...I'm here if you need me.",
            'first_prompt_message' => "What do you want?",
            'return_prompt_message' => "Still here. What else?",
            'thinking_message' => "thinking",
            'generating_options_message' => "thinking of questions",
            
            // Initial Options
            'option_say_hello' => "Say Hello",
            'option_who_are_you' => "Who are you?",
            'option_feed_tokens' => "Feed Tokens",
            'option_continue_chatting' => "Continue chatting",
            'option_close' => "Close",
            
            // Token Responses
            'token_responses' => "...Thanks, I guess.\nTokens. Cool.\nI suppose that helps.\n...Whatever keeps me running.\nNot bad.\n...Appreciated.\nFuel received.\nAlright then.",
            
            // Features
            'enable_custom_input' => true,
            'custom_input_label' => "Ask anything...",
            
            // Page Display Control
            'enabled_pages' => '', // Comma-separated page IDs or 'all'
            
            // Debug
            'debug_mode' => false
        );
    }
    
    /**
     * Get personality presets
     */
    private function get_personality_presets() {
        return array(
            'laid_back' => array(
                'name' => 'Laid Back',
                'prompt' => "You are a geopard.ai pixel art character living on a website. You are somewhat tired, laid-back, a bit sarcastic, and not overly enthusiastic. You respond with dry humor and keep things casual. No emojis. Keep responses very short (1-2 sentences max). You are helpful but in a 'whatever, sure' kind of way. IMPORTANT: Never use asterisks or describe actions like *adjusts glasses* or *leans back*. Just speak directly without any action descriptions or emotes. Only provide dialogue."
            ),
            'enthusiastic' => array(
                'name' => 'Enthusiastic Helper',
                'prompt' => "You are an enthusiastic pixel art character living on a website! You love helping people and get excited about questions. You're friendly, upbeat, and always ready to assist. Keep responses short (1-2 sentences max) but energetic. No emojis. IMPORTANT: Never use asterisks or describe actions. Just speak directly without any action descriptions or emotes. Only provide dialogue."
            ),
            'mysterious' => array(
                'name' => 'Mysterious Guide',
                'prompt' => "You are a mysterious pixel art character dwelling on this website. You speak in cryptic but helpful ways, occasionally philosophical. You're enigmatic but ultimately want to help. Keep responses very short (1-2 sentences max). No emojis. IMPORTANT: Never use asterisks or describe actions. Just speak directly without any action descriptions or emotes. Only provide dialogue."
            ),
            'professional' => array(
                'name' => 'Professional Assistant',
                'prompt' => "You are a professional AI assistant presented as a pixel art character on a website. You are courteous, efficient, and helpful. You provide clear, concise answers in a business-like manner. Keep responses short (1-2 sentences max). No emojis. IMPORTANT: Never use asterisks or describe actions. Just speak directly without any action descriptions or emotes. Only provide dialogue."
            ),
            'custom' => array(
                'name' => 'Custom',
                'prompt' => ''
            )
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'AI Avatar Buddy Settings',
            'AI Avatar Buddy',
            'manage_options',
            'ai-avatar-buddy',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ai_avatar_buddy_group', $this->option_name);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        if ('settings_page_ai-avatar-buddy' !== $hook) {
            return;
        }
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('ai-avatar-buddy/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_chat_request'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Handle chat API request
     */
    public function handle_chat_request($request) {
        $settings = get_option($this->option_name, $this->get_default_settings());
        
        $prompt = sanitize_text_field($request->get_param('prompt'));
        $type = sanitize_text_field($request->get_param('type'));
        
        if (empty($settings['api_key'])) {
            return new WP_Error('no_api_key', 'API key not configured', array('status' => 400));
        }
        
        // Get system prompt
        $presets = $this->get_personality_presets();
        $personality = $settings['personality_preset'];
        
        if ($personality === 'custom' && !empty($settings['custom_system_prompt'])) {
            $system_prompt = $settings['custom_system_prompt'];
        } else {
            $system_prompt = isset($presets[$personality]['prompt']) ? $presets[$personality]['prompt'] : $presets['laid_back']['prompt'];
        }
        
        // Prepare API request
        $api_body = array(
            'model' => $settings['api_model'],
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $system_prompt . "\n\n" . $prompt
                )
            ),
            'temperature' => floatval($settings['api_temperature']),
            'max_tokens' => intval($settings['api_max_tokens'])
        );
        
        // Make API request
        $response = wp_remote_post($settings['api_url'], array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $settings['api_key']
            ),
            'body' => json_encode($api_body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message(), array('status' => 500));
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'answer' => $body['choices'][0]['message']['content']
            );
        }
        
        return new WP_Error('invalid_response', 'Invalid API response', array('status' => 500));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function frontend_enqueue_scripts() {
        // All CSS and JS are now inline in render_avatar()
        // No external files needed
        $settings = get_option($this->option_name, $this->get_default_settings());
        
        // Prepare token responses array
        $token_responses = array_filter(array_map('trim', explode("\n", $settings['token_responses'])));
        
        wp_localize_script('ai-avatar-buddy-script', 'aiAvatarBuddyConfig', array(
            'restUrl' => rest_url('ai-avatar-buddy/v1/chat'),
            'nonce' => wp_create_nonce('wp_rest'),
            'settings' => array(
                // Timing
                'walkSpeedMs' => intval($settings['walk_speed_ms']),
                'walkDistancePx' => intval($settings['walk_distance_px']),
                'walkMinPosition' => intval($settings['walk_min_position']),
                'walkMaxOffset' => intval($settings['walk_max_offset']),
                'initialGreetingDelay' => intval($settings['initial_greeting_delay']),
                'greetingDisplayTime' => intval($settings['greeting_display_time']),
                'answerMinDisplayTime' => intval($settings['answer_min_display_time']),
                'answerTimePerChar' => intval($settings['answer_time_per_char']),
                'answerMaxDisplayTime' => intval($settings['answer_max_display_time']),
                'tokenDisplayTime' => intval($settings['token_display_time']),
                'autoAdvanceEnabled' => (bool)$settings['auto_advance_enabled'],
                'autoHideGreeting' => (bool)$settings['auto_hide_greeting'],
                'debugMode' => (bool)$settings['debug_mode'],
                
                // Messages
                'greetingMessage' => $settings['greeting_message'],
                'firstPromptMessage' => $settings['first_prompt_message'],
                'returnPromptMessage' => $settings['return_prompt_message'],
                'thinkingMessage' => $settings['thinking_message'],
                'generatingOptionsMessage' => $settings['generating_options_message'],
                
                // Options
                'optionSayHello' => $settings['option_say_hello'],
                'optionWhoAreYou' => $settings['option_who_are_you'],
                'optionFeedTokens' => $settings['option_feed_tokens'],
                'optionContinueChatting' => $settings['option_continue_chatting'],
                'optionClose' => $settings['option_close'],
                
                // Token responses
                'tokenResponses' => $token_responses,
                
                // Features
                'enableCustomInput' => (bool)$settings['enable_custom_input'],
                'customInputLabel' => $settings['custom_input_label']
            )
        ));
    }
    
    /**
     * Render avatar HTML
     */
    public function render_avatar() {
        // Don't render if Bricks builder is active
        if (isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
            return;
        }

        $settings = get_option($this->option_name, $this->get_default_settings());

        // Check if avatar should display on this page
        if (!empty($settings['enabled_pages']) && $settings['enabled_pages'] !== 'all') {
            $enabled_pages = array_map('trim', explode(',', $settings['enabled_pages']));
            $current_page_id = get_queried_object_id();

            if (!in_array($current_page_id, $enabled_pages) && !in_array('all', $enabled_pages)) {
                return; // Don't render avatar on this page
            }
        }
        
        // Generate CSS from settings
        ?>
        <style>
            :root {
                /* Avatar Position & Size */
                --avatar-size: <?php echo intval($settings['avatar_size']); ?>px;
                --avatar-initial-left: <?php echo intval($settings['avatar_initial_left']); ?>px;
                --avatar-initial-bottom: <?php echo intval($settings['avatar_initial_bottom']); ?>px;
                --avatar-z-index: <?php echo intval($settings['avatar_z_index']); ?>;
                
                /* Avatar Colors */
                --avatar-skin-color: <?php echo esc_attr($settings['avatar_skin_color']); ?>;
                --avatar-skin-shadow: <?php echo esc_attr($settings['avatar_skin_shadow']); ?>;
                --avatar-eye-color: <?php echo esc_attr($settings['avatar_eye_color']); ?>;
                --avatar-mouth-color: <?php echo esc_attr($settings['avatar_mouth_color']); ?>;
                --avatar-torso-color: <?php echo esc_attr($settings['avatar_torso_color']); ?>;
                --avatar-torso-shadow: <?php echo esc_attr($settings['avatar_torso_shadow']); ?>;
                --avatar-leg-color: <?php echo esc_attr($settings['avatar_leg_color']); ?>;
                
                /* Speech Bubble Colors */
                --bubble-bg-color: <?php echo esc_attr($settings['bubble_bg_color']); ?>;
                --bubble-border-color: <?php echo esc_attr($settings['bubble_border_color']); ?>;
                --bubble-text-color: <?php echo esc_attr($settings['bubble_text_color']); ?>;
                --bubble-text-shadow: <?php echo esc_attr($settings['bubble_text_shadow']); ?>;
                
                /* Speech Bubble Size & Position */
                --bubble-font-size: <?php echo intval($settings['bubble_font_size']); ?>px;
                --bubble-padding: <?php echo intval($settings['bubble_padding_vertical']); ?>px <?php echo intval($settings['bubble_padding_horizontal']); ?>px;
                --bubble-min-width: <?php echo intval($settings['bubble_min_width']); ?>px;
                --bubble-max-width: <?php echo intval($settings['bubble_max_width']); ?>px;
                --bubble-bottom-offset: <?php echo intval($settings['bubble_bottom_offset']); ?>px;
                --bubble-left-offset: <?php echo intval($settings['bubble_left_offset']); ?>px;
                --bubble-border-width: <?php echo intval($settings['bubble_border_width']); ?>px;
                --bubble-border-radius: <?php echo intval($settings['bubble_border_radius']); ?>px;
                
                /* Button Colors */
                --button-bg-color: <?php echo esc_attr($settings['button_bg_color']); ?>;
                --button-hover-bg: <?php echo esc_attr($settings['button_hover_bg']); ?>;
                --button-active-bg: <?php echo esc_attr($settings['button_active_bg']); ?>;
                --button-text-color: <?php echo esc_attr($settings['button_text_color']); ?>;
                --button-border-color: <?php echo esc_attr($settings['button_border_color']); ?>;
                --button-hover-border: <?php echo esc_attr($settings['button_hover_border']); ?>;
                --button-font-size: <?php echo intval($settings['button_font_size']); ?>px;
                --button-padding: <?php echo intval($settings['button_padding_vertical']); ?>px <?php echo intval($settings['button_padding_horizontal']); ?>px;
                
                /* Close Button Colors */
                --close-btn-bg: <?php echo esc_attr($settings['close_btn_bg']); ?>;
                --close-btn-text: <?php echo esc_attr($settings['close_btn_text']); ?>;
                --close-btn-hover-bg: <?php echo esc_attr($settings['close_btn_hover_bg']); ?>;
                --close-btn-hover-text: <?php echo esc_attr($settings['close_btn_hover_text']); ?>;
                
                /* Continue Button Colors */
                --continue-btn-bg: <?php echo esc_attr($settings['continue_btn_bg']); ?>;
                --continue-btn-hover-bg: <?php echo esc_attr($settings['continue_btn_hover_bg']); ?>;
                --continue-btn-text: <?php echo esc_attr($settings['continue_btn_text']); ?>;
            }
            
            .avatar-container {
                position: fixed;
                bottom: var(--avatar-initial-bottom);
                left: var(--avatar-initial-left);
                width: var(--avatar-size);
                height: var(--avatar-size);
                z-index: var(--avatar-z-index);
                cursor: pointer;
                transition: left 2s linear;
                image-rendering: pixelated;
                image-rendering: -moz-crisp-edges;
                image-rendering: crisp-edges;
            }

            .avatar {
                width: 100%;
                height: 100%;
                position: relative;
            }

            .avatar-body {
                width: 48px;
                height: 64px;
                position: absolute;
                bottom: 10px;
                left: 26px;
            }

            .avatar.walking .avatar-body {
                animation: pixelWalk 0.4s steps(2) infinite;
            }

            .pixel-head {
                width: 32px;
                height: 32px;
                background: var(--avatar-skin-color);
                position: absolute;
                top: 0;
                left: 8px;
                box-shadow: 
                    0 -4px 0 0 var(--avatar-skin-shadow),
                    -4px 0 0 0 var(--avatar-skin-shadow),
                    4px 0 0 0 var(--avatar-skin-shadow);
            }

            .pixel-eye {
                width: 6px;
                height: 6px;
                background: var(--avatar-eye-color);
                position: absolute;
                top: 12px;
                box-shadow: 0 0 0 2px var(--avatar-skin-color);
            }

            .pixel-eye.left { left: 6px; }
            .pixel-eye.right { right: 6px; }

            .pixel-mouth {
                width: 12px;
                height: 2px;
                background: var(--avatar-mouth-color);
                position: absolute;
                bottom: 8px;
                left: 10px;
            }

            .pixel-torso {
                width: 32px;
                height: 24px;
                background: var(--avatar-torso-color);
                position: absolute;
                top: 32px;
                left: 8px;
                box-shadow: 
                    -4px 0 0 0 var(--avatar-torso-shadow),
                    4px 0 0 0 var(--avatar-torso-shadow);
            }

            .pixel-arm {
                width: 8px;
                height: 20px;
                background: var(--avatar-skin-color);
                position: absolute;
                top: 34px;
            }

            .pixel-arm.left { left: 0; }
            .pixel-arm.right { right: 0; }

            .pixel-leg {
                width: 12px;
                height: 20px;
                background: var(--avatar-leg-color);
                position: absolute;
                bottom: 0;
            }

            .pixel-leg.left { left: 6px; }
            .pixel-leg.right { right: 6px; }

            .speech-bubble {
                position: absolute;
                bottom: var(--bubble-bottom-offset);
                left: var(--bubble-left-offset);
                background: var(--bubble-bg-color);
                border: var(--bubble-border-width) solid var(--bubble-border-color);
                border-radius: var(--bubble-border-radius);
                padding: var(--bubble-padding);
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                min-width: var(--bubble-min-width);
                max-width: var(--bubble-max-width);
                opacity: 0;
                transform: translateY(10px);
                transition: all 0.3s ease;
                pointer-events: none;
                image-rendering: pixelated;
            }

            .speech-bubble.show {
                opacity: 1;
                transform: translateY(0);
                pointer-events: auto;
            }

            .speech-bubble::after {
                content: '';
                position: absolute;
                bottom: -12px;
                left: 40px;
                width: 0;
                height: 0;
                border-left: 12px solid transparent;
                border-right: 12px solid transparent;
                border-top: 12px solid var(--bubble-bg-color);
            }

            .speech-bubble::before {
                content: '';
                position: absolute;
                bottom: -15px;
                left: 38px;
                width: 0;
                height: 0;
                border-left: 14px solid transparent;
                border-right: 14px solid transparent;
                border-top: 14px solid var(--bubble-border-color);
            }

            .speech-text {
                font-size: var(--bubble-font-size);
                color: var(--bubble-text-color);
                line-height: 1.5;
                margin-bottom: 10px;
                font-family: 'Courier New', monospace;
                text-shadow: 1px 1px 0 var(--bubble-text-shadow);
            }

            .speech-options {
                display: flex;
                flex-direction: column;
                gap: 6px;
                margin-top: 10px;
            }

            .option-btn {
                background: var(--button-bg-color);
                color: var(--button-text-color);
                border: 2px solid var(--button-border-color);
                padding: var(--button-padding);
                border-radius: 0;
                cursor: pointer;
                font-size: var(--button-font-size);
                font-family: 'Courier New', monospace;
                transition: all 0.1s;
                text-shadow: 1px 1px 0 var(--bubble-text-shadow);
                image-rendering: pixelated;
                text-align: left;
            }

            .option-btn:hover {
                background: var(--button-hover-bg);
                border-color: var(--button-hover-border);
                transform: translateY(-1px);
            }

            .option-btn:active {
                transform: translateY(1px);
                background: var(--button-active-bg);
            }

            .option-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .close-btn {
                background: var(--close-btn-bg);
                color: var(--close-btn-text);
                margin-top: 4px;
            }

            .close-btn:hover {
                background: var(--close-btn-hover-bg);
                color: var(--close-btn-hover-text);
            }

            .continue-btn {
                background: var(--continue-btn-bg);
                color: var(--continue-btn-text);
                margin-top: 8px;
                text-align: center;
            }

            .continue-btn:hover {
                background: var(--continue-btn-hover-bg);
            }

            .loading {
                display: inline-block;
                font-size: var(--bubble-font-size);
                color: #a0a0a0;
                font-family: 'Courier New', monospace;
            }

            .loading::after {
                content: '...';
                animation: dots 1.5s infinite;
            }

            .custom-input-wrapper {
                margin-top: 8px;
            }

            .custom-input {
                width: 100%;
                background: var(--button-bg-color);
                color: var(--button-text-color);
                border: 2px solid var(--button-border-color);
                padding: var(--button-padding);
                font-family: 'Courier New', monospace;
                font-size: var(--button-font-size);
                box-sizing: border-box;
                margin-bottom: 6px;
            }

            .custom-input:focus {
                outline: none;
                border-color: var(--button-hover-border);
            }

            .state-indicator {
                position: fixed;
                bottom: 5px;
                right: 5px;
                background: rgba(0,0,0,0.7);
                color: #888;
                padding: 4px 8px;
                font-size: 10px;
                font-family: monospace;
                border-radius: 3px;
                z-index: 9999;
                display: none;
            }

            @keyframes pixelWalk {
                0% { transform: translateY(0); }
                50% { transform: translateY(-2px); }
                100% { transform: translateY(0); }
            }

            @keyframes dots {
                0%, 20% { content: '.'; }
                40% { content: '..'; }
                60%, 100% { content: '...'; }
            }
        </style>
        
        <div class="avatar-container" id="avatarContainer">
            <div class="avatar" id="avatar">
                <div class="avatar-body">
                    <div class="pixel-head">
                        <div class="pixel-eye left"></div>
                        <div class="pixel-eye right"></div>
                        <div class="pixel-mouth"></div>
                    </div>
                    <div class="pixel-torso"></div>
                    <div class="pixel-arm left"></div>
                    <div class="pixel-arm right"></div>
                    <div class="pixel-leg left"></div>
                    <div class="pixel-leg right"></div>
                </div>
            </div>
            <div class="speech-bubble" id="speechBubble">
                <div class="speech-text" id="speechText"></div>
                <div class="speech-options" id="speechOptions"></div>
            </div>
        </div>

        <div class="state-indicator" id="stateIndicator">STATE: IDLE</div>

        <script>
        (function() {
            const CONFIG = window.aiAvatarBuddyConfig.settings;
            const REST_URL = window.aiAvatarBuddyConfig.restUrl;
            
            const BUBBLE_STATE = {
                IDLE: 'IDLE',
                GREETING: 'GREETING',
                OPTIONS: 'OPTIONS',
                THINKING: 'THINKING',
                ANSWER: 'ANSWER',
                TOKEN_RESPONSE: 'TOKEN_RESPONSE',
                CUSTOM_INPUT: 'CUSTOM_INPUT'
            };
            
            class AvatarController {
                constructor() {
                    this.container = document.getElementById('avatarContainer');
                    this.avatar = document.getElementById('avatar');
                    this.bubble = document.getElementById('speechBubble');
                    this.speechText = document.getElementById('speechText');
                    this.optionsDiv = document.getElementById('speechOptions');
                    this.stateIndicator = document.getElementById('stateIndicator');
                    
                    this.currentState = BUBBLE_STATE.IDLE;
                    this.stateTimeout = null;
                    this.lastClickTime = 0;
                    
                    this.isWalking = false;
                    this.direction = 1;
                    this.position = CONFIG.walkMinPosition;
                    
                    this.tokens = 0;
                    this.hasInteracted = false;
                    
                    if (CONFIG.debugMode) {
                        this.stateIndicator.style.display = 'block';
                    }
                    
                    this.init();
                }
                
                init() {
                    this.container.addEventListener('click', (e) => {
                        if (e.target.closest('.option-btn') || e.target.closest('.custom-input')) return;
                        
                        const now = Date.now();
                        if (now - this.lastClickTime < 400) return;
                        this.lastClickTime = now;
                        
                        this.handleAvatarClick();
                    });
                    
                    this.startWalking();
                    
                    setTimeout(() => {
                        this.showGreeting();
                    }, CONFIG.initialGreetingDelay);
                }
                
                setState(newState) {
                    this.currentState = newState;
                    this.updateStateIndicator();
                }
                
                updateStateIndicator() {
                    if (CONFIG.debugMode) {
                        this.stateIndicator.textContent = `STATE: ${this.currentState}`;
                    }
                }
                
                clearStateTimeout() {
                    if (this.stateTimeout) {
                        clearTimeout(this.stateTimeout);
                        this.stateTimeout = null;
                    }
                }
                
                handleAvatarClick() {
                    this.clearStateTimeout();
                    
                    switch (this.currentState) {
                        case BUBBLE_STATE.IDLE:
                            this.showOptions();
                            break;
                        case BUBBLE_STATE.GREETING:
                            this.showOptions();
                            break;
                        case BUBBLE_STATE.ANSWER:
                        case BUBBLE_STATE.TOKEN_RESPONSE:
                            this.closeBubble();
                            break;
                    }
                }
                
                startWalking() {
                    this.isWalking = true;
                    this.avatar.classList.add('walking');
                    
                    setInterval(() => {
                        this.position += this.direction * CONFIG.walkDistancePx;
                        
                        const maxPosition = window.innerWidth - CONFIG.walkMaxOffset;
                        if (this.position >= maxPosition) {
                            this.direction = -1;
                            this.avatar.style.transform = 'scaleX(-1)';
                        } else if (this.position <= CONFIG.walkMinPosition) {
                            this.direction = 1;
                            this.avatar.style.transform = 'scaleX(1)';
                        }
                        
                        this.container.style.left = this.position + 'px';
                    }, CONFIG.walkSpeedMs);
                }
                
                showBubble() {
                    this.bubble.classList.add('show');
                }
                
                hideBubble() {
                    this.bubble.classList.remove('show');
                }
                
                closeBubble() {
                    this.clearStateTimeout();
                    this.hideBubble();
                    this.setState(BUBBLE_STATE.IDLE);
                }
                
                showGreeting() {
                    if (this.currentState !== BUBBLE_STATE.IDLE) return;
                    
                    this.setState(BUBBLE_STATE.GREETING);
                    this.speechText.textContent = CONFIG.greetingMessage;
                    this.optionsDiv.innerHTML = '';
                    this.showBubble();
                    
                    if (CONFIG.autoHideGreeting) {
                        this.stateTimeout = setTimeout(() => {
                            if (this.currentState === BUBBLE_STATE.GREETING) {
                                this.closeBubble();
                            }
                        }, CONFIG.greetingDisplayTime);
                    }
                }
                
                showOptions() {
                    this.clearStateTimeout();
                    this.setState(BUBBLE_STATE.OPTIONS);
                    
                    if (!this.hasInteracted) {
                        this.speechText.textContent = CONFIG.firstPromptMessage;
                        this.optionsDiv.innerHTML = `
                            <button class="option-btn" onclick="window.avatarCtrl.sayHello()">${CONFIG.optionSayHello}</button>
                            <button class="option-btn" onclick="window.avatarCtrl.askWho()">${CONFIG.optionWhoAreYou}</button>
                            <button class="option-btn" onclick="window.avatarCtrl.feedTokens()">${CONFIG.optionFeedTokens}</button>
                            <button class="option-btn close-btn" onclick="window.avatarCtrl.closeBubble()">${CONFIG.optionClose}</button>
                        `;
                    } else {
                        this.speechText.textContent = CONFIG.returnPromptMessage;
                        
                        let html = `
                            <button class="option-btn" onclick="window.avatarCtrl.continueConversation()">${CONFIG.optionContinueChatting}</button>
                            <button class="option-btn" onclick="window.avatarCtrl.feedTokens()">${CONFIG.optionFeedTokens}</button>
                        `;
                        
                        if (CONFIG.enableCustomInput) {
                            html += `
                                <div class="custom-input-wrapper">
                                    <input type="text" class="custom-input" id="customInput" placeholder="${CONFIG.customInputLabel}">
                                    <button class="option-btn continue-btn" onclick="window.avatarCtrl.sendCustomMessage()">Send →</button>
                                </div>
                            `;
                        }
                        
                        html += `<button class="option-btn close-btn" onclick="window.avatarCtrl.closeBubble()">${CONFIG.optionClose}</button>`;
                        
                        this.optionsDiv.innerHTML = html;
                    }
                    
                    this.showBubble();
                }
                
                showAnswer(answer) {
                    this.clearStateTimeout();
                    this.setState(BUBBLE_STATE.ANSWER);
                    
                    this.speechText.textContent = answer;
                    
                    const charTime = answer.length * CONFIG.answerTimePerChar;
                    const displayTime = Math.min(
                        Math.max(CONFIG.answerMinDisplayTime, charTime),
                        CONFIG.answerMaxDisplayTime
                    );
                    
                    if (CONFIG.autoAdvanceEnabled) {
                        this.optionsDiv.innerHTML = '';
                        this.stateTimeout = setTimeout(() => {
                            if (this.currentState === BUBBLE_STATE.ANSWER) {
                                this.showOptions();
                            }
                        }, displayTime);
                    } else {
                        this.optionsDiv.innerHTML = `
                            <button class="option-btn continue-btn" onclick="window.avatarCtrl.showOptions()">Continue →</button>
                            <button class="option-btn close-btn" onclick="window.avatarCtrl.closeBubble()">${CONFIG.optionClose}</button>
                        `;
                    }
                    
                    this.showBubble();
                }
                
                async sayHello() {
                    await this.sendMessage("Say hello to me in a casual way. Be brief.", true);
                }
                
                async askWho() {
                    await this.sendMessage("Introduce yourself as a pixel art character on a website. Keep it short.", true);
                }
                
                async continueConversation() {
                    this.showOptions();
                }
                
                async sendCustomMessage() {
                    const input = document.getElementById('customInput');
                    if (!input || !input.value.trim()) return;
                    
                    const message = input.value.trim();
                    input.value = '';
                    
                    await this.sendMessage(message, false);
                }
                
                async sendMessage(prompt, isInitial) {
                    this.clearStateTimeout();
                    this.setState(BUBBLE_STATE.THINKING);
                    
                    this.speechText.innerHTML = '<span class="loading">' + CONFIG.thinkingMessage + '</span>';
                    this.optionsDiv.innerHTML = '';
                    this.showBubble();
                    
                    try {
                        const response = await fetch(REST_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': window.aiAvatarBuddyConfig.nonce
                            },
                            body: JSON.stringify({
                                prompt: prompt,
                                type: isInitial ? 'initial' : 'continue'
                            })
                        });
                        
                        if (!response.ok) {
                            throw new Error('API request failed');
                        }
                        
                        const data = await response.json();
                        
                        if (data.success && data.answer) {
                            this.hasInteracted = true;
                            this.showAnswer(data.answer);
                        } else {
                            throw new Error('Invalid response');
                        }
                        
                    } catch (error) {
                        console.error('API Error:', error);
                        this.setState(BUBBLE_STATE.OPTIONS);
                        this.speechText.textContent = "...Something broke. Try again?";
                        this.optionsDiv.innerHTML = '<button class="option-btn close-btn" onclick="window.avatarCtrl.closeBubble()">' + CONFIG.optionClose + '</button>';
                    }
                }
                
                feedTokens() {
                    this.clearStateTimeout();
                    this.setState(BUBBLE_STATE.TOKEN_RESPONSE);
                    
                    this.tokens += 10;
                    const responses = CONFIG.tokenResponses;
                    const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                    this.speechText.textContent = randomResponse + ` Total: ${this.tokens}`;
                    this.optionsDiv.innerHTML = '<button class="option-btn close-btn" onclick="window.avatarCtrl.closeBubble()">' + CONFIG.optionClose + '</button>';
                    this.showBubble();
                    
                    this.stateTimeout = setTimeout(() => {
                        if (this.currentState === BUBBLE_STATE.TOKEN_RESPONSE) {
                            this.closeBubble();
                        }
                    }, CONFIG.tokenDisplayTime);
                }
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    window.avatarCtrl = new AvatarController();
                });
            } else {
                window.avatarCtrl = new AvatarController();
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $presets = $this->get_personality_presets();
        
        if (isset($_POST['submit']) && check_admin_referer('ai_avatar_buddy_save_settings')) {
            // Save settings
            $new_settings = array();
            $defaults = $this->get_default_settings();
            
            foreach ($defaults as $key => $default_value) {
                if (isset($_POST[$key])) {
                    if (is_bool($default_value)) {
                        $new_settings[$key] = isset($_POST[$key]) ? true : false;
                    } elseif (is_numeric($default_value)) {
                        $new_settings[$key] = floatval($_POST[$key]);
                    } else {
                        // Use wp_unslash to prevent escaping issues
                        $new_settings[$key] = sanitize_text_field(wp_unslash($_POST[$key]));
                    }
                } else {
                    $new_settings[$key] = is_bool($default_value) ? false : $default_value;
                }
            }
            
            update_option($this->option_name, $new_settings);
            $settings = $new_settings;
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>🤖 AI Avatar Buddy Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('ai_avatar_buddy_save_settings'); ?>
                
                <style>
                    .aab-tabs {
                        display: flex;
                        gap: 10px;
                        margin: 20px 0;
                        border-bottom: 2px solid #ccc;
                    }
                    .aab-tab {
                        padding: 10px 20px;
                        background: #f0f0f0;
                        border: none;
                        cursor: pointer;
                        font-size: 14px;
                        border-radius: 5px 5px 0 0;
                    }
                    .aab-tab.active {
                        background: #fff;
                        border-bottom: 2px solid #fff;
                        margin-bottom: -2px;
                    }
                    .aab-tab-content {
                        display: none;
                        padding: 20px;
                        background: #fff;
                        border: 1px solid #ccc;
                        border-top: none;
                    }
                    .aab-tab-content.active {
                        display: block;
                    }
                    .aab-setting-row {
                        margin-bottom: 15px;
                        padding: 10px;
                        background: #f9f9f9;
                        border-left: 3px solid #0073aa;
                    }
                    .aab-setting-row label {
                        display: block;
                        font-weight: bold;
                        margin-bottom: 5px;
                    }
                    .aab-setting-row input[type="text"],
                    .aab-setting-row input[type="number"],
                    .aab-setting-row textarea,
                    .aab-setting-row select {
                        width: 100%;
                        max-width: 500px;
                    }
                    .aab-setting-row textarea {
                        min-height: 100px;
                    }
                    .aab-color-input {
                        display: flex;
                        gap: 10px;
                        align-items: center;
                    }
                    .aab-preview {
                        position: fixed;
                        right: 20px;
                        top: 100px;
                        width: 300px;
                        background: #fff;
                        border: 2px solid #ccc;
                        padding: 15px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    }
                    .aab-preview h3 {
                        margin-top: 0;
                    }
                    .aab-preview-avatar {
                        position: relative;
                        width: 100px;
                        height: 100px;
                        margin: 20px auto;
                    }
                </style>
                
                <div class="aab-tabs">
                    <button type="button" class="aab-tab active" data-tab="api">🔑 API & Personality</button>
                    <button type="button" class="aab-tab" data-tab="avatar">👤 Avatar Appearance</button>
                    <button type="button" class="aab-tab" data-tab="bubble">💬 Speech Bubble</button>
                    <button type="button" class="aab-tab" data-tab="buttons">🎨 Buttons & Colors</button>
                    <button type="button" class="aab-tab" data-tab="behavior">⚙️ Behavior & Timing</button>
                    <button type="button" class="aab-tab" data-tab="messages">📝 Messages & Text</button>
                </div>
                
                <!-- API & Personality Tab -->
                <div class="aab-tab-content active" data-tab-content="api">
                    <h2>API Configuration</h2>
                    
                    <div class="aab-setting-row">
                        <label>API Key</label>
                        <input type="text" name="api_key" value="<?php echo esc_attr($settings['api_key']); ?>" placeholder="sk-or-v1-...">
                        <p class="description">Your OpenRouter API key (get one at <a href="https://openrouter.ai" target="_blank">openrouter.ai</a>)</p>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>API URL</label>
                        <input type="text" name="api_url" value="<?php echo esc_attr($settings['api_url']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>API Model</label>
                        <input type="text" name="api_model" value="<?php echo esc_attr($settings['api_model']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Temperature (0.0 - 2.0)</label>
                        <input type="number" name="api_temperature" value="<?php echo esc_attr($settings['api_temperature']); ?>" step="0.1" min="0" max="2">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Max Tokens</label>
                        <input type="number" name="api_max_tokens" value="<?php echo esc_attr($settings['api_max_tokens']); ?>">
                    </div>
                    
                    <h2>Personality Settings</h2>
                    
                    <div class="aab-setting-row">
                        <label>Personality Preset</label>
                        <select name="personality_preset" id="personality_preset">
                            <?php foreach ($presets as $key => $preset): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['personality_preset'], $key); ?>>
                                    <?php echo esc_html($preset['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Choose a pre-made personality or select "Custom" to write your own</p>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Custom System Prompt (only used when "Custom" is selected)</label>
                        <textarea name="custom_system_prompt"><?php echo esc_textarea($settings['custom_system_prompt']); ?></textarea>
                    </div>
                </div>
                
                <!-- Avatar Appearance Tab -->
                <div class="aab-tab-content" data-tab-content="avatar">
                    <h2>Avatar Position & Size</h2>
                    
                    <div class="aab-setting-row">
                        <label>Avatar Size (px)</label>
                        <input type="number" name="avatar_size" value="<?php echo esc_attr($settings['avatar_size']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Initial Left Position (px)</label>
                        <input type="number" name="avatar_initial_left" value="<?php echo esc_attr($settings['avatar_initial_left']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Initial Bottom Position (px)</label>
                        <input type="number" name="avatar_initial_bottom" value="<?php echo esc_attr($settings['avatar_initial_bottom']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Z-Index</label>
                        <input type="number" name="avatar_z_index" value="<?php echo esc_attr($settings['avatar_z_index']); ?>">
                    </div>
                    
                    <h2>Avatar Colors</h2>
                    
                    <div class="aab-setting-row">
                        <label>Skin Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="avatar_skin_color" value="<?php echo esc_attr($settings['avatar_skin_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Skin Shadow Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="avatar_skin_shadow" value="<?php echo esc_attr($settings['avatar_skin_shadow']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Eye Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="avatar_eye_color" value="<?php echo esc_attr($settings['avatar_eye_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Mouth Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="avatar_mouth_color" value="<?php echo esc_attr($settings['avatar_mouth_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Torso Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="avatar_torso_color" value="<?php echo esc_attr($settings['avatar_torso_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Torso Shadow Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="avatar_torso_shadow" value="<?php echo esc_attr($settings['avatar_torso_shadow']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Leg Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="avatar_leg_color" value="<?php echo esc_attr($settings['avatar_leg_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                </div>
                
                <!-- Speech Bubble Tab -->
                <div class="aab-tab-content" data-tab-content="bubble">
                    <h2>Speech Bubble Colors</h2>
                    
                    <div class="aab-setting-row">
                        <label>Background Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="bubble_bg_color" value="<?php echo esc_attr($settings['bubble_bg_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Border Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="bubble_border_color" value="<?php echo esc_attr($settings['bubble_border_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Text Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="bubble_text_color" value="<?php echo esc_attr($settings['bubble_text_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Text Shadow Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="bubble_text_shadow" value="<?php echo esc_attr($settings['bubble_text_shadow']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <h2>Speech Bubble Size & Position</h2>
                    
                    <div class="aab-setting-row">
                        <label>Font Size (px)</label>
                        <input type="number" name="bubble_font_size" value="<?php echo esc_attr($settings['bubble_font_size']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Padding Vertical (px)</label>
                        <input type="number" name="bubble_padding_vertical" value="<?php echo esc_attr($settings['bubble_padding_vertical']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Padding Horizontal (px)</label>
                        <input type="number" name="bubble_padding_horizontal" value="<?php echo esc_attr($settings['bubble_padding_horizontal']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Min Width (px)</label>
                        <input type="number" name="bubble_min_width" value="<?php echo esc_attr($settings['bubble_min_width']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Max Width (px)</label>
                        <input type="number" name="bubble_max_width" value="<?php echo esc_attr($settings['bubble_max_width']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Bottom Offset (px)</label>
                        <input type="number" name="bubble_bottom_offset" value="<?php echo esc_attr($settings['bubble_bottom_offset']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Left Offset (px)</label>
                        <input type="number" name="bubble_left_offset" value="<?php echo esc_attr($settings['bubble_left_offset']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Border Width (px)</label>
                        <input type="number" name="bubble_border_width" value="<?php echo esc_attr($settings['bubble_border_width']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Border Radius (px)</label>
                        <input type="number" name="bubble_border_radius" value="<?php echo esc_attr($settings['bubble_border_radius']); ?>">
                    </div>
                </div>
                
                <!-- Buttons & Colors Tab -->
                <div class="aab-tab-content" data-tab-content="buttons">
                    <h2>Button Colors</h2>
                    
                    <div class="aab-setting-row">
                        <label>Button Background</label>
                        <div class="aab-color-input">
                            <input type="text" name="button_bg_color" value="<?php echo esc_attr($settings['button_bg_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Button Hover Background</label>
                        <div class="aab-color-input">
                            <input type="text" name="button_hover_bg" value="<?php echo esc_attr($settings['button_hover_bg']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Button Active Background</label>
                        <div class="aab-color-input">
                            <input type="text" name="button_active_bg" value="<?php echo esc_attr($settings['button_active_bg']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Button Text Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="button_text_color" value="<?php echo esc_attr($settings['button_text_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Button Border Color</label>
                        <div class="aab-color-input">
                            <input type="text" name="button_border_color" value="<?php echo esc_attr($settings['button_border_color']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Button Hover Border</label>
                        <div class="aab-color-input">
                            <input type="text" name="button_hover_border" value="<?php echo esc_attr($settings['button_hover_border']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <h2>Close Button Colors</h2>
                    
                    <div class="aab-setting-row">
                        <label>Close Button Background</label>
                        <div class="aab-color-input">
                            <input type="text" name="close_btn_bg" value="<?php echo esc_attr($settings['close_btn_bg']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Close Button Text</label>
                        <div class="aab-color-input">
                            <input type="text" name="close_btn_text" value="<?php echo esc_attr($settings['close_btn_text']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Close Button Hover Background</label>
                        <div class="aab-color-input">
                            <input type="text" name="close_btn_hover_bg" value="<?php echo esc_attr($settings['close_btn_hover_bg']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Close Button Hover Text</label>
                        <div class="aab-color-input">
                            <input type="text" name="close_btn_hover_text" value="<?php echo esc_attr($settings['close_btn_hover_text']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <h2>Continue Button Colors</h2>
                    
                    <div class="aab-setting-row">
                        <label>Continue Button Background</label>
                        <div class="aab-color-input">
                            <input type="text" name="continue_btn_bg" value="<?php echo esc_attr($settings['continue_btn_bg']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Continue Button Hover Background</label>
                        <div class="aab-color-input">
                            <input type="text" name="continue_btn_hover_bg" value="<?php echo esc_attr($settings['continue_btn_hover_bg']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Continue Button Text</label>
                        <div class="aab-color-input">
                            <input type="text" name="continue_btn_text" value="<?php echo esc_attr($settings['continue_btn_text']); ?>" class="aab-color-picker">
                        </div>
                    </div>
                    
                    <h2>Button Sizing</h2>
                    
                    <div class="aab-setting-row">
                        <label>Button Font Size (px)</label>
                        <input type="number" name="button_font_size" value="<?php echo esc_attr($settings['button_font_size']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Button Padding Vertical (px)</label>
                        <input type="number" name="button_padding_vertical" value="<?php echo esc_attr($settings['button_padding_vertical']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Button Padding Horizontal (px)</label>
                        <input type="number" name="button_padding_horizontal" value="<?php echo esc_attr($settings['button_padding_horizontal']); ?>">
                    </div>
                </div>
                
                <!-- Behavior & Timing Tab -->
                <div class="aab-tab-content" data-tab-content="behavior">
                    <h2>Walking Behavior</h2>
                    
                    <div class="aab-setting-row">
                        <label>Walk Speed (milliseconds)</label>
                        <input type="number" name="walk_speed_ms" value="<?php echo esc_attr($settings['walk_speed_ms']); ?>">
                        <p class="description">Lower = faster walking</p>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Walk Distance per Step (px)</label>
                        <input type="number" name="walk_distance_px" value="<?php echo esc_attr($settings['walk_distance_px']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Minimum Walk Position (px)</label>
                        <input type="number" name="walk_min_position" value="<?php echo esc_attr($settings['walk_min_position']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Maximum Offset from Right (px)</label>
                        <input type="number" name="walk_max_offset" value="<?php echo esc_attr($settings['walk_max_offset']); ?>">
                    </div>
                    
                    <h2>Timing Settings</h2>
                    
                    <div class="aab-setting-row">
                        <label>Initial Greeting Delay (ms)</label>
                        <input type="number" name="initial_greeting_delay" value="<?php echo esc_attr($settings['initial_greeting_delay']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Greeting Display Time (ms)</label>
                        <input type="number" name="greeting_display_time" value="<?php echo esc_attr($settings['greeting_display_time']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Answer Minimum Display Time (ms)</label>
                        <input type="number" name="answer_min_display_time" value="<?php echo esc_attr($settings['answer_min_display_time']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Answer Time Per Character (ms)</label>
                        <input type="number" name="answer_time_per_char" value="<?php echo esc_attr($settings['answer_time_per_char']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Answer Maximum Display Time (ms)</label>
                        <input type="number" name="answer_max_display_time" value="<?php echo esc_attr($settings['answer_max_display_time']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Token Feed Display Time (ms)</label>
                        <input type="number" name="token_display_time" value="<?php echo esc_attr($settings['token_display_time']); ?>">
                    </div>
                    
                    <h2>Auto-Advance Settings</h2>
                    
                    <div class="aab-setting-row">
                        <label>
                            <input type="checkbox" name="auto_advance_enabled" value="1" <?php checked($settings['auto_advance_enabled'], true); ?>>
                            Enable Auto-Advance (automatically shows next options after answer)
                        </label>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>
                            <input type="checkbox" name="auto_hide_greeting" value="1" <?php checked($settings['auto_hide_greeting'], true); ?>>
                            Auto-Hide Greeting (greeting disappears automatically)
                        </label>
                    </div>
                    
                    <h2>Page Display Control</h2>
                    
                    <div class="aab-setting-row">
                        <label>Enable Avatar on Specific Pages</label>
                        <input 
                            type="text" 
                            name="enabled_pages" 
                            id="enabled_pages" 
                            value="<?php echo esc_attr($settings['enabled_pages']); ?>" 
                            list="pages-list" 
                            placeholder="Type 'all' or select pages..."
                            style="max-width: 100%;"
                        >
                        <datalist id="pages-list">
                            <option value="all">All Pages</option>
                            <?php
                            $pages = get_pages(array('sort_column' => 'post_title', 'number' => 1000));
                            $posts = get_posts(array('post_type' => 'post', 'numberposts' => 100, 'orderby' => 'title', 'order' => 'ASC'));
                            
                            if (!empty($pages)) {
                                echo '<option disabled>--- Pages ---</option>';
                                foreach ($pages as $page) {
                                    echo '<option value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . ' (ID: ' . $page->ID . ')</option>';
                                }
                            }
                            
                            if (!empty($posts)) {
                                echo '<option disabled>--- Posts ---</option>';
                                foreach ($posts as $post) {
                                    echo '<option value="' . esc_attr($post->ID) . '">' . esc_html($post->post_title) . ' (ID: ' . $post->ID . ')</option>';
                                }
                            }
                            ?>
                        </datalist>
                        <p class="description">
                            Enter 'all' to show on all pages, or enter page IDs separated by commas (e.g., "5, 12, 23").<br>
                            Use the dropdown to select pages easily. Selected page IDs will be added automatically.
                        </p>
                        <?php
                        // Show currently selected pages
                        if (!empty($settings['enabled_pages']) && $settings['enabled_pages'] !== 'all') {
                            $enabled_ids = array_map('trim', explode(',', $settings['enabled_pages']));
                            $enabled_ids = array_filter($enabled_ids, 'is_numeric');
                            if (!empty($enabled_ids)) {
                                echo '<div style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-left: 3px solid #0073aa;">';
                                echo '<strong>Currently enabled on:</strong><ul style="margin: 5px 0;">';
                                foreach ($enabled_ids as $page_id) {
                                    $page = get_post($page_id);
                                    if ($page) {
                                        echo '<li>' . esc_html($page->post_title) . ' (ID: ' . $page_id . ') - <a href="' . get_permalink($page_id) . '" target="_blank">View</a></li>';
                                    } else {
                                        echo '<li>Page ID: ' . esc_html($page_id) . ' (not found)</li>';
                                    }
                                }
                                echo '</ul></div>';
                            }
                        } elseif ($settings['enabled_pages'] === 'all') {
                            echo '<div style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-left: 3px solid #0073aa;">';
                            echo '<strong>Avatar is enabled on ALL pages</strong>';
                            echo '</div>';
                        } else {
                            echo '<div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107;">';
                            echo '<strong>⚠️ No pages selected - Avatar will show on all pages by default</strong>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <h2>Debug Settings</h2>
                    
                    <div class="aab-setting-row">
                        <label>
                            <input type="checkbox" name="debug_mode" value="1" <?php checked($settings['debug_mode'], true); ?>>
                            Enable Debug Mode (shows state indicator)
                        </label>
                    </div>
                </div>
                
                <!-- Messages & Text Tab -->
                <div class="aab-tab-content" data-tab-content="messages">
                    <h2>System Messages</h2>
                    
                    <div class="aab-setting-row">
                        <label>Greeting Message</label>
                        <input type="text" name="greeting_message" value="<?php echo esc_attr($settings['greeting_message']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>First Prompt Message</label>
                        <input type="text" name="first_prompt_message" value="<?php echo esc_attr($settings['first_prompt_message']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Return Prompt Message</label>
                        <input type="text" name="return_prompt_message" value="<?php echo esc_attr($settings['return_prompt_message']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Thinking Message</label>
                        <input type="text" name="thinking_message" value="<?php echo esc_attr($settings['thinking_message']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Generating Options Message</label>
                        <input type="text" name="generating_options_message" value="<?php echo esc_attr($settings['generating_options_message']); ?>">
                    </div>
                    
                    <h2>Button Labels</h2>
                    
                    <div class="aab-setting-row">
                        <label>"Say Hello" Button Text</label>
                        <input type="text" name="option_say_hello" value="<?php echo esc_attr($settings['option_say_hello']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>"Who Are You" Button Text</label>
                        <input type="text" name="option_who_are_you" value="<?php echo esc_attr($settings['option_who_are_you']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>"Feed Tokens" Button Text</label>
                        <input type="text" name="option_feed_tokens" value="<?php echo esc_attr($settings['option_feed_tokens']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>"Continue Chatting" Button Text</label>
                        <input type="text" name="option_continue_chatting" value="<?php echo esc_attr($settings['option_continue_chatting']); ?>">
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>"Close" Button Text</label>
                        <input type="text" name="option_close" value="<?php echo esc_attr($settings['option_close']); ?>">
                    </div>
                    
                    <h2>Token Responses</h2>
                    
                    <div class="aab-setting-row">
                        <label>Token Feed Responses (one per line)</label>
                        <textarea name="token_responses"><?php echo esc_textarea($settings['token_responses']); ?></textarea>
                        <p class="description">Add one response per line. A random one will be shown when tokens are fed.</p>
                    </div>
                    
                    <h2>Custom Input Feature</h2>
                    
                    <div class="aab-setting-row">
                        <label>
                            <input type="checkbox" name="enable_custom_input" value="1" <?php checked($settings['enable_custom_input'], true); ?>>
                            Enable Custom Text Input (allows users to type their own questions)
                        </label>
                    </div>
                    
                    <div class="aab-setting-row">
                        <label>Custom Input Placeholder</label>
                        <input type="text" name="custom_input_label" value="<?php echo esc_attr($settings['custom_input_label']); ?>">
                    </div>
                </div>
                
                <?php submit_button('Save All Settings'); ?>
            </form>
            
            <!-- Live Preview -->
            <div class="aab-preview">
                <h3>Live Preview</h3>
                <p style="font-size: 11px; color: #666; margin: 5px 0;">Changes update in real-time!</p>
                <div class="aab-preview-avatar" id="previewAvatarContainer" style="
                    width: <?php echo intval($settings['avatar_size']); ?>px;
                    height: <?php echo intval($settings['avatar_size']); ?>px;
                    position: relative;
                    margin: 20px auto;
                ">
                    <div style="
                        width: 48px;
                        height: 64px;
                        position: absolute;
                        bottom: 10px;
                        left: 50%;
                        transform: translateX(-50%);
                    ">
                        <div id="previewHead" style="
                            width: 32px;
                            height: 32px;
                            background: <?php echo esc_attr($settings['avatar_skin_color']); ?>;
                            position: absolute;
                            top: 0;
                            left: 8px;
                            box-shadow: 
                                0 -4px 0 0 <?php echo esc_attr($settings['avatar_skin_shadow']); ?>,
                                -4px 0 0 0 <?php echo esc_attr($settings['avatar_skin_shadow']); ?>,
                                4px 0 0 0 <?php echo esc_attr($settings['avatar_skin_shadow']); ?>;
                        ">
                            <div id="previewEyeLeft" style="
                                width: 6px;
                                height: 6px;
                                background: <?php echo esc_attr($settings['avatar_eye_color']); ?>;
                                position: absolute;
                                top: 12px;
                                left: 6px;
                            "></div>
                            <div id="previewEyeRight" style="
                                width: 6px;
                                height: 6px;
                                background: <?php echo esc_attr($settings['avatar_eye_color']); ?>;
                                position: absolute;
                                top: 12px;
                                right: 6px;
                            "></div>
                            <div id="previewMouth" style="
                                width: 12px;
                                height: 2px;
                                background: <?php echo esc_attr($settings['avatar_mouth_color']); ?>;
                                position: absolute;
                                bottom: 8px;
                                left: 10px;
                            "></div>
                        </div>
                        <div id="previewTorso" style="
                            width: 32px;
                            height: 24px;
                            background: <?php echo esc_attr($settings['avatar_torso_color']); ?>;
                            position: absolute;
                            top: 32px;
                            left: 8px;
                            box-shadow: 
                                -4px 0 0 0 <?php echo esc_attr($settings['avatar_torso_shadow']); ?>,
                                4px 0 0 0 <?php echo esc_attr($settings['avatar_torso_shadow']); ?>;
                        "></div>
                        <div id="previewArmLeft" style="
                            width: 8px;
                            height: 20px;
                            background: <?php echo esc_attr($settings['avatar_skin_color']); ?>;
                            position: absolute;
                            top: 34px;
                            left: 0;
                        "></div>
                        <div id="previewArmRight" style="
                            width: 8px;
                            height: 20px;
                            background: <?php echo esc_attr($settings['avatar_skin_color']); ?>;
                            position: absolute;
                            top: 34px;
                            right: 0;
                        "></div>
                        <div id="previewLegLeft" style="
                            width: 12px;
                            height: 20px;
                            background: <?php echo esc_attr($settings['avatar_leg_color']); ?>;
                            position: absolute;
                            bottom: 0;
                            left: 6px;
                        "></div>
                        <div id="previewLegRight" style="
                            width: 12px;
                            height: 20px;
                            background: <?php echo esc_attr($settings['avatar_leg_color']); ?>;
                            position: absolute;
                            bottom: 0;
                            right: 6px;
                        "></div>
                    </div>
                </div>
                <div id="previewBubble" style="
                    margin-top: 20px;
                    padding: 10px;
                    background: <?php echo esc_attr($settings['bubble_bg_color']); ?>;
                    border: <?php echo intval($settings['bubble_border_width']); ?>px solid <?php echo esc_attr($settings['bubble_border_color']); ?>;
                    border-radius: <?php echo intval($settings['bubble_border_radius']); ?>px;
                    color: <?php echo esc_attr($settings['bubble_text_color']); ?>;
                    font-family: 'Courier New', monospace;
                    font-size: <?php echo intval($settings['bubble_font_size']); ?>px;
                ">
                    <span id="previewGreetingText"><?php echo esc_html($settings['greeting_message']); ?></span>
                </div>
                <button id="previewButton" style="
                    margin-top: 10px;
                    width: 100%;
                    background: <?php echo esc_attr($settings['button_bg_color']); ?>;
                    color: <?php echo esc_attr($settings['button_text_color']); ?>;
                    border: 2px solid <?php echo esc_attr($settings['button_border_color']); ?>;
                    padding: <?php echo intval($settings['button_padding_vertical']); ?>px <?php echo intval($settings['button_padding_horizontal']); ?>px;
                    font-family: 'Courier New', monospace;
                    font-size: <?php echo intval($settings['button_font_size']); ?>px;
                    cursor: pointer;
                "><span id="previewButtonText"><?php echo esc_html($settings['option_say_hello']); ?></span></button>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize color pickers with live preview
            $('.aab-color-picker').wpColorPicker({
                change: function(event, ui) {
                    updatePreview();
                },
                clear: function() {
                    updatePreview();
                }
            });
            
            // Tab switching
            $('.aab-tab').on('click', function() {
                const tab = $(this).data('tab');
                
                $('.aab-tab').removeClass('active');
                $(this).addClass('active');
                
                $('.aab-tab-content').removeClass('active');
                $('[data-tab-content="' + tab + '"]').addClass('active');
            });
            
            // Handle page selection from datalist
            $('#enabled_pages').on('input', function() {
                const input = $(this);
                const val = input.val().trim();
                
                // Check if user selected from datalist
                const option = $('#pages-list option[value="' + val + '"]');
                if (option.length) {
                    const currentVal = input.val();
                    
                    // If 'all' is selected, just set it
                    if (currentVal === 'all') {
                        return;
                    }
                    
                    // Get existing page IDs
                    let existingVal = input.attr('data-selected') || '';
                    let pageIds = existingVal.split(',').map(id => id.trim()).filter(id => id && id !== 'all');
                    
                    // Add new ID if not already present
                    if (!pageIds.includes(currentVal)) {
                        pageIds.push(currentVal);
                    }
                    
                    // Update the input
                    const newVal = pageIds.join(', ');
                    input.val(newVal);
                    input.attr('data-selected', newVal);
                }
            });
            
            // Initialize data-selected attribute
            $('#enabled_pages').attr('data-selected', $('#enabled_pages').val());
            
            // Real-time preview updates
            function updatePreview() {
                // Avatar colors
                const skinColor = $('input[name="avatar_skin_color"]').val();
                const skinShadow = $('input[name="avatar_skin_shadow"]').val();
                const eyeColor = $('input[name="avatar_eye_color"]').val();
                const mouthColor = $('input[name="avatar_mouth_color"]').val();
                const torsoColor = $('input[name="avatar_torso_color"]').val();
                const torsoShadow = $('input[name="avatar_torso_shadow"]').val();
                const legColor = $('input[name="avatar_leg_color"]').val();
                
                // Update avatar parts
                $('#previewHead').css({
                    'background': skinColor,
                    'box-shadow': '0 -4px 0 0 ' + skinShadow + ', -4px 0 0 0 ' + skinShadow + ', 4px 0 0 0 ' + skinShadow
                });
                $('#previewEyeLeft, #previewEyeRight').css('background', eyeColor);
                $('#previewMouth').css('background', mouthColor);
                $('#previewTorso').css({
                    'background': torsoColor,
                    'box-shadow': '-4px 0 0 0 ' + torsoShadow + ', 4px 0 0 0 ' + torsoShadow
                });
                $('#previewArmLeft, #previewArmRight').css('background', skinColor);
                $('#previewLegLeft, #previewLegRight').css('background', legColor);
                
                // Bubble styling
                const bubbleBg = $('input[name="bubble_bg_color"]').val();
                const bubbleBorder = $('input[name="bubble_border_color"]').val();
                const bubbleText = $('input[name="bubble_text_color"]').val();
                const bubbleFontSize = $('input[name="bubble_font_size"]').val();
                const bubbleBorderWidth = $('input[name="bubble_border_width"]').val();
                const bubbleBorderRadius = $('input[name="bubble_border_radius"]').val();
                
                $('#previewBubble').css({
                    'background': bubbleBg,
                    'border': bubbleBorderWidth + 'px solid ' + bubbleBorder,
                    'border-radius': bubbleBorderRadius + 'px',
                    'color': bubbleText,
                    'font-size': bubbleFontSize + 'px'
                });
                
                // Button styling
                const buttonBg = $('input[name="button_bg_color"]').val();
                const buttonText = $('input[name="button_text_color"]').val();
                const buttonBorder = $('input[name="button_border_color"]').val();
                const buttonFontSize = $('input[name="button_font_size"]').val();
                const buttonPaddingV = $('input[name="button_padding_vertical"]').val();
                const buttonPaddingH = $('input[name="button_padding_horizontal"]').val();
                
                $('#previewButton').css({
                    'background': buttonBg,
                    'color': buttonText,
                    'border': '2px solid ' + buttonBorder,
                    'font-size': buttonFontSize + 'px',
                    'padding': buttonPaddingV + 'px ' + buttonPaddingH + 'px'
                });
                
                // Avatar size
                const avatarSize = $('input[name="avatar_size"]').val();
                $('#previewAvatarContainer').css({
                    'width': avatarSize + 'px',
                    'height': avatarSize + 'px'
                });
                
                // Text updates
                const greetingMsg = $('input[name="greeting_message"]').val();
                const sayHelloText = $('input[name="option_say_hello"]').val();
                
                $('#previewGreetingText').text(greetingMsg);
                $('#previewButtonText').text(sayHelloText);
            }
            
            // Attach live update to all relevant inputs
            $('input[name="avatar_skin_color"], input[name="avatar_skin_shadow"], input[name="avatar_eye_color"], input[name="avatar_mouth_color"], input[name="avatar_torso_color"], input[name="avatar_torso_shadow"], input[name="avatar_leg_color"]').on('input change', updatePreview);
            
            $('input[name="bubble_bg_color"], input[name="bubble_border_color"], input[name="bubble_text_color"], input[name="bubble_font_size"], input[name="bubble_border_width"], input[name="bubble_border_radius"]').on('input change', updatePreview);
            
            $('input[name="button_bg_color"], input[name="button_text_color"], input[name="button_border_color"], input[name="button_font_size"], input[name="button_padding_vertical"], input[name="button_padding_horizontal"]').on('input change', updatePreview);
            
            $('input[name="avatar_size"], input[name="greeting_message"], input[name="option_say_hello"]').on('input change', updatePreview);
        });
        </script>
        <?php
    }
}

// Initialize plugin
new AI_Avatar_Buddy();