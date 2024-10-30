<?php

/**
 * Plugin Name: Confetti Wordcount
 * Description: Motivate yourself with a weekly overview of your word count stats on your dashboard and a realtime word count progress meter just below your editor.
 * Version: 0.1
 * Author: Asprise
 * Author URI: https://wpimager.com/confetti-wordcount/
 */

add_action('admin_enqueue_scripts', 'countfetti_add_admin_scripts', 10, 1);

function countfetti_add_admin_scripts($hook)
{

	global $post;

	if ($hook == 'post-new.php' || $hook == 'post.php') {
		wp_enqueue_script('countfetti-meter', plugins_url('js/countfetti-wordcount.js', __FILE__));
		wp_enqueue_script('jquery.confetti', plugins_url('js/jquery.confetti.js', __FILE__));
		wp_enqueue_style('jquery.confetti', plugins_url('css/style.css', __FILE__));
	}
}

add_filter('media_buttons_context', 'countfetti_media_buttons_context');

function countfetti_media_buttons_context($editor_id = '')
{
	global $posts;
	$count = 0;
	$posts = get_posts(array(
		'numberposts' => -1,
		'post_status' => 'publish',
		'post_type' => 'any',
		'orderby' => 'post_type',
	));
	foreach ($posts as $post) {
		$count += str_word_count(strip_tags(get_post_field('post_content', $post->ID)));
	}
	$num = number_format_i18n($count);
	$target = get_option('countfetti_default_target', 500);
	$shower = get_option('countfetti_trigger_shower', 1);
	$countfetti_custom_target = get_post_meta(get_the_ID(), 'confetti_post_target', TRUE);
	$post_status = get_post_status();
	if ($countfetti_custom_target && $countfetti_custom_target >= 100 && $countfetti_custom_target <= 50000) {
		$target = $countfetti_custom_target;
	} else if ($post_status == 'draft' || $post_status == 'auto-draft') {
		update_post_meta(get_the_ID(), 'confetti_post_target', $target);
	}
	return '<input id="countfetti-word-total" type="hidden" value="' . $count . '"><input id="countfetti-target" type="hidden" value="' . $target . '"><input id="countfetti-word-shower" type="hidden" value="' . $shower . '">';
}


function countfetti_widget()
{
	$count = 0;
	$posts = get_posts(array(
		'numberposts' => -1,
		'post_status' => 'publish',
		'post_type' => 'any',
		'orderby' => 'post_type',
	));
	foreach ($posts as $post) {
		$count += str_word_count(strip_tags(get_post_field('post_content', $post->ID)));
	}
	$num = number_format_i18n($count);
	$text = 'Words';
	$average = $count;
	$average /= count($posts);
	$average = number_format_i18n($average);
	$url = admin_url('edit.php');
	echo "<div style='line-height:1.8em'>";
	echo "<div class='word-count' style='width:100%;'><a href='{$url}' onclick=\"jQuery('#countfetti-word-summary').toggle();return false;\" title='Average of {$average} words per post'><span style='font-size:24px;'>{$num}</span> {$text}</a> (All time)</div>";

	echo '<div id="countfetti-word-summary" style="margin:0 10px 10px 28px;display:none;">';
	foreach ($posts as $post) {
		echo $word_count = str_word_count(strip_tags(get_post_field('post_content', $post->ID)));
		echo ' - ' . $post->post_title;
		if ($post->post_type == 'page') {
			echo ' (Page)';
		}
		echo '<br>';
	}
	echo '</div>';


	$date_today = new DateTime();
	$wk = $date_today->format("W");
	$yr = $date_today->format("Y");
	$countfetti_display_numweeks = get_option('countfetti_display_numweeks', 4);
	$weeks_nowords = array();
	$_wk_decremented = $wk;
	for ($_wk = $wk; $_wk > $wk - $countfetti_display_numweeks; $_wk--) {
		$count = 0;
		$args = array(
			//		'numberposts' => -1,
			'post_type' => array('post', 'page'),
			'post_status' => 'publish',
			'date_query' => array(
				'column' => 'post_date',
				'week' => $_wk_decremented,
				'year' => $yr,
			)
		);
		
		if (--$_wk_decremented <= 0) {
			$_wk_decremented = 52;
		}


		$posts = new WP_Query($args);
		foreach ($posts->posts as $post) {
			$post_word_count = str_word_count(strip_tags(get_post_field('post_content', $post->ID)));
			if ($post_word_count > 0) {
				$count += $post_word_count;
			}
		}

		$num = number_format_i18n($count);
		$average = $count;
		$average /= count($posts);
		$average = number_format_i18n($average);
		if ($count > 0 || $_wk == $wk) {
			echo "<div class='week-count'><a href='{$url}'  onclick=\"jQuery('#countfetti-word-summary-week-{$_wk}').toggle();return false;\"  title='Average of {$average} words per post'>Week {$_wk_decremented} &ndash; {$num} words</a>";
			echo '<div id="countfetti-word-summary-week-' . $_wk . '" style="margin:0 10px 10px 28px;display:none;clear:both;">';
			foreach ($posts->posts as $post) {
				$word_count = str_word_count(strip_tags(get_post_field('post_content', $post->ID)));
				if ($word_count > 0) {
					echo $word_count . ' - ' . $post->post_title;
					if ($post->post_type == 'page') {
						echo ' (Page)';
					}
					echo '<br>';
				}
			}
			echo '</div></div>';
		} else {
			$weeks_nowords[$_wk_decremented] = 1;
		}
	}
	if (count($weeks_nowords) > 0) {
		echo '<div>No publishing &ndash; Week ' . implode(", ", array_keys($weeks_nowords)) . '</div>';
	}
	$countfetti_display_numweeks = get_option('countfetti_display_numweeks', 4);
	$countfetti_default_target = get_option('countfetti_default_target', 500);
	$countfetti_trigger_shower = get_option('countfetti_trigger_shower', 1);
	echo "<div style='margin-top:10px;'><span class='dashicons dashicons-admin-generic' style='color:#888'></span> <a href='#' class='text-right' onclick='jQuery(\"#show-countfetti-settings\").toggle();return false;'>Settings</a> &ndash; <span id='countfetti_settings_info'>Default Target: {$countfetti_default_target} words, Confetti " . ($countfetti_trigger_shower == 1 ? 'Enabled' : 'Disabled') . ".</span></div>";
	echo "<div id='show-countfetti-settings' style='display:none;margin:0 0 10px 0;'>Show number of weeks: ";
	echo '<input type="number" step="1" min="1" class="screen-per-page" name="" id="countfetti_shownum_weeks" maxlength="2" value="' . $countfetti_display_numweeks . '"> weeks';
	echo "<br>Default Word Count Target in Editor: ";
	echo '<input type="number" step="50" min="100" max="50000" class="screen-per-page" name="" id="countfetti_post_target" maxlength="5" value="' . $countfetti_default_target . '"> words';
	echo '<br><input type="checkbox" name="countfetti_trigger_shower" id="countfetti_trigger_shower" value="1"'.($countfetti_trigger_shower?'checked':'').'> Trigger confetti when targeted words reached.';
	echo "<div style='margin-top:6px;'><button id='update_countfetti_settings' class='button button-primary'>Update</button></div>";
	echo "</div>";
	echo "</div>";
	echo "<script>
		    jQuery(\"#update_countfetti_settings\").click(function() {
		var shownum_weeks = jQuery(\"#countfetti_shownum_weeks\").val();
		var target = jQuery(\"#countfetti_post_target\").val();
		var shower = (jQuery(\"#countfetti_trigger_shower\").is(\":checked\") ?'1':'0');
		
        jQuery.ajax({
            type: 'POST',
            url: '" . admin_url('admin-ajax.php') . "',
            data: {action: 'update_countfetti_settings', wordcounter_confetti: false, countfetti_post_target:target, countfetti_display_numweeks: shownum_weeks, countfetti_trigger_shower: shower },
            dataType: 'json',
            cache: false,
            success: function (msg) {
				window.location.reload();				
            }
        });
        
    });
	</script>";
}

add_action('wp_dashboard_setup', 'countfetti_dashboard_widgets');

function countfetti_dashboard_widgets()
{
	wp_add_dashboard_widget('custom_help_widget', 'Confetti Word Count', 'countfetti_widget');
}

add_action('wp_ajax_update_countfetti_settings', 'update_countfetti_settings');

function update_countfetti_settings()
{
	$_POST = array_map('stripslashes_deep', $_POST);
	$countfetti_default_target = (int) $_POST['countfetti_post_target'];
	$countfetti_display_numweeks = (int) $_POST['countfetti_display_numweeks'];
	$countfetti_trigger_shower = (int) $_POST['countfetti_trigger_shower'];
	update_option('countfetti_default_target', $countfetti_default_target);
	update_option('countfetti_display_numweeks', $countfetti_display_numweeks);
	update_option('countfetti_trigger_shower', $countfetti_trigger_shower);
	$return_arr['success'] = true;
	echo json_encode($return_arr);
	wp_die();
}
