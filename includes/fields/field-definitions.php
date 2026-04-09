<?php
/**
 * Custom Field Definitions Registry
 *
 * Central reference for all custom fields used across CPTs.
 * Actual registration is handled in each post-type file.
 *
 * @package KACCUSA-Connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get field definitions for a given post type.
 *
 * @param string $post_type The post type slug.
 * @return array Field definitions.
 */
function dw_get_field_definitions( $post_type ) {
	$fields = [
		'dw_portfolio' => [
			'_dw_client'           => [
				'label' => 'Client Name',
				'type'  => 'text',
			],
			'_dw_project_url'      => [
				'label' => 'Project URL',
				'type'  => 'url',
			],
			'_dw_project_category' => [
				'label' => 'Category',
				'type'  => 'text',
			],
			'_dw_completed_date'   => [
				'label' => 'Completed Date',
				'type'  => 'date',
			],
		],
		'dw_community' => [
			'_dw_region'       => [
				'label' => 'Region',
				'type'  => 'text',
			],
			'_dw_event_date'   => [
				'label' => 'Event Date',
				'type'  => 'date',
			],
			'_dw_location'     => [
				'label' => 'Location',
				'type'  => 'text',
			],
			'_dw_contact_info' => [
				'label' => 'Contact Info',
				'type'  => 'text',
			],
		],
	];

	return $fields[ $post_type ] ?? [];
}
