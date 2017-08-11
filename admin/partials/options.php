<div>

<h1>Patreon API Settings</h1>

<form method="post" action="options.php">
    <?php settings_fields( 'patreon-options' ); ?>
    <?php do_settings_sections( 'patreon-options' ); ?>

    <?php if($creator_id == false) { ?>
    <br>
    <p>Cannot retrieve creator ID. Error connecting with Patreon.</p>
    <?php } ?>

    <br>

    <h2>API Settings</h2>
    <table class="form-table">

        <tr valign="top">
        <th scope="row">Redirect URI</th>
        <td><input type="text" value="<?php echo site_url().'/patreon-authorization/'; ?>" disabled class="large-text" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Client ID</th>
        <td><input type="text" name="patreon-client-id" value="<?php echo esc_attr( get_option('patreon-client-id', '') ); ?>" class="large-text" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Client Secret</th>
        <td><input type="text" name="patreon-client-secret" value="<?php echo esc_attr( get_option('patreon-client-secret', '') ); ?>" class="large-text" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Creator's Access Token</th>
        <td><input type="text" name="patreon-creators-access-token" value="<?php echo esc_attr( get_option('patreon-creators-access-token', '') ); ?>" class="large-text" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Creator's Refresh Token</th>
        <td><input type="text" name="patreon-creators-refresh-token" value="<?php echo esc_attr( get_option('patreon-creators-refresh-token', '') ); ?>" class="large-text" /></td>
        </tr>

        <?php if(get_option('patreon-creator-id', false)) { ?>
        <tr valign="top">
        <th scope="row">Creator ID</th>
        <td><input type="text" value="<?php echo esc_attr( get_option('patreon-creator-id', '') ); ?>" disabled class="large-text" /></td>
        </tr>
        <?php } ?>

        <tr valign="top">
        <th scope="row">URL for image to show when user is not yet a patron (or not yet paying enough)</th>
        <td><input type="text" name="patreon-paywall-img-url" value="<?php echo esc_attr( get_option('patreon-paywall-img-url', '') ); ?>" class="large-text" /></td>
        </tr>

    </table>

    <?php submit_button(); ?>

</form>
</div>
