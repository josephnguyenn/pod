=== Tag Manager - Header, Body And Footer ===
Contributors: YYDevelopment
Tags: Tag Manager, Google Analytics, Facebook Pixel, Add Codes, Insert Code
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 3.6.2
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.yydevelopment.com/coffee-break/?plugin=tag-manager-header-body-footer

Simple plugin that allow you add head, body and footer codes for google tag manager, analytics & facebook pixel codes.

== Description ==

The tag manager is a simple plugin that will allow you to insert/inject code into your website in the header section, after the start body tag and in the footer above the ending body tag.

You can use this plugin to add code and snippets into your website for services like Google Tag Manager, Google Analytics, Facebook Pixel, Google Adsense and much more...

This plugin will also allow you to insert meta tags to your website header and even add phone/WhatsApp button or anything using custom HTML code with footer code injection.

#### Tag Manager - Injection Location And Features

* Head section - between the top page head tags
* Body section - below the body start tag
* Footer section - at the end of the page above the close body tag
* The ability to insert more than one code for each section on the site
* The ability to exclude pages by user role (admin or logged in users)
* The ability to exclude/include pages code by page id

#### Supported Services And Platforms

* Google Tag Manager
* Google Analytics
* Google Adsense
* Facebook Pixels
* Yandex Metrika
* Any other website that require code injection.

#### Tags LazyLoad Support

* GTM Lazy Load
* Google Analytics Lazy Load
* Facebook Pixel Load
* Yandex Metrika Lazy Load
* Lazy Load For Custom Javascript Codes

#### Supported Plugin Actions

The actions below will allow you to directly load the code on external files that uses wordpress functions. With these functions you will be able to add header, footer and body codes for web elements outside your wordpres site.

* yydev_tag_manager_head()
* yydev_tag_manager_below_body()
* yydev_tag_manager_before_closing_body()

#### About the author & license

This plugin was brought to you for free by [YYDevelopment](https://www.yydevelopment.com/) under GPLv2 license.

The plugin is 100% free and we intend to keep it that way in the future as well. You are free to use this plugin and all our other [free wordpress plugins](https://www.yydevelopment.com/yydevelopment-wordpress-plugins/) for your projects, your client's projects or for anything else you need.

If this plugin was helpful for you please share it online and if you get a chance to give it a [positive review](https://wordpress.org/plugins/tag-manager-header-body-footer/#reviews) we will appreciate that.

If have any problems or questions regarding our tag manager – header, body and footer plugin [submit a ticket](https://wordpress.org/support/plugin/tag-manager-header-body-footer/) and we will be happy to help.

By the way, we are based in Israel so we welcome you to visit our Hebrew site as well [YYDevelopment Israel](https://www.yydevelopment.co.il/) if you are fellow Israeli.

#### Help support us with a coffee donation

Don’t you just hate it when you download a plugin and you find out that in order to use it you have to buy a pro version? 

Even bigger problem is when you use a plugin and then just out of the blue the developer decides to add a pro version and he either changes the way the plugin works or he converts some of the free functions to paid ones.

We sure did hate that and a few years back we decided to start creating some of the plugins ourselves and we decided to share them all with the WordPress community **100% FREE**. 

Nowadays we have more than 15 plugins and you can download and use them all for free by [Clicking Here](https://wordpress.org/plugins/search/yydevelopment/).

If you liked this plugin and you want to help support our cause, [buy us a coffee](https://www.yydevelopment.com/coffee-break/?plugin=tag-manager-header-body-footer). Studies show that coffee helps with creating WordPress plugins.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/tag-manager-header-body-footer` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. On your admin panel go into "Tag Manager" option and add your code.

== Frequently Asked Questions ==

= Do I need to take any action to make this plugin work? =

All you need to do it to install and activate the tag manager plugin and you will be able to see it on the site wordpress mene.

= How do I add header code into the site =

To add code inside your website head tags go inside the tag manager plugin page and insert the code under the "insert head tags code below" title.

= How do I add code below the body tag in the site =

To add code below the body tag simply go into to tag manager plugin page and insert the code under the "Insert below body tags code below" title.

= How to add code at the bottom/footer of the page above the end body tag =

To add code  at the end of the document above end body tag insert your code under "Insert footer tags code below, code above body" title.

== Screenshots ==

1. The wordpress tag manager plugin admin panel
2. The wordpress tag manager exclude settings
3. The header code placment in the page itself
4. The body snippet code code placment in the page itself
5. The footer code in the page above end body

== Changelog ==

= 1.0.0 =
* Tag Manager - Header, Body And Footer plugin launch

= 1.1.5 =
* Fixing php alerts 

= 1.1.6 =
* Fixing "Cannot modify header information" error

= 1.1.8 =
* Fixing PHP warnings

= 1.1.9 =
* Plugin name change

= 1.2.1 =
* Added nonce to plugin
* Added global rating message block

= 1.3.0, 1.3.2 =
* Added language support

= 1.4.0 =
* Improved content
* Improved page layout

= 1.4.1 =
* Fixing the checkbox menu working the other way around

= 1.5.0 =
* adding the option to load head code with yydev_tag_manager_head()
* adding the option to load below body code with yydev_tag_manager_below_body()
* adding the option to load above end body code with yydev_tag_manager_before_closing_body()
* added new way to load below body code without ob_buffer

= 1.5.1 =
* Adding line breaks to output code

= 1.5.2 =
* Remove ob_start from back end

= 1.5.3 =
* Fixing "count(): Parameter must be an array or an object that implements Countable" error

= 2.0.0 =
* Added the option to add more than one tag

= 2.0.1 =
* Escape the data better

= 2.1.0 =
* Added donation button to the plugin description
* Added support for php7,4

= 2.3.0 =
* Added the option to exclude pages by user role (admin or logged in users)
* Added the option to exclude code by pages id

= 2.3.1 =
* Trying to deal with all to undefined function wp_get_current_user() error

= 3.0.0 =
* Added lazy load options for google analytics and yandex metrica

= 3.1.4 =
* Fixed error on upgrade to php 8.1
* Changed including files method
* Added facebook pixel lazy loading
* Added lazy loading timer
* Added exclude lazy loading for pages

= 3.2.0 =
* Added the ability to insert custom javascript code that will be in delay

= 3.3.0 =
* Added the ability to stop custom js on elementor editor 

= 3.3.1 =
* Fixing remove image not showing up 

= 3.4.0 =
* Added the ability to add more than one lazyload script
* Changed the lazyload by action to be javascript without jquery

= 3.4.1 =
* Fixing problem with tag manager code not working well without body open selected

= 3.6.0 =
* Added the option to add notes and storage unwanted tags data

= 3.6.1 =
* Fixed warning on php 8.0

= 3.6.2 =
* Fixed broken access control vulnerability 

== Upgrade Notice ==

= 1.1.5 =
New version of the plugin is available and you should upgrade yours