<?php
/**
 * Template for displaying job type filter
 *
 * Override this template by copying it to yourtheme/simple_job_board/v2/search/type-filter.php
 *
 * @author 	PressTigers
 * @package     Simple_Job_Board
 * @subpackage  Simple_Job_Board/templates/search
 * @version     1.0.0
 * @since       2.2.3
 * @since       2.3.0   Added "sjb_job_type_filter_template" filter.
 * @since       2.4.0   Revised whole HTML structure
 */
ob_start();

// Check For Settings Option and the Term Existance
if (sjb_is_type_filter()) {    
    $selected_jobtype = function_exists('sjb_get_selected_filter_terms') ? sjb_get_selected_filter_terms('selected_jobtype') : FALSE;
    if (!$selected_jobtype && NULL != filter_input(INPUT_GET, 'selected_jobtype')) {
        $selected_jobtype = sanitize_text_field( filter_input( INPUT_GET, 'selected_jobtype' ) );
    }
    $allowed_tags = sjb_get_allowed_html_tags();
    /**
     * Creating list on non-empty job type
     * 
     * Job Type Selectbox
     */
    // Job Type Arguments
    $jobtype_args = array(
        'show_option_none'  => apply_filters( 'sjb_type_filter_title', esc_html__('Job Type', 'simple-job-board') ),
        'orderby'           => 'NAME',
        'order'             => 'ASC',
        'hide_empty'        => 1,
        'echo'              => FALSE,
        'name'              => 'selected_jobtype',
        'id'                => 'jobtype',
        'class'             => 'form-control',
        'selected'          => is_array($selected_jobtype) ? reset($selected_jobtype) : $selected_jobtype,
        'hierarchical'      => TRUE,
        'taxonomy'          => 'jobpost_job_type',
        'value_field'       => 'slug',
    );

    // Display or retrieve the HTML dropdown list of job type     
    $jobtype_select = wp_dropdown_categories(apply_filters('sjb_job_type_filter_args', $jobtype_args, $atts));

    if (!empty($jobtype_select) && function_exists('sjb_is_multiselect_filter') && sjb_is_multiselect_filter()) {
        $jobtype_select = str_replace(
            array("name='selected_jobtype'", 'name="selected_jobtype"'),
            'name="selected_jobtype[]" multiple="multiple"',
            $jobtype_select
        );
        $jobtype_select = str_replace(
            array("class='form-control'", 'class="form-control"'),
            'class="form-control sjb-multiselect-filter"',
            $jobtype_select
        );
        if (is_array($selected_jobtype)) {
            foreach ($selected_jobtype as $type_slug) {
                if ('' !== $type_slug && '-1' !== (string)$type_slug) {
                    $jobtype_select = str_replace('value="' . esc_attr($type_slug) . '"', 'value="' . esc_attr($type_slug) . '" selected="selected"', $jobtype_select);
                }
            }
        }
    }
    ?> 

    <!-- Job Type Filter -->
    <div class="sjb-search-job-type <?php echo apply_filters('sjb_job_type_filter_class', 'col-md-3 col-xs-12'); ?>">
        <div class="form-group">
            <?php
            if (NULL != $jobtype_select) {
                echo wp_kses( $jobtype_select, $allowed_tags);
            }
            ?>
        </div>
    </div>
    <?php
}

$html_type_filter = ob_get_clean();

/**
 * Modify the Job Type Filter Template. 
 *                                       
 * @since   2.3.0
 * 
 * @param   html    $html_type_filter   Job Type Filter HTML.                   
 */
echo apply_filters( 'sjb_job_type_filter_template', $html_type_filter );