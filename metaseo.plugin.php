<?php

	namespace Habari;

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
 */

class MetaSeo extends Plugin
{
	/**
	 * @var $theme Theme object that is currently being used for display
	 */
	private $theme;

	public function action_init()
	{
		$this->load_text_domain( 'metaseo' );
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
		return array( 'theme_header' => 6 );
	}

	/**
	 * function default_options
	 *
	 * returns defaults for the plugin
	 * @return array default options array
	 */
	private static function default_options()
	{
		$home_keys = array();

		$tags = Tags::get_by_frequency( 50, 'entry' );

		foreach ( $tags as $tag ) {
			$home_keys[] = Utils::htmlspecialchars( strip_tags( $tag->term_display ) );
		}

		return array(
			'home_desc' => Utils::htmlspecialchars( strip_tags( Options::get( 'tagline' ) ) ),
			'home_keywords' => $home_keys,
			'home_index' => true,
			'home_follow' => true,
			'posts_index' => true,
			'posts_follow' => true,
			'archives_index' => false,
			'archives_follow' => true,
		);
	}

	/**
	 * Add appropriate CSS to this plugin's configuration form
	 */
	public function action_admin_header( $theme ) {
		$vars = Controller::get_handler_vars();
		if ( $theme->admin_page == 'plugins' && isset( $vars['configure'] ) && $vars['configure'] === $this->plugin_id ) {
			Stack::add( 'admin_stylesheet', array( $this->get_url() . '/metaseo.css', 'screen' ) );
		}
	}

	/**
	 * function action_plugin_activation
	 *
	 * if the file being passed in is this file, sets the default options
	 *
	 * @param $file string name of the file
	 */
	public function action_plugin_activation( $file )
	{
		if ( realpath( $file ) == __FILE__ ) {
			foreach ( self::default_options() as $name => $value ) {
				if ( !( Options::get( 'MetaSEO__' . $name ) ) ) {
					Options::set( 'MetaSEO__' . $name, $value );
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
			$actions['reload'] = _t( 'Re-Load Top Keywords', 'metaseo' );
		}
		return $actions;
	}

	public function configure()
	{
		$ui = new FormUI( 'MetaSEO' );
		$ui->append( FormControlFieldset::create( 'homepage' )->set_caption( _t( 'HomePage', 'metaseo' ) ) );
		
		$ui->homepage->append( FormControlLabel::wrap( _t( 'Description', 'metaseo' ), FormControlTextArea::create( 'home_desc', 'option:MetaSEO__home_desc' ) ) );
		$ui->homepage->append( FormControlLabel::wrap( _t( 'Keywords', 'metaseo' ), FormControlTextmulti::create( 'home_keywords', 'option:MetaSEO__home_keywords' ) ) );
		
		$ui->append( FormControlFieldset::create( 'robots' )->set_caption( _t( 'Robots', 'metaseo' ) ) );
		
		$ui->robots->append( FormControlLabel::wrap( _t( 'Index Home Page', 'metaseo' ), FormControlCheckbox::create( 'home_index', 'option:MetaSEO__home_index' ) ) );
		$ui->robots->append( FormControlLabel::wrap( _t( 'Follow Home Page Links', 'metaseo' ), FormControlCheckbox::create( 'home_follow', 'option:MetaSEO__home_follow' ) ) );
		$ui->robots->append( FormControlLabel::wrap( _t( 'Index Posts', 'metaseo' ), FormControlCheckbox::create( 'posts_index', 'option:MetaSEO__posts_index' ) ) );
		$ui->robots->append( FormControlLabel::wrap( _t( 'Follow Post Links', 'metaseo' ), FormControlCheckbox::create( 'posts_follow', 'option:MetaSEO__posts_follow' ) ) );
		$ui->robots->append( FormControlLabel::wrap( _t( 'Index Archives', 'metaseo' ), FormControlCheckbox::create( 'archives_index', 'option:MetaSEO__archives_index' ) ) );
		$ui->robots->append( FormControlLabel::wrap( _t( 'Follow Archive Links', 'metaseo' ), FormControlCheckbox::create( 'archives_follow', 'option:MetaSEO__archives_follow' ) ) );

		$ui->append( 'submit', 'save', _t( 'Save', 'metaseo' ) );
		$ui->out();
	}

	public function action_plugin_ui_reload( $plugin_id, $action )
	{
		// get the keywords
		$options = self::default_options();
		$keywords = $options['home_keywords'];

		Options::set( 'MetaSEO__home_keywords', $keywords );

		Session::notice( _t( 'Keywords have been reloaded!', 'metaseo' ) );
	}

	/**
	 * Add additional controls to the publish page tab
	 *
	 * @param FormUI $form The form that is used on the publish page
	 * @param Post $post The post being edited
	 */
	public function action_form_publish( $form, $post )
	{
		if ( $form->content_type->value == Post::type( 'entry' ) || $form->content_type->value == Post::type( 'page' ) ) {

			// pre-calculate our values
			$title = strlen( $post->info->html_title ) ? $post->info_html_title : '';
			$keywords = strlen( $post->info->metaseo_keywords ) ? $post->info->metaseo_keywords : '';
			$description = isset( $post->info->metaseo_desc ) ? $post->info->metseo_desc : '';

			$metaseo = $form->publish_controls->append( FormControlFieldset::create( 'metaseo' )->set_caption( _t( 'Meta SEO', 'metaseo' ) ));
			
			$metaseo->append( FormControlLabel::wrap( _t( 'Page Title', 'metaseo' ), FormControlText::create( 'html_title' )->set_value( $title ) ) );
			$metaseo->append( FormControlLabel::wrap( _t( 'Keywords', 'metaseo' ), FormControlText::create( 'keywords' )->set_value( $keywords ) ) );
			$metaseo->append( FormControlLabel::wrap( _t( 'Description', 'metaseo' ), FormControlTextArea::create( 'description' )->set_value( $description ) ) );
		}
	}


	/**
	 * Modify a post before it is updated
	 *
	 * Called whenever a post is about to be updated or published . If a new html title,
	 * meta description, or meta keywords are entered on the publish page,
	 * sove them into the postinfo table. If any of these are empty, remove
	 * their entry from the postinfo table if it exists.
	 *
	 * @param Post $post The post being saved, by reference
	 * @param FormUI $form The form that was submitted on the publish page
	 */
	public function action_publish_post( $post, $form )
	{
		if ( $post->content_type == Post::type( 'entry' ) || $post->content_type == Post::type( 'page' ) ) {
			if ( strlen( $form->metaseo->html_title->value ) ) {
				$post->info->html_title = Utils::htmlspecialchars( strip_tags( $form->metaseo->html_title->value ) );
			}
			else {
				$post->info->__unset( 'html_title' );
			}
			if ( strlen( $form->metaseo->description->value ) ) {
				$post->info->metaseo_desc = Utils::htmlspecialchars( Utils::truncate( strip_tags( $form->metaseo->description->value ), 200, false ) );
			}
			else {
				$post->info->__unset( 'metaseo_desc' );
			}
			if ( strlen( $form->metaseo->keywords->value ) ) {
				$post->info->metaseo_keywords = Utils::htmlspecialchars( strip_tags( $form->metaseo->keywords->value ) );
			}
			else {
				$post->info->__unset( 'metaseo_keywords' );
			}
		}

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
		$seo_title = $this->get_title();
		if ( strlen( $seo_title ) ) {
			if ( strpos( $buffer, '<title>' ) !== false ) {
				$buffer = preg_replace( "%<title\b[^>]*>(.*?)</title>%is", "<title>{$seo_title}</title>", $buffer );
			}
			else {
				$buffer = preg_replace( "%</head>%is", "<title>{$seo_title}</title>\n</head>", $buffer );
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
	public function theme_header( $theme )
	{
		$this->theme = $theme;
		return $this->get_keywords() . $this->get_description() . $this->get_robots();
	}

	/** function get_description
	 *
	 * This function creates the meta description tag  based on an excerpt of the post being displayed.
	 * Single entry - the excerpt for the individual entry
	 * Page - the excerpt for the page
	 *
	 * @return string the description meta tag
	 */
	private function get_description()
	{
		$out = '';
		$desc = '';

		$matched_rule = URL::get_matched_rule();

		if ( is_object( $matched_rule ) ) {
			$rule = $matched_rule->name;
			switch ( $rule) {
				case 'display_home':
					$desc = Options::get( 'MetaSEO__home_desc' );
					break;
				case 'display_entry':
				case 'display_page':
					if ( isset( $this->theme->post ) ) {
						if ( strlen( $this->theme->post->info->metaseo_desc ) ) {
							$desc = $this->theme->post->info->metaseo_desc;
						}
						else {
							$desc = Utils::truncate( $this->theme->post->content, 200, false );
						}
					}
					break;
				default:
			}
		}
		if ( strlen( $desc ) ) {
			$desc = str_replace( "\r\n", " ", $desc );
			$desc = str_replace( "\n", " ", $desc );
			$desc = Utils::htmlspecialchars( strip_tags( $desc ) );
			$desc = strip_tags( $desc );
			$out = "<meta name=\"description\" content=\"{$desc}\" >\n";
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
		$out = '';
		$keywords = '';

		$matched_rule = URL::get_matched_rule();

		if ( is_object( $matched_rule ) ) {
			$rule = $matched_rule->name;
			switch ( $rule) {
				case 'display_entry':
				case 'display_page':
					if ( isset( $this->theme->post ) ) {
						if ( strlen( $this->theme->post->info->metaseo_keywords ) ) {
							$keywords = $this->theme->post->info->metaseo_keywords;
						}
						else if ( count( $this->theme->post->tags ) > 0 ) {
							$keywords = implode( ', ', (array)$this->theme->post->tags );
						}
					}
					break;
				case 'display_entries_by_tag':
					$keywords = Controller::get_var( 'tag' );
					break;
				case 'display_home':
					if ( count( Options::get( 'MetaSEO__home_keywords' ) ) ) {
						$keywords = implode( ', ', Options::get( 'MetaSEO__home_keywords' ) );
					}
					break;
				default:
			}
		}
		$keywords = Utils::htmlspecialchars( strip_tags( $keywords ) );
		if ( strlen( $keywords ) ) {
			$out = "<meta name=\"keywords\" content=\"{$keywords}\">\n";
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
		$out = '';
		$robots = '';

		$matched_rule = URL::get_matched_rule();

		if ( is_object( $matched_rule ) ) {
			$rule = $matched_rule->name;
			switch ( $rule) {
				case 'display_entry':
				case 'display_page':
					if ( Options::get( 'MetaSEO__posts_index' ) ) {
						$robots = 'index';
					}
					else {
						$robots = 'noindex';
					}
					if ( Options::get( 'MetaSEO__posts_follow' ) ) {
						$robots .= ', follow';
					}
					else {
						$robots .= ', nofollow';
					}
					break;
				case 'display_home':
					if ( Options::get( 'MetaSEO__home_index' ) ) {
						$robots = 'index';
					}
					else {
						$robots = 'noindex';
					}
					if ( Options::get( 'MetaSEO__home_follow' ) ) {
						$robots .= ', follow';
					}
					else {
						$robots .= ', nofollow';
					}
					break;
				case 'display_entries_by_tag':
				case 'display_entries_by_date':
				case 'display_entries':
					if ( Options::get( 'MetaSEO__archives_index' ) ) {
						$robots = 'index';
					}
					else {
						$robots = 'noindex';
					}
					if ( Options::get( 'MetaSEO__archives_follow' ) ) {
						$robots .= ', follow';
					}
					else {
						$robots .= ', nofollow';
					}
					break;
				default:
					$robots = 'noindex, follow';
					break;
			}
		}
		if ( strlen( $robots ) ) {
			$out = "<meta name=\"robots\" content=\"{$robots}\" >\n";
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
		$months = array(
				1 => _t( 'January', 'metaseo' ),
				_t( 'February', 'metaseo' ),
				_t( 'March', 'metaseo' ),
				_t( 'April', 'metaseo' ),
				_t( 'May', 'metaseo' ),
				_t( 'June', 'metaseo' ),
				_t( 'July', 'metaseo' ),
				_t( 'August', 'metaseo' ),
				_t( 'September', 'metaseo' ),
				_t( 'October', 'metaseo' ),
				_t( 'November', 'metaseo' ),
				_t( 'December', 'metaseo' ),
				);
		$out = '';

		$matched_rule = URL::get_matched_rule();
		if ( is_object( $matched_rule ) ) {
			$rule = $matched_rule->name;
			switch ( $rule ) {
				case 'display_home':
				case 'display_entries':
					$out = Options::get( 'title' );
					if ( Options::get( 'tagline' ) ) {
						$out .= ' - ' . Options::get( 'tagline' );
					}
					break;
				case 'display_entries_by_date':
					$out = 'Archive for ';
					if ( isset( $this->theme->day ) ) {
						$out .= $this->theme->day . ' ';
					}
					if ( isset( $this->theme->month ) ) {
						$out .= $months[$this->theme->month] . ' ';
					}
					if ( isset( $this->theme->year) ) {
						$out .= $this->theme->year . ' ';
					}
					$out .= ' - ' . Options::get( 'title' );
					break;
				case 'display_entries_by_tag':
					// parse the tags out of the URL, just like the handler does
					$tags = Tags::parse_url_tags( Controller::get_var( 'tag' ) );

					// build the pieces we'll use for text
					$include_tag_text = array();
					$exclude_tag_text = array();
					foreach ( $tags['include_tag'] as $include_tag ) {
						$include_tag_text[] = Tags::vocabulary()->get_term( $include_tag )->term_display;
					}

					foreach ( $tags['exclude_tag'] as $exclude_tag ) {
						$exclude_tag_text[] = Tags::vocabulary()->get_term( $exclude_tag )->term_display;
					}

					$out = Format::and_list( $include_tag_text );

					if ( !empty( $exclude_tag_text ) ) {
						$out .= ' but not ' . Format::and_list( $exclude_tag_text );
					}

					$out .= ' Archive - ' . Options::get( 'title' );
					break;
				case 'display_entry':
				case 'display_page':
					if ( strlen( $this->theme->post->info->html_title ) ) {
						$out = $this->theme->post->info->html_title;
					}
					else {
						$out = $this->theme->post->title;
					}
					$out .= ' - ' . Options::get( 'title' );
					break;
				case 'display_search':
					if ( isset( $_GET['criteria'] ) ) {
						$out = 'Search Results for ' . $_GET['criteria'] . ' - ' ;
					}
					$out .= Options::get( 'title' );
					break;
				case 'display_404':
					$out = 'Page Not Found';
					$out .= ' - ' . Options::get( 'title' );
					break;
			}

			if ( strlen( $out ) ) {
				$out = Utils::htmlspecialchars( strip_tags( $out ) );
				$out = stripslashes( $out );
			}
		}

		return $out;
	}

}
?>
