<?php
/**
 * Simple_Job_Board_Admin_Alerts class
 *
 * Add job board shortcode builder to TinyMCE. Define the shortcode button and
 * parameters in TinyMCE. Also creates shortcodes with given parameters.
 *
 * @link       https://wordpress.org/plugins/simple-job-board
 * @since      2.2.3
 * @since      2.8.0 - Made TinyMCE compatible with Gutenberg Classic Editor
 *
 * @package    Simple_Job_Board
 * @subpackage Simple_Job_Board/admin
 * @author     PressTigers <support@presstigers.com>
 */
class Simple_Job_Board_Admin_Alerts
{

    /**
     * Initilaize class.
     *
     * @since   2.2.3
     */
    public function __construct()
    {
        
        if ( ! file_exists( WP_PLUGIN_DIR . '/sjb-job-alert/sjb-job-alert.php' ) ){
            add_action('admin_enqueue_scripts', array( $this, 'load_admin_styles_on_sjb_pages' ));
            add_action( 'all_admin_notices', array( $this, 'render_admin_alerts_banner' ) );
        }
        add_action('admin_init', array( $this, 'check_addon_versions_and_display_notifications' ));

    }

    /**
     * Add jobpost meta boxes.
     *
     * @since 2.1.0
     */
    public function render_admin_alerts_banner() {

        if (isset($_COOKIE['alertCloseCount'])) {
            $alert_clicked_count = intval($_COOKIE['alertCloseCount']); // Safely convert to integer
        } else {
            $alert_clicked_count = 1; // Default to 0 if cookie is not set
        }
            if ($alert_clicked_count < 3 ) {

                $screen = get_current_screen();

            
                // Check if the post_type is 'jobpost'
                if ( isset($screen->post_type) && $screen->post_type === 'jobpost' || $screen->post_type === 'jobpost_applicants' ) {
                    ?>
                        <div class="sjb-alert-banner">
                            <div class="banner-content">
                                <div class="banner-col-1">
                                    <a href="https://market.presstigers.com/">
                                        <img src="<?php echo esc_url( plugin_dir_url(dirname(__FILE__)) . 'admin/images/simple-job-board-logo.png' ); ?>" alt="<?php esc_attr_e('Job Alerts Icon', 'simple-job-board'); ?>" class="banner-icon sjb-logo">
                                    </a>
                                    <img src="<?php echo esc_url( plugin_dir_url(dirname(__FILE__)) . 'admin/images/alert-avatar.png' ); ?>" alt="<?php esc_attr_e('Job Alerts Icon', 'simple-job-board'); ?>" class="banner-icon alertavatar">
                                    <button class="alert-close-btn">X</button>
                                </div>
                                <div class="banner-col-2">
                                    <h2 class="banner-title"><?php esc_html_e('BOOST YOUR RECRUITMENT WITH JOB ALERTS!', 'simple-job-board'); ?></h2>
                                    <p class="banner-text">
                                        <?php
                                        echo sprintf(
                                            esc_html__('Simple Job Board has launched a new add-on %s with awesome features to let the job seekers subscribe for your new job openings.', 'simple-job-board'),
                                            '<strong>' . esc_html__('Job Alerts', 'simple-job-board') . '</strong>'
                                        );
                                        ?>
                                    </p>
                                    <a href="https://market.presstigers.com/product/job-alerts-add-on/" target="_blank" class="banner-link"><?php esc_html_e('Get the Add-on', 'simple-job-board'); ?></a>
                                </div>
                                <div class="banner-col-3">
                                    <ul class="banner-features">
                                        <li><?php esc_html_e('Create and manage job alerts as per preferences of job seeker.', 'simple-job-board'); ?></li>
                                        <li><?php esc_html_e('Set daily, weekly, fortnightly, or monthly alerts.', 'simple-job-board'); ?></li>
                                        <li><?php esc_html_e('Get job alerts through email, and share with others.', 'simple-job-board'); ?></li>
                                    </ul>
                                </div>
                                <div class="banner-col-4">
                                    <button class="alert-close-btn">X</button>
                                    <a href="https://market.presstigers.com/">
                                        <img src="<?php echo esc_url( plugin_dir_url(dirname(__FILE__)) . 'admin/images/presstigers-logo.png' ); ?>" alt="<?php esc_attr_e('PressTigers Logo', 'simple-job-board'); ?>" class="banner-logo">
                                    </a>
                                </div>
                            </div>
                        </div>
                    <script type="text/javascript">
                        document.addEventListener('DOMContentLoaded', function() {
                            var closeBtn = document.querySelector('.alert-close-btn');
                            var alertBanner = document.querySelector('.sjb-alert-banner');

                            // Function to set cookies
                            function setCookie(name, value, days) {
                                var expires = "";
                                if (days) {
                                    var date = new Date();
                                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                                    expires = "; expires=" + date.toUTCString();
                                }
                                document.cookie = name + "=" + (value || "") + expires + "; path=/";
                            }

                            // Function to get cookies
                            function getCookie(name) {
                                var nameEQ = name + "=";
                                var ca = document.cookie.split(';');
                                for (var i = 0; i < ca.length; i++) {
                                    var c = ca[i];
                                    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                                    if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
                                }
                                return null;
                            }

                            // Initialize click count from cookie or start at 0
                            var clickCount = parseInt(getCookie('alertCloseCount')) || 0;

                            // Hide the banner if the click count is greater than 4
                            if (clickCount > 4) {
                                alertBanner.style.display = 'none';
                            }

                            // Increment click count and update cookie on close button click
                            closeBtn.addEventListener('click', function() {
                                clickCount++;
                                setCookie('alertCloseCount', clickCount, 30); // Cookie lasts 30 days

                                if (clickCount > 4) {
                                    alertBanner.style.display = 'none';
                                } else {
                                    alertBanner.style.display = 'none'; // Hide the banner on each click
                                }
                            });
                        });
                    </script>
                    <?php
                }
            }
    }


                    
   public function load_admin_styles_on_sjb_pages($hook) {
        // Get the current screen
        $screen = get_current_screen();
    
        // Check if the current screen is a taxonomy page
        if ( isset($screen->post_type) && $screen->post_type === 'jobpost' || $screen->post_type === 'jobpost_applicants' ) {
            // Enqueue your CSS file for taxonomy pages
            wp_enqueue_style('sjb-admin-alert-css', plugin_dir_url(dirname(__FILE__)) . 'admin/css/sjb-admin-alert-style.css');
        }
    }

    public function check_addon_versions_and_display_notifications() {
        // Your WooCommerce API credentials
        $consumer_key = 'ck_0fbca498c2fe9491ce5cfcdbc2a03d2b396153c7';  // Replace with your WooCommerce consumer key
        $consumer_secret = 'cs_66dafc2cb72361dd98cf37cb08ec5508eb49cc97';  // Replace with your WooCommerce consumer secret

       // Base URL for the WooCommerce API
        $api_url = 'https://market.presstigers.com/wc-api/v3/products';

        // Request to get all products without pagination
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
            ),
            'sslverify' => true, // Disable SSL verification (temporary solution)
            'timeout' => 30,
            'body' => array(
                'filter[limit]' => -1, // Get all products without pagination
                'type' => 'variable'   // You can filter by product type if needed
            )
        ));

        if (is_wp_error($response)) {
            return; // Stop if there is an error with the request
        }

        // Decode the response body
        $body = wp_remote_retrieve_body($response);
        $products = json_decode($body, true); // Decode the JSON response

        // Prepare the $addons array from the API response
        $addons = array();
        if (!empty($products['products'])) {
            foreach ($products['products'] as $product) {
                // Ensure that both add_on_version, plugin_main_slug, and permalink are set and non-empty
                $add_on_version = isset($product['add_on_version']) ? $product['add_on_version'] : null;
                $plugin_main_slug = isset($product['plugin_main_slug']) ? $product['plugin_main_slug'] : null;
                $permalink = isset($product['permalink']) ? $product['permalink'] : null;

                if (!empty($add_on_version) && !empty($plugin_main_slug) && !empty($permalink)) {
                    // Store an array with version and permalink
                    $addons[$plugin_main_slug] = array(
                        'version' => $add_on_version,
                        'permalink' => $permalink
                    );
                }
            }
        }

        // Check if the base (free) plugin is installed (instead of active)
        if (file_exists(WP_PLUGIN_DIR . '/simple-job-board/simple-job-board.php')) {
            foreach ($addons as $addon_slug => $addon_info) {
                // Check if the add-on plugin is installed (file exists)
                if (file_exists(WP_PLUGIN_DIR . '/' . $addon_slug)) {
                    // Get the add-on data
                    $addon_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $addon_slug);

                    // Compare installed version with the latest version
                    if (version_compare($addon_data['Version'], $addon_info['version'], '<')) {
                        // Display an admin notice if the version is outdated
                        add_action('admin_notices', function() use ($addon_data, $addon_info) {
                            ?>
                            <div class="notice notice-warning is-dismissible">
                                    <p><?php 
                                        echo sprintf(
                                            /* translators: 1: Version number, 2: Permalink URL, 3: Add-on name, 4: Account URL */
                                            wp_kses(
                                                __('There is a new version (%1$s) available for <a href="%2$s"><strong>%3$s</strong></a>. Please <a href="%4$s">download</a> and install the latest version!', 'simple-job-board'),
                                                array(
                                                    'a' => array(
                                                        'href' => array(),
                                                        'strong' => array(),
                                                    )
                                                )
                                            ),
                                            esc_html($addon_info['version']),
                                            esc_url($addon_info['permalink']),
                                            esc_html($addon_data['Name']),
                                            esc_url('https://market.presstigers.com/my-account/')
                                        );
                                    ?></p>
                                </div>
                            <?php
                        });
                    }
                }
            }
        }


}
    
       

}

new Simple_Job_Board_Admin_Alerts();
