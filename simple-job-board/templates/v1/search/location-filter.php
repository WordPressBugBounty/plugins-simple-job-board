<?php
/**
 * Template for displaying job location filter
 *
 * Override this template by copying it to yourtheme/simple_job_board/v1/search/location-filter.php
 *
 * @author 	PressTigers
 * @package     Simple_Job_Board
 * @subpackage  Simple_Job_Board/templates/search
 * @version     1.0.0
 * @since       2.2.3
 * @since       2.3.0   Added "sjb_job_location_filter_template" filter.
 * @since       2.4.0   Revised whole HTML structure
 */
ob_start();

// Check For Settings Option and the Term Existance
if ( sjb_is_location_filter() ) {
    $allowed_tags = sjb_get_allowed_html_tags();
    $selected_location = function_exists('sjb_get_selected_filter_terms') ? sjb_get_selected_filter_terms('selected_location') : FALSE;
    if (!$selected_location && NULL != filter_input(INPUT_GET, 'selected_location')) {
        $selected_location = sanitize_text_field( filter_input( INPUT_GET, 'selected_location' ) );
    }

    /**
     * Creating list on non-empty job location
     * 
     * Job Location Selectbox
     */
    // Job Location Arguments
    $jobloc_args = array(
        'show_option_none' => apply_filters( 'sjb_location_filter_title', esc_html__('Location', 'simple-job-board') ),
        'orderby' => 'NAME',
        'order' => 'ASC',
        'hide_empty' => 1,
        'echo' => FALSE,
        'name' => 'selected_location',
        'id' => 'location',
        'class' => 'form-control',
        'selected' => is_array($selected_location) ? reset($selected_location) : $selected_location,
        'hierarchical' => TRUE,
        'taxonomy' => 'jobpost_location',
        'value_field' => 'slug',
    );

    // Display or retrieve the HTML dropdown list of job locations                  
    $jobloc_select = wp_dropdown_categories(apply_filters('sjb_job_location_filter_args', $jobloc_args, $atts));

    if (!empty($jobloc_select) && function_exists('sjb_is_multiselect_filter') && sjb_is_multiselect_filter()) {
        $jobloc_select = str_replace(
            array("name='selected_location'", 'name="selected_location"'),
            'name="selected_location[]" multiple="multiple"',
            $jobloc_select
        );
        $jobloc_select = str_replace(
            array("class='form-control'", 'class="form-control"'),
            'class="form-control sjb-multiselect-filter"',
            $jobloc_select
        );
        if (is_array($selected_location)) {
            foreach ($selected_location as $loc_slug) {
                if ('' !== $loc_slug && '-1' !== (string)$loc_slug) {
                    $jobloc_select = str_replace('value="' . esc_attr($loc_slug) . '"', 'value="' . esc_attr($loc_slug) . '" selected="selected"', $jobloc_select);
                }
            }
        }
    }
    ?>

    <!-- Job Location Filter-->
    <div class="sjb-search-location <?php echo apply_filters('sjb_job_location_filter_class', 'col-md-3 col-xs-12'); ?>">
        <div class="form-group">
            <?php
            if (NULL != $jobloc_select)
                echo wp_kses( $jobloc_select, $allowed_tags );
            ?>
        </div>
    </div>
    <?php
}

$html_location_filter = ob_get_clean();

/**
 * Modify the Job Location Filter Template. 
 *                                       
 * @since   2.3.0
 * 
 * @param   html    $html_location_filter   Job Location Filter HTML.                   
 */
echo apply_filters( 'sjb_job_location_filter_template', $html_location_filter );