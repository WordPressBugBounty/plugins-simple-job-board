<?php
/**
 * Template for displaying category filter dropdown
 *
 * Override this template by copying it to yourtheme/simple_job_board/v2/search/category-filter.php
 *
 * @author 	PressTigers
 * @package     Simple_Job_Board
 * @subpackage  Simple_Job_Board/templates/search
 * @version     1.0.0
 * @since       2.3.0   Added "sjb_category_filter_template" filter.
 * @since       2.4.0   Revised the whole HTML template
 * @since       2.13.9  Modify template to display related categories in dropdown instead of all categories when shortcode is used for job listing page
 */

ob_start();

// Check for setting page option and the term existence
if (sjb_is_category_filter()) {

    $selected_category = ( NULL != filter_input(INPUT_GET, 'selected_category') ) ? sanitize_text_field( filter_input( INPUT_GET, 'selected_category' ) ) : FALSE;
    $allowed_tags = sjb_get_allowed_html_tags();

    // Get 'category' attribute from shortcode
    $category_atts = isset($atts['category']) ? trim($atts['category']) : '';

    // Prepare category arguments
    $category_args = array(
        'show_option_none' => apply_filters('sjb_category_filter_title', esc_html__('Category', 'simple-job-board')),
        'orderby'          => 'NAME',
        'order'            => 'ASC',
        'hide_empty'       => 0,
        'echo'             => FALSE,
        'hierarchical'     => TRUE,
        'name'             => 'selected_category',
        'id'               => 'category',
        'class'            => 'form-control',
        'selected'         => $selected_category,
        'taxonomy'         => 'jobpost_category',
        'value_field'      => 'slug',
    );

    // Logic for category filter behavior
    if (empty($category_atts)) {
        // Show all categories (default behavior)
        $category_select = wp_dropdown_categories(apply_filters('sjb_category_filter_args', $category_args, $atts));

    } else {
        $category_list = array_map('trim', explode(',', $category_atts));

        if (count($category_list) === 1) {
            // Single category — do NOT show dropdown
            $category_select = '';
        } else {
            // Multiple categories — show only those in dropdown
            $category_args['include'] = array();

            foreach ($category_list as $cat_slug) {
                $term = get_term_by('slug', $cat_slug, 'jobpost_category');
                if ($term) {
                    $category_args['include'][] = $term->term_id;
                }
            }

            if (!empty($category_args['include'])) {
                $category_select = wp_dropdown_categories(apply_filters('sjb_category_filter_args', $category_args, $atts));
            } else {
                $category_select = '';
            }
        }
    }
    ?>

    <!-- Category Filter-->
    <?php if (!empty($category_select)) : ?>
        <div class="sjb-search-categories <?php echo apply_filters('sjb_category_filter_class', 'col-md-3 col-xs-12'); ?>">
            <div class="form-group">
                <?php echo wp_kses($category_select, $allowed_tags); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
}

$html_category_filter = ob_get_clean();


/**
 * Modify the Category Filter Template. 
 *                                       
 * @since   2.3.0
 * 
 * @param   html    $html_category_filter   Category Filter HTML.                   
 */
echo apply_filters( 'sjb_category_filter_template', $html_category_filter );