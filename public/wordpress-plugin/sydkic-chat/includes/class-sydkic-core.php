<?php

class Sydkic_Core {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'sydkic-chat';
		$this->version = SYDKIC_VERSION;

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_api_hooks();
	}

	private function load_dependencies() {
		require_once SYDKIC_PLUGIN_DIR . 'includes/class-sydkic-loader.php';
		require_once SYDKIC_PLUGIN_DIR . 'admin/class-sydkic-admin.php';
		require_once SYDKIC_PLUGIN_DIR . 'public/class-sydkic-public.php';
		require_once SYDKIC_PLUGIN_DIR . 'includes/class-sydkic-api.php';
		$this->loader = new Sydkic_Loader();
	}

	private function define_admin_hooks() {
		$plugin_admin = new Sydkic_Admin( $this->plugin_name, $this->version );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
	}

	private function define_public_hooks() {
		$plugin_public = new Sydkic_Public( $this->plugin_name, $this->version );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'render_widget' );
	}

	private function define_api_hooks() {
		$plugin_api = new Sydkic_API( $this->plugin_name, $this->version );
		$this->loader->add_action( 'rest_api_init', $plugin_api, 'register_routes' );
	}

	public function run() {
		$this->loader->run();
	}
}
