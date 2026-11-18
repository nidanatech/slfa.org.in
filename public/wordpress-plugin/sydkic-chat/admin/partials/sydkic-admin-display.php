<div class="wrap">
    <h1>Sydkic Integration</h1>
    <p>Enter your Channel credentials to connect your website.</p>
    
    <form method="post" action="options.php">
        <?php
            settings_fields( 'sydkic-chat' );
            do_settings_sections( 'sydkic-chat' );
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Channel ID (Public Key)</th>
                <td><input type="text" name="sydkic_channel_id" value="<?php echo esc_attr( get_option('sydkic_channel_id') ); ?>" class="regular-text" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">API Secret (Verification Token)</th>
                <td><input type="password" name="sydkic_verification_token" value="<?php echo esc_attr( get_option('sydkic_verification_token') ); ?>" class="regular-text" /></td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
