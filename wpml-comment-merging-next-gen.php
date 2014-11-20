<?php

/**
 * Plugin Name: WPML comment merging new gen
 * Description: This plugin merges comments from all translations of the posts and pages, so that they all are displayed on each other. Comments are internally still attached to the post or page they were made on. WooCommerce compatible. Based on the original http://wordpress.org/extend/plugins/wpml-comment-merging/
 * Version: 1.0
 * Author: Alexandre Froger
 * Author URI: http://www.crystal-asia.com
 * License: MIT 
 */

if (function_exists('icl_get_languages')) {
	define('ARRAY_LANG', serialize(icl_get_languages()));
} else {
	define('ARRAY_LANG', serialize(array()));
}


function sort_merged_comments($a, $b) { 
	return $a->comment_ID - $b->comment_ID;
}


function merge_comments($comments, $post_ID) {
	global $sitepress;
	
	// get all the languages for which this post exists
	$languages = unserialize(ARRAY_LANG);
	if (!empty($languages)) {

		remove_filter('comments_clauses', array($sitepress, 'comments_clauses'));
		$post = get_post( $post_ID );
		$type = $post->post_type;

		foreach ($languages as $code => $l) {
			// in $comments are already the comments from the current language
			if($l['code'] != ICL_LANGUAGE_CODE) {
				$otherID = icl_object_id($post_ID, $type, false, $l['code']);
				$othercomments = get_comments(array('post_id' => $otherID, 'status' => 'approve', 'order' => 'ASC'));
				$comments = array_merge($comments, $othercomments);
			}
		}

		if ($languages) {
			// if we merged some comments in we need to reestablish an order
			usort($comments, 'sort_merged_comments');
		}
		
		add_filter('comments_clauses', array($sitepress, 'comments_clauses'));

	}
	
	return $comments;
}



function merge_comment_count($count, $post_ID) {
	// get all the languages for which this post exists
	global $wp_query, $post, $product;

	$languages = unserialize(ARRAY_LANG);

	if (!empty($languages)) {

		$type = is_page($post_ID) ? 'page' : 'post';
		$post = get_post( $post_ID );
		$type = $post->post_type;

		foreach ($languages as $l) {
			// in $count is already the count from the current language
			if ($l['code'] != ICL_LANGUAGE_CODE) {
				$otherID = icl_object_id($post_ID, $type, false, $l['code']);
				if ($otherID) {
					// cannot use call_user_func due to php regressions
					if ($type == 'page') {
						$otherpost = get_page($otherID);
					} else {
						$otherpost = get_post($otherID);
					}
					if ($otherpost) {
						// increment comment count using translation post comment count.
						$count = $count + $otherpost->comment_count;
					}
				}
			}
		}
	}
	
	return $count;
}
add_filter('get_comments_number', 'merge_comment_count', 100, 2);

