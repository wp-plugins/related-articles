<?php
/**
Plugin Name: Related Articles
Description: <p>Returns a list of related entries to display into your posts/pages/etc.</p><p>You may configure the apparence, the weights, etc.</p><p>It is also possible to display featured images or first images in articles. </p><p>This plugin is under GPL licence</p>
Version: 1.2.1
Framework: SL_Framework
Author: SedLex
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/plugins/related-articles/
License: GPL3
*/

//Including the framework in order to make the plugin work

require_once('core.php') ; 

require_once ( ABSPATH . 'wp-admin/includes/image.php' );

/** ====================================================================================================================================================
* This class has to be extended from the pluginSedLex class which is defined in the framework
*/
class related_articles extends pluginSedLex {

	/** ====================================================================================================================================================
	* Plugin initialization
	* 
	* @return void
	*/
	static $instance = false;

	protected function _init() {
		global $wpdb ; 
		
		// Name of the plugin (Please modify)
		$this->pluginName = 'Related Articles' ; 
		
		// The structure of the SQL table if needed (for instance, 'id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', UNIQUE KEY id_post (id_post)') 
		$this->tableSQL = "id_post mediumint(9) NOT NULL, extracted_keywords TEXT DEFAULT '', related_posts TEXT DEFAULT '', signature_param TEXT DEFAULT '', date_maj DATETIME, UNIQUE KEY id_post (id_post)" ; 
		// The name of the SQL table (Do no modify except if you know what you do)
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 

		//Configuration of callbacks, shortcode, ... (Please modify)
		// For instance, see 
		//	- add_shortcode (http://codex.wordpress.org/Function_Reference/add_shortcode)
		//	- add_action 
		//		- http://codex.wordpress.org/Function_Reference/add_action
		//		- http://codex.wordpress.org/Plugin_API/Action_Reference
		//	- add_filter 
		//		- http://codex.wordpress.org/Function_Reference/add_filter
		//		- http://codex.wordpress.org/Plugin_API/Filter_Reference
		// Be aware that the second argument should be of the form of array($this,"the_function")
		// For instance add_action( "the_content",  array($this,"modify_content")) : this function will call the function 'modify_content' when the content of a post is displayed
		
		add_action('save_post', array($this,"post_has_been_saved"));
		
		// Important variables initialisation (Do not modify)
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		// activation and deactivation functions (Do not modify)
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array('related_articles','uninstall_removedata'));
		
		add_action( 'widgets_init', array($this, '_load_widget')) ;		
	}
	
	/** ====================================================================================================================================================
	* In order to uninstall the plugin, few things are to be done ... 
	* (do not modify this function)
	* 
	* @return void
	*/
		
	public function _load_widget () {
		register_widget( 'related_articles_Widget' );
	}
	
	/** ====================================================================================================================================================
	* In order to uninstall the plugin, few things are to be done ... 
	* (do not modify this function)
	* 
	* @return void
	*/
	
	public function uninstall_removedata () {
		global $wpdb ;
		// DELETE OPTIONS
		delete_option('related_articles'.'_options') ;
		if (is_multisite()) {
			delete_site_option('related_articles'.'_options') ;
		}
		
		// DELETE SQL
		if (function_exists('is_multisite') && is_multisite()){
			$old_blog = $wpdb->blogid;
			$old_prefix = $wpdb->prefix ; 
			// Get all blog ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->blogs));
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				$wpdb->query("DROP TABLE ".str_replace($old_prefix, $wpdb->prefix, $wpdb->prefix . "pluginSL_" . 'related_articles')) ; 
			}
			switch_to_blog($old_blog);
		} else {
			$wpdb->query("DROP TABLE ".$wpdb->prefix . "pluginSL_" . 'related_articles' ) ; 
		}
		
		// DELETE FILES if needed
		//SLFramework_Utils::rm_rec(WP_CONTENT_DIR."/sedlex/my_plugin/"); 
		$plugins_all = 	get_plugins() ; 
		$nb_SL = 0 ; 	
		foreach($plugins_all as $url => $pa) {
			$info = pluginSedlex::get_plugins_data(WP_PLUGIN_DIR."/".$url);
			if ($info['Framework_Email']=="sedlex@sedlex.fr"){
				$nb_SL++ ; 
			}
		}
		if ($nb_SL==1) {
			SLFramework_Utils::rm_rec(WP_CONTENT_DIR."/sedlex/"); 
		}
	}

	/**====================================================================================================================================================
	* Function called when the plugin is activated
	* For instance, you can do stuff regarding the update of the format of the database if needed
	* If you do not need this function, you may delete it.
	*
	* @return void
	*/
	
	public function _update() {
		
	}
	
	/**====================================================================================================================================================
	* Function called to return a number of notification of this plugin
	* This number will be displayed in the admin menu
	*
	* @return int the number of notifications available
	*/
	 
	public function _notify() {
		return 0 ; 
	}
	
	/**====================================================================================================================================================
	* Function to instantiate the class and make it a singleton
	* This function is not supposed to be modified or called (the only call is declared at the end of this file)
	*
	* @return void
	*/
	
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** ====================================================================================================================================================
	* Init javascript for the admin side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('my_plugin_script', plugins_url('/script.js', __FILE__));</code>
	*	<code>$this->add_inline_js($js_text);</code>
	*	<code>$this->add_js($js_url_file);</code>
	*
	* @return void
	*/
	
	function _admin_js_load() {	
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init css for the admin side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _admin_css_load() {
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init javascript for the public side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('my_plugin_script', plugins_url('/script.js', __FILE__));</code>
	*	<code>$this->add_inline_js($js_text);</code>
	*	<code>$this->add_js($js_url_file);</code>
	*
	* @return void
	*/
	
	function _public_js_load() {	
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init css for the public side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _public_css_load() {	
		$this->add_inline_css(".related_post_featured_image_img {
   width:".$this->get_param('width_thumb')."px;
   height:".$this->get_param('height_thumb')."px;
}
".$this->get_param('css')) ; 
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Called when the content is displayed
	*
	* @param string $content the content which will be displayed
	* @param string $type the type of the article (e.g. post, page, custom_type1, etc.)
	* @param boolean $excerpt if the display is performed during the loop
	* @return string the new content
	*/
	
	function _modify_content($content, $type, $excerpt) {	
		global $post ; 
		if ( ($excerpt == true) && (!$this->get_param('display_in_excerpt')) )
			return $content ; 
		
			
		if (strpos(','.$this->get_param('display_in').',', ','.$type.',')!==FALSE) {
			return $content.$this->display_related($post->ID) ; 
		} 
		
		return $content; 
	}
	
	/** ====================================================================================================================================================
	* Define the default option values of the plugin
	* This function is called when the $this->get_param function do not find any value fo the given option
	* Please note that the default return value will define the type of input form: if the default return value is a: 
	* 	- string, the input form will be an input text
	*	- integer, the input form will be an input text accepting only integer
	*	- string beggining with a '*', the input form will be a textarea
	* 	- boolean, the input form will be a checkbox 
	* 
	* @param string $option the name of the option
	* @return variant of the option
	*/
	public function get_default_option($option) {
		switch ($option) {
			// Alternative default return values (Please modify)
			case 'nb_similar_posts' 		: return 5				; break ; 
			case 'nb_keywords' 		: return 20				; break ; 

			case 'css' 		: return "*.related_posts {
   background-color:#EEEEEE ; 
   margin:10px;
   padding : 10px;
}
.related_posts_title {
   font-size:14px ;
   text-align:center;
   font-weight:bold;
}
.related_post {
   font-size:11px ;
   font-style:normal ; 
}
.related_post_fi {
   font-size:11px ;
   font-style:normal ; 
   text-align:center ; 
}
.related_post_fi_div {
   position:absolute;
   bottom:0px ; 
   background-color:#FFFFFF;
   opacity:0.7;
   width:100%;
}
.related_post_featured_image {
   border: 1px solid #999999;
   float:left ; 
   width:160px;
   height:160px;
   margin:5px;
   padding:0px;
   overflow:hidden;
   position:relative;
}
.related_post_featured_image_img {
   margin:0px;
   padding:0px;
}
.related_post_featured_image_img:hover {
    opacity: 0.8;
}
"		; break ; 
			case 'html' 		: return "*<div class='related_posts'>
<p class='related_posts_title'>You may also like ...</p>
     %related_posts_with_featured_image%
</div>"			; break ; 
			
			case 'width_thumb' 		: return 160				; break ; 
			case 'height_thumb' 		: return 160				; break ; 
			case 'a_thumb' 		: return false				; break ; 

			case 'default_image' 		: return "[media]"				; break ; 

			case 'ponderation_content' 		: return 1				; break ; 
			case 'ponderation_title' 		: return 1				; break ; 
			case 'ponderation_category' 		: return 1				; break ; 
			case 'ponderation_keywords' 		: return 1				; break ; 

			case 'show_number' 		: return false				; break ; 

			case 'type_list' 		: return "post,page"				; break ; 
			case 'display_in' 		: return "post"				; break ; 
			case 'display_in_excerpt' 		: return false				; break ; 
			
			case 'last_update_posts'		: return 0 ; 
		}
		return null ;
	}

	/** ====================================================================================================================================================
	* The admin configuration page
	* This function will be called when you select the plugin in the admin backend 
	*
	* @return void
	*/
	
	public function configuration_page() {
		global $wpdb, $blog_id;
		?>
		<div class="plugin-titleSL">
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		
		<div class="plugin-contentSL">		
			<?php echo $this->signature ; ?>

			<?php
			//===============================================================================================
			// After this comment, you may modify whatever you want
			
			// We check rights
			$this->check_folder_rights( array(array(WP_CONTENT_DIR."/sedlex/test/", "rwx")) ) ;
			
			$tabs = new SLFramework_Tabs() ; 
			
			ob_start() ; 
				$params = new SLFramework_Parameters($this) ; 


				$params->add_title(__("General appearance options", $this->pluginID)) ; 
				$params->add_param('nb_similar_posts', __("The number of related posts to display in each post:", $this->pluginID)) ; 
				$params->add_param('display_in', __("The list of type of posts where the related posts are displayed:", $this->pluginID)) ; 
				$params->add_comment(__("It is a coma separated list. The default value is:", $this->pluginID)) ; 
				$params->add_comment_default_value('display_in') ; 
				$params->add_param('display_in_excerpt', __("Display in excerpt:", $this->pluginID)) ; 
				
				$params->add_title(__("Advanced appearance options", $this->pluginID)) ; 
				$params->add_param('html', __("HTML:", $this->pluginID)) ; 
				$params->add_comment(sprintf(__("Please note that %s stands for a list of related posts. The default value of the HTML is:", $this->pluginID), "<code>%related_posts%</code>")) ; 
				$params->add_comment(sprintf(__("Please note that %s stands for a list of related posts with their featured images or their first images.", $this->pluginID), "<code>%related_posts_with_featured_image%</code>")) ; 
				$params->add_comment(__("The default value of the HTML is:", $this->pluginID)) ; 
				$params->add_comment_default_value('html') ; 
				$params->add_param('width_thumb', __("Width of the thumbnail image if you choose to display the featured image (see above):", $this->pluginID)) ; 
				$params->add_comment(__("The CSS will be modified.", $this->pluginID)) ; 
				$params->add_param('height_thumb', __("Height of the thumbnail image if you choose to display the featured image (see above):", $this->pluginID)) ; 
				$params->add_comment(__("The CSS will be modified.", $this->pluginID)) ; 
				$params->add_param('a_thumb', __("Make the featured images clickable:", $this->pluginID)) ; 
				$params->add_param('css', __("CSS:", $this->pluginID)) ; 
				$params->add_comment(__("The default value of the CSS is:", $this->pluginID)) ; 
				$params->add_comment_default_value('css') ; 
				$params->add_param('default_image', __("Choose the default image to be displayed when no image is found:", $this->pluginID)) ; 
				
				$params->add_title(__("Advanced options", $this->pluginID)) ; 
				$params->add_param('show_number', __("Show the score in articles and for the logged users:", $this->pluginID)) ; 
				$params->add_param('type_list', __("What are the different types of posts you want to look for:", $this->pluginID)) ; 
				$params->add_comment(__("It is a coma separated list. The default value is:", $this->pluginID)) ; 
				$params->add_comment_default_value('type_list') ; 
				
				$params->add_title(__("Computation of the proximity score", $this->pluginID)) ; 
				$params->add_param('nb_keywords', __("The number of words used to compare posts:", $this->pluginID)) ; 
				$params->add_param('ponderation_content', __("The weight of the content:", $this->pluginID)) ; 
				$params->add_param('ponderation_title', __("The weight of the title:", $this->pluginID)) ; 
				$params->add_param('ponderation_category', __("The weight of the categories:", $this->pluginID)) ; 
				$params->add_param('ponderation_keywords', __("The weight of the keywords:", $this->pluginID)) ; 
				
				$params->flush() ; 
				
			$parameters = ob_get_clean() ;
			
			ob_start() ; 
			
				// Examples for creating tables
				//----------------------------------
				$list_post = $this->get_param('type_list') ; 
				$list_post = explode(",",$list_post) ; 
				$nb_post = 0 ; 
				$detail_nb = "" ; 
				foreach($list_post  as $l) {
					$published_posts = wp_count_posts($l);
					$nb_post += $published_posts->publish ; 
					if ($detail_nb!="")
						$detail_nb .= ", " ; 
					$detail_nb .= $published_posts->publish." ".$l ; 
				}

				echo "<p>".sprintf(__("For now, your have %s published articles (i.e. %s).", $this->pluginID), $nb_post, $detail_nb)."</p>" ; 
				echo "<p>".sprintf(__("%s articles have their keywords cached.", $this->pluginID), $this->count_keywords_cached_posts())."</p>" ; 
				echo "<p>".sprintf(__("%s articles have similar posts cached.", $this->pluginID), $this->count_similar_posts_cache())."</p>" ; 
				
				
				echo "<p>".__("In the following table, you will find some examples of related posts that may be displayed.", $this->pluginID)."</p>" ; 
				echo "<p>".__("Please note that the presented examples are randomly selected. Refresh the page to change the examples.", $this->pluginID)."</p>" ; 
				
				$table = new SLFramework_Table() ; 
				$table->title(array(__("Title of the post", $this->pluginID), __("Extracted words", $this->pluginID), __("Related posts", $this->pluginID))) ; 
				
				$nb_max = 1 ; 
				$multiple = 3 ; 
				
				$args = array( 'numberposts' => $nb_max , 'orderby' => 'rand' );
				$rand_posts = get_posts( $args );
				foreach( $rand_posts as $p ) {
					
					$cel1 = new adminCell("<strong><a href='".get_permalink($p->ID)."'>".$p->post_title."</a></strong>") ; 		
					ob_start() ; 
						$keywords = $this->extract_keywords_fromcache($p->ID, $this->get_param('nb_keywords')) ; 
						foreach ($keywords as $k=>$n) {
							$progress_status = new SLFramework_Progressbar (300, 20, floor($n), $k." ($n %)") ; 
							$progress_status->flush() ; 
							echo "<br>" ; 
						}
					$cel2 = new adminCell(ob_get_clean()) ; 	
					
					ob_start() ; 
						$related_posts = $this->similar_posts_fromcache($p->ID, $this->get_param('nb_similar_posts')) ; 
						foreach ($related_posts as $pi=>$n) {
							echo "<p>" ; 
							echo "<a href='".get_permalink($pi)."'>".get_the_title($pi)."</a> ($n)" ; 
							echo "</p>" ; 
						}
					
					$cel3 = new adminCell(ob_get_clean()) ; 		
					$table->add_line(array($cel1, $cel2, $cel3), '1') ; 				
				}
			
				echo $table->flush() ; 
				
			$tabs->add_tab(__('Examples',  $this->pluginID), ob_get_clean()) ; 	
			// HOW To
			ob_start() ;
				echo "<p>".__("This plugin enables the display of a number of related posts in each post.", $this->pluginID)."</p>" ; 
			$howto1 = new SLFramework_Box (__("Purpose of that plugin", $this->pluginID), ob_get_clean()) ; 
			ob_start() ;
				echo "<p>".__('This plugin only compute a proximity score between each page/post and score the pages/posts with the hightest score.', $this->pluginID)."</p>" ; 
				echo "<p>".__('The score is based on the title of the post, the content of the post, the category associated with the post and the keywords.', $this->pluginID)."</p>" ; 
			$howto2 = new SLFramework_Box (__("How it works?", $this->pluginID), ob_get_clean()) ; 

			ob_start() ;
				 echo $howto1->flush() ; 
				 echo $howto2->flush() ; 
			$tabs->add_tab(__('How To',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_how.png") ; 				

			$tabs->add_tab(__('Parameters',  $this->pluginID), $parameters, plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	
			
			$frmk = new coreSLframework() ;  
			if (((is_multisite())&&($blog_id == 1))||(!is_multisite())||($frmk->get_param('global_allow_translation_by_blogs'))) {
				ob_start() ; 
					$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
					$trans = new SLFramework_Translation($this->pluginID, $plugin) ; 
					$trans->enable_translation() ; 
				$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 	
			}

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new SLFramework_Feedback($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	
			
			ob_start() ; 
				// A list of plugin slug to be excluded
				$exlude = array('wp-pirate-search') ; 
				// Replace sedLex by your own author name
				$trans = new SLFramework_OtherPlugins("sedLex", $exlude) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	
			
			echo $tabs->flush() ; 
			
			// Before this comment, you may modify whatever you want
			//===============================================================================================
			?>
			<?php echo $this->signature ; ?>
		</div>
		<?php
	}
	
	/** ====================================================================================================================================================
	* Get param signature
	*
	* @return void
	*/
	
	function get_param_signature() {
		return sha1($this->get_param('nb_similar_posts').$this->get_param('nb_keywords').$this->get_param('ponderation_content').$this->get_param('ponderation_title').$this->get_param('ponderation_category').$this->get_param('ponderation_keywords')) ; 
	}
	
	/** ====================================================================================================================================================
	* Called when a post or a page has been saved
	*
	* @return void
	*/
	
	function post_has_been_saved($post_id) {
		global $wpdb ; 
		
		// We update the parameter to inform that the previous similar post stored in the database should be recomputed
		$this->set_param('last_update_posts', date("Y-m-d H:i:s")) ; 
		
		//We check if there is already an entry
		$query = "SELECT id_post FROM ".$this->table_name." WHERE id_post='$post_id'"  ; 
		if ($post_id==$wpdb->get_var($query)) {
			$query = "UPDATE ".$this->table_name." SET extracted_keywords='', signature_param='".$this->get_param_signature()."' WHERE id_post='$post_id'"  ; 
			$wpdb->query($query) ; 
		} 
	}
	
	/** ====================================================================================================================================================
	* Extract keywords from a post
	*
	* @return void
	*/

	function extract_keywords($ID, $max = 20) {
		// We retrieve the HTML
		$post = get_post($ID) ;  
		
		if ($post==null) {
			return array() ; 
		}
		
		//THE CONTENT
		//=============================
		
		$html = $post->post_content ; 

		// strip tags and html entities
		$text = preg_replace('/&(#x[0-9a-f]+|#[0-9]+|[a-zA-Z]+);/', '', strip_tags($html) );
		
		// strip shorttag
		$text = preg_replace('/\[.*\]/', '', $text );
	
		// 3.2.2: ignore soft hyphens
		// Requires PHP 5: http://bugs.php.net/bug.php?id=25670
		$softhyphen = html_entity_decode('&#173;',ENT_NOQUOTES,'UTF-8');
		$text = str_replace($softhyphen, '', $text);
	
		$charset = get_option('blog_charset');
		if ( function_exists('mb_split') && !empty($charset) ) {
			mb_regex_encoding($charset);
			$wordlist = mb_split('\s*\W+\s*', mb_strtolower($text, $charset));
		} else {
			$wordlist = preg_split('%\s*\W+\s*%', strtolower($text));
		}
	
		// Build an array of the unique words and number of times they occur.
		$tokens = array_count_values($wordlist);
		
		// We compute the score for each word
		$score = array() ; 
		foreach ($tokens as $word=>$occurrence) {
			if ( function_exists('mb_strlen') ) {
				$size = mb_strlen($word)  ; 
			} else {
				$size = strlen($word)  ; 
			}
			// We take only words longer than 2 characters (i.e. 3 and longer)
			if ($size>2) {
				$score[$word] = $occurrence/count($wordlist)*10*$size*$size*$this->get_param('ponderation_content') ; 
			}
		}	
		
		//THE TITLE 
		//=============================
		
		$titre = $post->post_title ;
		// strip tags and html entities
		$text = preg_replace('/&(#x[0-9a-f]+|#[0-9]+|[a-zA-Z]+);/', '', $titre);
	
		$charset = get_option('blog_charset');
		if ( function_exists('mb_split') && !empty($charset) ) {
			mb_regex_encoding($charset);
			$wordlist = mb_split('\s*\W+\s*', mb_strtolower($text, $charset));
		} else {
			$wordlist = preg_split('%\s*\W+\s*%', strtolower($text));
		}
	
		// Build an array of the unique words and number of times they occur.
		$tokens = array_count_values($wordlist);
		foreach ($tokens as $word=>$occurrence) {
			if ( function_exists('mb_strlen') ) {
				$size = mb_strlen($word)  ; 
			} else {
				$size = strlen($word)  ; 
			}
			// We take only words longer than 2 characters (i.e. 3 and longer)
			if ($size>2) {
				if (isset($score[$word])) {
					$score[$word] += $occurrence/count($wordlist)*$size*$size*2*$this->get_param('ponderation_title') ; 
				} else {			
					$score[$word] = $occurrence/count($wordlist)*$size*$size*2*$this->get_param('ponderation_title') ; 
				}
			}
		}
		
		// CATEGORIES
		//=============================
		
		$categories = get_the_category($ID) ;
		if ($categories) {
			foreach ($categories as $cat) {
				if ( function_exists('mb_split') && !empty($charset) ) {
					if (isset($score[mb_strtolower($cat->cat_name, $charset)])) {
						$score[mb_strtolower($cat->cat_name, $charset)] += 1/sqrt(count($categories))*35*$this->get_param('ponderation_category') ;	
					} else {
						$score[mb_strtolower($cat->cat_name, $charset)] = 1/sqrt(count($categories))*35*$this->get_param('ponderation_category') ;	
					}
				} else {
					if (isset($score[strtolower($cat->cat_name)])) {
						$score[strtolower($cat->cat_name)] += 1/sqrt(count($categories))*35*$this->get_param('ponderation_category');	
					} else {
						$score[strtolower($cat->cat_name)] = 1/sqrt(count($categories))*35*$this->get_param('ponderation_category');	
					}
				}
			}
		}
		
		// KEYWORDS
		//=============================
		
		$posttags = get_the_tags();
		if ($posttags) {
			foreach($posttags as $tag) {
				if ( function_exists('mb_split') && !empty($charset) ) {
					if (isset($score[mb_strtolower($tag->name, $charset)])) {
						$score[mb_strtolower($tag->name, $charset)] += 1/sqrt(count($posttags))*20*$this->get_param('ponderation_keywords') ;	
					} else {
						$score[mb_strtolower($tag->name, $charset)] = 1/sqrt(count($posttags))*20*$this->get_param('ponderation_keywords') ;	
					}
				} else {
					if (isset($score[strtolower($tag->name)])) {
						$score[strtolower($tag->name)] += 1/sqrt(count($posttags))*20*$this->get_param('ponderation_keywords') ;	
					} else {
						$score[strtolower($tag->name)] = 1/sqrt(count($posttags))*20*$this->get_param('ponderation_keywords') ;	
					}
				}
			}	
		}
	
		arsort($score, SORT_NUMERIC);
		
		$final_list = array_slice($score, 0, $max, true) ; 
		// Normalized
		$sum = 0 ; 
		foreach ($final_list as $l) {
			$sum += $l ; 
		}
		$nomalized_final_list = array() ; 
		foreach ($final_list as $w => $n) {
			$nomalized_final_list[$w] = floor(100*100*$n/$sum)/100 ; 
		}
	
		return $nomalized_final_list ;
	}
	
	/** ====================================================================================================================================================
	* Extract keywords from a post (cached version)
	*
	* @return void
	*/

	function extract_keywords_fromcache($ID, $max = 20) {
		global $wpdb ; 
		$query = "SELECT extracted_keywords, signature_param FROM ".$this->table_name." WHERE id_post='$ID'"  ; 
		$results = $wpdb->get_results($query) ; 
		if ((isset($results[0]))&&($results[0]->extracted_keywords!="")&&($results[0]->signature_param==$this->get_param_signature())) {
			return unserialize(stripslashes($results[0]->extracted_keywords)) ; 
		} else {
			// We compute the keywords
			$keywords = $this->extract_keywords($ID, $max) ; 
			
			//We check if there is already an entry
			$query = "SELECT id_post FROM ".$this->table_name." WHERE id_post='$ID'"  ; 
			if ($ID==$wpdb->get_var($query)) {
				$query = "UPDATE ".$this->table_name." SET extracted_keywords='".addslashes(serialize($keywords))."', signature_param='".$this->get_param_signature()."' WHERE id_post='$ID'"  ; 
				$wpdb->query($query) ; 
			} else {
				$query = "INSERT INTO ".$this->table_name." (id_post, extracted_keywords, signature_param) VALUES ('$ID', '".addslashes(serialize($keywords))."', '".$this->get_param_signature()."') "  ; 
				$wpdb->query($query) ; 
			}
			return $keywords ; 
		}
	}
	
	/** ====================================================================================================================================================
	* Count post that are keyword cached ...
	*
	* @return void
	*/

	function count_keywords_cached_posts() {
		global $wpdb ; 
		$query = "SELECT id_post, extracted_keywords, signature_param FROM ".$this->table_name.""  ; 
		$results = $wpdb->get_results($query) ; 
		$count = 0 ; 
		foreach ($results as $r) {
			if (($r->extracted_keywords!="")&&($r->signature_param==$this->get_param_signature())) {
				//On teste que c'est bien un post de la category qu'on veut qu'il est publie 
				$p = get_post($r->id_post) ;
				if (($p!=null)&&($p->post_status=="publish")) {
					$list_post = $this->get_param('type_list') ; 
					$list_post = explode(",",$list_post) ; 
					foreach($list_post  as $l) {
						if ($p->post_type ==trim($l)) {
							$count ++ ;  
						}	
					}
				}
			} 
		}
		return $count ; 
	}
	
	/** ====================================================================================================================================================
	* Compute a distance between two posts
	*
	* @return integer
	*/

	function compute_distance($keyword1, $keyword2) {
		$val = 0 ; 
		foreach ($keyword1 as $w => $n){
			if (isset($keyword2[$w])) {
				$val += sqrt($n*$keyword2[$w]) ; 
			}
		}
		return floor(100*$val)/100 ; 
	}
	
	/** ====================================================================================================================================================
	* Retrieve similar posts
	*
	* @return integer
	*/

	function similar_posts($ID, $max = 5, $old_results = array()) {
		if (count($old_results)==0) {
			$nb_search = 500 ; 
		} else {
			$nb_search = 10 ; 
		}
		
		$args = array(
		    'numberposts'     => $nb_search,
		    'orderby'         => 'rand',
		    'post_type'       => explode(",",$this->get_param('type_list')),
		    'post_status'     => 'publish' );
		$posts = get_posts($args) ; 
		
		$init_keywords = $this->extract_keywords_fromcache($ID, $this->get_param('nb_keywords')) ; 
		
		$score = array();
		foreach($posts as $p) {
			if ($p->ID==$ID) {
				continue ; 
			}
			$score[$p->ID] = $this->compute_distance($this->extract_keywords_fromcache($p->ID, $this->get_param('nb_keywords')), $init_keywords)  ; 
		}	
		
		// On merge avec les anciens resultats
		foreach ($old_results as $k=>$v) {
			if(!isset($score[$k])) {
				$score[$k] = $v ; 
			}
		}
		
		arsort($score, SORT_NUMERIC);
		$final_list = array_slice($score, 0, $max, true) ; 
		return $final_list ; 
	}
	
	/** ====================================================================================================================================================
	* Extract similar posts from posts (cached version)
	*
	* @return void
	*/

	function similar_posts_fromcache($ID, $max = 5) {
		global $wpdb ; 
		
		$query = "SELECT related_posts, signature_param, date_maj FROM ".$this->table_name." WHERE id_post='$ID'"  ; 
		$results = $wpdb->get_results($query) ; 
		
		if ((count($results)>0) && ($results[0]->related_posts!="") && ($results[0]->signature_param==$this->get_param_signature()) && (strtotime($results[0]->date_maj)-strtotime($this->get_param('last_update_posts'))>0)) {
			$previous_results = unserialize(stripslashes($results[0]->related_posts)) ; 
			// We compute the new posts but with fewer articles
			$related_posts = $this->similar_posts($ID, $max, $previous_results) ; 
		} else {
			// We compute the posts
			$related_posts = $this->similar_posts($ID, $max) ; 
		}
		//We check if there is already an entry
		$query = "SELECT id_post FROM ".$this->table_name." WHERE id_post='$ID'"  ; 
		if ($ID==$wpdb->get_var($query)) {
			$query = "UPDATE ".$this->table_name." SET related_posts='".esc_attr(serialize($related_posts))."', date_maj='".date("Y-m-d H:i:s")."', signature_param='".$this->get_param_signature()."' WHERE id_post='$ID'"  ; 
			$wpdb->query($query) ; 
		} else {
			$query = "INSERT INTO ".$this->table_name." (id_post, related_posts, signature_param,date_maj) VALUES ('$ID', '".esc_attr(serialize($keywords))."', '".$this->get_param_signature()."','".date("Y-m-d H:i:s")."')"  ; 
			$wpdb->query($query) ; 
		}
		return $related_posts ; 
	}
	
	/** ====================================================================================================================================================
	* Count cached similar posts from posts 
	*
	* @return void
	*/

	function count_similar_posts_cache() {
		global $wpdb ; 
		
		$query = "SELECT id_post, related_posts, signature_param, date_maj FROM ".$this->table_name  ; 
		$results = $wpdb->get_results($query) ; 
		
		$count = 0 ; 
		foreach ($results as $r) {
			if (($r->related_posts!="") && ($r->signature_param==$this->get_param_signature()) && (strtotime($r->date_maj)-strtotime($this->get_param('last_update_posts'))>0)) {
				//On teste que c'est bien un post de la category qu'on veut qu'il est publie 
				$p = get_post($r->id_post) ;
				if (($p!=null)&&($p->post_status=="publish")) {
					$list_post = $this->get_param('type_list') ; 
					$list_post = explode(",",$list_post) ; 
					foreach($list_post  as $l) {
						if ($p->post_type ==trim($l)) {
							$count ++ ;  
						}	
					}
				}
			} 
		}
		return $count ; 
	}
	
	/** ====================================================================================================================================================
	* Display the related content
	*
	* @return string
	*/
	
	function display_related($id) {
		global $wpdb ; 
		$content = $this->get_param('html') ; 
		
		$related_posts = $this->similar_posts_fromcache($id, $this->get_param('nb_similar_posts')) ; 
		$cp = "" ; 
		$cp_fi = "" ; 
		foreach ($related_posts as $pi=>$n) {
		
			// Normal posts
			// ---------------------
			$cp .= "<p class='related_post'>" ; 
			$cp .= "<a href='".get_permalink($pi)."'>".get_the_title($pi)."</a>" ; 
			if ((is_user_logged_in())&&($this->get_param('show_number'))) {
				$cp .= " ($n)" ; 
			} 
			$cp .= "</p>" ; 
			
			// with featured image
			// ---------------------
			add_image_size("related-articles-thumb", $this->get_param("width_thumb"), $this->get_param("height_thumb"), true);
			$cp_fi .= "<div class='related_post_featured_image'>" ; 
			$cp_fi .= "<div class='related_post_featured_image_img'>" ;
			if ($this->get_param('a_thumb')) {
				$cp_fi .= "<a href='".get_permalink($pi)."'>" ; 
 			}
			if (has_post_thumbnail($pi)) {
				$id = get_post_thumbnail_id($pi) ; 
				$image = wp_get_attachment_image_src($id, "related-articles-thumb");
				$image_path = wp_get_attachment_metadata($id);
				if (isset($image_path['file'])) {
					$image_path = $image_path['file'] ; 
				} else {
					$image_path = "" ; 
				}
				$upload_dir = wp_upload_dir() ; 
				$upload_dir = $upload_dir['basedir'] ; 
				if (is_file($upload_dir."/".$image_path)) {
					if ($image){
						list($width, $height) = getimagesize($image[0]);
						if (($this->get_param("width_thumb") == $width) && ($this->get_param("height_thumb") == $height)){
							$cp_fi .=  get_the_post_thumbnail( $pi, "related-articles-thumb") ; 
						} else {
							// We have to generate the thumbnail
							$metadata = wp_generate_attachment_metadata( $id, get_attached_file( $id ) );
							wp_update_attachment_metadata( $id, $metadata );
							$cp_fi .=  get_the_post_thumbnail( $pi, "related-articles-thumb") ; 
						}
					} else {
						$cp_fi .=  get_the_post_thumbnail( $pi, "related-articles-thumb") ; 
					}	
				}			
			} else {
				$files = get_children("post_parent=$pi&post_type=attachment&post_mime_type=image");
			  	if($files) {
					$keys = array_reverse(array_keys($files));
					$index = -1 ;
					$upload_dir = "" ; 
					$image_path = "" ; 
					do {
						$index ++ ; 
						if (!isset($keys[$index])) {
							break ; 
						}
						$id = $keys[$index];
						$image = wp_get_attachment_image_src($id, "related-articles-thumb");
							$image_path = wp_get_attachment_metadata($id);
						if (isset($image_path['file'])) {
							$image_path = $image_path['file'] ; 
						} else {
							$image_path = "" ; 
						}
						$upload_dir = wp_upload_dir() ; 
						$upload_dir = $upload_dir['basedir'] ; 
					} while(is_file($upload_dir."/".$image_path)) ; 
					
					if (is_file($upload_dir."/".$image_path)) {
						if ($image){
							list($width, $height) = getimagesize($image[0]);
							if (($this->get_param("width_thumb") == $width) && ($this->get_param("height_thumb") == $height)){
								$cp_fi .=   wp_get_attachment_image($id, "related-articles-thumb");
							} else {
								// We have to generate the thumbnail
								$metadata = wp_generate_attachment_metadata( $id, get_attached_file( $id ) );
								wp_update_attachment_metadata( $id, $metadata );
								$cp_fi .=  wp_get_attachment_image($id, "related-articles-thumb");
							}
						} else {
							$cp_fi .=  wp_get_attachment_image($id, "related-articles-thumb");
						}	
					}			
			  	} else {
			  		// get the URL of the default image
					$attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '',  $this->get_param('default_image'));
					$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';",$attachment_url)); 
					if (isset($attachment[0])) {
						$id_media =  $attachment[0]; 
						if (wp_attachment_is_image( $id_media )) {
							$cp_fi .=  wp_get_attachment_image($id_media, "related-articles-thumb");
						} 
 					} 
			  	}
			}
			if ($this->get_param('a_thumb')) {
				$cp_fi .= "</a>" ; 
 			}
			$cp_fi .= "</div>" ; 			
			$cp_fi .= "<div class='related_post_fi_div'><p class='related_post_fi'>" ; 
			$cp_fi .= "<a href='".get_permalink($pi)."'>".trim(get_the_title($pi))."</a>" ; 
			if ((is_user_logged_in())&&($this->get_param('show_number'))) {
				$cp_fi .= " ($n)" ; 
			} 
			$cp_fi .= "</p></div>" ; 
			$cp_fi .= "</div>" ; 
		}
		$cp_fi .= "<div style='clear:both;'></div>" ;
		
		return str_replace("%related_posts_with_featured_image%", $cp_fi, str_replace("%related_posts%", $cp, $content)) ; 
	}
}

$related_articles = related_articles::getInstance();

// Widget
//=================

class related_articles_Widget extends WP_Widget {
	
	// widget actual processes
	//-----------------------------
	function related_articles_Widget() {
        $this->WP_Widget('related_articles_Widget', 'Related Articles', array('classname' => 'related_articles_Widget', 'description' => 'Display related articles in a widget' ));
    }
	
	// outputs the content of the widget
	//-----------------------------
	public function widget( $args, $instance ) {
		global $post ; 
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo '<h3 class="widget-title">'.$title."</h3>" ; 
		echo "<p>&nbsp;</p>" ; 
		$instance_plugin = call_user_func(array('related_articles', 'getInstance')); 
		
		if ($instance_plugin!==false) {
			echo $instance_plugin->display_related($post->ID);
		}
	}
	
	// outputs the options form on admin
	//-----------------------------
 	public function form($instance) {
        $instance = wp_parse_args( (array) $instance, array( 'title' => 'Related Articles') );
        $title = strip_tags($instance['title']);
		?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		<?php	
	}
	
	// processes widget options to be saved
	//-----------------------------
	public function update( $new_instance, $old_instance ) {
      	$instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']); 
        return $instance;
	}

}


?>