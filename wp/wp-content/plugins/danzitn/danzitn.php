<?php
/*
Plugin Name: DanziTN
Plugin URI: http://www.danzi.tn.it
Description: Integration of the website with the backend
Version: 1.0
Author: Andrea Danzi
Author URI: http://www.danzi.tn.it
Requires at least: 3.5
Tested up to: 3.6

	Copyright: © 2013 Andrea Danzi.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
// danzi.tn@20150525 added process_email for vtiger
// danzi.tn@20150603 spostato tutto in wp_insert_post per prendere le informazioni da Flamingo_Inbound_Message...in modo che si possa fare poi con un batch 
// include for zurmo integration
include_once( 'zurmolib/zurmolib.php' );
include_once( 'vtwsclib/vtwsclib.php' );

// global options for storing zurmo parameters. This is horrible, should be cleaned up at some point
global $wpdnz_options;
$wpdnz_options = array (
	'crm_url' => 'http://crm.hostname.it',
	'crm_username' => '',
	'crm_password' => '',
	'crm_enable'=>'false',
	'crm_enable_wp-members-profile_update'=>'false',
	'crm_enable_wp-members-login'=>'false',
	'crm_enable_wp-members-register'=>'false',
	'crm_enable_cf7-sumbit'=>'false',
	'crm_enable_download-monitor'=>'false',
	'crm_backend'=>'vtiger',
);

if (!function_exists('danzitn_plugin_activate')) :
function danzitn_plugin_activate() {

    // Activation code here...
}
endif;
register_activation_hook( __FILE__, 'danzitn_plugin_activate' );



if (!function_exists('danzitn_plugin_deactivate')) :
function danzitn_plugin_deactivate() {

    // Activation code here...
}
endif;
register_deactivation_hook( __FILE__, 'danzitn_plugin_deactivate' );



if (!function_exists('wp_danzitn_activate')) :
function wp_danzitn_activate() {

    // Activation code here...
}
endif;


// create required options
if (!function_exists('wp_danzitn_activate_options')) :
function wp_danzitn_activate_options() {
	
	global $wpdnz_options;
	
	// Create the required options...
	foreach ($wpdnz_options as $name => $val) {
		add_option($name,$val);
	}
	
}
endif;

// create required options
if (!function_exists('wp_danzitn_whitelist_options')) :
function wp_danzitn_whitelist_options($whitelist_options) {
	
	global $wpdnz_options;
	
	// Add our options to the array
	$whitelist_options['crm_app'] = array_keys($wpdnz_options);
	
	return $whitelist_options;
	
}
endif;


/**
 * This function outputs the plugin options page.
 */
if (!function_exists('wp_danzitn_options_page')) :
// Define the function
function wp_danzitn_options_page() {
	
	// Load the options
	global $wpdnz_options, $phpmailer;
	
	// Make sure the CURL is enabled
	if ( !function_exists('curl_version') ) {
		
		// do soemthing
	}

	// Send a test request if enabled
	if (isset($_POST['wpdnz_action']) && $_POST['wpdnz_action'] == __('Send Test', 'wp_danzitn') && isset($_POST['crmappmail'])) {
		
		// Set up the mail variables
		$event = array('type'=>'Web Site Contact Request',
		       'subject'=>'test admin option page');
		$crmappmail = $_POST['crmappmail'];
		$crm_password = get_option('crm_password');
		$crm_enable = get_option('crm_enable');
		$crm_username = get_option('crm_username');
		$crm_backend = get_option('crm_backend');
		$crm_url = get_option('crm_url');
		// performs login to crm app
		if($crm_backend == 'zurmo') $crmcl = new ZurmoClient($crm_url,$crm_username, $crm_password,"contact");
		if($crm_backend == 'vtiger') $crmcl = new VtigerClient($crm_url,$crm_username, $crm_password);
		$ret_login = $crmcl->login();
		if(!empty($ret_login) && $ret_login['status'] == 'SUCCESS') {
			$out_result = "Login succedeed on ".$crm_backend." for ".$crm_username." with crm_username=".$crm_username;
			if($crm_backend == 'zurmo') $found_res = find_zurmo_entity_by_email($crmcl, $crmappmail);
			if($crm_backend == 'vtiger') $found_res = find_vtiger_entity_by_email($crmcl, $crmappmail);
			if($found_res["status"] == 'SUCCESS') {
				$bFound = false;
				foreach($found_res["data"] as $module_key=>$entities) {
					$out_result .= "<br/><strong>for " .$module_key."</strong>";
					foreach($entities as $entity) {
						foreach($entity as $key=>$val) {
							$out_result .= "<br/>".$key." = " .$val;
						}
					}
					$bFound = true;
				}
				if( !$bFound  ) {
					$out_result .= " but nothing found!";
				}
			} else {
				$out_result .= " but searching for ".$crmappmail." failed, with message ".$found_res["errors"];
			}
		} else {
			$out_result = "Login Failed";
		}	
		// Output the response
		?>
<div id="message" class="updated fade"><p><strong><?php _e('Test Message Sent', 'wp_danzitn'); ?></strong></p>
<p><?php _e('The result was:', 'wp_danzitn'); ?></p>
<pre><?php echo $out_result; ?></pre>
</div>
		<?php
		
		// Disconnect
		

	}
	
	?>
<div class="wrap">
<h2><?php _e('DanziTN CRM Options', 'wp_danzitn'); ?></h2>
<form method="post" action="options.php">
<?php wp_nonce_field('crm_app-options'); ?>

<table class="optiontable form-table">
<tr valign="top">
<th scope="row"><label for="crm_url"><?php _e('CRM URL', 'wp_danzitn'); ?></label></th>
<td><input name="crm_url" type="text" id="crm_url" value="<?php print(get_option('crm_url')); ?>" size="40" class="regular-text" />
<span class="description"><?php _e('You can specify the url of your crm instalation.', 'wp_danzitn'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="$crm_backend"><?php _e('CRM Backend', 'wp_danzitn'); ?></label></th>
<td><input name="crm_backend" type="text" id="crm_backend" value="<?php print(get_option('crm_backend')); ?>" size="40" class="regular-text" />
<span class="description"><?php _e('You can specify your CRM platform, choose between vtiger an zurmo', 'wp_danzitn'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="$crm_username"><?php _e('CRM User Name', 'wp_danzitn'); ?></label></th>
<td><input name="crm_username" type="text" id="crm_username" value="<?php print(get_option('crm_username')); ?>" size="40" class="regular-text" />
<span class="description"><?php _e('You can specify the user name of your crm user.', 'wp_danzitn'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="crm_password"><?php _e('CRM User Password or Key', 'wp_danzitn'); ?></label></th>
<td><input name="crm_password" type="text" id="crm_password" value="<?php print(get_option('crm_password')); ?>" size="40" class="regular-text" />
<span class="description"><?php _e('You can specify the app password or key of your crm user.', 'wp_danzitn'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="crm_enable"><?php _e('CRM Enable Tracking', 'wp_danzitn'); ?></label></th>
<td><input name="crm_enable" type="checkbox" id="crm_enable" value="true" <?php checked('true', get_option('crm_enable')); ?> />
<span class="description"><?php _e('You can specify if enable event tracking.', 'wp_danzitn'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="crm_enable_wp-members-profile_update"><?php _e('CRM Profile Update Tracking', 'wp_danzitn'); ?></label></th>
<td><input name="crm_enable_wp-members-profile_update" type="checkbox" id="crm_enable_wp-members-profile_update" value="true" <?php checked('true', get_option('crm_enable_wp-members-profile_update')); ?> />
<span class="description"><?php _e('You can specify if enable event tracking.', 'wp_danzitn'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="crm_enable_wp-members-login"><?php _e('CRM user Login Tracking', 'wp_danzitn'); ?></label></th>
<td><input name="crm_enable_wp-members-login" type="checkbox" id="crm_enable_wp-members-login" value="true" <?php checked('true', get_option('crm_enable_wp-members-login')); ?> />
<span class="description"><?php _e('You can specify if enable event tracking.', 'wp_danzitn'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="crm_enable_wp-members-register"><?php _e('CRM User Registration Tracking', 'wp_danzitn'); ?></label></th>
<td><input name="crm_enable_wp-members-register" type="checkbox" id="crm_enable_wp-members-register" value="true" <?php checked('true', get_option('crm_enable_wp-members-register')); ?> />
<span class="description"><?php _e('You can specify if enable event tracking.', 'wp_danzitn'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="crm_enable_cf7-sumbit"><?php _e('CRM CF7 Tracking', 'wp_danzitn'); ?></label></th>
<td><input name="crm_enable_cf7-sumbit" type="checkbox" id="crm_enable_cf7-sumbit" value="true" <?php checked('true', get_option('crm_enable_cf7-sumbit')); ?> />
<span class="description"><?php _e('You can specify if enable event tracking.', 'wp_danzitn'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="crm_enable_download-monitor"><?php _e('CRM Download Tracking', 'wp_danzitn'); ?></label></th>
<td><input name="crm_enable_download-monitor" type="checkbox" id="crm_enable_download-monitor" value="true" <?php checked('true', get_option('crm_enable_download-monitor')); ?> />
<span class="description"><?php _e('You can specify if enable event tracking.', 'wp_danzitn'); ?></span></td>
</tr>
</table>

<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" /></p>
<input type="hidden" name="action" value="update" />
</p>
<input type="hidden" name="option_page" value="crm_app">
</form>


<h3><?php _e('Send a Test Request', 'wp_danzitn'); ?></h3>

<form method="POST" action="options-general.php?page=<?php echo plugin_basename(__FILE__); ?>">
<table class="optiontable form-table">
<tr valign="top">
<th scope="row"><label for="crmappmail"><?php _e('Email:', 'wp_danzitn'); ?></label></th>
<td><input name="crmappmail" type="text" id="crmappmail" value="" size="40" class="code" />
<span class="description"><?php _e('Type an email here and then click Send Test to generate a test request.', 'wp_danzitn'); ?></span></td>
</tr>
</table>
<p class="submit"><input type="submit" name="wpdnz_action" id="wpdnz_action" class="button-primary" value="<?php _e('Send Test', 'wp_danzitn'); ?>" /></p>
</form>

</div>
	<?php
	
} // End of wp_danzitn_options_page() function definition
endif;


/**
 * This function adds the required page (only 1 at the moment).
 */
if (!function_exists('wp_danzitn_menus')) :
function wp_danzitn_menus() {
	
	if (function_exists('add_submenu_page')) {
		add_options_page(__('DanziTN CRM Options', 'wp_danzitn'),__('DNZ CRM', 'wp_danzitn'),'manage_options',__FILE__,'wp_danzitn_options_page');
	}
	
} // End of wp_danzitn_menus() function definition
endif;


function wp_danzitn_action_links( $links, $file ) {
	if ( $file != plugin_basename( __FILE__ ))
		return $links;

	$settings_link = '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __( 'Settings', 'wp_danzitn' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

if (!defined('WPMS_ON') || !WPMS_ON) {
	// Whitelist our options
	add_filter('whitelist_options', 'wp_danzitn_whitelist_options');
	// Add the create pages options
	add_action('admin_menu','wp_danzitn_menus');
	// Add an activation hook for this plugin
	register_activation_hook(__FILE__,'wp_danzitn_activate');
	// Adds "Settings" link to the plugin action page
	add_filter( 'plugin_action_links', 'wp_danzitn_action_links',10,2);
}

/* see action dlm_downloading, plugin Download Monitor required*/
if (!function_exists('download_flamingo_init')) :
function download_flamingo_init() {
    if ( ! class_exists( 'Flamingo_Inbound_Message' ) )
            return;
	
    if ( ! class_exists( 'DLM_Download' ) )
            return;

    if ( ! term_exists( 'download-monitor', Flamingo_Inbound_Message::channel_taxonomy ) ) {
            wp_insert_term( __( 'Download Monitor', 'dwnld_mntr' ),
                    Flamingo_Inbound_Message::channel_taxonomy,
                    array( 'slug' => 'download-monitor' ) );
    }
}
endif;
add_action( 'flamingo_init', 'download_flamingo_init' );

/* see action wpmem_post_register_data, plugin WP-Members required*/
if (!function_exists('register_flamingo_init')) :
function register_flamingo_init() {
    if ( ! class_exists( 'Flamingo_Inbound_Message' ) )
            return;
    
    if ( ! function_exists( 'wpmem' ) )
            return;
	
    if ( ! term_exists( 'wp-members-register', Flamingo_Inbound_Message::channel_taxonomy ) ) {
            wp_insert_term( __( 'Members Registration', 'wpmem_reg' ),
                    Flamingo_Inbound_Message::channel_taxonomy,
                    array( 'slug' => 'wp-members-register' ) );
    }
}
endif;
add_action( 'flamingo_init', 'register_flamingo_init' );

/* see action danzitn_login wp_login on user.php,  plugin WP-Members required*/
if (!function_exists('login_flamingo_init')) :
function login_flamingo_init() {
    if ( ! class_exists( 'Flamingo_Inbound_Message' ) )
            return;

    if ( ! function_exists( 'wpmem_login' )  )
            return;

    if ( ! term_exists( 'wp-members-login', Flamingo_Inbound_Message::channel_taxonomy ) ) {
            wp_insert_term( __( 'Members Login', 'wpmem_login' ),
                    Flamingo_Inbound_Message::channel_taxonomy,
                    array( 'slug' => 'wp-members-login' ) );
    }
}
endif;
add_action( 'flamingo_init', 'login_flamingo_init' );


/*see action  wpmem_post_update_data and  password_reset on user.php, plugin WP-Members required*/
if (!function_exists('profile_update_flamingo_init')) :
function profile_update_flamingo_init() {
    if ( ! class_exists( 'Flamingo_Inbound_Message' ) )
            return;

    if ( ! function_exists( 'wpmem_registration' )  )
            return;
    
    if ( ! function_exists( 'wpmem_change_password' )  )
            return;
	    
    if ( ! term_exists( 'wp-members-profile_update', Flamingo_Inbound_Message::channel_taxonomy ) ) {
            wp_insert_term( __( 'Members Profile Update', 'wpmem_profile' ),
                    Flamingo_Inbound_Message::channel_taxonomy,
                    array( 'slug' => 'wp-members-profile_update' ) );
    }
}
endif;
add_action( 'flamingo_init', 'profile_update_flamingo_init' );
 
 /* track downloads performed by registered users, plugin WP-Members required, plugin Download Monitor required */
if (!function_exists('downloading_flamingo')) :
function downloading_flamingo($download, $version, $file_path) {
    if ( ! class_exists( 'DLM_Download' ) )
        return;
    
    if ( ! ( class_exists( 'Flamingo_Contact' ) && class_exists( 'Flamingo_Inbound_Message' ) ) )
        return;

    if ( empty( $download ) )
        return;
	
    global $current_user;
    get_currentuserinfo();
    
    if(empty($current_user->user_email)) 
	return;

    #$all_meta_for_user = array_map( function( $a ){ return $a[0]; }, get_user_meta( $current_user->ID ) );   
    $all_meta_for_user = get_user_meta( $current_user->ID );
    $user_props = array(
		'first_name' =>  $all_meta_for_user['first_name'],
		'last_name' => $all_meta_for_user['last_name'] );

    $email = $current_user->user_email;
    $name = $current_user->user_login;
    
    $channel = 'download-monitor';
    
    Flamingo_Contact::add( array(
        'email' => $email,
        'name' => $name ,
	'props' => $user_props,
        'channel' => $channel ) );
    
    $subject = "Download ".$download->get_the_title() . " for " .$name;
    $cats_descr = array();
    $categories = wp_get_post_terms($download->post->ID, 'dlm_download_category');
    foreach( $categories as $category) {
	$cats_descr[] = $category->name;
    }
    $params = array(
                    "title"=>$download->get_the_title(),
                    "url"=>$download->get_the_download_link(),
                    "download count"=>$download->get_the_download_count(),
                    "version"=>$download->get_the_version_number(),
                    "filename"=>$download->get_the_filename(),
                    "filetype"=>$download->get_the_filetype(),
                    "categories"=>(implode("|",$cats_descr)),
                    "members_only"=>$download->members_only,
                    "email"=>$email
                    );
    $params['crm_enable'] = get_option('crm_enable');
    $params['crm_processed'] = -1;
    foreach ( $all_meta_for_user as $key => $value ) {
        $params[$key] = $value;
    }
    $message_post = Flamingo_Inbound_Message::add( array(
        'channel' => $channel,
        'subject' => $subject,
        'from' => trim( sprintf( '%s <%s>', $name, $email ) ),
        'from_name' => $name,
        'from_email' => $email,
        'fields' => $params ) );
    // if( get_option('crm_enable_download-monitor') ) danzitn_add_crm_flamingo("Web Site Download",$subject, $email,$name,$params,$all_meta_for_user);
    dnz_log ( "downloading_flamingo terminated" );
}
endif;
add_action( 'dlm_downloading', 'downloading_flamingo', 11,3);

if (!function_exists('danzitn_flamingo_before_submit')):
function danzitn_flamingo_before_submit( $contactform, $result  ) {
	dnz_log ( "starting danzitn_flamingo_before_submit " );
	if ( ! ( class_exists( 'Flamingo_Contact' ) && class_exists( 'Flamingo_Inbound_Message' ) ) ) {
		dnz_log ( "danzitn_flamingo_before_submit Flamingo classes missing" );
		return;
	}

	if ( empty( $contactform ) ) {
		dnz_log ( "danzitn_flamingo_before_submit empty contactform" );
		return;
	}

	$fields_senseless = $contactform->form_scan_shortcode(
		array( 'type' => array( 'captchar', 'quiz', 'acceptance' ) ) );

	$exclude_names = array();

	foreach ( $fields_senseless as $tag )
		$exclude_names[] = $tag['name'];

	$submission = WPCF7_Submission::get_instance();

	if ( ! $submission || ! $posted_data = $submission->get_posted_data() ) {
		return;
	}
	
	foreach ( $posted_data as $key => $value ) {
		if ( '_' == substr( $key, 0, 1 ) || in_array( $key, $exclude_names ) )
			unset( $posted_data[$key] );
	}
	dnz_log ( $posted_data );
	
	$user_props = array();
	$email = isset( $posted_data['your-email'] ) ? trim( $posted_data['your-email'] ) : '';
	$name = isset( $posted_data['nome'] ) ? trim( $posted_data['nome'] ) : '';
	$telefono = isset( $posted_data['telefono'] ) ? trim( $posted_data['telefono'] ) : '';
	$last_name = isset( $posted_data['your-name'] ) ? trim( $posted_data['your-name'] ) : '';
	$subject = isset( $posted_data['your-subject'] ) ? trim( $posted_data['your-subject'] ) : '';
	$city =  isset( $posted_data['city'] ) ? trim( $posted_data['city'] ) : '';
	$data_arrivo = isset( $posted_data['data_arrivo'] ) ? trim( $posted_data['data_arrivo'] ) : '';
	$numero_notti =  isset( $posted_data['numero_notti'] ) ? trim( $posted_data['numero_notti'] ) : '';
	$your_message =  isset( $posted_data['your-message'] ) ? trim( $posted_data['your-message'] ) : '';
	$user_props['first_name'] = $name;
	$user_props['last_name'] = $last_name;
	$user_props['city'] = $city;
	$user_props['user_email'] = $email;
	$user_props['phone1'] = $telefono;
	$user_props['richiesta'] = $subject;
	$user_props['messaggio'] = $your_message;
	$posted_data['crm_enable'] = get_option('crm_enable');
    $posted_data['crm_processed'] = -1;
	$subject = $subject;
	// if( get_option('crm_enable_cf7-sumbit') ) danzitn_add_crm_flamingo("Web Site Contact Request",$subject, $email,$name,$posted_data,$user_props);
	dnz_log ( "danzitn_flamingo_before_submit terminated" );
}
endif;
add_action( 'wpcf7_submit', 'danzitn_flamingo_before_submit',11,2 );

if (!function_exists('danzitn_post_register_data')) :
function danzitn_post_register_data($fields) {
	if ( ! ( class_exists( 'Flamingo_Contact' ) && class_exists( 'Flamingo_Inbound_Message' ) ) )
		return;

	if ( empty( $fields ) )
		return;
	
	
	$email = $fields['user_email'];
	$name = $fields['username'];
	$subject = "User registration for ".$name." (".$email.")";
	$channel = "wp-members-register";
	
	$user_props = array(
		'first_name' =>  $fields['first_name'],
		'last_name' => $fields['last_name'] );
	
	Flamingo_Contact::add( array(
		'email' => $email,
		'name' => $name ,
		'props' => $user_props,
		'channel' => $channel ) ); 
	
	$fields['crm_enable'] = get_option('crm_enable');
    $fields['crm_processed'] = -1;
	$message_post = Flamingo_Inbound_Message::add( array(
		'channel' => $channel,
		'subject' => $subject,
		'from' => trim( sprintf( '%s <%s>', $name, $email ) ),
		'from_name' => $name,
		'from_email' => $email,
		'fields' => $fields ) );
	
	// if( get_option('crm_enable_wp-members-register') ) danzitn_add_crm_flamingo("Web Site Registration",$subject, $email,$name,$fields,$fields);
    dnz_log ( "danzitn_post_register_data terminated" );
}
endif;
add_action( 'wpmem_post_register_data', 'danzitn_post_register_data');

if (!function_exists('danzitn_login')) :
function danzitn_login($user_login, $user) {
	if ( ! ( class_exists( 'Flamingo_Contact' ) && class_exists( 'Flamingo_Inbound_Message' ) ) )
		return;

	if ( empty( $user ) || empty($user_login) )
		return;
		
	$email = $user->user_email;
	$name = $user_login; // display_name
	$subject = "User login ".$name." (".$email.")";
	$channel = "wp-members-login";
	#$all_meta_for_user = array_map( function( $a ){ return $a[0]; }, get_user_meta( $user->ID ) );
	$all_meta_for_user = get_user_meta( $user->ID );
	$user_props = array(
		'first_name' =>  $all_meta_for_user['first_name'],
		'last_name' => $all_meta_for_user['last_name'] );
	
	Flamingo_Contact::add( array(
		'email' => $email,
		'name' => $name ,
		'props' => $user_props,
		'channel' => $channel ) ); 
	
	$all_meta_for_user['crm_enable'] = get_option('crm_enable');
    $all_meta_for_user['crm_processed'] = -1;
	$message_post = Flamingo_Inbound_Message::add( array(
		'channel' => $channel,
		'subject' => $subject,
		'from' => trim( sprintf( '%s <%s>', $name, $email ) ),
		'from_name' => $name,
		'from_email' => $email,
		'fields' => $all_meta_for_user ) );
	// if( get_option('crm_enable_wp-members-login') ) danzitn_add_crm_flamingo("Web Site Login",$subject, $email,$name,$all_meta_for_user,$all_meta_for_user);
    dnz_log ( "danzitn_login terminated" );
}
endif;
add_action( 'wp_login', 'danzitn_login',11,2);

if (!function_exists('danzitn_pwdreset')) :
function danzitn_pwdreset($parms) {
	if ( ! ( class_exists( 'Flamingo_Contact' ) && class_exists( 'Flamingo_Inbound_Message' ) ) )
		return $parms;

	if ( empty( $parms )  )
		return $parms;
	
	if( username_exists( $parms['user'] ) ) {	
		$user = get_user_by( 'login', $parms['user'] );
	} else {
		return $parms;
	}
	$email = $parms['email'];
	$name = $parms['user']; // display_name
	$subject = "Password reset for ".$name." (".$email.")";
	$channel = "wp-members-profile_update";
	#$all_meta_for_user = array_map( function( $a ){ return $a[0]; }, get_user_meta( $user->ID ) );   
	$all_meta_for_user = get_user_meta( $user->ID );
	$user_props = array(
		'first_name' =>  $all_meta_for_user['first_name'],
		'last_name' => $all_meta_for_user['last_name'] );
	
	Flamingo_Contact::add( array(
		'email' => $email,
		'name' => $name ,
		'props' => $user_props,
		'channel' => $channel ) ); 
	
	$all_meta_for_user['crm_enable'] = get_option('crm_enable');
    $all_meta_for_user['crm_processed'] = -1;
	$message_post = Flamingo_Inbound_Message::add( array(
		'channel' => $channel,
		'subject' => $subject,
		'from' => trim( sprintf( '%s <%s>', $name, $email ) ),
		'from_name' => $name,
		'from_email' => $email,
		'fields' => $all_meta_for_user ) );
	// if( get_option('crm_enable_wp-members-profile_update') ) danzitn_add_crm_flamingo("Web Site Profile Update",$subject, $email,$name,$all_meta_for_user,$all_meta_for_user);
    dnz_log ( "danzitn_pwdreset terminated" );
	return $parms;
}
endif;
add_filter('wpmem_pwdreset_args','danzitn_pwdreset');
// danzitn_profile_update channel wp-members-profile_update $arr = apply_filters( 'wpmem_pwdreset_args', array( 'user' => $_POST['user'], 'email' => $_POST['email'] ) );

// manages flamingo events and inserts data into the CRM
function danzitn_add_crm_flamingo($channel,$subject, $email,$name,$posted_data,$user_props=array()) {
	dnz_log( "starting danzitn_add_crm_flamingo" );
	dnz_log( "danzitn_add_crm_flamingo posted_data" );
	dnz_log( $posted_data );
	dnz_log( "danzitn_add_crm_flamingo user_props" );
	dnz_log( $user_props );
	$posted_description = "";
	foreach($posted_data as $key=>$value){
		$posted_description .= $key."=".$value."\n";
	}
	$crm_password = get_option('crm_password');
	$crm_username = get_option('crm_username');
	$crm_backend = get_option('crm_backend');
	$crm_url = get_option('crm_url');
	if( get_option('crm_enable') )
	{
		if($crm_backend=="zurmo") $crmcl = new ZurmoClient($crm_url,$crm_username, $crm_password,"contact");
		if($crm_backend=="vtiger") $crmcl = new VtigerClient($crm_url,$crm_username, $crm_password);
		$ret_login = $crmcl->login();
		if(!empty($ret_login) && $ret_login['status'] == 'SUCCESS') {
			dnz_log( "danzitn_add_crm_flamingo ZurmoClient->login success" );
			$bFound = false;
			if($crm_backend=="vtiger") {
                $event = array('type'=>$channel,
                                   'subject'=>$user_props['last_name'] . " - " . $subject,
                                   'location'=>'WWW',
                                   'description'=>$posted_description);
			    $main_parms = array("contact"=>$user_props , "event"=>$event);
			    $opParms = array(
                    "email"=>$email,
                    "element"=>$crmcl->toParameterString($main_parms) ,
                    );
                $retOp = $crmcl->doOperation("process_email",$opParms);
                dnz_log( $retOp );
			} else if($crm_backend=="zurmo") {
			
			    $found_res = find_zurmo_entity_by_email($crmcl, $email);
			    if($found_res["status"] == 'SUCCESS') {
				    foreach($found_res["data"] as $module_key=>$entities) {				
					    dnz_log( "danzitn_add_crm_flamingo email ". $email. " found for module " .$module_key );
					    foreach($entities as $entity) {
						    dnz_log( $entity );		
						    $event_subject = $subject;
					        if( $module_key == "Contacts" ) $event_subject = $entity["lastName"] . " - " .$subject;
                            if( $module_key == "Leads" )    $event_subject = $entity["lastName"] . " - " .$subject;
                            if( $module_key == "Accounts" ) $event_subject = $entity["companyName"] . " - " .$subject;
	                        $event = array('type'=>$channel,
		                               'subject'=>$event_subject,
		                               'location'=>'WWW',
		                               'description'=>$posted_description);
						    // $evnt_ret = create_event_for_entity($crmcl,$module_key,$entity,$event);
						    if($crm_backend=="zurmo") $evnt_ret = create_zurmo_task_for_entity($crmcl,$module_key,$entity,$event);
						    if($crm_backend=="vtiger") $evnt_ret = create_vtiger_event_for_entity($crmcl,$module_key,$entity,$event);
						    dnz_log( $evnt_ret );
						    $bFound = true;
					    }
				    }
			    }
			    if( !$bFound  ) {
				    dnz_log( "danzitn_add_crm_flamingo email ". $email. " not found"  );
				    if($crm_backend=="zurmo") $response = create_new_zurmo_lead($crmcl,$channel,$subject, $user_props);
				    if($crm_backend=="vtiger") $response = create_new_vtiger_lead($crmcl,$channel,$subject, $user_props);
				    if ($response["status"] == 'SUCCESS')
				    {
				        dnz_log( "danzitn_add_crm_flamingo email lead for email ". $email. " created"  );
				        dnz_log( $response );
				        $record_id = $response["data"]["id"];
				        $record = array();
				        $record['companyName'] = $response["data"]["companyName"];
				        $record['firstName'] = $response["data"]["firstName"];
				        $record['lastName'] = $response["data"]["lastName"];
				        $record['id'] = $response["data"]["id"];
	                    $event = array('type'=>$channel,
		                           'subject'=>$record['lastName'] . " - " . $subject,
		                           'location'=>'WWW',
		                           'description'=>$posted_description);
				        if($crm_backend=="zurmo") $evnt_ret = create_zurmo_task_for_entity($crmcl,"lead",$record,$event);
				        if($crm_backend=="vtiger") $evnt_ret = create_vtiger_event_for_entity($crmcl,"Leads",$record,$event);
				        dnz_log( $evnt_ret );
				    } else {
				        dnz_log( "danzitn_add_crm_flamingo create_new_lead for email ". $email. " failed" );
				        dnz_log( $response );
				    }
			    }
		    }
		} else {
			dnz_log( "danzitn_add_crm_flamingo login failed" );
		}
	} else {
		dnz_log( "danzitn_add_crm_flamingo crm_enable is False" );
	}
	dnz_log( "danzitn_add_crm_flamingo terminated" );
}

if (!function_exists('custom_upload_mimes')) :
function custom_upload_mimes ( $existing_mimes=array() ) {
    // add your extension to the mimes array as below
    $existing_mimes['dwg'] = 'application/dwg';
    $existing_mimes['ctb'] = 'application/ctb';
    return $existing_mimes;
}
endif;
add_filter('upload_mimes', 'custom_upload_mimes');

if (!function_exists('dnz_update_flamingo_inbound_meta')) :
function dnz_update_flamingo_inbound_meta ($inbound_message_id) {
	$obj = get_post($inbound_message_id);
	if ( 'flamingo_inbound' != $obj->post_type ) {
	    return;
	}
	dnz_log ( "dnz_update_flamingo_inbound_meta on a ".$obj->post_type );  
    dnz_log ( "dnz_update_flamingo_inbound_meta on postid ". $inbound_message_id );
    wp_schedule_single_event( time() + 120, 'danzi_tn_single_event', array($inbound_message_id ) );
    dnz_log ( "dnz_update_flamingo_inbound_meta call to wp_schedule_single_event terminated!" );
    update_post_meta( $inbound_message_id, '_crm_enable', (get_option('crm_enable')=='true' ? 'true': 'false') );
    update_post_meta( $inbound_message_id, '_crm_processed', 0 );

}
endif;
add_action( "wp_insert_post", "dnz_update_flamingo_inbound_meta" );

function do_danzitn_in_two_minutes( $inbound_message_id) {
    dnz_log ( "do_danzitn_in_two_minutes is starting...." );
    dnz_log ( "inbound_message_id=".$inbound_message_id );
    $inbound_message = new Flamingo_Inbound_Message($inbound_message_id);
    if( $inbound_message instanceof Flamingo_Inbound_Message ) {
        $subject = $inbound_message->subject;
        $email = $inbound_message->from_email;
        $name = $inbound_message->from_name;
        $posted_data = $inbound_message->fields;
        $channel = $inbound_message->channel; 
        dnz_log ( "channel=".$channel );
        dnz_log ( "subject=".$subject );
        if( get_option('crm_enable_download-monitor') && $channel == "download-monitor" ) {
            danzitn_add_crm_flamingo("Web Site Download",$subject, $email,$name,$posted_data,$posted_data);
            update_post_meta( $inbound_message_id, '_crm_processed', 1 );
        }
        if( get_option('crm_enable_cf7-sumbit') && ($channel == "contact-form-7" || $channel == "richiesta-informazioni_copy")) {
            $user_props = array();
            $email = isset( $posted_data['your-email'] ) ? trim( $posted_data['your-email'] ) : '';
            $name = isset( $posted_data['nome'] ) ? trim( $posted_data['nome'] ) : '';
            $telefono = isset( $posted_data['telefono'] ) ? trim( $posted_data['telefono'] ) : '';
            $last_name = isset( $posted_data['your-name'] ) ? trim( $posted_data['your-name'] ) : '';
            $subject = isset( $posted_data['your-subject'] ) ? trim( $posted_data['your-subject'] ) : '';
            $city =  isset( $posted_data['city'] ) ? trim( $posted_data['city'] ) : '';
            $data_arrivo = isset( $posted_data['data_arrivo'] ) ? trim( $posted_data['data_arrivo'] ) : '';
            $numero_notti =  isset( $posted_data['numero_notti'] ) ? trim( $posted_data['numero_notti'] ) : '';
            $your_message =  isset( $posted_data['your-message'] ) ? trim( $posted_data['your-message'] ) : '';
            $user_props['first_name'] = $name;
            $user_props['last_name'] = $last_name;
            $user_props['city'] = $city;
            $user_props['user_email'] = $email;
            $user_props['phone1'] = $telefono;
            $user_props['richiesta'] = $subject;
            $user_props['messaggio'] = $your_message;
            danzitn_add_crm_flamingo("Web Site Contact Request",$subject, $email,$name,$posted_data,$user_props);
            update_post_meta( $inbound_message_id, '_crm_processed', 1 );
        }
        if( get_option('crm_enable_wp-members-register') && $channel == "wp-members-register" ) {
            danzitn_add_crm_flamingo("Web Site Registration",$subject, $email,$name,$posted_data,$posted_data);
            update_post_meta( $inbound_message_id, '_crm_processed', 1 );
        }
        if( get_option('crm_enable_wp-members-profile_update') && $channel == "wp-members-profile_update" ) {
            danzitn_add_crm_flamingo("Web Site Profile Update",$subject, $email,$name,$posted_data,$posted_data);
            update_post_meta( $inbound_message_id, '_crm_processed', 1 );
        }
        if( get_option('crm_enable_wp-members-login') && $channel == "wp-members-login" ) {
            danzitn_add_crm_flamingo("Web Site Login",$subject, $email,$name,$posted_data,$posted_data);
            update_post_meta( $inbound_message_id, '_crm_processed', 1 );
        }
    }
    dnz_log ( "....do_danzitn_in_two_minutes terminated!" );
}
add_action( 'danzi_tn_single_event', 'do_danzitn_in_two_minutes', 10, 1 );


if (!function_exists('dnz_log')):
function dnz_log ( $log )  {
   if ( is_array( $log ) || is_object( $log ) ) {
      error_log( "DNZ ". print_r( $log, 1 ),0 );
   } else {
      error_log( "DNZ ". $log,0 );
   }
}
endif;



