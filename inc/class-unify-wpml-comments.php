<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Unify_WPML_Comments {

	public function __construct() {
		global $sitepress;

		if ( $sitepress ) {
			// Merge comments across languages
			add_filter( 'comments_array', array( $this, 'merge_comments' ), 99, 2 );
			// Adjust comments number across languages
			add_filter( 'get_comments_number', array( $this, 'merge_comment_count' ), 99, 2 );
			add_filter( 'woocommerce_product_get_review_count', array( $this, 'merge_comment_count' ), 99, 2 );
			// Adjust product rating across languages
			add_filter( 'woocommerce_product_get_average_rating', array( $this, 'merge_ratings' ), 99, 2 );
		}
	}

	/*******************************************************************
	* Public methods
	*******************************************************************/

	public function merge_ratings( $rating, $product ) {
		$lang              = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : get_bloginfo( 'language' );
		$languages         = apply_filters( 'wpml_active_languages', array(), array(
			'skip_missing' => 1,
		) );
		$ids               = array( $product->get_id() );
		$count             = absint( $product->get_rating_count() );
		$other_has_ratings = false;

		foreach ( $languages as $l ) {

			if ( $l['code'] !== $lang ) {
				$other_id = apply_filters( 'wpml_object_id', $product->get_id(), 'product', false, $l['code'] );

				if ( $other_id ) {
					$other_product = wc_get_product( $other_id );

					if ( $other_product ) {
						$ids[]       = $other_product->get_id();
						$other_count = absint( $other_product->get_rating_count() );
						$count       = $count + absint( $other_product->get_rating_count() );

						if ( $other_count > 0 ) {
							$has_new = true;
						}
					}
				}
			}
		}

		if ( $count && count( $ids ) > 1 && $other_has_ratings ) {
			$rating = $this->_calculate_global_average_rating( $count, $ids );
		}

		return $rating;
	}

	public function merge_comment_count( $count, $post ) {
		global $post;

		$lang      = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : get_bloginfo( 'language' );
		$languages = apply_filters( 'wpml_active_languages', array(), array(
			'skip_missing' => 0,
		) );

		foreach ( $languages as $l ) {

			if ( $l['code'] !== $lang ) {
				$main_post = get_post( $post );
				$other_id  = apply_filters( 'wpml_object_id', $main_post->ID, $main_post->post_type, false, $l['code'] );

				if ( $other_id ) {
					$other_post = get_post( $other_id );

					if ( $other_post ) {
						$count = $count + $other_post->comment_count;
					}
				}
			}
		}

		return $count;
	}

	public function merge_comments( $comments, $post_ID ) {
		global $sitepress;

		$languages = apply_filters( 'wpml_active_languages', array(), array( 'skip_missing' => 0 ) );

		if ( ! empty( $languages ) ) {
			remove_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ) );

			$post = get_post( $post_ID );
			$lang = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : get_bloginfo( 'language' );

			if ( $post instanceof WP_Post ) {

				foreach ( $languages as $code => $l ) {

					if ( $l['code'] !== $lang ) {

						$other_id = apply_filters( 'wpml_object_id', $post->ID, $post->post_type, false, $l['code'] );

						if ( $other_id ) {
							$other_comments = get_comments( array(
								'post_id' => $other_id,
								'status'  => 'approve',
								'order'   => 'ASC',
							) );
							$comments       = array_merge( $comments, $other_comments );
						}
					}
				}

				usort( $comments, array( $this, 'sort_comments_helper' ) );
			}

			add_filter( 'comments_clauses', array( $sitepress, 'comments_clauses' ) );
		}

		return $comments;
	}

	public function sort_comments_helper( $a, $b ) {

		return $a->comment_ID - $b->comment_ID;
	}

	/*******************************************************************
	* Private methods
	*******************************************************************/

	private function _calculate_global_average_rating( $count, $ids ) {// @codingStandardsIgnoreLine
		global $wpdb;

		$ids_in = array_map( function( $id ) {

			return "'" . esc_sql( $id ) . "'";
		}, $ids );

		$ids_in    = implode( ',', $ids );
		$cache_key = ksort( $ids );
		$rating    = wp_cache_get( 'wp_uc_rating_' . $cache_key, 'wp_uc' );

		if ( false === $rating ) {
			$ratings = $wpdb->get_var( "
				SELECT SUM(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID IN (" . esc_sql( $ids_in ) . ")
				AND comment_approved = '1'
				AND meta_value > 0
			" );
			$rating  = number_format( $ratings / $count, 2, '.', '' );

			wp_cache_set( 'wp_uc_rating_' . $cache_key, 'wp_uc', $rating );
		}

		remove_filter( 'woocommerce_product_get_average_rating', array( $this, 'merge_ratings' ), 99, 2 );

		foreach ( $ids as $id ) {
			$product    = wc_get_product( $id );
			$data_store = $product->get_data_store();

			$product->set_average_rating( $rating );
			$data_store->update_average_rating( $product );
		}

		add_filter( 'woocommerce_product_get_average_rating', array( $this, 'merge_ratings' ), 99, 2 );

		return $rating;
	}

}
