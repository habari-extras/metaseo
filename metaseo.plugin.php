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
	const VERSION= '0.31';
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
	* set priority to a number lower than that used by most plugins 
	* to ensure it is the first one called so it doesn't interfere with 
	* other plugins calling theme_header()
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
		$home_keys= array();
		$tags= Tags::get();
		foreach( $tags as $tag ) {
			// limit to the first 50 tags to prevent keyword stuffing
			if( count( $home_keys ) < 50 ) {
				$home_keys[]= htmlspecialchars( strip_tags( $tag->tag ), ENT_COMPAT, 'UTF-8' );
			}
			else {
				break;
			}
		}
		return array(
			'home_desc' => htmlspecialchars( strip_tags( Options::get( 'tagline' ) ), ENT_COMPAT, 'UTF-8' ),
			'home_keywords' => $home_keys,
			'home_index' => true,
			'home_follow' => true,
			'posts_index' => true,
			'posts_follow' => true,
			'archives_index' => false,
			'archives_follow' => true,
			);
	}

	public function action_admin_header( $theme ) {
		$vars= Controller::get_handler_vars();
		if ($theme->admin_page == 'plugins' && isset( $vars['configure'] ) && $vars['configure'] === $this->plugin_id ) {
			Stack::add('admin_stylesheet', array($this->get_url() . '/metaseo.css', 'screen'));
		}
	}

	/**
	* function action_plugin_activation
	*
	*if the file being passed in is this file, sets the default options
	*
	* @param $file string name of the file 
	*/
	public function action_plugin_activation( $file )
	{
		if ( realpath( $file ) == __FILE__ ) {
			foreach ( self::default_options() as $name => $value ) {
				if( !(Options::get( self::OPTION_NAME . ':' . $name ) ) ) {
					Options::set( self::OPTION_NAME . ':' . $name, $value );
				}
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
	*/
	public function action_plugin_ui( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			switch ( $action ) {
				case _t('Configure' ) :
					$ui= new FormUI( self::OPTION_NAME );
					// Add a text control for the home page description and textmultis for the home page keywords
					$home_desc= $ui->add( 'textarea', 'home_desc', _t('Description: ' ) );
					$home_keywords= $ui->add( 'textmulti', 'home_keywords', _t( 'Keywords: ' ) );
					$ui->add( 'fieldset', _t( 'HomePage' ), array( $home_desc, $home_keywords ) );
					
					// Add checkboxes for the indexing and link following options
					$home_index= $ui->add( 'checkbox', 'home_index', _t( 'Index Home Page') );
					$home_follow= $ui->add( 'checkbox', 'home_follow', _t( 'Follow Home Page Links' ) );
					$posts_index= $ui->add( 'checkbox', 'posts_index', _t( 'Index Posts' ) );
					$posts_follow= $ui->add( 'checkbox', 'posts_follow', _t( 'Follow Post Links' ) );
					$archives_index= $ui->add( 'checkbox', 'archives_index', _t( 'Index Archives' ) );
					$archives_follow= $ui->add( 'checkbox', 'archives_follow', _t( 'Follow Archive Links' ) );
					$ui->add( 'fieldset', _t( 'Robots' ), array( $home_index, $home_follow, $posts_index, $posts_follow, $archives_index, $archives_follow ) );
					
					$ui->set_option( 'show_form_on_success', false );
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
	* function action_post_update_before
	*
	* called whenever a post is updated or published . If a new html title,
	* meta description, or meta keywords are entered on the publish page, 
	* sove them into the postinfo table. If any of these are empty, remove
	* their entry from the postinfo table if it exists.
	*
	* @param $post the Post object being updated
	* @return nothing
	*/
	public function action_post_update_before( $post )
	{
		$vars= Controller::get_handler_vars();
		if( $vars['content_type'] == Post::type( 'entry' ) || $vars['content_type'] == Post::type( 'page' ) ) {
			if( strlen( $vars['html_title'] ) ) {
				$post->info->html_title= htmlspecialchars( strip_tags( $vars['html_title'] ), ENT_COMPAT, 'UTF-8' );
			}
			else {
				$post->info->__unset( 'html_title' );
			}
			if( strlen( $vars['metaseo_desc'] ) ) {
				$post->info->metaseo_desc= htmlspecialchars( Utils::truncate( strip_tags( $vars['metaseo_desc'] ), 200, false ), ENT_COMPAT, 'UTF-8' );
			}
			else {
				$post->info->__unset( 'metaseo_desc' );
			}
			if( strlen( $vars['metaseo_keywords'] ) ) {
				$post->info->metaseo_keywords= htmlspecialchars( strip_tags( $vars['metaseo_keywords'] ), ENT_COMPAT, 'UTF-8' );
			}
			else {
				$post->info->__unset( 'metaseo_keywords' );
			}
		}
	}

	/**
	* function filter_publish_controls
	*
	* adds controls to the publish page so we can set the html title we want on a
	* page or entry if we don't want it to be the same as the actual page or post title
	*
	* @return array  containing the controls to be on the publish page
	*/
	public function filter_publish_controls ($controls, $post) {
		$vars= Controller::get_handler_vars();
				
		if( $vars['content_type'] == Post::type('entry') || $vars['content_type'] == Post::type('page') ) {
			$output= '';
			
			$output .= '<div class="text container">';
			$output .= '<p class="column span-2"><label for="html_title">Page Title:</label></p>';
			$output .= '<p class="column span-17 last"><input style="width: 400px;" type="text" id="page-title" name="html_title" value="';
			if( strlen( $post->info->html_title ) ) {
				$output .= $post->info->html_title;
			}
			$output .= '" /></p>';

			$output .= '<p class="column span-2"><label for="metaseo_keywords">Keywords:</label></p>';
			$output .= '<p class="column span-17 last"><input style="width: 400px;" type="text" id="metaseo_keywords" name="metaseo_keywords" value="';
			if( strlen( $post->info->metaseo_keywords ) ) {
				$output .= $post->info->metaseo_keywords;
			}
			$output .= '" /></p>';

			$output .= '<p class="column span-2"><label for="meta_desc">Description:</label></p>';
			$output .= '<p class="column span-17 last"><textarea id="metaseo_desc" name="metaseo_desc" style="height: 100px; width: 400px;" >';
			if( strlen( $post->info->metaseo_desc ) ) {
				$output .= $post->info->metaseo_desc;
			}
			$output .= '</textarea></p>';
			$output .= '</div>';
			$controls['Meta SEO']= $output;
		}
		
		return $controls;
	}

	/**
	* function filter_final_output
	*
	* this filter is called before the display of any page, so it is used 
	* to make any final changes to the output before it is sent to the browser
	*
	* @param $buffer string the page being sent to the browser
	* @return  string the modified page
	*/
	public function filter_final_output( $buffer )
	{
		$seo_title= $this->get_title();
		if( strlen( $seo_title ) ) {
			if( strpos( $buffer, '<title>' ) !== false ) {
				$buffer= preg_replace("%<title\b[^>]*>(.*?)</title>%is", "<title>{$seo_title}</title>", $buffer );
			}
			else {
				$buffer= preg_replace("%<head>%is", "<head>\n<title>{$seo_title}</title>", $buffer );
			}
		}
		return $buffer;
	}

	/**
	* function theme_header
	*
	* called to added output to the head of a page before it is being displayed.
	* Here it is being used to insert the keywords, description, and robot meta tags
	* into the page head.
	* 
	* @param $theme Theme object being displayed
	* @return string the keywords, description, and robots meta tags
	*/
	public function theme_header($theme)
	{
		$this->theme= $theme;
		return $this->get_keywords() . $this->get_description() . $this->get_robots();
	}

	/**
	* function action_update_check
	*
	* Add update beacon support
	**/
	public function action_update_check()
	{
		Update::add( 'Meta SEO', 'DE6CFC70-1661-11DD-8BC9-25DB55D89593', $this->info->version );
	}

	/* function get_tag_text
	*
	 * gets the display text from a tag slug
	*
	* @param $tag the tag-slug you want the display text for
	* @return string the tag's display text
	*/
	public function get_tag_text( $tag ) {
		return DB::get_value( "select tag_text from {tags} where tag_slug= ?", array($tag) );
	}

	/* function get_description
	*
	 * This function creates the meta description tag  based on an excerpt of the post being displayed.
	 * Single entry - the excerpt for the individual entry
	 * Page - the excerpt for the page
	*
	* @return string the description meta tag
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
					$desc= Options::get( self::OPTION_NAME . ':home_desc' );
					break;
				case 'display_entry':
				case 'display_page':
					if( isset( $this->theme->post ) ) {
						if( strlen( $this->theme->post->info->metaseo_desc ) ) {
							$desc= $this->theme->post->info->metaseo_desc;
						}
						else {
							$desc= Utils::truncate( $this->theme->post->content, 200, false );
						}
					}
					break;
				default:
			}
		}
		if( strlen( $desc ) ) {
			$desc= str_replace( "\r\n", " ", $desc );
			$desc= str_replace( "\n", " ", $desc );
			$desc= htmlspecialchars( strip_tags( $desc ), ENT_COMPAT, 'UTF-8' );
			$out= "<meta name=\"description\" content=\"{$desc}\" >\n";
		}

		return $out;
	}

	/**
	 * function get_keywords
	 *
	 * This function creates the meta keywords tag based on the type of page being loaded.
	 * Single entry and single page - the tags for the individual entry
	 * Home - the keywords entered in the options
	 * Tag page - the tag for which the page was generated
	 *
	 * @return string the keywords meta tag
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
					if( isset( $this->theme->post ) ) {
						if( strlen( $this->theme->post->info->metaseo_keywords ) ) {
							$keywords= $this->theme->post->info->metaseo_keywords;
						}
						else if( count( $this->theme->post->tags ) > 0 ) {
							$keywords= implode( ', ', $this->theme->post->tags );
						}
					}
					break;
				case 'display_entries_by_tag':
					$keywords= Controller::get_var( 'tag' );
					break;
				case 'display_home':
					if( count( Options::get( self::OPTION_NAME . ':home_keywords' ) ) ) {
						$keywords= implode( ', ', Options::get( self::OPTION_NAME . ':home_keywords' ) );
					}
					break;
				default:
			}
		}
		$keywords= htmlspecialchars( strip_tags( $keywords ), ENT_COMPAT, 'UTF-8' );
		if( strlen( $keywords ) ) {
			$out= "<meta name=\"keywords\" content=\"{$keywords}\">\n";
		}
		return $out;
	}

	/**
	 * function get_robots
	 *
	 * creates the robots tag based on the type of page being loaded.
	 *
	 * @return string the robots meta tag
	*/
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
					if( Options::get(self::OPTION_NAME . ':posts_index' ) ) {
						$robots= 'index';
					}
					else {
						$robots= 'noindex';
					}
					if( Options::get(self::OPTION_NAME . ':posts_follow' ) ) {
						$robots .= ', follow';
					}
					else {
						$robots .= ', nofollow';
					}
					break;
				case 'display_home':
					if( Options::get(self::OPTION_NAME . ':home_index' ) ) {
						$robots= 'index';
					}
					else {
						$robots= 'noindex';
					}
					if( Options::get(self::OPTION_NAME . ':home_follow' ) ) {
						$robots .= ', follow';
					}
					else {
						$robots .= ', nofollow';
					}
					break;
				case 'display_entries_by_tag':
				case 'display_entries_by_date':
				case 'display_entries':
					if( Options::get(self::OPTION_NAME . ':archives_index' ) ) {
						$robots= 'index';
					}
					else {
						$robots= 'noindex';
					}
					if( Options::get(self::OPTION_NAME . ':archives_follow' ) ) {
						$robots .= ', follow';
					}
					else {
						$robots .= ', nofollow';
					}
					break;
				default:
					$robots= 'noindex, follow';
					break;
			}
		}
		if( strlen( $robots ) ) {
			$out= "<meta name=\"robots\" content=\"{$robots}\" >\n";
		}
		return $out;
	}

	/**
	* function get_title
	* 
	* creates the html title for the page being displayed
	*
	* @return string the html title for the page
	*/
	private function get_title()
	{
		$months= array('01'=>'January', '02'=>'February', '03'=>'March', '04'=>'April', '05'=>'May', '06'=>'June', '07'=>'July', '08'=>'August', '09'=>'September', '10'=>'October', '11'=>'November', '12'=>'December');
		$out= '';

		$matched_rule= URL::get_matched_rule();
		if (is_object( $matched_rule ) ) {
			$rule= $matched_rule->name;
			switch( $rule ) {
				case 'display_home':
				case 'display_entries':
					$out= Options::get( 'title' ) . ' - ' . Options::get( 'tagline' );
					break;
				case 'display_entries_by_date':
					$out= 'Archive for ';
					if( isset($this->theme->day) ) {
						$out .= $this->theme->day . ' ';
					}
					if( isset($this->theme->month) ) {
						$out .= $months[$this->theme->month] . ' ';
					}
					if (isset( $this->theme->year) ) {
						$out .= $this->theme->year . ' ';
					}
					$out .= ' - ' . Options::get( 'title' );
					break;
				case 'display_entries_by_tag':
					$out= $this->get_tag_text(Controller::get_var( 'tag' ) ) . ' Archive';
					$out .= ' - ' . Options::get( 'title' );
					break;
				case 'display_entry':
				case 'display_page':
					if( strlen( $this->theme->post->info->html_title ) ) {
						$out= $this->theme->post->info->html_title;
					}
					else {
						$out= $this->theme->post->title;
					}
					$out .= ' - ' . Options::get( 'title' );
					break;
				case 'display_search':
					$out= 'Search Results for ' . $this->theme->criteria ;
					$out .= ' - ' . Options::get( 'title' );
					break;
				case 'display_404':
					$out= 'Page Not Found';
					$out .= ' - ' . Options::get( 'title' );
					break;
			}

			if( strlen( $out ) ) {
				$out= htmlspecialchars( strip_tags( $out ), ENT_COMPAT, 'UTF-8' );
				$out= stripslashes( $out );
			}
		}

		return $out;
	}

}

?>