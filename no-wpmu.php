<?php if (! defined('ABSPATH')) exit;
/**
 * We don't want to allow for this plugin to be used in WP-MS or network wide.
 *
 * @author Sean Fisher
**/
class Disable_WPMS_Plugin_LD
{
	/**
	 * Object Constructor
	 *
	 * @return void
	**/
	function __construct()
	{
		register_activation_hook(LD_FILE_NAME, array( &$this, 'on_activate') );
	}
	
	/**
	 * Called when activating the plugin
	 *
	 * @access private
	**/
	function on_activate()
	{
		// Disable buggy sitewide activation in WPMU and WP 3.0
		if ((is_multisite() && isset($_GET['sitewide'])) || ($this->is_network_mode() && isset($_GET['networkwide'])))
			$this->network_activate_error();
		
		// Default options
		update_option('ld_http_auth', 'none');
		update_option('ld_hide_wp_admin', 'no');
	}
	
	/**
	 * De-activate a plugin
	 *
	 * @access private
	**/
	public function network_activate_error()
	{
		// De-activate the plugin
		$active_plugins = (array) get_option('active_plugins');
		$active_plugins_network = (array) get_site_option('active_sitewide_plugins');
		
		// workaround for WPMU deactivation bug
		remove_action('deactivate_' . LD_FILE_NAME, 'deactivate_sitewide_plugin');
		
		do_action('deactivate_plugin', LD_FILE_NAME);
		
		$key = array_search(LD_FILE_NAME, $active_plugins);
		
		if ($key !== false) {
			array_splice($active_plugins, $key, 1);
		}
		
		unset($active_plugins_network[LD_FILE_NAME]);
		
		do_action('deactivate_' . LD_FILE_NAME);
		do_action('deactivated_plugin', LD_FILE_NAME);
		
		update_option('active_plugins', $active_plugins);
		update_site_option('active_sitewide_plugins', $active_plugins_network);
		
		wp_die('The plugin cannot be activate network-wide.');
	}
	
	/**
	 * Returns true if it's WP with enabled Network mode
	 *
	 * @return boolean
	 * @author W3 Total Cache
	 */
	function is_network_mode()
	{
		static $network_mode = null;
		
		if ($network_mode === null) {
			$network_mode = (defined('MULTISITE') && MULTISITE);
		}
		
		return $network_mode;
	}
}

// The object.
$setup_no_wpmu = new Disable_WPMS_Plugin_LD;

/* End of file: no-wpmu.php */