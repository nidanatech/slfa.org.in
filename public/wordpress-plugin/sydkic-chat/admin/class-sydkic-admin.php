<?php

class Sydkic_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			'Sydkic', 
			'Sydkic', 
			'manage_options', 
			$this->plugin_name, 
			array( $this, 'display_plugin_setup_page' ),
			'dashicons-format-chat',
			25
		);
	}

	public function register_settings() {
		register_setting( $this->plugin_name, 'sydkic_channel_id' );
		register_setting( $this->plugin_name, 'sydkic_verification_token' );
	}

	public function display_plugin_setup_page() {
		include_once 'partials/sydkic-admin-display.php';
	}
}
