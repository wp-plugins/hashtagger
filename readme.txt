=== hastagger ===
Contributors: smartware.cc
Tags: hashtag, hashtags, tag, tags, tag archive
Requires at least: 3.0
Tested up to: 3.9.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Tag your posts by using #hashtags

== Description ==

Use [#hashtags](http://en.wikipedia.org/wiki/Hashtag) in posts. This plugin uses the [WordPress Tag system](http://codex.wordpress.org/Posts_Tags_Screen) to field your post under the desired tags.

When saving a post each #hashtag is added as a "normal" tag (without leading hash) to the post, so it is fully compatible with existing tags. 

When showing a post all #hastags are automatically converted to links leading to the corresponding tag archive page.

Use duplicate ##hashes to tell the plugin that this word should not be converted into a tag. Duplicate hashes are replaced by a single hash when showing the post.

**Caution:** It is not necessary to generally adapt existing posts, because their tags stay unchanged. But keep in mind that on saving a post all existing tas are **removed** and replaced by the tags found in your post! 

= More Information =

Visit the [Plugin Homepage](http://smartware.cc/wp-hashtagger)

== Installation ==

1. Upload the "hashtagger" folder to your "/wp-content/plugins/" directory.
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Does this also work for pages?  =

Yes and No. Yes - the plugin adds the tags also for pages. No - WordPress does not show the tags section for pages and also pages are not listed on tag archives. 

This plugin does not change this behavior of WordPress because there already exist several plugins that add the tag functionality for pages. Please use one of them if you want to tag your pages.

== Screenshots ==

1. Use hashtags in your post
2. After saving the post the tags are added automatically
3. In frontend the hashtags link to the tag archives

== Changelog ==

= 1.0 (2014-07-13) =
* Initial Release