<?php
/**
* Plugin Name: WD Limit Admin IP
* Plugin URI: https://wedev.pt
* Description: Plugin to limit an admin to login on a specific network ip
* Version: 1.0.0
* Author: WEDEV
* Author URI: https://wedev.pt/
**/

add_action( 'show_user_profile', 'wd_limit_ip_extra_profile_fields' );
add_action( 'edit_user_profile', 'wd_limit_ip_extra_profile_fields' );

function wd_limit_ip_extra_profile_fields( $user ) { ?>

    <h3><?php _e('Limit Access by IP','wd-limit-ip-admin')?></h3>

    <table class="form-table">
        <tr>
            <th><label for="ip_login_limit"><?php _e('This user only can access from a specific IP','wd-limit-ip-admin')?></label></th>
            <td>
                <input type="checkbox" name="ip_login_limit" id="ip_login_limit" value="1" <?php if(get_the_author_meta( 'ip_login_limit', $user->ID )==1) echo 'checked'; ?> /><br />
                <span class="description"><?php _e('Check this box to force the ip connection of the user.','wd-limit-ip-admin')?></span>
            </td>
        </tr>
    </table>
<?php }
add_action( 'personal_options_update', 'wd_limit_ip_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'wd_limit_ip_save_extra_profile_fields' );

function wd_limit_ip_save_extra_profile_fields( $user_id ) {

    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }

    update_usermeta( $user_id, 'ip_login_limit', sanitize_text_field( $_POST['ip_login_limit'] ) );
}

add_filter( 'wd_authenticate_user', 'wd_limit_ip_authenticate_user' );

function wd_limit_ip_authenticate_user( $userdata ) {

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    $options = get_option( 'wd_limit_ip_options' );


    if(isset($options) && isset($options['ip']) && get_the_author_meta( 'ip_login_limit', $userdata->ID )==1 && $ip!=$options['ip']){
        $denied_message = _e("You need be connected to a specific network, your IP: ",'wd-limit-ip-admin').$ip;
        $message = new WP_Error( 'denied_access', $denied_message );
    }else{
        $message = $userdata;
    }

    return $message;
}

function wd_limit_ip_add_settings_page() {
    add_options_page( _e('Limit IP Admin','wd-limit-ip-admin'), _e('Limit IP Admin','wd-limit-ip-admin'), 'manage_options', 'wp-limit-ip-admin', 'wd_limit_ip_render_plugin_settings_page' );
}
add_action( 'admin_menu', 'wd_limit_ip_add_settings_page' );

function wd_limit_ip_render_plugin_settings_page() {
    ?>
    <h2><?php echo _e('Limit IP Admin','wd-limit-ip-admin')?></h2>
    <form action="options.php" method="post">
        <?php
        settings_fields( 'wd_limit_ip_options' );
        do_settings_sections( 'wd_limit_ip' ); ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php _e( 'Save','wd-limit-ip-admin' ); ?>" />
    </form>
    <?php
}
function wd_limit_ip_register_settings() {
    register_setting( 'wd_limit_ip_options', 'wd_limit_ip_options', 'wd_limit_ip_options_validate' );
    add_settings_section( 'api_settings', 'Settings', 'wd_limit_ip_section_text', 'wd_limit_ip' );

    add_settings_field( 'wd_limit_ip_setting_ip', 'IP Address', 'wd_limit_ip_setting_ip', 'wd_limit_ip', 'api_settings' );
}
add_action( 'admin_init', 'wd_limit_ip_register_settings' );

function wd_limit_ip_options_validate( $input ) {
    $newinput['ip'] = trim( $input['ip'] );
    /*if ( ! preg_match( '/^[a-z0-9]{32}$/i', $newinput['ip'] ) ) {
        $newinput['ip'] = '';
    }*/

    return $newinput;
}

function wd_limit_ip_section_text() {
    echo '<p>'._e('Set your ip to filter admin login','wd-limit-ip-admin').'</p>';
}

function wd_limit_ip_setting_ip() {
    $options = get_option( 'wd_limit_ip_options' );
    echo "<input id='wd_limit_ip_setting_ip' name='wd_limit_ip_options[ip]' type='text' value='" . sanitize_text_field( $options['ip'] ) . "' />";
}
