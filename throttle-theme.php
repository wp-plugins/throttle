<?php
/*
Plugin Name: Throttle:Theme
Plugin URI: http://www.mutube.com/projects/wordpress/throttle/
Description: Default extension for the Throttle Plugin-API. Reduce bandwidth by switching to a low-bandwidth theme. Save this theme under folder low-bandwidth in /wp-content/themes/
Author: Martin Fitzpatrick
Version: 0.2
Author URI: http://www.mutube.com
*/

/*  Copyright 2006  MARTIN FITZPATRICK  (email : martin.fitzpatrick@mutube.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/* Throttle:Redirect */

function throttle_theme($theme)
{
	if (throttled('Theme Switch: Low Bandwidth')){
		$theme='low-bandwidth';
	}
	return $theme;
}


/*
  INITIALISATION
  All functions in here called at startup (after other plugins have loaded)
*/

function throttle_theme_init(){
	add_throttle('Theme Switch: Low Bandwidth');  //add throttle, disabled by default
}

add_action('plugins_loaded', 'throttle_theme_init');

add_filter('stylesheet', 'throttle_theme');
add_filter('template', 'throttle_theme');

?>