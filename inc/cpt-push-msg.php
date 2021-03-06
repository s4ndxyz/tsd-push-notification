<?php
function tsd_add_push_notification_post_type() {
	// Ref: https://developer.wordpress.org/reference/functions/register_post_type/#comment-351
	$labels = [
		'name'                  => 'Push Notifications',
		'singular_name'         => 'Push Notification',
		'menu_name'             => 'Push Notifications',
		'name_admin_bar'        => 'Push Notification',
		'add_new'               => 'Send New',
		'add_new_item'          => 'Send New Push Notification',
		'new_item'              => 'New Push Notification',
		'edit_item'             => 'Edit Push Notification',
		'view_item'             => 'View Push Notification',
		'all_items'             => 'Past Push Notifications',
		'search_items'          => 'Search Push Notifications',
		'parent_item_colon'     => 'Parent Push Notifications:',
		'not_found'             => 'No Push Notifications found.',
		'not_found_in_trash'    => 'No Push Notifications found in Trash.',
		'featured_image'        => 'Push Notification Feature Image',
		'set_featured_image'    => 'Set feature image',
		'remove_featured_image' => 'Remove feature image',
		'use_featured_image'    => 'Use as feature image',
		'archives'              => 'Push Notification archives',
		'insert_into_item'      => 'Insert into Push Notification',
		'uploaded_to_this_item' => 'Uploaded to this Push Notification',
		'filter_items_list'     => 'Filter Push Notifications list',
		'items_list_navigation' => 'Push Notifications list navigation',
		'items_list'            => 'Push Notifications list',
	];
	$args = [
		'labels'             => $labels,
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_rest'       => false,
		'menu_icon'          => "dashicons-cloud",
		'query_var'          => false,
		'rewrite'            => false,
		'capability_type'    => 'post',
		'capabilities' => [
			'edit_published_posts' => false,
			'delete_published_posts' => false,
			// https://wordpress.stackexchange.com/a/54962/75147
			'edit_post'          => 'update_core',
			'read_post'          => 'update_core',
			'delete_post'        => 'update_core',
			'edit_posts'         => 'update_core',
			'edit_others_posts'  => 'update_core',
			'delete_posts'       => 'update_core',
			'publish_posts'      => 'update_core',
			'read_private_posts' => 'update_core',
		],
		'map_meta_cap'       => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => [ 'title', 'excerpt', 'custom-fields', 'thumbnail' ],
	];
	register_post_type( 'tsd_push_msg', $args );
}
add_action( 'init', 'tsd_add_push_notification_post_type' );

// Input post must support custom taxonomy "tsd_push_msg_receiver_group"
function tsd_pn_get_receiver_ids_from_tsd_push_msg_receiver_group( $post_id ) {
	$receiver_groups = get_the_terms( $post_id, 'tsd_push_msg_receiver_group' );
	$receiver_cpt_ids = [];
	if ( $receiver_groups ) {
		foreach ( $receiver_groups as $each_receiver_group ) {
			$each_receiver_group_id = $each_receiver_group->term_id;
			$receiver_cpt_ids = array_merge( $receiver_cpt_ids, tsd_pn_sub_get_receivers_for_item( "list", $each_receiver_group_id ) );
		}
	}
	$receiver_cpt_ids = array_unique( $receiver_cpt_ids );
	return $receiver_cpt_ids;
}

// https://wordpress.stackexchange.com/a/137257/75147
function tsd_push_notification_post_type_on_publish( $post_id, $post ) {
	// https://stackoverflow.com/a/139553/2603230
	//$log_content = "<pre>".var_export( $post, true )."</pre>";
	//var_dump($log_content);

	$receiver_cpt_ids = tsd_pn_get_receiver_ids_from_tsd_push_msg_receiver_group( $post_id );

	$custom_fields = get_post_custom( $post_id );
	// https://stackoverflow.com/a/4979308/2603230
	$tsd_pn_custom_fields = [];
	foreach ( $custom_fields as $key => $value ) {
		if ( strpos( $key, 'tsd_pn_' ) === 0 ) {
			// Remove `tsd_pn_` from key.
			$tsd_pn_custom_fields[ substr( $key, 7 ) ] = $value[ 0 ];
		}
	}

	$send_results = tsd_send_expo_push_notification(
		$receiver_cpt_ids,
		$post->post_title,
		$post->post_excerpt,
		$tsd_pn_custom_fields,
		true
	);

	// If failed.
	if ( !$send_results ) {
		wp_update_post( [
			'ID' => $post_id,
			'post_status' => 'draft',
		] );
	}
}
add_action( 'publish_tsd_push_msg', 'tsd_push_notification_post_type_on_publish', 10, 2 );
