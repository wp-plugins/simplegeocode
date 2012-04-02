=== simpleGeocode ===
Contributors: matthewrosenberg 
Tags: google maps, google maps API V3, maps, geocode, marker, widget, shortcode, geocode, geocoder
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.0
License: GPLv2 or later

Type in an address and have simpleGeocode place a marker on the map. 

== Description ==

**Requires PHP5**

simpleGeocode was built with speed and ease-of-use as its core. This plugin makes it easy to give each post a postal address and build a map of those posts. Each marker automatically inherits the post title, excerpt and permalink so readers can easily navigate to the full post. This version includes limited support for directions. This map can be placed on a page through either a widget or a shortcode. 

== Installation ==

1. Upload the `simpleGeocode` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enter an address for a post.
1. Place a widget or use the shortcode [simpleGeocode] on a page.

== Frequently Asked Questions ==

**Q:** How to I use the simpleGeocode shortcode?

**A:** The simpleGeocode shortcode currently accepts two arguments, "height" and "div". The shortcode defaults to 300px in height and a div id of "simple_geocode". If you want multiple maps on a page, be sure to change the name of the DIV. 
More options may be added in future releases. 

EX: [simpleGeocode height="400px" div="my_new_map"]



**Q:** How to I use the simpleGeocode widget?

**A:** The simpleGeocode widget has three options, "title", "height", and "global". 

Title: The title is displayed above the widget and is also used to create the DIV ID. A widget with the title of "Bar Locations" has a DIV ID of  "simple_geocode_bar_locations". TO have more than one map on a page, make sure each widget has a different title. 

Height: Height defaults to 300px but any value can be entered. Pixels are probably the best method but percents and ems are also acceptable but have not been tested. 

Global: If this widget is displayed on a post page the widget defaults to showing the marker for that post if one exists. If a marker doesn't exist, it shows all post markers. You can override that behavior by clciking the "Global" option. This option forces the widget to always show all available markers. 


== Screenshots ==
1. Admin Map
1. Admin Map with address
1. Widget Admin


== Upgrade Notice ==

Be sure to deactivate the plugin before overwriting the plugin files. 


== Changelog ==

= 1.0 =
* Uses Google Maps API V3
* Introduces a shortcode for inclusion on pages.
* Allows multiple maps per page.
* Limited support for directions.



