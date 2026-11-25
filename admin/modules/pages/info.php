<?php
// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Global initialization tweaks
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

if (isset($mybb->settings['home_disable']) && (int)$mybb->settings['home_disable'] == 1) {
	admin_redirect("index.php");
}

$page->add_breadcrumb_item($lang->pages_plugin, "index.php?module=pages/info");
$plugins->run_hooks("admin_pages_info_begin");

// Define Dashboard Navigation Tabs
$sub_tabs['pages_info'] = array(
	'title'       => $lang->pages_overview,
	'link'        => "index.php?module=pages/info",
	'description' => $lang->pages_info_description
);

$sub_tabs['pages'] = array(
	'title'       => $lang->pages_page_management,
	'link'        => "index.php?module=pages/info&tab=pages",
	'description' => $lang->pages_manage_pages
);
$sub_tabs['blocks'] = array(
	'title'       => $lang->pages_block_management,
	'link'        => "index.php?module=pages/info&tab=blocks",
	'description' => $lang->pages_manage_blocks
);


// 1. CORE ROUTER SYSTEM

$action = isset($mybb->input['action']) ? $mybb->input['action'] : '';

$post_actions = array(
	'enablepage',
	'disablepage',
	'deletepage',
	 'editpage',
	 'addpage',
	'enableblock',
	'disableblock',
	'deleteblock',
	'editblock',
   'saveblock',
	'layout',
);

if (in_array($action, $post_actions, true)) {
	if ($mybb->request_method != 'post') {
		$mybb->input['do']=""; // no data save unless we have a post
	}
	verify_post_check($mybb->get_input('my_post_key'));
}
$tab = isset($mybb->input['tab']) ? $mybb->input['tab'] : 'pages_info';

if (!empty($action)) {

	switch ($action) {
		/*case "addblock":
			$id = (int) $mybb->input['id'];
			home_action_block_form($id);
			break;*/

		case "addpage":
			$id = (int) $mybb->input['id'];
			if($mybb->input['do'] == "newpage"){
				home_save_object($mybb->input);
			} 
			home_action_page_form($id);
			break;
		case "editpage":
			$id = (int) $mybb->input['id'];
			if (in_array($mybb->input['do'], array('newpage', 'savepage'), true)) {
				home_save_object($mybb->input);
			}
			home_action_page_form($id);
			break;
		case "addblock":
		case "editblock":
			//die('<pre>' . print_r($mybb->input, true) . '</pre>');
			$id = (int) $mybb->input['id'];
			if (in_array($mybb->input['do'], array('newblock', 'editblock'), true)) {
				home_save_object($mybb->input);
			}
			home_action_block_form($id);
			break;

		case "enablepage":
			home_action_set_status("pages_pages", 1, "pages");
			break;

		case "disablepage":

			home_action_set_status("pages_pages", 0, "pages");
			break;

		case "deletepage":
			//die(print_r($mybb->input));
			home_action_delete_object("pages_pages", "pages");
			break;
		
		case "enableblock":
			home_action_set_status("pages_blocks", 1, "blocks");
			break;

		case "disableblock":
			home_action_set_status("pages_blocks", 0, "blocks");
			break;

		case "deleteblock":
			home_action_delete_object("pages_blocks", "blocks");
			break;
		case 'layout':
			if ($mybb->input['do'] == 'savelayout') {
				home_save_object($mybb->input);
			}
			home_page_layout_modal();
			exit;
		default:
			admin_redirect("index.php?module=pages/info");	

	}
}

else{
    $plugins->run_hooks("admin_pages_info_start");
    
    switch ($tab){
        case "home_info":
            home_view_tab_home_info($sub_tabs);
            break;
        case "pages_info":
            home_view_tab_overview($sub_tabs);
            break;
        case "pages":
            home_view_tab_pages_list($sub_tabs);
            break;
        case "blocks":
            home_view_tab_blocks_list($sub_tabs);
            break;
        case "layout":
			home_view_tab_layout_manager($sub_tabs);
			break;    
    }
}
// ==========================================
// 2. ISOLATED TAB VIEW FUNCTIONS
// ==========================================

function home_view_tab_overview($sub_tabs) {
	global $page, $db, $lang;

	$page->output_header($lang->pages_title);
	$page->output_nav_tabs($sub_tabs, 'pages_info');

	$query = $db->simple_select("pages_blocks", "COUNT(id) AS numblocks");
	$blocksnum = (int)$db->fetch_field($query, "numblocks");

	$query = $db->simple_select("pages_blocks", "COUNT(id) AS numblocks", "enabled='1'");
	$activeblocksnum = (int)$db->fetch_field($query, "numblocks");

	$passiveblocksnum = $blocksnum - $activeblocksnum;

	$query = $db->simple_select("pages_pages", "COUNT(id) AS numpages");
	$numpages = (int)$db->fetch_field($query, "numpages");

	$query = $db->simple_select("pages_pages", "COUNT(id) AS numpages", "enabled='1'");
	$numenabledpages = (int)$db->fetch_field($query, "numpages");

	$query = $db->simple_select("pages_layout", "COUNT(id) AS numassignments");
	$numassignments = (int)$db->fetch_field($query, "numassignments");

	$query = $db->simple_select("pages_layout", "COUNT(id) AS numassignments", "zone='0'");
	$topblocksnum = (int)$db->fetch_field($query, "numassignments");

	$query = $db->simple_select("pages_layout", "COUNT(id) AS numassignments", "zone='1'");
	$leftblocksnum = (int)$db->fetch_field($query, "numassignments");

	$query = $db->simple_select("pages_layout", "COUNT(id) AS numassignments", "zone='2'");
	$rightblocksnum = (int)$db->fetch_field($query, "numassignments");

	$query = $db->simple_select("pages_layout", "COUNT(id) AS numassignments", "zone='3'");
	$bottomblocksnum = (int)$db->fetch_field($query, "numassignments");
	$query = $db->simple_select("pages_pages", "COUNT(id) AS numpages", "show_blocks='1'");
	$numblockpages = (int)$db->fetch_field($query, "numpages");
	$query = $db->simple_select("pages_pages", "COUNT(id) AS numpages", "php_enabled='1'");
	$numphppages = (int)$db->fetch_field($query, "numpages");
	$numdisabledpages = $numpages - $numenabledpages;
	$blocksnum = my_number_format($blocksnum);
	$activeblocksnum = my_number_format($activeblocksnum);
	$passiveblocksnum = my_number_format($passiveblocksnum);
	$numpages = my_number_format($numpages);
	$numenabledpages = my_number_format($numenabledpages);
	$numassignments = my_number_format($numassignments);
	$topblocksnum = my_number_format($topblocksnum);
	$leftblocksnum = my_number_format($leftblocksnum);
	$rightblocksnum = my_number_format($rightblocksnum);
	$bottomblocksnum = my_number_format($bottomblocksnum);
	$numblockpages = my_number_format($numblockpages);
	$numphppages = my_number_format($numphppages);
	$numdisabledpages = my_number_format($numdisabledpages);
	$table = new Table();
	$table->construct_cell("<a href='index.php?module=pages/info&tab=blocks'>{$lang->pages_blocksnum}</a>", array('width' => '25%'));
	$table->construct_cell("{$blocksnum}", array('colspan' => 3));
	$table->construct_row();
	$table->construct_cell("<strong>{$lang->pages_activeblocksnum}</strong>", array('width' => '25%'));
	$table->construct_cell("{$activeblocksnum}", array('width' => '25%'));
	$table->construct_cell("<strong>{$lang->pages_passiveblocksnum}</strong>", array('width' => '25%'));
	$table->construct_cell("{$passiveblocksnum}", array('width' => '25%'));
	$table->construct_row();
	$table->construct_cell("<strong>{$lang->pages_leftsidenum}</strong>", array('width' => '25%'));
	$table->construct_cell("{$leftblocksnum}", array('width' => '25%'));
	//$table->construct_row();
	$table->construct_cell("<strong>{$lang->pages_rightsidenum}</strong>", array('width' => '25%'));
	$table->construct_cell("{$rightblocksnum}");
	$table->construct_row();
	$table->construct_cell("<strong>{$lang->pages_blocks_zonetop} Blocks</strong>", array('width' => '25%'));
	$table->construct_cell("{$topblocksnum }");
	$table->construct_cell("<strong>{$lang->pages_blocks_zonebottom} Blocks</strong>", array('width' => '25%'));
	$table->construct_cell("{$bottomblocksnum }");
	$table->construct_row();
	ob_start();
	$table->output($lang->pages_block_management);
	$table_html = ob_get_clean();
	$table = new Table();
	$query = $db->simple_select(
		"pages_pages",
		"title",
		"is_default='1'",
		array("limit" => 1)
	);

	$default_page = $db->fetch_field($query, "title");
	$table->construct_cell(
		"<strong>{$lang->pages_home_page_menu}</strong>",
		array('width' => '25%')
	);
	$table->construct_cell(
		htmlspecialchars_uni($default_page ?: "None"),
		array('colspan' => 3)
	);
	$table->construct_row();
	$table->construct_cell("<a href='index.php?module=pages/info&tab=pages'>Pages</a>", array('width' => '25%'));
	$table->construct_cell("{$numpages}", array('width' => '25%'));
	$table->construct_cell("<strong>Enabled Pages</strong>", array('width' => '25%'));
	$table->construct_cell("{$numenabledpages}", array('width' => '25%'));
	$table->construct_row();
	$table->construct_cell("<strong>Disabled Pages</strong>", array('width' => '25%'));
	$table->construct_cell("{$numdisabledpages}", array('width' => '25%'));
	$table->construct_cell("<strong>PHP enabled pages</strong>", array('width' => '25%'));
	$table->construct_cell("{$numphppages}", array('width' => '25%'));
	$table->construct_row();
	$table->construct_cell("<strong>Pages displaying blocks</strong>", array('width' => '25%'));
	$table->construct_cell("{$numblockpages}", array('width' => '25%'));
	$table->construct_cell("<strong>Blocks assignments</strong>", array('width' => '25%'));
	$table->construct_cell("{$numassignments}", array('width' => '25%'));
	$table->construct_row();
	ob_start();

	$table->output($lang->pages_page_management);
	$table2_html = ob_get_clean();

	echo "<div style='float:left;width:46%;padding-left:2%;'>$table2_html</div><div style='width:2%;float:left'>&nbsp;</div><div style='float:left;width:46%;'>$table_html</div>";
	$page->output_footer();
}

function home_view_tab_pages_list($sub_tabs) {
	global $page, $db, $lang, $cache, $mybb, $templates;

	$page->extra_header = '<link rel="stylesheet" href="styles/sitepages.css" />';
	$js_templates = array();

	foreach (array('modal', 'modal_button') as $template) {
		eval('$js_templates[$template] = "'.$templates->get($template, 1, 0).'";');
	}

	$page->extra_header .= "
<script type=\"text/javascript\">
var templates = {
    modal: ".json_encode($js_templates['modal']).",
    modal_button: ".json_encode($js_templates['modal_button'])."
};
</script>";
	$page->add_breadcrumb_item($lang->pages_page_management, "");
	$page->output_header($lang->pages_page_management);
	$page->output_nav_tabs($sub_tabs, 'pages');

	$table = new Table();
	$table->construct_header($lang->pages_pages_title, array('width' => '35%'));
	$table->construct_header($lang->pages_pages_name, array('width' => '20%'));
	$table->construct_header($lang->pages_pages_isenabled, array('width' => '15%'));
	$table->construct_header($lang->pages_pages_visible_s, array('width' => '15%'));
	$table->construct_header($lang->pages_pages_options, array('width' => '15%'));

	$query = $db->simple_select("pages_pages", "id, title, name, enabled, visible, is_default", "", array('order_by' => 'is_default DESC, title ASC, id ASC'));
	if ($db->num_rows($query) == 0) {
		$table->construct_cell($lang->pages_pages_nopages, array('colspan' => 5));
		$table->construct_row();
	} 
	else {
		$has_default = false;
		$groupscache = $cache->read("usergroups");
		while ($mypage = $db->fetch_array($query)) {
			$is_default = ((int)$mypage['is_default'] === 1);
			if ($is_default) {
				$has_default = true;
				$default_indicator = " <em class='red'>*</em>";
			} 
			else {
				$default_indicator = "";
			}
			$title = "<a href=\"index.php?module=pages/info&amp;action=editpage&amp;id=".(int)$mypage['id']."\">" . htmlspecialchars_uni($mypage['title']) . "</a>" . $default_indicator;
			$title .= "<div style='padding-top:1%'><a href='{$mybb->settings['bburl']}/home.php?page={$mypage['name']}' target='_blank'>View Page </a> {$mybb->settings['bburl']}/home.php?page={$mypage['name']}</div>";
			$enabled = ($mypage['enabled'] == "1") ? "<span class='green'>".$lang->pages_pages_yes."</span>" : "<span class='red'>".$lang->pages_pages_no."</span>";
			$delete_page = htmlspecialchars_uni($mypage['title']);
			$buttons = "<form method=\"post\" action=\"index.php?module=pages/info\">";
			$buttons .= "<input type=\"hidden\" name=\"my_post_key\" value=\"" . $mybb->post_code . "\">";
			$buttons .= "<input type=\"hidden\" name=\"id\" value=\"" . (int)$mypage['id'] . "\">";
			$buttons .= "<input type=\"hidden\" name=\"action\" id=\"page_action\" value=\"\">";
			if ($mypage['enabled'] == "1") {
				$buttons .= "<button class='popup_button button-disable' type=\"submit\" onclick=\"this.form.elements['action'].value='disablepage';\">Disable</button>";
			} 
			else {
				$buttons .= "<button class='popup_button button-enable' type=\"submit\" onclick=\"this.form.elements['action'].value='enablepage';\">Enable</button>";
			}
			$buttons .= "<button class='popup_button button-delete' type=\"button\" onclick=\"var pageForm = this.form; MyBB.prompt(['Delete page', 'Are you sure you want to delete the $delete_page page?'], {buttons:[{title:'Yes',value:true},{title:'No',value:false}],submit:function(e,v){if(v === true){pageForm.elements['action'].value='deletepage';pageForm.submit();}}}); return false;\">";
			$buttons .= "Delete</button>";
			$buttons .= "</form>";
			$groups_array = explode(",", $mypage['visible']);
			$valid_groups = array();
			foreach ($groups_array as $usergroup_id) {
				$usergroup_id = (int)$usergroup_id;
				if (isset($groupscache[$usergroup_id])) {
					$valid_groups[] = $groupscache[$usergroup_id]['title'];
				}
			}
			if (count($valid_groups) > 0) {
				$groups_select = "<select class=\"text_input\">";
				$groups_select .= "<option selected>Visible to " . count($valid_groups) . " group(s)</option>";
				foreach ($valid_groups as $group_title) {
					$groups_select .= "<option>" . htmlspecialchars_uni($group_title) . "</option>";
				}
				$groups_select .= "</select>";
			} 
			else {
				$groups_select = "<span class='red'>No user groups can view</span>";
			}


			// Build page row
			$table->construct_cell($title);
			$table->construct_cell(htmlspecialchars_uni($mypage['name'])); // do we need the internal name here ?
			$table->construct_cell($enabled);
			$table->construct_cell($groups_select);
			$table->construct_cell($buttons);
			$table->construct_row();
		}
	}
	$table->output($lang->pages_page_management);
	echo  " <em class='red' style='padding-left:10px;'>*</em> &nbsp;Default page";
	$page->output_footer();
}

function home_view_tab_blocks_list($sub_tabs) {
	global $page, $db, $lang, $cache, $mybb, $plugins, $templates;

	$page->extra_header = '<link rel="stylesheet" href="styles/sitepages.css" />';
	$js_templates = array();
	foreach (array('modal', 'modal_button') as $template) {
		eval('$js_templates[$template] = "'.$templates->get($template, 1, 0).'";');
	}
	$page->extra_header .= "
		<script type=\"text/javascript\">
			var templates = {
			modal: ".json_encode($js_templates['modal']).",
			modal_button: ".json_encode($js_templates['modal_button'])."
			};
		</script>";
	$page->add_breadcrumb_item($lang->pages_block_management, "");
	$page->output_header($lang->pages_block_management);
	$page->output_nav_tabs($sub_tabs, 'blocks');

	$plugins->run_hooks("admin_pages_blocks_start");

	$table = new Table();
	$table->construct_header($lang->pages_blocks_title, array('width' => '25%'));
	$table->construct_header('Used On', array('width' => '25%'));
	$table->construct_header($lang->pages_blocks_enabled, array('width' => '10%'));
	$table->construct_header($lang->pages_blocks_visible_s, array('width' => '20%'));
	$table->construct_header($lang->pages_blocks_options, array('width' => '20%'));

	// Fetch all blocks.
	$query = $db->simple_select(
		'pages_blocks',
		'id, title, enabled, visible',
		'',
		array('order_by' => 'title', 'order_dir' => 'ASC')
	);

	$blocks = home_fetch_all($query);

	if (empty($blocks)) {
		$table->construct_cell($lang->pages_blocks_noblock, array('colspan' => 5));
		$table->construct_row();
	} else {
		// Build a list of pages using each block.
		$used_on = array();

		$query = $db->query("
			SELECT DISTINCT l.block_id, p.id, p.title
			FROM " . TABLE_PREFIX . "pages_layout l
			INNER JOIN " . TABLE_PREFIX . "pages_pages p ON p.id = l.page_id
			ORDER BY p.title ASC
		");

		$page_rows = home_fetch_all($query);

		foreach ($page_rows as $page_row) {
			$block_id = (int)$page_row['block_id'];
			$page_id = (int)$page_row['id'];
			$page_title = htmlspecialchars_uni($page_row['title']);

			$used_on[$block_id][] = "<a href=\"index.php?module=pages/info&amp;action=editpage&amp;id={$page_id}\">{$page_title}</a>";
		}

		// Read usergroups once for the visibility display.
		$groupscache = $cache->read("usergroups");

		foreach ($blocks as $block) {
			$block_id = (int)$block['id'];
			$block_title = htmlspecialchars_uni($block['title']);

			// Block title links to the edit form.
			$title = "<a href=\"index.php?module=pages/info&amp;action=editblock&amp;id={$block_id}\">{$block_title}</a>";

			// Show pages using this block.
			if (!empty($used_on[$block_id])) {
				$used_pages = implode(', ', $used_on[$block_id]);
			} else {
				$used_pages = '<em>Not used</em>';
			}

			// Enabled status.
			$enabled = ((int)$block['enabled'] === 1);

			if ($enabled) {
				$status = "<span class='green'>{$lang->pages_pages_yes}</span>";
				$action = 'disableblock';
				$action_label = 'Disable';
				$button_class = 'button-disable';
			} else {
				$status = "<span class='red'>{$lang->pages_pages_no}</span>";
				$action = 'enableblock';
				$action_label = 'Enable';
				$button_class = 'button-enable';
			}

			// Build visibility display.
			$groups_array = explode(',', $block['visible']);
			$valid_groups = array();

			foreach ($groups_array as $usergroup_id) {
				$usergroup_id = (int)$usergroup_id;

				if (isset($groupscache[$usergroup_id])) {
					$valid_groups[] = $groupscache[$usergroup_id]['title'];
				}
			}

			if (!empty($valid_groups)) {
				$groups_select = "<select class=\"text_input\">";
				$groups_select .= "<option selected>Visible to " . count($valid_groups) . " group(s)</option>";

				foreach ($valid_groups as $group_title) {
					$groups_select .= "<option>" . htmlspecialchars_uni($group_title) . "</option>";
				}

				$groups_select .= "</select>";
			} else {
				$groups_select = "<span class='red'>No user groups can view</span>";
			}

			// Build action controls.
			$buttons = "<form method=\"post\" action=\"index.php?module=pages/info\">";
			$buttons .= "<input type=\"hidden\" name=\"my_post_key\" value=\"{$mybb->post_code}\">";
			$buttons .= "<input type=\"hidden\" name=\"id\" value=\"{$block_id}\">";
			$buttons .= "<input type=\"hidden\" name=\"action\" value=\"\">";

			$buttons .= "<button class=\"popup_button {$button_class}\" type=\"submit\" onclick=\"this.form.elements['action'].value='{$action}';\">{$action_label}</button>";

			$buttons .= "<button class=\"popup_button button-delete\" type=\"button\" onclick=\"var blockForm=this.form; MyBB.prompt(['Delete block', 'Are you sure you want to delete this block?'], {buttons:[{title:'Yes',value:true},{title:'No',value:false}],submit:function(e,v){if(v === true){blockForm.elements['action'].value='deleteblock';blockForm.submit();}}}); return false;\">Delete</button>";

			$buttons .= "</form>";

			// Build block row.
			$table->construct_cell($title);
			$table->construct_cell($used_pages);
			$table->construct_cell($status);
			$table->construct_cell($groups_select);
			$table->construct_cell($buttons);
			$table->construct_row();
		}
	}

	$table->output($lang->pages_block_management);

	$page->output_footer();
}

// ==========================================
// 3. SECURE SUBMISSION / ACTION HANDLERS
// ==========================================
/**
 * Action Controller: Securely modifications existing sidebar blocks records
 */
 function home_action_block_form(int $id = 0) {
	global $db, $mybb, $page, $lang, $sub_tabs, $plugins;
	$page->extra_header = '<link rel="stylesheet" href="modules/pages/sitepages.css" />';
	$page->extra_header .= '<script type="text/javascript" src="modules/pages/sitepages.js"></script>';
	home_load_codemirror();
	$is_new = ($id < 1); // ready for combined form
	if ($is_new) {
		$block_data = array(
			'name' => '',
			'title' => '',
			'show_title' => 1,
			'content' => '',
			'block' => '',
			'enabled' => 0,
			'visible' => '2,3,4,5,6'
		);
		
	} 
	else {
		$query = $db->simple_select('pages_blocks', '*', "id='{$id}'");
		$block_data = $db->fetch_array($query);
		if (!$block_data) {
			flash_message('Invalid Block', 'error');
			admin_redirect("index.php?module=pages/info&action=blocks");
		}
		
}

	
	
	$query = $db->simple_select('usergroups', 'gid, title');
	$usergroup_rows = home_fetch_all($query);
	$usergroups = array();
	foreach ($usergroup_rows as $usergroup) {
		$usergroups[$usergroup['gid']] = $usergroup['title'];
	}
	$blockenabled_no = array();
	$blockenabled_yes = array();
	$titleenabled_no = array();
	$titleenabled_yes = array();
	if ($block_data['enabled'] == "0") {
		$blockenabled_no['checked'] = true;
	} 
	else {
		$blockenabled_yes['checked'] = true;
	}
	if($block_data['show_title'] == "0"){
		$titleenabled_no['checked'] = true;
	}
	else {
		$titleenabled_yes['checked'] = true;
	}
	$visible = $block_data['visible'];

	if (!is_array($visible)) {
		$visible = explode(',', $visible);
	}
	$visible = array_filter(array_map('intval', $visible));
	$visible_field = '
		<div class="group-dropdown" id="visible_dropdown">
		<button type="button" class="group-dropdown-toggle"
			onclick="toggleGroupDropdown()">
			Select usergroups &#9662;
		</button>
		<div class="group-dropdown-content" id="visible_options">';

	foreach ($usergroups as $gid=>$usergroup) {
		$gid = (int) $gid;
		$title = $usergroup;
		$checked = in_array($gid, $visible, true) ? ' checked' : '';
		$visible_field .= '
			<label>
				<input type="checkbox" name="visible[]"
				value="'.$gid.'"'.$checked.'>
				'.htmlspecialchars_uni($title).'
			</label>';
	}
	$visible_field .= ' </div></div>';
	// find blocks in pages
	$query = $db->query("
		SELECT DISTINCT p.id, p.title
		FROM ".TABLE_PREFIX."pages_pages p
		INNER JOIN ".TABLE_PREFIX."pages_layout l
		ON l.page_id = p.id
		WHERE l.block_id = '{$id}'
		ORDER BY p.title"
	);
	$pages = home_fetch_all($query);
	if (count($pages) > 0) {
		$page_select = "<b>{$lang->pages_blocks_enabled_in}</b>&nbsp;&nbsp;<select class='text_input' id='page_select'>";
		$page_select .= '<option selected>Select a page</option>';

		foreach ($pages as $row) {
			$page_select .= '<option value="'.(int) $row['id'].'">'
				.htmlspecialchars_uni($row['title']).'</option>';
		}
		$page_select .= '</select>';
	} 
	else {
		$page_select = "<div class='block-field-offset'><b>Currently</b> <span class='red'>Unused</span></div>";
	}
	$do = $is_new ? 'newblock' : 'editblock';
	$form_action = $is_new ? 'addblock' : 'editblock';
	$plugins->run_hooks("admin_pages_blocks_$do"); // hook needs to run after we have valid data
	home_load_codemirror();
	$heading_key = $is_new ? 'pages_blocks_addblock' : 'pages_blocks_editblock';
	//echo "$heading_key";
	$heading = $lang->$heading_key;
	//die($heading);
	$page->add_breadcrumb_item($heading);
	$page->output_header($heading);
	$page->output_nav_tabs($sub_tabs, 'blocks');
	$form = new Form("index.php?module=pages/info&action={$form_action}", "post");
	$name = "<span style='padding-right:7%;'><b>Name</b></span>";
	$name .= $is_new ? $form->generate_text_box('name', $block_data['name'], array('id' => 'name','class' => 'name-input')) : htmlspecialchars_uni($block_data['name']);
	$html_content = "<div style='width:50%;float:left;box-sizing:border-box;padding-right:8px;'><b>Page HTML Markup</b><div style='font-weight:normal; font-size: 11px; color:#555; padding-bottom:4px;'>This forms the primary rendering container layout area of your public page.</div>" . $form->generate_text_area('content', $block_data['content'], array('id' => 'page_html', 'style' => 'width: 90%; height: 250px;'))."</div>";
    $php_field =  "<div style='width:50%;float:left;box-sizing:border-box;padding-right:8px;'><b>PHP Code</b><div style='font-weight:normal; font-size: 11px; color:#555; padding-bottom:4px;'>php code that is used with HTML markup</div>".$form->generate_text_area('block', $block_data['block'], array('id' => 'page_php','style' => 'width: 90%; height: 250px;'))."</div>";
	echo $form->generate_hidden_field('my_post_key', $mybb->post_code);
    echo $form->generate_hidden_field('return_url',$_SERVER['QUERY_STRING']);
    echo $form->generate_hidden_field('do',$do);
    echo  $form->generate_hidden_field('id',$id); // this will be 0 on add
	$form_container = new FormContainer($heading); // conditional
	$title = "<span><b>{$lang->pages_blocks_title}</b> <em class='red'>*</em></span><span style='padding-left:10px'>".$form->generate_text_box('title', $block_data['title'], array('id' => 'title','style' => 'width:fit-content;'), 'title')."</span>";
	$enabled = "<span><b>{$lang->pages_blocks_enabled}</b></span><span style='padding-left:10px'>". $form->generate_radio_button('enabled', '1', $lang->pages_blocks_yes, $blockenabled_yes)."&nbsp;&nbsp;&nbsp;&nbsp;".$form->generate_radio_button('enabled', '0', $lang->pages_blocks_no, $blockenabled_no)."</span>";
	$title_enabled = "<span><b>{$lang->pages_title_enabled}</b></span><span style='padding-left:10px'>". $form->generate_radio_button('show_title', '1', $lang->pages_blocks_yes, $titleenabled_yes)."&nbsp;&nbsp;&nbsp;&nbsp;".$form->generate_radio_button('show_title', '0', $lang->pages_blocks_no, $titleenabled_no)."</span>";
	
	$row ="<div class='block-form-details'>
    <div class='block-field'>
        $title
    </div>";
	if($do == "newblock"){
		$row .="<div class='block-field' >
        $name
		</div>";
	}

    $row.= "<div class='block-field'>
        $page_select
    </div>

    <div class='block-field block-field-offset'>
        $enabled
    </div>

    <div class='block-field block-field-offset'>
        $title_enabled
    </div>

    <div class='block-field'>
       $visible_field
    </div>
</div>";
	//$form_container->output_row("", "", "<div style='float:left;width:15%'>$title</div><div style='float:left;width:15%'>$name</div><div style='float:left;width:10%'><div style='float:left;width:10%'> $page_select</div><div style='float:left;width:15%;padding-top:7px;'>$enabled</div><div style='float:left;width:15%;padding-top:7px;'>$title_enabled</div><div style='float:left;padding-left:2%;'>$visible_field</div> ", '');
	$form_container->output_row("","",$row,'');
	$form_container->output_row("","","$html_content $php_field");
	$form_container->end();
	//$reset_url = "index.php?module=portal/blocks&amp;action=reset&amp;id={$block_data['id']}"; // needs changing
	$buttons[] = $form->generate_submit_button($heading); // conditional
	//$buttons[] = "<a href=\"{$reset_url}\" title='reset the current block to default'><input type=\"button\" class='submit_button' value=\"Reset Block Code \" /></a>"; // don't think we need this
	$form->output_submit_wrapper($buttons);
	$form->end();


	$page->output_footer();
	exit;
}

function home_action_set_status($table, $enabled, $tab){
	global $mybb, $db;
    $id = (int) $mybb->input['id'];
    $enabled = (int) $enabled;
    $allowed_tables = array('pages_pages', 'pages_blocks');
    if($id > 0 && in_array($table, $allowed_tables, true) && in_array($enabled, array(0, 1), true)){
		$db->update_query($table,array('enabled' => $enabled),"id='{$id}'");
    } 
      admin_redirect("index.php?module=pages/info&tab=$tab");
}

function home_action_delete_object($table,$tab){
	global $mybb, $db;
	
    $id = (int) $mybb->input['id'];
    $allowed_tables = array('pages_pages', 'pages_blocks');
    $allowed_tabs = array('pages', 'blocks');
    if($id > 0 && in_array($table, $allowed_tables, true) && in_array($tab, $allowed_tabs, true)){
        /* Delete database records
         * check if this a page or block
        */    
        switch ($table){
			case "pages_pages":
				$db->delete_query('pages_layout', "page_id='{$id}'");
				$db->delete_query('pages_pages', "id='{$id}'");
				break;
			case "pages_blocks":
				$db->delete_query('pages_layout', "block_id='{$id}'");
				$db->delete_query('pages_blocks', "id='{$id}'");
				break;
		}			
    }

    admin_redirect("index.php?module=pages/info&tab=$tab");
}
 
function home_action_page_form(int $id = 0){
	global $db, $mybb, $page, $lang, $sub_tabs;
	 $page->extra_header = '<link rel="stylesheet" href="modules/pages/sitepages.css" />';
	 $page->extra_header .= '<script type="text/javascript" src="modules/pages/sitepages.js"></script>';
	 home_load_codemirror();
	$is_new = ($id < 1);

	if ($is_new) {
		$page_data = array(
			'id'          => 0,
			'title'       => '',
			'name'        => '',
			'content'     => '',
			'php'         => '',
			'enabled'     => 1,
			'php_enabled' => 0,
			'show_blocks' => 0,
			'visible'     => '1,2,3,5,6,7',
			'is_default'  => 0
		);
		$do = 'newpage';
		$button_text = $lang->pages_add_page;
		$page_title = $lang->pages_add_page;
	} 
	else {
		$id = (int) $id;
		$query = $db->simple_select('pages_pages', '*', "id='{$id}'");
		$page_data = $db->fetch_array($query);

		if (!$page_data) {
			return false;
		}
		$do = 'savepage';
		$button_text = $lang->pages_save_page;
		$page_title = $lang->pages_pages_edit ." - {$page_data['title']}";
	}

	$action = $is_new ? 'addpage' : 'editpage';
	// Build the form using the page data
	$enabled_yes = ($page_data['enabled'] == 1);
	$enabled_no = !$enabled_yes;
	$php_yes = ($page_data['php_enabled'] == 1);
	$php_no = !$php_yes;
	$default_yes = ($page_data['is_default'] == 1);
	$default_no = !$default_yes;
	$query = $db->simple_select('usergroups', 'gid, title');
	$usergroup_rows = home_fetch_all($query);
	foreach ($usergroup_rows as $usergroup) {
		$usergroups[$usergroup['gid']] = $usergroup['title'];
	}
	//die(print_r($usergroups));
	$visible = trim((string) $block_data['visible']);
	if ($visible !== '') {
		$visible = array_map('intval', explode(',', $visible));
	} 
	else {
		$visible = array();
	}
	$visible_field = '
		<div class="group-dropdown" id="visible_dropdown">
		<button type="button" class="group-dropdown-toggle"
			onclick="toggleGroupDropdown()">
			Select usergroups &#9662;
		</button>
		<div class="group-dropdown-content" id="visible_options">';

	foreach ($usergroups as $gid=>$usergroup) {
		$gid = (int) $gid;
		$title = $usergroup;
		$checked = in_array($gid, $visible, true) ? ' checked' : '';
		$visible_field .= '
			<label>
				<input type="checkbox" name="visible[]"
				value="'.$gid.'"'.$checked.'>
				'.htmlspecialchars_uni($title).'
			</label>';
	}
	$visible_field .= ' </div></div>';
	$page->add_breadcrumb_item($page_title);
	$page->output_header($lang->pages_pages_edit); 
	$page->output_nav_tabs($sub_tabs, 'pages');
	$form = new Form("index.php?module=pages/info&action={$action}", "post");
    echo $form->generate_hidden_field('my_post_key', $mybb->post_code);
    echo $form->generate_hidden_field('return_url',$_SERVER['QUERY_STRING']);
    echo $form->generate_hidden_field('do',$do);
    echo  $form->generate_hidden_field('id',$id); // this will be 0 on add
	$form_container = new FormContainer($page_title);
    $title_field = "<div class='label'>{$lang->pages_pages_title} <em class='red'>*</em></div><div class='control'>" . $form->generate_text_box('title', $page_data['title'], array('id' => 'title', 'style'=>'width:90%;')) . "</div>";
    $slug_field = "<span class='label'>{$lang->pages_pages_name} <em class='red'>*</em></span><div class='control'>" . $form->generate_text_box('name', $page_data['name'], array('id' => 'name', 'style'=>'width:60%;')) . "</div>";
    $enabled_field = "<span class='label-option'>{$lang->pages_pages_isenabled}</span><div class='control-option'>" . $form->generate_radio_button('enabled', '1', $lang->pages_pages_yes, array('checked' => $enabled_yes)) . "" . $form->generate_radio_button('enabled', '0', $lang->pages_pages_no, array('checked' => $enabled_no)) . "</div>";
    $default_field = "<span class='label-option'>{$lang->pages_default}</span><div class='control-option'>" . $form->generate_radio_button('is_default', '1', "Yes", array('checked' => $default_yes)) . "" . $form->generate_radio_button('is_default', '0', "No", array('checked' => $default_no)) . "</div>";
    $form_container->output_row("", "", "<div style='float:left;width:20%'>$title_field</div><div style='float:left;width:20%'>$slug_field</div><div style='float:left;width:15%'>$enabled_field</div><div style='float:left;width:15%'>$default_field</div><div style='float:left;width:15%'>$visible_field</div>", '');
    $html_content = "<div style='width:50%;float:left;box-sizing:border-box;padding-right:8px;'><b>Page HTML Markup</b><div style='font-weight:normal; font-size: 11px; color:#555; padding-bottom:4px;'>This forms the primary rendering container layout area of your public page.</div>" . $form->generate_text_area('content', $page_data['content'], array('id' => 'page_html', 'style' => 'width: 90%; height: 250px;'))."</div>";
    $php_field =  "<div style='width:50%;float:left;box-sizing:border-box;padding-right:8px;'><b>PHP Code</b><div style='font-weight:normal; font-size: 11px; color:#555; padding-bottom:4px;'>php code that is used with HTML markup</div>".$form->generate_text_area('php', $page_data['php'], array('id' => 'page_php','style' => 'width: 90%; height: 250px;'))."</div>";
    $form_container->output_row("","","$html_content $php_field");
    $form_container->end();
	$layout_button = "<button type='submit' class='button' name='manage_layout' value='1'>{$lang->pages_manage_layout}</button>";
	$buttons[] = $layout_button;
    $buttons[] = $form->generate_submit_button($button_text);
    $form->output_submit_wrapper($buttons);
    $form->end(); 
    echo '<div id="page_layout_modal" class="modal-container" style="display:none;">
				<div class="modal-content">
					<!-- AJAX content goes here -->
				</div>
			</div>';
	if ((int) $mybb->get_input('open_layout') === 1) {
		echo "<script>
			jQuery(function($) {
				home_page_layout({$id});
			});
		</script>";
	}
	 $page->output_footer();
}
   
function home_page_layout( int $page_id ){
	if ($page_id < 1) {		
		return false;
	}
	global $mybb, $db, $page, $lang, $cache,$plugins;
	$query = $db->simple_select('pages_blocks', '*');
	$available_blocks = home_fetch_all($query); // build a multi select from this data
	//print_r($available_blocks);
	$a_blocks ='<select id="available_blocks" multiple size="7">';
	foreach($available_blocks as $block){
		$title = htmlspecialchars_uni($block['title']);
		$a_blocks.= "<option value='{$block["id"]}'>{$title}</option>";
	}
	$a_blocks .="</select>";
	$query = $db->query("SELECT l.*, b.title FROM ".TABLE_PREFIX."pages_layout l    LEFT JOIN ".TABLE_PREFIX."pages_blocks b ON (l.block_id = b.id) WHERE l.page_id = '{$page_id}'  ORDER BY l.zone, l.display_order");
	$page_blocks = home_fetch_all($query); 
	$c_blocks = '<select id="current_blocks" name="blocks[]" multiple size="7">';
	foreach ($page_blocks as $block) {
		switch ($block['zone']){
			case 0:
				$zone = "Top";
				break;
			case 1:
				$zone = "Left";
				break;
			case 2:
				$zone = "Right";
				break;
			case 3:
				$zone = "Bottom";
				break;
		}			
		$title = htmlspecialchars_uni($block['title']);
		$c_blocks .= "<option value='{$block['block_id']}' data-zone='{$block['zone']}' data-title='{$title}'>{$title} ({$zone})</option>";
	}
	$c_blocks .="</select>";
	// start the from
	$zone_blocks = '<select id="zone_blocks" size="7">';
	$zone_blocks .= '<option value="" disabled selected>No Blocks defined</option>';
	$zone_blocks .= '</select>';
	$form = new Form("index.php?module=pages/info&action=layout&id=$page_id", "post","layout_form",array('id' =>'layout_form'));
	
	ob_start();
	$radio_options = array(
		$form->generate_radio_button("zone", 0, "Top", array("checked" => true)),
		$form->generate_radio_button("zone", 1, "Left", array("checked" => false)),
		$form->generate_radio_button("zone", 2, "Right", array("checked" => false)),
		$form->generate_radio_button("zone", 3, "Bottom", array("checked" => false))
	);
	echo $form->generate_hidden_field('my_post_key', $mybb->post_code);
	echo $form->generate_hidden_field('return_url',$return_url);
	echo $form->generate_hidden_field('do','savelayout');
	echo  $form->generate_hidden_field('id',$page_id);
	$test = $_SERVER['QUERY_STRING'];
	$zone_radios = implode('',$radio_options);
	$form_container = new FormContainer(""); // title the form however this should be titled at the modal & not here the title should be removed	
	$form_container->output_row("","","<div><div style='float:left'><b>Select Zone To Edit</b></div><div style='float:left'>$zone_radios</div></div>",'');
	$a = "<div style='float:left;width:33%;'><span style='float:left;padding-bottom: 2%;padding-right:2%;'><b>Available Blocks</b></span> $a_blocks</div>";
	$b ="<div style='float:left;width:33%'><span style='float:left;padding-right:2%;'><b>Current Page Blocks</b><br></span> $c_blocks</div>";
	$zone_controls = "
	<div id='zone-controls'>
		<button type='button' id='move-up'>Move Up</button>
		<button type='button' id='move-down'>Move Down</button>
		<button type='button' id='remove-block'>Remove</button>
	</div>
";

$c = "
	<div style='float:left;width:33%;' id='selected-zone'>
		<span style='float:left;padding-right:4%;'><b>Selected Zone</b></span>
		<div style='float:left;padding-bottom:2%;'>
			$zone_blocks
		</div>
		<div style='margin-top:1%;text-align:center;clear:both;'>	
			$zone_controls
		</div>	
		</div>
	</div>
";
	
	$form_container->output_row("","","{$b}{$a}{$c}",'');
	 //display  the form table
	 $form_container->end();
	 $buttons[] = $form->generate_submit_button($lang->pages_save_layout);
	 $form->output_submit_wrapper($buttons);
	 $form->end();
	 echo '<script>
		var page_blocks = '.json_encode($page_blocks).';
		</script>';
	$layout_html = ob_get_clean();
	return $layout_html;
}

function home_page_layout_modal() {
	// display the modal
	global $mybb;
	$page_id = (int)$mybb->get_input('id');
	$layout = home_page_layout($page_id, );
	$content = "
        <div class='modal'>
            <div class='thead'>
                Page Layout
                 <button type='button' class='modal-close' onclick='home_close_layout_modal()'>&times;</button>
            </div>
            <div class='tborder'>
                <div style='padding:15px;'>
                   $layout
                </div>
            </div>
        </div>
    ";

	echo $content;
}
	
function home_save_object($data) {
	//die('<pre>' . print_r($data, true) . '</pre>');
	global  $db, $mybb;
	$db_type = $mybb->config['database']['type']; // get the database type, ready for transactions
	$id = isset($data['id']) ? (int)$data['id'] : 0;
	$visible = isset($data['visible']) && is_array($data['visible']) ? implode(",", array_map('intval', $data['visible'])) : '';
	// using the mybb default 'do'  this is set in the form
	$return_url = "index.php?module=pages/info";
	switch ($data['do']) {
		case "newpage":
			$title = isset($data['title']) ? trim($data['title']) : '';
			$name = isset($data['name']) ? trim($data['name']) : '';
			if ($title === '' || $name === '') {
				flash_message('Both page title and page name are required.', 'error');
				admin_redirect("index.php?module=pages/info&action=addpage");
			}
			if (!$db->table_exists('pages_pages')) {
				flash_message('Site Pages database table is missing. Please check the plugin installation.', 'error');
				admin_redirect($return_url);
			}
			$name_sql = $db->escape_string($name);
			$query = $db->simple_select('pages_pages', 'name', "name='{$name_sql}'");
			$dup = $db->fetch_field($query, 'name');
			if(!empty($dup)){
				flash_message("This page name already exists. $dup", 'error');
				admin_redirect("index.php?module=pages/info&action=addpage");
			}
			$insert_data = array(
				'name'  => $db->escape_string($name),
				'title' => $db->escape_string($title),
				'content'     => $db->escape_string($data['content']),
				'php'         => $db->escape_string($data['php']),
				'enabled'     => (int) $data['enabled'],
				'visible'     => $db->escape_string($visible),
				'is_default'  => (int) $data['is_default']
			);
			if ((int) $data['is_default'] === 1) {
				$db->update_query(
					'pages_pages',
					array('is_default' => 0),
					"is_default='1'"
				);
			}
			$db->insert_query(
				'pages_pages',
				$insert_data
			);
			$id = (int) $db->insert_id();
			if ($id < 1) {
				flash_message('Unable to create the page.', 'error');
				admin_redirect($return_url);
			}
			if (isset($data['manage_layout'])) {
				$redirect_url = "index.php?module=pages/info&action=editpage&id={$id}&open_layout=1";
				admin_redirect($redirect_url);
			}
			flash_message("{$data['title']} saved successfully", 'success');
			$redirect_url = "index.php?module=pages/info&action=editpage&id={$id}";
			admin_redirect($redirect_url);
			break;
			
		case "savepage":
			$return_url = "index.php?".$data['return_url'];
			$update_data = array(
				'name'        => $db->escape_string($data['name']),
				'title'       => $db->escape_string($data['title']),
				'content'     => $db->escape_string($data['content']),
				'php'         => $db->escape_string($data['php']),
				'enabled'     => (int) $data['enabled'],
				'visible'     => $db->escape_string($visible),
				'is_default'  => (int) $data['is_default']
			);
			if ($id < 1) {
				flash_message('Invalid page ID.', 'error');
				admin_redirect($return_url);
			}
			if (!$db->table_exists('pages_pages')) {
				flash_message('Site Pages database table is missing. Please check the plugin installation.', 'error');
				admin_redirect($return_url);
			}
			if ((int) $data['is_default'] === 1) {
				$db->update_query(
					'pages_pages',
					array('is_default' => 0),
					"is_default='1'"
				);
			}
			$db->update_query(
				'pages_pages',
				$update_data,
				"id='{$id}'"
			);
			if (isset($data['manage_layout'])) {
				$redirect_url = "index.php?module=pages/info&action=editpage&id={$id}&open_layout=1";
				//flash_message("Page saved successfully", 'success');
				admin_redirect($redirect_url);
			}
			flash_message("{$data['title']} saved successfully", 'success');
			admin_redirect("index.php?module=pages/info&action=editpage&id={$id}");

			// no break
		case "savelayout":
			//die(print_r($data));
			$return_url = "index.php?module=pages/info&action=editpage&id=$id";
			$blocks = isset($data['blocks']) && is_array($data['blocks'])	? $data['blocks']: array();
			$zones = isset($data['zones']) && is_array($data['zones'])? $data['zones']: array();
			$blocks = array_values($blocks);
			$zones = array_values($zones);
			if ($id < 1 || !$db->table_exists('pages_layout')) {
				flash_message('Invalid page or missing layout table.', 'error');
				admin_redirect($return_url);
			}
			if (!$db->fetch_field($db->simple_select('pages_pages', 'id', "id='{$id}'"),'id')) {
				flash_message('Invalid page ID.', 'error');
				admin_redirect($return_url);
			}
			if (count($blocks) !== count($zones)) {
				flash_message('Corrupt Layout Data.', 'error');
				admin_redirect($return_url);
			}
			$valid_zones = array(0, 1, 2, 3);
			foreach ($zones as $zone) {
				if (filter_var($zone, FILTER_VALIDATE_INT) === false || !in_array((int) $zone, $valid_zones, true)) {
					flash_message('Invalid Zone.', 'error');
					admin_redirect($return_url);
				}
			}
			
			$query = $db->simple_select('pages_blocks', 'id');
			$valid_blocks = array_map('intval',	array_column(home_fetch_all($query), 'id'));
			foreach ($blocks as $block) {
				if (filter_var($block, FILTER_VALIDATE_INT) === false || !in_array((int) $block, $valid_blocks, true)) {
					flash_message('Invalid Block.', 'error');
					admin_redirect($return_url);
				}
			}
			$assignments = array();
			foreach ($blocks as $key => $block) {
				$assignment = (int) $block . ':' . (int) $zones[$key];
				if (in_array($assignment, $assignments, true)) {
					flash_message('Duplicate block assignment.', 'error');
					admin_redirect($return_url);
				}
				$assignments[] = $assignment;
			}
			// Delete existing layout and insert submitted assignments here.
			$db->write_query("BEGIN"); // start db transaction
			$db->delete_query('pages_layout', "page_id='{$id}'"); // remove all data
			$display_order = array(0, 0, 0, 0);
			$inserted_layout = array();
			foreach ($blocks as $key => $block) {
				$zone = (int) $zones[$key];
				$insert_data = array(
					'page_id' => $id,
					'block_id' => (int) $block,
					'zone' => $zone,
					'display_order' => $display_order[$zone]
				);
				$db->insert_query('pages_layout', $insert_data);
				unset($insert_data['page_id']);
				$inserted_layout[] = $insert_data;
				$display_order[$zone]++;
			}
			$query = $db->simple_select(
				'pages_layout',
				'block_id, zone, display_order',
				"page_id='{$id}'",
				array('order_by' => 'zone, display_order')
			);

			$saved_layout = home_fetch_all($query);
			$saved_layout = array_map(function($row) {
				return array(
					'block_id' => (int) $row['block_id'],
					'zone' => (int) $row['zone'],
					'display_order' => (int) $row['display_order']
				);
			}, $saved_layout);

			usort($inserted_layout, function($a, $b) {
				return [$a['zone'], $a['display_order']] <=> [$b['zone'], $b['display_order']];	
			});

		if ($saved_layout !== $inserted_layout) {
			$db->write_query("ROLLBACK");
			flash_message('Layout verification failed.', 'error');
			admin_redirect($return_url);
		}
		$db->write_query("COMMIT");
			flash_message('Layout successfully saved.', 'success');
			admin_redirect($return_url);
			break;
		case "newblock":
		
			$title = isset($data['title']) ? trim($data['title']) : '';
			$name = isset($data['name']) ? trim($data['name']) : '';
			if ($title === '' || $name === '') {
				flash_message('Both block title and block name are required.', 'error');
				admin_redirect("index.php?module=pages/info&action=addblock");
			}
			if (!$db->table_exists('pages_blocks')) {
				flash_message('Site Pages database table is missing. Please check the plugin installation.', 'error');
				admin_redirect($return_url);
			}
			$name_sql = $db->escape_string($name);
			$query = $db->simple_select('pages_blocks', 'name', "name='{$name_sql}'");
			$dup = $db->fetch_field($query, 'name');
			if (!empty($dup)) {
				flash_message("This block name already exists. $dup", 'error');
				admin_redirect("index.php?module=pages/info&action=addblock");
			}
			$insert_data = array(
				'name' => $db->escape_string($name),
				'title' => $db->escape_string($title),
				'show_title' => isset($data['show_title']) ? (int) $data['show_title'] : 0,
				'content' => $db->escape_string($data['content']),
				'block' => $db->escape_string($data['block']),
				'enabled' => isset($data['enabled']) ? (int) $data['enabled'] : 0,
				'visible' => $db->escape_string($visible)
			);
			$db->insert_query('pages_blocks', $insert_data);
			$id = (int) $db->insert_id();
			if ($id < 1) {
				flash_message('Unable to create the block.', 'error');
				admin_redirect("index.php?module=pages/info&action=addblock");
			}
			flash_message("{$title} saved successfully", 'success');
			$redirect_url = "index.php?module=pages/info&action=editblock&id={$id}";
			admin_redirect($redirect_url);
			break;
		case "editblock":
			if ($id < 1) {
				flash_message('Invalid block ID.', 'error');
				admin_redirect("index.php?module=pages/info&action=blocks");
			}

			if (!$db->table_exists('pages_blocks')) {
				flash_message('Site Pages database table is missing. Please check the plugin installation.', 'error');
				admin_redirect($return_url);
			}
			$title = isset($data['title']) ? trim($data['title']) : '';

			if ($title === '') {
				flash_message('Block title is required.', 'error');
				admin_redirect("index.php?module=pages/info&action=editblock&id={$id}");
			}

			$query = $db->simple_select('pages_blocks', 'id', "id='{$id}'");
			if (!$db->fetch_field($query, 'id')) {
				flash_message('Block does not exist.', 'error');
				admin_redirect("index.php?module=pages/info&action=blocks");
			}
			$update_data = array(
				'title' => $db->escape_string($title),
				'show_title' => isset($data['show_title']) ? (int) $data['show_title'] : 0,
				'content' => $db->escape_string($data['content'] ?? ''),
				'block' => $db->escape_string($data['block'] ?? ''),
				'enabled' => isset($data['enabled']) ? (int) $data['enabled'] : 0,
				'visible' => $db->escape_string($visible)
			);
			$db->update_query('pages_blocks', $update_data, "id='{$id}'");
			flash_message("{$title} saved successfully", 'success');
			admin_redirect("index.php?module=pages/info&action=editblock&id={$id}");
			break;
			
		default:
	}
}

// ==========================================
// 4. HELPER FUNCTIONS
// ==========================================
function home_fetch_all($query) {
	global $db;
	$rows = array();
	while ($row = $db->fetch_array($query)) {
		$rows[] = $row;
	}

	return $rows;
}

function home_load_codemirror() {
	global $page;
	static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

	$codemirror = '
        <link href="./jscripts/codemirror/lib/codemirror.css" rel="stylesheet">
        <link href="./jscripts/codemirror/theme/mybb.css?ver=1804" rel="stylesheet">

        <script src="./jscripts/codemirror/lib/codemirror.js"></script>
        <script src="./jscripts/codemirror/mode/xml/xml.js"></script>
        <script src="./jscripts/codemirror/mode/javascript/javascript.js"></script>
        <script src="./jscripts/codemirror/mode/css/css.js"></script>
        <script src="./jscripts/codemirror/mode/htmlmixed/htmlmixed.js"></script>
        <script src="./jscripts/codemirror/mode/clike/clike.js"></script>
        <script src="./jscripts/codemirror/mode/php/php.js"></script>

        <link href="./jscripts/codemirror/addon/dialog/dialog-mybb.css" rel="stylesheet">
        <script src="./jscripts/codemirror/addon/dialog/dialog.js"></script>
        <script src="./jscripts/codemirror/addon/search/searchcursor.js"></script>
        <script src="./jscripts/codemirror/addon/search/search.js?ver=1808"></script>
        <script src="./jscripts/codemirror/addon/fold/foldcode.js"></script>
        <script src="./jscripts/codemirror/addon/fold/xml-fold.js"></script>
        <script src="./jscripts/codemirror/addon/edit/matchbrackets.js"></script>
        <script src="./jscripts/codemirror/addon/edit/matchtags.js"></script>
        <script src="./jscripts/codemirror/addon/edit/closetag.js"></script>
        <script src="./jscripts/codemirror/addon/fold/foldgutter.js"></script>

        <link href="./jscripts/codemirror/addon/fold/foldgutter.css" rel="stylesheet">
    ';
	if (empty($page->extra_header)) {
		$page->extra_header = $codemirror;
	} else {
		$page->extra_header .= $codemirror;
	}

}
