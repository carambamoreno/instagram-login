<h1>Instagram Login</h1>

<form method="post" action="options.php"> 
<?php settings_fields( 'instagram-login-settings-group' ); ?>
<?php do_settings_sections( 'my-cool-plugin-settings-group' ); ?>
<table class="form-table">
        <tr valign="top">
        	<th scope="row">API Key here</th>
        	<td>
        		<input type="text" name="ilogin_api_key" value="<?php echo esc_attr( get_option('ilogin_api_key') ); ?>" />
        	</td>
        </tr>
         
        <tr valign="top">
	        <th scope="row">API Secret</th>
	        <td>
	        	<input type="text" name="ilogin_api_secret" value="<?php echo esc_attr( get_option('ilogin_api_secret') ); ?>" />
	        </td>
        </tr>
    </table>

    <?php submit_button(); ?>

</form>