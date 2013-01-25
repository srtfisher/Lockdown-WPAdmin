<?php if (! defined('ABSPATH')) exit;
/**
 * We don't want to allow for this plugin to be used in WP-MS or network wide.
 *
 * @author Sean Fisher
**/
class Disable_WPMS_Plugin_LD
{
	/**
	 * PHP 4 style constructor
	 *
	 * @access private
	 * @return void
	**/
	function Disable_WPMS_Plugin_LD()
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
		/**
         * Disable buggy sitewide activation in WPMU and WP 3.0
         */
        if ((is_multisite() && isset($_GET['sitewide'])) || ($this->is_network_mode() && isset($_GET['networkwide']))) {
            $this->network_activate_error();
        }
		
		//	Default options
		update_option('ld_http_auth', 'none');
		update_option('ld_hide_wp_admin', 'no');
	}
	
	/**
	 * De-activate a plugin
	 *
	 * @access private
	**/
	function network_activate_error()
	{
		//	De-activate the plugin
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
		
		?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Network Activation Error</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	</head>
	<body>
		<p>
		    <strong>Error:</strong> This plugin cannot be activated network-wide.
		</p>
		<p>
			<a href="javascript:history.back(-1);">Back</a>			
		</p>
	</body>
</html>
<?php
		exit();
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

//	The object.
$setup_no_wpmu = new Disable_WPMS_Plugin_LD();

/* End of file: no-wpmu.php */