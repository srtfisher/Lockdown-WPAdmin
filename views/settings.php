<?php if (! defined('ABSPATH')) exit; ?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2>Lockdown WordPress Admin</h2>
	<?php if ( defined('LD_WP_ADMIN') && LD_WP_ADMIN == TRUE ) : ?>
	<div class="updated fade">
		<p>Options updated!</p>
	</div>
	<?php endif;
	if ( defined('LD_DIS_BASE') && LD_DIS_BASE == TRUE ) : ?>
		<div class="updated error fade">
			<p>You can't make that your URL Base.</p>
		</div>
	<?php endif; ?>

	<p>
		We are going to help make WordPress a bit more secure.
	</p>
	<p>
		<a href="https://twitter.com/srtfisher" class="twitter-follow-button" data-show-count="false">Follow @srtfisher</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script><br />
		I tweet a lot of things and often post whenever I update this plugin. Follow me <a href="http://twitter.com/srtfisher">@srtfisher</a>.
		<br>
		<em>
			(Also, I am a freelancer and would love to <a href="http://seanfisher.co/contact">hear from you about your project</a>!)
		</em>
	</p>

	<p>
		Follow Lockdown WP-Admin development on <a href="https://github.com/srtfisher/Lockdown-WPAdmin" target="_blank">GitHub</a>
	</p>

	<form method="POST" action="<?php echo admin_url('admin.php?page=lockdown-wp-admin'); ?>">
		
		<?php wp_nonce_field('lockdown-wp-admin'); ?>
		<h3>
			Hide WP Admin
		</h3>
		<p>
			We can "hide" WordPress's administration interface from the public.
			If you enable this, when you access
			<code><?php echo admin_url(); ?></code> when you
			<strong>aren't</strong> logged in, you will recieve a
			<a href="http://en.wikipedia.org/wiki/HTTP_404">404 error page</a>
			instead of redirecting to the login page.
		</p>
		<label>
			<input type="checkbox" name="hide_wp_admin" value="yep"
				<?php if ( get_option('ld_hide_wp_admin') === 'yep' ) { ?>
					checked<?php
				} ?>>
			Yes, please hide WP Admin from the user when they aren't logged in.
		</label>
		
		<h3 style="margin-top: 30px;">
			WordPress Login URL
		</h3>
		<label>
			Change the WordPress Login URL?
			<?php echo wp_guess_url().'/'; ?>
			<input type="text" name="login_base" value="<?php echo $this->instance->application->getLoginBase(); ?>" />
			<br>
			<em>
				This will change it from <?php echo wp_guess_url(); ?>/wp-login.php to whatever you put in this box. If you leave it <strong>blank</strong>, it will be disabled.<br />
				Say if you put "<strong>login</strong>" into the box, your new login URL will be <?php echo home_url(); ?>/login/.
			</em>
		</label>
		<?php
		global $auth_obj;
		$url = home_url() . '/'. $this->instance->application->getLoginBase();
		?>
		<p>
			Your current login URL is <code><a href="<?php echo $url; ?>"><?php echo $url; ?></a></code>.
		</p>

		<?php if ($this->instance->application->isSuggestedAgainst()) : ?>
			<div class="updated error"><p>
				Your login base <strong><?php echo $this->login_base; ?></strong> is highly insecure!
				We strongly reccomend using another login URL to ensure maximum security.
			</p>
			</div>
		<?php endif; ?>
		<blockquote>
			<h4>Please Note Something!</h4>
			<p>If you are using a cache plugin (WTC, WP Super Cache, etc), you need to enable it
				to not cache the above base. That means (for most caching plugins) adding
				whatever you enter into the box above into your plugins Caching Whitelist, that
				is the list of URLs that your plugin doesn't cache. If you have any questions, tweet
				me <a href="http://twitter.com/srtfisher">@srtfisher</a>.</p>
		</blockquote>
		<h3>HTTP Authentication</h3>
		<p>Please read about HTTP Authentication on <a href="http://en.wikipedia.org/wiki/Basic_access_authentication">http://en.wikipedia.org/wiki/Basic_access_authentication</a>.</p>
		<?php $http_auth_type = get_option('ld_http_auth'); ?>
		<label>
			<input name="http_auth" type="radio" value="none" <?php if ( $http_auth_type === '' || $http_auth_type === 'none' || !$http_auth_type ) { ?>checked<?php } ?>>
			Disable HTTP Auth.</label>
		<div class="clear"></div>
		<label>
			<input type="radio" name="http_auth" <?php if ( $http_auth_type === 'wp_creds' ) { ?>checked<?php } ?> value="wp_creds">
			WordPress Login Credentials</label>
		<div class="clear"></div>
		<label>
			<input type="radio" name="http_auth" <?php if ( $http_auth_type === 'private' ) { ?>checked<?php } ?> value="private">
			Private Usernames/Passwords</label>
		<div class="clear"></div>
		<br>
		<input type="hidden" name="did_update" value="yes_we_did">
		<input class='button-primary' type='submit' name='Save' value='<?php _e('Save Options'); ?>' id='submitbutton' />
	</form>
</div>
