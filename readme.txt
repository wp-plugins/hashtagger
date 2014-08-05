=== hashtagger ===
Contributors: smartware.cc
Tags: hashtag, hashtags, tag, tags, tag archive
Requires at least: 3.0
Tested up to: 3.9.1
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Tag your posts by using #hashtags

== Description ==

Use [#hashtags](http://en.wikipedia.org/wiki/Hashtag) in posts. This plugin uses the [WordPress Tag system](http://codex.wordpress.org/Posts_Tags_Screen) to field your post under the desired tags.

When saving a post each #hashtag is added as a "normal" tag (without leading hash) to the post, so it is fully compatible with existing tags. 

When showing a post all #hastags are automatically converted to links leading to the corresponding tag archive page.

Use duplicate ##hashes to tell the plugin that this word should not be converted into a tag. Duplicate hashes are replaced by a single hash when showing the post.

Additional CSS Class(es) to add to the #hashtag links can configured on the plugins setting page.

**Caution:** It is not necessary to generally adapt existing posts, because their tags stay unchanged. But keep in mind that on saving a post all existing tas are **removed** and replaced by the tags found in your post! 

= Languages =

* English
* German

**Translators welcome!** The languages directory contains POT files to start new translations. Please [contact Author](http://smartware.cc/) if you would like to do a translation.

= More Information =

Visit the [Plugin Homepage](http://smartware.cc/wp-hashtagger)

== Installation ==

1. Upload the "hashtagger" folder to your "/wp-content/plugins/" directory.
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What characters can a hashtag include? =

The hashtag detection follows the rules for hastags on Twitter, Facebook and Google+. The minimum length for a hastag is 2 characters. A hashtag must not start with a number. A hashtag not only ends at a space but also at punctuation marks and other special characters. A hashtag may contain underscores.

= Does this also work for pages? =

Yes and No. Yes - the plugin adds the tags also for pages. No - WordPress does not show the tags section for pages and also pages are not listed on tag archives. 

This plugin does not change this behavior of WordPress because there already exist several plugins that add the tag functionality for pages. Please use one of them if you want to tag your pages.

= How to change the Tag base? =

The Tag base for the Tag Archive Page URL (e.g. example.com/**tag**/anytag) can be set on the **Permalink Settings** page under **Tag base** in your WP admin.

== Screenshots ==

1. Use hashtags in your post
2. After saving the post the tags are added automatically
3. In frontend the hashtags link to the tag archives
4. The hashtagger Settings Page

== Changelog ==

= 1.2 (2014-08-05) =
* hashtags can contain non ASCII characters
* hashtags must not start with a number
* hashtags can start after punctuation marks without whitespace
* hashtags end at punctuation marks

= 1.1 (2014-07-31) =
* Option to specify css class added
* German translation added

= 1.0 (2014-07-09) =
* Initial Release