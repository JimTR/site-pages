<?php
// Disallow direct access to this file for security reasons.
if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function pages_meta()
{
	global $page, $lang, $plugins, $db;

	// Check whether the plugin is enabled.
	$query = $db->simple_select(
		'settings',
		'value',
		"name='home_disable'"
	);

	$disabled = $db->fetch_field($query, 'value');

	if ($disabled !== '0') {
		return false;
	}

	// Get the Site Pages settings group ID.
	$query = $db->simple_select(
		'settinggroups',
		'gid',
		"name='home'"
	);

	$gid = (int) $db->fetch_field($query, 'gid');

	// Build the module menu.
	$sub_menu = array();

	$sub_menu['10'] = array(
		'id' => 'block',
		'title' => $lang->pages_add_block,
		'link' => 'index.php?module=pages/info&action=addblock'
	);

	$sub_menu['20'] = array(
		'id' => 'page',
		'title' => $lang->pages_add_page,
		'link' => 'index.php?module=pages/info&action=addpage'
	);

	if ($gid > 0) {
		$sub_menu['30'] = array(
			'id' => 'settings',
			'title' => $lang->pages_settings,
			'link' => "index.php?module=config-settings&action=change&gid={$gid}"
		);
	}

	$plugins->run_hooks('admin_pages_menu', $sub_menu);

	// Register the Site Pages module in the Admin CP.
	$page->add_menu_item(
		$lang->pages_plugin,
		'pages',
		'index.php?module=pages',
		70,
		$sub_menu
	);

	return true;
}

function pages_action_handler($action)
{
	global $page, $lang, $plugins, $db;

	$page->active_module = 'pages';

	$actions = array(
		'info' => array(
			'active' => 'info',
			'file' => 'info.php'
		)
	);

	$plugins->run_hooks('admin_pages_action_handler', $actions);

	// Validate the requested module action.
	if (!isset($actions[$action])) {
		$action = 'info';
	}

	// Get the Site Pages template group ID.
	$query = $db->simple_select(
		'templategroups',
		'gid',
		"prefix='sitepages'"
	);

	$group_id = (int) $db->fetch_field($query, 'gid');

	// Get installed themes.
	$query = $db->simple_select(
		'themes',
		'tid, name, properties',
		'tid > 0',
		array('order_by' => 'name')
	);

	$sub_menu = array();

	if ($db->num_rows($query) > 0) {
		$counter = 10;

		while ($theme = $db->fetch_array($query)) {
			$theme_id = (int) $theme['tid'];
			$theme_name = htmlspecialchars_uni($theme['name']);

			$properties = my_unserialize($theme['properties']);

			$style_id = isset($properties['templateset'])
				? (int) $properties['templateset']
				: $theme_id;

			if ($style_id < 0) {
				continue;
			}

			if ($group_id > 0) {
				$tpl_link = "index.php?module=style-templates&sid={$style_id}&expand={$group_id}#group_{$group_id}";
			} else {
				$tpl_link = "index.php?module=style-templates&sid={$theme_id}";
			}

			$sub_menu[$counter] = array(
				'id' => 'pages_tpl_' . $theme_id,
				'title' => "{$theme_name} Templates",
				'link' => $tpl_link
			);

			$counter += 10;
		}
	}

	$plugins->run_hooks('admin_pages_menu_templates', $sub_menu);

	// Build the template shortcuts sidebar.
	$sidebar = new SidebarItem($lang->pages_templates);
	$sidebar->add_menu_items($sub_menu, $actions[$action]['active']);

	$page->sidebar .= $sidebar->get_markup();

	$page->active_action = $actions[$action]['active'];

	return $actions[$action]['file'];
}
?>
