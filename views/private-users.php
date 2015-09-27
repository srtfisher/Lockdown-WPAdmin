<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
	<h2><?php esc_html_e( 'Lockdown WordPress Admin', 'lockdown-wp-admin' ); ?></h2>
	<h3><?php esc_html_e( 'HTTP Authentication Private Users', 'lockdown-wp-admin' ); ?></h3>
	<?php include LD_PLUGIN_DIR . '/views/errors.php'; ?>

	<form method="POST" action="<?php echo admin_url('admin.php?page=lockdown-private-users'); ?>">
		<?php wp_nonce_field('lockdown-wp-admin'); ?>
		<p>
		<?php esc_html_e( 'Adding users below will only work if you have "Private Usernames/Passwords" selected for HTTP Authentication.', 'lockdown-wp-admin' ); ?>
	</p>

<div class="error">
	<p>
		<strong><?php esc_html_e( 'Please note a few things:', 'lockdown-wp-admin' ); ?></strong>
		<ol>
			<li>
				<?php esc_html_e( 'If you are ever locked out, you can just delete the plugin files via FTP (/wp-content/plugins/lockdown-wp-admin/) and you will be able to login again.', 'lockdown-wp-admin' ); ?>
			</li>
			<li>
				<?php esc_html_e( 'You cannot delete the current HTTP Authentication username you are using right now.', 'lockdown-wp-admin' ); ?>
			</li>

			<li>
				<?php esc_html_e( 'Private user HTTP Authentication will not work if you don\'t have a username added below.', 'lockdown-wp-admin' ); ?>
			</li>
		</ol>
	</p>
</div>

	<table class="widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Username', 'lockdown-wp-admin' ); ?></th>
				<th><?php esc_html_e( 'Action', 'lockdown-wp-admin' ); ?></th>
			</tr>
		</thead>

		<tfoot>
			<tr>
				<th><?php esc_html_e( 'Username', 'lockdown-wp-admin' ); ?></th>
				<th><?php esc_html_e( 'Action', 'lockdown-wp-admin' ); ?></th>
			</tr>
		</tfoot>
		<tbody>
			<?php if ( ! empty( $private_users ) ) : ?>
				<?php $nonce = wp_create_nonce('lockdown-wp-admin'); ?>

				<?php foreach ($private_users as $key => $user) : ?>
					<tr>
						<td><?php echo $user['user']; ?></td>
						<td>
							<a href="admin.php?page=lockdown-private-users&delete=<?php echo esc_attr( $key ); ?>&_wpnonce=<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'Delete', 'lockdown-wp-admin' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<h4><?php esc_html_e( 'Add a Private User', 'lockdown-wp-admin' ); ?></h4>
	<?php if ( 'private' !== $this->instance->application->getHttpAuth() ) : ?>
		<p><?php esc_html_e( 'To add a user, fill out the username and password below and click "Save Options" below.', 'lockdown-wp-admin' ); ?></p>
		<?php else : ?>
		<p><?php esc_html_e( 'Private Username/Password HTTP Authentication is enabled.', 'lockdown-wp-admin' ); ?></p>
	<?php endif; ?>

	<table class="form-table">
		<tr>
			<th>
				<label for="private_username"><?php esc_html_e( 'New Username', 'lockdown-wp-admin' ); ?></label>
			</th>
			<td>
				<input type="text" name="private_username" autocapitalize="none" id="private_username">
			</td>
		</tr>

		<tr>
			<th>
				<label for="private_password"><?php esc_html_e( 'New Password', 'lockdown-wp-admin' ); ?></label>
			</th>
			<td>
				<input type="password" id="private_password" name="private_password">
			</td>
		</tr>
	</table>

	<div class="clear"></div>
	<br />
	<input type="hidden" name="did_update" value="yes_we_did">
	<input class='button-primary' type='submit' value='<?php esc_html_e( 'Save Options', 'lockdown-wp-admin' ); ?>' id='submitbutton' />

	</form>
</div>
