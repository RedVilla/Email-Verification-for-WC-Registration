<?php
/**
 * Customizer Setup and Custom Controls
 *
 */

/**
 * Adds the individual sections, settings, and controls to the theme customizer
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class evwfr_Initialise_Customizer_Settings {
	// Get our default values	
	private static $order_ids  = null;
	public $defaults;
	public function __construct() {
		// Get our Customizer defaults
		$this->defaults = $this->evwfr_generate_defaults();		
		
		// Register our sample default controls
		add_action( 'customize_register', array( $this, 'evwfr_register_sample_default_controls' ) );
		
		// Only proceed if this is own request.				
		if ( ! self::is_own_customizer_request() && ! self::is_own_preview_request()) {
			return;
		}						
		
		// Register our sections
		add_action( 'customize_register', array( wc_evwfr_customizer(), 'evwfr_add_customizer_sections' ) );	
		
		// Remove unrelated components.
		add_filter( 'customize_loaded_components', array( wc_evwfr_customizer(), 'remove_unrelated_components' ), 99, 2 );

		// Remove unrelated sections.
		add_filter( 'customize_section_active', array( wc_evwfr_customizer(), 'remove_unrelated_sections' ), 10, 2 );	
		
		// Unhook divi front end.
		add_action( 'woomail_footer', array( wc_evwfr_customizer(), 'unhook_divi' ), 10 );

		// Unhook Flatsome js
		add_action( 'customize_preview_init', array( wc_evwfr_customizer(), 'unhook_flatsome' ), 50  );	

		add_filter( 'customize_controls_enqueue_scripts', array( wc_evwfr_customizer(), 'enqueue_customizer_scripts' ) );		
		
		add_action( 'parse_request', array( $this, 'set_up_preview' ) );

		add_action( 'customize_preview_init', array( $this, 'enqueue_preview_scripts' ) );	
	}

	/**
	 * Add css and js for preview
	*/	
	public function enqueue_preview_scripts() {		 
		 wp_enqueue_style('evwfr-preview-styles', woo_customer_email_verification()->plugin_dir_url() . 'assets/css/preview-styles.css', array(), woo_customer_email_verification()->version  );		 
	}
	
	/**
	 * Checks to see if we are opening our custom customizer preview
	 *	 
	 * @return bool
	 */
	public static function is_own_preview_request() {

  		// Check if the 'evwfr-email-preview' key exists in the request data
  		if ( ! isset( $_REQUEST['evwfr-email-preview'] ) ) {
    			return false;
  		}

  		// Check if the value is '1'
  		if ( '1' !== $_REQUEST['evwfr-email-preview'] ) {
    			return false;
  		}

  		// Generate a unique nonce with the action 'evwfr_email_preview'
  		$nonce = wp_create_nonce( 'evwfr_email_preview' );

  		// Check if the 'evwfr-email-preview' nonce from request data is valid 
  		return wp_verify_nonce( $_REQUEST['evwfr-email-preview'], 'evwfr_email_preview' );
	}
	
	/**
 	* Checks to see if we are opening our custom customizer controls
 	*  
 	* @return bool
 	*/
	public static function is_own_customizer_request() {
  		// Check for presence of both section and security nonce
  		if ( ! isset( $_REQUEST['section'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'evwfr_customizer_nonce' ) ) {
    			return false;
  		}

  		return 'evwfr_main_controls_section' === $_REQUEST['section'];
	}

	
	/**
	 * Get Customizer URL	 
	 */
	public static function get_customizer_url( $section ) {	
		
		$customizer_url = add_query_arg( array(
			'evwfr-customizer' => '1',
			'section' => $section,
			'url'     => urlencode( add_query_arg( array( 'evwfr-email-preview' => '1' ), home_url( '/' ) ) ),
		), admin_url( 'customize.php' ) );		

		return $customizer_url;
	}
	
	/**
	 * Code for initialize default value for customizer
	*/	
	public function evwfr_generate_defaults() {
		$customizer_defaults = array(
			'evwfr_verification_email_heading' => __( 'Please Verify Your Email Address', 'customer-email-verification-for-woocommerce' ),			
			'evwfr_verification_email_subject' =>  __( 'Please Verify Your Email Address on {site_title}', 'customer-email-verification-for-woocommerce' ),		
			'evwfr_verification_email_body' => __( 'To finish signing up, please confirm your email address. This ensures we have the right email in case we need to contact you.<p>Your verification code: {evwfr_user_verification_pin}</p><p>Or, verify your account clicking on the button below:', 'customer-email-verification-for-woocommerce' ),			
		);

		return $customizer_defaults;
	}		

	/**
	 * Register our sample default controls
	 */
	public function evwfr_register_sample_default_controls( $wp_customize ) {		
		/**
		* Load all our Customizer Custom Controls
		*/
		require_once trailingslashit( dirname(__FILE__) ) . 'custom-controls.php';		
				

		// Email Subject	
		$wp_customize->add_setting( 'evwfr_verification_email_subject',
			array(
				'default' => $this->defaults['evwfr_verification_email_subject'],
				'transport' => 'refresh',
				'type' => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'evwfr_verification_email_subject',
			array(
				'label' => __( 'Subject', 'woocommerce' ),
				'description' => '',
				'section' => 'evwfr_controls_section',
				'type' => 'text',
				'priority' => 1,
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => $this->defaults['evwfr_verification_email_subject'],
				),
			)
		);
		
		// Email Heading	
		$wp_customize->add_setting( 'evwfr_verification_email_heading',
			array(
				'default' => $this->defaults['evwfr_verification_email_heading'],
				'transport' => 'refresh',
				'type' => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'evwfr_verification_email_heading',
			array(
				'label' => __( 'Email Heading', 'customer-email-verification-for-woocommerce' ),
				'description' => '',
				'section' => 'evwfr_controls_section',
				'type' => 'text',
				'priority' => 6,
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => $this->defaults['evwfr_verification_email_heading'],
				),
			)
		);

		// Email content	
		$wp_customize->add_setting( 'evwfr_verification_email_body',
			array(
				'default' => $this->defaults['evwfr_verification_email_body'],
				'transport' => 'refresh',
				'type' => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'evwfr_verification_email_body',
			array(
				'label' => __( 'Verification Message', 'customer-email-verification-for-woocommerce' ),
				'description' => '',
				'section' => 'evwfr_controls_section',
				'type' => 'textarea',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => $this->defaults['evwfr_verification_email_body'],
				),
			)
		);

		$wp_customize->add_setting( 'evwfr_email_code_block',
			array(
				'default' => '',
				'transport' => 'postMessage',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( new WP_Customize_evwfr_codeinfoblock_Control( $wp_customize, 'evwfr_email_code_block',
			array(
				'label' => __( 'Available variables', 'customer-email-verification-for-woocommerce' ),
				'description' => '<code>{site_title}<br>{evwfr_user_verification_pin}<br>{evwfr_user_verification_link}</code>','You can use HTML tag : <strong>, <i>',
				'section' => 'evwfr_controls_section',				
			)
		) );			
	}	
	
	/**
	 * Set up preview
	 *
	 * @return void
	 */
	public function set_up_preview() {
		
		// Make sure this is own preview request.
		if ( ! self::is_own_preview_request() ) {
			return;
		}
		include woo_customer_email_verification()->get_plugin_path() . '/includes/customizer/preview/preview.php';		
		exit;			
	}	

	/**
	 * Code for preview of tracking info in email
	*/	
	public function preview_account_email() {
		
		// Load WooCommerce emails.
		$wc_emails      = WC_Emails::instance();
		$emails         = $wc_emails->get_emails();				
		WC_customer_email_verification_email_Common()->wuev_user_id  = 1;				
		
		$email_heading     = get_option( 'evwfr_verification_email_heading', $this->defaults['evwfr_verification_email_heading'] );
		$email_heading 	   = WC_customer_email_verification_email_Common()->maybe_parse_merge_tags( $email_heading );		
		
		$email_content = get_option( 'evwfr_verification_email_body', $this->defaults['evwfr_verification_email_body'] );
					
		$email = '';
				
		$mailer = WC()->mailer();			

		// create a new email
		$email = new WC_Email();
		$email->id = 'Customer_New_Account';	
		$email_content = WC_customer_email_verification_email_Common()->maybe_parse_merge_tags( $email_content );
		$email_content = apply_filters( 'evwfr_verification_email_content', $email_content );
		
		$email_content = wpautop( $email_content );
		$email_content = wp_kses_post( $email_content );	

			
		
		// wrap the content with the email template and then add styles
		$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $email_content ) ) );
		$message = apply_filters( 'wc_evwfr_decode_html_content', $message );		
		echo wp_kses_post( $message );
	}	
}
/**
 * Initialise our Customizer settings
 */

$evwfr_customizer_settings = new evwfr_Initialise_Customizer_Settings();

