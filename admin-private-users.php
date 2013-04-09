<?php if (! defined('ABSPATH')) exit; ?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div><h2>HTTP Authentication Private Users</h2>

<?php if ( defined('LD_WP_ADMIN') && LD_WP_ADMIN == TRUE ) : ?>
<div class="updated fade"><p>Updated!</p></div>	
<?php endif;

// Error message?
if ( defined('LD_ERROR') && LD_ERROR == 'delete-self') : ?>
<div class="error fade"><p>You can't delete yourself!</p></div>	
<?php elseif (defined('LD_ERROR') AND LD_ERROR == 'username-exists') : ?>
<div class="error fade"><p>Username already taken.</p></div>	
<?php endif; ?>

<form method="POST" action="<?php echo admin_url('admin.php?page=lockdown-private-users'); ?>">
	<?php wp_nonce_field('lockdown-wp-admin'); ?>
	<p>Adding users below will <em>only</em> work if you have "Private Usernames/Passwords" selected for HTTP Authentication.</p>

	<p><strong>Please note a few things:</strong>
		<ul>
			<li>1. If you are ever locked out, you can just delete the plugin files via FTP (<code>/wp-content/plugins/lockdown-wp-admin/</code>) and you will be able to login again.</li>
			<li>2. You cannot delete the current HTTP Authentication username you are using right now.</li>
			
			<li>3. Private user HTTP Authentication will not work if you don't have a username added below.</li>
		</ul>
	</p>
	
	<table class="widefat">
	<thead>
	    <tr>
	        <th>Username</th>
	        <th>Action</th>
	    </tr>
	</thead>

	<tfoot>
	   
		<tr>
			<th>Username</th>
			<th>Action</th>
		</tr>
		
	</tfoot>
	<tbody>
		<?php if ( isset( $private_users ) && count( $private_users ) > 0 ) : ?>
		<?php $nonce = wp_create_nonce('lockdown-wp-admin'); ?>
		
	   <?php foreach( $private_users as $key => $user ) : ?>
	   <tr>
			<td><?php echo $user['user']; ?></td>
			<td><a href="admin.php?page=<?php echo $_GET['page']; ?>&delete=<?php echo $key; ?>&_wpnonce=<?php echo $nonce; ?>">Delete</a></td>
		</tr><?php endforeach; endif; ?>
	</tbody>
	</table>

<h4>Add a Private User</h4>
<p>To add a user, fill out the username and password below and click "Save Options" below.</p>
<label><input type="text" name="private_username"  /> New Username</label><br />
<label><input type="password" name="private_password" /> New Password</label>

<div class="clear"></div><br />
<input type="hidden" name="did_update" value="yes_we_did">
<input class='button-primary' type='submit' name='Save' value='<?php _e('Save Options'); ?>' id='submitbutton' />

</form>
</div>