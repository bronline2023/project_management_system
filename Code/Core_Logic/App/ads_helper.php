<?php
/**
 * ads_helper.php
 * This file handles the logic for displaying Google AdSense ads on digital service pages.
 * It checks for "Pro" user status and admin settings.
 */

if (!function_exists('is_pro_user')) {
    /**
     * Checks if a user has an active subscription to hide ads.
     *
     * @param int|null $userId The user ID to check.
     * @return bool True if the user is a "Pro" user.
     */
    function is_pro_user($userId = null) {
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        if (!$userId) return false;

        $pdo = connectDB();
        $sub_query = "SELECT id FROM user_subscriptions 
                      WHERE user_id = ? AND status = 'active' AND end_date >= NOW() 
                      LIMIT 1";
        $sub = fetchOne($pdo, $sub_query, [$userId]);

        return !empty($sub);
    }
}

if (!function_exists('should_show_ads')) {
    /**
     * Checks if ads should be displayed for a specific digital service.
     *
     * @param string $serviceSlug The slug of the current service.
     * @return bool True if ads should be shown.
     */
    function should_show_ads($serviceSlug) {
        $pdo = connectDB();
        $settings = fetchOne($pdo, "SELECT ads_global_toggle, ads_enabled_services, adsense_global_code FROM settings WHERE id = 1 LIMIT 1");

        // 1. Is global toggle ON?
        if (!$settings || (int)$settings['ads_global_toggle'] !== 1) return false;

        // 2. Is there even a script code?
        if (empty($settings['adsense_global_code'])) return false;

        // 3. Is the user a Pro user? (If logged in)
        if (is_pro_user()) return false;

        // 4. Is ads enabled for THIS specific service?
        $enabledServices = json_decode($settings['ads_enabled_services'] ?? '[]', true);
        if (!is_array($enabledServices)) $enabledServices = [];

        return in_array($serviceSlug, $enabledServices);
    }
}

if (!function_exists('get_adsense_codes')) {
    /**
     * Fetches the AdSense global and ad unit codes.
     *
     * @return array
     */
    function get_adsense_codes() {
        $pdo = connectDB();
        $settings = fetchOne($pdo, "SELECT adsense_global_code, adsense_ad_unit_code FROM settings WHERE id = 1 LIMIT 1");
        return [
            'global' => $settings['adsense_global_code'] ?? '',
            'ad_unit' => $settings['adsense_ad_unit_code'] ?? ''
        ];
    }
}
?>
