<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php $manager = Lockdown_Manager::instance(); ?>
<div class="wrap">
	<h2><?php esc_html_e( 'Lockdown WordPress Admin', 'lockdown-wp-admin' ); ?></h2>
	<?php include LD_PLUGIN_DIR . '/views/errors.php'; ?>

	<p><?php esc_html_e( 'We are going to help make WordPress a bit more secure.', 'lockdown-wp-admin' ); ?></p>
	<p>
		<a href="https://twitter.com/srtfisher" class="twitter-follow-button" data-show-count="false">Follow @srtfisher</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		<br />
		<br>
		<em>
			(Also, I am a freelancer and would love to <a href="http://seanfisher.co/contact">hear from you about your project</a>!)
		</em>
	</p>

	<p>
		<?php esc_html_e( 'Follow Lockdown WP-Admin development on', 'lockdown-wp-admin' ); ?>
		<a href="https://github.com/srtfisher/Lockdown-WPAdmin" target="_blank"><?php esc_html_e( 'GitHub', 'lockdown-wp-admin' ); ?></a>
	</p>

	<form method="POST" action="<?php echo admin_url('admin.php?page=lockdown-wp-admin'); ?>">
		<?php wp_nonce_field('lockdown-wp-admin'); ?>
		<h3><?php esc_html_e( 'Hide WP Admin', 'lockdown-wp-admin' ); ?></h3>
		<p>
			We can "hide" WordPress's administration interface from the public.
			If you enable this, when you access
			<code><?php echo admin_url(); ?></code> when you
			<strong>aren't</strong> logged in, you will recieve a
			<a href="http://en.wikipedia.org/wiki/HTTP_404">404 error page</a>
			instead of redirecting to the login page.
		</p>
		<label>
			<input
				type="checkbox"
				name="hide_wp_admin"
				<?php if ( $manager->application->is_hiding_admin() ) echo 'checked'; ?>
				value="yes">
			<?php esc_html_e( 'Yes, please hide WP Admin from the user when they aren\'t logged in.', 'lockdown-wp-admin' ); ?>
		</label>

		<h3 style="margin-top: 30px;">
			<?php esc_html_e( 'WordPress Login URL', 'lockdown-wp-admin' ); ?>
		</h3>
		<label>
			<?php esc_html_e( 'Change the WordPress Login URL:', 'lockdown-wp-admin' ); ?>
			<?php echo wp_guess_url().'/'; ?>
			<input type="text" name="login_base" value="<?php echo $this->instance->application->getLoginBase(); ?>" />
			<br>
			<em>
				<?php echo esc_html( sprintf( __( 'This will change it from %s/wp-login.php to whatever you put in this box. If you leave it blank, it will be disabled.', 'lockdown-wp-admin' ), wp_guess_url() ) ); ?>
				<?php echo esc_html( sprintf( __( 'If you put \'login\' into the box, your new login URL will be %s/login/.', 'lockdown-wp-admin' ), wp_guess_url() ) ); ?>
			</em>
		</label>
		<?php
		$url = home_url() . '/'. $this->instance->application->getLoginBase();
		?>
		<p>
			<?php echo esc_html_e( 'Your current login URL is', 'lockdown-wp-admin' ); ?>
			<code><a href="<?php echo $url; ?>"><?php echo $url; ?></a></code>.
		</p>

		<?php if ($this->instance->application->isSuggestedAgainst()) : ?>
			<div class="updated error">
				<p>
					<?php echo esc_html( sprintf( __( 'Your login base %s is highly insecure! We strongly reccomend using another login URL to ensure maximum security.', 'lockdown-wp-admin' ), $this->login_base ) ); ?>
			</p>
			</div>
		<?php endif; ?>
		<blockquote>
			<h4><?php esc_html_e( 'Please Note Something!', 'lockdown-wp-admin' ); ?></h4>
			<p>
				<?php esc_html_e( 'If you are using a cache plugin (WTC, WP Super Cache, etc), you need to enable it
				to not cache the above base. That means (for most caching plugins) adding
				whatever you enter into the box above into your plugins Caching Whitelist, that
				is the list of URLs that your plugin doesn\'t cache. If you have any questions, tweet
				me', 'lockdown-wp-admin' ); ?>
				<a href="http://twitter.com/srtfisher">@srtfisher</a>.
			</p>
		</blockquote>
		<h3><?php esc_html_e( 'HTTP Authentication', 'lockdown-wp-admin' ); ?></h3>
		<p>
			<?php esc_html_e( 'Please read about HTTP Authentication on', 'lockdown-wp-admin' ); ?> <a href="http://en.wikipedia.org/wiki/Basic_access_authentication">http://en.wikipedia.org/wiki/Basic_access_authentication</a>.</p>
		<?php $http_auth_type = $this->instance->application->getHttpAuth(); ?>
		<label>
			<input name="http_auth" type="radio" value="none" <?php if ( empty( $http_auth_type ) || 'none' === $http_auth_type ) { ?>checked<?php } ?>>
			<?php esc_html_e( 'Disable HTTP Auth.', 'lockdown-wp-admin' ); ?>
		</label>
		<div class="clear"></div>
		<label>
			<input type="radio" name="http_auth" <?php if ( 'wp_creds' === $http_auth_type ) { ?>checked<?php } ?> value="wp_creds">
			<?php esc_html_e( 'WordPress Login Credentials', 'lockdown-wp-admin' ); ?>
		</label>
		<div class="clear"></div>
		<label>
			<input type="radio" name="http_auth" <?php if ( 'private' === $http_auth_type ) { ?>checked<?php } ?> value="private">
			<?php esc_html_e( 'Private Usernames/Passwords', 'lockdown-wp-admin' ); ?>
		</label>
		<div class="clear"></div>
		<br>
		<input type="hidden" name="did_update" value="yes_we_did">
		<input class='button-primary' type='submit' value='<?php echo esc_attr( __( 'Save Options', 'lockdown-wp-admin' ) ); ?>' id='submitbutton' />
	</form>
</div>
