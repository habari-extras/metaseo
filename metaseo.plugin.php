<?php
/**
 * Meta SEO an SEO plugin for Habari
 * 
 * @package metaseo
 *
 * This class automatically change your page title
 * to one appropriate for SEO. adds a description
 * and keywords to the page header, and injects
 * indexing tags based on the preferences
 *
 **/

class MetaSeo extends Plugin
{
	/**
	* @var string plugin version number
	*/
	const VERSION= '0.2';
	/**
	* @var OPTION_NAME prepended to all options for saving/retrieval
	*/
	const OPTION_NAME= 'MetaSEO';

	/**
	* @var $them Theme object that is currently being use for display
	*/
	private $theme;
	
	/**
	 * function info
	 *
	 * Returns information about this plugin
	 * @return array Plugin info array
	*/
	function info()
	{
		return array(
		'name' => 'Meta SEO',
		'version' => self::VERSION,
		'url' => 'http://habariproject.org',
		'author' => 'Habari Community',
		'authorurl' => 'http://habariproject.org',
		'license' => 'Apache License 2.0',
		'description' => 'Adds search engine optimizations to the page head',
		);
	}

	/**
	* function set_priorities
	*
	* set priority to a number lower than that
	* used by most plugins to ensure it is the first one called
	* so it doesn't interfere with other plugins calling
	* theme_header()
	*
	* @return array the plugin's priority
	*/
	public function set_priorities()
	{
	  return array(
	    'theme_header' => 6,
	  );
	}

	/**
	* function default_options
	*
	* returns defaults for the plugin
	* @return array default options array
	*/
	private static function default_options()
	{
		return array(
			'home_desc' => Options::get( 'tagline' ),
			);
	}

	/**
	* function action_plugin_activation
	*
	*if the file being passed in,is this file, sets
	* the default options
	*
	* @param $file string name of the file 
	*/
	public function action_plugin_activation( $file )
	{
		if ( realpath( $file ) == __FILE__ ) {
			foreach ( self::default_options() as $name => $value ) {
				Options::set( self::OPTION_NAME . ':' . $name, $value );
			}
		}
	}

	/**
	 * function filter_plugin_config
	 *
	 * Returns  actions to be performed on configuration
	 *
	 * @param array $actions list of actions to perform
	 * @param plugin_id id of the plugin
	 * @return $actions array of actions the plugin will respond to
	 */
	public function filter_plugin_config( $actions, $plugin_id )
      {
		if ( $plugin_id == $this->plugin_id() ) {
			$actions[]= _t('Configure' );
		}
		return $actions;
	}

	/**
	* function action_plugin_ui
	*
	* displays the option form 
	* @param $plugin_id string id of the plugin that is being called
	* @param $action mixed the action begin requested
	* 
	*/
	public function action_plugin_ui( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			switch ( $action ) {
				case _t('Configure' ) :
					$ui = new FormUI( self::OPTION_NAME );
					// Add a text control for the home page description
					$home_desc= $ui->add( 'textarea', 'home_desc', _t('Home Page Description: ' ) );
					$ui->add( 'fieldset', _t( 'Description Options' ), array( $home_desc ) );
					$ui->out();
					break;
			}
		}
	}

	/**
	* function save_options
	 * Fail-safe method to force options to be saved in Habari's options table.
	 *
	 * @return bool Return true to force options to be saved in Habari's options table.
	 */
	public function save_options( $ui ) {
		return true;
	}
	
	/**
	* function action_post_update_status
	*
	*called when either the save or publish button
	* is pressed on the publish page. if a new html title
	* is entered, save it to the postinfo table
	*
	* @return nothing
	*/
	public function action_post_update_status( $post, $new_status )
	{
		$vars = Controller::get_handler_vars();
		
		if($vars['content_type'] == Post::type('entry') || $vars['content_type'] == Post::type('page')) {
				$post->info->html_title= $vars['html_title'];
		}
	}
	
	/**
	* function filter_publish_controls
	*
	* adds controls to the publish page
	* so we can set the html title we want on a
	* page or entry if we don't want it to be the 
	* same as the actual page or post title
	*
	* @return array  containing the controls to be on the publish page
	*/
	public function filter_publish_controls ($controls, $post) {
		$vars = Controller::get_handler_vars();
				
		if( $vars['content_type'] == Post::type('entry') || $vars['content_type'] == Post::type('page') ) {
			$output = '';
			
			$output.= '<div class="text container">';
			$output.= '<p class="column span-2"><label for="html_title">Page Title:</label></p>';
			$output.= '<p class="column span-17 last"><input style="width: 300px;" type="text" id="page-title" name="html_title" value="';
			if(strlen($post->info->html_title) > 0) {
				$output .= $post->info->html_title;
			}
			$output .= '" /></p></div>';
			$controls['Page Title'] = $output;
		}
		
		return $controls;
	}

	/**
	* function filter_template_fallback
	*
	* this filter is called before the display of any page,
	* so it is used to begin buffering output if a page being
	* displayed for normal readers is being requested
	*
	* @param $fallback string the theme template to display
	* @return  string the theme template to display. unmodified
	*/
	public function filter_template_fallback($fallback)
	{
		ob_start(array( $this, 'dotags' ));
		return $fallback;
	}
	
	/**
	* function theme_header
	*
	* called to added output to the head of a page before it is being displayed.
	* Here it is being used to grab the contents of the buffer if we are buffering
	* output, change the title tag text, and return the modified contents for output
	* 
	* @param $theme Theme object being displayed
	* @return nothing
	*/
	public function theme_header($theme)
	{
		$this->theme= $theme;
	}
	
	/**
	* function action_update_check
	*
	* Add update beacon support
	**/
	public function action_update_check()
	{
		Update::add( 'FriendFeed', 'DE6CFC70-1661-11DD-8BC9-25DB55D89593', $this->info->version );
	}
 
 	

	/* function get_description
	*
	 * This function creates the meta description tag  
	 * based on an excerpt of the post being displayed.
	 * Single entry - the excerpt for the individual entry
	 * Page - the excerpt for the page
	*
	* @return string the complete meta description tag
	*
	*/
	private function get_description()
	{
		$out= '';
		$desc= '';
		
		$matched_rule= URL::get_matched_rule();
		
		if ( is_object( $matched_rule ) ) {
			$rule= $matched_rule->name;
			switch( $rule) {
				case 'display_home':
					$desc= htmlspecialchars( strip_tags( Options::get( self::OPTION_NAME . ':home_desc' ) ) );
					break;
				case 'display_entry':
				case 'display_page':
					if( isset( $this->theme->post ) ) {
						$desc= htmlspecialchars( Utils::truncate( strip_tags( $this->theme->post->content), 200, false ) );
					}
					break;
				default:
			}
		}
		if( strlen( $desc ) ) {
			$desc= str_replace( "\r\n", " ", $desc );
			$desc= str_replace( "\n", " ", $desc );
			$out= "<meta name=\"description\" content=\"";
			$out.= $desc;
			$out.= "\" >\n";
		}
	
		return $out;
	}
	
	/**
	 * function get_keywords
	 *
	 * This function creates the meta keywords tag based 
	 * based on the type of page being loaded.
	 * Single entry and single page - the tags for the individual entry
	 * Home - all the tags for the blog up to a maximum of 50
	 * Tag page - the tag for which the page was generated
	 *
	 * @return string the meta keywords entry
	*/
	private function get_keywords()
	{
		$out= '';
		$keywords= '';
		
		$matched_rule= URL::get_matched_rule();
		
		if ( is_object( $matched_rule ) ) {
			$rule= $matched_rule->name;
			switch( $rule) {
				case 'display_entry':
				case 'display_page':
					if( isset( $this->theme->post ) && is_array( $this->theme->post->tags ) ) {
						$keywords= implode( ', ', $this->theme->post->tags );
					}
					break;
				case 'display_entries_by_tag':
					$keywords= Controller::get_var( 'tag' );
					break;
				case 'display_home':
					$tags= array();
					foreach( Tags::get() as $tag ) {
						// limit to the first 50 tags to prevent keyword stuffing
						if( count( $tags ) < 50 ) {
							$tags[]= htmlspecialchars( $tag->tag);
						}
						else {
							break;
						}
					}
					$keywords= implode( ', ', $tags );
					break;
				default:
			}
		}
		if( strlen( $keywords ) ) {
			$out= "<meta name=\"keywords\" content=\"";
			$out.= $keywords;
			$out.= "\" >\n";
		}
		return $out;
	}

	private function get_robots()
	{
		$out= '';
		$robots= '';
		
		$matched_rule= URL::get_matched_rule();
		
		if ( is_object( $matched_rule ) ) {
			$rule= $matched_rule->name;
			switch( $rule) {
				case 'display_entry':
				case 'display_page':
				case 'display_home':
					$robots= 'index,follow';
					break;
				case 'display_entries_by_tag':
					$robots= 'noindex,follow';
					break;
				default:
					$robots= 'noindex,follow';
					break;
			}
		}
		if( strlen( $robots ) ) {
			$out= "<meta name=\"robots\" content=\"";
			$out.= $robots;
			$out.= "\" >\n";
		}
		return $out;
	}

	/**
	* function get_tag_text
	* 
	* gets the text for a tag based on the given tag slug
	*
	* @param $tag string containing the tag slug
	* @return string the text associated with the slug
	*/
	private function get_tag_text( $tag )
	{
		return DB::get_value( 'select tag_text from ' . DB::table( 'tags' ) . ' where tag_slug= ?', array($tag) );
	}
	
	/**
	* function out_metatags
	* 
	*  gets the contents of the current buffer and replaces the contents
	* of the html title tag with content more suitable for SEO purposes
	* based on the type of page being displayed
	*
	* @param $theme Theme object being displayed
	* @return string the contents of the modified buffer
	*/
	public function dotags( $page )
	{
		$months= array('01'=>'January', '02'=>'February', '03'=>'March', '04'=>'April', '05'=>'May', '06'=>'June', '07'=>'July', '08'=>'August', '09'=>'September', '10'=>'October', '11'=>'November', '12'=>'December');
		$out= '';
		
		$matched_rule= URL::get_matched_rule();
		if (is_object( $matched_rule ) ) {
			$rule= $matched_rule->name;
			switch( $rule ) {
				case 'display_entry':
				case 'display_page':
					if( strlen( $this->theme->post->info->html_title ) ) {
						$out= $this->theme->post->info->html_title;
					}
					else {
						$out= $this->theme->post->title;
					}
					$out.= ' - ' . Options::get( 'title' );
					break;
				case 'display_entries_by_date':
					$out= 'Archive for ';
					if( isset($this->theme->day) ) {
						$out.= $this->theme->day . ' ';
					}
					if( isset($this->theme->month) ) {
						$out.= $months[$this->theme->month] . ' ';
					}
					if (isset( $this->theme->year) ) {
						$out.= $this->theme->year . ' ';
					}
					$out.= ' - ' . Options::get( 'title' );
					break;
				case 'display_entries_by_tag':
					$out= $this->get_tag_text(Controller::get_var( 'tag' ) ) . ' Archive';
					$out.= ' - ' . Options::get( 'title' );
					break;
				case 'display_search':
					$out= 'Search Results for ' . $this->theme->criteria ;
					$out.= ' - ' . Options::get( 'title' );
					break;
				case 'display_404':
					$out= 'Page Not Found';
					$out.= ' - ' . Options::get( 'title' );
					break;
				case 'display_home':
					$out= Options::get( 'title' ) . ' - ' . Options::get( 'tagline' );
					break;
				default:
					$out= Options::get( 'title' ) . ' - ' . Options::get( 'tagline' );
			}

			$out= htmlspecialchars( $out, ENT_COMPAT, 'UTF-8' );
			$out= stripslashes( $out );
			$keys= $this->get_keywords();
			$desc= $this->get_description();
			$robots= $this->get_robots();
			
			if( strpos( $page, '<title>' ) !== false ) {
				$page= preg_replace("%<title\b[^>]*>(.*?)</title>%is", "<title>$out</title>\n{$keys}{$desc}{$robots}", $page);
			}
			else {
				$page= preg_replace("%<head>%is", "<head>\n<title>$out</title>\n{$keys}{$desc}{$robots}", $page);
			}
		}

		return $page;
	}

}

?>