<?php

namespace Ref247\Shortcodes;

use Ref247\Api\Ref247Client;
use Ref247\Tracking\ReferralTracker;

class SignupShortcode
{
    public function __construct()
    {
        add_shortcode('ref247_signup', [$this, 'render']);
        // Hook into admin_post or init to handle form submissions
        add_action('init', [$this, 'handleSubmission']);
    }

    public function handleSubmission()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ref247_signup_nonce'])) {
            if (!wp_verify_nonce($_POST['ref247_signup_nonce'], 'ref247_signup_action')) {
                return;
            }

            $email = sanitize_email($_POST['ref247_email'] ?? '');
            if (!is_email($email)) {
                $this->setFlashMessage('Invalid email address provided.', 'error');
                return;
            }

            $orgId = get_option('ref247_org_id');
            $roleId = get_option('ref247_affiliate_role_id');

            if (!$orgId || !$roleId) {
                $this->setFlashMessage('Ref247 plugin is not fully configured (missing Org ID or Affiliate Role ID).', 'error');
                return;
            }

            $client = new Ref247Client();
            $affiliateData = ReferralTracker::getStoredData();
            $affId = isset($affiliateData['affId']) ? $affiliateData['affId'] : null;
            $linkUri = isset($affiliateData['linkUri']) ? $affiliateData['linkUri'] : null;
            $result = $client->addUserToOrganization($orgId, $email, $roleId, $linkUri, $affId);

            if ($result === false) {
                $this->setFlashMessage('Failed to create affiliate account. The email might already be registered.', 'error');
            } else {
                $this->setFlashMessage('Successfully signed up! Check your email for next steps.', 'success');
            }

            // Redirect to the same page to prevent form resubmission
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }

    public function render($atts)
    {
        ob_start();

        // Display flash messages
        $this->displayFlashMessage();

        // Render Form Output
        ?>
        <div class="ref247-signup-wrapper" style="max-width: 400px; margin: 20px 0; font-family: sans-serif;">
            <form method="post" action="">
                <?php wp_nonce_field('ref247_signup_action', 'ref247_signup_nonce'); ?>
                
                <div style="margin-bottom: 15px;">
                    <label for="ref247_email" style="display: block; font-weight: bold; margin-bottom: 5px;">Email Address *</label>
                    <input type="email" id="ref247_email" name="ref247_email" required
                           style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"
                           placeholder="Enter your email to become an affiliate">
                </div>
                
                <div>
                    <button type="submit" style="background-color: #0073aa; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">
                        Sign Up as Affiliate
                    </button>
                </div>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    private function setFlashMessage($message, $type = 'success')
    {
        // Simple session-less transient flash using WordPress transients tied to user IP
        // A better approach in production might be a session or JS redirect, but transient works for simple use cases
        $ip = $_SERVER['REMOTE_ADDR'];
        set_transient('ref247_flash_' . md5($ip), ['message' => $message, 'type' => $type], 60);
    }

    private function displayFlashMessage()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $transient_key = 'ref247_flash_' . md5($ip);
        $flash = get_transient($transient_key);

        if ($flash) {
            delete_transient($transient_key); // clear immediately after reading
            
            $bg = $flash['type'] === 'error' ? '#f8d7da' : '#d4edda';
            $color = $flash['type'] === 'error' ? '#721c24' : '#155724';
            $border = $flash['type'] === 'error' ? '#f5c6cb' : '#c3e6cb';

            echo '<div style="background-color: ' . esc_attr($bg) . '; color: ' . esc_attr($color) . '; border: 1px solid ' . esc_attr($border) . '; padding: 12px; margin-bottom: 20px; border-radius: 4px;">';
            echo esc_html($flash['message']);
            echo '</div>';
        }
    }
}
