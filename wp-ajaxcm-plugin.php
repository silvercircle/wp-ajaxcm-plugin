<?php
/*
Plugin Name: WP-AjaxCM
Description: Provides ajax comment paging with demand-loading, ajax-powered comment previews and ajax-powered comment posting
Version: 0.1
Author: Alex Vie
Author URI: http://blog.miranda.or.at

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

define(AJAXCM_PLUGIN_PATH, '/wp-ajaxcm-plugin');

define(NAVI_TOP, 1);
define(NAVI_BOTTOM, 2);
define(NAVI_BOTH, 3);
/*
 * todo: clear comment form after posting
 * todo: allow options to disable commment preview and posting for people who want
 *       to use separate plugins for this functionality
 */
$ajaxcm_default_settings = array('cmcallback' => 'custom_comments', 
		'highlight_bg' => '#00ffff', 
		'highlight_fg' => '#000000', 
		'container_id' => 'ajaxcm_container',
		'naviclass' => 'commentnavi',
		'use_prev_next' => false,
		'force_scroll' => 0,
		'js_eval' => '',
		'navi_position' => NAVI_BOTH);
		
$ajaxcm_plugin_options = array();

if(is_admin()) {
	require('ajaxcm-options.php');
	add_action('admin_menu', 'ajaxcm_plugin_menu');
	add_action('admin_init', 'ajaxcm_admin_init');
}

add_action('wp_footer', 'ajaxcm_output_footer');
add_action('template_redirect', 'ajaxcm_query', 1000);
add_action('wp_loaded', 'ajaxcm_preview');
add_action('comments_template', "ajaxcm_insert_search");
add_action('comment_post', 'ajaxcm_post', 1002); // make this low priority so this callback runs after all other
												 // comment_post hooks.

function ajaxcm_load_options()												 
{
	global $ajaxcm_plugin_options, $ajaxcm_default_settings;

	$ajaxcm_plugin_options = get_option('ajaxcm_plugin_settings');
	foreach($ajaxcm_default_settings as $key => $value) {
		if(!isset($ajaxcm_plugin_options[$key])) {
			$ajaxcm_plugin_options[$key] = $value;
		}
	}
}

/*
 * insert the search form on top of the comments section
 * uses javascript so that it won't be visible when JavaScript is not available
 */
function ajaxcm_insert_search()
{
	global $wp_query;
	$c = get_comment_count($wp_query->post->ID);

	if(comments_open() && $c['approved'] > 0) {
	echo '&nbsp;
	<script type="text/javascript">
	//<![CDATA[ 
	document.write(\'<form style="text-align:center;" id="ajaxcm_srchfrm" action="" method="get"><fieldset style="border:0;"><input class="textfield" type="text" name="ajcom_search" id="ajcom_search" value="" size="22" title="Search term(s), regular expressions allowed"/>&nbsp;&nbsp;<input class="button" type="button" name="submit" value="Search Comments" title="Search comment bodies" onclick="ajaxcm.ajSearch()"/>&nbsp;&nbsp;<input style="display:none;" id="ajaxcm_resetbtn" class="button" type="button" name="reset" value="Reset" onclick="ajaxcm.ajReset()"></fieldset></form>\');
	// ]]>
	</script>
	';
	}
	echo '<div id="ajaxcm_errorbox" style="padding:4px;display:none;text-align:center;background-color:#ebcd19;color:black;position:fixed;left:0;top:0;min-height:20px;width:100%;z-index:100;"></div>';
}
/*
 * set up some javascript variables in the footer. only required when a
 * single page is diplayed
 */
function ajaxcm_output_footer()
{
	global $ajaxcm_plugin_options, $wp_query;
	
	if(is_singular()) {
		echo '<script type="text/javascript">
			// <![CDATA[

			var ajaxcm_blogurl =\'' . get_bloginfo('wpurl') . '\';
			var ajaxcm_postID = \'' . $wp_query->post->ID . '\';
			var ajaxcm_cpage = \''.get_query_var('cpage').'\';
			var ajaxcm_bgcolor = \''.$ajaxcm_plugin_options['highlight_bg'].'\';
			var ajaxcm_fgcolor = \''.$ajaxcm_plugin_options['highlight_fg'].'\';
			var ajaxcm_containerid = \''.$ajaxcm_plugin_options['container_id'].'\';
			var ajaxcm_force_scroll = \''.$ajaxcm_plugin_options['force_scroll'].'\';
			var ajaxcm_js_eval = \''.$ajaxcm_plugin_options['js_eval'].'\';
			var ajaxcm_permalink = \''. get_permalink() . '\';
			ajaxcm.init();
			
			// ]]>
			</script>
			';
	}	
}

/*
 * enqueue the javascript
 */
function ajaxcm_enqueue_scripts()
{
	global $ajaxcm_plugin_options;
	
	if(is_singular()) {
		if($ajaxcm_plugin_options['use_MCE'])
   			wp_enqueue_script('tiny_mce', get_option('siteurl') . '/wp-includes/js/tinymce/tiny_mce.js', false, '20081129', false);
		wp_enqueue_script('ajaxcm-js', WP_PLUGIN_URL . AJAXCM_PLUGIN_PATH . '/ajaxcm-js.js', false, '1.0', true);
	}
}

/*
 * output one page of $perpage toplevel comments and run them
 * through wp_list_comments() so that custom comment callbacks
 * can be used.
 */												 
function ajaxcm_output($postid, $page, $term = '')
{
		global $post, $ajaxcm_plugin_options, $wp_query;
		$n = 0;
		$c_new = array();
		/*
		 * get relevant options
		 */
		
		$post = get_post($postid);
		$order = get_option('comment_order');
		$perpage = get_option('comments_per_page');
		$threaded = get_option('thread_comments');
		$comments = get_comments(array('post_id' => $postid, 'status' => 'approved', 'order' => 'ASC')); //, 'offset' => $offset, 'number' => $perpage));
		if(strlen($term) > 1) {
			foreach ($comments as $c) {
				if (preg_match("/".$term."/i", $c->comment_content)) {
					$c_new[$n++] = $c;
				}
			}
			$comments = $c_new;
		}
		$max_pages = get_comment_pages_count($comments, $perpage, $threaded);
		if($page == -1)
			$page = $max_pages;
		/*
		 * output the comment navigation
		 */
		$wp_query = new WP_Query('p='.$postid.'8&cpage='.$page);
		if($ajaxcm_plugin_options['use_prev_next']) {
			$link = ajaxcm_get_previous_comments_link();
			$link1 = ajaxcm_get_next_comments_link('', $max_pages);
			$pagination = '<div class="alignright">'.$link1.'</div><div class="alignleft">'.$link.'</div><div style="clear:both;"></div>';
		}
		else {
   			$pagination = ajaxcm_paginate_comments_links(array('cpage' => $page, 'cm_pages' => $max_pages, 'echo' => 0), true);
		}
   		if($ajaxcm_plugin_options['navi_position'] == NAVI_TOP || $ajaxcm_plugin_options['navi_position'] == NAVI_BOTH)
			echo '<div class="',$ajaxcm_plugin_options['naviclass'],'">', $pagination, '</div>';
   		/*
   		 * output the comments, run them through wp_list_comments to allow custom comment
   		 * callbacks
   		 */
		echo '<ol id="thecomments" class="commentlist">';
		wp_list_comments(array('per_page' =>$perpage, 'page' => $page, 'callback' => $ajaxcm_plugin_options['cmcallback']), $comments);
		echo '</ol>';
   		if($ajaxcm_plugin_options['navi_position'] == NAVI_BOTTOM || $ajaxcm_plugin_options['navi_position'] == NAVI_BOTH)
			echo '<div class="',$ajaxcm_plugin_options['naviclass'],'">', $pagination, '</div>';
}

/*
 * process the XMLHttpRequest for posting a comment
 * ouput the last comment page
 */
function ajaxcm_post($val)
{
	if( $_REQUEST['ajax_post'] == '1' ) {
		
		global $ajaxcm_plugin_options;
		ajaxcm_load_options();
		
		$postid = intval($_REQUEST['comment_post_ID']);
		/*
		 * for a threaded reply redirect to the current page
		 * otherwise to the last comment page
		 */
		if(intval($_REQUEST['comment_parent']) != 0) {
			$page 	= intval($_REQUEST['apage']);
		}
		else {
			$page = -1;
		}
		ajaxcm_output($postid, $page);
		die;
	}
	else
		return($val);
}

function ajaxcm_preview($val)
{
	if($_REQUEST['ajaxcm_preview'] == '1') {
		
		global $ajaxcm_plugin_options;
		ajaxcm_load_options();
			
		$comments = array();
		$c = new stdClass;
		$c->comment_ID = '1';
		$c->comment_content =  stripslashes(urldecode($_REQUEST['comment']));
		$c->comment_date_gmt = date('Y-m-d, H:i (UTC)', time());
		$c->comment_date = $c->comment_date_gmt;
		$comments[0] = $c;
		echo '<h3 style="text-align:center;">Comment preview<a href="#respond" onclick="ajaxcm.clearPreview();return false;">  (Clear)</a></h3><ol id="thecomments" class="commentlist">';
		wp_list_comments(array('per_page' =>1, 'page' => 1, 'callback' => $ajaxcm_plugin_options['cmcallback']), $comments);
		echo '</ol>';
		die;
	}
	else
		return($val);
}

function ajaxcm_query($val)
{
	global $ajaxcm_plugin_options;
	
	ajaxcm_load_options();
	ajaxcm_enqueue_scripts();
	/*
	 * process the XMLHttpRequest
	 */
	if( $_REQUEST['ajax_getcomments'] == '1' ) {
		
		/*
		 * sanitize arguments
		 */
		$postid = intval($_REQUEST['postid']);
		$page 	= intval($_REQUEST['apage']);
		
		if($page < 1)
			$page = 1;
		
		if( $_REQUEST['ajaxcm_find'] ) {
			$term = $_REQUEST['ajaxcm_find'];
		}
		ajaxcm_output($postid, $page, $term);
		die;
	}
	else 
		return($val);
}

/**
 * Create pagination links for the comments on the current post.
 *
 * @see paginate_links()
 * @since 2.7.0
 *
 * @param string|array $args Optional args. See paginate_links.
 * @return string Markup for pagination links.
*/
function ajaxcm_paginate_comments_links($args = array(), $inline = false) {
        global $wp_rewrite;

        if ( !get_option('page_comments') )
                return;

        if( $inline )
        	$page = $args['cpage'];
        else
        	$page = get_query_var('cpage');
        	
        if ( !$page )
            $page = 1;
        
        if( $inline )
        	$max_page = $args['cm_pages'];
        else
        	$max_page = get_comment_pages_count();
        	
        $defaults = array(
                'base' => add_query_arg( 'cpage', '%#%' ),
                'format' => '',
                'total' => $max_page,
                'current' => $page,
                'echo' => true,
                'add_fragment' => '#comments'
        );
        if ( $wp_rewrite->using_permalinks() )
                $defaults['base'] = user_trailingslashit(trailingslashit(get_permalink()) . 'comment-page-%#%', 'commentpaged');

        $args = wp_parse_args( $args, $defaults );
        $page_links = paginate_links( $args );

        if ( $args['echo'] )
                echo $page_links;
        else
                return $page_links;
}

function ajaxcm_get_previous_comments_link( $label = '' ) {
        $page = get_query_var('cpage');

        $prevpage = intval($page) - 1;

        if($prevpage < 1)
        	return;
        	
        if ( empty($label) )
                $label = __('&laquo; Older Comments');

        return '<a href="' . esc_url( ajaxcm_get_comments_pagenum_link( $prevpage ) ) . '" ' . apply_filters( 'previous_comments_link_attributes', '' ) . '>' . preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label) .'</a>';
}

function ajaxcm_get_next_comments_link( $label = '', $max_page = 0 ) {
        global $wp_query;

        $page = get_query_var('cpage');

        $nextpage = intval($page) + 1;

        if($nextpage > $max_page)
        	return;
        	
        if ( empty($label) )
                $label = __('Newer Comments &raquo;');

        return '<a href="' . esc_url( ajaxcm_get_comments_pagenum_link( $nextpage, $max_page ) ) . '" ' . apply_filters( 'next_comments_link_attributes', '' ) . '>' . preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label) .'</a>';
}

function ajaxcm_get_comments_pagenum_link( $pagenum = 1, $max_page = 0 ) {
	global $post, $wp_rewrite;

	$pagenum = (int) $pagenum;

	$result = get_permalink( $post->ID );

	if ( $wp_rewrite->using_permalinks() )
		$result = user_trailingslashit( trailingslashit($result) . 'comment-page-' . $pagenum, 'commentpaged');
	else
		$result = add_query_arg( 'cpage', $pagenum, $result );

	$result .= '#comments';

	$result = apply_filters('get_comments_pagenum_link', $result);

	return $result;
}
?>