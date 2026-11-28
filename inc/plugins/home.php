<?php
/*
 * home.php
 * 
 * Copyright 2026 Jim Richardson <jim@phporyx.co.uk>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 * 
 */
 ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);
if(!defined("IN_MYBB")){die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");}
$GLOBALS['home_codename'] = basename(__FILE__, '.php');
$calling_hook = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_FILENAME)."_end";
if(defined("IN_ADMINCP")){
	$plugins->add_hook("admin_config_settings_change_commit", "home_reload");
	$plugins->add_hook("admin_config_plugins_plugin_list","home_update_check");
	$plugins->add_hook("admin_config_plugins_begin", "home_update");
}
$plugins->add_hook("build_friendly_wol_location_end", "home_online_activity");
$plugins->add_hook($calling_hook, 'home_mangle_nav');
/*
 * 
 * name: home_info
 * purpose: return plugin information to mybb
 * @param none
 * @return array
 * 
 */
function home_info() {
	global $lang, $home_codename;
	//$codename = str_replace('.php', '', basename(__FILE__));
	return array(
		"name"			=> "{$lang->pages_plugin}",
		"description"	=> "Build pages around a mybb forum",
		"website"		=> "https://phporyx.co.uk",
		"author"			=> 'JimR',
		"authorsite"	=> "https://community.mybb.com/user-37677.html",
		"version"		=> '1.03',
		"guid" 			=> '',
		"codename"	=> $home_codename,
		"compatibility" => '18*'
	);

}

/*
 * 
 * name: home_install
 * purpose: install plugin data into the database
 * @param none
 * @return none
 * 
 */
function home_install($data = array(),$current_data = array()) {
	global $db, $mybb;
	$install = false;
	try {
		$db_type = $mybb->config['database']['type'];
		switch ($db_type) {
			case 'mysqli':
			case 'mysql':
				$db_engine = 'mysql';
				break;

			case 'pgsql':
				$db_engine = 'pgsql';
				break;

			case 'sqlite':
				$db_engine = 'sqlite';
				break;

			default:
				throw new RuntimeException(
					"Site Pages: Unsupported database driver: {$db_type}"
				);
		}
		$install = false;

		if (count($data) == 0 && count($current_data) == 0) {
			// Fresh installation
			$data = home_load_resources();
			$install = true;
		}
		elseif (count($data) == 0 || count($current_data) == 0) {
			throw new RuntimeException('Site Pages: Invalid resource data for installation or update.');
		}
		if (!$install) {
			// Update: compare current_data against new_data
			// Identify changes to tables, templates, CSS, etc.
			// return out of the function  or manipulate the data before continuing
		}
		home_validate_resources($data, array(
			'tables' => array('name', 'mysql', 'pgsql', 'sqlite'),
			'templates' => array('name', 'content'),
			'css' => array('name', 'content'),
			'blocks' => array('name', 'title', 'show_title', 'content', 'block'),
			'pages' => array('name', 'title', 'content', 'php', 'enabled', 'php_enabled', 'show_blocks', 'visible', 'is_default'),
			'layouts' => array('page', 'block', 'zone', 'display_order')
		));

		$json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		if ($json === false) {
			throw new RuntimeException('Site Pages: Unable to encode resource data.');
		}

		// Create database tables.
		foreach ($data['tables'] as $table) {
			if (!$db->table_exists($table['name'])) {
				// Create the table.
				$sql = $table[$db_engine];
				$db->write_query($sql);
				// Create any additional indexes.
				if (isset($table['indexes'][$db_engine])) {
					foreach ($table['indexes'][$db_engine] as $index_sql) {
						$db->write_query($index_sql);
					}
				}
			}
		}

		// Add sample data.
		//blocks
		foreach ($data['blocks'] as $block) {
			$name = $db->escape_string($block['name']);
			$query = $db->simple_select(
				'pages_blocks',
				'id',
				"name='{$name}'"
			);
			$block_id = (int) $db->fetch_field($query, 'id');

			if (!$block_id) {
				$insert_data = array(
					'name'       => $db->escape_string($block['name']),
					'title'      => $db->escape_string($block['title']),
					'show_title' => (int) $block['show_title'],
					'content'    => $db->escape_string($block['content']),
					'block'      => $db->escape_string($block['block']),
					'enabled'    => (int) $block['enabled'],
					'visible'    => $db->escape_string($block['visible'])
				);

				$db->insert_query('pages_blocks', $insert_data);
			}
		}

		// Add sample pages.
		foreach ($data['pages'] as $page) {
			$name = $db->escape_string($page['name']);

			$query = $db->simple_select(
				'pages_pages',
				'id',
				"name='{$name}'"
			);

			$page_id = (int) $db->fetch_field($query, 'id');

			if (!$page_id) {
				$insert_data = array(
					'name'        => $db->escape_string($page['name']),
					'title'       => $db->escape_string($page['title']),
					'content'     => $db->escape_string($page['content']),
					'php'         => $db->escape_string($page['php']),
					'enabled'     => (int) $page['enabled'],
					'php_enabled' => (int) $page['php_enabled'],
					'show_blocks' => (int) $page['show_blocks'],
					'visible'     => $db->escape_string($page['visible']),
					'is_default'  => (int) $page['is_default']
				);

				$db->insert_query('pages_pages', $insert_data);
			}
		}
		/* Insert layouts */
		foreach ($data['layouts'] as $layout) {
			// Find page ID using the page name.
			$page = $db->escape_string($layout['page']);
			$query = $db->simple_select(
				'pages_pages',
				'id',
				"name='{$page}'"
			);

			$page_id = (int) $db->fetch_field($query, 'id');
			if (!$page_id) {
				throw new RuntimeException(
					"Site Pages: Layout references a page that does not exist: {$page}"
				);
			}

			// Find block ID using the block name.
			$block = $db->escape_string($layout['block']);

			$query = $db->simple_select(
				'pages_blocks',
				'id',
				"name='{$block}'"
			);

			$block_id = (int) $db->fetch_field($query, 'id');

			if (!$block_id) {
				throw new RuntimeException(
					"Site Pages: Layout references a block that does not exist: {$block}"
				);
			}
			// Get layout zone.
			$zone = (int) $layout['zone'];
			// Check whether this layout already exists.
			$query = $db->simple_select(
				'pages_layout',
				'id',
				"page_id='{$page_id}' AND block_id='{$block_id}' AND zone='{$zone}'"
			);

			$layout_id = (int) $db->fetch_field($query, 'id');

			if (!$layout_id) {
				// Build insertion data.
				$insert_data = array(
					'page_id'       => $page_id,
					'block_id'      => $block_id,
					'zone'          => $zone,
					'display_order' => (int) $layout['display_order']
				);

				$db->insert_query('pages_layout', $insert_data);
			}
		}
		// Create template group.
		$template_group = array(
			'prefix'    => 'sitepages',
			'title'     => 'Site Pages',
			'isdefault' => 1
		);
		$query = $db->simple_select(
			'templategroups',
			'gid',
			"prefix='sitepages'"
		);

		$gid = (int) $db->fetch_field($query, 'gid');
		if (!$gid) {
			$db->insert_query('templategroups', $template_group);
		} else {
			// Existing template group found.
			$db->update_query(
				'templategroups',
				$template_group,
				"gid='{$gid}'"
			);
		}
		// Insert templates.
		$datetime = TIME_NOW;
		foreach ($data['templates'] as $template) {
			$title = $db->escape_string($template['name']);
			$insert_data = array(
				'title'    => $title,
				'template' => $db->escape_string($template['content']),
				'sid'      => -2,
				'version'  => '',
				'dateline' => $datetime
			);

			$query = $db->simple_select(
				'templates',
				'tid',
				"title='{$title}' AND sid='-2'"
			);

			$template_id = (int) $db->fetch_field($query, 'tid');

			if ($template_id) {
				$db->update_query('templates', $insert_data, "tid='{$template_id}'");
			} 
			else {
				$db->insert_query('templates', $insert_data);
			}
		}
		// Register global CSS stylesheets.
		require_once MYBB_ROOT . $mybb->config['admin_dir'] . '/inc/functions_themes.php';
		$tid = 1; // Master Style.
		foreach ($data['css'] as $css) {
			$name = $css['name'];
			$cachefile = str_replace('/', '', $name);
			$insert_data = array(
				'tid'          => $tid,
				'name'         => $db->escape_string($name),
				'attachedto'   => '',
				'stylesheet'   => $db->escape_string($css['content']),
				'cachefile'    => $db->escape_string($cachefile),
				'lastmodified' => $datetime
			);
			// Check whether this stylesheet already exists.
			$query = $db->simple_select(
				'themestylesheets',
				'sid',
				"tid='{$tid}' AND name='" . $db->escape_string($name) . "'"
			);

			$sid = (int) $db->fetch_field($query, 'sid');
			if ($sid) {
				$db->update_query('themestylesheets', $insert_data, "sid='{$sid}'");
			} else {
				$sid = (int) $db->insert_query('themestylesheets', $insert_data);
			}
			// Create the cached stylesheet.
			if (!cache_stylesheet($tid, $cachefile, $css['content'])) {
				$db->update_query(
					'themestylesheets',
					array(
							'cachefile' => "css.php?stylesheet={$sid}"),
					"sid='{$sid}'"
				);
			}
		}
		// Update the Master Style and its child themes.
		update_theme_stylesheet_list($tid);
		// create the resources data
		// no need for id as it is auto generated allows for perhaps more rows so you can rollback the data if required
		$db->update_query(
			'pages_resources',
			array(	'active' => 0),
			"active='1'"
		);
		$plugin_info = home_info();
		$checksum = hash('sha256', $json);
		$insert_data = array(
			'data'     => $db->escape_string($json),
			'checksum' => $db->escape_string($checksum),
			'version'  => $db->escape_string($plugin_info['version']),
			'dateline' => TIME_NOW,
			'active'   => 1
		);
		$db->insert_query('pages_resources', $insert_data);
	} 
	catch (Throwable $e) {
		flash_message($e->getMessage(), 'error');
		admin_redirect('index.php?module=config-plugins');
		return;
	}
}


function home_activate() {
	global $db;
	
	try {
		$data = home_load_resources();

		if (!isset($data['settings']) || !is_array($data['settings'])) {
			throw new RuntimeException('Site Pages: Missing or invalid settings resource section.');
		}
	} catch (Throwable $e) {
		flash_message($e->getMessage(), 'error');
		admin_redirect('index.php?module=config-plugins');
		return;
	}

	// Create or update the setting group.
	$group_data = array(
		'name'		=> 'home',
		'title'		=> 'Site Pages',
		'description'	=> 'Settings for the Site Pages plugin.',
		'disporder'	=> 1,
		'isdefault'	=> 0
	);

	$query = $db->simple_select(
		'settinggroups',
		'gid',
		"name='home'"
	);

	$gid = (int) $db->fetch_field($query, 'gid');

	if (!$gid) {
		$gid = (int) $db->insert_query('settinggroups', $group_data);
	} else {
		$db->update_query('settinggroups', $group_data, "gid='{$gid}'");
	}

	// Create or update the settings.
	foreach ($data['settings'] as $setting) {
		$name = $db->escape_string($setting['name']);

		// home_disable must remain hidden from the settings page.
		if ($setting['name'] === 'home_disable') {
			$setting['gid'] = 0;
		} else {
			$setting['gid'] = $gid;
		}

		$insert_data = array(
			'name'			=> $name,
			'title'			=> $db->escape_string($setting['title']),
			'description'		=> $db->escape_string($setting['description']),
			'optionscode'		=> $db->escape_string($setting['optionscode']),
			'value'			=> $db->escape_string($setting['value']),
			'disporder'		=> (int) $setting['disporder'],
			'gid'			=> (int) $setting['gid']
		);

		$query = $db->simple_select(
			'settings',
			'sid',
			"name='{$name}'"
		);

		$sid = (int) $db->fetch_field($query, 'sid');

		if ($sid) {
			// Preserve the existing setting value.
			unset($insert_data['value']);

			$db->update_query('settings', $insert_data, "sid='{$sid}'");
		} else {
			$db->insert_query('settings', $insert_data);
		}
	}

	// Enable the plugin.
	$db->update_query(
		'settings',
		array('value' => 0),
		"name='home_disable'"
	);

	rebuild_settings();
}


function home_deactivate() {
	global $db, $mybb;
	if (!empty($mybb->input['uninstall'])) {
		return;
	}
	$db->update_query('settings', array('value' => 1), "name = 'home_disable'");
	$db->delete_query("settinggroups", "name = 'home'");
	rebuild_settings();

}

function home_is_installed() {
	global $mybb,$db;
	return $db->table_exists('pages_blocks');
}

function home_uninstall() {
	global $mybb, $db, $lang,$page;
	if ($mybb->request_method != 'post') {
		$page->output_confirm_action(
			'index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=home',
			$lang->home_uninstall_message,
			$lang->home_uninstall
		);
	}
	if (isset($mybb->input['no'])) {
		admin_redirect('index.php?module=config-plugins');
	}

	// Verify the form submission.
	if (!verify_post_check($mybb->get_input('my_post_key'))) {
		flash_message("Security validation failed. Please try again.", 'error');
		admin_redirect('index.php?module=config-plugins');
		return;
	}

	// Load resource definitions.

	try {
		$data = home_load_resources();
		$required_sections = array('tables', 'templates', 'css');
		foreach ($required_sections as $section) {
			if (!isset($data[$section]) || !is_array($data[$section])) {
				throw new RuntimeException(
					"Site Pages: Missing or invalid resource section: {$section}"
				);
			}
		}
	} catch (Throwable $e) {
		flash_message($e->getMessage(), 'error');
		admin_redirect('index.php?module=config-plugins');
		return;
	}
	require_once MYBB_ROOT . $mybb->config['admin_dir'] . '/inc/functions_themes.php';
		
	// Remove CSS stylesheets.
	foreach ($data['css'] as $css) {

		if (empty($css['name'])) {
			continue;
		}

		$name = $db->escape_string($css['name']);

		$query = $db->simple_select(
			'themestylesheets',
			'sid, tid, cachefile',
			"tid='1' AND name='{$name}'"
		);

		while ($stylesheet = $db->fetch_array($query)) {

			$sid = (int) $stylesheet['sid'];
			$tid = (int) $stylesheet['tid'];
			$cachefile = $stylesheet['cachefile'];

			// Remove cached stylesheet.
			if ($cachefile && strpos($cachefile, '?') === false) {
				@unlink(MYBB_ROOT . "cache/themes/{$tid}_{$cachefile}");
			}

			// Remove stylesheet database entry.
			$db->delete_query('themestylesheets', "sid='{$sid}'");
		}
	}

	// Refresh Master Style and child theme stylesheet lists.
	update_theme_stylesheet_list(1);

	// Remove plugin-owned database tables.
	if ($db instanceof DB_SQLite) {
		foreach ($db->query_objects as $query) {
			if ($query instanceof PDOStatement) {  
				if (strpos($query->queryString, 'mybb_settings') !== false ||
					strpos($query->queryString, 'mybb_settinggroups') !== false) {
					error_log('Site Pages: Closing ' . $query->queryString);
					$query->closeCursor();
				}
			}
		}
	} 
	foreach ($data['tables'] as $table) {
		if (!empty($table['name']) && $db->table_exists($table['name'])) {
			$db->drop_table($table['name']);
		}
	}

	// Remove Site Pages templates.
	$db->delete_query('templates', "title LIKE 'sitepages_%'");

	// Remove Site Pages template group.
	$db->delete_query('templategroups', "prefix='sitepages'");

	// Remove plugin settings.
	$db->delete_query('settings', "name LIKE 'home!_%' ESCAPE '!'");

	// Remove plugin setting group.
	$db->delete_query('settinggroups', "name='home'");

	$admin_dir = rtrim(MYBB_ADMIN_DIR, '/\\') . DIRECTORY_SEPARATOR;
	$root_dir = rtrim(MYBB_ROOT, '/\\') . DIRECTORY_SEPARATOR;

	$module_path = $admin_dir . 'modules' . DIRECTORY_SEPARATOR . 'pages';
	$resource_path = $root_dir . 'inc' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'home';

	error_log("Site Pages: MYBB_ROOT = " . MYBB_ROOT);
	error_log("Site Pages: MYBB_ADMIN_DIR = " . MYBB_ADMIN_DIR);
	error_log("Site Pages: Module path = " . $module_path);
	error_log("Site Pages: Module directory exists = " . (is_dir($module_path) ? 'YES' : 'NO'));
	error_log("Site Pages: Resource path = " . $resource_path);
	error_log("Site Pages: Resource directory exists = " . (is_dir($resource_path) ? 'YES' : 'NO'));

	$module_result = home_recursive_rmdir($module_path);
	$resource_result = home_recursive_rmdir($resource_path);

	error_log("Site Pages: Module deletion result = " . var_export($module_result, true));
	error_log("Site Pages: Resource deletion result = " . var_export($resource_result, true));
	// Remove main plugin file.
	@unlink("{$root_dir}inc/plugins/home.php");
}


function home_reload() {
	global $mybb,$db,$lang;
	$query = $db->simple_select('settinggroups', 'gid', "name='home'");
	$gid = $db->fetch_field($query, 'gid');
	if ($mybb->get_input('gid', MyBB::INPUT_INT) == $gid && $mybb->request_method == "post") {
		flash_message("Settings have been successfully updated.", 'success');
		admin_redirect("index.php?module=pages/info&update=true");
	}
}



function home_recursive_rmdir($dir) {
	if (is_link($dir)) {
		return unlink($dir);
	}

	if (!is_dir($dir)) {
		return !file_exists($dir);
	}

	$objects = scandir($dir);

	if ($objects === false) {
		error_log("Site Pages: Unable to scan directory: {$dir}");
		return false;
	}

	$success = true;

	foreach ($objects as $object) {
		if ($object === '.' || $object === '..') {
			continue;
		}

		$path = $dir . DIRECTORY_SEPARATOR . $object;

		if (is_dir($path) && !is_link($path)) {
			if (!home_recursive_rmdir($path)) {
				$success = false;
			}
		} else {
			if (!unlink($path)) {
				error_log("Site Pages: Unable to delete file: {$path}");
				$success = false;
			}
		}
	}

	if ($success && !rmdir($dir)) {
		error_log("Site Pages: Unable to remove directory: {$dir}");
		$success = false;
	}

	return $success;
}


 
function home_online_activity(&$pluginargs) {
	global $lang;

	// 1. Safely capture the user's raw location string track
	$current_location = !empty($pluginargs['user_activity']['location']) ? $pluginargs['user_activity']['location'] : '';

	// 2. Check if the user is on home.php
	if (strpos($current_location, 'home.php') !== false) {
		// 3. Match 'page=some-text' dynamically using a regular expression
		if (preg_match('/page=([a-zA-Z0-9_\-]+)/', $current_location, $matches)) {
			$page_key = $matches[1]; // e.g., 'online-games' or 'some-text'

			// Convert 'some-text' or 'my_custom_page' into 'Some Text' or 'My Custom Page'
			$friendly_name = ucwords(str_replace(['-', '_'], ' ', $page_key));

			// Set the dynamic output string location cleanly
			$pluginargs['location_name'] = "Viewing <a href=\"home.php?page={$page_key}\">{$friendly_name}</a>";
		} else {
			// Default Fallback: Just plain home.php
			if (!isset($lang->viewing_home)) {
				$lang->viewing_home = "Viewing <a href=\"home.php\">Home</a>";
			}
			$pluginargs['location_name'] = $lang->viewing_home;
		}
	}
}


function home_load_resources() {
	global $db;

	$path = __DIR__ . '/home/install.dta';

	// Check for database resource storage.
	if ($db->table_exists('pages_resources')) {
		$query = $db->simple_select(
			'pages_resources',
			'data, checksum',
			"active='1'"
		);

		$record = $db->fetch_array($query);
		$db->free_result($query);
		if ($record) {
			// Verify the stored data.
			$checksum = hash('sha256', $record['data']);
			

			if (!hash_equals($record['checksum'], $checksum)) {
				throw new RuntimeException(
					'Site Pages: Database resource data failed checksum validation.'
				);
			}

			$data = json_decode($record['data'], true);

			if (!is_array($data) || empty($data)) {
				throw new RuntimeException(
					'Site Pages: Database resource data is invalid or empty. read'
				);
			}

			return $data;
		}
	}

	// No database record exists; load the original resource file.
	if (!is_file($path) || !is_readable($path)) {
		throw new RuntimeException(
			"Site Pages: Data file missing or unreadable: {$path}"
		);
	}

	$data = include $path;

	if (!is_array($data) || empty($data)) {
		throw new RuntimeException(
			'Site Pages: Resource data is invalid or empty.fallback'
		);
	}

	return $data;
}

function home_validate_resources($data, $required_sections) {
	
	if (!is_array($data) || empty($data)) {
		throw new RuntimeException(
			'Site Pages: Resource data is invalid or empty.'
		);
	}

	foreach ($required_sections as $section => $required_fields) {

		if (!isset($data[$section]) || !is_array($data[$section])) {
			throw new RuntimeException(
				"Site Pages: Missing or invalid resource section: {$section}"
			);
		}

		foreach ($data[$section] as $key => $resource) {

			if (!is_array($resource)) {
				throw new RuntimeException(
					"Site Pages: Invalid resource entry in {$section}: {$key}"
				);
			}

			// Validate required fields.
			foreach ($required_fields as $field) {

				if (!array_key_exists($field, $resource)) {
					throw new RuntimeException(
						"Site Pages: Missing field '{$field}' in {$section}: {$key}"
					);
				}

				if (!is_string($resource[$field]) &&
					!is_int($resource[$field])) {
					throw new RuntimeException(
						"Site Pages: Invalid field '{$field}' in {$section}: {$key}"
					);
				}
			}

			// Validate optional database indexes.
			if ($section === 'tables' && isset($resource['indexes'])) {

				if (!is_array($resource['indexes'])) {
					throw new RuntimeException(
						"Site Pages: Invalid indexes definition for table: {$key}"
					);
				}

				foreach ($resource['indexes'] as $engine => $indexes) {

					if (!in_array($engine, array('mysql', 'pgsql', 'sqlite'), true) ||
						!is_array($indexes)) {
						throw new RuntimeException(
							"Site Pages: Invalid index definition for table: {$key}"
						);
					}

					foreach ($indexes as $index_sql) {

						if (!is_string($index_sql) || trim($index_sql) === '') {
							throw new RuntimeException(
								"Site Pages: Invalid index SQL for table: {$key}"
							);
						}
					}
				}
			}
		}
	}

	return true;
}

function home_debug($message) {
	$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

	$caller = isset($trace[1]['function'])
		? $trace[1]['function']
		: 'Unknown';

	error_log(
		'Site Pages [' . $caller . ']: ' . print_r($message, true)
	);
}

function home_mangle_nav() {
	global $mybb, $navbits, $home_codename;
	 if(home_is_navman_active()){
		  return;
	 }
	$home_url = trim($mybb->settings["{$home_codename}_home_page"]);

	if ($home_url === '') {
		$home_url = $mybb->settings['bburl'].'/index.php';
	}

	$home_hide_breadcrumb = (int) $mybb->settings["{$home_codename}_home_hide_breadcrumb"];
	$home_title = $mybb->settings["{$home_codename}_home_title"];
	$forum_title = $mybb->settings["{$home_codename}_forum_title"];

	$home_parts = parse_url($home_url);
	$home_path = $home_parts['path'] ?? '';
	$home_query = $home_parts['query'] ?? '';
	$home_script = basename($home_path);

	$home_host = $home_parts['host'] ?? '';
	$forum_host = parse_url($mybb->settings['bburl'], PHP_URL_HOST);

	$is_local_home = empty($home_host) || strcasecmp($home_host, $forum_host) === 0;
	$is_home = $is_local_home && $home_script === THIS_SCRIPT;

	$tmp_navbits = $navbits;

	$home_scripts = array_map(
		'trim',
		explode(',', $mybb->settings["{$home_codename}_files"])
	);

	array_splice($navbits, 0);

	if (
		$is_home &&
		$home_query === ($_SERVER['QUERY_STRING'] ?? '') &&
		$home_hide_breadcrumb
	) {
		return;
	}
	if ($is_home) {
	$navbits[0]['name'] = $home_title;
	$navbits[0]['url'] = $home_url;
	return;
}
	if (in_array(THIS_SCRIPT, $home_scripts, true)) {
		unset($tmp_navbits[0]);
	}
	else {
		$tmp_navbits[0]['name'] = $forum_title;
	}

	$navbits[0]['name'] = $home_title;
	$navbits[0]['url'] = $home_url;

	foreach ($tmp_navbits as $navbit) {
		$navbits[] = $navbit;
	}
}

function home_update_check() {
	global $db, $page;

	$current_info = home_info();
	$current_version = $current_info['version'];

	if (!$db->table_exists('pages_resources')) {
		return;
	}

	$query = $db->simple_select(
		'pages_resources',
		'version',
		"active='1'"
	);

	if ($db->num_rows($query) == 0) {
		return;
	}

	$record = $db->fetch_array($query);
	$working_version = $record['version'];

	if ($working_version !== $current_version) {
		error_log('Site Pages: version mismatch detected');
		$page->output_alert(
			"Site Pages Plugin version differs from the installed resources. <a href='index.php?module=config-plugins&action=update_home'>Click here to update</a>"
		);
	}
}

function home_load_file_resources() {
	$path = __DIR__ . '/home/install.dta';

	if (!is_file($path) || !is_readable($path)) {
		throw new RuntimeException(
			'Site Pages: Data file missing or unreadable.'
		);
	}

	$data = include $path;

	if (!is_array($data) || empty($data)) {
		throw new RuntimeException(
			'Site Pages: Resource data is invalid or empty.'
		);
	}

	return $data;
}
function home_update() {
	global $db, $mybb;
	if ($mybb->get_input('action') != 'update_home') {
		return;
	}
	try {
		$current_data = home_load_resources();
		$new_data = home_load_file_resources();

		home_install($new_data, $current_data);
	}
	catch (Throwable $e) {
		flash_message($e->getMessage(), 'error');
		admin_redirect('index.php?module=config-plugins');
		return;
	}

	flash_message('Site Pages successfully Updated', 'success');
	admin_redirect('index.php?module=config-plugins');
}

function home_is_navman_active() {
	global $cache;
	$plugins = $cache->read('plugins');
	if (!empty($plugins['active']) &&	in_array('nav_man', $plugins['active'], true)	) {
		return true;
	}
	return false;
}