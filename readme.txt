=== Pay to Comment with Lightning ===
Contributors: pseudozach
Tags: comments, bitcoin, lightning, micropayment, spam, captcha
Requires at least: 4.6
Tested up to: 6.0
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: me@pay.pseudozach.com

Require the user to send a small payment before being able to comment on a wordpress blog.

== Description ==

This plugin disables the comment form until a user has sent a bitcoin micropayment over Lightning Network.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Configure provider url and invoice key under Settings > Pay to Comment with Lightning section
1. Enjoy a spam-free comment section!

== Frequently Asked Questions ==

Q: Which Lightning payment processors are supported?
A: LNBits and BTCPayServer

Q: Which Lightning Network implementations are supported?
A: Both LNBits and BTCPayServer support almost all implementations (CLN, Eclair, LND)
