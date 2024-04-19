<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/*
Plugin Name: Gravity Forms Authorize.Net Add-On
Plugin URI: https://gravityforms.com
Description: Integrates Gravity Forms with Authorize.Net, enabling end users to purchase goods and services through Gravity Forms.
Version: 2.11
Author: Gravity Forms
Author URI: https://gravityforms.com
License: GPL-2.0+
Text Domain: gravityformsauthorizenet
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2009-2021 Rocketgenius, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

define( 'GF_AUTHORIZENET_VERSION', '2.11' );

add_action( 'gform_loaded', array( 'GF_AuthorizeNet_Bootstrap', 'load' ), 5 );
add_action( 'gform_loaded', array( 'GF_AuthorizeNet_Bootstrap', 'load_dupe_submissions' ), 6 );

class GF_AuthorizeNet_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-authorizenet.php' );

		GFAddOn::register( 'GFAuthorizeNet' );
	}

	/**
	 * Load the files and classes necessary to handle duplicate submissions.
	 *
	 * NOTE: This will move to the main core plugin after being vetted within the auth.net context.
	 *
	 * @since 2.9.1
	 */
	public static function load_dupe_submissions() {

		// No need to load this on admin pages.
		if ( is_admin() ) {
			return;
		}

		require_once( 'includes/class-gf-dupe-submissions-handler.php' );

		add_filter( 'gform_duplicate_submissions_protection_disable', array( 'GF_AuthorizeNet_Bootstrap', 'limit_dupe_protection_to_authnet_forms' ), 9, 2 );

		$base_url         = plugin_dir_url( __FILE__ );
		$dupe_submissions = new GF_Dupe_Submissions_Handler( $base_url );

		$dupe_submissions->init();
	}

	/**
	 * Limit the forms affected by Duplicate Submissions protection to only those which use Auth.net.
	 *
	 * @since 2.9.1
	 *
	 * @param bool       $disabled The current disabled value.
	 * @param int|string $form_id  The form ID being processed.
	 *
	 * @return bool
	 */
	public static function limit_dupe_protection_to_authnet_forms( $disabled, $form_id ) {
		return ! GFAuthorizeNet::form_uses_authnet( $form_id );
	}

}

function gf_authorizenet() {
	return GFAuthorizeNet::get_instance();
}