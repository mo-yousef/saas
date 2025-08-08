<?php
/**
 * Dashboard Page: Settings (Business & Email)
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Fetch settings
$settings_manager = new \MoBooking\Classes\Settings();
$user_id = get_current_user_id();
$biz_settings = $settings_manager->get_business_settings($user_id);

// Helper functions (can be moved to a common file if used across multiple pages)
function mobooking_get_biz_setting_value($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_attr($settings[$key]) : esc_attr($default);
}
function mobooking_get_biz_setting_textarea($settings, $key, $default = '') {
    return isset($settings[$key]) ? esc_textarea($settings[$key]) : esc_textarea($default);
}
function mobooking_select_biz_setting_value($settings, $key, $value, $default_value = '') {
    $current_val = isset($settings[$key]) ? $settings[$key] : $default_value;
    return selected($value, $current_val, false);
}

?>
<div>
    <h3 class="text-3xl font-medium text-gray-700 dark:text-gray-200">Business Settings</h3>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage your core business information, email configurations, and operating hours.</p>

    <div class="mt-8">
        <form id="mobooking-business-settings-form" method="post">
            <?php wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field'); ?>
            <div id="mobooking-settings-feedback" class="hidden p-4 mb-4 text-sm rounded-lg"></div>

            <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Business Information</h2>
                <div class="grid grid-cols-1 gap-6 mt-4 sm:grid-cols-2">
                    <div>
                        <label class="text-gray-700 dark:text-gray-200" for="biz_name">Business Name</label>
                        <input id="biz_name" type="text" name="biz_name" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_name'); ?>"
                               class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                    </div>

                    <div>
                        <label class="text-gray-700 dark:text-gray-200" for="biz_email">Public Business Email</label>
                        <input id="biz_email" type="email" name="biz_email" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_email'); ?>"
                               class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                    </div>

                    <div>
                        <label class="text-gray-700 dark:text-gray-200" for="biz_phone">Business Phone</label>
                        <input id="biz_phone" type="tel" name="biz_phone" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_phone'); ?>"
                               class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                    </div>

                    <div>
                        <label class="text-gray-700 dark:text-gray-200" for="biz_logo_url">Business Logo URL</label>
                        <input id="biz_logo_url" type="url" name="biz_logo_url" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_logo_url'); ?>"
                               class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                    </div>

                    <div class="col-span-2">
                        <label class="text-gray-700 dark:text-gray-200" for="biz_address">Business Address</label>
                        <textarea id="biz_address" name="biz_address"
                                  class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring"><?php echo mobooking_get_biz_setting_textarea($biz_settings, 'biz_address'); ?></textarea>
                    </div>

                    <div>
                        <label class="text-gray-700 dark:text-gray-200" for="biz_currency_code">Currency</label>
                        <select id="biz_currency_code" name="biz_currency_code"
                                class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                            <option value="USD" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'USD', 'USD'); ?>>USD (US Dollar)</option>
                            <option value="EUR" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'EUR'); ?>>EUR (Euro)</option>
                            <option value="SEK" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'SEK'); ?>>SEK (Swedish Krona)</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-gray-700 dark:text-gray-200" for="biz_user_language">Language</label>
                        <select id="biz_user_language" name="biz_user_language"
                                class="block w-full px-4 py-2 mt-2 text-gray-700 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-500 focus:outline-none focus:ring">
                            <option value="en_US" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'en_US', 'en_US'); ?>>English (US)</option>
                            <option value="sv_SE" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'sv_SE'); ?>>Swedish (sv_SE)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" name="save_business_settings" id="mobooking-save-biz-settings-btn"
                        class="px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">
                    Save Business Settings
                </button>
            </div>
        </form>
    </div>
</div>
