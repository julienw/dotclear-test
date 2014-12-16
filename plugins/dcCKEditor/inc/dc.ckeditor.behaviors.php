<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2014 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

class dcCKEditorBehaviors
{
    protected static $p_url = 'index.php?pf=dcCKEditor';
    protected static $config_url = 'plugin.php?p=dcCKEditor&config=1';

    public static function adminPostEditor($editor='',$context='') {
        if (empty($editor) || $editor!='dcCKEditor') { return;}

        $config_js = self::$config_url;
        if (!empty($context)) {
            $config_js .= '&context='.$context;
        }

        return
            '<script type="text/javascript">'."\n".
            "//<![CDATA[\n".
            'var CKEDITOR_BASEPATH = "'.DC_ADMIN_URL.self::$p_url.'/js/ckeditor/";'."\n".
            dcPage::jsVar('dotclear.base_url', $GLOBALS['core']->blog->host).
            dcPage::jsVar('dotclear.dcckeditor_plugin_url',DC_ADMIN_URL.self::$p_url).
            'CKEDITOR_GETURL = function(resource) {
	            // If this is not a full or absolute path.
	            if ( resource.indexOf(":/") == -1 && resource.indexOf("/") !== 0 ) {
		            resource = this.basePath + resource;
	            }
	            return resource;
             };'.
            "dotclear.msg.img_select_title = '".html::escapeJS(__('Media chooser'))."'; ".
            "dotclear.msg.post_link_title = '".html::escapeJS(__('Link to an entry'))."'; ".
            "dotclear.msg.link_title = '".html::escapeJS(__('Link'))."'; ".
            "\n//]]>\n".
            "</script>\n".
            dcPage::jsLoad(self::$p_url.'/js/ckeditor/ckeditor.js').
            dcPage::jsLoad(self::$p_url.'/js/ckeditor/adapters/jquery.js').
            dcPage::jsLoad($config_js);
	}

    public static function adminPopupMedia($editor='') {
        if (empty($editor) || $editor!='dcCKEditor') { return;}

    	return dcPage::jsLoad(self::$p_url.'/js/popup_media.js');
    }

    public static function adminPopupLink($editor='') {
        if (empty($editor) || $editor!='dcCKEditor') { return;}

    	return dcPage::jsLoad(self::$p_url.'/js/popup_link.js');
    }

    public static function adminPopupPosts($editor='') {
        if (empty($editor) || $editor!='dcCKEditor') { return;}

    	return dcPage::jsLoad(self::$p_url.'/js/popup_posts.js');
    }

    public static function adminMediaURLParams($p) {
        if (!empty($_GET['editor'])) {
            $p['editor']=html::sanitiseURL($_GET['editor']);
        }
    }
}
