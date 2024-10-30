<?php
/**
 * @package Inwebo
 *
 * Extension of inwebo plugin allowing to add a user meta telling if user login is managed by In-Webo plugin or by classical WP authentication
 */

/* 
 * Class grouping functions allowing to manipulate this extra user meta, named inwebo_inwebologin
 */
class inwebo_user_meta {
 
    /* Function to initialize hooks and related functions */
    function inwebo_user_meta() {
        if ( is_admin() ) {
        add_action( 'show_user_profile', array(&$this,'inwebo_show_extra_meta') ); //Show user profile hook
        add_action( 'edit_user_profile', array(&$this,'inwebo_show_extra_meta') ); //Edit user profile hook
        add_action( 'personal_options_update', array(&$this,'inwebo_save_extra_meta') ); // Edit personnal options hook
        add_action( 'edit_user_profile_update', array(&$this,'inwebo_save_extra_meta') ); // Edit user profile hook
        }
    }

    /*
     * Function allowing to display the inwebo meta extra form field
     */
    function inwebo_show_extra_meta( $user ) {
        //Form field is displayed only to admin users (level 10)
        if ( current_user_can('level_10')) {
        ?>
        <h3>In-Webo Login</h3>
        <table class="form-table">
        <tbody>
        <tr>
        <th><label for="inwebo">User login is managed by In-Webo</label></th>
        <td>

        <?php
            //Getting the value of the inwebo meta in user settings => if undefined or set to off value is '', if set to on, value is 'on'
            $inwebologin = get_user_meta($user->ID , 'inwebo_inwebologin', true);
        ?>

        <input id="inwebologin" class="regular-text" name="inwebo_inwebologin" <?php if ($inwebologin == 'on'){ ?> checked <?php }?> type="checkbox"></td>
        </tr>
        </tbody>
        </table>

    <?php }
    } 

    /*
     * Function allowing to save the value of the inwebo meta in the user settings
     */
    function inwebo_save_extra_meta( $user_id ) {
        //Function will work only for admin users (level 10)
        if ( current_user_can('level_10')) {
        update_user_meta( $user_id, 'inwebo_inwebologin', $_POST['inwebo_inwebologin'] );
        }
    }
}

/* Initialise the user edit and update profile actions globally */
add_action('plugins_loaded', create_function('','global $inwebo_user_meta_instance; $inwebo_user_meta_instance = new inwebo_user_meta();'));
?>