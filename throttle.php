<?php
/*
Plugin Name: Throttle
Plugin URI: http://www.mutube.com/projects/wordpress/throttle/
Description: Adds load-throttling capability to Wordpress.
Author: Martin Fitzpatrick
Version: 1.5
Author URI: http://www.mutube.com
*/

@define('THROTTLE_VERSION', '1.5');
@define('THROTTLE_DIRPATH','/wp-content/plugins/throttle/');

/*	Advanced configuration options.  These options were originally configurable within the main admin area, however they should not need to be changed for normal use. */

@define('THROTTLE_FRAME',60); //Analysis is done over 60minute 'frame'
@define('THROTTLE_RESOLUTION',5); //1 in 5 hits results in throttle checking

$_BENICE[]='throttle;3287067876809234;3926914184';
		
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

/*
	API FUNCTIONS
	These functions provide services for plugins, themes and the rest of Wordpress to
	use from outside the throttle itself. Provides functions for adding and removing
	throttle checks, and for checking whether a given throttle has been tripped.
*/

/*
	throttled( throttle:string )
	Checks the status of the named throttle. If the current load is above the trigger set
	for the given throttle returns TRUE. If not returns FALSE.
*/

function throttled($throttlename)
{
	global $throttle;
	$options = get_option('plugin_throttle');

	/* 11 indicates throttle is disabled - will never be reached */
	if(	($throttle->get_status()>=$options['throttles'][$throttlename]) )
	{ return true; }
	else
	{ return false; }
}

/*
	add_throttle (throttle:string)
	Adds given throttle, default to disabled (11)
*/

function add_throttle($throttlename,$level=11)
{
	$options = get_option('plugin_throttle');
	if(!$options['throttles'][$throttlename])
		{	
			$options['throttles'][$throttlename] = $level;
		 	update_option('plugin_throttle',$options);
			return true;
		} else {
			return false;
		}
}

/*
	set_throttle (throttle:string, level:integer)
	Sets given throttle with trigger value set to level.
*/

function set_throttle($throttlename,$level=0)
{
	$options = get_option('plugin_throttle');
	$options['throttles'][$throttlename] = $level;
	update_option('plugin_throttle',$options);
	return true;
}

/*
	get_throttle (throttle:string)
	Gets the current trigger level of the given throttle.
*/

function get_throttle($throttlename)
{
	$options = get_option('plugin_throttle');
	if($options['throttles'][$throttlename])
	{ return $options['throttles'][$throttlename]; }
	else
	{ return false; }
}

/*
	THROTTLE CLASS
	Contains all the internals for throttle code. As a general rule these functions
	should not need to be called, but can provide some additional functions - such as
	the throttle-bar status indicator.
*/

class throttle {

/* Hook, update hit-count for the current timeframe - calculate hits/minute */

	function get_status(){
		$data=get_option('plugin_throttle');

		if(true === $data['setting_enabled'])
		{
			return min(round($data['data_hitcount']/$data['setting_maxhits']*10),10);
		}	
		
		return 0;
	}

	function hook()
	{ $this->calculate(); }

	function calculate($hit=true) {

		$data=get_option('plugin_throttle');

		if( true === $data['setting_enabled'] 
			&& 1 == mt_rand(1, THROTTLE_RESOLUTION)){

			$minutes_elapsed = (time()-$data['data_timestamp'])/60;

			/* Expected hits is the number of hits we would expect to in minutes_elapsed
			based on trend over latest frame */
			$expectedhits  = ($data['data_hitcount'] / THROTTLE_FRAME) * $minutes_elapsed;		
		
			/* Remove expected hits */
			$data['data_hitcount'] -= $expectedhits;

			if($hit){ /* So we can call throttle-calculate without a hit being registered */
				/* We increase 1 in x, so increase by x each time */
				$data['data_hitcount']+=THROTTLE_RESOLUTION;
			}

			$data['data_hitcount'] = max(0,$data['data_hitcount']);
			$data['data_timestamp'] = time()-1;

			update_option('plugin_throttle',$data);
		}

	}


/* Throttler Plugin Options Section */

    function admin_options_subpage() {
	
		$options = get_option('plugin_throttle');

		// Get our options and see if we're handling a form submission.
		if ( !is_array($options) )
			{
				/* Default settings values */
				$options['setting_enabled'] = false;

				$options['setting_bandwidth'] = 2000; /* MB monthly bandwidth from host, 2Gb default*/
				$options['setting_pagesize'] = 10; /* KB average page size, taking cache into account*/

			/* Initialise data values */
				$options['data_hitcount'] = 1;
				$options['data_timestamp'] = time()-5; /* fake 5min delay for setup */
				/* $options['data_status']=0; */

				/* 43200 = minutes in a month, scale up from minutes-frame, to monthly-bandwidth */
				$options['setting_maxhits'] = round((THROTTLE_FRAME /43200) * ($options['setting_bandwidth'] / ($options['setting_pagesize']/1000)),2);

				$options['throttles']=array();

				update_option('plugin_throttle',$options);			
			}

		if ( $_POST['throttle-submit'] ) {

			// Remember to sanitize and format use input appropriately.
			if ($_POST['throttle-enabled']){ $options['setting_enabled'] = ( 'Enable Throttle' == $_POST['throttle-enabled']); }

			$options['setting_bandwidth'] = max(strip_tags(stripslashes($_POST['throttle-bandwidth'])),1);
			$options['setting_maxhits'] = max(strip_tags(stripslashes($_POST['throttle-maxhits'])),1);			
			$options['setting_pagesize'] = strip_tags(stripslashes($_POST['throttle-pagesize']));

			/*  Loop through current throttles, check for data from matching SELECT form element
				Also prevents new throttle triggers being created by submission */
			if(sizeof($options['throttles'])>0)
			{
				foreach($options['throttles'] as $throt => $level)
				{
					if($_POST['throttle-' . urlencode($throt)] ){
						$options['throttles'][$throt] = strip_tags(stripslashes($_POST['throttle-' . urlencode($throt)]));
					}					
				}
			}

			update_option('plugin_throttle', $options);

		}
		?>


		<div class="wrap">
        <h2>Throttle Options</h2>
		<div style="margin:20px; 0px">

		<form action="" method="post" id="throttle-config">


		<style>
		
		table.throttle { padding:1em 0; }
		table.throttle td { padding-right:1em; }
		table.throttle tr th { font-weight:bold; text-align:left; padding-bottom:0.5em; padding-right:1em;}

		.throttle-active { font-weight:bold; color:red; }

		</style>
		
		<div style="width:58%;float:left;margin-right:2%;">
		<h3>Throttles</h3>
		<p>Currently configured Throttle levels are shown below.<br />Adjust the values to set the point at which the behaviour (e.g. Stop showing Latest Posts) will be triggered.</p>
		<table cellspacing="0" cellpadding="0" class="throttle">
		<tr><th style="font-weight:bold;">Throttle Name</th><th style="font-weight:bold;">Level</th><th style="font-weight:bold;">Status</th></tr>
		<?php

		$current=$this->get_status();

		if(sizeof($options['throttles'])>0)
		{
			foreach($options['throttles'] as $throt => $level)
			{
		?>
			<tr>
			<td style="padding-right:2em;"><?php echo $throt; ?></td>
			<td><select name="throttle-<?php echo urlencode($throt); ?>">
			<?
				for($a = 11; $a >= 0; $a--)
				{
					?><option value="<?php echo $a; ?>" <?php if($a==$level){echo "selected";}?>><?php if($a!=11){ echo $a; } else { echo "Disabled"; } ?></option> <?php
				}
			?></select></td>
			<td><?php if(11 == $level){echo 'Disabled';} else if($current>=$level){echo '<span class="throttle-active">Active</a>';} else {echo 'Inactive';}?></td>
			</tr>
		<?php
			}
		} else {
			?>
			<tr><td>None defined.</td></tr>
			<?php
		}
		?>
		</table>
		
		<div class="submit" style="text-align:left;"><input type="submit" value="Save changes &raquo;"></div>
		
		</div>

		
		<div style="width:38%;margin-left:2%;float:left;">

		<div>
		<h3>Current Status</h3>

		<?php $this->display_status(); ?>

		<p><input type="submit" name="throttle-enabled" value="<?php if(true === $options['setting_enabled']){echo 'Disable Throttle" style="border:double red;"';} else {echo 'Enable Throttle" style="border:double green;"';}?>"></p>
		</div>

		<div style="padding-top:1em;">
		<h3 id="throttle-advanced-h">Advanced Configuration</h3>
		<div id="throttle-advanced">
		<p>Throttle has calculated the default values below which will
		be correct for most websites. </p>

		<script>

		function calcmaxhits(){

			form = document.getElementById('throttle-config');
			maxhits = ( <?php echo THROTTLE_FRAME; ?> / 43200 ) * ( form['throttle-bandwidth'].value / ( form['throttle-pagesize'].value / 1000 ) );
			return Math.round( maxhits *100 )/100; // 2 decimal places
			
		}

		function checkmaxhits(){

			self = document.getElementById('throttle-config')['throttle-maxhits'];
			if(self.value != calcmaxhits()){self.style.color='red';} else {self.style.color='black';}

		}

<?php
		/* PHP Equivalent of the Javascript code above */
		/* Calculate what max hits should be based on page size, so we can flag where maths does not match up */
		$calcmaxhits = round((THROTTLE_FRAME /43200) * ($options['setting_bandwidth'] / ($options['setting_pagesize']/1000)),2);
?>

		</script>

		<table>
		<tr>
		<td><label for="throttle-bandwidth">Host bandwidth</label></td><td><input style="text-align:right;" name="throttle-bandwidth" size="5" value="<?php echo htmlspecialchars($options['setting_bandwidth'], ENT_QUOTES) ?>" title="Enter your host's bandwidth limit: 1GB = 1000MB."> MB / month</td>
		</tr>
		<tr>
		<td><label for="throttle-pagesize">Average Page Size</label></td>
		<td><input style="text-align:right;" name="throttle-pagesize" size="5" value="<?php echo htmlspecialchars($options['setting_pagesize'], ENT_QUOTES) ?>" title="Set this to ~50% of total page size to allow for caching.">&nbsp;KB</td>
		</tr>
		<tr>
		<td class="submit" style="text-align:left;width:150px;"><input type="button" value="Recalculate Max &raquo;" onClick="document.getElementById('throttle-config')['throttle-maxhits'].value=calcmaxhits();checkmaxhits();"></td>
		<td><input style="text-align:right;" name="throttle-maxhits" size="5" value="<?php echo htmlspecialchars($options['setting_maxhits'], ENT_QUOTES) ?>"
		onChange="checkmaxhits();"> <label for="throttle-maxhits">hits / <?php echo THROTTLE_FRAME; ?> mins</label></td>
		</tr>
		
		<tr><td></td><td class="submit" style="text-align:left;"><input type="submit" value="Save changes &raquo;"></td></tr>
		
		</table>
		</div>
		</div>
		<script>checkmaxhits();</script>

		</div>

		
		<br class="clear" />

		<input type="hidden" name="throttle-submit" value="1" />

        </form></div></div>
		<?php

	}

	function display_status() {

		$status_description = array(
			0 => "No Load",
			1 => "Minimal Load",
			2 => "Minor Load",
			3 => "Minor Load",
			4 => "Average Load",
			5 => "Average Load",
			6 => "Average Load",
			7 => "Heavy Load",
			8 => "Heavy Load",
			9 => "Serious Load",
			10 => "Maximum Load"
		);	

		$options=get_option('plugin_throttle');
		$current=$this->get_status();		
		
		if( $options['setting_enabled'] === true ) {
			/* Active, show current site status */

			?>
			<div style="width:200px;height:20px;color:#fff;font-weight:bold;background-image:url('<?php echo get_bloginfo('wpurl') . THROTTLE_DIRPATH; ?>/throttle-grid.gif');" alt="Throttle Level <?php echo $current;?>/10">
			<div style="width:<?php echo $current * 20; ?>px;height:20px;background:top left;background-image:url('<?php echo get_bloginfo('wpurl') . THROTTLE_DIRPATH; ?>/throttle-bar.gif');text-align:right;">
			<span style="vertical-align:middle;padding-right:5px;"><small><?php echo $current; ?></small></span>
			</div>
			</div>

			<p>Your site is currently under <strong><?php echo $status_description[$current];?></strong>.
			<br />Throttle <strong>enabled</strong>. </p>
			<?php 

		} else {
			/* Active, show current site status */
			?><p>Throttle is currently <strong>disabled</strong><?php
		}
	}

	/* Display current status: For the Activity panel on the Dashboard */
	function activity_box() {
		echo '<div><h3>Throttle</h3><div style="padding:5px 0 0 0;">';
		$this->display_status();
		echo '</div></div>';
	}

	function add_pages()
	{
		add_options_page("Throttle", "Throttle", 10, "throttle-options", array(&$this,'admin_options_subpage'));
	}

}


$throttle = new throttle;

// Run our code later in case this loads prior to any required plugins.
add_action('admin_menu', array(&$throttle,'add_pages'));
add_action('wp_head', array(&$throttle,'hook'));

add_action('activity_box_end', array(&$throttle,'activity_box')); 

?>
