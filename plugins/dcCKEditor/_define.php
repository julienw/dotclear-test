<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2015 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

$this->registerModule(
	/* Name */			    "dcCKEditor",
	/* Description*/		"dotclear CKEditor integration",
	/* Author */			"dotclear Team",
	/* Version */			"1.0.0",
	array(
		'permissions' =>	'usage,contentadmin',
		'type'		=>		'plugin'
	)
);
