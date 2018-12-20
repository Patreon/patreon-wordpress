=== Plugin Name ===
Contributors: wordpressorg@patreon.com, codebard
Tags: patreon, membership, members
Requires at least: 4.0
Tested up to: 5.0.2
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Serve patron-only posts - directly on your WordPress website.

== Description ==

When a patron pledges, they can then head over to www.yourwordpresssite.com and click your “Unlock with Patreon” button to let the site know they are a patron. When you create content on WordPress you will be given the option to limit it to only paying patrons.

This plugin is maintained by Patreon. For advanced features, you can find additional premium WordPress plugins in this directory.

Read an in-depth review of this free plugin and how to <a href="https://www.elegantthemes.com/blog/tips-tricks/how-to-create-a-patreon-membership-site-on-wordpress" target="_blank">combine Patreon and WordPress part of your membership business</a> on Elegant Themes

### FEATURES FOR CREATORS

- Choose a minimum pledge amount necessary to see a particular post
- Alternatively, set a minimum pledge amount to see all posts.
- Set custom HTML that non-patrons see instead of the post, prompting them to become a patron

*<b>You can post entirely independently on your WordPress site from your Patreon page.*</b> There is no need for WordPress and Patreon posts to correspond to one another in content or in locked status. The choice is up to you as a creator.

Got ideas? Suggest them to the developer community on our <a href="https://www.patreondevelopers.com/t/wordpress-plugin-feature-ideas/215">Patreon WordPress Ideas Thread</a>

### FEATURES FOR PATRONS

- This plugin adds a “Unlock with Patreon” button to every locked post.
- “Unlock with Patreon” takes care of everything: whether they’re not yet a patron, need to upgrade their pledge, or already pledging enough, we’ll guide them through the process and back to your content

### FEATURES FOR DEVELOPERS

- Extend this free basic WordPress plugin with your own inter-operable plugins that install side by side.
- Rapidly develop patron-only WordPress features without initial overhead
- Immediately usable by existing Patreon creators running this plugin

Learn more in our [developer portal](https://www.patreon.com/portal).

### PRICING

This plugin is provided by Patreon for free.

== Installation ==

## Install the Patreon WordPress Plugin

1. Install & activate the plugin
2. Click on ‘Patreon Settings’ to view the options page. It will look something like the below, copy the ‘Redirect URI‘ into the clip board and keep for the next step.
![](https://c5.patreon.com/external/platform/wordpress-client.png)
3. This plugin requires that you enable "pretty permalinks." (ie /page-name/ as opposed to ?pid=36). You can do this from the WordPress admin interface, visit Settings > Permalinks and choose any format aside from "Plain."

## Generate API Credentials on Patreon.com

1. Ensure you are logged into Patreon, using your creator account. When it comes to testing this out you will want to have two Patreon accounts, one that acts as a patron and one being the content creator. The OAuth client needs to be created on your content creating account.
2. Visit the [oAuth client page here](https://www.patreon.com/portal/registration/register-clients) and click "Create Client", fill out the form and add in an image URL for the icon that will appear to users when they are connecting to your website.
3. Note the field for ‘Redirect URI‘, this should still be in your clipboard from the previous steps – if not copy/paste it exactly as it appears on the WordPress admin Patreon Settings page.
4. Click the ‘Create Client’ button, and you should now see the new client in the list.
5. Click the downward facing caret on the right side to expose additional information about the client
6. You should see something like this
![](https://c5.patreon.com/external/platform/wordpress-install-ss5.png)
(The keys in the image are fake, doctored for this screenshot.)

## Paste Your Credentials into WordPress

1. Copy and paste the Client ID and Client Secret from this page into the matching fields on the ‘Patreon Settings’ page.
2. Copy the Client ID, Client Secret, Access Token, and Refresh Token from the top of this page into the WordPress admin Patreon Settings page.
3. Hit ‘Update Settings’ at the bottom of the page.
4. IMPORTANT FINAL STEP: In your WordPress admin ‘Settings’ -> ‘Permalinks’ section, click ‘Save’. This ensures your rewrite rules are flushed.

You should now be up and running with the Patreon WordPress plugin!

## Protecting Posts

When editing a post (or a custom post type) you will see a meta box in the right column titled ‘Patreon Level’.

This box contains a text field that lets you specify a minimum contribution level in dollars. This could be $1 or $1.40 or even $10000. This is entirely up to you.

![](https://c5.patreon.com/external/platform/wordpress_protecting_posts_pages.png)

It defaults to $0 even if left empty.

## Protecting Videos

It is  difficult to protect videos due the intensive bandwidth requirements of hosting video  and having to rely on third parties such as Youtube or Vimeo. Youtube allows you to set videos to ‘private’ but Vimeo offers extra controls by only allowing videos to be played on specific domains. Visit this guide to [protecting your video content with Vimeo](https://help.vimeo.com/hc/en-us/articles/224817847-Privacy-settings-overview).


== Changelog ==

= 1.2.2 =

* Fixed a potential object injection vulnerability which could lead to vulnerable 3rd party plugins getting compromised

= 1.2.1 =

* A bug causing posts to display earlier posts' locking info in locked excerpts was fixed
* oAuth process now returns errors in case Patreon API can't be contacted due to maintenance or any other reason
* 3rd party code and plugins can now override custom banner even if no custom banner was saved in plugin options

= 1.2.0 =

* Now compatible with Patreon API v2
* Patron info related calls to API made to work using v2 - they currently work without needing to upgrade tokens to v2 
* New advanced locking option based on total historical pledge of patrons added
* New advanced locking option based on membership start date of patrons added
* New advanced locking options made work in conjunction with each other to provide a total of 4 locking options
* New advanced locking options added to post locking interface
* Locked content interface now takes into account the new locking options - a proper text is shown to user for each locking case (simple lock, membership start date, total pledge etc)
* Post locking interface now uses a jQuery "Show/Hide Advanced" toggle to keep interface clean
* "Show/Hide Advanced" toggle now remembers user preference
* Existing locking option and new locking options linked to help document from post metabox
* New method for directly locking any part of content or site added - now anything can be locked, not only posts or custom post types. A part of the theme or content can be locked for any given pledge level by using some code (content in sidebars, widgets, header, footer, inside posts etc)
* 
* Compatibility class to hold compatibility related code added
* Do not cache variable added to compatibility class to tell caching plugins to not cache critical Patreon related routing pages (flow, auth)
* Cache control / no cache headers added to headers for Patreon routing pages (flow, auth)
* Added update available notice to tell site owners that a new version is available (dismissable until next update check)
* API v2 accessibility checking functions removed since API v2 is now always being used for patron related calls
* Creator's token refresh code removed from getPatreonCreatorInfo
* Code added to keep track of expiration of creator's access token
* Function added to refresh creators token before it expires to prevent any connectivity issues related to expiration - now it will auto refresh when necessary
* Function that checked creator's url on every page load was removed - this should reduce load
* Function which retrieves patron's details from Patreon on every page load made to do the check every 24 hours instead of every page load - this should reduce load
* Hooks and filters added to Patreon login action that happens in WordPress site after Patreon oAuth
* All API access error cases covered with error messages
* Security cookie check removed to address issues with sites experiencing problems with cookies
* lock_or_not function to receive a post id and decide whether a content should be locked is added
* lock_or_not function now returns the reason why content was locked (not enough pledge, membership start not old enough, not enough total pledge, declined etc)
* All interface functions in locked content interface are made to use lock_or_not function and are simplified
* All interface generating functions made to receive post id so now they can be used programmatically to generate interface for any content - not only the current post
* Error message added in case Patreon does not return a result or WP site cannot connect to Patreon. This will prevent parse errors when this situation happens
* User agent string added to oAuth calls
* Message added to creators that they are seeing the post because they are the creator - for when they log in with creator account
* Unlock button CSS was updated to prevent themes from overriding its size. This will fix issues in sites where the button was showing up too large or too long
* Fixed patronage checking function not returning a value for catch-all case
* get_user_pledge_relationship_start function added to get membership relationship start from v2 API
* Fixed interface text for declined patrons not being used
* Redundant duplicate text for locked content was removed from custom banner part of the interface in cases when no custom banner was entered in plugin options
* fetch_user call uses API v2 with v1 tokens without needing to upgrade to v2
* fetch_creator_info uses still API v1 with v1 tokens - to be revisited in future
* New routing case added to enable direct unlocks
* Plugin now checks if the saved creator's access token is valid upon change/save of credentials, informs of success/failure
* lock_or_not caches its results in a static var to prevent redundant running of code and to increase speed
* Label generators for labels over and under universal button now accept post id and are usable outside loop
* Case for valid patron return fixed
* Undefined var/index notices fixed
* MakeUniversalFlowLink now has a filter to allow filtering of links before sending user to Patreon flow
* Lingering security cookie code removed
* Numerous more minor fixes and changes

= 1.1.2 =

* Functionality for using Patreon API v2 added
* API class uses v2 if v2 credentials are saved in settings-overview
* Content drip locking options added to post interface when API v2 is being used
* Content drip locking logic added to protectContentFromUsers function for when API v2 is being used
* Now can easily switch in between API v1 and API v2 by just changing API credentials in settings-overview
* Code to handle any connection errors added to API class
* Connection errors are now shown in frontend so users wont get confused
* GDPR privacy policy addendum via using WP 4.9.6's new privacy policy helper page
* GDPR admin notice and plugin settings page infobox added
* Links to GDPR tutorial at Patreon Zendesk added
* readfile in image protection functions replaced with echo file_get_contents to make protected images load faster
* Undefined var notice fixes for API v2 functions

= 1.1.1 =

* Option to turn image locking function on/off
* Image locking function defaults to off
* Nginx compatibility for image locking function
* Protocol fix for locked images - no longer http/https confusion
* Filterable utm parameters for login and flow links
* More reliable way to update htaccess with image locking rules
* Refresh htaccess rules when image feature turned on/offers
* Locked posts are protected in RSS feeds
* Transitional option to disable image feature on update
* Notice about new image locking option and info for image locking feature
* Additional messages after login/unlock flow redirection landing

= 1.1.0 =
* Image locking functionality added
* Users are now able to designate a different pledge level for any image and lock them
* Locked images wont be visible when their direct link is used - which also prevents hotlinking of these images
* Clicking on a locked image sends user to the pledge flow at Patreon with appropriate pledge level
* Easy to notice and use image lock icon which appears when an image is clicked in post editor while in visual mode
* Easy to use jQuery modal pledge level interface to lock image while editing a post
* Images can also be locked from media library by setting a pledge level
* Plugin now blurs the original image, and adds an unlock interface to make a locked image placeholder and caches them for performance
* Cached placeholder images are refreshed every time pledge level for an image is updated
* Image unlock links are made cacheable to allow sites using cache to work with locked images without problems
* Front-end jQuery code to only catch clicks on images locked for the current user and send them to pledge flow
* Plugin now imports emails of Patreon users who has their email verified at Patreon
* Login button added to register form
* User agent string added to API contacting function to identify the plugin
* Various information like user's logged in state at Patreon, pledge level and various pledge parameters of user are now cached for any given page load. This will prevent contacting API more than once during a page load and help speed up operations - especially post listings

= 1.0.2 =
* Page protection added
* New logic to make cacheable unlock links
* New logic to make cacheable login links
* Login button shortcode added
* State var urlencoded when going to Patreon and urlencoded when back
* Button width fix
* Login button now appears in login form
* User creation logic now uses Patreon-supplied names for WP display name/
* Support link updated in plugin admin

= 1.0.1 =
* API endpoint protocol fix - http to https
* Added !important to button width and height to prevent themes from overriding them

= 1.0 =
* Plugin launched.
