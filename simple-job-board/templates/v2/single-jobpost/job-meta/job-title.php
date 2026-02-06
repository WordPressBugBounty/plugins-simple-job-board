<?php
/**
 * The template for displaying job title
 *
 * Override this template by copying it to yourtheme/simple_job_board/v2/single-jobpost/job-meta/job-title.php
 * 
 * @author      PressTigers
 * @package     Simple_Job_Board
 * @subpackage  Simple_Job_Board/templates/single-jobpost/job-meta
 * @version     1.0.0
 * @since       2.2.3
 * @since       2.3.0   Added "sjb_job_meta_job_title_template" filter.
 */
ob_start();

$post_id = get_the_ID();

// -----------------------------
// Detect Elementor-built post
// -----------------------------
$is_elementor_post = false;
if ( did_action('elementor/loaded') ) {
    $document = \Elementor\Plugin::$instance->documents->get($post_id);
    if ( $document && $document->is_built_with_elementor() ) {
        $is_elementor_post = true;
    }
} else {
    // Fallback if Elementor deactivated
    $edit_mode = get_post_meta($post_id, '_elementor_edit_mode', true);
    if ( !empty($edit_mode) ) {
        $is_elementor_post = true;
    }
}

// -----------------------------
// Detect WPBakery-built post
// -----------------------------
$is_wpbakery_post = false;
if ( metadata_exists('post', $post_id, '_wpb_vc_js_status') ) {
    $wpb_status = get_post_meta($post_id, '_wpb_vc_js_status', true);
    if ( !empty($wpb_status) && $wpb_status === 'true' ) {
        $is_wpbakery_post = true;
    }
}

// -----------------------------
// Plugin activation checks
// -----------------------------
$is_elementor_active        = is_plugin_active('elementor/elementor.php');
$is_elementor_addon_active  = is_plugin_active('sjb-add-on-elementor/sjb-add-on-elementor.php');
$is_wpbakery_active         = is_plugin_active('js_composer/js_composer.php');
$is_wpbakery_addon_active   = is_plugin_active('sjb-add-on-wpbakery/sjb-add-on-wpbakery.php');

// -----------------------------
// Layout & title options
// -----------------------------
$layout        = get_option('job_board_pages_layout');
$title_setting = get_option('job_post_title_settings');

// -----------------------------
// Determine if title should show
// -----------------------------
$should_show_title = false;

//  Case 1: Builder + Add-on active
if (
    ($is_elementor_active && $is_elementor_addon_active) ||
    ($is_wpbakery_active && $is_wpbakery_addon_active)
) {
    if (
        ($layout === 'theme-layout' && ($is_elementor_post || $is_wpbakery_post)) ||
        ($layout === 'sjb-layout' && ($is_elementor_post || $is_wpbakery_post)) ||
        ($layout === 'sjb-layout' && $title_setting === 'with-title')
    ) {
        $should_show_title = true;
    }
}
//  Case 2: Default/fallback layout (non-builder)
elseif ($layout === 'sjb-layout' || $layout === false) {
    $should_show_title = true;
}
// Case 3: Previously built with Elementor/WPBakery but add-on now inactive
elseif (
    ($is_elementor_post && !$is_elementor_addon_active) ||
    ($is_wpbakery_post && !$is_wpbakery_addon_active)
) {
    $should_show_title = true;
}
// Render title
if ($should_show_title) :
    ?>
    <!-- Start Job Title ================================================== -->
    <div class="sjb-job-detail">
        <div class="job-detail">
            <?php the_title('<h2><span class="job-title">', '</span></h2>'); ?>
        </div>
    </div>
    <!-- End Job Title ================================================== -->
<?php
endif;

$html_title = ob_get_clean();

/**
 * Modify the Job Title Template.
 *                                       
 * @since   2.3.0
 * 
 * @param   html    $html_title   Job Title HTML.                   
 */
echo apply_filters( 'sjb_job_meta_job_title_template', $html_title );