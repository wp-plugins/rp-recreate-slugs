<?php
/*
Plugin Name: RP Recreate Slugs
Plugin URI: http://www.rationalplanet.com/2011/03/free-plugin-utility-for-wordpress-rp-recreate-slugs/
Description: Recreate articles and pages slugs with the most current ruleset. Useful when slug generation is changed or updated, especially with transliteration. Global slugs recovery. Saves sites. Use with care.
Author: Alexander Missa
Author URI: http://www.rationalplanet.com
Version: 1.1
License: GPL GPL2

    Copyright 2011 Alexander Missa (email : http://www.rationalplanet.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}
require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions.php' ;
$my_rp_rec_slugs_plugin = new rp_rec_slugs_plugin();