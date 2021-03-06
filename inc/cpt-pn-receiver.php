<?php
function tsd_add_push_notification_receiver_post_type() {
	$args = [
		'label'              => "PN Users",
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_rest'       => false,
		'menu_icon'          => "dashicons-networking",
		'query_var'          => false,
		'rewrite'            => false,
		'capability_type'    => 'post',
		'capabilities' => [
			'edit_post'          => 'update_core',
			'read_post'          => 'update_core',
			'delete_post'        => 'update_core',
			'edit_posts'         => 'update_core',
			'edit_others_posts'  => 'update_core',
			'publish_posts'      => 'update_core',
			'read_private_posts' => 'update_core',
			'create_posts'       => 'update_core',
		],
		'map_meta_cap'       => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => [ 'title', 'custom-fields' ],
	];
	register_post_type( 'tsd_pn_receiver', $args );
}
add_action( 'init', 'tsd_add_push_notification_receiver_post_type' );

// https://wordpress.stackexchange.com/a/91052/75147
function tsd_push_notification_receiver_post_type_trash_post( $post_id ) {
	$post_type = get_post_type( $post_id );
	$post_status = get_post_status( $post_id );

	if ( $post_type == 'tsd_pn_receiver' && in_array( $post_status, [ 'publish', 'draft', 'future' ] ) ) {
		tsd_pn_sub_clear_receiver( $post_id );
	}
}
add_action( 'wp_trash_post', 'tsd_push_notification_receiver_post_type_trash_post' );

function tsd_push_notification_receiver_post_type_edit_form_after_title($post) {
	if ( $post->post_type === 'tsd_pn_receiver' ) {
		$data = [];
		foreach ( tsd_pn_get_subscription_types() as $each_sub_type ) {
			$data[ $each_sub_type ] = tsd_pn_sub_get_items_for_receiver( $post->ID, $each_sub_type );
		}
		echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
	}
}
add_action( 'edit_form_after_title', 'tsd_push_notification_receiver_post_type_edit_form_after_title' );
