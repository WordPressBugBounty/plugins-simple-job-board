<?php if (!defined('ABSPATH')) { exit; } // Exit if accessed directly
/**
 * Simple_Job_Board_Ajax class
 *
 * This file includes the ajax call for:
 * 
 *  - Uploading resume validation
 *  - Storing applicant data on job submit.
 * 
 * @link        https://wordpress.org/plugins/simple-job-board
 * @since       2.1.0
 * @since       2.2.3   Code Optimization
 * @since       2.4.0   Revised the Nonce Field Checks & Revised the Input/Output Sanitization & Escaping
 * @since       2.5.0   Added "new" status for job application.
 *
 * @package     Simple_Job_Board
 * @subpackage  Simple_Job_Board/includes
 * @author      PressTigers <support@presstigers.com>
 */
class Simple_Job_Board_Ajax {
    
    /**
     * Base directory of uploaded resume.
     *
     * @since    2.2.3
     * @access   private
     * @var      Simple_Job_Board_Ajax    $upload_basedir   Store the base directory of uploaded resume.
     */
    private $upload_basedir;
    
    /**
     * Base url of uploaded resume.
     *
     * @since    2.2.3
     * @access   private
     * @var      Simple_Job_Board_Ajax    $upload_baseurl   Store the base url of uploaded resume.
     */
    private $upload_baseurl;
    
    /**
     * Flag to indicate the error while uploading file
     *
     * @since    2.2.3
     * @access   private
     * @var      Simple_Job_Board_Ajax     $upload_file_error_indicator    Store error indicator during file upload.
     */
    private $upload_file_error_indicator;
    
    /**
     * Uploaded file error message
     *
     * @since    2.2.3
     * @access   private
     * @var      Simple_Job_Board_Ajax    $upload_file_error    Store error message of file upload.
     */
    private $upload_file_error = array();
    
    /**
     * The name of uploaded file
     *
     * @since    2.2.3
     * @access   private
     * @var      Simple_Job_Board_Ajax    $upload_file_name Store the name of uploaded file.
     */
    private $upload_file_name = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        
        $this->upload_file_error_indicator = 0; 
        
        // Hook - Entertain Applicant Request From Job Apply Form
        add_action( 'wp_ajax_nopriv_process_applicant_form', array( $this, 'process_applicant_form' ) );
        add_action( 'wp_ajax_process_applicant_form', array($this, 'process_applicant_form') );

	// Hook - Entertain Applicant Request From Job Apply Form
        add_action( 'wp_ajax_nopriv_fetch_quick_job', array($this, 'fetch_quick_job') );
        add_action( 'wp_ajax_fetch_quick_job', array($this, 'fetch_quick_job') );
        
        // Hook - Uploaded Resume Validation
        add_action( 'sjb_uploaded_resume_validation', array($this, 'uploaded_resume_validation') );
    }

    /**
     * Entertain Applicant Request From Job Apply Form
     *
     * @since   2.9.5       changes add_post_meta to update_post_meta
     * @since   2.10.0      Improved file rename mechanism.
     * 
     * @access public
     * @return void
     */
    public function process_applicant_form() {

        $sjb_csrf_token = sanitize_key($_POST['sjb_csrf_token']);
        $sjb_guest_id = sanitize_key($_POST['sjb_guest_id']);
       
        $sjb_csrf_token_check =  get_option('sjb_csrf_token_disable');
        if($sjb_csrf_token_check !='yes'){
            if (!isset($sjb_csrf_token) || !sjb_verify_csrf_token($sjb_csrf_token, $sjb_guest_id)) {
                wp_die(__('Invalid or expired CSRF token. Please refresh the page and try again.', 'simple-job-board'));
            }
        }
         
        check_ajax_referer( 'jobpost_security_nonce', 'wp_nonce' );
        
        /**
         * Fires on job submission 
         * 
         * @since 2.2.3
         */
        do_action('sjb_uploaded_resume_validation');
        
        $allowed_tags = sjb_get_allowed_html_tags();

        if ( 1 == $this->upload_file_error_indicator ) {  
            $errors = '<div class="clearfix"></div><div class="alert alert-danger" role="alert">';
            
            foreach ( $this->upload_file_error as $error_value ) {
                $errors .= esc_html__($error_value, 'simple-job-board');
            }

            $response = json_encode(apply_filters( 'sjb_job_submission_validation_error' , array( 'success' => FALSE, 'error' => wp_kses( $errors, $allowed_tags ) ), $errors));
            header( "Content-Type: application/json" );
            echo apply_filters( 'sjb_job_submit_validation_errors', $response );
            die();
        }
        
        /**
         * Fires before inserting applicant's post.
         * 
         * @since 2.2.3
         */
        do_action( 'sjb_applicants_insert_post_before' );
        
        $parent_id = sanitize_text_field( $_POST['job_id'] );
        if(get_post_status($parent_id) == "publish"){
        $args = apply_filters('sjb_applicant_insert_post_args', array(
            'post_type'    => 'jobpost_applicants',
            'post_content' => '',
            'post_parent'  => intval( $parent_id ),
            'post_title'   => trim( strip_tags( get_the_title( $parent_id ) ) ),
            'post_status'  => 'publish',
        ));
        
        $pid = wp_insert_post($args);
    }
        
        /**
         * Fires before inserting applicant's post meta.
         * 
         * @since 2.2.3
         */
        do_action( 'sjb_applicants_insert_post_meta_start', $pid );        
        
        /**
         * Attachment's Manipulation:
         * 
         * - Prepend Applicant Id with File Name
         * - Rename File
         * - Store File's Meta in DB
         */
        if ( !empty( $_FILES ) ) {
            
            $upload_files_name = array();
            $count = 0;
            
            // Rename File
            foreach ( $_FILES as $key => $val ) {
                $random_hash = bin2hex(random_bytes(3));
                // Prepend Applicant Id with File Name
                $resume_name = $pid . '_' . $random_hash . '-' . sanitize_file_name( $this->upload_file_name[$count] );
                $resume_path = $this->upload_basedir . '/' . $resume_name;
                rename($this->upload_basedir. '/' . $this->upload_file_name[$count], $resume_path);
                $upload_files_name[] = ( '' != $val['name'] ) ?  $resume_name : '';                     
                $count++;
            } 
            
            // Keep Resume Meta for Backward Compatibility
            if( !empty( $_FILES['applicant_resume'] ) ) {                
                
                // Get Resume Attachment
                $resume_name = array_pop( $upload_files_name );
                
                if( $resume_name ) {
                    $resume_url = $this->upload_baseurl . '/' . $resume_name;
                    $resume_path = $this->upload_basedir . '/' . $resume_name;

                    /* Replace single backslash with double for DB Storage */
                    $resume_path = str_replace("\\", "\\\\", $resume_path);
                    update_post_meta($pid, 'resume', sanitize_text_field( $resume_url) );
                    update_post_meta($pid, 'resume_path', sanitize_text_field( $resume_path) );
                }
            }
            
            // Store Meta if Multiple Attachments
            if( !empty( $upload_files_name ) ) {
                
                // Replace single backslash with double for DB Storage
                $file_path = str_replace( "\\", "\\\\", (string) $this->upload_basedir );                
                
                $upload_files_name = array_map('sanitize_file_name', $upload_files_name);
                $file_meta = array (
                    'base_dir'  => sanitize_text_field( $file_path ),
                    'base_url'  => sanitize_text_field( $this->upload_baseurl ),
                    'file_name' => $upload_files_name,
                );
                
                // Store Attachments' Meta
                update_post_meta( $pid, 'attachments_meta', $file_meta );
            }            
        }
        
        // Add post meta with for application status
        update_post_meta( $pid, 'sjb_jobapp_status', apply_filters('sjb_jobapp_default_status', 'new' ));
        
        $POST_data = filter_input_array( INPUT_POST );
        
        // Save Applicant Details
        foreach ( $POST_data as $key => $val ) :
            
            if (substr($key, 0, 7) == 'jobapp_'):
                $allow_special_char = apply_filters("allow_special_char",false);
                if (is_array($val)) {
                    $string_val = implode(', ', $val);
                    if($allow_special_char == true){
                        $sanitized_string_val = sanitize_text_field(preg_replace('/[^\p{L}a-zA-Z0-9_@. -]/u', '', $string_val));
                    }else{
                        // $sanitized_string_val = sanitize_text_field(preg_replace('/[^a-zA-Z0-9_@. -]/s', '', $string_val));
                        $sanitized_string_val = sanitize_textarea_field($string_val);
                    }
                    $val = $sanitized_string_val;
                    
                    update_post_meta( $pid, $key, $val );
                }else{
                    if($allow_special_char == true){
                        update_post_meta( $pid, $key, sanitize_text_field( preg_replace('/[^\p{L}a-zA-Z0-9_@. -]/u','', $val) ) );
                    }else{
                       
                 
                        // update_post_meta( $pid, $key, sanitize_text_field( preg_replace('/[^a-zA-Z0-9_@. -]/s','', $val) ) );
                        update_post_meta($pid, $key, sanitize_textarea_field($val));

                    }
                  
                }
                
                
            endif;          
        endforeach;         

        /**
         * Fires after inserting applicant's post meta.
         *
         * @since 2.2.3
         */
        do_action( 'sjb_applicants_insert_post_meta_end', $pid );
        
        $success_alert = '<div class="clearfix"></div><div class="alert alert-success" role="alert">'. apply_filters( 'sjb_job_submission_alert', __('Your application has been received. We will get back to you soon.', 'simple-job-board') ) .'</div>';
    
        $fail_alert = '<div class="clearfix"></div><div class="alert alert-danger" role="alert">'. apply_filters( 'sjb_job_submission_fail_alert', http_response_code().': '.sjb_http_responses_code(http_response_code()) ) .'</div>';

        // Generate Response
        $response = ( $pid > 0 && (http_response_code() == 200 || http_response_code() == 201) ) ? json_encode( array( 'success' => TRUE, 'success_alert' => wp_kses( $success_alert, $allowed_tags ) ) ) : json_encode( array( 'success' => FALSE, 'error' => wp_kses( $fail_alert, $allowed_tags ) ) );
        
        // Output Response 
        header("Content-Type: application/json"); 
       
        echo $response; 

        // Admin Notification 
        if ( 'yes' === get_option( 'job_board_admin_notification' ) )
            Simple_Job_Board_Notifications::admin_notification( $pid );

        //  HR Notification
        if ( ('yes' === get_option( 'job_board_hr_notification' ) ) )
            Simple_Job_Board_Notifications::hr_notification( $pid );

        // Applicant Notification
        if ( 'yes' === get_option( 'job_board_applicant_notification' ) )
            Simple_Job_Board_Notifications::applicant_notification( $pid );
        
        /**
         * Fires after sending notifications on job submission page.
         *
         * @since 2.2.3
         */
        do_action( 'sjb_admin_notices_after', $pid );

        exit();
    }

    /**
     * Entertain Applicant Request From Job Apply Form
     *
     * @since   2.10.0
     * 
     * @access public
     * @return void
     */
    public function fetch_quick_job() {
        
        
        // Validate the job_id parameter
        $job_id = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;
    
        $args = array(
            'post_type'      => 'jobpost',
            'post_status'    => 'publish',
            'p'              => $job_id, 
            'posts_per_page' => 1, 
        );
        
        // Create a new instance of WP_Query with the arguments
        $query = new WP_Query($args);
        
        // Check if any posts are found
        if ($query->have_posts()) {
            // Loop through the posts
            while ($query->have_posts()) {
                $query->the_post();
                // Output the post content or perform other actions
                ?>
                <div class="popup-outer">
                    <?php
                    do_action('sjb_enqueue_scripts');
                    do_action('sjb_single_job_content_start');
                    get_option('job_board_pages_layout') == 'theme-layout' ? the_title('<h3><span class="job-title">', '</span></h3>') : null;
                    do_action('sjb_single_job_listing_start');
                    ?>
                    <div class="job-description" id="job-desc">
                        <?php
                        if(!is_elementor_widget_added_to_jobpost('job-details')) { 
                            echo apply_filters('popup_post_content', wp_kses_post(nl2br(get_the_content())));
                        }
                        ?>
                    </div>
                    <div class="clearfix"></div>
                    <?php
                    do_action('sjb_single_job_listing_end');
                    do_action('sjb_single_job_content_end');
                    ?>
                </div>
                <?php
            }
        } else {
            // No posts found, handle the case accordingly
            wp_die('Invalid Job ID', 'Invalid Job ID', array('response' => 400));
        }
    
        // Reset post data
        wp_reset_postdata();
        die();
    }


    /**
     * Uploaded Resume Validation
     * 
     * @since  2.2.3
     */
    public function uploaded_resume_validation() {
        
        /**
	 * Fires before uploaded resume validation.
	 *
	 * @since 2.2.3
	 */
        do_action('sjb_uploaded_resume_validation_before');
        
        if(isset($_FILES)) {
            foreach ( $_FILES as $file ) {
                
                // Check the File Existence
                if (strlen($file['name']) > 3) {
                    if (is_array( $file )) {

                        // WP Upload Directory 
                        $upload_dir = wp_upload_dir();

                        // Allowable File Size
                        $assignment_upload_size = get_option('job_file_max_size') ? esc_html(get_option('job_file_max_size')) : 2;

                        $time = (!empty($_SERVER['REQUEST_TIME'])) ? $_SERVER['REQUEST_TIME'] : (time() + (get_option('gmt_offset') * 3600)); // Fallback of now

                        $post_type = 'jobpost';

                        // Getting Current Date
                        $date = explode(" ", date('Y m d H i s', $time));
                        $timestamp = strtotime(date('Y m d H i s'));
                        
                        if ($post_type) {
                            $upload_dir = array(
                                'path'   => $upload_dir['basedir'] . '/' . $post_type . '/' . $date[0],
                                'url'    => $upload_dir['baseurl'] . '/' . $post_type . '/' . $date[0],
                                'subdir' => '',
                                'error'  => FALSE,
                            );
                        }

                        // Make Upload Directory 
                        if (!is_dir($upload_dir['path'])) {
                            if (wp_mkdir_p($upload_dir['path'])) {
                                // Path to the index.php file
                                $index_file = trailingslashit($upload_dir['path']) . '/index.php';
                        
                                // Check if index.php already exists
                                if (!file_exists($index_file)) {
                                    // Create a basic index.php file to prevent directory browsing
                                    file_put_contents($index_file, "<?php\n// Silence is golden.\n");
                                }
                            }
                        }

                        // Uploaded File Parameters 
                        $uploadfiles = array(
                            'name'     => $file['name'],
                            'type'     => $file['type'],
                            'tmp_name' => $file['tmp_name'],
                            'error'    => $file['error'],
                            'size'     => $file['size'],
                        );

                        // Look only for uploded files
                        if ( 0 == $uploadfiles['error'] ) {
                            $filetmp = $uploadfiles['tmp_name'];
                            $filesize = $uploadfiles['size'];
                            $max_upload_size = $assignment_upload_size * 1048576; // Megabytes to Bytes conversion

                            // Check Uploaded File Size
                            if ( $max_upload_size < $filesize ) {
                                $this->upload_file_error[] = esc_html__('Maximum upload File size allowed ' . $assignment_upload_size . 'MB.', 'simple-job-board');
                                $this->upload_file_error_indicator = 1;
                            }                    

                            /** Get file info
                             *  @fixme: wp checks the file extension
                             */
                            $filetype = wp_check_filetype(basename($uploadfiles['name']), NULL);
                            $file_ext = strtolower($filetype['ext']);
                            $filetitle = preg_replace('/\.[^.]+$/', '', basename($uploadfiles['name']));
                            $uploadfiles['name'] = $filetitle . $timestamp . '.' . $file_ext;

                            /**
                             * Check if the filename already exist in the directory & rename
                             * the file if necessary
                             */
                            $i = 0;

                            while ( file_exists( $upload_dir['path'] . '/' . $uploadfiles['name'] ) ) {
                                $uploadfiles['name'] = $filetitle . $timestamp . '_' . $i . '.' . $file_ext;
                                $i++;
                            }
                            $filedest = $upload_dir['path'] . '/' . $uploadfiles['name'];

                            // Check Write Permissions
                            if (!is_writeable($upload_dir['path'])) {
                                $this->upload_file_error[] = 'Unable to write to directory %s. Is this directory writable by the server?';
                                $this->upload_file_error_indicator = 1;
                            }

                            // Check valid file extensions
                            $allowed_file_exts = get_option('job_board_allowed_extensions');
                            $settings_file_exts = get_option('job_board_upload_file_ext');

                            // Selection of Setting Extension 
                            $file_extension = ( ( 'yes' === get_option('job_board_all_extensions_check') ) || ( NULL == $settings_file_exts ) ) ? $allowed_file_exts : $settings_file_exts;

                            if (!in_array($file_ext, $file_extension)) {
                                $this->upload_file_error[] = esc_html__( $uploadfiles['name'] . ' is not an allowed file type.', 'simple-job-board');
                                $this->upload_file_error_indicator = 1;
                            }

                            // Save Temporary File to Uploads Dir
                            if ($this->upload_file_error_indicator <> 1) {
                                if (!@move_uploaded_file($filetmp, $filedest)) {
                                    $this->upload_file_error[] = 'Error, the file $filetmp could not moved to : $filedest .';
                                    $this->upload_file_error_indicator = 1;
                                }

                                // Upload Base Path & URL
                                $this->upload_baseurl = $upload_dir['url'];
                                $this->upload_basedir = $upload_dir['path'];
                            }
                        }
                    }
                }
                
                $this->upload_file_name[] = $uploadfiles['name'];
            }
        }
        
        /**
	 * Fires after uploaded resume validation.
	 *
	 * @since 2.2.3
	 */
        do_action('sjb_uploaded_resume_validation_after');
    }
}

new Simple_Job_Board_Ajax();