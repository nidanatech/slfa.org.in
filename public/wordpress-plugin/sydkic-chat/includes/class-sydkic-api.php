<?php

class Sydkic_API {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function register_routes() {
		register_rest_route( 'sydkic/v1', '/verify', array(
			'methods' => 'POST',
			'callback' => array( $this, 'verify_connection' ),
            'permission_callback' => '__return_true',
		) );
	}

    public function verify_connection( $request ) {
        $params = $request->get_json_params();
        $received_token = isset($params['verification_token']) ? $params['verification_token'] : '';
        $received_channel_id = isset($params['channel_id']) ? $params['channel_id'] : '';

        // Get stored options
        $stored_token = get_option('sydkic_verification_token');
        $stored_channel_id = get_option('sydkic_channel_id');

        // Validate
        if ( $received_token === $stored_token && $received_channel_id === $stored_channel_id ) {
            return new WP_REST_Response( array( 
                'status' => 'success', 
                'message' => 'Verified',
                'version' => $this->version
            ), 200 );
        }

        return new WP_REST_Response( array( 
            'status' => 'error', 
            'message' => 'Invalid credentials',
            'debug' => [
                'match_token' => ($received_token === $stored_token),
                'match_id' => ($received_channel_id === $stored_channel_id)
            ]
        ), 403 );
    }
}
