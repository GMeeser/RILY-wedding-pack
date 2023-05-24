=== Libretto ===
Contributors: automattic
Tags: gray, red, silver, tan, white, light, one-column, fixed-layout, responsive-layout, accessibility-ready, custom-background, custom-colors, custom-header, custom-menu, editor-style, featured-images, featured-image-header, flexible-header, infinite-scroll, microformats, post-formats, rtl-language-support, site-logo, sticky-post, theme-options, translation-ready, art, artwork, blog, design, design, fashion, food, hotel, journal, lifestream, magazine, photoblogging, photography, clean, conservative, elegant, faded, formal, light, minimal, simple, sophisticated, traditional
Tested up to: 4.2
Stable tag: 4.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Libretto is a responsive one-column theme with classic styling and careful typographic details. It’s ideally suited to showcasing longform writing interspersed with beautiful images and inspiring quotes.

Libretto features a clean type-based layout for writers who want their content to take centre stage. Showcase great images, clever quotes, and well-written posts without any distractions. Classic, simple, and subtle, Libretto will allow your words and images to shine.

== Installation ==

1. In your admin panel, go to Appearance â†’ Themes and click the Add New button.
2. Click Upload and Choose File, then select the theme's .zip file. Click Install Now.
3. Click Activate to use your new theme right away.

== Frequently Asked Questions ==

= Where can I add widgets? =

Libretto includes an optional footer for widgets. By default, this footer won't be shown unless you add one or more widgets to it. You can add up to four different columns of widgets. Libretto will look best with one or two widgets in each widget area.

Configure these areas by going to Appearance â†’ Widgets in your Dashboard.

= Can I customize my menu? =

You sure can! Libretto supports one menu at the top of the page.

Libretto will support a long menu, but it's happiest if you use shorter menus. For best results, stick to five or six top-level items, and group other links under sub-menus.

= How can I add a site logo? =

Brand your site and make it yours by including your business' logo with Jetpack (http://jetpack.me); navigate to Customize â†’ Site Title and upload a logo image in the space provided. The logo will appear on top of your site title in the header.

The maximum dimension for the logo is 100px (either horizontally or vertically), but you can upload a logo double that size for optimal display on Retina devices. A circular logo will look particularly nice here. If your logo doesn't work well against your header image, try putting it on a circular background using your image editor.

= Does Libretto use featured images? =

Yes! If a Featured Image is set for a post, it will display below the post title, and above the post content, on the blog index and archives. If a Custom Header Image has been set for the site, the Featured Image will also appear in the header area on individual post pages.

Libretto will look great if you use lots of big, beautiful images in your posts. For featured images, 1200 pixels wide is a good starting point, but you may want to upload larger images so they'll display as full-page images at the beginning of your post.

If the images used in your posts are larger than 890 pixels wide, they'll overlap the content area a little bit. Uploading larger images will allow you to showcase them more.

= Does Libretto support Infinite Scroll? =

Libretto includes built-in support for Infinite Scroll. If you're using any footer widgets or the social media nav menu, Infinite Scroll will automatically switch to click-to-load-more mode. In this case, you might prefer to switch Infinite Scroll off and instead use the static pagination included in Libretto for more of a classic, book-like feel.

= Does Libretto have social links? =

If you have social media links you'd like to include in the footer, add them using a custom menu.

1. Create a new Custom Menu, and assign it to the Social Links Menu location
2. Add links to each of your social services using the Links panel
3. Icons for your social links will automatically appear under the heart icon in the header

Full support is automatically included for the following networks: Facebook, Twitter, Google+, Tumblr, Stumbleupon, Reddit, Flickr, Instagram, LinkedIn, Dribbble, Pinterest, Vimeo, and Youtube. Additional networks will appear with a simple star icon. To customize their appearance, you'll need to manually tweak the CSS yourself.

= Can I remove the drop cap effect on paragraphs? =

A drop cap and first-line effect is applied to the first paragraph of standard posts. If you'd like to remove it, you can do so by wrapping your first paragraph with a p tag:

<p class="no-emphasis">Paragraph</p>

If you'd like to apply the styling to a different paragraph, you can do so by wrapping it in this tag:

<p class="emphasis">Paragraph</p>

= Quick Specs (all measurements in pixels) =

1. The main column width is 680.
2. Maximum width for images and overlapping elements is 896.
3. Widgets will vary in width depending on device and the number used, but they range from 242 to 437.
4. The site logo displays at a maximum width and height of 100.

== Changelog ==

= 24 April 2018 =
* Default theme pagination - wrap a string in a gettext function, so that it's available for translation.

= 5 April 2018 =
* Optimize images

= 7 March 2018 =
* Improve contact form appearance.

= 23 February 2018 =
* Simplify Headstart annotations.

= 20 December 2017 =
* Reverting accidental code changes
* Prevent menu from collapsing when scrolling on Android.

= 29 September 2017 =
* Update version number in preparation for .org submission.

= 27 September 2017 =
* Ensure adequate margins on content for screen sizes above 641 px.

= 25 August 2017 =
* Replace line of smarter featured image code that was not compatible with PHP 5.3.x, for backwards compatibility.

= 26 June 2017 =
* Styles to fix an edge case issue are causing text to disappear in some versions of Safari; removing those styles for that browser for the time being.

= 3 May 2017 =
* Add Smarter Featured Images support, off by default.

= 7 April 2017 =
* Check for post parent before outputting next, previous, and image attachment information to prevent fatals.

= 22 March 2017 =
* add Custom Colors annotations directly to the theme
* move fonts annotations directly into the theme

= 7 February 2017 =
* Replace get_the_tag_list() with the_tags() for a more straightforward approach that prevents potential fatal errors.

= 18 January 2017 =
* Add translation of Headstart annotation

= 30 November 2016 =
* Fix Content Options Featured Images undeclared variable by adding extra check to make sure variable is set

= 28 November 2016 =
* Fix typo

= 24 November 2016 =
* Add support for Content Options - Featured Images

= 15 November 2016 =
* Removing the CSS restrictions on logo.

= 19 October 2016 =
* Use CSS selector for Content Options

= 17 October 2016 =
* Remove dead image link from post content.

= 19 August 2016 =
* Adding conditional check to not display site branding on blog index page, where it's displayed below the nav-bar.

= 12 August 2016 =
* Adding back a modest z-index to title element to prevent it from being overshadowed by header gradient.

= 11 August 2016 =
* Removing the aggresive z-index on header elements.

= 26 July 2016 =
* Updates to get mobile header previewing more accurately:
* Add support for Content Options

= 28 June 2016 =
* Adjust styles so search toggle no longer overlaps last item in menu when menu is closed.

= 12 May 2016 =
* Add new classic-menu tag.

= 5 May 2016 =
* Move annotations into the `inc` directory.

= 4 May 2016 =
* Move existing annotations into their respective theme directories.

= 9 December 2015 =
* Ensure that proper post classes are applied to post types on main blog page, too.

= 2 November 2015 =
* Add a period to footer credit;

= 22 September 2015 =
* Tighten up comment styles.

= 28 August 2015 =
* Remove custom password form and restyle form to account for default password form.

= 31 July 2015 =
* Remove `.screen-reader-text:hover` and `.screen-reader-text:active` style rules.

= 27 July 2015 =
* Update theme description.
* Update screenshot and readme file for WordPress.org submission.
* Strip out unnecessary body classes.
* Remove extra padding from long menus with hidden titles.
* Ensure that menu close button for mobile menu lines up properly.
* Ensure that menu bar remains the same height regardless of whether there's a menu or not.
* Use a smaller drop cap on phones and mobile devices.
* Reduce font-size on blockquotes.

= 26 July 2015 =
* Clean up Google font registration for translators.

= 22 July 2015 =
* Update screenshot to show front page of demo, rather than an inside page.
* Remove `background-attachment: fixed` from header.

= 16 July 2015 =
* Generate new GlotPress project.
* Add screenshot in the proper folder.
* Add a proper screenshot, duh.
* Add a tiny bit of padding so search icon isn't quite so close to the menu.

= 15 July 2015 =
* Ensure that images are loaded before calculating dimensions.

= 14 July 2015 =
* Reduce opacity of child comments in order to ensure contrast;
* Tweak sizing and spacing of submit buttons so they work consistently across various use cases.

= 10 July 2015 =
* Slow down search animation and hide search box when focus shifts.
* Hide title on blog homepage.
* Reduce spacing between posts by half.
* Only resize header image to expand to full screen on larger devices.

= 9 July 2015 =
* Tweak widget styles so all wpcom widgets work nicely.
* Make sure search widget submit button doesn't get hidden by "overflowing" its container.
* Remove margins from avatars in widgets.
* Ensure that any content which overflows the widget's boundaries will be hidden.
* Ensure that search widget displays at full width.
* Better rating alignment on comments.
* Actually include new wp-com css styles, duh. Also style the rating a bit so it looks less garbagey.
* Remove extra formatting from "post comment" button.
* Adjust drop-cap and first-line styling for better cross-browser consistency.
* Tighten up spacing & alignment of footer.
* Hide title block on mobile devices to increase space and decrease repetition.

= 8 July 2015 =
* Improve page styling.
* Rework entry meta for betterness.
* Reorganise custom header image code.
* Code cleanup based on code review.
* Reduce line-height of text throughout. Drop body text size a smidge. Re-align drop cap as well as possible.

= 7 July 2015 =
* Force correct font stack for comment area.
* Add a bit of emphasis to text widget.
* Show featured images on pages too, because why not.
* Speed up animation of search bar.

= 6 July 2015 =
* If the user doesn't enter a search term, we should just close the search bar when the icon is clicked.
* Ensure that search form is only submitted when we click on the label itself.
* Ensure that layout works well without a custom header image uploaded.
* Various improvements to header image handling.
* Improve header height adjustments to account for nav bar and wp admin bar.

= 1 July 2015 =
* Stop saying hi to the console already.
* Unstick the top navbar.
* Add a little extra margin around drop cap to ensure wider letters look good.
* Ensure that all font-sizes in rems have px fallback.

= 30 June 2015 =
* Fine-tune RTL styles.
* Smoosh all component CSS files together and CSS-comb the lot.
* Allow IS even if we have a social menu.
* Clean up IS click-to-scroll button.
* Position pull quotes better on tablets and phones.
* Add pullquote styling for left and right aligned blockquotes.
* Tweak styling of blockquotes to fit better within available space.
* Add ability for users to manually add and remove first-paragraph styling.
* Tweaks to password-protected posts.
* Apply libretto-long-form class to search results.
* Tweak empty search results page to display properly.

= 26 June 2015 =
* Audit font usage and only load fonts used.
* Correct JS error introduced by wanton PHPCBF usage.
* Remove overlap/oversized class from images in comments.
* Use a darker grain for extra header backgrounds.
* Update tags to accurately reflect theme functionality.
* Use default search box rather than custom and tweak appearance a bit.
* Ensure correct placement of search bar whether main menu is used or not.
* Use a slightly darker background colour.
* Ensure that abbreviated footers (used
* Improved featured image display.
* Use proper template_tags function for post footer meta infos.

= 18 June 2015 =
* Move to live repo for more testing.

== Licensing ==

Libretto WordPress Theme, Copyright 2015 Automattic
Automattic is distributed under the terms of the GNU GPL

Libretto is a fork of Readly, originally by WP Shower. http://wpshower.com/themes/readly/

Libretto WordPress Theme bundles the following third-party resources:

Genericons icon font, Copyright 2013 Automattic
Genericons are licensed under the terms of the GNU GPL, Version 2 (or later)
Source: http://www.genericons.com

Touche jQuery library, Copyright 2013 Ben Howdle
Touche is licensed under the terms of the MIT license
Source: https://github.com/benhowdle89/touche/blob/master/LICENSE

Playfair Display, Copyright Claus Eggers Sørensen
Libre Baskerville, Copyright Impallari Type
Montserrat, Copyright Julieta Ulanovsky
All licensed under the terms of the SIL Open Font License, 1.1

Droid Sans, Copyright Steve Matteson
Licensed under the terms of the Apache License, version 2.0
