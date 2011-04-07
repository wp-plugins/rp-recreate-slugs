<?php
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

define('RP_REC_SLUGS', 'rp_rec_slugs');

define('RP_RCT_APP',   dirname(plugin_basename(__FILE__)));
define('RP_RCT_PATH',  sprintf('%s%s%s', WP_PLUGIN_DIR, DIRECTORY_SEPARATOR, RP_RCT_APP));
define('RP_RCT_HTTP',  sprintf('%s/%s', WP_PLUGIN_URL, RP_RCT_APP));

if(!function_exists('rp_get_range')){
    function rp_get_range($count, $page, $itemsperpage){
        if(!$count || !$itemsperpage || (($page-1) > ceil($count / $itemsperpage))){
            return array();
        }
        return array(($page-1)*$itemsperpage, $itemsperpage);
    }
}
if(!function_exists('rp_format_range')){
    function rp_format_range($range){
        return empty($range) ? '' : sprintf(" LIMIT %d,%d", $range[0], $range[1]) ;
    }
}

class rp_rec_slugs_plugin {

	function rp_rec_slugs_plugin() {
		add_filter('screen_layout_columns', array(&$this, 'on_screen_layout_columns'), 10, 2);
		add_action('admin_menu', array(&$this, 'on_admin_menu'));
		add_action('admin_post_save_rp_rec_slugs_general', array(&$this, 'on_process'));
	}

	function on_screen_layout_columns($columns, $screen) {
		if ($screen == $this->pagehook) {
			$columns[$this->pagehook] = 2;
		}
		return $columns;
	}

	function on_admin_menu() {
        if(current_user_can('manage_options'))
            $this->pagehook = add_posts_page('Recreate Slugs Utility', "RP Recreate Slugs", 'manage_options', RP_REC_SLUGS, array(&$this, 'on_show_page'));
		add_action('load-'.$this->pagehook, array(&$this, 'on_load_page'));
	}

	function on_load_page() {
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
		add_meta_box('rp-rec-slugs-sidebox-1', 'Other RP Plugins', array(&$this, 'on_sidebox_1_content'), $this->pagehook, 'side', 'core');
		add_meta_box('rp-rec-slugs-sidebox-2', 'Problems?', array(&$this, 'on_sidebox_2_content'), $this->pagehook, 'side', 'core');
	}

	function on_show_page() {

		global $screen_layout_columns;
		add_meta_box('rp-contentbox-1', 'Options', array(&$this, 'on_contentbox_1_content'), $this->pagehook, 'normal', 'core');
		$data       = array();
        $message    = array();

        if(!@session_id()){@session_start();}
        $num_ch_posts       = $_SESSION['rpmessage']['process_posts'] ;
        $num_ch_pages       = $_SESSION['rpmessage']['process_pages'] ;
        $data['next_from']  = isset($_SESSION['rpmessage']['next_from']) ? intval($_SESSION['rpmessage']['next_from']) : 1;
        $data['next_to']    = isset($_SESSION['rpmessage']['next_to']) ? intval($_SESSION['rpmessage']['next_to']) : 1000;
        $asked_already      = $data['asked_already'] = $data['next_from'] > 1;
        unset($_SESSION['rpmessage']);

        if(intval($num_ch_posts)){
            $message[] = sprintf('Modified %d posts.', $num_ch_posts);
        }
        if(intval($num_ch_pages)){
            $message[] = sprintf('Modified %d pages.', $num_ch_pages);
        }        
		?>
        <?php if($message) echo '<div id="message" class="updated"><p><strong>', implode('<br /><br />', $message) , '</strong></p></div>' ; ?>

		<div id="rp-rec-slugs-main" class="wrap">
		<?php screen_icon('options-general'); ?>
		<h2>Recreate Slugs Utility</h2>
		<form action="admin-post.php" method="post" id="form-recreate-slugs">
			<?php wp_nonce_field('rp-rec-slugs-main'); ?>
			<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
			<input type="hidden" name="action" value="save_rp_rec_slugs_general" />

			<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
				<div id="side-info-column" class="inner-sidebar">
					<?php do_meta_boxes($this->pagehook, 'side', $data); ?>
				</div>
				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_meta_boxes($this->pagehook, 'normal', $data); ?>
                        <h4>WARNING:</h4>
						<p class="highlight">Please note that the changes might harm your Wordpress installation. The compatibility below version 3.0 has not been tested.</p>
						<p class="highlight">It is recommended that you switch your site into the <strong>maintenance mode</strong> and create your Wordpress installation <strong>database backup</strong>.</p>
						<p class="highlight">Please note that processing more than 100 records might take quite a long period of time to complete, please allow the script to end in a natural manner and never reload the page before it finishes working.</p>
						<p class="highlight">Please note that you are using this plugin at your own risk.</p>
						<br/>
						<?php do_meta_boxes($this->pagehook, 'additional', $data); ?>
						<p>
							<input type="submit" value="Process Changes" class="button-primary" name="Submit" id="the_submit" />
						</p>
					</div>
				</div>
				<br class="clear"/>
			</div>
		</form>
		</div>
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {
			$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
			postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
            <?php if(!$asked_already): ?>
            $('#form-recreate-slugs').bind('submit', function(){
                if(confirm("You are one step from the core modification of your worpdress installation.\nHave you created the current database backup?\nDo you still want to continue?")){
                    return confirm("This is the last backup warning, continue?");
                }
                return false;
            });
            <?php endif; ?>            
		});
		//]]>
	</script>

		<?php
	}

	function on_process() {
		if (!current_user_can('manage_options')) wp_die( __('Cheatin&#8217; uh?'));
		check_admin_referer('rp-rec-slugs-main');
        $num_ch_posts = $num_ch_pages = 0;
        @set_time_limit(0);
		if(isset($_POST['process_posts'])){
            $num_ch_posts = rp_rec_slugs_plugin::process_posts('post');
        }
        if(isset($_POST['process_pages'])){
            $num_ch_pages = rp_rec_slugs_plugin::process_posts('page');
        }
        if(!@session_id()){@session_start();}
        $_SESSION['rpmessage']['process_posts'] = $num_ch_posts;
        $_SESSION['rpmessage']['process_pages'] = $num_ch_pages;
		wp_redirect($_POST['_wp_http_referer']);
	}

	function on_sidebox_1_content($data) {
		?>
            <ul style="list-style-type:disc;margin-left:20px;">
                <li><a href="http://www.rationalplanet.com/2010/07/rp-newsticker-plugin-for-wordpress/">RP News Ticker</a>
                    - a news scroller that is able to display different useful content
                    (<a href="http://chernivtsi.ws/" target="_blank">demo</a>,
                        <a href="http://wordpress.org/extend/plugins/rp-news-ticker/" target="_blank">wordpress plugin page</a>).
                </li>
            </ul>
		<?php
	}

    function on_sidebox_2_content($data) {
		?>
            <ul style="list-style-type:disc;margin-left:20px;">
                <li>
                    <a href="http://www.rationalplanet.com/my-cv?from-plugin=rp-rec-slugs" target="_blank" title="Hire the developer">Hire the developer</a>
                </li>
            </ul>
		<?php
	}

    function on_contentbox_1_content($data) {
        extract($data);
        global $wpdb;
        $max_post_id = $wpdb->get_var( $wpdb->prepare("SELECT MAX(ID) FROM $wpdb->posts WHERE post_type = 'post' AND post_status NOT IN ('trash', 'auto-draft')"));
        ?>
        <div class="inline-edit-group">
            <strong>The following options are available:</strong>
            <div class="clear"></div>
            <label class="alignleft" style="margin:4px;">
                <input type="checkbox" value="open" name="process_posts" <?php echo $asked_already ? ' checked="checked"' : ''; ?> />
                <span class="checkbox-title"><em>process posts slugs,</em></span>            
                range from <input type="text" size="8" name="range_from" value="<?php echo $next_from; ?>" style="text-align:right" />
                to <input type="text" size="8" name="range_to"  value="<?php echo $next_to; ?>" style="text-align:right" /> (max valid post id is <strong><?php echo $max_post_id; ?></strong>)
            </label>
            <div class="clear"></div>
            <label class="alignleft" style="margin:4px;">
                <input type="checkbox" value="open" name="process_pages">
                <span class="checkbox-title"><em>process pages slugs</em></span>
            </label>
        </div>
        <div class="clear"></div>
        <?php
    }

    static function process_posts($type = 'post'){

        //ob_start();fb('posts', 'Here'); ob_end_flush();
        $page           = 1;
        $cnt            = 0;
        $itemsperpage   = 50;

        $range_from = isset($_POST['range_from']) && intval($_POST['range_from']) > 0 ? intval($_POST['range_from']) : 0 ;
        $range_to = isset($_POST['range_to']) && intval($_POST['range_to']) > 0 ? intval($_POST['range_to']) : 0 ;

        global $wpdb;
        $count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(1) FROM $wpdb->posts WHERE post_type = '$type' AND post_status NOT IN ('trash', 'auto-draft') AND ID BETWEEN $range_from AND $range_to"));
        $num_pages  = ceil($count / $itemsperpage);
        for ($i = 0; $i < $num_pages; $i++) {
            $query = sprintf(
                    "SELECT ID, post_title, post_name, post_parent FROM $wpdb->posts WHERE post_type = '$type' AND post_status NOT IN ('trash', 'auto-draft') AND ID BETWEEN $range_from AND $range_to ORDER BY ID %s",
                        rp_format_range(rp_get_range($count, $i+1, $itemsperpage))
            );
            $pack = $wpdb->get_results($wpdb->prepare($query));
            for ($j = 0; $j < count($pack); $j++) {
                $slug = wp_unique_post_slug(sanitize_title($pack[$j]->post_title, $pack[$j]->post_name), $pack[$j]->ID, 'published', '$type', $pack[$j]->post_parent);
                wp_update_post(array(
                    'ID'        => $pack[$j]->ID,
                    'post_name' => $slug,
                ));
                $cnt++;
            }
        }

        $_SESSION['rpmessage']['next_from']     = $range_to + 1;
        $_SESSION['rpmessage']['next_to']       = $range_to + 1 + ($range_to - $range_from) ;

        return $cnt;
    }
}