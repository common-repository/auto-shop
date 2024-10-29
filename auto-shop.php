<?php
/*
Plugin Name: Auto Shop
Plugin URI: autoshop
Description: Autoshop
Version: 0.1
Author: Orion Technology Solutions
Author URI: http://www.oriontechnologysolutions.com
*/

// Copyright (c) 2010 Orion Technology Solutions, All rights reserved
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// This is an add-on for WordPress
// http://wordpress.org/
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

/* TODO
*/

$autoshop_vehicle_attributes = Array(
  'autoshop_make'
, 'autoshop_model'
, 'autoshop_model_year'
, 'autoshop_price'
, 'autoshop_miles'
, 'autoshop_exterior_color'
, 'autoshop_interior_color'
, 'autoshop_body_style'
, 'autoshop_engine_size'
, 'autoshop_engine_type'
, 'autoshop_transmission'
, 'autoshop_drive_train'
, 'autoshop_fuel'
, 'autoshop_stereo'
, 'autoshop_doors'
, 'autoshop_vin'
, 'autoshop_description'
);
$autoshop_vehicle_photos = Array(
  'autoshop_cover_shot'
);

$autoshop_body_style_list = Array(
  'Truck'
, 'Hatchback'
, 'Coupe'
, 'Motorcycle'
, 'SUV'
, 'Sedan'
, 'Van'
);

$autoshop_engine_type_list = Array(
  '4-Cylinder'
, 'V-6'
, 'V-8'
, 'Inline 6'
, '2-Cylinder'
, 'Electric'
);

$autoshop_transmission_list = Array(
  'Automatic'
, '4 Speed Shiftable Automatic'
, '4 Speed Manual'
, '5 Speed Manual'
);

$autoshop_drive_train_list = Array(
  'Front Wheel Drive'
, 'Rear Wheel Drive'
, 'Four Wheel Drive / Rear Wheel Drive'
, 'All Wheel Drive'
, 'Chain Drive'
, 'Belt Drive'
, 'Shaft Drive'
);

$autoshop_fuel_list = Array(
  'Gasoline'
, 'Diesel'
, 'Electric'
);

$autoshop_stereo_list = Array(
  'CD Player'
, 'MP3 Player'
, 'Tape Player'
, 'AM/FM Radio'
);

class autoshop_vehicle {
	function autoshop_vehicle(
		$as_id
	) {
		global $autoshop_vehicle_attributes, $wpdb;
		global $autoshop_vehicle_photos;
		$this->modified = '';
		$this->as_created_at = $wpdb->escape(time());

                if(is_array($as_id)) {
                    $this->array_update($as_id);
                }
                else {
		    $this->post_id = $wpdb->escape($as_id);
                    $pid = wp_is_post_revision($this->post_id);
                    if($pid)
                        $this->post_id = $pid;
                    $custom = get_post_custom($this->post_id);
                    foreach($autoshop_vehicle_attributes as $attribute) {
                        if(isset($custom[$attribute][0]))
                            $this->attribs[$attribute] = $custom[$attribute][0];
                    }
                    foreach($autoshop_vehicle_photos as $photo) {
                        if(isset($custom[$photo][0]))
                            $this->attribs[$photo] = $custom[$photo][0];
                        //echo $photo . ":" . $custom[$photo][0];
                    }
                }
                $this->as_text = $this->create_text();
                $this->as_title = $this->create_title();
	}
	
	function array_update($vehicle_data) {
            global $autoshop_vehicle_attributes, $wpdb;
/*
echo "<pre>";
print_r($vehicle_data);
echo "</pre>";
*/
            if(isset($vehicle_data['autoshop_vid']))
                $this->post_id = $vehicle_data['autoshop_vid'];

            foreach($autoshop_vehicle_attributes as $attribute) {
                if(isset($vehicle_data[$attribute]))
                    $this->attribs[$attribute] = $wpdb->escape($vehicle_data[$attribute]);
            }
	}
	
	function create_title() {
            list($make) = get_categories( Array('include' => $this->attribs['autoshop_make'], 'hide_empty' => false ));
            return(sprintf("%d %s %s", $this->attribs['autoshop_model_year'], $make->name , $this->attribs['autoshop_model']));
        }
	function create_text() {
            return "[autoshop-vehicle]";
        }

	function vehicle_exists() {
		global $wpdb;
		$test = $wpdb->get_results("
			SELECT *
			FROM $wpdb->postmeta
			WHERE meta_key = 'autoshop_vid'
			AND meta_value = '".$wpdb->escape($this->as_id)."'
		");
		if (count($test) > 0) {
			return true;
		}
		return false;
	}
	
	function add_data() {
		global $wpdb, $aktt, $autoshop_vehicle_attributes;
                $atAr = Array();
                foreach($autoshop_vehicle_attributes as $attribute) {
                    $atAr[] = sprintf("(%d, '%s', '%s')", $this->post_id, $attribute, $this->attribs[$attribute]);
                }
                $sql = "INSERT INTO " . $wpdb->postmeta
                       . "(post_id, meta_key, meta_value) VALUES " . join(',', $atAr);
		$result = $wpdb->query($sql);
	}

	function update_data() {
		global $wpdb, $aktt, $autoshop_vehicle_attributes, $autoshop_vehicle_photos;
                $atAr = Array();
                foreach($autoshop_vehicle_attributes as $attribute) {
/*
                    if($attribute == 'autoshop_make')
                        continue;
*/
                    $sql = sprintf("INSERT INTO %s (post_id, meta_key, meta_value) VALUES (%d, '%s', '%s')"
                                    , $wpdb->postmeta
                                    , $this->post_id
                                    , $attribute
                                    , $this->attribs[$attribute]);
                    if(isset($this->post_id)) {
                        $sqlCheck = sprintf("SELECT count(post_id) FROM wp_postmeta WHERE post_id=%d AND meta_key = '%s';"
                                       , $this->post_id
                                       , $attribute
                                      );
		        $result = $wpdb->get_row($sqlCheck, ARRAY_N);
                        if($result[0] == 1) {
                            $sql = sprintf("UPDATE %s SET meta_value='%s' WHERE post_id=%d AND meta_key = '%s';"
                                           , $wpdb->postmeta 
                                           , $this->attribs[$attribute] 
                                           , $this->post_id
                                           , $attribute
                                          );
                        }
                    }
                    //echo "$sql <br />";
                    //error_log("$sql\n");
		    $result = $wpdb->query($sql);
                }
	}

    function update_photos() {
echo "<pre>";
print_r($this->attribs);
echo "</pre>";
        global $wpdb, $aktt, $autoshop_vehicle_photos;
        foreach($autoshop_vehicle_photos as $photo) {
            $sql = sprintf("SELECT count(post_id) FROM wp_postmeta WHERE post_id=%d AND meta_key = '%s';"
                          , $this->post_id
                          , $photo
                          );
            $result = $wpdb->get_row($sql, ARRAY_N);
            if($result[0] == 1) {
                $sql = sprintf("UPDATE %s SET meta_value='%s' WHERE post_id=%d AND meta_key = '%s';"
                              , $wpdb->postmeta 
                              , $this->attribs[$photo] 
                              , $this->post_id
                              , $photo
                              );
            }
            echo $sql;
/*
            else if($result[0] == 0) {
                $sql = sprintf("INSERT INTO %s (post_id, meta_key, meta_value) VALUES (%d, '%s', '%s')"
                              , $wpdb->postmeta
                              , $this->post_id
                              , $attribute
                              , $this->attribs[$attribute]);
            }
      //      echo "$sql <br />";
            $result = $wpdb->query($sql);
*/
        }
    }

        function update_tags() {
            global $autoshop_body_style_list;
            global $autoshop_engine_type_list, $autoshop_transmission_list;
            global $autoshop_drive_train_list, $autoshop_fuel_list;
            global $autoshop_stereo_list;

            $this->blog_post_tags = Array(
                "a " . $autoshop_engine_type_list[$this->attribs['autoshop_engine_type']] . " engine"
              , "a " . $this->attribs['autoshop_engine_size'] . " engine"
              , $this->attribs['autoshop_model']
              , $this->attribs['autoshop_model_year'] . " model year"
              , "a " . $this->attribs['autoshop_exterior_color'] . " exterior"
              , "a " . $this->attribs['autoshop_interior_color'] . " interior"
              , "a " . $autoshop_body_style_list[$this->attribs['autoshop_body_style']] . " body"
              , "a " . $autoshop_transmission_list[$this->attribs['autoshop_transmission']] . " transmission"
              , "a " . $autoshop_drive_train_list[$this->attribs['autoshop_drive_train']] . "train"
              , "a " . $autoshop_fuel_list[$this->attribs['autoshop_fuel']] . " engine"
              , "a " . $autoshop_stereo_list[$this->attribs['autoshop_stereo']]
              , $this->attribs['autoshop_doors'] . " doors"
            );
            wp_set_post_tags($this->post_id, $this->blog_post_tags);
        }

	function do_post($update = false) {
            global $wpdb;
            $data = array(
			'ID' => $this->post_id
			, 'post_content' => $wpdb->escape($this->as_text)
			, 'post_title' => $this->create_title()
			, 'post_date' => get_date_from_gmt(date('Y-m-d H:i:s', $this->as_created_at))
			, 'post_category' => Array($this->attribs['autoshop_make'])
			, 'post_status' => 'publish'
			, 'post_author' => 1
		);
            if($update == false) {
                $this->post_id = wp_insert_post($data);
            } else {
                $this->post_id = wp_update_post($data);
            }
            $this->update_data();
            $this->update_tags();
	}
}

function autoshop_vehicle_update($id) {
    if(! wp_is_post_revision($id)) {
        $vehicle = new autoshop_vehicle($id);
        $vehicle->update_tags();
    }
}
//add_action('edit_post', 'autoshop_vehicle_update');
//add_action('draft_post', 'autoshop_vehicle_update');
add_action('publish_post', 'autoshop_vehicle_update');
add_action('save_post', 'autoshop_vehicle_update');

function autoshop_form_text($name, $id, $value) {
    return(sprintf("<div><label for='%s'>%s:</label><input type='text' maxlength='40' id='%s' name='%s' value='%s' /></div>\n", $id, $name, $id, $id, $value));
}

function autoshop_form_dropdown($name, $id, $list, $value) {
    $output = sprintf("<div><label for='%s'>%s:</label><select name='%s' id='%s' value='%s'>\n", $id, $name, $id, $id, $value);
    $i = 0;
    foreach($list as $item) {
        if($i == $value)
            $output .= sprintf("<option value='%d' selected='selected'>%s</option>\n", $i, $item);
        else
            $output .= sprintf("<option value='%d'>%s</option>\n", $i, $item);
        $i++;
    }
    $output .= "</select></div>\n";
    return($output);
}

function autoshop_vehicle_form($input = Array(), $extra = '') {
    global $autoshop_body_style_list, $autoshop_vehicle_attributes;
    global $autoshop_engine_type_list, $autoshop_transmission_list;
    global $autoshop_drive_train_list, $autoshop_fuel_list;
    global $autoshop_stereo_list;

    foreach($autoshop_vehicle_attributes as $attribute) {
        if(isset($input[$attribute][0]))
            $vehicle_data[$attribute] = $input[$attribute][0];
        else
            $vehicle_data[$attribute] = '';
    }
/*
echo "<pre>";
print_r($vehicle_data);
echo "</pre>";
*/
    $categories = get_categories( Array('parent' => 0, 'hide_empty' => false) );
    foreach($categories as $cat) {
        switch($cat->category_nicename) {
            case 'make':
                $make_id = $cat->cat_ID;
                break;
        }
    }
    $make_cat = get_categories( Array('parent' => $make_id, 'hide_empty' => false) );

    $output = '';
    if (current_user_can('publish_posts')) {
        $output .= '
        <fieldset>
            <div><label for="autoshop_make">Make:</label><select id="autoshop_make" name="autoshop_make" value="' . $vehicle_data['autoshop_make'] . '">';
    foreach($make_cat as $make) {
        if($make->cat_ID == $vehicle_data['autoshop_make'])
            $output .= sprintf("<option value='%d' selected='selected'>%s</option>\n", $make->cat_ID, $make->name);
        else
            $output .= sprintf("<option value='%d'>%s</option>\n", $make->cat_ID, $make->name);
    }
            
    $output .= '</select></div>'
      . autoshop_form_text("Model", "autoshop_model", $vehicle_data['autoshop_model'])
      . autoshop_form_text("Model Year", "autoshop_model_year", $vehicle_data['autoshop_model_year'])
      . autoshop_form_text("Price", "autoshop_price", $vehicle_data['autoshop_price'])
      . autoshop_form_text("Miles", "autoshop_miles", $vehicle_data['autoshop_miles'])
      . autoshop_form_text("Exterior Color", "autoshop_exterior_color", $vehicle_data['autoshop_exterior_color'])
      . autoshop_form_text("Interior Color", "autoshop_interior_color", $vehicle_data['autoshop_interior_color'])
      . autoshop_form_dropdown("Body Style", "autoshop_body_style", $autoshop_body_style_list, $vehicle_data['autoshop_body_style'])
      . autoshop_form_text("Engine Size", "autoshop_engine_size", $vehicle_data['autoshop_engine_size'])
      . autoshop_form_dropdown("Engine Type", "autoshop_engine_type", $autoshop_engine_type_list, $vehicle_data['autoshop_engine_type'])
      . autoshop_form_dropdown("Transmission", "autoshop_transmission", $autoshop_transmission_list, $vehicle_data['autoshop_transmission'])
      . autoshop_form_dropdown("Drive Train", "autoshop_drive_train", $autoshop_drive_train_list, $vehicle_data['autoshop_drive_train'])
      . autoshop_form_dropdown("Fuel", "autoshop_fuel", $autoshop_fuel_list, $vehicle_data['autoshop_fuel'])
      . autoshop_form_dropdown("Stereo", "autoshop_stereo", $autoshop_stereo_list, $vehicle_data['autoshop_stereo'])
      . autoshop_form_text("Doors", "autoshop_doors", $vehicle_data['autoshop_doors'])
      . autoshop_form_text("VIN", "autoshop_vin", $vehicle_data['autoshop_vin'])
      . '</fieldset>'
      . '<fieldset>'
      . '<div><label for="autoshop_cover_shot">Cover Shot</label><input type="file" name="autoshop_cover_shot" id="autoshop_cover_shot"></div>'
      . '<div><label for="driver_front">Driver Front</label><input type="file" name="driver_front" id="driver_front"></div>'
      . '</fieldset>'
      . '<div class="autoshop_bottom"><label for="autoshop_description">Description:</label><textarea id="autoshop_description" name="autoshop_description" >'
      . $vehicle_data['autoshop_description']
      . '</textarea></div>'
      . wp_nonce_field('autoshop_new_vehicle', '_wpnonce', true, false).wp_referer_field(false).'
        ';
//FOOO
    }
    return $output;
}

function autoshop_head() {
    print('
        <link rel="stylesheet" type="text/css" href="'.site_url('/index.php?ak_action=autoshop_css').'" />
    ');
}
add_action('wp_head', 'autoshop_head');

function autoshop_head_admin() {
	print('
		<link rel="stylesheet" type="text/css" href="'.admin_url('index.php?ak_action=autoshop_css_admin').'" />
		<script type="text/javascript" src="'.admin_url('index.php?ak_action=aktt_js_admin').'"></script>
	');
}
add_action('admin_head', 'autoshop_head_admin');

function aktt_resources() {
	if (!empty($_GET['ak_action'])) {
		switch($_GET['ak_action']) {
			case 'aktt_js':
				header("Content-type: text/javascript");
				switch ($aktt->js_lib) {
					case 'jquery':
?>
function akttPostTweet() {
	var tweet_field = jQuery('#aktt_tweet_text');
	var tweet_form = tweet_field.parents('form');
	var tweet_text = tweet_field.val();
	if (tweet_text == '') {
		return;
	}
	var tweet_msg = jQuery("#aktt_tweet_posted_msg");
	var nonce = jQuery(tweet_form).find('input[name=_wpnonce]').val();
	var refer = jQuery(tweet_form).find('input[name=_wp_http_referer]').val();
	jQuery.post(
		"<?php echo site_url('index.php'); ?>",
		{
			ak_action: "aktt_post_tweet_sidebar", 
			aktt_tweet_text: tweet_text,
			_wpnonce: nonce,
			_wp_http_referer: refer
		},
		function(data) {
			tweet_msg.html(data);
			akttSetReset();
		}
	);
	tweet_field.val('').focus();
	jQuery('#aktt_char_count').html('');
	jQuery("#aktt_tweet_posted_msg").show();
}
function akttSetReset() {
	setTimeout('akttReset();', 2000);
}
function akttReset() {
	jQuery('#aktt_tweet_posted_msg').hide();
}
<?php
						break;
					case 'prototype':
?>
function akttPostTweet() {
	var tweet_field = $('aktt_tweet_text');
	var tweet_text = tweet_field.value;
	if (tweet_text == '') {
		return;
	}
	var tweet_msg = $("aktt_tweet_posted_msg");
	var nonce = $('_wpnonce').value;
	var refer = $('_wpnonce').next('input').value;
	var akttAjax = new Ajax.Updater(
		tweet_msg,
		"<?php echo site_url('index.php'); ?>",
		{
			method: "post",
			parameters: "ak_action=aktt_post_tweet_sidebar&aktt_tweet_text=" + tweet_text + '&_wpnonce=' + nonce + '&_wp_http_referer=' + refer,
			onComplete: akttSetReset
		}
	);
	tweet_field.value = '';
	tweet_field.focus();
	$('aktt_char_count').innerHTML = '';
	tweet_msg.style.display = 'block';
}
function akttSetReset() {
	setTimeout('akttReset();', 2000);
}
function akttReset() {
	$('aktt_tweet_posted_msg').style.display = 'none';
}
<?php
						break;
				}
				die();
				break;
			case 'autoshop_css':
				header("Content-Type: text/css");
?>
.autoshop_vehicle {
    width:300px;
    float:left;
}
.autoshop_vehicle dt {
    margin:0;
    padding:2px 0 2px 5px;
    width:120px;
    display:inline-block;
    border-bottom: 1px solid grey;
}
.autoshop_vehicle dd {
    margin:0;
    padding:0;
    width:175px;
    padding:2px 0 2px 0;
    display:inline-block;
    border-bottom: 1px solid grey;
}
.autoshop_vehicle_photos {
    margin-left:305px;
}
.autoshop_vehicle_photos img {
    width:450px;
    border:2px solid black;
    -moz-border-radius: 5px;
}
.autoshop_vehicle_list {
    width: 100%;
    margin-top:10px;
    border-collapse:collapse;
}
.autoshop_vehicle_list td {
    padding:5px;
}

.autoshop_vehicle_list td {
    vertical-align: text-top;
}

.autoshop_vehicle_list td + td + td {
    text-align: right;
    width: 80px;
    padding-right:25px;
}

.autoshop_vehicle_list tr.even {
    border: 1px solid lightgrey;
    border-bottom: 1px solid black;
}
.autoshop_vehicle_list tr.odd {
    border: 1px solid lightgrey;
    border-bottom: 1px solid black;
    background-color:lightgrey;
}
.autoshop_cover_shot  {
    display:block;
    width:100px;
    height:75px;
}
/*
*/

<?php
				die();
				break;
			case 'aktt_js_admin':
				header("Content-Type: text/javascript");
?>
function akttTestLogin() {
	var result = jQuery('#aktt_login_test_result');
	result.show().addClass('aktt_login_result_wait').html('<?php _e('Testing...', 'twitter-tools'); ?>');
	jQuery.post(
		"<?php echo admin_url('index.php'); ?>",
		{
			ak_action: "aktt_login_test",
			aktt_twitter_username: jQuery('#aktt_twitter_username').val(),
			aktt_twitter_password: jQuery('#aktt_twitter_password').val()
		},
		function(data) {
			result.html(data).removeClass('aktt_login_result_wait');
			setTimeout('akttTestLoginResult();', 5000);
		}
	);
};

function akttTestLoginResult() {
	jQuery('#aktt_login_test_result').fadeOut('slow');
};

(function($){

	jQuery.fn.timepicker = function(){
	
		var hrs = new Array();
		for(var h = 1; h <= 12; hrs.push(h++));

		var mins = new Array();
		for(var m = 0; m < 60; mins.push(m++));

		var ap = new Array('am', 'pm');

		function pad(n) {
			n = n.toString();
			return n.length == 1 ? '0' + n : n;
		}
	
		this.each(function() {

			var v = $(this).val();
			if (!v) v = new Date();

			var d = new Date(v);
			var h = d.getHours();
			var m = d.getMinutes();
			var p = (h >= 12) ? "pm" : "am";
			h = (h > 12) ? h - 12 : h;

			var output = '';

			output += '<select id="h_' + this.id + '" class="timepicker">';				
			for (var hr in hrs){
				output += '<option value="' + pad(hrs[hr]) + '"';
				if(parseInt(hrs[hr], 10) == h || (parseInt(hrs[hr], 10) == 12 && h == 0)) output += ' selected';
				output += '>' + pad(hrs[hr]) + '</option>';
			}
			output += '</select>';
	
			output += '<select id="m_' + this.id + '" class="timepicker">';				
			for (var mn in mins){
				output += '<option value="' + pad(mins[mn]) + '"';
				if(parseInt(mins[mn], 10) == m) output += ' selected';
				output += '>' + pad(mins[mn]) + '</option>';
			}
			output += '</select>';				
	
			output += '<select id="p_' + this.id + '" class="timepicker">';				
			for(var pp in ap){
				output += '<option value="' + ap[pp] + '"';
				if(ap[pp] == p) output += ' selected';
				output += '>' + ap[pp] + '</option>';
			}
			output += '</select>';
			
			$(this).after(output);
			
			var field = this;
			$(this).siblings('select.timepicker').change(function() {
				var h = parseInt($('#h_' + field.id).val(), 10);
				var m = parseInt($('#m_' + field.id).val(), 10);
				var p = $('#p_' + field.id).val();
	
				if (p == "am") {
					if (h == 12) {
						h = 0;
					}
				} else if (p == "pm") {
					if (h < 12) {
						h += 12;
					}
				}
				
				var d = new Date();
				d.setHours(h);
				d.setMinutes(m);
				
				$(field).val(d.toUTCString());
			}).change();

		});

		return this;
	};
	
	jQuery.fn.daypicker = function() {
		
		var days = new Array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		
		this.each(function() {
			
			var v = $(this).val();
			if (!v) v = 0;
			v = parseInt(v, 10);
			
			var output = "";
			output += '<select id="d_' + this.id + '" class="daypicker">';				
			for (var i = 0; i < days.length; i++) {
				output += '<option value="' + i + '"';
				if (v == i) output += ' selected';
				output += '>' + days[i] + '</option>';
			}
			output += '</select>';
			
			$(this).after(output);
			
			var field = this;
			$(this).siblings('select.daypicker').change(function() {
				$(field).val( $(this).val() );
			}).change();
		
		});
		
	};
	
	jQuery.fn.forceToggleClass = function(classNames, bOn) {
		return this.each(function() {
			jQuery(this)[ bOn ? "addClass" : "removeClass" ](classNames);
		});
	};
	
})(jQuery);

jQuery(function() {

	// add in the time and day selects
	jQuery('form#ak_twittertools input.time').timepicker();
	jQuery('form#ak_twittertools input.day').daypicker();
	
	// togglers
	jQuery('.time_toggle .toggler').change(function() {
		var theSelect = jQuery(this);
		theSelect.parent('.time_toggle').forceToggleClass('active', theSelect.val() === "1");
	}).change();
	
});
<?php
				die();
				break;
			case 'autoshop_css_admin':
				header("Content-Type: text/css");
?>
#autoshop_vehicle_form label {
     width:10em;
     display:inline-block;
}
#autoshop_vehicle_form input,
#autoshop_vehicle_form select {
     width:200px;
}
#autoshop_vehicle_form fieldset {
    width:350px;
    float:left;
}
#autoshop_vehicle_form fieldset + fieldset{
    margin-left: 5px;
    width:auto;
}
.autoshop_bottom {
    width:100%;
    clear:both;
}
#autoshop_vehicle_form .autoshop_bottom label {
    display:block;
    float:left;
    width:18%;
}
#autoshop_vehicle_form .autoshop_bottom textarea {
    width: 80%;
    height: 10em;
}
<?php
				die();
				break;
			case 'autoshop_vehicle_list':
                            echo json_encode(autoshop_get_vehicles());
                            die();
                            break;
		}
	}
}
add_action('init', 'aktt_resources', 1);

function aktt_request_handler() {
	global $wpdb, $aktt;
	if (!empty($_GET['ak_action'])) {
		switch($_GET['ak_action']) {
			case 'aktt_update_tweets':
				if (!wp_verify_nonce($_GET['_wpnonce'], 'aktt_update_tweets')) {
					wp_die('Oops, please try again.');
				}
				aktt_update_tweets();
				wp_redirect(admin_url('options-general.php?page=twitter-tools.php&tweets-updated=true'));
				die();
				break;
			case 'aktt_reset_tweet_checking':
				if (!wp_verify_nonce($_GET['_wpnonce'], 'aktt_update_tweets')) {
					wp_die('Oops, please try again.');
				}
				aktt_reset_tweet_checking();
				wp_redirect(admin_url('options-general.php?page=twitter-tools.php&tweet-checking-reset=true'));
				die();
				break;
			case 'aktt_reset_digests':
				if (!wp_verify_nonce($_GET['_wpnonce'], 'aktt_update_tweets')) {
					wp_die('Oops, please try again.');
				}
				aktt_reset_digests();
				wp_redirect(admin_url('options-general.php?page=twitter-tools.php&digest-reset=true'));
				die();
				break;
		}
	}
	if (!empty($_POST['ak_action'])) {
		switch($_POST['ak_action']) {
			case 'aktt_update_settings':
				if (!wp_verify_nonce($_POST['_wpnonce'], 'aktt_settings')) {
					wp_die('Oops, please try again.');
				}
				$aktt->populate_settings();
				$aktt->update_settings();
				wp_redirect(admin_url('options-general.php?page=twitter-tools.php&updated=true'));
				die();
				break;
			case 'aktt_post_tweet_sidebar':
				if (!empty($_POST['aktt_tweet_text']) && current_user_can('publish_posts')) {
					if (!wp_verify_nonce($_POST['_wpnonce'], 'aktt_new_tweet')) {
						wp_die('Oops, please try again.');
					}
					$tweet = new aktt_tweet();
					$tweet->tw_text = stripslashes($_POST['aktt_tweet_text']);
					if ($aktt->do_tweet($tweet)) {
						die(__('Tweet posted.', 'twitter-tools'));
					}
					else {
						die(__('Tweet post failed.', 'twitter-tools'));
					}
				}
				break;
			case 'aktt_post_tweet_admin':
				if (!empty($_POST['aktt_tweet_text']) && current_user_can('publish_posts')) {
					if (!wp_verify_nonce($_POST['_wpnonce'], 'aktt_new_tweet')) {
						wp_die('Oops, please try again.');
					}
					$tweet = new aktt_tweet();
					$tweet->tw_text = stripslashes($_POST['aktt_tweet_text']);
					if ($aktt->do_tweet($tweet)) {
						wp_redirect(admin_url('post-new.php?page=twitter-tools.php&tweet-posted=true'));
					}
					else {
						wp_die(__('Oops, your tweet was not posted. Please check your username and password and that Twitter is up and running happily.', 'twitter-tools'));
					}
					die();
				}
				break;
			case 'autoshop_post_vehicle':
                            include("wp-admin/includes/image.php");
                            if (current_user_can('publish_posts')) {
                                        if (!wp_verify_nonce($_POST['_wpnonce'], 'autoshop_new_vehicle')) {
						wp_die('Oops, please try again.');
					}
                                        if(isset($_POST['autoshop_vid']))
                                            $update = true;
                                        else
                                            $update = false;
					$vehicle = new autoshop_vehicle($_POST);
                                        $vehicle->do_post($update);
                                        $wud = wp_upload_dir();
                                        $dir = $wud['basedir'] . "/autoshop/" . $vehicle->post_id;
                                        list($make) = get_categories( Array('include' => $vehicle->attribs['autoshop_make'], 'hide_empty' => false ));
                                        $title = sprintf("%d %s %s %s"
                                                        , $vehicle->attribs['autoshop_model_year']
                                                        , $vehicle->attribs['autoshop_exterior_color']
                                                        , $make->name
                                                        , $vehicle->attribs['autoshop_model']
                                                 );
                                        $fname = sprintf("%s/%d-%s-%s-%s-autoshop_cover_shot.jpg"
                                                        , $dir
                                                        , $vehicle->attribs['autoshop_model_year']
                                                        , strtolower(str_replace('/','', str_replace(' ', '-', $vehicle->attribs['autoshop_exterior_color'])))
                                                        , $make->slug
                                                        , strtolower(str_replace(' ', '-', $vehicle->attribs['autoshop_model']))
                                                 );
                                        $img = Array(
                                              'post_title'     => $title
                                            , 'post_content'   => ''
                                            , 'pos_status'    => 'publish'
                                            , 'post_mime_type' => $_FILES['autoshop_cover_shot']['type']
                                        );
                                        if(! is_dir($dir))
                                            mkdir($dir, 755, true);
                                        move_uploaded_file($_FILES['autoshop_cover_shot']['tmp_name'], $fname);
/*
echo "<pre>";
print_r($attach_data);
print_r($attach_id);
print_r($_FILES);
print_r($dir);
print_r($make);
print_r($img);
echo "</pre>";
*/
//WOR

                                        wp_redirect(admin_url('admin.php?page=autoshop-menu'));
                                        exit();
				}
				break;
			case 'aktt_login_test':
				$test = @aktt_login_test(
					@stripslashes($_POST['aktt_twitter_username'])
					, @stripslashes($_POST['aktt_twitter_password'])
				);
				die(__($test, 'twitter-tools'));
				break;
		}
	}
}
add_action('init', 'aktt_request_handler', 10);

function autoshop_edit_vehicle_form() {
    global $_GET, $PHP_SELF, $_POST, $autoshop_vehicle_attributes;
    if(isset($_GET['autoshop_vid'])) {
        $vehicle_data = get_post_custom($_GET['autoshop_vid']);
        list($make) = get_categories( Array('include' => $vehicle_data['autoshop_make'][0], 'hide_empty' => false ));
        $output .= sprintf("<h1>%d %s %s</h1>", $vehicle_data['autoshop_model_year'][0], $make->name, $vehicle_data['autoshop_model'][0]);
        $output .= '<form action="'. site_url('index.php') .'" method="post" id="autoshop_vehicle_form" enctype="multipart/form-data">>';
        $output .= '<input type="hidden" name="ak_action" value="autoshop_post_vehicle" />';
        $output .= autoshop_vehicle_form($vehicle_data);
        $output .= '<input type="submit" id="aktt_tweet_submit" name="aktt_tweet_submit" value="'.__('Update Vechicle!', 'autoshop').'" class="button-primary" />';
        $output .= '<input type="hidden" id="autoshop_vid" name="autoshop_vid" value="' . $_GET['autoshop_vid'] . '" />';
        $output .= '</form>';
    }
    else {
        $vehicle_data = autoshop_get_vehicles(Array('category'=>"Make"));
        $output = "\n<table class='widefat post fixed'>\n<thead><tr><th>Vehicle</th><th>Action</th></tr></thead><tbody>\n";
        echo "<pre>";
        $i = 1;
        foreach($vehicle_data as $vehicle) {
            if($i % 2)
                $row = '';
            else
                $row = 'alternate';
            $output .= sprintf("<tr class='%s'><td><a href='%s&amp;autoshop_vid=%d'>%d %s %s</a></td><td></td></tr>\n"
                               , $row
                               , admin_url('admin.php?page=autoshop-menu')
                               , $vehicle['autoshop_vid']
                               , $vehicle['autoshop_model_year']
                               , $vehicle['autoshop_make']
                               , $vehicle['autoshop_model']
                       );
            $i++;
        }
        $output .= '</tbody></table>';
    }
    echo $output;
}

function autoshop_new_vehicle_form() {
	global $aktt;
	if ( $_GET['new-vehicle'] ) {
		print('
			<div id="message" class="updated fade">
				<p>'.__('Car added.', 'auto-shop').'</p>
			</div>
		');
	}
        print('
            <div class="wrap" id="autoshop_new_vehicle">
              <h2>'.__('New Vehicle', 'auto-shop').'</h2>
              <p>'.__('Fill out this form to add a vehicle to your inventory</a>.', 'twitter-tools').'</p>
              <form action="'.site_url('index.php').'" method="post" id="autoshop_vehicle_form" enctype="multipart/form-data">
              <input type="hidden" name="ak_action" value="autoshop_post_vehicle" />
            '.autoshop_vehicle_form().'
              <input type="submit" id="aktt_tweet_submit" name="aktt_tweet_submit" value="'.__('Add Vechicle!', 'autoshop').'" class="button-primary" />
              </form>
            </div>
        ');
}

function autoshop_options_form() {
	global $wpdb, $aktt;

	$categories = get_categories('hide_empty=0');
	$cat_options = '';
	foreach ($categories as $category) {
// WP < 2.3 compatibility
		!empty($category->term_id) ? $cat_id = $category->term_id : $cat_id = $category->cat_ID;
		!empty($category->name) ? $cat_name = $category->name : $cat_name = $category->cat_name;
		if ($cat_id == $aktt->blog_post_category) {
			$selected = 'selected="selected"';
		}
		else {
			$selected = '';
		}
		$cat_options .= "\n\t<option value='$cat_id' $selected>$cat_name</option>";
	}

	$authors = get_users_of_blog();
	$author_options = '';
	foreach ($authors as $user) {
		$usero = new WP_User($user->user_id);
		$author = $usero->data;
		// Only list users who are allowed to publish
		if (! $usero->has_cap('publish_posts')) {
			continue;
		}
		if ($author->ID == $aktt->blog_post_author) {
			$selected = 'selected="selected"';
		}
		else {
			$selected = '';
		}
		$author_options .= "\n\t<option value='$author->ID' $selected>$author->user_nicename</option>";
	}
	
	$js_libs = array(
		'jquery' => 'jQuery'
		, 'prototype' => 'Prototype'
	);
	$js_lib_options = '';
	foreach ($js_libs as $js_lib => $js_lib_display) {
		if ($js_lib == $aktt->js_lib) {
			$selected = 'selected="selected"';
		}
		else {
			$selected = '';
		}
		$js_lib_options .= "\n\t<option value='$js_lib' $selected>$js_lib_display</option>";
	}
	$digest_tweet_orders = array(
		'ASC' => __('Oldest first (Chronological order)', 'twitter-tools'),
		'DESC' => __('Newest first (Reverse-chronological order)', 'twitter-tools')
	);
	$digest_tweet_order_options = '';
	foreach ($digest_tweet_orders as $digest_tweet_order => $digest_tweet_order_display) {
		if ($digest_tweet_order == $aktt->digest_tweet_order) {
			$selected = 'selected="selected"';
		}
		else {
			$selected = '';
		}
		$digest_tweet_order_options .= "\n\t<option value='$digest_tweet_order' $selected>$digest_tweet_order_display</option>";
	}	
	$yes_no = array(
		'create_blog_posts'
		, 'create_digest'
		, 'create_digest_weekly'
		, 'notify_twitter'
		, 'notify_twitter_default'
		, 'tweet_from_sidebar'
		, 'give_tt_credit'
		, 'exclude_reply_tweets'
	);
	foreach ($yes_no as $key) {
		$var = $key.'_options';
		if ($aktt->$key == '0') {
			$$var = '
				<option value="0" selected="selected">'.__('No', 'twitter-tools').'</option>
				<option value="1">'.__('Yes', 'twitter-tools').'</option>
			';
		}
		else {
			$$var = '
				<option value="0">'.__('No', 'twitter-tools').'</option>
				<option value="1" selected="selected">'.__('Yes', 'twitter-tools').'</option>
			';
		}
	}
	if ( $_GET['tweets-updated'] ) {
		print('
			<div id="message" class="updated fade">
				<p>'.__('Tweets updated.', 'twitter-tools').'</p>
			</div>
		');
	}
	if ( $_GET['tweet-checking-reset'] ) {
		print('
			<div id="message" class="updated fade">
				<p>'.__('Tweet checking has been reset.', 'twitter-tools').'</p>
			</div>
		');
	}
	print('
            <div class="wrap" id="aktt_options_page">
                <h2>'.__('Auto Shop Options', 'auto-shop').'</h2>
                <form id="ots_autoshop" name="ots_autoshop" action="'.admin_url('options-general.php').'" method="post">
                    <fieldset class="options">
                        <div class="option">
                            There are currently no options.
                        </div>
                    </fieldset>
                </form>
	');
?>
<!--
  - good idea, make it point to me.
<div style="padding-left: 50px; width: 600px">
<script type="text/javascript">
var WPHC_AFF_ID = '14303';
var WPHC_WP_VERSION = '<?php global $wp_version; echo $wp_version; ?>';
</script>
<script type="text/javascript"
	src="http://cloud.wphelpcenter.com/wp-admin/0001/deliver.js">
</script>
</div>
  -->
<?php
}

function aktt_store_post_options($post_id, $post = false) {
	global $aktt;
	$post = get_post($post_id);
	if (!$post || $post->post_type == 'revision') {
		return;
	}

	$notify_meta = get_post_meta($post_id, 'aktt_notify_twitter', true);
	$posted_meta = $_POST['aktt_notify_twitter'];

	$save = false;
	if (!empty($posted_meta)) {
		$posted_meta == 'yes' ? $meta = 'yes' : $meta = 'no';
		$save = true;
	}
	else if (empty($notify_meta)) {
		$aktt->notify_twitter_default ? $meta = 'yes' : $meta = 'no';
		$save = true;
	}
	else {
		$save = false;
	}
	
	if ($save) {
		if (!update_post_meta($post_id, 'aktt_notify_twitter', $meta)) {
			add_post_meta($post_id, 'aktt_notify_twitter', $meta);
		}
	}
}
//add_action('draft_post', 'aktt_store_post_options', 1, 2);
//add_action('publish_post', 'aktt_store_post_options', 1, 2);
//add_action('save_post', 'aktt_store_post_options', 1, 2);

function aktt_menu_items() {
/*
	if (current_user_can('manage_options')) {
		add_options_page(
			__('Auto Shop Options', 'auto-shop')
			, __('Auto Shop', 'auto-shop')
			, 10
			, basename(__FILE__)
			, 'autoshop_options_form'
		);
	}
*/
	if (current_user_can('publish_posts')) {
		add_menu_page(
			'Auto Shop'
                      , 'Auto Shop'
                      , 2
                      , 'autoshop-menu'
		);
		add_submenu_page(
			'autoshop-menu'
			,'Edit Vehicle'
			,'Edit Vehicle'
			, 2
			, 'autoshop-menu'
			, 'autoshop_edit_vehicle_form'
		);
		add_submenu_page(
			'autoshop-menu'
			,'New Vehicle'
			,'New Vehicle'
			, 2
			, basename(__FILE__)
			, 'autoshop_new_vehicle_form'
		);
	}
}
add_action('admin_menu', 'aktt_menu_items');

function aktt_plugin_action_links($links, $file) {
	$plugin_file = basename(__FILE__);
	if (basename($file) == $plugin_file) {
		$settings_link = '<a href="options-general.php?page='.$plugin_file.'">'.__('Settings', 'twitter-tools').'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
add_filter('plugin_action_links', 'aktt_plugin_action_links', 10, 2);

if (!function_exists('trim_add_elipsis')) {
	function trim_add_elipsis($string, $limit = 100) {
		if (strlen($string) > $limit) {
			$string = substr($string, 0, $limit)."...";
		}
		return $string;
	}
}

if (!function_exists('ak_gmmktime')) {
	function ak_gmmktime() {
		return gmmktime() - get_option('gmt_offset') * 3600;
	}
}

/**

based on: http://www.gyford.com/phil/writing/2006/12/02/quick_twitter.php

	 * Returns a relative date, eg "4 hrs ago".
	 *
	 * Assumes the passed-in can be parsed by strtotime.
	 * Precision could be one of:
	 * 	1	5 hours, 3 minutes, 2 seconds ago (not yet implemented).
	 * 	2	5 hours, 3 minutes
	 * 	3	5 hours
	 *
	 * This is all a little overkill, but copied from other places I've used it.
	 * Also superfluous, now I've noticed that the Twitter API includes something
	 * similar, but this version is more accurate and less verbose.
	 *
	 * @access private.
	 * @param string date In a format parseable by strtotime().
	 * @param integer precision
	 * @return string
	 */
function aktt_relativeTime ($date, $precision=2)
{

	$now = time();

	$time = gmmktime(
		substr($date, 11, 2)
		, substr($date, 14, 2)
		, substr($date, 17, 2)
		, substr($date, 5, 2)
		, substr($date, 8, 2)
		, substr($date, 0, 4)
	);

	$time = strtotime(date('Y-m-d H:i:s', $time));

	$diff 	=  $now - $time;

	$months	=  floor($diff/2419200);
	$diff 	-= $months * 2419200;
	$weeks 	=  floor($diff/604800);
	$diff	-= $weeks*604800;
	$days 	=  floor($diff/86400);
	$diff 	-= $days * 86400;
	$hours 	=  floor($diff/3600);
	$diff 	-= $hours * 3600;
	$minutes = floor($diff/60);
	$diff 	-= $minutes * 60;
	$seconds = $diff;

	if ($months > 0) {
		return date_i18n( __('Y-m-d', 'twitter-tools'), $time);
	} else {
		$relative_date = '';
		if ($weeks > 0) {
			// Weeks and days
			$relative_date .= ($relative_date?', ':'').$weeks.' '.__ngettext('week', 'weeks', $weeks, 'twitter-tools');
			if ($precision <= 2) {
				$relative_date .= $days>0? ($relative_date?', ':'').$days.' '.__ngettext('day', 'days', $days, 'twitter-tools'):'';
				if ($precision == 1) {
					$relative_date .= $hours>0?($relative_date?', ':'').$hours.' '.__ngettext('hr', 'hrs', $hours, 'twitter-tools'):'';
				}
			}
		} elseif ($days > 0) {
			// days and hours
			$relative_date .= ($relative_date?', ':'').$days.' '.__ngettext('day', 'days', $days, 'twitter-tools');
			if ($precision <= 2) {
				$relative_date .= $hours>0?($relative_date?', ':'').$hours.' '.__ngettext('hr', 'hrs', $hours, 'twitter-tools'):'';
				if ($precision == 1) {
					$relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' '.__ngettext('min', 'mins', $minutes, 'twitter-tools'):'';
				}
			}
		} elseif ($hours > 0) {
			// hours and minutes
			$relative_date .= ($relative_date?', ':'').$hours.' '.__ngettext('hr', 'hrs', $hours, 'twitter-tools');
			if ($precision <= 2) {
				$relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' '.__ngettext('min', 'mins', $minutes, 'twitter-tools'):'';
				if ($precision == 1) {
					$relative_date .= $seconds>0?($relative_date?', ':'').$seconds.' '.__ngettext('sec', 'secs', $seconds, 'twitter-tools'):'';
				}
			}
		} elseif ($minutes > 0) {
			// minutes only
			$relative_date .= ($relative_date?', ':'').$minutes.' '.__ngettext('min', 'mins', $minutes, 'twitter-tools');
			if ($precision == 1) {
				$relative_date .= $seconds>0?($relative_date?', ':'').$seconds.' '.__ngettext('sec', 'secs', $seconds, 'twitter-tools'):'';
			}
		} else {
			// seconds only
			$relative_date .= ($relative_date?', ':'').$seconds.' '.__ngettext('sec', 'secs', $seconds, 'twitter-tools');
		}
	}

	// Return relative date and add proper verbiage
	return sprintf(__('%s ago', 'twitter-tools'), $relative_date);
}

// For PHP < 5.2.0
if ( !function_exists('json_encode') ) {

	if (!class_exists('Services_JSON')) { // still make this conditional

// PEAR JSON class

/**
* Converts to and from JSON format.
*
* JSON (JavaScript Object Notation) is a lightweight data-interchange
* format. It is easy for humans to read and write. It is easy for machines
* to parse and generate. It is based on a subset of the JavaScript
* Programming Language, Standard ECMA-262 3rd Edition - December 1999.
* This feature can also be found in  Python. JSON is a text format that is
* completely language independent but uses conventions that are familiar
* to programmers of the C-family of languages, including C, C++, C#, Java,
* JavaScript, Perl, TCL, and many others. These properties make JSON an
* ideal data-interchange language.
*
* This package provides a simple encoder and decoder for JSON notation. It
* is intended for use with client-side Javascript applications that make
* use of HTTPRequest to perform server communication functions - data can
* be encoded into JSON notation for use in a client-side javascript, or
* decoded from incoming Javascript requests. JSON format is native to
* Javascript, and can be directly eval()'ed with no further parsing
* overhead
*
* All strings should be in ASCII or UTF-8 format!
*
* LICENSE: Redistribution and use in source and binary forms, with or
* without modification, are permitted provided that the following
* conditions are met: Redistributions of source code must retain the
* above copyright notice, this list of conditions and the following
* disclaimer. Redistributions in binary form must reproduce the above
* copyright notice, this list of conditions and the following disclaimer
* in the documentation and/or other materials provided with the
* distribution.
*
* THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
* WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
* MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
* NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
* INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
* BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
* OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
* TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
* USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
* DAMAGE.
*
* @category
* @package     Services_JSON
* @author      Michal Migurski <mike-json@teczno.com>
* @author      Matt Knapp <mdknapp[at]gmail[dot]com>
* @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
* @copyright   2005 Michal Migurski
* @version     CVS: $Id: JSON.php,v 1.31 2006/06/28 05:54:17 migurski Exp $
* @license     http://www.opensource.org/licenses/bsd-license.php
* @link        http://pear.php.net/pepr/pepr-proposal-show.php?id=198
*/

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_SLICE',   1);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_STR',  2);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_ARR',  3);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_OBJ',  4);

/**
* Marker constant for Services_JSON::decode(), used to flag stack state
*/
define('SERVICES_JSON_IN_CMT', 5);

/**
* Behavior switch for Services_JSON::decode()
*/
define('SERVICES_JSON_LOOSE_TYPE', 16);

/**
* Behavior switch for Services_JSON::decode()
*/
define('SERVICES_JSON_SUPPRESS_ERRORS', 32);

/**
* Converts to and from JSON format.
*
* Brief example of use:
*
* <code>
* // create a new instance of Services_JSON
* $json = new Services_JSON();
*
* // convert a complexe value to JSON notation, and send it to the browser
* $value = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
* $output = $json->encode($value);
*
* print($output);
* // prints: ["foo","bar",[1,2,"baz"],[3,[4]]]
*
* // accept incoming POST data, assumed to be in JSON notation
* $input = file_get_contents('php://input', 1000000);
* $value = $json->decode($input);
* </code>
*/
class Services_JSON
{
   /**
    * constructs a new JSON instance
    *
    * @param    int     $use    object behavior flags; combine with boolean-OR
    *
    *                           possible values:
    *                           - SERVICES_JSON_LOOSE_TYPE:  loose typing.
    *                                   "{...}" syntax creates associative arrays
    *                                   instead of objects in decode().
    *                           - SERVICES_JSON_SUPPRESS_ERRORS:  error suppression.
    *                                   Values which can't be encoded (e.g. resources)
    *                                   appear as NULL instead of throwing errors.
    *                                   By default, a deeply-nested resource will
    *                                   bubble up with an error, so all return values
    *                                   from encode() should be checked with isError()
    */
    function Services_JSON($use = 0)
    {
        $this->use = $use;
    }

   /**
    * convert a string from one UTF-16 char to one UTF-8 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf16  UTF-16 character
    * @return   string  UTF-8 character
    * @access   private
    */
    function utf162utf8($utf16)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
        }

        $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

        switch(true) {
            case ((0x7F & $bytes) == $bytes):
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x7F & $bytes);

            case (0x07FF & $bytes) == $bytes:
                // return a 2-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xC0 | (($bytes >> 6) & 0x1F))
                     . chr(0x80 | ($bytes & 0x3F));

            case (0xFFFF & $bytes) == $bytes:
                // return a 3-byte UTF-8 character
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0xE0 | (($bytes >> 12) & 0x0F))
                     . chr(0x80 | (($bytes >> 6) & 0x3F))
                     . chr(0x80 | ($bytes & 0x3F));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

   /**
    * convert a string from one UTF-8 char to one UTF-16 char
    *
    * Normally should be handled by mb_convert_encoding, but
    * provides a slower PHP-only method for installations
    * that lack the multibye string extension.
    *
    * @param    string  $utf8   UTF-8 character
    * @return   string  UTF-16 character
    * @access   private
    */
    function utf82utf16($utf8)
    {
        // oh please oh please oh please oh please oh please
        if(function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
        }

        switch(strlen($utf8)) {
            case 1:
                // this case should never be reached, because we are in ASCII range
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return $utf8;

            case 2:
                // return a UTF-16 character from a 2-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr(0x07 & (ord($utf8{0}) >> 2))
                     . chr((0xC0 & (ord($utf8{0}) << 6))
                         | (0x3F & ord($utf8{1})));

            case 3:
                // return a UTF-16 character from a 3-byte UTF-8 char
                // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                return chr((0xF0 & (ord($utf8{0}) << 4))
                         | (0x0F & (ord($utf8{1}) >> 2)))
                     . chr((0xC0 & (ord($utf8{1}) << 6))
                         | (0x7F & ord($utf8{2})));
        }

        // ignoring UTF-32 for now, sorry
        return '';
    }

   /**
    * encodes an arbitrary variable into JSON format
    *
    * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
    *                           see argument 1 to Services_JSON() above for array-parsing behavior.
    *                           if var is a strng, note that encode() always expects it
    *                           to be in ASCII or UTF-8 format!
    *
    * @return   mixed   JSON string representation of input var or an error if a problem occurs
    * @access   public
    */
    function encode($var)
    {
        switch (gettype($var)) {
            case 'boolean':
                return $var ? 'true' : 'false';

            case 'NULL':
                return 'null';

            case 'integer':
                return (int) $var;

            case 'double':
            case 'float':
                return (float) $var;

            case 'string':
                // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
                $ascii = '';
                $strlen_var = strlen($var);

               /*
                * Iterate over every character in the string,
                * escaping with a slash or encoding to UTF-8 where necessary
                */
                for ($c = 0; $c < $strlen_var; ++$c) {

                    $ord_var_c = ord($var{$c});

                    switch (true) {
                        case $ord_var_c == 0x08:
                            $ascii .= '\b';
                            break;
                        case $ord_var_c == 0x09:
                            $ascii .= '\t';
                            break;
                        case $ord_var_c == 0x0A:
                            $ascii .= '\n';
                            break;
                        case $ord_var_c == 0x0C:
                            $ascii .= '\f';
                            break;
                        case $ord_var_c == 0x0D:
                            $ascii .= '\r';
                            break;

                        case $ord_var_c == 0x22:
                        case $ord_var_c == 0x2F:
                        case $ord_var_c == 0x5C:
                            // double quote, slash, slosh
                            $ascii .= '\\'.$var{$c};
                            break;

                        case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                            // characters U-00000000 - U-0000007F (same as ASCII)
                            $ascii .= $var{$c};
                            break;

                        case (($ord_var_c & 0xE0) == 0xC0):
                            // characters U-00000080 - U-000007FF, mask 110XXXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c, ord($var{$c + 1}));
                            $c += 1;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xF0) == 0xE0):
                            // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}));
                            $c += 2;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xF8) == 0xF0):
                            // characters U-00010000 - U-001FFFFF, mask 11110XXX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}));
                            $c += 3;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xFC) == 0xF8):
                            // characters U-00200000 - U-03FFFFFF, mask 111110XX
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}));
                            $c += 4;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;

                        case (($ord_var_c & 0xFE) == 0xFC):
                            // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                            // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                            $char = pack('C*', $ord_var_c,
                                         ord($var{$c + 1}),
                                         ord($var{$c + 2}),
                                         ord($var{$c + 3}),
                                         ord($var{$c + 4}),
                                         ord($var{$c + 5}));
                            $c += 5;
                            $utf16 = $this->utf82utf16($char);
                            $ascii .= sprintf('\u%04s', bin2hex($utf16));
                            break;
                    }
                }

                return '"'.$ascii.'"';

            case 'array':
               /*
                * As per JSON spec if any array key is not an integer
                * we must treat the the whole array as an object. We
                * also try to catch a sparsely populated associative
                * array with numeric keys here because some JS engines
                * will create an array with empty indexes up to
                * max_index which can cause memory issues and because
                * the keys, which may be relevant, will be remapped
                * otherwise.
                *
                * As per the ECMA and JSON specification an object may
                * have any string as a property. Unfortunately due to
                * a hole in the ECMA specification if the key is a
                * ECMA reserved word or starts with a digit the
                * parameter is only accessible using ECMAScript's
                * bracket notation.
                */

                // treat as a JSON object
                if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                    $properties = array_map(array($this, 'name_value'),
                                            array_keys($var),
                                            array_values($var));

                    foreach($properties as $property) {
                        if(Services_JSON::isError($property)) {
                            return $property;
                        }
                    }

                    return '{' . join(',', $properties) . '}';
                }

                // treat it like a regular array
                $elements = array_map(array($this, 'encode'), $var);

                foreach($elements as $element) {
                    if(Services_JSON::isError($element)) {
                        return $element;
                    }
                }

                return '[' . join(',', $elements) . ']';

            case 'object':
                $vars = get_object_vars($var);

                $properties = array_map(array($this, 'name_value'),
                                        array_keys($vars),
                                        array_values($vars));

                foreach($properties as $property) {
                    if(Services_JSON::isError($property)) {
                        return $property;
                    }
                }

                return '{' . join(',', $properties) . '}';

            default:
                return ($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
                    ? 'null'
                    : new Services_JSON_Error(gettype($var)." can not be encoded as JSON string");
        }
    }

   /**
    * array-walking function for use in generating JSON-formatted name-value pairs
    *
    * @param    string  $name   name of key to use
    * @param    mixed   $value  reference to an array element to be encoded
    *
    * @return   string  JSON-formatted name-value pair, like '"name":value'
    * @access   private
    */
    function name_value($name, $value)
    {
        $encoded_value = $this->encode($value);

        if(Services_JSON::isError($encoded_value)) {
            return $encoded_value;
        }

        return $this->encode(strval($name)) . ':' . $encoded_value;
    }

   /**
    * reduce a string by removing leading and trailing comments and whitespace
    *
    * @param    $str    string      string value to strip of comments and whitespace
    *
    * @return   string  string value stripped of comments and whitespace
    * @access   private
    */
    function reduce_string($str)
    {
        $str = preg_replace(array(

                // eliminate single line comments in '// ...' form
                '#^\s*//(.+)$#m',

                // eliminate multi-line comments in '/* ... */' form, at start of string
                '#^\s*/\*(.+)\*/#Us',

                // eliminate multi-line comments in '/* ... */' form, at end of string
                '#/\*(.+)\*/\s*$#Us'

            ), '', $str);

        // eliminate extraneous space
        return trim($str);
    }

   /**
    * decodes a JSON string into appropriate variable
    *
    * @param    string  $str    JSON-formatted string
    *
    * @return   mixed   number, boolean, string, array, or object
    *                   corresponding to given JSON input string.
    *                   See argument 1 to Services_JSON() above for object-output behavior.
    *                   Note that decode() always returns strings
    *                   in ASCII or UTF-8 format!
    * @access   public
    */
    function decode($str)
    {
        $str = $this->reduce_string($str);

        switch (strtolower($str)) {
            case 'true':
                return true;

            case 'false':
                return false;

            case 'null':
                return null;

            default:
                $m = array();

                if (is_numeric($str)) {
                    // Lookie-loo, it's a number

                    // This would work on its own, but I'm trying to be
                    // good about returning integers where appropriate:
                    // return (float)$str;

                    // Return float or int, as appropriate
                    return ((float)$str == (integer)$str)
                        ? (integer)$str
                        : (float)$str;

                } elseif (preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2]) {
                    // STRINGS RETURNED IN UTF-8 FORMAT
                    $delim = substr($str, 0, 1);
                    $chrs = substr($str, 1, -1);
                    $utf8 = '';
                    $strlen_chrs = strlen($chrs);

                    for ($c = 0; $c < $strlen_chrs; ++$c) {

                        $substr_chrs_c_2 = substr($chrs, $c, 2);
                        $ord_chrs_c = ord($chrs{$c});

                        switch (true) {
                            case $substr_chrs_c_2 == '\b':
                                $utf8 .= chr(0x08);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\t':
                                $utf8 .= chr(0x09);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\n':
                                $utf8 .= chr(0x0A);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\f':
                                $utf8 .= chr(0x0C);
                                ++$c;
                                break;
                            case $substr_chrs_c_2 == '\r':
                                $utf8 .= chr(0x0D);
                                ++$c;
                                break;

                            case $substr_chrs_c_2 == '\\"':
                            case $substr_chrs_c_2 == '\\\'':
                            case $substr_chrs_c_2 == '\\\\':
                            case $substr_chrs_c_2 == '\\/':
                                if (($delim == '"' && $substr_chrs_c_2 != '\\\'') ||
                                   ($delim == "'" && $substr_chrs_c_2 != '\\"')) {
                                    $utf8 .= $chrs{++$c};
                                }
                                break;

                            case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $c, 6)):
                                // single, escaped unicode character
                                $utf16 = chr(hexdec(substr($chrs, ($c + 2), 2)))
                                       . chr(hexdec(substr($chrs, ($c + 4), 2)));
                                $utf8 .= $this->utf162utf8($utf16);
                                $c += 5;
                                break;

                            case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                                $utf8 .= $chrs{$c};
                                break;

                            case ($ord_chrs_c & 0xE0) == 0xC0:
                                // characters U-00000080 - U-000007FF, mask 110XXXXX
                                //see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 2);
                                ++$c;
                                break;

                            case ($ord_chrs_c & 0xF0) == 0xE0:
                                // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 3);
                                $c += 2;
                                break;

                            case ($ord_chrs_c & 0xF8) == 0xF0:
                                // characters U-00010000 - U-001FFFFF, mask 11110XXX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 4);
                                $c += 3;
                                break;

                            case ($ord_chrs_c & 0xFC) == 0xF8:
                                // characters U-00200000 - U-03FFFFFF, mask 111110XX
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 5);
                                $c += 4;
                                break;

                            case ($ord_chrs_c & 0xFE) == 0xFC:
                                // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                $utf8 .= substr($chrs, $c, 6);
                                $c += 5;
                                break;

                        }

                    }

                    return $utf8;

                } elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
                    // array, or object notation

                    if ($str{0} == '[') {
                        $stk = array(SERVICES_JSON_IN_ARR);
                        $arr = array();
                    } else {
                        if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                            $stk = array(SERVICES_JSON_IN_OBJ);
                            $obj = array();
                        } else {
                            $stk = array(SERVICES_JSON_IN_OBJ);
                            $obj = new stdClass();
                        }
                    }

                    array_push($stk, array('what'  => SERVICES_JSON_SLICE,
                                           'where' => 0,
                                           'delim' => false));

                    $chrs = substr($str, 1, -1);
                    $chrs = $this->reduce_string($chrs);

                    if ($chrs == '') {
                        if (reset($stk) == SERVICES_JSON_IN_ARR) {
                            return $arr;

                        } else {
                            return $obj;

                        }
                    }

                    //print("\nparsing {$chrs}\n");

                    $strlen_chrs = strlen($chrs);

                    for ($c = 0; $c <= $strlen_chrs; ++$c) {

                        $top = end($stk);
                        $substr_chrs_c_2 = substr($chrs, $c, 2);

                        if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == SERVICES_JSON_SLICE))) {
                            // found a comma that is not inside a string, array, etc.,
                            // OR we've reached the end of the character list
                            $slice = substr($chrs, $top['where'], ($c - $top['where']));
                            array_push($stk, array('what' => SERVICES_JSON_SLICE, 'where' => ($c + 1), 'delim' => false));
                            //print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                            if (reset($stk) == SERVICES_JSON_IN_ARR) {
                                // we are in an array, so just push an element onto the stack
                                array_push($arr, $this->decode($slice));

                            } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                                // we are in an object, so figure
                                // out the property name and set an
                                // element in an associative array,
                                // for now
                                $parts = array();
                                
                                if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // "name":value pair
                                    $key = $this->decode($parts[1]);
                                    $val = $this->decode($parts[2]);

                                    if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                } elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                    // name:value pair, where name is unquoted
                                    $key = $parts[1];
                                    $val = $this->decode($parts[2]);

                                    if ($this->use & SERVICES_JSON_LOOSE_TYPE) {
                                        $obj[$key] = $val;
                                    } else {
                                        $obj->$key = $val;
                                    }
                                }

                            }

                        } elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != SERVICES_JSON_IN_STR)) {
                            // found a quote, and we are not inside a string
                            array_push($stk, array('what' => SERVICES_JSON_IN_STR, 'where' => $c, 'delim' => $chrs{$c}));
                            //print("Found start of string at {$c}\n");

                        } elseif (($chrs{$c} == $top['delim']) &&
                                 ($top['what'] == SERVICES_JSON_IN_STR) &&
                                 ((strlen(substr($chrs, 0, $c)) - strlen(rtrim(substr($chrs, 0, $c), '\\'))) % 2 != 1)) {
                            // found a quote, we're in a string, and it's not escaped
                            // we know that it's not escaped becase there is _not_ an
                            // odd number of backslashes at the end of the string so far
                            array_pop($stk);
                            //print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '[') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a left-bracket, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false));
                            //print("Found start of array at {$c}\n");

                        } elseif (($chrs{$c} == ']') && ($top['what'] == SERVICES_JSON_IN_ARR)) {
                            // found a right-bracket, and we're in an array
                            array_pop($stk);
                            //print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($chrs{$c} == '{') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a left-brace, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false));
                            //print("Found start of object at {$c}\n");

                        } elseif (($chrs{$c} == '}') && ($top['what'] == SERVICES_JSON_IN_OBJ)) {
                            // found a right-brace, and we're in an object
                            array_pop($stk);
                            //print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        } elseif (($substr_chrs_c_2 == '/*') &&
                                 in_array($top['what'], array(SERVICES_JSON_SLICE, SERVICES_JSON_IN_ARR, SERVICES_JSON_IN_OBJ))) {
                            // found a comment start, and we are in an array, object, or slice
                            array_push($stk, array('what' => SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false));
                            $c++;
                            //print("Found start of comment at {$c}\n");

                        } elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == SERVICES_JSON_IN_CMT)) {
                            // found a comment end, and we're in one now
                            array_pop($stk);
                            $c++;

                            for ($i = $top['where']; $i <= $c; ++$i)
                                $chrs = substr_replace($chrs, ' ', $i, 1);

                            //print("Found end of comment at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");

                        }

                    }

                    if (reset($stk) == SERVICES_JSON_IN_ARR) {
                        return $arr;

                    } elseif (reset($stk) == SERVICES_JSON_IN_OBJ) {
                        return $obj;

                    }

                }
        }
    }

    /**
     * @todo Ultimately, this should just call PEAR::isError()
     */
    function isError($data, $code = null)
    {
        if (class_exists('pear')) {
            return PEAR::isError($data, $code);
        } elseif (is_object($data) && (get_class($data) == 'services_json_error' ||
                                 is_subclass_of($data, 'services_json_error'))) {
            return true;
        }

        return false;
    }
}

if (class_exists('PEAR_Error')) {

    class Services_JSON_Error extends PEAR_Error
    {
        function Services_JSON_Error($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {
            parent::PEAR_Error($message, $code, $mode, $options, $userinfo);
        }
    }

} else {

    /**
     * @todo Ultimately, this class shall be descended from PEAR_Error
     */
    class Services_JSON_Error
    {
        function Services_JSON_Error($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {

        }
    }

}

	} // end if (!class_exists('Services_JSON')) { 

// adapted from WP 2.9

	function json_encode( $string ) {
		global $wp_json;

		if ( !is_a($wp_json, 'Services_JSON') ) {
			$wp_json = new Services_JSON();
		}

		return $wp_json->encode( $string );
	}

	function json_decode( $string ) {
		global $wp_json;

		if ( !is_a($wp_json, 'Services_JSON') ) {
			$wp_json = new Services_JSON();
		}

		return $wp_json->decode( $string );
	}
}

function autoshop_get_vehicles($filter = '', $json = false) {
    global $autoshop_vehicle_attributes;
    $search_opts = Array('numberposts' => -1);

    if(is_array($filter)) {
        if(isset($filter['tag']))
            $search_opts['tag'] = sprintf("%s",$filter['tag']);
        else if(isset($filter['category']))
            $search_opts['category_name'] = sprintf("%s",$filter['category']);
    }
    $rval = Array();
    $post_list = get_posts($search_opts);

    foreach($post_list as $post) {
        $tmp = Array();
        $the_ID = $post->ID;
        $custom = get_post_custom($the_ID);
        foreach($autoshop_vehicle_attributes as $attribute) {
            $tmp[$attribute] = $custom[$attribute][0];
        }
        list($make) = get_categories( Array('include' => $custom['autoshop_make'][0], 'hide_empty' => false ));
        $tmp['autoshop_make'] = $make->name;
        $tmp['autoshop_url'] = $post->guid;
        $tmp['autoshop_vid'] = $the_ID;
        $rval[] = $tmp;
    }
    return($rval);
}

function autoshop_show_vehicle() {
    global $autoshop_body_style_list;
    global $autoshop_engine_type_list, $autoshop_transmission_list;
    global $autoshop_drive_train_list, $autoshop_fuel_list;
    global $autoshop_stereo_list;
    global $wp_query;
    $the_ID = $wp_query->post->ID;
    $custom = get_post_custom();
    $wud = wp_upload_dir();
    $dir = $wud['baseurl'] . "/autoshop/" . $the_ID;

    list($make) = get_categories( Array('include' => $custom['autoshop_make'][0], 'hide_empty' => false ));
    //print_r($custom);
    $output = sprintf("
    <dl class='autoshop_vehicle'>
      <dt>Price:</dt><dd>$%s</dd>
      <dt>VIN:</dt><dd>%s</dd>
      <dt>Mileage:</dt><dd>%s</dd>
      <dt>Engine:</dt><dd>%s %s</dd>
      <dt>Fuel:</dt><dd>%s</dd>
      <dt>Transmission:</dt><dd>%s</dd>
      <dt>Drive Train:</dt><dd>%s</dd>
      <dt>Exterior Color:</dt><dd>%s</dd>
      <dt>Interior Color:</dt><dd>%s</dd>
      <dt>Body Style:</dt><dd>%s</dd>
      <dt>Doors:</dt><dd>%d</dd>
      <dt>Stereo:</dt><dd>%s</dd>
    </dl>"
        , number_format($custom['autoshop_price'][0])
        , $custom['autoshop_vin'][0]
        , number_format($custom['autoshop_miles'][0])
        , $custom['autoshop_engine_size'][0]
        , $autoshop_engine_type_list[$custom['autoshop_engine_type'][0]]
        , $autoshop_fuel_list[$custom['autoshop_fuel'][0]]
        , $autoshop_transmission_list[$custom['autoshop_transmission'][0]]
        , $autoshop_drive_train_list[$custom['autoshop_drive_train'][0]]
        , $custom['autoshop_exterior_color'][0]
        , $custom['autoshop_interior_color'][0]
        , $autoshop_body_style_list[$custom['autoshop_body_style'][0]]
        , $custom['autoshop_doors'][0]
        , $autoshop_stereo_list[$custom['autoshop_stereo'][0]]
    );
    $fname = sprintf("%s/%d-%s-%s-%s-autoshop_cover_shot.jpg"
                    , $dir
                    , $custom['autoshop_model_year'][0]
                    , strtolower(str_replace('/','', str_replace(' ', '-', $custom['autoshop_exterior_color'][0])))
                    , $make->slug
                    , strtolower(str_replace(' ', '-', $custom['autoshop_model'][0]))
             );
    $output .= sprintf("<div class='autoshop_vehicle_photos'><img src='%s'></div>"
                      , $fname
                      );
    return $output;
}

function autoshop_show_list() {
    return autoshop_show('');
}
function autoshop_show($filter) {
    global $autoshop_engine_type_list;
    $wud = wp_upload_dir();

    $vehicle_data = autoshop_get_vehicles($filter);
    $output = "\n<table class='autoshop_vehicle_list'>\n<thead><tr><th>Year</th><th>Make Model</th><th>Engine</th><th>Miles</th><th>Price</th></tr></thead><tbody>\n";
    foreach($vehicle_data as $vehicle) {
        $dir = $wud['baseurl'] . "/autoshop/" . $vehicle['autoshop_vid'];
        $fname = sprintf("%s/%d-%s-%s-%s-autoshop_cover_shot.jpg"
                        , $dir
                        , $vehicle['autoshop_model_year']
                        , strtolower(str_replace('/','', str_replace(' ', '-', $vehicle['autoshop_exterior_color'])))
                        , strtolower($vehicle['autoshop_make'])
                        , strtolower(str_replace(' ', '-', $vehicle['autoshop_model']))
                 );
        $output .= sprintf("<tr><td>%d<a href='%s'><img class='autoshop_cover_shot' src='%s'></a></td><td><a href='%s'>%s %s</a><div class='description'>%s</div><div class='details'><a href='%s'>More details on this %s %s</a></td><td>%s %s</td><td>%s</td><td>$%s</td></tr>\n"
                           , $vehicle['autoshop_model_year']
                           , $vehicle['autoshop_url']
                           , $fname
                           , $vehicle['autoshop_url']
                           , $vehicle['autoshop_make']
                           , $vehicle['autoshop_model']
                           , substr($vehicle['autoshop_description'], 0, 200)
                           , $vehicle['autoshop_url']
                           , $vehicle['autoshop_make']
                           , $vehicle['autoshop_model']
                           , $vehicle['autoshop_engine_size']
                           , $autoshop_engine_type_list[$vehicle['autoshop_engine_type']]
                           , number_format($vehicle['autoshop_miles'])
                           , number_format($vehicle['autoshop_price'])
                   );
    }
    $output .= '</tbody></table>

<script style="text/javascript">
function odump(object, depth, max){
  depth = depth || 0;
  max = max || 2;

  if (depth > max)
    return false;

  var indent = "";
  for (var i = 0; i < depth; i++)
    indent += "  ";

  var output = "";  
  for (var key in object){
    output += "\n" + indent + key + ": ";
    switch (typeof object[key]){
      case "object": output += odump(object[key], depth + 1, max); break;
      case "function": output += "function"; break;
      default: output += object[key]; break;        
    }
  }
  return output;
}

jQuery.fn.dataTableExt.oSort["currency-asc"] = function(a,b) {
    /* Remove any commas (assumes that if present all strings will have a fixed number of d.p) */
    var x = a == "-" ? 0 : a.replace( /,/g, "" );
    var y = b == "-" ? 0 : b.replace( /,/g, "" );
     
    /* Remove the currency sign */
    x = x.substring( 1 );
    y = y.substring( 1 );
     
    /* Parse and return */
    x = parseFloat( x );
    y = parseFloat( y );
    return x - y;
};
 
jQuery.fn.dataTableExt.oSort["currency-desc"] = function(a,b) {
    /* Remove any commas (assumes that if present all strings will have a fixed number of d.p) */
    var x = a == "-" ? 0 : a.replace( /,/g, "" );
    var y = b == "-" ? 0 : b.replace( /,/g, "" );
     
    /* Remove the currency sign */
    x = x.substring( 1 );
    y = y.substring( 1 );
     
    /* Parse and return */
    x = parseFloat( x );
    y = parseFloat( y );
    return y - x;
};
jQuery.fn.dataTableExt.oSort["numeric-comma-asc"]  = function(a,b) {
        var x = (a == "-") ? 0 : a.replace( /,/, "." );
        var y = (b == "-") ? 0 : b.replace( /,/, "." );
        x = parseFloat( x );
        y = parseFloat( y );
        return ((x < y) ? -1 : ((x > y) ?  1 : 0));
};

jQuery.fn.dataTableExt.oSort["numeric-comma-desc"] = function(a,b) {
        var x = (a == "-") ? 0 : a.replace( /,/, "." );
        var y = (b == "-") ? 0 : b.replace( /,/, "." );
        x = parseFloat( x );
        y = parseFloat( y );
        return ((x < y) ?  1 : ((x > y) ? -1 : 0));
};

    jQuery(".autoshop_vehicle_list").dataTable( {
                "bPaginate": true,
                "bLengthChange": true,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
                "bAutoWidth": false,
                "aaSorting": [[ 4, "desc" ]],
                "aoColumns": [
                          { "sType": "numeric" }
                        , { "sType": "string" }
                        , { "sType": "string" }
                        , { "sType": "numeric-comma" }
                        , { "sType": "currency" }
                ]

        }
    );
/*
*/
</script>
';
    return($output);
}

add_shortcode('autoshop-vehicle', 'autoshop_show_vehicle');
add_shortcode('autoshop-all', 'autoshop_show_list');

// fuel economy
function get_fe() {
    $html = file_get_contents("/var/www/virtual/oriontechnologysolutions.com/chucksauto/htdocs/wp-content/plugins/auto-shop/galant.html");
    /*** a new dom object ***/
    $dom = new domDocument;

    /*** load the html into the object ***/
    $dom->loadHTML($html);

    /*** discard white space ***/
    $dom->preserveWhiteSpace = false;

    /*** the table by its tag name ***/
    $links = $dom->getElementsByTagName('a');
    echo "<h3>" . $links->length . "</h3>";

    /*** loop over the table rows ***/
    foreach ($links as $link)
    {
        /*** get each column by tag name ***/
        //$link = $row->getElementsByTagName('a');
        /*** echo the values ***/
        echo '<pre>';
        //echo $link->length;
        echo $link->getAttribute("href");
        echo $link->item(0)->nodeValue;
        print_r($link->item);
        echo '</pre>';
        echo '<hr />';
    }
}
?>
