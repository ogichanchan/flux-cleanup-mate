=== Flux Cleanup Mate ===
Contributors: ogichanchan
Tags: wordpress, plugin, utility, cleanup, performance, admin, speed, optimization
Requires at least: 6.2
Tested up to: 6.5.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Flux Cleanup Mate is a simple and efficient PHP-only WordPress utility designed to streamline and optimize your site by removing unnecessary elements and controlling core WordPress features. This plugin acts as a "mate" to help you declutter your WordPress installation, improving performance and security.

Key features include:
*   **Remove Unnecessary Links & Tags**: Disable RSD and Windows Live Writer manifest links, shortlinks, WordPress generator tag, RSS feed links, and REST API discovery links from the HTML head.
*   **Disable Core Features**: Turn off WordPress emojis, oEmbed functionality, and XML-RPC to reduce requests and potential attack vectors.
*   **Script Optimization**: Prevent `comment-reply.js` from loading site-wide (except where needed) and remove `jQuery Migrate` from the frontend.
*   **Heartbeat API Control**: Disable the Heartbeat API on the frontend for non-logged-in/non-editor users, and increase its interval in the admin area to reduce server load.
*   **Admin Bar Management**: Hide the WordPress admin bar on the frontend for all users except administrators.
*   **Post Revisions Control**: Limit the number of post revisions stored in your database, or disable them entirely, to keep your database lean.
*   **Dashboard Widget Removal**: Choose to remove various default dashboard widgets (e.g., At a Glance, Activity, Quick Draft, Site Health Status) to simplify the admin interface.

Flux Cleanup Mate offers a straightforward settings page where you can toggle these optimizations on or off, providing granular control over your WordPress cleanup process.

This plugin is open source. Report bugs at: https://github.com/ogichanchan/flux-cleanup-mate

== Installation ==
1. Upload the `flux-cleanup-mate` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to 'Settings' > 'Flux Cleanup Mate' to configure the cleanup options.

== Changelog ==
= 1.0.0 =
* Initial release.