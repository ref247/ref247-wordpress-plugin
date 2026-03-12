<?php

namespace Ref247\Tracking;

class ReferralTracker
{
    const STORAGE_KEY = 'ref247_affiliate';

    public function __construct()
    {
        // Hook early so cookies can be set before headers are sent
        add_action('init', [$this, 'init']);
    }

    public function init()
    {
        // Only run on frontend to avoid tracking admin actions
        if (is_admin()) {
            return;
        }

        $newData = [];

        // Check for URL parameters
        if (isset($_GET['affId'])) {
            $newData['affId'] = sanitize_text_field($_GET['affId']);
        }
        if (isset($_GET['linkUri'])) {
            $newData['linkUri'] = sanitize_text_field($_GET['linkUri']);
        }
        if (isset($_GET['clickId'])) {
            $newData['clickId'] = sanitize_text_field($_GET['clickId']);
        }

        // If we found any new data from the URL, process and update cookie
        if (!empty($newData)) {
            $stored = self::getStoredData();
            
            // Merge existing data with new data (new overwrites existing)
            $mergedData = array_merge($stored, $newData);
            
            // Fetch configured cookie days or default to 30
            $cookieDays = (int) get_option('ref247_cookie_days', 30);
            if ($cookieDays <= 0) {
                $cookieDays = 30;
            }
            
            // Set cookie to expire
            $expire = time() + ($cookieDays * DAY_IN_SECONDS);
            
            // Serialize data as JSON before storing
            $jsonValue = wp_json_encode($mergedData);
            
            setcookie(
                self::STORAGE_KEY,
                $jsonValue,
                $expire,
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(), // Secure flag
                true // HttpOnly flag to prevent JS access if possible (tracker might need JS access depending on design, but secure by default)
            );

            // Also update the current $_COOKIE array so it's instantly available in the same request
            $_COOKIE[self::STORAGE_KEY] = $jsonValue;
        }
    }

    /**
     * Retrieve the currently stored affiliate data from the cookie.
     * 
     * @return array associative array ['affId' => '...', 'clickId' => '...']
     */
    public static function getStoredData()
    {
        if (isset($_COOKIE[self::STORAGE_KEY])) {
            // Check if stripslashes is needed (WordPress sometimes adds magic quotes to $_COOKIE)
            $cookieData = wp_unslash($_COOKIE[self::STORAGE_KEY]);
            $decoded = json_decode($cookieData, true);
            
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        return [];
    }

    /**
     * Helper to clear the stored affiliate data
     */
    public static function clear()
    {
        setcookie(
            self::STORAGE_KEY,
            '',
            time() - 3600,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
        unset($_COOKIE[self::STORAGE_KEY]);
    }
}
