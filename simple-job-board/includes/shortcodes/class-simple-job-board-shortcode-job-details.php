<?php
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Simple_Job_Board_Shortcode_job_details Details Page
 * 
 * This class lists the jobs on frontend for SJB detail widget
 * 
 * @link        https://wordpress.org/plugins/simple-job-board
 * @since       2.9.6
 * @since       2.10.0   Changed defined templates to do_actions.
 * @package     Simple_Job_Board
 * @author      PressTigers <support@presstigers.com>
 */


class Simple_Job_Board_Shortcode_job_details {

	public function __construct() {

        // Hook -> Add Job "Job details" widget
        add_shortcode('job_details', array($this, 'sjb_job_form_function'));
    }

	public function sjb_job_form_function($atts) {

		$atts = shortcode_atts([

			'show_job_features' => 'yes', 
			'show_job_meta' => 'yes',

		], $atts);

		
		do_action('sjb_enqueue_scripts');
		
		do_action('sjb_single_job_content_start');

		
		if(is_singular('jobpost')){

			if($atts['show_job_meta'] === 'yes') {
				do_action('sjb_single_job_listing_start'); 
			}else{
				remove_action('sjb_single_job_listing_start', 'sjb_job_listing_meta_display', 20);
			}

			if($atts['show_job_features'] === 'yes'){
				add_action('sjb_single_job_listing_end', 'sjb_job_listing_features', 20);
			}else{
				remove_action('sjb_single_job_listing_end', 'sjb_job_listing_features', 20);
			}
		}

		do_action('sjb_single_job_listing_end');
		
		do_action('sjb_single_job_content_end');

	}

}