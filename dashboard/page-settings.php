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
<!-- ======== main-content start ======== -->
<section class="p-4 md:p-6 2xl:p-10">
    <!-- Breadcrumb Start -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-semibold text-black dark:text-white">
            Settings
        </h2>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Dashboard /</a></li>
                <li class="text-primary">Settings</li>
            </ol>
        </nav>
    </div>
    <!-- Breadcrumb End -->

    <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
        <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
            <h3 class="font-medium text-black dark:text-white">
                Business Settings
            </h3>
            <p class="text-sm">Manage your core business information, email configurations, and operating hours.</p>
        </div>
        <form id="mobooking-business-settings-form" method="post" class="p-6.5">
            <?php wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field'); ?>
            <div id="mobooking-settings-feedback" class="mb-4"></div>

            <!-- Tabs -->
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="settingsTabs" role="tablist">
                    <li class="mr-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 rounded-t-lg" id="bizinfo-tab" data-tabs-target="#bizinfo" type="button" role="tab" aria-controls="bizinfo" aria-selected="true">Business Information</button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="emailconf-tab" data-tabs-target="#emailconf" type="button" role="tab" aria-controls="emailconf" aria-selected="false">Email Configuration</button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="bizhours-tab" data-tabs-target="#bizhours" type="button" role="tab" aria-controls="bizhours" aria-selected="false">Business Hours</button>
                    </li>
                </ul>
            </div>

            <div id="settingsTabContent">
                <!-- Business Information Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="bizinfo" role="tabpanel" aria-labelledby="bizinfo-tab">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                         <div class="flex flex-col gap-5.5">
                            <div>
                                <label class="mb-3 block text-black dark:text-white">Business Name</label>
                                <input type="text" name="biz_name" placeholder="Your Business Name" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_name'); ?>" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                            </div>
                             <div>
                                <label class="mb-3 block text-black dark:text-white">Public Business Email</label>
                                <input type="email" name="biz_email" placeholder="contact@yourbusiness.com" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_email'); ?>" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                            </div>
                            <div>
                                <label class="mb-3 block text-black dark:text-white">Business Phone</label>
                                <input type="tel" name="biz_phone" placeholder="+1 123 456 7890" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_phone'); ?>" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                            </div>
                             <div>
                                <label class="mb-3 block text-black dark:text-white">Business Logo URL</label>
                                <input type="url" name="biz_logo_url" placeholder="https://example.com/logo.png" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'biz_logo_url'); ?>" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                            </div>
                        </div>
                        <div class="flex flex-col gap-5.5">
                            <div>
                                <label class="mb-3 block text-black dark:text-white">Business Address</label>
                                <textarea name="biz_address" rows="4" placeholder="123 Business St, City, Country" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary"><?php echo mobooking_get_biz_setting_textarea($biz_settings, 'biz_address'); ?></textarea>
                            </div>
                             <div>
                                <label class="mb-3 block text-black dark:text-white">Currency</label>
                                 <select name="biz_currency_code" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                                    <option value="USD" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'USD', 'USD'); ?>>USD (US Dollar)</option>
                                    <option value="EUR" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'EUR'); ?>>EUR (Euro)</option>
                                    <option value="SEK" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'SEK'); ?>>SEK (Swedish Krona)</option>
                                    <option value="NOK" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'NOK'); ?>>NOK (Norwegian Krone)</option>
                                    <option value="DKK" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'DKK'); ?>>DKK (Danish Krone)</option>
                                    <option value="" disabled>---</option>
                                    <option value="GBP" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'GBP'); ?>>GBP (British Pound)</option>
                                    <option value="CAD" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'CAD'); ?>>CAD (Canadian Dollar)</option>
                                    <option value="AUD" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'AUD'); ?>>AUD (Australian Dollar)</option>
                                    <option value="JPY" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_currency_code', 'JPY'); ?>>JPY (Japanese Yen)</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-3 block text-black dark:text-white">Language</label>
                                <select name="biz_user_language" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                                    <option value="en_US" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'en_US', 'en_US'); ?>>English (US)</option>
                                    <option value="sv_SE" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'sv_SE'); ?>>Swedish (sv_SE)</option>
                                    <option value="nb_NO" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'nb_NO'); ?>>Norwegian (nb_NO)</option>
                                    <option value="fi_FI" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'fi_FI'); ?>>Finnish (fi_FI)</option>
                                    <option value="da_DK" <?php echo mobooking_select_biz_setting_value($biz_settings, 'biz_user_language', 'da_DK'); ?>>Danish (da_DK)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Configuration Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="emailconf" role="tabpanel" aria-labelledby="emailconf-tab">
                    <div class="flex flex-col gap-6">
                        <div>
                            <h4 class="text-lg font-semibold text-black dark:text-white mb-4">Sender Settings</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="mb-3 block text-black dark:text-white">Email "From" Name</label>
                                    <input type="text" name="email_from_name" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'email_from_name'); ?>" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                                </div>
                                <div>
                                    <label class="mb-3 block text-black dark:text-white">Email "From" Address</label>
                                    <input type="email" name="email_from_address" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'email_from_address'); ?>" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-lg font-semibold text-black dark:text-white mb-4">Customer Booking Confirmation Email</h4>
                             <div class="flex flex-col gap-5.5">
                                <div>
                                    <label class="mb-3 block text-black dark:text-white">Subject</label>
                                    <input type="text" name="email_booking_conf_subj_customer" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'email_booking_conf_subj_customer'); ?>" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                                </div>
                                <div>
                                    <label class="mb-3 block text-black dark:text-white">Body</label>
                                    <textarea name="email_booking_conf_body_customer" rows="8" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary"><?php echo mobooking_get_biz_setting_textarea($biz_settings, 'email_booking_conf_body_customer'); ?></textarea>
                                    <p class="text-sm mt-2">Placeholders: {{customer_name}}, {{business_name}}, etc.</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-lg font-semibold text-black dark:text-white mb-4">Admin New Booking Notification Email</h4>
                            <div class="flex flex-col gap-5.5">
                                <div>
                                    <label class="mb-3 block text-black dark:text-white">Subject</label>
                                    <input type="text" name="email_booking_conf_subj_admin" value="<?php echo mobooking_get_biz_setting_value($biz_settings, 'email_booking_conf_subj_admin'); ?>" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
                                </div>
                                <div>
                                    <label class="mb-3 block text-black dark:text-white">Body</label>
                                    <textarea name="email_booking_conf_body_admin" rows="8" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary"><?php echo mobooking_get_biz_setting_textarea($biz_settings, 'email_booking_conf_body_admin'); ?></textarea>
                                    <p class="text-sm mt-2">Placeholders: {{customer_name}}, {{admin_booking_link}}, etc.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Hours Tab -->
                <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="bizhours" role="tabpanel" aria-labelledby="bizhours-tab">
                    <div>
                        <label class="mb-3 block text-black dark:text-white">Operating Hours (JSON format)</label>
                        <textarea name="biz_hours_json" id="biz_hours_json" class="w-full rounded-lg border-[1.5px] border-stroke bg-transparent py-3 px-5 font-mono text-sm outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary" rows="14"><?php echo mobooking_get_biz_setting_textarea($biz_settings, 'biz_hours_json', '{}'); ?></textarea>
                        <p class="text-sm mt-2">
                            Define your weekly business hours using JSON. Use 24-hour format for times (HH:MM).<br>
                            Example: <code>{"monday": {"open": "09:00", "close": "17:00", "is_closed": false}, ...}</code>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" name="save_business_settings" id="mobooking-save-biz-settings-btn" class="flex justify-center rounded bg-primary py-2 px-6 font-medium text-gray hover:bg-opacity-90">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = new Flowbite.Tabs(document.getElementById('settingsTabs'), {
        defaultTabId: 'bizinfo',
        activeClasses: 'text-primary border-primary dark:text-primary dark:border-primary',
        inactiveClasses: 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300',
        onShow: (tab) => {
            // Your on show callback
        }
    });
});
</script>
<!-- ======== main-content end ======== -->
