<?php
/**
 * Template Name: Portfolio
 * Template Post Type: page
 *
 * @package KACCUSA-Connect
 */

get_header();
do_action( 'dw_gps_auth_check' );
dw_render_block_template( 'portfolio' );
get_footer();
