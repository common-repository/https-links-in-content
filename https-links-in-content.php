<?php
/*
Plugin Name: HTTP to HTTPS link changer by Eyga.net
Plugin URI: http://wordpress.org/extend/plugins/https-content/
Description: When WP is moved to HTTPS, all local absolute links in content are changed from HTTP to HTTPS.
Version: 0.2.4
Author: DSmidgy
Author URI: http://blog.slo-host.com/
*/

// Replaces HTTP with HTTPS protocol or vice versa in page content
add_filter('the_content', 'https_links_in_content_content', 10, 1);
function https_links_in_content_content($content) {
	$content_new = https_links_in_content_replace($content);
	return $content_new;
}

// Replaces HTTP with HTTPS protocol or vice versa in avatar
add_filter('get_avatar', 'https_links_in_content_avatar', 10, 1);
function https_links_in_content_avatar($avatar) {
	$avatar_new = https_links_in_content_replace($avatar);
	return $avatar_new;
}

// Replaces HTTP with HTTPS protocol in link
function https_links_in_content_replace($html) {
	// Example:
	// Site URL: http://www.example.com/en
	// Page URL: http://www.example.com/wp-content/uploads/2016/01/example.jpg
	
	// Set FROM and TO protocol
	if (is_ssl()) {
		$replace_from_protocol = "http";
		$replace_to_protocol = "https";
	} else {
		$replace_from_protocol = "https";
		$replace_to_protocol = "http";
	}
	
	// Read hosts from options and sdd SITE_URL to the list
	$hosts = explode('|', get_option('https_links_in_content'));
	$hosts[] = parse_url(get_site_url())['host'];
	
	// Replace protocols for all hosts
	$html_new = $html;
	foreach ($hosts as $host) {
		$host = trim($host);
		for ($i = 0; $i < 3; $i++) {
			$replace_from = "$replace_from_protocol://$host";
			$replace_to = "$replace_to_protocol://$host";
			// Replace protocol for subpages
			if ($i === 0) {
				$replace_from .= '/';
				$replace_to .= '/';
			}
			// Replace protocol for main page
			if ($i === 1) {
				$replace_from = '"' . $replace_from . '"';
				$replace_to = '"' . $replace_to . '"';
			}
			if ($i === 2) {
				$replace_from = "'" . $replace_from . "'";
				$replace_to = "'" . $replace_to . "'";
			}
			$html_new = str_replace($replace_from, $replace_to, $html_new);
		}
	}
	return $html_new;
}

/***********************/
/* Administration area */
/***********************/

add_action('admin_menu', 'https_links_in_content_admin');
function https_links_in_content_admin() {
	// Save options
	if (filter_input(INPUT_GET, 'page') == 'https_links_in_content_plugin' && filter_input(INPUT_POST, 'save') == 'Save Changes') {
		// Prepare string to write it into database
		$db_str = '';
		$post = filter_input_array(INPUT_POST);
		$plugin_prefix = 'http2https_';
		foreach ($post as $key => $value) {
			if (substr($key, 0, strlen($plugin_prefix)) == $plugin_prefix) {
				if (strlen($db_str) > 0) {
					$db_str .= '|';
				}
				$db_str .= str_replace('|', '', $value);
			}
		}
		// Write options to database
		update_option('https_links_in_content', str_replace("'", "''", $db_str));
		// Reload the page so "Saved" message will be displayed
		header("Location: options-general.php?page=https_links_in_content_plugin&saved=true");
		die;
	}
	// Call an options page
	add_options_page('Http2Https', 'HTTP to HTTPS link changer', 'administrator', 'https_links_in_content_plugin', 'https_links_in_content_settings');
}

function https_links_in_content_settings() {
	// Options have been saved
	if (filter_input(INPUT_POST, 'saved')) {
		echo '<div id="message" class="updated fade"><p><strong>Settings saved.</strong></p></div>';
	}
	// Read hosts from options
	$hosts = explode('|', get_option('https_links_in_content'));
	if (count($hosts) > 0 && $hosts[0] !== '') $hosts[] = '';
	$hosts_count = count($hosts);
	// Display the interface
	$width = '500px';
	?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>HTTP to HTTPS link changer options</h2>
		<div>This plugin replaces protocol (from http to https or vice versa) for all hosts listed below.<br>
			If a blog page is loaded through https all http links will converted to https for the domains specified below (and vice versa).</div>
			The protocol is replaced in content and avatar links.</div>
		<form action="" method="post">
			<table class="form-table">
				<tr valign="top">
					<td style="padding: 0;">
						<input type="text" name="http2https_0" value="<?php echo parse_url(get_site_url())['host'] ?>" style="width: <?php echo $width ?>;" disabled>
					</td>
				</tr><?php
				for ($i = 0; $i < $hosts_count; $i++) {
				?>
				<tr>
					<td style="padding: 0;">
						<input type="text" name="http2https_<?php echo $i + 1 ?>" value="<?php echo $hosts[$i] ?>" style="width: <?php echo $width ?>;">
					</td>
				</tr><?php
				}
				?>
			</table>
			<p class="submit">
				<input type="submit" name="save" value="Save Changes" class="button-primary" />
				<input type="submit" name="cancel" value="Cancel" />
			</p>
		</form>
	</div><?php
}
