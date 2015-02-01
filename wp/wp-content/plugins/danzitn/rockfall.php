<?php
/*
Plugin Name: Rockfall
Plugin URI: http://www.rockfalldefence.com
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

include_once( 'vtwsclib/Vtiger/WSClient.php' );
include_once( 'vtwsclib/Vtiger/rd_crm_functions.php' );

global $wprd_options; // This is horrible, should be cleaned up at some point
$wprd_options = array (
	'vt_url' => 'http://crm.rockfalldefence.com',
	'vt_username' => '',
	'vt_appkey' => '',
	'vt_enable'=>'false'
);

if (!function_exists('wp_rd_activate_options')) :
function wp_rd_activate_options() {
	
	global $wprd_options;
	
	// Create the required options...
	foreach ($wprd_options as $name => $val) {
		add_option($name,$val);
	}
	
}
endif;

if (!function_exists('wp_rd_whitelist_options')) :
function wp_rd_whitelist_options($whitelist_options) {
	
	global $wprd_options;
	
	// Add our options to the array
	$whitelist_options['vtiger'] = array_keys($wprd_options);
	
	return $whitelist_options;
	
}
endif;


/**
 * This function outputs the plugin options page.
 */
if (!function_exists('wp_rd_options_page')) :
// Define the function
function wp_rd_options_page() {
	
	// Load the options
	global $wprd_options, $phpmailer;
	
	// Make sure the CURL is enabled
	if ( !function_exists('curl_version') ) {
		
		// do soemthing
	}

	// Send a test request if enabled
	if (isset($_POST['wprd_action']) && $_POST['wprd_action'] == __('Send Test', 'wp_rd') && isset($_POST['vtemail'])) {
		
		// Set up the mail variables
		$event = array('type'=>'contact-form-7',
		       'subject'=>'test admin option page');
		$vtemail = $_POST['vtemail'];
		$vt_appkey = get_option('vt_appkey');
		$vt_enable = get_option('vt_enable');
		$vt_username = get_option('vt_username');
		$vt_url = get_option('vt_url');
		$ret_login = login_ws_client($vt_url,$vt_username, $vt_appkey);
		if($ret_login["success"]) {
			$client = $ret_login["result"];
			$out_result = "Login succedeed for ".$vt_username." with userid=".$client->_userid;
			$found_res = find_entity_by_email($client, $vtemail);
			// print_r($found_res);
			if($found_res["success"] == true) {
				foreach($found_res["result"] as $module_key=>$entities) {
					$out_result .= "<br/><strong>for " .$module_key."</strong>";
					foreach($entities as $entity) {
						foreach($entity as $key=>$val) {
							$out_result .= "<br/>".$key." = " .$val;
						}
						$event['description'] = $out_result;
						create_event_for_entity($client,$module_key,$entity,$event);
					}
				}
				if( empty($found_res["result"]) ) $out_result .= " but nothing found!";
			} else {
				$out_result .= " but searching for ".$vtemail." failed, with message ".$found_res["result"];
			}
		} else {
			$out_result = "Login Failed";
		}
		// Output the response
		?>
<div id="message" class="updated fade"><p><strong><?php _e('Test Message Sent', 'wp_rd'); ?></strong></p>
<p><?php _e('The result was:', 'wp_rd'); ?></p>
<pre><?php echo $out_result; ?></pre>
</div>
		<?php
		
		// Disconnect
		

	}
	
	?>
<div class="wrap">
<h2><?php _e('Rockfall CRM Options', 'wp_rd'); ?></h2>
<form method="post" action="options.php">
<?php wp_nonce_field('vtiger-options'); ?>

<table class="optiontable form-table">
<tr valign="top">
<th scope="row"><label for="vt_url"><?php _e('VTiger URL', 'wp_rd'); ?></label></th>
<td><input name="vt_url" type="text" id="vt_url" value="<?php print(get_option('vt_url')); ?>" size="40" class="regular-text" />
<span class="description"><?php _e('You can specify the url of your vtiger instalation.', 'wp_rd'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="$vt_username"><?php _e('VTiger User Name', 'wp_rd'); ?></label></th>
<td><input name="vt_username" type="text" id="vt_username" value="<?php print(get_option('vt_username')); ?>" size="40" class="regular-text" />
<span class="description"><?php _e('You can specify the user name of your vtiger user.', 'wp_rd'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="vt_appkey"><?php _e('VTiger User Key', 'wp_rd'); ?></label></th>
<td><input name="vt_appkey" type="text" id="vt_appkey" value="<?php print(get_option('vt_appkey')); ?>" size="40" class="regular-text" />
<span class="description"><?php _e('You can specify the app key of your vtiger user.', 'wp_rd'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><label for="vt_enable"><?php _e('VTiger Enable Tracking', 'wp_rd'); ?></label></th>
<td><input name="vt_enable" type="checkbox" id="vt_enable" value="true" <?php checked('true', get_option('vt_enable')); ?> />
<span class="description"><?php _e('You can specify if enable event tracking.', 'wp_rd'); ?></span></td>
</tr>
</table>

<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" /></p>
<input type="hidden" name="action" value="update" />
</p>
<input type="hidden" name="option_page" value="vtiger">
</form>


<h3><?php _e('Send a Test Request', 'wp_rd'); ?></h3>

<form method="POST" action="options-general.php?page=<?php echo plugin_basename(__FILE__); ?>">
<table class="optiontable form-table">
<tr valign="top">
<th scope="row"><label for="vtemail"><?php _e('Email:', 'wp_rd'); ?></label></th>
<td><input name="vtemail" type="text" id="vtemail" value="" size="40" class="code" />
<span class="description"><?php _e('Type an email here and then click Send Test to generate a test request.', 'wp_rd'); ?></span></td>
</tr>
</table>
<p class="submit"><input type="submit" name="wprd_action" id="wprd_action" class="button-primary" value="<?php _e('Send Test', 'wp_rd'); ?>" /></p>
</form>

</div>
	<?php
	
} // End of wp_rd_options_page() function definition
endif;


/**
 * This function adds the required page (only 1 at the moment).
 */
if (!function_exists('wp_rd_menus')) :
function wp_rd_menus() {
	
	if (function_exists('add_submenu_page')) {
		add_options_page(__('Rockfall CRM Options', 'wp_rd'),__('RD CRM', 'wp_rd'),'manage_options',__FILE__,'wp_rd_options_page');

		add_submenu_page('users.php', 'Rockfall Emergency Password Reset', 'RD Password Reset', 'administrator', 'rd_password_reset_main', 'rd_password_reset_main' );
	}
	
} // End of wp_rd_menus() function definition
endif;


if (!function_exists('rd_password_reset_main')) :
function rd_password_reset_main() {
	if(current_user_can('manage_options'))
    	{
        	global $wpdb;
       		$wpdb->show_errors();
		echo'<h2>Rockfall Password Reset</h2>';

	 	if(!empty($_POST['emergency_accept']) && check_admin_referer('emergency_reset','emergency_reset'))
		{
		    echo'<p>Ok, si parte, 100 alla volta...</p>';
		    $results=$wpdb->get_results("SELECT ID FROM ".$wpdb->prefix."users  
			JOIN ".$wpdb->prefix."usermeta ON ".$wpdb->prefix."usermeta.user_id = ".$wpdb->prefix."users.id
			AND ".$wpdb->prefix."usermeta.meta_key =  'rd_notified' AND ".$wpdb->prefix."usermeta.meta_value =  'false'
			WHERE user_login <> 'admin'
			ORDER BY ID
			LIMIT 0 , 100");
		    if($results){
			$ireset=0;
			foreach($results AS $row) {
				emergency_password_reset($row->ID);
				update_user_meta( $row->ID, 'rd_notified',  'true'  );
				$ireset++;
			}
		    }
		    echo '<h2>Fatto tutto ('.$ireset.' utenti resettati)</h2>';
		}
		else
		{
		    echo'<p><form action="" method="post">';
		    echo wp_nonce_field('emergency_reset','emergency_reset');
		    echo'<input type="hidden" name="emergency_accept" value="yes"/><input type="submit" value="Resetta tutte le password"/></form></p>';
		}

	}
	else{echo"<p>Non hai i permessi per eseguire il reset delle password!</p>";}
}
endif;


function emergency_password_reset($user_id)
{
    if(current_user_can('manage_options'))
    {
        $new_pass = wp_generate_password();
        wp_set_password( $new_pass, $user_id );
	$user = get_userdata( $user_id );
	$message = '<p>Per la migrazione alla nuova piattaforma web abbiamo dovuto resettare la tua password di accesso a '.site_url().'<br/>Il tuo username è ancora <b>'.$user->user_login.'</b> , ma la tua nuova password è <b>'.$new_pass.'</b> Consigliamo di  accedere al sito per cambiarla.<br/> Non rispondere a questo messaggio in quanto generato automaticamente solo a scopo informativo. <br/>Grazie!</p><br/>***********************************<br/>';
        $message .= '<p>For platform migration we have had to reset your password on '.site_url().'<br/>Your username is still <b>'.$user->user_login.'</b>, but your new password is <b>'.$new_pass.'</b><br/> Thanks.</p>';
        // echo'<p>Password changed for '.$user->user_login.'</p>';
        add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
        wp_mail($user->user_email, 'Rockfall Defence - password reset',$message);
    }
}


function wp_rd_action_links( $links, $file ) {
	if ( $file != plugin_basename( __FILE__ ))
		return $links;

	$settings_link = '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __( 'Settings', 'wp_rd' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

if (!defined('WPMS_ON') || !WPMS_ON) {
	// Whitelist our options
	add_filter('whitelist_options', 'wp_rd_whitelist_options');
	// Add the create pages options
	add_action('admin_menu','wp_rd_menus');
	// Add an activation hook for this plugin
	register_activation_hook(__FILE__,'wp_rd_activate');
	// Adds "Settings" link to the plugin action page
	add_filter( 'plugin_action_links', 'wp_rd_action_links',10,2);
}


if (!function_exists('wpcff_flamingo_init')) :
function wpcff_flamingo_init() {
	if ( ! class_exists( 'Flamingo_Inbound_Message' ) )
		return;

	if ( ! term_exists( 'calculated-fields-form', Flamingo_Inbound_Message::channel_taxonomy ) ) {
		wp_insert_term( __( 'Calculated Fields Form', 'wpcff' ),
			Flamingo_Inbound_Message::channel_taxonomy,
			array( 'slug' => 'calculated-fields-form' ) );
	}
}
endif;
add_action( 'flamingo_init', 'wpcff_flamingo_init');

if (!function_exists('download_flamingo_init')) :
function download_flamingo_init() {
    if ( ! class_exists( 'Flamingo_Inbound_Message' ) )
            return;
    if ( ! term_exists( 'download-monitor', Flamingo_Inbound_Message::channel_taxonomy ) ) {
            wp_insert_term( __( 'Download Monitor', 'dwnld_mntr' ),
                    Flamingo_Inbound_Message::channel_taxonomy,
                    array( 'slug' => 'download-monitor' ) );
    }
}
endif;
add_action( 'flamingo_init', 'download_flamingo_init' );

/* see action wpmem_post_register_data */
if (!function_exists('register_flamingo_init')) :
function register_flamingo_init() {
    if ( ! class_exists( 'Flamingo_Inbound_Message' ) )
            return;
    if ( ! term_exists( 'wp-members-register', Flamingo_Inbound_Message::channel_taxonomy ) ) {
            wp_insert_term( __( 'Members Registration', 'wpmem_reg' ),
                    Flamingo_Inbound_Message::channel_taxonomy,
                    array( 'slug' => 'wp-members-register' ) );
    }
}
endif;
add_action( 'flamingo_init', 'register_flamingo_init' );

/* see action rockfall_login wp_login on user.php*/
if (!function_exists('login_flamingo_init')) :
function login_flamingo_init() {
    if ( ! class_exists( 'Flamingo_Inbound_Message' ) )
            return;
    if ( ! term_exists( 'wp-members-login', Flamingo_Inbound_Message::channel_taxonomy ) ) {
            wp_insert_term( __( 'Members Login', 'wpmem_login' ),
                    Flamingo_Inbound_Message::channel_taxonomy,
                    array( 'slug' => 'wp-members-login' ) );
    }
}
endif;
add_action( 'flamingo_init', 'login_flamingo_init' );


/*see action  wpmem_post_update_data and  password_reset on user.php*/
 
if (!function_exists('profile_update_flamingo_init')) :
function profile_update_flamingo_init() {
    if ( ! class_exists( 'Flamingo_Inbound_Message' ) )
            return;
    if ( ! term_exists( 'wp-members-profile_update', Flamingo_Inbound_Message::channel_taxonomy ) ) {
            wp_insert_term( __( 'Members Profile Update', 'wpmem_profile' ),
                    Flamingo_Inbound_Message::channel_taxonomy,
                    array( 'slug' => 'wp-members-profile_update' ) );
    }
}
endif;
add_action( 'flamingo_init', 'profile_update_flamingo_init' );
 
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
    $params['vt_enable'] = get_option('vt_enable');
    $message_post = Flamingo_Inbound_Message::add( array(
        'channel' => $channel,
        'subject' => $subject,
        'from' => trim( sprintf( '%s <%s>', $name, $email ) ),
        'from_name' => $name,
        'from_email' => $email,
        'fields' => $params ) );
    // update_post_meta( $message_post->id, '_vt_enable', $params['vt_enable'] );
    rockfall_add_crm_flamingo($channel,$subject, $email,$name,$params,$all_meta_for_user);
        
}
endif;
add_action( 'dlm_downloading', 'downloading_flamingo', 11,3);

if (!function_exists('wpcff_flamingo_send_mail')) :
function wpcff_flamingo_send_mail( $params,$myrows ) {
	if ( ! ( class_exists( 'Flamingo_Contact' ) && class_exists( 'Flamingo_Inbound_Message' ) ) )
		return;
	
	global $current_user;
	get_currentuserinfo();
	if ( empty( $params ) || empty( $current_user ) )
		return;
	
	#$all_meta_for_user = array_map( function( $a ){ return $a[0]; }, get_user_meta( $current_user->ID ) );
	$all_meta_for_user = get_user_meta( $current_user->ID );
        $user_props = array(
		'first_name' =>  $all_meta_for_user['first_name'],
		'last_name' => $all_meta_for_user['last_name'] );
	
	$email = $current_user->user_email;
	$name = $current_user->user_login;
	$subject = "Calcolo " . $myrows["form_name"]. "  per " .$name;
	$channel = "calculated-fields-form";
		
	Flamingo_Contact::add( array(
		'email' => $email,
		'name' => $name ,
		'props' => $user_props,
		'channel' => $channel ) );
	
        $params["email"] = $email;
	$params['vt_enable'] = get_option('vt_enable');
	$message_post = Flamingo_Inbound_Message::add( array(
		'channel' => $channel,
		'subject' => $subject,
		'from' => trim( sprintf( '%s <%s>', $name, $email ) ),
		'from_name' => $name,
		'from_email' => $email,
		'fields' => $params ) );
	// update_post_meta( $message_post->id, '_vt_enable', $params['vt_enable'] );
	rockfall_add_crm_flamingo($channel,$subject, $email,$name,$params,$all_meta_for_user);
	
}
endif;
add_action( 'wpcff_send_mail', 'wpcff_flamingo_send_mail', 11,2);

if (!function_exists('rockfall_flamingo_before_send_mail')):
function rockfall_flamingo_before_send_mail( $contactform ) {
	if ( ! ( class_exists( 'Flamingo_Contact' ) && class_exists( 'Flamingo_Inbound_Message' ) ) )
		return;

	if ( empty( $contactform->posted_data ) || ! empty( $contactform->skip_mail ) )
		return;

	$fields_senseless = $contactform->form_scan_shortcode(
		array( 'type' => array( 'captchar', 'quiz', 'acceptance' ) ) );

	$exclude_names = array();

	foreach ( $fields_senseless as $tag )
		$exclude_names[] = $tag['name'];

	$posted_data = $contactform->posted_data;

	foreach ( $posted_data as $key => $value ) {
		if ( '_' == substr( $key, 0, 1 ) || in_array( $key, $exclude_names ) )
			unset( $posted_data[$key] );
	}
	$user_props = array();
	$email = isset( $posted_data['your-email'] ) ? trim( $posted_data['your-email'] ) : '';
	$name = isset( $posted_data['your-name'] ) ? trim( $posted_data['your-name'] ) : '';
	$last_name = isset( $posted_data['last_name'] ) ? trim( $posted_data['last_name'] ) : '';
	$subject = isset( $posted_data['your-subject'] ) ? trim( $posted_data['your-subject'] ) : '';
	$radio_attivita = isset( $posted_data['radio-attivita'] ) ? trim( $posted_data['radio-attivita'] ) : '';
	$company_name =  isset( $posted_data['company_name'] ) ? trim( $posted_data['company_name'] ) : '';
	$city =  isset( $posted_data['city'] ) ? trim( $posted_data['city'] ) : '';
	$user_props['first_name'] = $name;
	$user_props['last_name'] = $last_name;
	$user_props['user_email'] = $email;
	$user_props['city'] = $city;
	$user_props['company_name'] = $company_name;
	$user_props['business_type'] = $radio_attivita=="Pubblica amministrazione"?"PA":$radio_attivita;
	$posted_data['vt_enable'] = get_option('vt_enable');
	rockfall_add_crm_flamingo('contact-form-7',$subject, $email,$name,$posted_data,$user_props);
}
endif;
add_action( 'wpcf7_before_send_mail', 'rockfall_flamingo_before_send_mail',11,1 );

if (!function_exists('rockfall_post_register_data')) :
function rockfall_post_register_data($fields) {
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
	
	$fields['vt_enable'] = get_option('vt_enable');
	$message_post = Flamingo_Inbound_Message::add( array(
		'channel' => $channel,
		'subject' => $subject,
		'from' => trim( sprintf( '%s <%s>', $name, $email ) ),
		'from_name' => $name,
		'from_email' => $email,
		'fields' => $fields ) );
	
	// update_post_meta( $message_post->id, '_vt_enable', $params['vt_enable'] );
	rockfall_add_crm_flamingo($channel,$subject, $email,$name,$fields,$fields);
}
endif;
add_action( 'wpmem_post_register_data', 'rockfall_post_register_data');

if (!function_exists('rockfall_login')) :
function rockfall_login($user_login, $user) {
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
	
	$all_meta_for_user['vt_enable'] = get_option('vt_enable');
	$message_post = Flamingo_Inbound_Message::add( array(
		'channel' => $channel,
		'subject' => $subject,
		'from' => trim( sprintf( '%s <%s>', $name, $email ) ),
		'from_name' => $name,
		'from_email' => $email,
		'fields' => $all_meta_for_user ) );
	// update_post_meta( $message_post->id, '_vt_enable', $params['vt_enable'] );
	rockfall_add_crm_flamingo($channel,$subject, $email,$name,$all_meta_for_user,$all_meta_for_user);
}
endif;
add_action( 'wp_login', 'rockfall_login',11,2);

if (!function_exists('rockfall_pwdreset')) :
function rockfall_pwdreset($parms) {
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
	
	$all_meta_for_user['vt_enable'] = get_option('vt_enable');
	$message_post = Flamingo_Inbound_Message::add( array(
		'channel' => $channel,
		'subject' => $subject,
		'from' => trim( sprintf( '%s <%s>', $name, $email ) ),
		'from_name' => $name,
		'from_email' => $email,
		'fields' => $all_meta_for_user ) );
	// update_post_meta( $message_post->id, '_vt_enable', $params['vt_enable'] );
	rockfall_add_crm_flamingo($channel,$subject, $email,$name,$all_meta_for_user,$all_meta_for_user);
	return $parms;
}
endif;
add_filter('wpmem_pwdreset_args','rockfall_pwdreset');
// rockfall_profile_update channel wp-members-profile_update $arr = apply_filters( 'wpmem_pwdreset_args', array( 'user' => $_POST['user'], 'email' => $_POST['email'] ) );

function rockfall_add_crm_flamingo($channel,$subject, $email,$name,$posted_data,$user_props=array()) {
	$posted_description = "";
	foreach($posted_data as $key=>$value){
		$posted_description .= $key."=".$value."\n";
	}
	$event = array('type'=>$channel,
		       'subject'=>$subject,
		       'description'=>$posted_description);
	$vt_appkey = get_option('vt_appkey');
	$vt_username = get_option('vt_username');
	$vt_url = get_option('vt_url');
	if( get_option('vt_enable') )
	{
		$ret_login = login_ws_client($vt_url,$vt_username, $vt_appkey);
		if($ret_login["success"]) {
			$client = $ret_login["result"];
			$bFound = false;
			$found_res = find_entity_by_email($client, $email);
			// print_r($found_res);
			if($found_res["success"] == true) {
				foreach($found_res["result"] as $module_key=>$entities) {
					foreach($entities as $entity) {
						create_event_for_entity($client,$module_key,$entity,$event);
					}
					$bFound = true;
				}
			}
			if( !$bFound  ) {
				$record_id = create_new_lead($client,$channel,$subject, $user_props);
				if( isset($record_id) ) {
					create_event_for_entity($client,"Leads",array("id"=>$record_id),$event);
				}
			}
		}
	}
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

if (!function_exists('rd_update_flamingo_inbound_meta')) :
function rd_update_flamingo_inbound_meta ($inbound_message_id) {
	$obj = get_post($inbound_message_id);
	if ( 'flamingo_inbound' != $obj->post_type ) {
	    return;
	}
	update_post_meta( $inbound_message_id, '_vt_enable', (get_option('vt_enable')=='true' ? 'true': 'false') );
}
endif;
add_action( "wp_insert_post", "rd_update_flamingo_inbound_meta" );



