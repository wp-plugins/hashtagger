=== hashtagger ===
Contributors: smartware.cc
Donate link:http://smartware.cc/make-a-donation/
Tags: hashtag, hashtags, tag, tags, tag archive
Requires at least: 3.0
Tested up to: 4.1
Stable tag: 2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Tag your posts by using #hashtags

== Description ==

> Use #hashtags and @usernames in your posts.

**Version 3.0 is completely rewritten and introduces a lot of new features and settings. Take a look at the Screenshots to get an idea of all the news!**

= #hashtags =

This plugin uses the [WordPress Tag system](http://codex.wordpress.org/Posts_Tags_Screen) to field your post under the desired tags. When saving a post each [#hashtag](http://en.wikipedia.org/wiki/Hashtag) is added as a "normal" tag (without leading hash) to the post, so it is fully compatible with existing tags. 

When showing a post all #hastags are automatically converted to links leading to the corresponding tag archive page.

Use duplicate ##hashes to tell the plugin that this word should not be converted into a tag. Duplicate hashes are replaced by a single hash when showing the post.

Use +#hashtag to only link to a tag archive page without adding "hashtag" as tag to the post. When showing the post the link is showed as "#hashtag" (without "+"). If the tag does not exist the text remains unchanged and no link is created.

Additional CSS Class(es) to add to the #hashtag links can be configured on the plugins setting page.

**Caution:** It is not necessary to generally adapt existing posts, because their tags stay unchanged. But keep in mind that on saving a post all existing tas are **removed** and replaced by the tags found in your post! **This behavior can be changed in the Plugin Settings Area.**

= @usernames =

The usage of @usernames can be activated optionally. @usernames can link either to the Users Profile Page or to the Users Website. If the username does not exist the text remains unchanged and no link is created.

Use @@username to avoid link creation. When showing the post this is displayed as "@username" without link.

Additional CSS Class(es) to add to the @username links can be configured on the plugins setting page.

Optionally @nicknames can be used instead of @usernames. This is **recommended to enhance security** ([read more](http://smartware.cc/wp-hashtagger/hashtagger-plugin-why-you-should-use-nicknames-instead-of-usernames/)). Although using @nicknames is the better option, the default is @usernames for compatibility to Plugin versions prior 2.1.

= Post Types and Section Types =

In the plugins Settings you can define where #hashtags and @usernames should be processed. It is possible to use them on Posts, on Pages and on Custom Post Types within Content, Title and Excerpt. Activate only the Post Types and Section Types you want to be processed to avoid unnecessary processing for best performance.

= Languages =

* English
* German

**Translators welcome!** The languages directory contains POT files to start new translations. Please [contact Author](http://smartware.cc/contact/) if you would like to do a translation.

= Theme function =

You can use `do_hashtagger( $content )` in your theme files to process #hashtags and @usernames in $content.

**Example:**
`<?php echo do_hashtagger( get_post_meta( get_the_ID(), 'mycustomfield', true ) ); ?>` 

= More Information =

Visit the [Plugin Homepage](http://smartware.cc/wp-hashtagger)

= Do you like the hashtagger Plugin? =

Thanks, I appreciate that. You don’t need to make a donation. No money, no beer, no coffee. Please, just [tell the world that you like what I’m doing](http://smartware.cc/make-a-donation/)! And that’s all.

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins' -> 'Add New'
1. Search for 'hashtagger'
1. Activate the plugin through the 'Plugins' menu in WordPress

= Manually from wordpress.org =

1. Download hashtagger from wordpress.org and unzip the archive
1. Upload the `hashtagger` folder to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

= Usage =

Just type anywhere in a post:

* **#hashtag** to add "hashtag" as tag to the current post and link to tag archive page for "hashtag"
* **+#hashtag** to link to tag archive page for "hashtag" without adding "hashtag" as tag to the current post
* **##hashtag** to display "#hashtag" when viewing post without creating a link and without adding a tag
* **@username** to link either to the Profile Page or the Website of User "username" (@username feature has to be activated)
* **@@username** to display "@username" when viewing post without creating a link (@username feature has to be activated)

= WordPress Security =

**It is highly recommended to use @nicknames instead of @usernames!** Please read [this article](http://smartware.cc/wp-hashtagger/hashtagger-plugin-why-you-should-use-nicknames-instead-of-usernames/)!

== Frequently Asked Questions ==

= What characters can a hashtag include? =

The hashtag detection follows the rules for hastags on Twitter, Facebook and Google+. The minimum length for a hastag is 2 characters. A hashtag must not start with a number. A hashtag not only ends at a space but also at punctuation marks and other special characters. A hashtag may contain underscores.

= Does this also work for pages? =

Yes and No. Yes - the plugin can add the tags also for pages. No - WordPress does not show the tags section for pages and also pages are not listed on tag archives. 

This plugin does not change this behavior of WordPress because there already exist several plugins that add the tag functionality for pages. Please use one of them if you want to tag your pages.

= How to change the Tag base? =

The Tag base for the Tag Archive Page URL (e.g. example.com/**tag**/anytag) can be set on the 'Permalink Settings' page under 'Tag base' in your WP admin.

= Where does @username link to? =

This can be set on hashtagger Settings Page. @username links can either link to the Users Profile Page or to the Users Webiste (Users Profile Page if no Webpage is set). When linking to Users Website the link can be opened in a new window if desired.

= Why should I use @nicknames instead of @usernames =

This is important to enhance WordPress security. Please read [this article](http://smartware.cc/wp-hashtagger/hashtagger-plugin-why-you-should-use-nicknames-instead-of-usernames/).

= Can I use #hashtags and/or @usernames in Excerpts? =

Yes, just activate this feature.

= Can I use #hashtags and/or @usernames in Titles? =

Yes, just activate this feature.

= Can I regenerate all existing Posts after changing Settings? =

Yes, you can regenerate all affected objects (Post, Pages, Custom Posts) using the current settings.

== Screenshots ==

1. hashtagger Settings General Section: This section shows the current Tag base setting and the handling of @usernames respectively @nicknames can be changed here
2. hashtagger Settings Advanced Section: As of version 3.0 it is possible to not delete tags that are not found in content – may be useful if already a lot of posts with tags exist - but please note that with this setting activated tags will not be removed if you delete a hashtag
3. hashtagger Settings Post Types Section: In this section the Post Types to handle can be defined - activate only the Post Types for which you want to use hashtags to make sure posts are nor processed unnecessarily for better performance
4. hashtagger Settings Section Types Section: In this section the Section Types to handle can be defined - only activate the Section Types you want to use for best performance
5. hashtagger Settings CSS Style Section: Add CSS Classes to use for the generated links in Front End
6. hashtagger Settings Regenerate Section: Regenerate all existing Objects with the current settings
7. hashtagger Settings Regenerate Section: Regeneration in process

== Changelog ==

= 3.0 (2014-12-21) =
* Completely rewritten with a lot of new Settings and a feature to regenerate existing objects

= 2.1 (2014-10-10) =
* Optionally use @nicknames instead of @usernames (**important** - [read more](http://smartware.cc/wp-hashtagger/hashtagger-plugin-why-you-should-use-nicknames-instead-of-usernames/) - thanks to [joeymalek](https://profiles.wordpress.org/joeymalek/) for pointing out)
* Added `do_hashtagger()` Theme Function (thanks to [joinfof](https://profiles.wordpress.org/joinfof/) for the idea)

= 2.0 (2014-09-17) =
* Optional usage of @usernames
* Syntax +#hashtag to create link only without adding tag

= 1.3 (2014-08-15) =
* Solved: do not use hex color codes in css as hashtags (see [this Support topic](http://wordpress.org/support/topic/this-is-really-great-but-it-doesnt-let-me-color-code-anything))

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

== Upgrade Notice ==

= 3.0 =
Version 3.0 introduces a lot of new features and settings. Don't miss it!