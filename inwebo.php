<?php
/**
 * @package Inwebo
 */
/*
Plugin Name: In-Webo Login
Plugin URI: http://in-webo.com
Description: 
Version: 1.2.1
Author: Emmanuel NINET / In-Webo Technologies
Author URI: 
License: 
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

//Main variables initialization
define('INWEBO_VERSION', '1.2.1');
define('INWEBO_PLUGIN_URL', plugin_dir_url( __FILE__ ));
define('INWEBO_SERVICE_ID', 0);
define('INWEBO_CERT_PASSPHRASE', "Enter certificate passphrase here");
define ('INWEBO_PLUGIN_PATH', dirname(__FILE__));

//Including mandatory and extension files
require_once( ABSPATH . 'wp-includes/registration.php' );
require_once( ABSPATH . 'wp-includes/formatting.php' );
require_once( ABSPATH . 'wp-includes/functions.php' );
require_once( dirname(__FILE__) . "/inwebometa.php" );
require_once( dirname(__FILE__) . "/includes/AuthenticationService.php" );

//----------------------------------------------------------------------------
// SETUP FUNCTIONS & GLOBAL VARIABLES
//----------------------------------------------------------------------------

global $inwebo_opt, $inwebo_version;

//Getting In-Webo plugin options
$inwebo_opt = get_option('inwebo_options');

//Getting In-Webo plugin version
$inwebo_version = get_option('inwebo_version');

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}

//Function init
function inwebo_init() {

}

//----------------------------------------------------------------------------
// Activation Functions
//----------------------------------------------------------------------------

function inwebo_activate()
{
	global $inwebo_opt;
	
        //Checking if the version of plugin activated is different than the one previously installed (if installed)
        
	$inwebo_version = get_option('inwebo_version'); //Version of the previously installed plugin
	$inwebo_this_version = INWEBO_VERSION; //Version of the currently installed plugin
	
	//Plugin has never been installed
	if (empty($inwebo_version))
	{
		add_option('inwebo_version', $inwebo_this_version);
	}
        //Plugin has been installed but in a different version
	elseif ($inwebo_version != $inwebo_this_version)
	{
		update_option('inwebo_version', $inwebo_this_version);
	}
	
	//Setting up default plugin options array
	$optionarray_def = array(
		'inwebo_service_id' => INWEBO_SERVICE_ID, //Default service ID
                'inwebo_cert_passphrase' => INWEBO_CERT_PASSPHRASE, //Default certificate passphrase
	);
	
        //If no plugin options previously defined, default options become current options
	if (empty($inwebo_opt)) {
		add_option('inwebo_options', $optionarray_def);
	}
}

//--------------------------------------------------------------------------
// Add Admin Page
//--------------------------------------------------------------------------

function inwebo_add_options_page()
{
	if (function_exists('add_options_page'))
	{
		add_options_page(__('In-Webo Login', "inwebo"), __('In-Webo Login', "inwebo"), 8, basename(__FILE__), 'inwebo_options_page');
	}
}

add_action('init', 'inwebo_init');

//----------------------------------------------------------------------------
// Disable user registration: user cannot self register to the site
//----------------------------------------------------------------------------
function inwebo_register_disabled()
{
	if (get_option('users_can_register'))
		update_option('users_can_register', FALSE);
}

//----------------------------------------------------------------------------
// Disable password reset: user cannot reset password by themselves
//----------------------------------------------------------------------------
function inwebo_no_password_reset()
{
	return FALSE;
}

//----------------------------------------------------------------------------
// Hide or Show Password Field
//----------------------------------------------------------------------------
function inwebo_show_password_field()
{
	return TRUE; //Shows password field(s) / Read Only on user profile / Active on Admin create user form => Requires using dummy pwd
}

//----------------------------------------------------------------------------
// Disable some profile form fields using javascript.
//----------------------------------------------------------------------------
function inwebo_profile_notes()
{
	$text = "<p><span class='description'>".
		__("Note: Some fields of profile can not be changed here. These fields are disabled below.", "inwebo") .
		"</span></p>";
	print $text;
}

function inwebo_profile_js()
{
	$script = <<<EOD
<script language='javascript'>
	var theform = document.getElementById("your-profile");
        theform.pass1.disabled = true;
        theform.pass2.disabled = true;

        var passcontent = document.getElementById("password");
        passcontent.hidden = true;
        
</script>
EOD;
	print $script;
}

//----------------------------------------------------------------------------
// Authentication routing : main authentication function
//----------------------------------------------------------------------------
function inwebo_authenticate($user, $username, $password) {
    // Getting In-Webo plugin options
    $optionarray_def = get_option('inwebo_options');
    
    //Getting the IW auto provisioning strategy defined in the plugin options
    $autoprovisioning = $optionarray_def['inwebo_allow_autoprovisioning']; //equals true or false
    
    //If there is already a WP user logged in...
    if ( is_a($user, 'WP_User') ) { return $user; }
  
    //If user credentials are empty
    if ( empty($username) || empty($password) ) {
        $error = new WP_Error();

        if ( empty($username) )
                $error->add('empty_username', __('<strong>ERROR</strong>: The username field is empty.', "inwebo"));

        if ( empty($password) )
                $error->add('empty_password', __('<strong>ERROR</strong>: The password field is empty.', "inwebo"));
        
        return $error;
    }
    
    //Retrieving the current user settings
    $userdata = get_userdatabylogin($username);
    
    //If impossible to retrieve user account
    if ( !$userdata ) {

        //If In-Webo auto pro is activated
        if ($autoprovisioning) {
            
            //Testing authentication at this step
            $authentication = _inwebo_authentication_query($username, $password);
            
            //Authentication successful
            if ($authentication) {
                //Creating new account in WP from In-Webo login
                inwebo_user_insert($username);
                //Retrieving the new user settings
                $userdata = get_userdatabylogin($username);
                //Loading and returning the user
                $user =  new WP_User($userdata->ID);
                return $user;
                
            //Authentication unsuccessful   
            } else {
               $error = new WP_Error();
               $error->add('login failed', '<strong>ERROR</strong>: unable to validate user authentication at In-Webo.');
               return $error; 
            }
            
        //If auto pro no allowed => generating normal error
        } else {
            return new WP_Error('invalid_username', sprintf(__('<strong>ERROR</strong>: Invalid username.')));
        }
    
    //Account successfully retrieved    
    } else {
    
        //Retrieving the user inwebo meta indicating if login must be done against In-Webo service or using classical WP authentication
        $inwebologin = get_user_meta($userdata->ID , 'inwebo_inwebologin', true);

        //If using classical authentication
        if ($inwebologin != 'on') {

            $userdata = apply_filters('wp_authenticate_user', $userdata, $password);
            if ( is_wp_error($userdata) )
                    return $userdata;

            if ( !wp_check_password($password, $userdata->user_pass, $userdata->ID) )
                    return new WP_Error( 'incorrect_password', sprintf( __( '<strong>ERROR</strong>: The password you entered for the username <strong>%1$s</strong> is incorrect. <a href="%2$s" title="Password Lost and Found">Lost your password</a>?' ),
                    $username, wp_lostpassword_url() ) );
            //Loading and returning the user
            $user =  new WP_User($userdata->ID);
            return $user;

        //If using In-Webo authentication    
        } elseif ($inwebologin == 'on') {

            $authentication = _inwebo_authentication_query($username, $password);

            //If authentication successful
            if ($authentication) {
                //Loading and returning the user
                $user =  new WP_User($userdata->ID);
                return $user;

            //If authentication KO
            } else {
                $error = new WP_Error();
                $error->add('login failed', '<strong>ERROR</strong>: unable to validate user authentication at In-Webo.');
                return $error;
            }
        }  
    }
}

//----------------------------------------------------------------------------
// Authentication function : IW Web Services authentication query
//----------------------------------------------------------------------------
function _inwebo_authentication_query($username, $password) {
    // Getting In-Webo plugin options
    $optionarray_def = get_option('inwebo_options');
    
     //Get In-Webo certificate passphrase in options array
    $passphrase = $optionarray_def['inwebo_cert_passphrase'];
    $certname = $optionarray_def['inwebo_cert_name'];

    //Loading the wsdl file
    $wsdl = dirname(__FILE__) .'/includes/Authentication.wsdl';
    $wsdl_options = array('encoding'=>'UTF-8','local_cert' => dirname(__FILE__) .'/includes/'.$certname, 'passphrase' => $passphrase);

    //Get In-Webo Service ID in options array
    $service_id = $optionarray_def['inwebo_service_id'];

    //Calling the In-Webo Web Service authentication function
    $x=new authenticate;
    $x->userId=$username;
    $x->serviceId=$service_id ;
    $x->token=$password;

    $auth=new AuthenticationService($wsdl, $wsdl_options);
    $resp=$auth->authenticate($x);
    $code=$resp->authenticateReturn;                                                                                            

    //If authentication successful
    if (strcmp($code,"OK")==0) {

        return true;

    //If authentication KO
    } else {

        return false;
    }
}

//-------------------------------------------------------------------------------------------------
// Custom new user registration (used to register users from In-Webo if auto provisioning activated
//-------------------------------------------------------------------------------------------------
function inwebo_user_insert($username) {
    global $wpdb;
    
    //Defining user default values
    $user_login = $username;
    $user_pass = '';
    $user_email = '';
    $user_url = '';
    $user_nicename = $username;
    $display_name = $username;
    $user_registered = gmdate('Y-m-d H:i:s');
    
    //Preparing the data for insertion into the sql statement
    $data = compact( 'user_pass', 'user_email', 'user_url', 'user_nicename', 'display_name', 'user_registered' );

    //request
    $wpdb->insert( $wpdb->users, $data + compact( 'user_login' ) );
    $user_id = (int) $wpdb->insert_id;

    //Initializing the new user object from sql query returned id
    $user = new WP_User( $user_id );

    //Giving default role to the user
    $user->set_role(get_option('default_role'));
    
    //Activating in-webo login method for the user
    update_user_meta( $user_id, 'inwebo_inwebologin', 'on' );

    wp_cache_delete($user_id, 'users');
    wp_cache_delete($user_login, 'userlogins');

    do_action('user_register', $user_id);

    return $user_id;
}

//----------------------------------------------------------------------------
// Logout Redirection: user is redirected to WP homepage
//----------------------------------------------------------------------------

function inwebo_logout_redirect()
{
	$logout_url = get_bloginfo('url');
	wp_redirect($logout_url);
	exit();
}

//----------------------------------------------------------------------------
// ADMIN OPTION PAGE FUNCTIONS: definition of the In-Webo plugin administration page
//----------------------------------------------------------------------------
function inwebo_options_page()
{
	//If option page has been posted
        if (isset($_POST['submit']) ) {
           
		// Options Array Update
		$optionarray_update = array (
                        'inwebo_allow_autoprovisioning' => $_POST['inwebo_allow_autoprovisioning'],
			'inwebo_service_id' => (int) $_POST['inwebo_service_id'],
                        'inwebo_cert_name' => $_POST['inwebo_cert_name'],
                        'inwebo_cert_passphrase' => $_POST['inwebo_cert_passphrase'],   
		);
		//Updating options
		update_option('inwebo_options', $optionarray_update);
	}

        // Getting Options
	$optionarray_def = get_option('inwebo_options');
?>
	<div class="wrap">
	<h2><?php _e("In-Webo Login", "inwebo"); ?></h2>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF'] . '?page=' . basename(__FILE__); ?>&updated=true">
        <fieldset class="options" style="border: none">
        <h3><?php _e("In-Webo User Management", "inwebo"); ?></h3>
        <p>User created in In-Webo Administration Console can be automatically added to your site database when they use their InWebo authentication tools to connect to the site for the first time.</p>
        <table width="100%" <?php echo $wpversion >= 2.5 ? 'class="form-table"' : 'cellspacing="2" cellpadding="5" class="editform"'; ?> >
		<tr valign="top">
			<th width="300px" scope="row"><?php echo __("Allow automatic provisioning of In-Webo users", "inwebo"); ?></th>
			<td width="100px" colspan="2"><input type="checkbox" name="inwebo_allow_autoprovisioning" <?php if ($optionarray_def['inwebo_allow_autoprovisioning'] == true) { ?> checked <?php } ?>></td>
		</tr>
	</table>
        <h3><?php _e("In-Webo Service Certificate", "inwebo"); ?></h3>
        <p>Copy the authentication certificate provided by In-Webo Technologies in the 'includes' subfolder of the In-Webo Login plugin directory:
            <strong><?php echo INWEBO_PLUGIN_PATH ?>/includes</strong></p>
        <p>Enter the file name (filename with extension .crt) and the passphrase of the certificate in the form below:</p>
	<table width="100%" <?php echo $wpversion >= 2.5 ? 'class="form-table"' : 'cellspacing="2" cellpadding="5" class="editform"'; ?> >
                <tr valign="top">
			<th width="200px" scope="row"><?php echo __("In-Webo Certificate File Name", "inwebo"); ?></th>
			<td width="100px" colspan="2"><input type="text" name="inwebo_cert_name" size="80" value="<?php echo  $optionarray_def['inwebo_cert_name']; ?>"></td>
		</tr>
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("In-Webo Certificate Passphrase", "inwebo"); ?></th>
			<td width="100px" colspan="2"><input type="text" name="inwebo_cert_passphrase" size="80" value="<?php echo  $optionarray_def['inwebo_cert_passphrase']; ?>"></td>
		</tr>
	</table>
	<h3><?php _e("In-Webo Login Options", "inwebo"); ?></h3>
	<table width="100%" <?php echo $wpversion >= 2.5 ? 'class="form-table"' : 'cellspacing="2" cellpadding="5" class="editform"'; ?> >
		<tr valign="top">
			<th width="200px" scope="row"><?php echo __("In-Webo Service ID", "inwebo"); ?></th>
			<td width="100px" colspan="2"><input type="text" name="inwebo_service_id" size="3" value="<?php echo  $optionarray_def['inwebo_service_id']; ?>"></td>
		</tr>
	</table>
	</fieldset>
	<p />
	<div class="submit">
		<input type="submit" name="submit" value="<?php _e('Update Options', "inwebo") ?> &raquo;" />
	</div>
	</form>
<?php
}

//Registers a plugin function to be run when the plugin is activated. 
register_activation_hook(basename(dirname(__FILE__)) . '/' .  basename(__FILE__),'inwebo_activate');

// Admin hooks
add_action('admin_menu', 'inwebo_add_options_page');

// Common hooks
add_action('login_form_register', 'inwebo_register_disabled');
add_action('personal_options', 'inwebo_profile_notes');
add_action('show_user_profile', 'inwebo_profile_js');
add_action('edit_user_profile', 'inwebo_profile_js');
add_filter('show_password_fields', 'inwebo_show_password_field', 10, 0);
add_filter('allow_password_reset', 'inwebo_no_password_reset', 10, 0);

//Authentication hook
add_filter('authenticate', 'inwebo_authenticate', 10, 3);
add_action('wp_logout', 'inwebo_logout_redirect');

