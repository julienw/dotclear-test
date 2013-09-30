<?php

class adminModulesList
{
	public $core;
	public $modules;

	public static  $allow_multi_install;

	protected $list_id = 'unknow';

	protected $path = false;
	protected $path_writable = false;
	protected $path_pattern = false;

	protected $page_url = 'plugins.php';
	protected $page_qs = '?';
	protected $page_tab = '';

	public static $nav_indexes = 'abcdefghijklmnopqrstuvwxyz0123456789';
	protected $nav_list = array();
	protected $nav_special = 'other';

	protected $sort_field = 'sname';
	protected $sort_asc = true;

	public function __construct($core, $root, $allow_multi_install=false)
	{
		$this->core = $core;
		self::$allow_multi_install = (boolean) $allow_multi_install;
		$this->setPathInfo($root);
		$this->setNavSpecial(__('other'));

		$this->init();
	}

	protected function init()
	{
		return null;
	}

	public function newList($list_id)
	{
		$this->modules = array();
		$this->page_tab = '';
		$this->list_id = $list_id;

		return $this;
	}

	protected function setPathInfo($root)
	{
		$paths = explode(PATH_SEPARATOR, $root);
		$path = array_pop($paths);
		unset($paths);

		$this->path = $path;
		if (is_dir($path) && is_writeable($path)) {
			$this->path_writable = true;
			$this->path_pattern = preg_quote($path,'!');
		}

		return $this;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function isPathWritable()
	{
		return $this->path_writable;
	}

	public function isPathDeletable($root)
	{
		return $this->path_writable 
			&& preg_match('!^'.$this->path_pattern.'!', $root) 
			&& $this->core->auth->isSuperAdmin();
	}

	public function setPageURL($url)
	{
		$this->page_qs = strpos('?', $url) ? '&' : '?';
		$this->page_url = $url;

		return $this;
	}

	public function getPageURL($queries='', $with_tab=true)
	{
		return $this->page_url.
			(!empty($queries) ? $this->page_qs : '').
			(is_array($queries) ? http_build_query($queries) : $queries).
			($with_tab && !empty($this->page_tab) ? '#'.$this->page_tab : '');
	}

	public function setPageTab($tab)
	{
		$this->page_tab = $tab;

		return $this;
	}

	public function getPageTab()
	{
		return $this->page_tab;
	}

	public function getSearchQuery()
	{
		$query = !empty($_REQUEST['m_search']) ? trim($_REQUEST['m_search']) : null;
		return strlen($query) > 1 ? $query : null;
	}

	public function displaySearchForm()
	{
		$query = $this->getSearchQuery();

		if (empty($this->modules) && $query === null) {
			return $this;
		}

		echo 
		'<form action="'.$this->getPageURL().'" method="get" class="fieldset">'.
		'<p><label for="m_search" class="classic">'.__('Search in repository:').'&nbsp;</label><br />'.
		form::field(array('m_search','m_search'), 30, 255, html::escapeHTML($query)).
		'<input type="submit" value="'.__('Search').'" /> ';
		if ($query) { echo ' <a href="'.$this->getPageURL().'" class="button">'.__('Reset search').'</a>'; }
		echo '</p>'.
		'</form>';

		if ($query) {
			echo 
			'<p class="message">'.sprintf(
				__('Found %d result for search "%s":', 'Found %d results for search "%s":', count($this->modules)), 
				count($this->modules), html::escapeHTML($query)
				).
			'</p>';
		}
		return $this;
	}

	public function setNavSpecial($str)
	{
		$this->nav_special = (string) $str;
		$this->nav_list = array_merge(str_split(self::$nav_indexes), array($this->nav_special));

		return $this;
	}

	public function getNavQuery()
	{
		return isset($_REQUEST['m_nav']) && in_array($_REQUEST['m_nav'], $this->nav_list) ? $_REQUEST['m_nav'] : $this->nav_list[0];
	}

	public function displayNavMenu()
	{
		if (empty($this->modules) || $this->getSearchQuery() !== null) {
			return $this;
		}

		# Fetch modules required field
		$indexes = array();
		foreach ($this->modules as $id => $module) {
			if (!isset($module[$this->sort_field])) {
				continue;
			}
			$char = substr($module[$this->sort_field], 0, 1);
			if (!in_array($char, $this->nav_list)) {
				$char = $this->nav_special;
			}
			if (!isset($indexes[$char])) {
				$indexes[$char] = 0;
			}
			$indexes[$char]++;
		}

		$buttons = array();
		foreach($this->nav_list as $char) {
			# Selected letter
			if ($this->getNavQuery() == $char) {
				$buttons[] = '<li class="active" title="'.__('current selection').'"><strong> '.$char.' </strong></li>';
			}
			# Letter having modules
			elseif (!empty($indexes[$char])) {
				$title = sprintf(__('%d module', '%d modules', $indexes[$char]), $indexes[$char]);
				$buttons[] = '<li class="btn" title="'.$title.'"><a href="'.$this->getPageURL('m_nav='.$char).'" title="'.$title.'"> '.$char.' </a></li>';
			}
			# Letter without modules
			else {
				$buttons[] = '<li class="btn no-link" title="'.__('no module').'"> '.$char.' </li>';
			}
		}
		# Parse navigation menu
		echo '<div class="pager">'.__('Browse index:').' <ul>'.implode('',$buttons).'</ul></div>';

		return $this;
	}

	public function setSortField($field, $asc=true)
	{
		$this->sort_field = $field;
		$this->sort_asc = (boolean) $asc;

		return $this;
	}

	public function getSortQuery()
	{
		return !empty($_REQUEST['m_sort']) ? $_REQUEST['m_sort'] : $this->sort_field;
	}

	public function displaySortForm()
	{
		//not yet implemented
	}

	public function displayMessage($action)
	{
		switch($action) {
			case 'activate': 
				$str = __('Module successfully activated.'); break;
			case 'deactivate': 
				$str = __('Module successfully deactivated.'); break;
			case 'delete': 
				$str = __('Module successfully deleted.'); break;
			case 'install': 
				$str = __('Module successfully installed.'); break;
			case 'update': 
				$str = __('Module successfully updated.'); break;
			default:
				$str = ''; break;
		}
		if (!empty($str)) {
			dcPage::success($str);
		}
	}

	public function setModules($modules)
	{
		$this->modules = array();
		foreach($modules as $id => $module) {
			$this->modules[$id] = self::setModuleInfo($id, $module);
		}

		return $this;
	}

	public function getModules()
	{
		return $this->modules;
	}

	public static function setModuleInfo($id, $module)
	{
		$label = empty($module['label']) ? $id : $module['label'];
		$name = __(empty($module['name']) ? $label : $module['name']);
		
		return array_merge(
			# Default values
			array(
				'desc' 				=> '',
				'author' 			=> '',
				'version' 			=> 0,
				'current_version' 	=> 0,
				'root' 				=> '',
				'root_writable' 	=> false,
				'permissions' 		=> null,
				'parent' 			=> null,
				'priority' 			=> 1000,
				'standalone_config' => false,
				'support' 			=> '',
				'section' 			=> '',
				'tags' 				=> '',
				'details' 			=> ''
			),
			# Module's values
			$module,
			# Clean up values
			array(
				'id' 				=> $id,
				'sid' 				=> self::sanitizeString($id),
				'label' 			=> $label,
				'name' 				=> $name,
				'sname' 			=> self::sanitizeString($name)
			)
		);
	}

	public static function isDistributedModule($module)
	{
		return in_array($module, array(
			'aboutConfig',
			'akismet',
			'antispam',
			'attachments',
			'blogroll',
			'blowupConfig',
			'daInstaller',
			'fairTrackbacks',
			'importExport',
			'maintenance',
			'pages',
			'pings',
			'simpleMenu',
			'tags',
			'themeEditor',
			'userPref',
			'widgets'
		));
	}

	public static function sortModules($modules, $field, $asc=true)
	{
		$sorter = array();
		foreach($modules as $id => $module) {
			$sorter[$id] = isset($module[$field]) ? $module[$field] : $field;
		}
		array_multisort($sorter, $asc ? SORT_ASC : SORT_DESC, $modules);

		return $modules;
	}

	public function displayModulesList($cols=array('name', 'config', 'version', 'desc'), $actions=array(), $nav_limit=false)
	{
		echo 
		'<div class="table-outer">'.
		'<table id="'.html::escapeHTML($this->list_id).'" class="modules'.(in_array('expander', $cols) ? ' expandable' : '').'">'.
		'<caption class="hidden">'.html::escapeHTML(__('Modules list')).'</caption><tr>';

		if (in_array('name', $cols)) {
			echo 
			'<th class="first nowrap"'.(in_array('icon', $cols) ? ' colspan="2"' : '').'>'.__('Name').'</th>';
		}

		if (in_array('version', $cols)) {
			echo 
			'<th class="nowrap count" scope="col">'.__('Version').'</th>';
		}

		if (in_array('current_version', $cols)) {
			echo 
			'<th class="nowrap count" scope="col">'.__('Current version').'</th>';
		}

		if (in_array('desc', $cols)) {
			echo 
			'<th class="nowrap" scope="col">'.__('Details').'</th>';
		}

		if (in_array('distrib', $cols)) {
			echo '<th'.(in_array('desc', $cols) ? '' : ' class="maximal"').'></th>';
		}

		if (!empty($actions) && $this->core->auth->isSuperAdmin()) {
			echo 
			'<th class="minimal nowrap">'.__('Action').'</th>';
		}

		echo 
		'</tr>';

		$sort_field = $this->getSortQuery();

		# Sort modules by id
		$modules = $this->getSearchQuery() === null ?
			self::sortModules($this->modules, $sort_field, $this->sort_asc) :
			$this->modules;

		$count = 0;
		foreach ($modules as $id => $module)
		{
			# Show only requested modules
			if ($nav_limit && $this->getSearchQuery() === null) {
				$char = substr($module[$sort_field], 0, 1);
				if (!in_array($char, $this->nav_list)) {
					$char = $this->nav_special;
				}
				if ($this->getNavQuery() != $char) {
					continue;
				}
			}

			echo 
			'<tr class="line" id="'.html::escapeHTML($this->list_id).'_m_'.html::escapeHTML($id).'" title="'.
			sprintf(__('Configure module "%"'), html::escapeHTML($module['name'])).'">';

			if (in_array('icon', $cols)) {
				echo 
				'<td class="nowrap icon">'.sprintf(
					'<img alt="%1$s" title="%1$s" src="%2$s" />', 
					html::escapeHTML($id), file_exists($module['root'].'/icon.png') ? 'index.php?pf='.$id.'/icon.png' : 'images/module.png'
				).'</td>';
			}

			# Link to config file
			$config = in_array('config', $cols) && !empty($module['root']) && file_exists(path::real($module['root'].'/_config.php'));

			echo 
			'<td class="nowrap" scope="row">'.($config ? 
				'<a href="'.$this->getPageURL('module='.$id.'&conf=1').'">'.html::escapeHTML($module['name']).'</a>' : 
				html::escapeHTML($module['name'])
			).'</td>';

			if (in_array('version', $cols)) {
				echo 
				'<td class="nowrap count">'.html::escapeHTML($module['version']).'</td>';
			}

			if (in_array('current_version', $cols)) {
				echo 
				'<td class="nowrap count">'.html::escapeHTML($module['current_version']).'</td>';
			}

			if (in_array('desc', $cols)) {
				echo 
				'<td class="maximal">'.html::escapeHTML($module['desc']).'</td>';
			}

			if (in_array('distrib', $cols)) {
				echo 
				'<td class="distrib">'.(self::isDistributedModule($id) ? 
					'<img src="images/dotclear_pw.png" alt="'.
					__('Module from official distribution').'" title="'.
					__('module from official distribution').'" />' 
				: '').'</td>';
			}

			if (!empty($actions) && $this->core->auth->isSuperAdmin()) {
				echo 
				'<td class="nowrap">';

				$this->displayLineActions($id, $module, $actions);

				echo
				'</td>';
			}

			echo 
			'</tr>';

			$count++;
		}
		echo 
		'</table></div>';

		if(!$count) {
			echo 
			'<p class="message">'.__('No module matches your search.').'</p>';
		}
	}

	protected function displayLineActions($id, $module, $actions)
	{
		$submits = array();

		# Activate
		if (in_array('deactivate', $actions) && $module['root_writable']) {
			$submits[] = '<input type="submit" name="deactivate" value="'.__('Deactivate').'" />';
		}

		# Deactivate
		if (in_array('activate', $actions) && $module['root_writable']) {
			$submits[] = '<input type="submit" name="activate" value="'.__('Activate').'" />';
		}

		# Delete
		if (in_array('delete', $actions) && $this->isPathDeletable($module['root'])) {
			$submits[] = '<input type="submit" class="delete" name="delete" value="'.__('Delete').'" />';
		}

		# Install (form repository)
		if (in_array('install', $actions) && $this->path_writable) {
			$submits[] = '<input type="submit" name="install" value="'.__('Install').'" />';
		}

		# Update (from repository)
		if (in_array('update', $actions) && $this->path_writable) {
			$submits[] = '<input type="submit" name="update" value="'.__('Update').'" />';
		}

		# Parse form
		if (!empty($submits)) {
			echo 
			'<form action="'.$this->getPageURL().'" method="post">'.
			'<div>'.
			$this->core->formNonce().
			form::hidden(array('module'), html::escapeHTML($id)).
			form::hidden(array('tab'), $this->page_tab).
			implode(' ', $submits).
			'</div>'.
			'</form>';
		}
	}

	public function executeAction($prefix, dcModules $modules, dcRepository $repository)
	{
		if (empty($_POST['module'])	|| !$this->core->auth->isSuperAdmin() || !$this->isPathWritable()) {
			return null;
		}

		$id = $_POST['module'];

		if (!empty($_POST['activate'])) {

			$enabled = $modules->getDisabledModules();
			if (!isset($enabled[$id])) {
				throw new Exception(__('No such module.'));
			}

			# --BEHAVIOR-- moduleBeforeActivate
			$this->core->callBehavior($type.'BeforeActivate', $id);

			$modules->activateModule($id);

			# --BEHAVIOR-- moduleAfterActivate
			$this->core->callBehavior($type.'AfterActivate', $id);

			http::redirect($this->getPageURL('msg=activate'));
		}

		if (!empty($_POST['deactivate'])) {

			if (!$modules->moduleExists($id)) {
				throw new Exception(__('No such module.'));
			}

			$module = $modules->getModules($id);
			$module['id'] = $id;

			if (!$module['root_writable']) {
				throw new Exception(__('You don\'t have permissions to deactivate this module.'));
			}

			# --BEHAVIOR-- moduleBeforeDeactivate
			$this->core->callBehavior($prefix.'BeforeDeactivate', $module);

			$modules->deactivateModule($id);

			# --BEHAVIOR-- moduleAfterDeactivate
			$this->core->callBehavior($prefix.'AfterDeactivate', $module);

			http::redirect($this->getPageURL('msg=deactivate'));
		}

		if (!empty($_POST['delete'])) {

			$disabled = $modules->getDisabledModules();
			if (!isset($disabled[$id])) {

				if (!$modules->moduleExists($id)) {
					throw new Exception(__('No such module.'));
				}

				$module = $modules->getModules($id);
				$module['id'] = $id;

				if (!$this->isPathDeletable($module['root'])) {
					throw new Exception(__("You don't have permissions to delete this module."));
				}

				# --BEHAVIOR-- moduleBeforeDelete
				$this->core->callBehavior($prefix.'BeforeDelete', $module);

				$modules->deleteModule($id);

				# --BEHAVIOR-- moduleAfterDelete
				$this->core->callBehavior($prefix.'AfterDelete', $module);
			}
			else {
				$modules->deleteModule($id, true);
			}

			http::redirect($this->getPageURL('msg=delete'));
		}

		if (!empty($_POST['update'])) {

			$updated = $repository->get();
			if (!isset($updated[$id])) {
				throw new Exception(__('No such module.'));
			}

			if (!$modules->moduleExists($id)) {
				throw new Exception(__('No such module.'));
			}

			$module = $updated[$id];
			$module['id'] = $id;
		
			if (!self::$allow_multi_install) {
				$dest = $module['root'].'/../'.basename($module['file']);
			}
			else {
				$dest = $this->getPath().'/'.basename($module['file']);
				if ($module['root'] != $dest) {
					@file_put_contents($module['root'].'/_disabled', '');
				}
			}

			# --BEHAVIOR-- moduleBeforeUpdate
			$this->core->callBehavior($type.'BeforeUpdate', $module);

			$repository->process($module['file'], $dest);

			# --BEHAVIOR-- moduleAfterUpdate
			$this->core->callBehavior($type.'AfterUpdate', $module);

			http::redirect($this->getPageURL('msg=upadte'));
		}

		if (!empty($_POST['install'])) {

			$updated = $repository->get();
			if (!isset($updated[$id])) {
				throw new Exception(__('No such module.'));
			}

			$module = $updated[$id];
			$module['id'] = $id;

			$dest = $this->getPath().'/'.basename($module['file']);

			# --BEHAVIOR-- moduleBeforeAdd
			$this->core->callBehavior($type.'BeforeAdd', $module);

			$ret_code = $repository->process($module['file'], $dest);

			# --BEHAVIOR-- moduleAfterAdd
			$this->core->callBehavior($type.'AfterAdd', $module);

			http::redirect($this->getPageURL('msg='.($ret_code == 2 ? 'update' : 'install')));
		}
	}

	public static function sanitizeString($str)
	{
		return preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
	}
}

class adminThemesList extends adminModulesList
{

}
