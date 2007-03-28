<?php
/*
Plugin Name: Throttle:Limit
Plugin URI: http://www.mutube.com/projects/wordpress/throttle/
Description: Default extension for the Throttle Plugin-API. Reduce bandwidth use under load by redirecting visitors to Coral Cache and temporarily removing images from posts & pages.
Author: Martin Fitzpatrick
Version: 0.7
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

function throttle_redirect()
{
	global $throttle;
	/*	Redirect when throttle status increases above limit. */
	/* NB: 10 is the point where, if load continued at this rate, the user
	would exceed their server capability or bandwidth allowance.
	Once throttled, it should not exceed. */
	/* Do not redirect admin pages */
	/* Do not redirect requests coming from the cache */

	if (	throttled('REDIRECT: Coral Cache CDN')
			&& 	( !is_admin() )
			&& 	( strpos($_SERVER['HTTP_USER_AGENT'],'CoralWebPrx')===false ) ){

		/* Update the throttle status, but do not register a hit */
		$throttle->calculate(false);

		/*	$redirect_google='http://www.google.com/search?q=cache:' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; */

		
		$redirect_cdn='http://' . $_SERVER['SERVER_NAME'] . '.nyud.net:8080' . $_SERVER['REQUEST_URI'];
		
		header("Location: " . $redirect_cdn);
		exit(0);

	}
}

/*	Throttle:Images */

function throttle_images($content = ''){
	global $throttle;
	/*	Redirect when throttle status increases above limit. */
	/* NB: 7 is the point where we are heading towards exceeding server capability.
	Once throttled, it should not exceed. */
	/* Do not redirect admin pages */
	/* Do not redirect requests coming from the cache */

	if (	throttled('Strip Images from Posts & Pages')
		&& 	( !is_admin() )
		&& 	( strpos($_SERVER['HTTP_USER_AGENT'],'CoralWebPrx')===false ) )
	{
		$content=preg_replace('/<img(.*?)alt[^"]+?"(.*?)"(.*?)>/','<a $1 $3 title="Site currently experiencing heavy load. Click to view image.">Image: $2</a>', $content);
		$content=preg_replace('/<a(.*?)src(.*?)>/','<a$1 href$2>', $content);
		return  $content;

	} else { return $content; }
}

/*
  INITIALISATION
  All functions in here called at startup (after other plugins have loaded)
*/

function throttle_limit_init(){
	add_throttle('Strip Images from Posts & Pages'); //add throttle, disabled by default
	add_throttle('REDIRECT: Coral Cache CDN');
}


add_action('init', 'throttle_redirect');
add_filter('the_content', 'throttle_images');

add_action('init', 'throttle_limit_init');

?>