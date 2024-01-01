<?php
/**
 * Plugin Name: LinkedIn Data Shortcode Maker
 * Plugin URI: https://github.com/mayurjobanputra/LinkedIn-Data-Shortcode-Maker---WordPress-Plugin
 * Description: Display LinkedIn data using shortcodes to showcase total followers and insights of specific posts.
 * Version: 0.1.0
 * Author: Mayur Jobanputra
 * Author URI: https://mayur.ca
 *
 * @package LinkedIn_Data_Shortcode_Maker
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LinkedIn_Data_Shortcode_Maker {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
        add_shortcode( 'linkedin_followers', array( $this, 'linkedin_followers_shortcode' ) );
        add_shortcode( 'linkedin_post_details', array( $this, 'linkedin_post_details_shortcode' ) );
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        add_options_page(
            'LinkedIn Data Shortcode Maker Settings',
            'LinkedIn Shortcode Maker',
            'manage_options',
            'linkedin_data_shortcode_maker',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Initialize settings.
     */
    public function settings_init() {
        register_setting( 'linkedinShortcodes', 'linkedin_data_shortcode_maker_settings' );

        add_settings_section(
            'linkedin_data_shortcode_maker_section',
            __( 'LinkedIn API Settings', 'wordpress' ),
            array( $this, 'settings_section_callback' ),
            'linkedinShortcodes'
        );

        add_settings_field(
            'linkedin_api_key',
            __( 'LinkedIn API Key', 'wordpress' ),
            array( $this, 'linkedin_api_key_render' ),
            'linkedinShortcodes',
            'linkedin_data_shortcode_maker_section'
        );

        add_settings_field(
            'linkedin_api_secret',
            __( 'LinkedIn API Secret', 'wordpress' ),
            array( $this, 'linkedin_api_secret_render' ),
            'linkedinShortcodes',
            'linkedin_data_shortcode_maker_section'
        );

        add_settings_field(
            'linkedin_member_id',
            __( 'LinkedIn Member ID', 'wordpress' ),
            array( $this, 'linkedin_member_id_render' ),
            'linkedinShortcodes',
            'linkedin_data_shortcode_maker_section'
        );
    }

    /**
     * Render API Key field.
     */
    public function linkedin_api_key_render() {
        $options = get_option( 'linkedin_data_shortcode_maker_settings' );
        ?>
        <input type='text' name='linkedin_data_shortcode_maker_settings[linkedin_api_key]' value='<?php echo $options['linkedin_api_key']; ?>'>
        <?php
    }

    /**
     * Render API Secret field.
     */
    public function linkedin_api_secret_render() {
        $options = get_option( 'linkedin_data_shortcode_maker_settings' );
        ?>
        <input type='password' name='linkedin_data_shortcode_maker_settings[linkedin_api_secret]' value='<?php echo $options['linkedin_api_secret']; ?>'>
        <?php
    }


    /**
     * Render LinkedIn Member ID field.
     */
    public function linkedin_member_id_render() {
        $options = get_option( 'linkedin_data_shortcode_maker_settings' );
        ?>
        <input type='text' name='linkedin_data_shortcode_maker_settings[linkedin_member_id]' value='<?php echo $options['linkedin_member_id']; ?>'>
        <?php
    }



    /**
     * Settings section callback.
     */
    public function settings_section_callback() {
        echo __( 'Enter your LinkedIn API credentials to enable data fetching for shortcodes.', 'wordpress' );
    }

    /**
     * Settings page.
     */
    public function settings_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>LinkedIn Data Shortcode Maker</h2>
            <?php
            settings_fields( 'linkedinShortcodes' );
            do_settings_sections( 'linkedinShortcodes' );
            submit_button();
            ?>
            
            <h3>LinkedIn App Settings</h3>
            <p>Use the following Redirect URI when setting up your LinkedIn app:</p>
            <input type="text" value="<?php echo esc_attr($this->get_redirect_uri()); ?>" readonly>
        </form>
        <?php
    }
    
    private function get_redirect_uri() {
        // Construct the redirect URI
        $redirect_uri = admin_url('options-general.php');
        $redirect_uri = add_query_arg(array(
            'page' => 'linkedin_data_shortcode_maker',
            'action' => 'linkedin_callback'
        ), $redirect_uri);
    
        return esc_url($redirect_uri);
    }
    

    /**
     * Shortcode to display LinkedIn followers count.
     */
    public function linkedin_followers_shortcode() {
        return $this->fetch_linkedin_followers_count();
    }

    /**
     * Fetch LinkedIn Followers Count.
     */
    private function fetch_linkedin_followers_count() {
        $api_key = ''; // Replace with your LinkedIn API Key
        $api_secret = ''; // Replace with your LinkedIn API Secret
        $access_token = $this->get_linkedin_access_token($api_key, $api_secret);
    
        if (!$access_token) {
            return 'Error retrieving access token.';
        }
    
        $url = 'https://api.linkedin.com/v2/networkSizes/{linkedin_member_id}?edgeType=CompanyFollowedByMember'; // Replace {linkedin_member_id} with actual member ID.
    
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
        ));
    
        if (is_wp_error($response)) {
            return 'Error fetching followers count.';
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
    
        if (isset($data->firstDegreeSize)) {
            return 'Total Followers: ' . $data->firstDegreeSize;
        } else {
            return 'Followers count unavailable.';
        }
    }

    
    
    private function get_linkedin_access_token($api_key, $api_secret, $authorization_code) {
        $token_url = 'https://www.linkedin.com/oauth/v2/accessToken';
    
        // Use WordPress functions to build the redirect URI
        $redirect_uri = get_redirect_uri();
        
        $response = wp_remote_post($token_url, array(
            'body' => array(
                'grant_type' => 'authorization_code',
                'code' => $authorization_code,
                'redirect_uri' => $redirect_uri,
                'client_id' => $api_key,
                'client_secret' => $api_secret,
            )
        ));
    
        if (is_wp_error($response)) {
            return false; // Error in fetching token
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
    
        if (isset($data->access_token)) {
            return $data->access_token;
        } else {
            return false; // Access token not found in response
        }
    }
    
    

    /**
     * Shortcode to display LinkedIn post details.
     */
    public function linkedin_post_details_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'url' => '',
        ), $atts, 'linkedin_post_details' );

        if ( empty( $atts['url'] ) ) {
            return 'LinkedIn post URL not provided.';
        }

        return $this->fetch_linkedin_post_details( $atts['url'] );
    }

    /**
     * Fetch LinkedIn Post Details.
     * @param string $url LinkedIn post URL.
     */
    private function fetch_linkedin_post_details( $url ) {
        // Fetch data from LinkedIn API using $url.
        // Placeholder for actual API integration.
        return 'Post Views: 500, Reposts: 50, Comments: 30'; // Example return value.
    }
}

$linkedin_data_shortcode_maker = new LinkedIn_Data_Shortcode_Maker();
