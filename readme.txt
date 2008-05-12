Plugin: Meta SEO
URL: http://habariproject.org
Version: 0.31
Author: Habari Project

Purpose 

Meta SEO performs several search engine optimization tasks for you. It will:
	
1.Generate a description meta tag and inject it into the head of a page. Descriptions are created for your home page, individual post pages, and static pages. For your home page, it will use the description you entered on the plugin's configuration page. For entry and static pages, it adds the option to create a description on the publish page. If you enter your own description, this will be used to create the description meta tag. If you don't the first part of the post will be used to create the tag.
2. Generate a keywords meta tag and inject it into the head of a page. For the home page, it will use the tags you have selected in the plugin configuration. For entries and static pages, it adds the option to the publish page to create your own keywords. If you do so, it will use this to generate the tag. If you don't, it will use the tags you tagged the post with. For tag archives, it will use the archive's tag.
3. Generate a title more suitable for search engines than the standard blog title. For individual post and static pages, it will add the option to the publish page to enter your own title. If you do so, that title will be used to create the html title for the page. If you don't, the title of the entry will be used to generate the html title for the page. Archive and search pages will receive a title appropriate to the type of archive or the search performed.
4. Generate a robots meta tag. Duplicate content can lead to lower search engine rankings. The robots tag tells search engines whether or not to index a page, and whether to follow links on the page. By default, Meta SEO has all links followed, but only your home page and individual post and static pages indexed.

Requirements 

The theme must call $theme->header() in its head template.

Installation

1. Copy the plugin directory into your user/plugins directory or the site's plugins directory.
2. Go to the plugins page of your Habari admin panel.
3. Click on the Activate button for Meta SEO.

Usage

If your theme, usually in the header template, contains a description, keywords, or robots meta tag, back up the template that contains them, then delete them from your working copy of the template. 

Configuration for Meta SEO is performed by clicking the Configure button in its listing on your plugins page. The configuration screen has two main sections - the home page options, and the robots tag options.

1. Home Page - Use the configuration screen to set the description and keywords you wish to use for your home page. These default to your blog's tagline and the first 50 tags you have in your blog.

2. Robots Tag - Meta SEO sees your site as having four main types of pages: the home page, individual posts and static pages, archive pages, and other pages such as search results and the 404 page. The robots tag for the other pages is always set to not index the page, but to follow the links on the page. Use the configuration screen to tell Meta SEO how to create the robots tag for the first three type of pages. If the Index checkbox is marked, the robots tag will contain 'index'. Otherwise, it will contain 'noindex'. If the Follow checkbox is marked, the robots tag will contain 'follow'. Otherwise, it will contain 'nofollow'.

3. After you're done making your configuration changes, click the 'Save' button at the bottom of the form to save the changes. The main part of the form will close. Click the 'Close' button to completely close the form.

4. Individual Post and Static Pages - Meta SEO adds a new form to Habari's publish page that is accessed by clicking the Meta SEO button. When the form opens, you will have options to enter a new title, keywords, and description for the entry. If you do so, these will be used to generate the meta tags for the entry. If you leave any empty, MetaSEO will generate the tags from the entry's regular title, tags, and the beginning of its content.

Uninstallation

1. Got to the plugins page of your Habari admin panel.
2. Click on the Deactivate button.
3. Delete the Meta SEO directory from your user/plugins directory.

Cleanup

1. The plugin places several items in your Options table. All are preceded by the word MetaSEO. You can safely delete these entries after you uninstall the plugin.
2. The plugin stores items in the postinfo table whenever you create a custom title, keywords, or description. These have the names 'html_title', 'metaseo_desc', and 'metaseo_keywords'. These entries can be safely deleted.

Changelog

Version 0.31
Fixed: Post titles in atom feed were all replaced by the site name and tagline.
Fixed: Style changes in the configuration form to compensate for the new admin interface.

Version 0.3
Change: Made the keywords to use on the home page an option.
Change: Made indexing and following links an option for the home page, individual posts and static pages, and archive pages.
Change: Added an entry box for the entry or page keywords on the publish page.
Change: Added an entry box for the entry or page description on the publish page.
Change: Added a style sheet for the configuration page.
Fixed: Code cleanup.

Version 0.2 - Initial release