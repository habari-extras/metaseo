Plugin: Meta SEO
URL: http://habariproject.org
Version: 0.2
Author: Habari Project

Purpose 

Meta SEO performs several search engine optimization tasks for you. It will:
	
1.Generate a description meta tag and injects it into the head of a page. Descriptions are created for your  home page, individual post 	pages, and static pages.
2. Generate a keywords meta tag and inject it into the head of a page. For the home page, it will use the first 50 tags from your site. For individual post and static pages, it will use the tags you tagged the post with. For tag archives, it will use the archive's tag.
3. Generate a title more suitable for search engines than the standard blog title. For individual post and static pages, it will add the option to enter your own title. If you do so, that title will be used to create the html title for the page. If you don't the title of the entry will be used to generate the html title for the page. Archive and search pages will receive a title appropriate to the type of archive or the search performed.
4. Generate a robots meta tag. Duplicate content can be a problem for search engines. The robots tag tells search engines whether or not to index a page, and whether to follow links on the page. By default, Meta SEO has all links followed, but only your home page and individual post and static pages indexed.

Requirements 

The theme must call $theme->header() in its head template

Installation

1. Copy the plugin directory into your user/plugins directory.
2. Go to the plugins page of your Habari admin panel.
3. Click on the Activate button for Meta SEO.

Configuration

1. Use the configuration screen to set the description you wish to use for your home page. This defaults to your blog's tagline.

Uninstallation

1. Got to the plugins page of your Habari admin panel.
2. Click on the Deactivate button.
3. Delete the Meta SEO directory from you user/plugins directory.

Cleanup

1. The plugin places the description to use for your home page in the Options table. The entry is named MetaSEO:home_desc. You can safely delete this entry when you are uninstalling the plugin.

Changelog

Version 0.21

Fixed: Output buffering was changed in Habari, breaking the buffering Meta SEO used. 

Version 0.2 - Initial release