=== Uniplace client ===
Contributors: idevtech
Donate link: 
Tags: 
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag:  trunk
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin for working with the Uniplace links broker.

== Description ==

It allows you to synchronize your site with Uniplace link broker.

== Installation ==

1. Upload plugin to the "/wp-content/plugins/" directory.
2. After plugin installation, go to the menu "Plugins"- "Installed Plugins" and click on the "Activate" link under the name of Uniplace plugin.
3. After plugin activation, go to the plugin settings page and add:
  a. Site hash. You will find it in Uniplace under the URL of your website.
  b. Encoding for data displaying (on default its UTF-8)
4. After setting up, please, add "Uniplace Widget" in the sidebar of your active theme on "Appearance" - "Widgets". 
If your active theme has no sidebar, you can add the following code to your template. 
For example in footer.php before closing tag </body>:
     <?php echo do_shortcode( "[uniplace_links]" ); ?>

Also, you can  add links to any article using the following shortcode:
     [uniplace_links]

== Frequently asked questions ==



== Screenshots ==



== Changelog ==



== Upgrade notice ==



== Arbitrary section 1 ==

