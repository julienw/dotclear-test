<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

class dcMaintenanceIndexcomments extends dcMaintenanceTask
{
	protected $group = 'index';
	protected $limit = 500;
	protected $step_task;

	protected function init()
	{
		$this->name 		= __('Search engine index');
		$this->task 		= __('Index all comments');
		$this->step_task 	= __('next');
		$this->step 		= __('Indexing comment %d to %d.');
		$this->success 		= __('Comments index done.');
		$this->error 		= __('Failed to index comments.');
	}

	public function execute()
	{
		$this->code = $this->core->indexAllComments($this->code, $this->limit);
		
		return $this->code ? $this->code : true;
	}

	public function task()
	{
		return $this->code ? $this->step_task : $this->task;
	}

	public function step()
	{
		return $this->code ? sprintf($this->step, $this->code - $this->limit, $this->code) : null;
	}

	public function success()
	{
		return $this->code ? sprintf($this->step, $this->code - $this->limit, $this->code) : $this->success;
	}
}