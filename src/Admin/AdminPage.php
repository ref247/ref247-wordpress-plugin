<?php
/**
 * Ref247 Admin Page Class
 *
 * Manages the admin dashboard and settings pages for the Ref247 plugin.
 * Displays organization statistics, commission metrics, and affiliate insights.
 *
 * @package Ref247\Admin
 * @since 1.0.0
 */

namespace Ref247\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminPage
 *
 * Handles admin dashboard rendering and settings management for Ref247.
 *
 * @since 1.0.0
 */
class AdminPage
{
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'handleActions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Enqueue administrative scripts and styles
     *
     * @param string $hook The current admin page hook.
     * @since 1.0.1
     */
    public function enqueueScripts($hook)
    {
        // Only load on our plugin page
        if ($hook !== 'toplevel_page_ref247') {
            return;
        }

        wp_enqueue_script(
            'ref247-chartjs',
            REF247_PLUGIN_URL . 'assets/js/chart.js',
            [],
            REF247_PLUGIN_VERSION,
            true
        );

        wp_enqueue_script(
            'ref247-admin-dashboard',
            REF247_PLUGIN_URL . 'assets/js/admin-dashboard.js',
            ['ref247-chartjs'],
            REF247_PLUGIN_VERSION,
            true
        );

        wp_localize_script('ref247-admin-dashboard', 'ref247Data', $this->getChartData());
    }

    /**
     * Get chart data for the dashboard
     * 
     * @return array
     */
    private function getChartData()
    {
        $client = new \Ref247\Api\Ref247Client();
        $orgId = get_option('ref247_org_id');
        
        $chartData = $client->getOrgCommissionsChartData($orgId);
        $referralsChartData = $client->getOrgReferralsChartData($orgId);

        $pendingLabels = [];
        $pendingSeries = [];
        $paidLabels = [];
        $paidSeries = [];
        
        if (is_array($chartData)) {
            if (isset($chartData['pending']) && is_array($chartData['pending'])) {
                foreach ($chartData['pending'] as $point) {
                    $pendingLabels[] = $point['date'] ?? '';
                    $value = 0;
                    foreach ($point as $key => $val) {
                        if (is_numeric($val) && $key !== 'date') {
                            $value = $val;
                            break;
                        }
                    }
                    $pendingSeries[] = $value;
                }
            }
            if (isset($chartData['paid']) && is_array($chartData['paid'])) {
                foreach ($chartData['paid'] as $point) {
                    $paidLabels[] = $point['date'] ?? '';
                    $value = 0;
                    foreach ($point as $key => $val) {
                        if (is_numeric($val) && $key !== 'date') {
                            $value = $val;
                            break;
                        }
                    }
                    $paidSeries[] = $value;
                }
            }
        }
        
        $referralsLabels = [];
        $referralsSeries = [];
        
        if (is_array($referralsChartData)) {
            foreach ($referralsChartData as $point) {
                $referralsLabels[] = $point['date'] ?? '';
                $value = 0;
                foreach ($point as $key => $val) {
                    if (is_numeric($val) && $key !== 'date') {
                        $value = $val;
                        break;
                    }
                }
                $referralsSeries[] = $value;
            }
        }

        return [
            'pendingLabels' => $pendingLabels,
            'pendingSeries' => $pendingSeries,
            'paidLabels'    => $paidLabels,
            'paidSeries'    => $paidSeries,
            'referralsLabels' => $referralsLabels,
            'referralsSeries' => $referralsSeries,
        ];
    }

    /**
     * Handle action buttons and form submissions
     *
     * Processes manual refresh requests and auto-refresh when API credentials are saved.
     *
     * @since 1.0.0
     */
    public function handleActions()
    {
        // 1. Handle manual refresh via button
        if (isset($_POST['ref247_refresh_action']) && current_user_can('manage_options')) {
            check_admin_referer('ref247_refresh');
            $this->performSync(__('Successfully refreshed organization & campaigns.', 'ref247-affiliate-tracking'));
        } 
        
        // 2. Handle auto-refresh when settings are saved
        // WordPress redirects back to settings page with `settings-updated=true`
        elseif (isset($_GET['settings-updated']) && sanitize_text_field(wp_unslash($_GET['settings-updated'])) === 'true' && isset($_GET['page']) && sanitize_text_field(wp_unslash($_GET['page'])) === 'ref247') {
            $this->performSync(__('API credentials saved and campaigns successfully synced.', 'ref247-affiliate-tracking'));
        }
    }

    /**
     * Perform synchronization with Ref247 API
     *
     * Fetches organization data, campaigns, roles, and event types from Ref247.
     * Validates user permissions and stores the retrieved data in WordPress options.
     *
     * @param string $successMessage Message to display on successful sync.
     *
     * @since 1.0.0
     */
    private function performSync($successMessage)
    {
            
            $client = new \Ref247\Api\Ref247Client();
            $orgs = $client->getOrganizations();
            
            if (!$orgs || empty($orgs)) {
                update_option('ref247_connected', false);
                add_settings_error('ref247_settings', 'ref247_error', __('Failed to fetch organizations or no organizations found.', 'ref247-affiliate-tracking'), 'error');
                return;
            }
            if (is_array($orgs) && isset($orgs[0])) {
                $firstOrg = $orgs[0];
                $roleName = $firstOrg['role']['name'];
                $orgId = $firstOrg['organization']['id'];
                $orgName = $firstOrg['organization']['name'];
                if ($roleName !== 'admin' && $roleName !== 'manager') {
                    update_option('ref247_connected', false);
                    add_settings_error('ref247_settings', 'ref247_error', __('You must be an admin or manager to use this plugin.', 'ref247-affiliate-tracking'), 'error');
                    return;
                }
                if (!$orgId) {
                    // If it's something else, try to fallback to the raw data structure dump
                    update_option('ref247_connected', false);
                    add_settings_error('ref247_settings', 'ref247_error', __('Organization mapping failed. Please check your API credentials.', 'ref247-affiliate-tracking'), 'error');
                    return;
                }
                update_option('ref247_org_id', $orgId);
                if ($orgName) {
                    update_option('ref247_org_name', $orgName);
                }

                $campaigns = $client->getAllCampaignsOfOrganization($orgId);
                if ($campaigns === false) {
                    update_option('ref247_connected', false);
                    add_settings_error('ref247_settings', 'ref247_error', __('Failed to fetch campaigns.', 'ref247-affiliate-tracking'), 'error');
                    return;
                }

                update_option('ref247_campaigns', $campaigns);
                
                // Fetch and save the Affiliate Role ID
                $roles = $client->getRoles();
                if (is_array($roles)) {
                    foreach ($roles as $role) {
                        if (strtolower($role['name']) === 'affiliate') {
                            $roleId = $role['id'] ?? $role['_id'];
                            update_option('ref247_affiliate_role_id', $roleId);
                            break;
                        }
                    }
                }

                // Fetch or Create WooCommerce Event Type
                $eventTypes = $client->getOrganizationEventTypes($orgId);
                $wcEventTypeId = null;

                if (is_array($eventTypes)) {
                    foreach ($eventTypes as $et) {
                        if ($et['name'] === 'woocommerce_purchase') {
                            $wcEventTypeId = $et['id'];
                            break;
                        }
                    }
                }

                if (!$wcEventTypeId) {
                    $newEventType = $client->addOrganizationEventType($orgId, 'woocommerce_purchase');
                    if (is_array($newEventType) && isset($newEventType['id'])) {
                        $wcEventTypeId = $newEventType['id'];
                    }
                }

                if ($wcEventTypeId) {
                    update_option('ref247_wc_event_type_id', $wcEventTypeId);
                }

                // Fetch or Create Unified Form Event Type
                $formEventTypeId = null;
                if (is_array($eventTypes)) {
                    foreach ($eventTypes as $et) {
                        if ($et['name'] === 'form_submission') {
                            $formEventTypeId = $et['id'];
                            break;
                        }
                    }
                }

                if (!$formEventTypeId) {
                    $newEventType = $client->addOrganizationEventType($orgId, 'form_submission');
                    if (is_array($newEventType) && isset($newEventType['id'])) {
                        $formEventTypeId = $newEventType['id'];
                    }
                }

                if ($formEventTypeId) {
                    update_option('ref247_form_event_type_id', $formEventTypeId);
                }

                // Fetch and save supported currencies
                $currencies = $client->getOrganizationCurrencies($orgId);
                if (is_array($currencies)) {
                    update_option('ref247_currencies', $currencies);
                }

                // Auto-generate Affiliate Signup Page
                $signupPageId = get_option('ref247_signup_page_id');
                if (!$signupPageId || get_post_status($signupPageId) === false) {
                    $postContent = "<!-- wp:paragraph -->\n";
                    $postContent .= "<p>Join our affiliate program and start earning rewards for referring new customers! Powered by <a href=\"https://ref247.io\" target=\"_blank\" rel=\"noopener\">Ref247.io</a>, an external affiliate tracking system where you can check your performance, get your unique affiliate links, and download marketing assets.</p>\n";
                    $postContent .= "<!-- /wp:paragraph -->\n\n";
                    $postContent .= "<!-- wp:shortcode -->\n";
                    $postContent .= "[ref247_signup]\n";
                    $postContent .= "<!-- /wp:shortcode -->";

                    $newPostId = wp_insert_post([
                        'post_title'    => 'Affiliate Signup',
                        'post_content'  => $postContent,
                        'post_status'   => 'publish',
                        'post_type'     => 'page',
                    ]);

                    if (!is_wp_error($newPostId)) {
                        update_option('ref247_signup_page_id', $newPostId);
                        add_settings_error('ref247_settings', 'ref247_page_created', 'The "Affiliate Signup" page was automatically created for you.', 'updated');
                    }
                }

                // Mark as successfully connected
                update_option('ref247_connected', true);
                add_settings_error('ref247_settings', 'ref247_success', $successMessage, 'updated');
            } else {
                update_option('ref247_connected', false);
                add_settings_error('ref247_settings', 'ref247_error', 'Organization loading failed. Received: ' . json_encode($orgs), 'error');
                return;
            }
    }

    /**
     * Add admin menu item
     *
     * Registers the Ref247 menu item in the WordPress admin sidebar.
     *
     * @since 1.0.0
     */
    public function addMenu()
    {
        add_menu_page(
            __('Ref247', 'ref247-affiliate-tracking'),
            __('Ref247', 'ref247-affiliate-tracking'),
            'manage_options',
            'ref247',
            [$this, 'render'],
            'dashicons-chart-line'
        );
    }

    /**
     * Check if API credentials are valid
     *
     * Validates that both API key and secret are set, and that they can
     * successfully authenticate with the Ref247 API.
     *
     * @return bool True if credentials are valid, false otherwise.
     *
     * @since 1.0.0
     */
    private function hasApiCredentials()
    {
        $key = get_option('ref247_api_key');
        $secret = get_option('ref247_api_secret');
        if (empty($key) || empty($secret)) {
            return false;
        }

        // try a lightweight API call to verify credentials
        $client = new \Ref247\Api\Ref247Client();
        $orgs = $client->getOrganizations();
        if ($orgs === false || is_wp_error($orgs) || empty($orgs)) {
            return false;
        }
        return true;
    }

    /**
     * Render the admin dashboard
     *
     * Displays key metrics, summary tables, and charts with commission and referral trends.
     * Fetches data from Ref247 API and displays it in an organized dashboard layout.
     *
     * @since 1.0.0
     */
    private function renderDashboard()
    {
        $client = new \Ref247\Api\Ref247Client();
        $orgId = get_option('ref247_org_id');

        // try to resolve org id if missing
        if (!$orgId) {
            $orgs = $client->getOrganizations();
            if (is_array($orgs) && isset($orgs[0]['organization']['id'])) {
                $orgId = $orgs[0]['organization']['id'];
                update_option('ref247_org_id', $orgId);
            }
        }

        if (!$orgId) {
            echo '<p><strong>Unable to determine organization ID.</strong> Please refresh data or check your API credentials.</p>';
            return;
        }

        // helper for rendering individual panels
        $generic = $client->getOrganizationGenericStats($orgId);
        $private = $client->getOrganizationPrivateStats($orgId);
        $commissionSummary = $client->getOrganizationCommissionStats($orgId);
        $affiliation = $client->getOrganizationAffiliationStats($orgId);
        $chartData = $client->getOrgCommissionsChartData($orgId);
        $referralsChartData = $client->getOrgReferralsChartData($orgId);

        echo '<h2>Dashboard</h2>';

        // Key metrics cards
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem;">';
        
        if (is_array($generic)) {
            $activeCampaigns = $generic['activeCampaigns'] ?? 0;
            $currencies = $generic['currencies'] ?? 0;
            $eventTypes = $generic['eventTypes'] ?? 0;
            
            echo '<div style="border:1px solid #ddd;padding:1rem;border-radius:4px;background:#f9f9f9;">';
            echo '<strong style="display:block;color:#0073aa;font-size:24px;">' . esc_html($activeCampaigns) . '</strong>';
            echo '<em>Active Campaigns</em></div>';
            
            echo '<div style="border:1px solid #ddd;padding:1rem;border-radius:4px;background:#f9f9f9;">';
            echo '<strong style="display:block;color:#0073aa;font-size:24px;">' . esc_html($currencies) . '</strong>';
            echo '<em>Currencies</em></div>';
            
            echo '<div style="border:1px solid #ddd;padding:1rem;border-radius:4px;background:#f9f9f9;">';
            echo '<strong style="display:block;color:#0073aa;font-size:24px;">' . esc_html($eventTypes) . '</strong>';
            echo '<em>Event Types</em></div>';
        }
        
        if (is_array($affiliation)) {
            $totalCommissionsGenerated = $affiliation['totalCommissionsGenerated'] ?? 0;
            $totalAffiliations = $affiliation['totalAffiliations'] ?? 0;
            
            echo '<div style="border:1px solid #ddd;padding:1rem;border-radius:4px;background:#f9f9f9;">';
            echo '<strong style="display:block;color:#0073aa;font-size:24px;">' . esc_html($totalAffiliations) . '</strong>';
            echo '<em>Total Affiliations</em></div>';
            
            echo '<div style="border:1px solid #ddd;padding:1rem;border-radius:4px;background:#f9f9f9;">';
            echo '<strong style="display:block;color:#0073aa;font-size:24px;">' . esc_html(number_format($totalCommissionsGenerated, 2)) . '</strong>';
            echo '<em>Commissions Generated</em></div>';
        }
        
        echo '</div>';

        // Commission Summary and User Role Distribution - Side by Side
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-top:1.5rem;">';
        
        // Commission Summary Table
        echo '<div>';
        echo '<h4>Commission Summary by Currency</h4>';
        if (is_array($commissionSummary) && !empty($commissionSummary)) {
            echo '<table style="width:100%;border-collapse:collapse;">';
            echo '<tr style="background:#f1f1f1;border-bottom:2px solid #ddd;">';
            echo '<th style="padding:10px;text-align:left;">Currency</th>';
            echo '<th style="padding:10px;text-align:left;">Pending</th>';
            echo '<th style="padding:10px;text-align:left;">Paid</th>';
            echo '</tr>';
            
            foreach ($commissionSummary as $summary) {
                $currency = $summary['currency'] ?? 'Unknown';
                $pending = $summary['pending'] ?? 0;
                $paid = $summary['paid'] ?? 0;
                echo '<tr style="border-bottom:1px solid #ddd;">';
                echo '<td style="padding:10px;">' . esc_html($currency) . '</td>';
                echo '<td style="padding:10px;">' . esc_html(number_format($pending, 2)) . '</td>';
                echo '<td style="padding:10px;">' . esc_html(number_format($paid, 2)) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p><em>No commission data available.</em></p>';
        }
        echo '</div>';

        // User Role Distribution
        echo '<div>';
        echo '<h4>User Role Distribution</h4>';
        if (is_array($private) && isset($private['userStats'])) {
            echo '<table style="width:100%;border-collapse:collapse;">';
            echo '<tr style="background:#f1f1f1;border-bottom:2px solid #ddd;">';
            echo '<th style="padding:10px;text-align:left;">Role</th>';
            echo '<th style="padding:10px;text-align:left;">Count</th>';
            echo '</tr>';
            
            foreach ($private['userStats'] as $stat) {
                $role = $stat['role'] ?? 'Unknown';
                $count = $stat['count'] ?? 0;
                echo '<tr style="border-bottom:1px solid #ddd;">';
                echo '<td style="padding:10px;">' . esc_html($role) . '</td>';
                echo '<td style="padding:10px;">' . esc_html($count) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p><em>No user role data available.</em></p>';
        }
        echo '</div>';
        
        echo '</div>';

        // Commission Chart - Pending vs Paid
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-top:1.5rem;">';
        
        // Charts container
        echo '<div>';
        echo '<h4>Commission Trend (Last 30 Days)</h4>';
        echo '<canvas id="ref247CommissionChart" width="400" height="250"></canvas>';
        echo '</div>';
        
        echo '<div>';
        echo '<h4>Referral Trend (Last 30 Days)</h4>';
        echo '<canvas id="ref247ReferralsChart" width="400" height="250"></canvas>';
        echo '</div>';
        
        echo '</div>';

        // Chart.js and initialization are enqueued via enqueueScripts()
    }

    /**
     * Render the admin page content
     *
     * Displays either the dashboard or settings based on the active tab.
     *
     * @since 1.0.0
     */
    public function render()
    {
        // Determine active tab from URL parameter, Ignore nonce check for this as not needed for simple tab switching
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Ref247', 'ref247-affiliate-tracking'); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=ref247&tab=dashboard" class="nav-tab <?php echo $tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Dashboard', 'ref247-affiliate-tracking'); ?>
                </a>
                <a href="?page=ref247&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Settings', 'ref247-affiliate-tracking'); ?>
                </a>
            </h2>

            <?php settings_errors('ref247_settings'); ?>

            <?php if ($tab === 'dashboard'): ?>
                <?php if ($this->hasApiCredentials() && get_option('ref247_connected')): ?>
                    <?php $this->renderDashboard(); ?>
                <?php else: ?>
                    <p>
                        <strong><?php esc_html_e('API credentials are missing, invalid, or connection failed.', 'ref247-affiliate-tracking'); ?></strong>
                        <?php
                        printf(
                            /* translators: %s is a link to the Settings tab */
                            esc_html__( 'Please visit the %s tab to enter correct credentials and verify the connection.', 'ref247-affiliate-tracking' ),
                            '<a href="?page=ref247&tab=settings">' . esc_html__( 'Settings', 'ref247-affiliate-tracking' ) . '</a>'
                        );
                        ?>
                    </p>
                <?php endif; ?>
            <?php else: // settings tab ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('ref247_settings');
                    do_settings_sections('ref247');
                    submit_button(__('Save API Credentials', 'ref247-affiliate-tracking'));
                    ?>
                </form>
                <hr>
                <h2><?php esc_html_e('Refresh Data', 'ref247-affiliate-tracking'); ?></h2>
                <p><?php esc_html_e('After saving your API credentials above, click this button to verify the connection and load your data.', 'ref247-affiliate-tracking'); ?></p>
                <form method="post" action="">
                    <input type="hidden" name="ref247_refresh_action" value="1">
                    <?php wp_nonce_field('ref247_refresh'); ?>
                    <?php submit_button(__('Force Refresh', 'ref247-affiliate-tracking'), 'secondary', 'submit', false); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
}