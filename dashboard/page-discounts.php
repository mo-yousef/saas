<?php
/**
 * Dashboard Page: Discounts
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Instantiate Discounts class and fetch initial data
$discounts_manager = new \MoBooking\Classes\Discounts();
$user_id = get_current_user_id();

$default_args = [
    'limit' => 20, // Items per page
    'paged' => 1,  // Start from the first page
    'status' => null, // Get all statuses by default
    'orderby' => 'created_at',
    'order' => 'DESC',
];
$discounts_result = $discounts_manager->get_discount_codes_by_user($user_id, $default_args);

$discounts_list = $discounts_result['discounts'];
$total_discounts = $discounts_result['total_count'];
$per_page = $discounts_result['per_page'];
$current_page = $discounts_result['current_page'];
$total_pages = ceil($total_discounts / $per_page);

// Nonce for JS operations
wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field');

?>
<div>
    <div class="flex items-center justify-between">
        <h3 class="text-3xl font-medium text-gray-700 dark:text-gray-200">Discounts</h3>
        <button id="mobooking-add-new-discount-btn" class="px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">
            Add New Discount Code
        </button>
    </div>

    <div class="mt-8">
        <div class="flex flex-col">
            <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200 dark:border-gray-700">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Code</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Type</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Value</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Expiry</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Usage (Used/Limit)</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="mobooking-discounts-list" class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            <?php if ( ! empty( $discounts_list ) ) : ?>
                                <?php foreach ( $discounts_list as $discount ) : ?>
                                    <?php
                                    $type_display = $discount['type'] === 'percentage' ? __('Percentage', 'mobooking') : __('Fixed Amount', 'mobooking');
                                    $value_display = $discount['type'] === 'percentage' ? $discount['value'] . '%' : \MoBooking\Classes\Utils::format_currency($discount['value']);
                                    $expiry_date_display = !empty($discount['expiry_date']) ? date_i18n(get_option('date_format'), strtotime($discount['expiry_date'])) : __('Never', 'mobooking');
                                    $usage_limit_display = !empty($discount['usage_limit']) ? $discount['usage_limit'] : __('Unlimited', 'mobooking');
                                    $usage_display = esc_html($discount['times_used']) . ' / ' . esc_html($usage_limit_display);
                                    $status_display = $discount['status'] === 'active' ? __('Active', 'mobooking') : __('Inactive', 'mobooking');
                                    ?>
                                    <tr class="mobooking-discount-item" data-id="<?php echo esc_attr($discount['discount_id']); ?>">
                                        <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html($discount['code']); ?></td>
                                        <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html($type_display); ?></td>
                                        <td class="px-6 py-4 whitespace-no-wrap"><?php echo wp_kses_post($value_display); ?></td>
                                        <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html($expiry_date_display); ?></td>
                                        <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html($usage_display); ?></td>
                                        <td class="px-6 py-4 whitespace-no-wrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $discount['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo esc_html($status_display); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-no-wrap text-sm font-medium">
                                            <button class="text-indigo-600 hover:text-indigo-900 dark:hover:text-indigo-400 mobooking-edit-discount-btn" data-id="<?php echo esc_attr($discount['discount_id']); ?>">Edit</button>
                                            <button class="ml-2 text-red-600 hover:text-red-900 dark:hover:text-red-400 mobooking-delete-discount-btn" data-id="<?php echo esc_attr($discount['discount_id']); ?>">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No discount codes found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
