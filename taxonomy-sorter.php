<?php
/*
Plugin Name: WordPress Taxonomy Sorter
Plugin URI:
Description: A WordPress plugin that allows you to sort taxonomies.
Author: Austin Matzko
Author URI: http://austinmatzko.com
Version: 1.0-alpha
*/

if ( ! defined('ABSPATH') ) {
	die('Please do not load this file directly.');
}

if ( version_compare( PHP_VERSION, '5.2.0') >= 0 ) {

	require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'core.php';
	
} else {
	
	function taxonomy_sorter_version_message()
	{
		?>
		<div id="taxonomy-sorter-warning" class="updated fade error">
			<p>
				<?php 
				printf(
					__('<strong>ERROR</strong>: Your WordPress site is using an outdated version of PHP, %s.  Version 5.2 of PHP is required to use the taxonomy sorter plugin. Please ask your host to update.', 'taxonomy-sorter'),
					PHP_VERSION
				);
				?>
			</p>
		</div>
		<?php
	}

	add_action('admin_notices', 'taxonomy_sorter_version_message');
}

function taxonomy_sorter_init_event()
{
	global $wp_taxonomy_sorter;
	$wp_taxonomy_sorter = new WP_Taxonomy_Sort_Control;
	load_plugin_textdomain('taxonomy-sorter', null, dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'l10n');
}

add_action('plugins_loaded', 'taxonomy_sorter_init_event');
