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

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('categories');

# Remove a categories
if (!empty($_POST['categories']) && !empty($_POST['delete'])) {
	try {
		# Check if category where to move posts exists
		$mov_cat = (int) $_POST['mov_cat'];
		$mov_cat = $mov_cat ? $mov_cat : null;
		if ($mov_cat !== null) {
			$c = $core->blog->getCategory($mov_cat);
			if ($c->isEmpty()) {
				throw new Exception(__('Category where to move posts does not exist'));
			}
			unset($c);

			if (in_array($mov_cat, $_POST['categories'])) {
				throw new Exception(__('The entries cannot be moved to the category you choose to delete.'));
			}
		}

		foreach ($_POST['categories'] as $cat_id) {
			# Check if category to delete exists
			$c = $core->blog->getCategory((integer) $cat_id);
			if ($c->isEmpty()) {
				continue;
			}
			unset($c);

			# Move posts
			if ($mov_cat != $cat_id) {
			        $core->blog->changePostsCategory($cat_id,$mov_cat);
			}

			# Delete category
			$core->blog->delCategory($cat_id);
		}
		http::redirect('categories.php?del='.count($_POST['categories']));
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Update order
if (!empty($_POST['save_order']))
{
	if (!empty($_POST['categories_order'])) { // js is enable
	    $categories = json_decode($_POST['categories_order']);

	    foreach ($categories as $category) {
	      if (!empty($category->item_id)) {
		$core->blog->updCategoryPosition($category->item_id, $category->left, $category->right);
	      }
	    }
	} elseif (!empty($_POST['cat_rank'])) {
		function countChildren($t, $id) {
			$c = 0;
			foreach ($t as $k => $v) {
				if (preg_match('`^'.$id.'\..*`', $k)) {
					$c++;
				}
			}

			return $c;
		}

		$i = 1;
		$ranks = array_flip($_POST['cat_rank']);
		uksort($ranks, 'version_compare');
		foreach ($ranks as $str => $id) {
			$core->blog->updCategoryPosition($id, $i, ($i + 2 * countChildren($ranks, $str) + 1));
			$i = $i+2;
		}
	}

	http::redirect('categories.php?reord=1');
}

# Reset order
if (!empty($_POST['reset']))
{
	try
	{
		$core->blog->resetCategoriesOrder();
		http::redirect('categories.php?reord=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}


/* Display
-------------------------------------------------------- */
$rs = $core->blog->getCategories(array('post_type'=>'post'));

$starting_script = dcPage::jsToolMan()."\n";
if (!$core->auth->user_prefs->accessibility->nodragdrop
	&& $core->auth->check('categories',$core->blog->id)
	&& $rs->count()>1) {
        $starting_script .= dcPage::jsLoad('js/jquery/jquery-ui.custom.js');
	$starting_script .= dcPage::jsLoad('js/jquery/jquery.mjs.nestedSortable.js');
}
$starting_script .= dcPage::jsLoad('js/_categories.js');

dcPage::open(__('Categories'),$starting_script,
	dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			'<span class="page-title">'.__('Categories').'</span>' => ''
		))
);

if (!empty($_GET['del'])) {
        dcPage::success(__('The category has been successfully removed.',
			   'The categories have been successfully removed.',
			   (int) $_GET['del']
			   )
			);
}
if (!empty($_GET['reord'])) {
	dcPage::success(__('Categories have been successfully reordered.'));
}

$categories_combo = array();
if (!$rs->isEmpty())
{
	while ($rs->fetch()) {
		$catparents_combo[] = $categories_combo[] = new formSelectOption(
			str_repeat('&nbsp;&nbsp;',$rs->level-1).($rs->level-1 == 0 ? '' : '&bull; ').html::escapeHTML($rs->cat_title),
			$rs->cat_id
		);
	}
}

echo
'<p class="top-add"><a class="button add" href="category.php">'.__('New category').'</a></p>';

echo
'<div class="col">';
if ($rs->isEmpty())
{
	echo '<p>'.__('No category yet.').'</p>';
}
else
{
	echo
	'<form action="categories.php" method="post" id="form-categories">'.
	'<h3>'.__('List of blog\'s categories').'</h3>'.
	'<div id="categories">';

	$ref_level = $level = $rs->level-1;
	$parts = array();
	while ($rs->fetch())
	{
		$attr = 'id="cat_'.$rs->cat_id.'"';

		if ($rs->level > $level) {
			echo str_repeat('<ul><li '.$attr.'>',$rs->level - $level);
			$parts[] = 1;
		} elseif ($rs->level < $level) {
			echo str_repeat('</li></ul>',-($rs->level - $level));
			$parts = array_slice($parts,0,(count($parts) - ($level - $rs->level)));
			$parts[count($parts)-1]++;
		} else {
			$parts[count($parts)-1]++;
		}

		if ($rs->level <= $level) {
			echo '</li><li '.$attr.'>';
		}

		$cat_rank = implode('.',$parts);
		echo
		'<p>'.
		form::checkbox(array('categories[]','cat-'.$rs->cat_id),$rs->cat_id,null,$rs->nb_total>0?'notempty':'').
		'<label class="classic" for="cat-'.$rs->cat_id.'"><a href="category.php?id='.$rs->cat_id.'">'.html::escapeHTML($rs->cat_title).'</a></label>'.
		' (<a href="posts.php?cat_id='.$rs->cat_id.'">'.
		sprintf(($rs->nb_post > 1 ? __('%d entries') : __('%d entry') ),$rs->nb_post).'</a>'.
		', '.__('total:').' '.$rs->nb_total.') '.
		'<span class="cat-url">'.__('URL:').' <code>'.html::escapeHTML($rs->cat_url).'</code></span>'.
		form::field('cat_rank['.$rs->cat_id.']',10,10,$cat_rank,'cat-rank').
		'</p>';

		$level = $rs->level;
	}

	if ($ref_level - $level < 0) {
		echo str_repeat('</li></ul>',-($ref_level - $level));
	}
	echo
	'</div>';

	echo
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	'<p class="col right" id="mov-cat">'.
	'<label for="mov_cat" class="classic">'.__('Category which will receive entries of deleted categories:').'</label> '.
	form::combo('mov_cat',array_merge(array(__('(No cat)') => ''),$categories_combo),'','').
	'</p>'.
	'<p class="right">'.
	'<input type="submit" name="delete" value="'.__('Delete selected categories').'"/>'.
	'</p>'.
	'</div>';

	echo '<h3 class="clear">'.__('Categories order').'</h3>';

	if ($core->auth->check('categories',$core->blog->id) && $rs->count()>1) {
		if (!$core->auth->user_prefs->accessibility->nodragdrop) {
			echo '<p class="no-js-hidden">'.__('To rearrange categories order, move items by drag and drop, then click on “Save categories order” button.').'</p>';
		}
		echo
		'<p class="dragdrop-hidden">'.__('To rearrange categories order, change position number and click on “Save categories order” button.').'</p>'.
		'<p>'.
		'<input type="hidden" id="categories_order" name="categories_order" value=""/>'.
		'<input type="submit" name="save_order" id="save-set-order" value="'.__('Save categories order').'" />'.
		'</p>';
	}

	echo
	'<p><input type="submit" name="reset" value="'.__('Reorder all categories on the top level').'" />'.
	$core->formNonce().'</p>'.
	'</form>';
}

echo '</div>';

dcPage::helpBlock('core_categories');
dcPage::close();
?>
