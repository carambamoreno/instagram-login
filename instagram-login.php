<?php 

/*
Plugin Name: Instagram Login
Plugin URI:  https://github.com/carambamoreno/instagram-login
Description: Simple Instagram Login and Media Load
Version:     1.0.0
Author:      carambamoreno.com
Author URI:  https://carambamoreno.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ilogin
Domain Path: /languages
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define( 'ILOGIN_VERSION', '1.0.0');

//add admin panel so user sets options

function set_up_admin(){

	//Uses function admin_layout

	add_menu_page(
		'Instagram Login',
		'Instagram Login',
		'manage_options',
		'ilogin',
		'admin_layout'
	);

	add_action( 'admin_init', 'register_instagram_login_settings' );

}

//Used by set_up_admin

function admin_layout(){

	view('admin/main');

}

function register_instagram_login_settings() {

	settings_fields( 'instagram-login-settings-group' );
	register_setting( 'instagram-login-settings-group', 'ilogin_api_key' );
	register_setting( 'instagram-login-settings-group', 'ilogin_api_secret' );

}

function register_instagram_login_ajax(){

	wp_register_script( 'ajaxHandle', get_template_directory() . 'assets/js/ajax-calls.js', array(), false, true );
	wp_enqueue_script( 'ajaxHandle' );
	wp_localize_script( 'ajaxHandle', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin_ajax.php' ) ) );

}

function instagram_login_button_shortcode( $atts, $content ){

	if( is_user_logged_in() )
		return;

	//(isset($atts['redirect'])) ? $atts['redirect'] :

	$redirect = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ;
	$redirect_page =  isset($atts['redirect']) ? $atts['redirect'] : $redirect;
	$pop_up =  isset($atts['pop-up-window']) ? $atts['pop-up-window'] : $redirect;

	$class = isset($atts['class']) ? $atts['class'] : '';

	$buttonText = isset($atts['button-text']) ? $atts['button-text'] : 'Login with instagram';

	ob_start();

	echo '<a id="ilogin_link" style="cursor: pointer;" class="'. $class.'" data-url="https://api.instagram.com/oauth/authorize/?client_id='.esc_attr(get_option('ilogin_api_key', 'update your client id')).'&redirect_uri='.esc_attr($pop_up).'&response_type=token">'.$buttonText.'</a>';

	echo '<input type="email" id="ilogin_user_email" placeholder="Your email" style="display: none;" type="text" name="ilogin_user_email">';
	echo '<div id="ilogin_error_email"> </div>';
	echo '<button style="display: none; margin-top: 5px;" id="ilogin_submit">CONTINUE</button>';
	echo '<input type="hidden" id="ilogin_redirect_url" value="'.$redirect_page.'">';

	$html = ob_get_contents();
	
	ob_clean();

	//add_action('receive_token', 'instagram_login_pop_up');

	wp_register_script( 'instagram_login_js', plugins_url('assets/js/app.js', __FILE__), array('jquery'));

	wp_enqueue_script( 'instagram_login_js' );

	wp_localize_script( 'instagram_login_js', 'wordpress',
        array( 
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'instagram_token' => $_SESSION['instagram_token'] ?? 'notoken',
        )
    );

	return $html;
}

function instagram_login_pop_up($atts, $content){ 

	$redirect = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ;
	$redirect_page =  isset($atts['redirect']) ? $atts['redirect'] : $redirect;

	ob_start();

	echo '<input type="email" id="ilogin_user_email" placeholder="Your email" style="display: none;" type="text" name="ilogin_user_email">';
	echo '<div id="ilogin_error_email"> </div>';
	echo '<button style="display: none; margin-top: 5px;" id="ilogin_submit">CONTINUE</button>';
	echo '<input type="hidden" id="ilogin_redirect_url" value="'.$redirect_page.'">';

	$html = ob_get_contents();
	
	ob_clean();

	wp_register_script( 'instagram_login_js', plugins_url('assets/js/app.js', __FILE__), array('jquery'));
	wp_enqueue_script( 'instagram_login_js' );

	wp_localize_script( 'instagram_login_js', 'wordpress',
        array( 
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'instagram_token' => $_SESSION['instagram_token'] ?? 'notoken',
        )
    );

	return $html;

	//return '<ul class="instagram-login"></ul>';
}

function ilogin_action(){

	//just in case a logged in user is using and old page to login (but is already logged in)
	if(is_user_logged_in())
			wp_logout();

	session_start();
	//store instagram access token in session
	$_SESSION['instagram_token'] = $_POST['token'];

	//checks if username with instagram username exists in database
	$user = get_user_by('login', $_POST['username']);

	if($user){
        $user_id = $user->ID;

        $user = get_userdata($user_id);

        //if user has not provided email
        if( empty($user->user_email) ){
        	$_SESSION['new_user_id'] = $user_id;
			wp_send_json_success(array('user' => $user, 'type' => 'noemail'));
        	
        }

        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );
		wp_send_json_success(array('user' => $user, 'type' => 'existing'));

	}else{
		//if no, creates new user
		$password = wp_generate_password( 8, false );
		$new_user = wp_create_user( $_POST['username'], $password);

		$_SESSION['new_user_id'] = $new_user;
		wp_send_json_success(array('user' => $new_user, 'type' => 'new', 'new_user_id' => $_SESSION['new_user_id']));
		wp_send_json_success(array('user' => $new_user, 'type' => 'new'));

	}

}

function ilogin_add_email(){

	session_start();

	if(!isset($_SESSION['new_user_id']))
		wp_send_json_error( array('user' => 'failure no new_user_id has been set') );

	$user_id = $_SESSION['new_user_id'];

	$user = wp_update_user( array( 'ID' => $user_id, 'user_email' => $_POST["email"] ) );

	if (!is_wp_error($user)) {
		wp_set_current_user( $user );
	    wp_set_auth_cookie( $user );
	}else{
		wp_send_json_error( array('user' => $user) );
	}

	unset($_SESSION['new_user_id']);

	wp_send_json_success(array('user' => $user));

}

//execute plugin

add_action( 'wp_ajax_ilogin_action', 'ilogin_action' );
add_action( 'wp_ajax_nopriv_ilogin_action', 'ilogin_action' );
add_action( 'wp_ajax_ilogin_add_email', 'ilogin_add_email' );
add_action( 'wp_ajax_nopriv_ilogin_add_email', 'ilogin_add_email' );

add_shortcode( 'instagram-login', 'instagram_login_button_shortcode' );
add_shortcode( 'instagram-pop-up', 'instagram_login_pop_up' );

add_action('admin_menu', 'set_up_admin');

// helper functions

function view($path, $array = null){

	require plugin_dir_path( __FILE__ ).'views/' . $path .'.php';

}
