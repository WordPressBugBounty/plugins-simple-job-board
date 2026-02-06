<?php
/**
 * Template for displaying search button
 *
 * Override this template by copying it to yourtheme/simple_job_board/v1/search/search-btn.php
 *
 * @author 	PressTigers
 * @package     Simple_Job_Board
 * @subpackage  Simple_Job_Board/templates/search
 * @version     1.0.0
 * @since       2.2.3
 * @since       2.3.0   Added "sjb_job_tag_template" filter.
 * @since       2.4.0   Revised whole HTML structure
 * @since       2.13.9  Modify to display only particular tags when shortcode display posts using the  particular tags.
 */

ob_start();

$_selected_tags = (!empty($_GET['selected_tag'])) ? sanitize_text_field($_GET['selected_tag']) : '';
$selected_tags  = explode(",", $_selected_tags);

// Determine shortcode tags (if any)
$shortcode_tags = (isset($atts['tag'])) ? trim($atts['tag']) : '';

if ($shortcode_tags === '') {
    // Case 1: No tags provided — work as normal, show all tags
    $terms = get_terms(array(
        'taxonomy' => 'jobpost_tag',
        'hide_empty' => true
    ));
} else {
    // Case 2: Tags provided — split and clean
    $tags_array = array_filter(array_map('trim', explode(',', $shortcode_tags)));

    if (count($tags_array) === 1) {
        // Case 3: Only one tag — do not show tags
        $terms = array(); 
    } else {
        // Case 2: Multiple tags — show only selected ones
        $terms = get_terms(array(
            'taxonomy' => 'jobpost_tag',
            'slug'     => $tags_array,
            'hide_empty' => true
        ));
    }
}

// Search Button 
if (!empty($terms)) {
    ?>
    <div class="col-md-12 sjb-filter-tags form-group">
        <div class="sjb-selected-tags">
            <p><?php echo __('Search by tag:', 'simple-job-board'); ?></p>
            <input type="hidden" name="selected_tag" id="selected_tag" value="<?php echo esc_attr($_selected_tags); ?>">
        </div>
        <div class="sjb-tag-listing">
            <?php
            foreach ($terms as $term) {
                $active_class = '';
                if (in_array($term->name, $selected_tags)) {
                    $active_class = 'tag-active';
                }
                ?>
                <a href="#" 
                   class="<?php echo apply_filters('sjb_tags_search_class', 'sjb-tags-search') . ' ' . esc_attr($active_class); ?>" 
                   data-value="<?php echo esc_attr($term->name); ?>">
                    <?php echo esc_attr($term->name); ?>
                </a>
                <?php
            }
            ?>    
        </div> 
    </div>
    <?php
}

$html_job_tag = ob_get_clean();

/**
 * Modify the Job Tag Template. 
 *                                       
 * @since   2.3.0
 * 
 * @param   html    $html_job_tag   Job Tag HTML.                   
 */
echo apply_filters('sjb_job_tag_template', $html_job_tag);

