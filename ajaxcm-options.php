<?php
function ajaxcm_plugin_menu()
{
	add_submenu_page('edit-comments.php', 'AjaxCm Settings', 'Ajax Comments', 'manage_options', 'ajaxcm_settings', 'ajaxcm_plugin_settings_page');
}												 

define(OPTION_NAME, 'ajaxcm_plugin_settings');

function ajaxcm_admin_init(){
	//wp_register_script('c_picker', WP_PLUGIN_URL.'/ajaxcm-request/colorpicker/js/colorpicker.js', false, '1.0', false);
	//wp_enqueue_script('colorpicker');
	
  	wp_enqueue_style( 'farbtastic' );
  	wp_enqueue_script( 'farbtastic' );	
  	
	//wp_enqueue_style('c_picker_css', WP_PLUGIN_URL.'/ajaxcm-request/colorpicker/css/colorpicker.css', false, '1.0');
	register_setting( 'ajaxcm_plugin_settings', 'ajaxcm_plugin_settings', 'ajaxcm_plugin_settings_validate' );
	add_settings_section('ajaxcm_plugin_main', 'General Settings', 'ajaxcm_plugin_section_general', 'ajaxcm_plugin_option_page');
	add_settings_field('comment_callback_name', 'Custom comment callback function name', 'ajaxcm_plugin_setting_cmcallback', 'ajaxcm_plugin_option_page', 'ajaxcm_plugin_main');
	add_settings_field('highlight_bgcolor', 'Background color for highlighting search terms', 'ajaxcm_plugin_setting_bgcolor', 'ajaxcm_plugin_option_page', 'ajaxcm_plugin_main');
	add_settings_field('highlight_fgcolor', 'Foreground (text) color for highlighting search terms', 'ajaxcm_plugin_setting_fgcolor', 'ajaxcm_plugin_option_page', 'ajaxcm_plugin_main');
	add_settings_field('naviclass', 'Class for wrapping the navigation links', 'ajaxcm_plugin_setting_naviclass', 'ajaxcm_plugin_option_page', 'ajaxcm_plugin_main');
	add_settings_field('use_prev_next', "Use previous/next page links instead of numbered pagination", 'ajaxcm_plugin_setting_prevnext', 'ajaxcm_plugin_option_page', 'ajaxcm_plugin_main');
	add_settings_field('navi_position', "Position of the page navigation links", 'ajaxcm_plugin_setting_position', 'ajaxcm_plugin_option_page', 'ajaxcm_plugin_main');
	add_settings_field('force_scroll', "Scroll comments into view", 'ajaxcm_plugin_setting_scroll', 'ajaxcm_plugin_option_page', 'ajaxcm_plugin_main');
	add_settings_field('js_eval', "Evaluate JavaScript.", 'ajaxcm_plugin_setting_jseval', 'ajaxcm_plugin_option_page', 'ajaxcm_plugin_main');
}

function ajaxcm_plugin_settings_page()
{
    global $ajaxcm_default_settings, $ajaxcm_plugin_options;
	
	ajaxcm_load_options();

	echo'<div class="wrap">
	<h3 style="text-align:center;">AjaxCM settings</h3>
	<div id="poststuff" class="ui-sortable meta-box-sortables">
	<div class="postbox">
	<form method="post" action="options.php">';
	settings_fields('ajaxcm_plugin_settings');
	do_settings_sections('ajaxcm_plugin_option_page');
	echo '<input name="Submit" type="submit" value="Save Changes" />
	</form>
	</div>
	</div>
	</div>
	<script type="text/javascript">
	jQuery(document).ready(function() { 
		jQuery(\'#farbtastic_bg\').farbtastic(\'#highlight_bgcolor\');
		jQuery(\'#highlight_bgcolor\').click(function(){jQuery(\'#farbtastic_bg\').slideToggle()});
		
		jQuery(\'#farbtastic_fg\').farbtastic(\'#highlight_fgcolor\');
		jQuery(\'#highlight_fgcolor\').click(function(){jQuery(\'#farbtastic_fg\').slideToggle()});
	});
	</script>';
}

function ajaxcm_validate_string(&$arg)
{
	return(preg_match('/^[a-z0-9\-\_]+$/i', $arg));
}

function ajaxcm_plugin_settings_validate($input)
{
	$newinput['cmcallback'] = trim($input['cmcallback']);
	if(!ajaxcm_validate_string($newinput['cmcallback'])) {
		$newinput['cmcallback'] = '';
	}
	$newinput['highlight_bg'] = trim($input['highlight_bg']);
	$newinput['highlight_fg'] = trim($input['highlight_fg']);
	
	$newinput['naviclass'] = trim($input['naviclass']);
	if(!ajaxcm_validate_string($newinput['naviclass'])) {
		$newinput['naviclass'] = '';
	}
	
	$newinput['use_prev_next'] = $input['use_prev_next'];
	$newinput['navi_position'] = $input['navi_position'];
	$newinput['force_scroll'] = $input['force_scroll'];
	$newinput['js_eval'] = $input['js_eval'];
	return $newinput;	
}

function ajaxcm_plugin_section_general()
{
}

function ajaxcm_plugin_setting_cmcallback()
{
	global $ajaxcm_plugin_options;
	
	echo 'If your theme provides a custom callback function for formatting comments, you can enter the name of this function here. When unset, no callback will be used. This function <strong>must</strong> exist in your theme.<br />';
	echo "<input id='comment_callback_name' name='ajaxcm_plugin_settings[cmcallback]' size='40' type='text' value='{$ajaxcm_plugin_options['cmcallback']}' />";
}

function ajaxcm_plugin_setting_bgcolor()
{
	global $ajaxcm_plugin_options;
	
	echo 'When searching comments, found terms will be highlighted using the following background color</br>';
	echo '<input id="highlight_bgcolor" name="ajaxcm_plugin_settings[highlight_bg]" size="10" type="text" value="'.$ajaxcm_plugin_options['highlight_bg'].'" />';
	echo '<div id="farbtastic_bg" style="display:none;">';
}

function ajaxcm_plugin_setting_fgcolor()
{
	global $ajaxcm_plugin_options;
	
	echo 'When searching comments, found terms will be highlighted using the following TEXT color</br>';
	echo '<input id="highlight_fgcolor" name="ajaxcm_plugin_settings[highlight_fg]" size="10" type="text" value="'.$ajaxcm_plugin_options['highlight_fg'].'" />';
	echo '<div id="farbtastic_fg" style="display:none;">';
}

function ajaxcm_plugin_setting_naviclass()
{
	global $ajaxcm_plugin_options;
	
	echo 'The navigation (pagination) links are usually wrapped inside a &lt;div&gt; element. You can specifiy the CSS class for this element here.<br />';
	echo '<input id="naviclass" name="ajaxcm_plugin_settings[naviclass]" size="40" type="text" value="'.$ajaxcm_plugin_options['naviclass'].'" />';
}

function ajaxcm_plugin_setting_prevnext()
{
	global $ajaxcm_plugin_options;

	echo 'When checked, the plugin will use previous / next links to page through comment pages, otherwise the default pagination with page numbers will be used<br />You can use this setting to match your theme\'s default behavior<br />';
	echo '<input id="use_prev_next" name="ajaxcm_plugin_settings[use_prev_next]" type="checkbox" ' . ($ajaxcm_plugin_options['use_prev_next'] ? 'checked="checked"' : '') . ' />';
}

function ajaxcm_plugin_setting_position()
{
	global $ajaxcm_plugin_options;
	
	$val = $ajaxcm_plugin_options['navi_position'];

	echo 'This setting controls where the plugin will output pagination links. You can use it to match your theme\'s default behavior.<br />';
	echo '<input id="navi_position" name="ajaxcm_plugin_settings[navi_position]" '.($val == NAVI_TOP ? 'checked ' : '').'type="radio" value="1" />At the top of comments<br />';
	echo '<input id="navi_position" name="ajaxcm_plugin_settings[navi_position]" '.($val == NAVI_BOTTOM ? 'checked ' : '').'type="radio" value="2" />At the bottom of comments<br />';
	echo '<input id="navi_position" name="ajaxcm_plugin_settings[navi_position]" '.($val == NAVI_BOTH? 'checked ' : '').'type="radio" value="3" />In both positions';
}

function ajaxcm_plugin_setting_scroll()
{
	global $ajaxcm_plugin_options;
	
	$val = $ajaxcm_plugin_options['use_MCE'];

	echo "When checked, this option will force the comment page to be scrolled into optimal view (first comment of the page on top of the browser window) on every page change. When unchecked, no forced scrolling will occur.<br />";
	echo '<input id="force_scroll" name="ajaxcm_plugin_settings[force_scroll]" type="checkbox" ' . ($ajaxcm_plugin_options['force_scroll'] ? 'checked="checked"' : '') . ' />';
}

function ajaxcm_plugin_setting_jseval()
{
	global $ajaxcm_plugin_options;

	echo 'This JavaScript code will be called using eval() whenever a new page of comments completes loading. You can use this to execute code that would normally run in a document.ready() handler. Good example would be a JavaScript-based syntax highlighter.<br /><strong>Make sure this code will not cause any errors as this could break the Ajax pager</strong><br />';
	echo '<textarea rows="5" cols="80" id="js_eval" name="ajaxcm_plugin_settings[js_eval]">'.$ajaxcm_plugin_options['js_eval']. '</textarea>';
}

?>