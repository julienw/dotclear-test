<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

function dotclearUpgrade($core)
{
	$version = $core->getVersion('core');
	
	if ($version === null) {
		return false;
	}
	
	if (version_compare($version,DC_VERSION,'<') == 1)
	{
		try
		{
			if ($core->con->driver() == 'sqlite') {
				throw new Exception(__('SQLite Database Schema cannot be upgraded.'));
			}
			
			# Database upgrade
			$_s = new dbStruct($core->con,$core->prefix);
			require dirname(__FILE__).'/db-schema.php';
			
			$si = new dbStruct($core->con,$core->prefix);
			$changes = $si->synchronize($_s);
			
			/* Some other upgrades
			------------------------------------ */
			# Populate media_dir field (since 2.0-beta3.3)
			if (version_compare($version,'2.0-beta3.3','<'))
			{
				$strReq = 'SELECT media_id, media_file FROM '.$core->prefix.'media ';
				$rs_m = $core->con->select($strReq);
				while($rs_m->fetch()) {
					$cur = $core->con->openCursor($core->prefix.'media');
					$cur->media_dir = dirname($rs_m->media_file);
					$cur->update('WHERE media_id = '.(integer) $rs_m->media_id);
				}
			}
			
			if (version_compare($version,'2.0-beta7.3','<'))
			{
				# Blowup becomes default theme
				$strReq = 'UPDATE '.$core->prefix.'setting '.
						"SET setting_value = '%s' ".
						"WHERE setting_id = 'theme' ".
						"AND setting_value = '%s' ".
						'AND blog_id IS NOT NULL ';
				$core->con->execute(sprintf($strReq,'blueSilence','default'));
				$core->con->execute(sprintf($strReq,'default','blowup'));
			}
			
			if (version_compare($version,'2.1-alpha2-r2383','<'))
			{
				$schema = dbSchema::init($core->con);
				$schema->dropUnique($core->prefix.'category',$core->prefix.'uk_cat_title');
				
				# Reindex categories
				$rs = $core->con->select(
					'SELECT cat_id, cat_title, blog_id '.
					'FROM '.$core->prefix.'category '.
					'ORDER BY blog_id ASC , cat_position ASC '
				);
				$cat_blog = $rs->blog_id;
				$i = 2;
				while ($rs->fetch()) {
					if ($cat_blog != $rs->blog_id) {
						$i = 2;
					}
					$core->con->execute(
						'UPDATE '.$core->prefix.'category SET '
						.'cat_lft = '.($i++).', cat_rgt = '.($i++).' '.
						'WHERE cat_id = '.(integer) $rs->cat_id
					);
					$cat_blog = $rs->blog_id;
				}
			}
			
			if (version_compare($version,'2.1.6','<='))
			{
				# ie7js has been upgraded
				$ie7files = array (
					'ie7-base64.php ',
					'ie7-content.htc',
					'ie7-core.js',
					'ie7-css2-selectors.js',
					'ie7-css3-selectors.js',
					'ie7-css-strict.js',
					'ie7-dhtml.js',
					'ie7-dynamic-attributes.js',
					'ie7-fixed.js',
					'ie7-graphics.js',
					'ie7-html4.js',
					'ie7-ie5.js',
					'ie7-layout.js',
					'ie7-load.htc',
					'ie7-object.htc',
					'ie7-overflow.js',
					'ie7-quirks.js',
					'ie7-server.css',
					'ie7-standard-p.js',
					'ie7-xml-extras.js'
					);
				foreach ($ie7files as $f) {
					@unlink(DC_ROOT.'/admin/js/ie7/'.$f);
				}
			}
			
			if (version_compare($version,'2.2-alpha1-r3043','<'))
			{
				# metadata has been integrated to the core.
				$core->plugins->loadModules(DC_PLUGINS_ROOT);
				if ($core->plugins->moduleExists('metadata')) {
					$core->plugins->deleteModule('metadata');
				}
				
				# Tags template class has been renamed
				$sqlstr =
					'SELECT blog_id, setting_id, setting_value '.
					'FROM '.$core->prefix.'setting '.
					'WHERE (setting_id = \'widgets_nav\' OR setting_id = \'widgets_extra\') '.
					'AND setting_ns = \'widgets\';';
				$rs = $core->con->select($sqlstr);
				while ($rs->fetch()) {
					$widgetsettings = base64_decode($rs->setting_value);
					$widgetsettings = str_replace ('s:11:"tplMetadata"','s:7:"tplTags"',$widgetsettings);
					$cur = $core->con->openCursor($core->prefix.'setting');
					$cur->setting_value = base64_encode($widgetsettings);
					$sqlstr = 'WHERE setting_id = \''.$rs->setting_id.'\' AND setting_ns = \'widgets\' '.
					'AND blog_id ' .
					($rs->blog_id == NULL ? 'is NULL' : '= \''.$core->con->escape($rs->blog_id).'\'');
					$cur->update($sqlstr);
				}
			}

			if (version_compare($version,'2.3','<'))
			{
				# Add global favorites
				$init_fav = array();

				$init_fav['new_post'] = array('new_post',__('New entry'),'post.php',
					'images/menu/edit.png','images/menu/edit-b.png',
					'usage,contentadmin',null,'menu-new-post');
				$init_fav['posts'] = array('posts',__('Entries'),'posts.php',
					'images/menu/entries.png','images/menu/entries-b.png',
					'usage,contentadmin',null,null);
				$init_fav['comments'] = array('comments',__('Comments'),'comments.php',
					'images/menu/comments.png','images/menu/comments-b.png',
					'usage,contentadmin',null,null);
				$init_fav['prefs'] = array('prefs',__('My preferences'),'preferences.php',
					'images/menu/user-pref.png','images/menu/user-pref-b.png',
					'*',null,null);
				$init_fav['blog_pref'] = array('blog_pref',__('Blog settings'),'blog_pref.php',
					'images/menu/blog-pref.png','images/menu/blog-pref-b.png',
					'admin',null,null);
				$init_fav['blog_theme'] = array('blog_theme',__('Blog appearance'),'blog_theme.php',
					'images/menu/themes.png','images/menu/blog-theme-b.png',
					'admin',null,null);

				$init_fav['pages'] = array('pages',__('Pages'),'plugin.php?p=pages',
					'index.php?pf=pages/icon.png','index.php?pf=pages/icon-big.png',
					'contentadmin,pages',null,null);
				$init_fav['blogroll'] = array('blogroll',__('Blogroll'),'plugin.php?p=blogroll',
					'index.php?pf=blogroll/icon-small.png','index.php?pf=blogroll/icon.png',
					'usage,contentadmin',null,null);

				$count = 0;
				foreach ($init_fav as $k => $f) {
					$t = array('name' => $f[0],'title' => $f[1],'url' => $f[2], 'small-icon' => $f[3],
						'large-icon' => $f[4],'permissions' => $f[5],'id' => $f[6],'class' => $f[7]);
					$sqlstr = 'INSERT INTO '.$core->prefix.'pref (pref_id, user_id, pref_ws, pref_value, pref_type, pref_label) VALUES ('.
						'\''.sprintf("g%03s",$count).'\',NULL,\'favorites\',\''.serialize($t).'\',\'string\',NULL);';
					$core->con->execute($sqlstr);
					$count++;
				}
			}
			
			$core->setVersion('core',DC_VERSION);
			$core->blogDefaults();
			
			# Drop content from session table
			$core->con->execute('DELETE FROM '.$core->prefix.'session ');
			
			# Empty templates cache directory
			try {
				$core->emptyTemplatesCache();
			} catch (Exception $e) {}
			
			return $changes;
		}
		catch (Exception $e)
		{
			throw new Exception(__('Something went wrong with auto upgrade:').
			' '.$e->getMessage());
		}
	}
	
	# No upgrade?
	return false;
}
?>