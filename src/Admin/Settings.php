<?php

namespace Ref247\Admin;

class Settings
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'register']);
    }

    public function register()
    {
        register_setting('ref247_settings', 'ref247_api_key');
        register_setting('ref247_settings', 'ref247_api_secret');
        register_setting('ref247_settings', 'ref247_cookie_days');
        register_setting('ref247_settings', 'ref247_clear_cookie_on_purchase');

        add_settings_section(
            'ref247_main',
            'Connection Settings',
            [$this, 'renderInstructions'],
            'ref247'
        );

        add_settings_field(
            'ref247_api_key',
            'API Key',
            [$this, 'apiKeyField'],
            'ref247',
            'ref247_main'
        );

        add_settings_field(
            'ref247_api_secret',
            'API Secret',
            [$this, 'apiSecretField'],
            'ref247',
            'ref247_main'
        );

        add_settings_field(
            'ref247_cookie_days',
            'Cookie Expiration (Days)',
            [$this, 'cookieDaysField'],
            'ref247',
            'ref247_main'
        );

        add_settings_field(
            'ref247_clear_cookie_on_purchase',
            'Clear Cookie After Purchase',
            [$this, 'clearCookieField'],
            'ref247',
            'ref247_main'
        );
    }

    public function renderInstructions()
    {
        echo '<p>To get started, <a href="https://ref247.io/dashboard" target="_blank">sign up at Ref247.io</a> and generate your API credentials.</p>';
        
        $orgId = get_option('ref247_org_id');
        $orgName = get_option('ref247_org_name');
        $campaigns = get_option('ref247_campaigns');
        $isConnected = get_option('ref247_connected');
        
        if ($isConnected && $orgId && is_array($campaigns)) {
            $displayName = $orgName ? $orgName . ' (' . $orgId . ')' : $orgId;
            echo '<div style="background: #e7f6e7; border-left: 4px solid #46b450; padding: 10px 15px; margin-bottom: 20px;">';
            echo '<p style="margin: 0 0 10px 0; color: #005a00;"><strong>Status: Connected</strong> to Organization: <strong>' . esc_html($displayName) . '</strong></p>';
            
            if (count($campaigns) > 0) {
                echo '<p style="margin: 0 0 5px 0;"><strong>Active Campaigns (' . count($campaigns) . '):</strong></p>';
                echo '<ul style="margin: 0 0 0 20px; list-style-type: disc;">';
                foreach ($campaigns as $campaign) {
                    $campName = $campaign['name'] ?? 'Unknown Campaign';
                    $campId = $campaign['id'] ?? $campaign['_id'] ?? 'N/A';
                    echo '<li><strong>' . esc_html($campName) . '</strong> (ID: <code>' . esc_html($campId) . '</code>)</li>';
                }
                echo '</ul>';
            } else {
                echo '<p style="margin: 0;">No campaigns found for this organization.</p>';
            }
            
            echo '</div>';

            // Show WooCommerce Integration Details
            $wcEventId = get_option('ref247_wc_event_type_id');
            $currencies = get_option('ref247_currencies', []);

            echo '<div style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">';
            echo '<h4 style="margin-top:0;">WooCommerce Integration</h4>';
            echo '<p><strong>Integrated Event:</strong> <code>woocommerce_purchase</code>' . ($wcEventId ? " (ID: $wcEventId)" : " (Sync required)") . '</p>';
            
            if (!empty($currencies)) {
                echo '<strong>Supported Currencies:</strong>';
                echo '<ul style="margin-top:10px; list-style: disc; padding-left: 20px;">';
                foreach ($currencies as $currency) {
                    $name = esc_html($currency['name'] ?? 'Unknown');
                    $id = esc_html($currency['id'] ?? 'N/A');
                    echo "<li>$name (ID: $id)</li>";
                }
                echo '</ul>';
            } else {
                echo '<p><em>No currencies synced. Click "Save API Credentials" or "Refresh Campaigns" to sync.</em></p>';
            }
            echo '</div>';

            // Show Unified Form Submission Integration Details
            $formEventId = get_option('ref247_form_event_type_id');
            echo '<div style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">';
            echo '<h4 style="margin-top:0;">Form Submissions Integration</h4>';
            echo '<p><strong>Integrated Event:</strong> <code>form_submission</code>' . ($formEventId ? " (ID: $formEventId)" : " (Sync required)") . '</p>';
            echo '<p style="margin-bottom:0; font-size: 12px; color: #666;">Supports: Contact Form 7, WPForms, and Gravity Forms.</p>';
            echo '</div>';
        } else {
            echo '<div style="background: #fff8e5; border-left: 4px solid #ffb900; padding: 10px 15px; margin-bottom: 20px;">';
            echo '<p style="margin: 0; color: #8a6a00;"><strong>Status: Not Connected</strong>. Please enter your API credentials and save.</p>';
            echo '</div>';
        }
    }

    public function apiKeyField()
    {
        $value = get_option('ref247_api_key');
        echo "<input type='text' name='ref247_api_key' value='" . esc_attr($value) . "' style='width:400px' />";
    }

    public function apiSecretField()
    {
        $value = get_option('ref247_api_secret');
        echo "<input type='password' name='ref247_api_secret' value='" . esc_attr($value) . "' style='width:400px' />";
    }

    public function cookieDaysField()
    {
        $value = get_option('ref247_cookie_days', 30);
        echo "<input type='number' name='ref247_cookie_days' value='" . esc_attr($value) . "' style='width:100px' min='1' max='365' />";
        echo "<p class='description'>How many days the affiliate tracking cookie should live. Default is 30.</p>";
    }

    public function clearCookieField()
    {
        // Default to true (or '1') if not set
        $value = get_option('ref247_clear_cookie_on_purchase', '1');
        $checked = checked(1, $value, false);
        echo "<input type='checkbox' name='ref247_clear_cookie_on_purchase' value='1' " . $checked . " />";
        echo "<p class='description'>If checked, the affiliate cookie is deleted upon successful purchase tracking. Uncheck to allow lifetime referral tracking from a single click.</p>";
    }
}