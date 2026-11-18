<?php

class Sydkic_Public {

	private $plugin_name;
	private $version;
    private $api_url;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->api_url = 'https://sydkic.com';
	}

	public function render_widget() {
        $channel_id = get_option('sydkic_channel_id');
        
        if ( empty($channel_id) ) {
            return;
        }

        // Check if user is logged in
        $user_data = 'null';
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            $user_data = json_encode([
                'name' => $user->display_name,
                'email' => $user->user_email,
                'external_id' => (string) $user->ID
            ]);
        }
        
        // Output the Embed Script
        ?>
        <script>
            window.Sydkic = {
                user: <?php echo $user_data; ?>,
                context: {
                    title: "<?php echo get_the_title(); ?>",
                    url: "<?php echo get_permalink(); ?>"
                }
            };
            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "<?php echo $this->api_url; ?>/chat/<?php echo esc_js($channel_id); ?>/embed.js";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'sydkic-jssdk'));
        </script>
        <?php
	}
}
