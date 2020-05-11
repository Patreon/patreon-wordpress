=== Patreon WordPress ===
Contributors: wordpressorg@patreon.com, codebard
Tags: patreon, membership, members
Requires at least: 4.0
Requires PHP: 5.4
Tested up to: 5.4.1
Stable tag: 1.5.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site and your Patreon to increase your patrons and pledges!

With Patreon WordPress, you can bring Patreon features to your WordPress website and integrate them to make them work together. You can even easily start posting patron-only content at your WordPress site and encourage your visitors to become your patrons to unlock your content.

You can lock any single post or all of your posts! You can also lock any custom post type. Your visitors can log into your site via Patreon, making it easier for them to use your site in addition to accessing your locked content.

Read how Lawless French increased their income <a href="https://blog.patreon.com/patreon-wordpress-plugin" target="_blank">50% in just 3 months using Patreon WordPress</a>.

This plugin is developed and maintained by Patreon. 

= FEATURES FOR CREATORS =

- Choose one of your tiers or a minimum pledge amount necessary to access a post or custom post
- All patrons with pledge at or above that minimum tier will be able to access your post
- Visitors who are not your patrons can click the "Unlock with Patreon" button on the locked post to pledge to you and access content
- Visitors will be automatically redirected to Patreon, pledge to you and come back to your site to original unlocked post
- Plugin will automatically log in Patreon users
- Alternatively, you can set a minimum pledge amount to see all posts
- Set custom HTML that non-patrons see instead of the post, prompting them to become a patron
- Patreon WordPress is compatible with Paid Memberships Pro - you can gate your content with either plugin
- Patreon pledges are matched with Paid Memberships Pro monthly memberships - works out of the box with no changes
- Any Patreon patron or Paid Memberships Pro member who qualifies for content via either plugin will access content

> *<b>You can post entirely independently on your WordPress site from your Patreon page.*</b> There is no need for WordPress and Patreon posts to correspond to one another in content or in locked status. The choice is up to you as a creator.

Got ideas? Post them on our [Patreon WordPress Ideas Thread](https://www.patreondevelopers.com/t/wordpress-plugin-feature-ideas/215)

= FEATURES FOR PATRONS =

- This plugin adds a "Unlock with Patreon" button to every post you lock.
- "Unlock with Patreon" takes care of everything: whether they’re not yet a patron, need to upgrade their pledge, or already pledging enough, we’ll guide them through the process and back to your content

= FEATURES FOR DEVELOPERS =

- Extend this free basic WordPress plugin with your own inter-operable plugins that install side by side.
- Rapidly develop patron-only WordPress features without initial overhead
- Immediately usable by existing Patreon creators running this plugin

Learn more in our [developer portal](https://www.patreon.com/portal).

= PRICING =

This plugin is provided by Patreon for free.

= Install the Patreon WordPress Plugin =

1. Install & activate the plugin
2. The setup wizard will kick in, helping you to easily connect your WordPress site and Patreon in only two clicks
3. That's it!

Your plugin is now set up and you can start making your posts patron only!

= Gating Posts =

When posting a new post or editing an existing post (or a custom post type) you will see a dropdown in the right hand column titled "Patreon Level".

This box shows a dropdown of your Patreon tiers. When you select a Patreon tier and then update the post, visitors will need to be your patrons from that tier level or above to be able to access that post.

To make a locked post public again, just choose "Everyone" from the select box and update your post.

= Protecting Videos =

It is  difficult to protect videos due the intensive bandwidth requirements of hosting video  and having to rely on third parties such as Youtube or Vimeo. Youtube allows you to set videos to ‘private’ but Vimeo offers extra controls by only allowing videos to be played on specific domains. Visit this guide to [protecting your video content with Vimeo](https://help.vimeo.com/hc/en-us/articles/224817847-Privacy-settings-overview).

== Upgrade Notice ==

= 1.5.5 =

* Added no cache headers to gated/locked images so browsers and ISPs will be less prone to caching them. This would address issues with images appearing locked/unlocked despite being in the opposite state.
* Added an option to allow hiding login with Patreon button in WP login page and login forms. Does not impact login - users can still unlock/login via Patreon even if the button is hidden.
* Added caching to getPatreonUser function. Will cache last 50 Patreon users' info when queried. This will speed up user listings and will reduce load on the api.
* getPatreonUser function now accepts $user object as parameter. You can now query different users' Patreon info as opposed to only the current user. This will help custom code and 3rd party plugins to do mass user processing to distribute benefits at WP sites.

= 1.5.4 =

* Made active patrons only choice desc text clearer
* Added a isset check to prevent notices from breaking login after return from Patreon in sites which display notices

= 1.5.3 =

* Added an override to set api version to 2 after return from connect/reconnect attempt at Patreon to address potential parse errors on v1 sites
* Added override now loads v2 version of api class
* Added overrides to set api version to 2 upon successful return from connect/reconnect attempt at Patreon
* Removed is_admin condition in api class loader's version overrides

= 1.5.2 =

* Added short term local copy of remote patron info to getPatreonUser function to help with temporary api connection issues
* Patron info is saved when user logins to WP site via Patreon
* Made getPatreonUser function try to refresh the token if token error was received from Patreon
* getPatreonUser function now falls back to the local copy if fresh info cannot be acquired from the api
* getPatreonUser function checks for validity of local patron info. Validity period is 3 days

= 1.5.1 =

* You can now set the currency that is shown on gated posts by setting the option in plugin settings
* Added an admin pointer to inform about PMP compatibility

= 1.5.0 =

* Patreon WordPress is now compatible with Paid Memberships Pro
* Both plugins cooperate over monthly membership and monthly pledge formats
* Paid Memberships Pro gated content can be unlocked via Patreon if user has qualifying pledge level that matches PMP gated content
* Patreon gated content can be unlocked by a matching PMP membership level
* Content gated by both PW and PMP can be unlocked by qualifying pledge from Patreon that matches the Patreon pledge
* Content gated by both PW and PMP can be unlocked by qualifying tier membership from PMP that matches the PMP tier
* Made the setup wizard erase v1 related labels from options to allow old v1 sites use v2 setup wizard to reconnect their site to Patreon

= 1.4.9 =

* Image lock toolbar now appears when an image in Gutenberg editor is clicked
* Reworked image lock interface to be unfirom across both desktop and mobile devices
* Image lock interface now warns if image lock is saved without image lock feature being enabled in site
* Made image lock toolbar disappear properly when anything that is not an image is clicked
* Image lock toolbar launch code adjusted to work for Classic editor and Gutenberg at the same time
* Image lock toolbar now finds the image's attachment id via attachment url instead of determining it via class name

= 1.4.8 =

* Minor fix to force update tiers from API when tier dropdown refresh button is clicked

= 1.4.7 =

* Added a refresh button next to tier dropdown in post editor. Allows manual refresh of tiers from Patreon without leaving post editor
* Removed forced auto-refreshing of tiers from Patreon when loading post editor

= 1.4.6 =

* Made variables that hold subclasses public instead of private to allow custom site mods and 3rd party plugins to be able to use them
* Turned subclass includers to include_once to allow custom site mods and 3rd party plugins to include and use them if needed
* Made subclass variable names uniform
* Subclass variable name which had the word patron instead of patreon was fixed. Lowercase
* Retry links in site disconnect and reconnect error messages fixed. They were pointing to disposable test site

= 1.4.5 =

* Added a simple way for hiding ads using a single function. This will allow creators to hide ads for their patrons in any part of their WP site
* Added a login widget that site admins can put in the sidebar or other widget areas of their site. It allows users to login via Patreon, and shows 'Connect your Patreon' version of the login button for WP users who dont have a connected Patreon account. Allows optional message and also shows a logout link.
* Made [patreon_login_button] shortcode allow connecting one's Patreon account if logged in. Shows 'Connect your Patreon' version in such cases
* Added a 'Connect your Patreon' button
* Patreon_Frontend::showPatreonLoginButton function now shows alternative 'Connect your Patreon' version of login image in all login forms
* Patreon_Frontend::showPatreonLoginButton now accepts args
* Patreon_Frontend::showPatreonLoginButton now allows override of login image via args
* Added parameters to make_tiers_select function to allow skipping updating creator tiers from Patreon via arguments

= 1.4.4 =

* Added a simple way for custom gating any part of a WP site using a single function. This will allow easier gating of any part of a site via theme files.
* Removed the formerly required 'patreon_enable_direct_unlocks' global var requirement for custom gating since now its not needed.
* Added a 'Connect site' button to show when all Patreon connection detail fields are empty. This will allow reconnecting sites to Patreon using connection wizard.

= 1.4.3 =

* Added compatbility for WP Fastest Cache - now plugin will tell WP Fastest Cache to not serve a post/page from cache if the post/page is a gated one. This should make content unlocking process for patrons better in sites using WP Fastest Cache

= 1.4.2 =

* Updated gated post interface text to be more informative. Now non-qualifying patrons should see the reason why they dont qualify for access to a gated post
* Fixed a minor PHP notice that non-patron Patreon users saw when they viewed a gated post after logging into WP site via Patreon

= 1.4.1 =

* Post meta saving code simplified
* Help link updated
* Credential check after credential save got minor enhancements
* Set a default email to make sure email check fails when a user doesnt have an email verified at Patreon
* A notice that appeared during plugin update check was fixed
* A duplicate state var was removed

= 1.4.0 =

Added no-cache HTTP header to prevent caching of gated content
More efficient and smooth addition/removal of image locking feature related htaccess rules
Universal deactivate function
Rewrite rules flushed upon activate/deactivate
Added filter for raw text of label over interface button

= 1.3.9 =

PW now prevents caching of gated content to make sure unlocked content unlocks

= 1.3.8 = 

Added a health check page that allows you to see the health of your Patreon integration

= 1.3.7 = 

Minor internal feature update

== Installation ==

1. Install & activate the plugin
2. The setup wizard will kick in, helping you to easily connect your WordPress site and Patreon in only two clicks
3. That's it!

== Screenshots ==

1. An example gated patron-only post
2. Example permission screen when a site user unlocks a post
3. Example unlocked patron-only post

== Frequently Asked Questions ==

= Does it work with any theme? =

Patreon WordPress works with any theme.

= Does it work with this particular plugin? =

Patreon WordPress should not affect functioning of any of your other plugins. Patreon WordPress sticks to WP coding standards and would play nice with any other plugin that does the same.

= Does it work with this particular membership plugin? =

Yes, you can use Patreon WordPress side by side with any other membership plugin.

= Does it work with WooCommerce? =

You can easily install and use Patreon WordPress alongside WooCommerce at the same time.

= Do my patrons get charged again if they unlock a post on my site? = 

Your patrons do not get charged again if they unlock any post on your site via the 'Unlock with Patreon' button. The plugin just checks if they are qualifying patrons, and if so, it lets them access your content.

= Do my posts at my site and Patreon need to be the same? =

Not at all - you can post different content totally independently at your site and Patreon.

= Will anything be changed at my site after I install the plugin? =

Nothing will be changed at your site - the plugin will just connect your site to Patreon to allow communication in between your site and Patreon.

== Changelog ==

= 1.5.5 =

* Added no cache headers to gated/locked images so browsers and ISPs will be less prone to caching them. This would address issues with images appearing locked/unlocked despite being in the opposite state.
* Added an option to allow hiding login with Patreon button in WP login page and login forms. Does not impact login - users can still unlock/login via Patreon even if the button is hidden.
* Added caching to getPatreonUser function. Will cache last 50 Patreon users' info when queried. This will speed up user listings and will reduce load on the api.
* getPatreonUser function now accepts $user object as parameter. You can now query different users' Patreon info as opposed to only the current user. This will help custom code and 3rd party plugins to do mass user processing to distribute benefits at WP sites.

= 1.5.4 =

* Made active patrons only choice desc text clearer
* Added a isset check to prevent notices from breaking login after return from Patreon in sites which display notices

= 1.5.3 =

* Added an override to set api version to 2 after return from connect/reconnect attempt at Patreon to address potential parse errors on v1 sites
* Added override now loads v2 version of api class
* Added overrides to set api version to 2 upon successful return from connect/reconnect attempt at Patreon
* Removed is_admin condition in api class loader's version overrides

= 1.5.2 =

* Added short term local copy of remote patron info to getPatreonUser function to help with temporary api connection issues
* Patron info is saved when user logins to WP site via Patreon
* Made getPatreonUser function try to refresh the token if token error was received from Patreon
* getPatreonUser function now falls back to the local copy if fresh info cannot be acquired from the api
* getPatreonUser function checks for validity of local patron info. Validity period is 3 days

= 1.5.1 =

* You can now set the currency that is shown on gated posts by setting the option in plugin settings
* Added an admin pointer to inform about PMP compatibility

= 1.5.0 =

* Patreon WordPress is now compatible with Paid Memberships Pro
* Both plugins cooperate over monthly membership and monthly pledge formats
* Paid Memberships Pro gated content can be unlocked via Patreon if user has qualifying pledge level that matches PMP gated content
* Patreon gated content can be unlocked by a matching PMP membership level
* Content gated by both PW and PMP can be unlocked by qualifying pledge from Patreon that matches the Patreon pledge
* Content gated by both PW and PMP can be unlocked by qualifying tier membership from PMP that matches the PMP tier
* Made the setup wizard erase v1 related labels from options to allow old v1 sites use v2 setup wizard to reconnect their site to Patreon

= 1.4.9 =

* Image lock toolbar now appears when an image in Gutenberg editor is clicked
* Reworked image lock interface to be unfirom across both desktop and mobile devices
* Image lock interface now warns if image lock is saved without image lock feature being enabled in site
* Made image lock toolbar disappear properly when anything that is not an image is clicked
* Image lock toolbar launch code adjusted to work for Classic editor and Gutenberg at the same time
* Image lock toolbar now finds the image's attachment id via attachment url instead of determining it via class name

= 1.4.8 =

* Minor fix to force update tiers from API when tier dropdown refresh button is clicked

= 1.4.7 =

* Added a refresh button next to tier dropdown in post editor. Allows manual refresh of tiers from Patreon without leaving post editor
* Removed forced auto-refreshing of tiers from Patreon when loading post editor

= 1.4.6 =

* Made variables that hold subclasses public instead of private to allow custom site mods and 3rd party plugins to be able to use them
* Turned subclass includers to include_once to allow custom site mods and 3rd party plugins to include and use them if needed
* Made subclass variable names uniform
* Subclass variable name which had the word patron instead of patreon was fixed. Lowercase
* Retry links in site disconnect and reconnect error messages fixed. They were pointing to disposable test site

= 1.4.5 =

* Added a simple way for hiding ads using a single function. This will allow creators to hide ads for their patrons in any part of their WP site
* Added a login widget that site admins can put in the sidebar or other widget areas of their site. It allows users to login via Patreon, and shows 'Connect your Patreon' version of the login button for WP users who dont have a connected Patreon account. Allows optional message and also shows a logout link.
* Made [patreon_login_button] shortcode allow connecting one's Patreon account if logged in. Shows 'Connect your Patreon' version in such cases
* Added a 'Connect your Patreon' button
* Patreon_Frontend::showPatreonLoginButton function now shows alternative 'Connect your Patreon' version of login image in all login forms
* Patreon_Frontend::showPatreonLoginButton now accepts args
* Patreon_Frontend::showPatreonLoginButton now allows override of login image via args
* Added parameters to make_tiers_select function to allow skipping updating creator tiers from Patreon via arguments

= 1.4.4 =

* Added a simple way for custom gating any part of a WP site using a single function. This will allow easier gating of any part of a site via theme files.
* Removed the formerly required 'patreon_enable_direct_unlocks' global var requirement for custom gating since now its not needed.
* Added a 'Connect site' button to show when all Patreon connection detail fields are empty. This will allow reconnecting sites to Patreon using connection wizard.

= 1.4.3 =

* Added compatbility for WP Fastest Cache - now plugin will tell WP Fastest Cache to not serve a post/page from cache if the post/page is a gated one. This should make content unlocking process for patrons better in sites using WP Fastest Cache

= 1.4.2 =

* Updated gated post interface text to be more informative. Now non-qualifying patrons should see the reason why they dont qualify for access to a post
* Fixed a minor PHP notice that non-patron Patreon users saw when they viewed a gated post after logging into WP site via Patreon

= 1.4.1 =

* Post meta saving code simplified
* Help link updated
* Credential check after credential save got minor enhancements
* Set a default email to make sure email check fails when a user doesnt have an email verified at Patreon
* A notice that appeared during plugin update check was fixed
* A duplicate state var was removed

= 1.4.0 =

* Added no-cache HTTP header to prevent caching of gated content
* More efficient and smooth addition/removal of image locking feature related htaccess rules
* Universal deactivate function
* Rewrite rules flushed upon activate/deactivate
* Added filter for raw text of label over interface button

= 1.3.9 =

* Plugin will now try preventing caching of gated content. This will help users to access the content they unlocked instead of still seeing the cached locked version. Has option to turn on/off
* Added admin pointers to help users navigate plugin related info
* Added admin pointer for new cache option
* Formatted gated content feed items to have proper html
* Fixed WP 5.3 causing add_submenu_page parameter notice

= 1.3.8 =

* Added a health check page that shows the health of Patreon integration
* Added compatibility checks for permalink settings and WP Super Cache settings
* Critical issues with the integration are shown in dismissable notice that shows up on a 7 day basis when dismissed
* Removed the transitional image locking option check code now that it is not needed

= 1.3.7 =

* Readme updated, screenshots added, Faq added
* Made __get_json function and token var in API class public so 3rd party addons and class extenders can access and use them

= 1.3.6 =

* Allowed existing sites using v1 to upgrade to v2 by using reconnect/disconnect/setup functions. This also fixes the PHP error these sites may have encountered if they attempted that upgrade.

= 1.3.5 =

* Addressed various PHP warnings and notices which may have appeared in websites that have warnings and notices turned on
* Fixed missing default app icon

= 1.3.4 =

* Gated post interface now shows refresh link to non logged in visitors. This allows existing patrons to easily refresh the content or login as opposed to being sent to plegdge flow
* Updated interface message that is shown to non-qualifying patrons. These patrons will now be shown a message asking them to ugprade their tier as opposed to just showing them the default message. 
* Added 2 links to setup wizard to allow creators to easily log in or register at Patreon as a creator before starting setup
* Added utm params to existing links

= 1.3.3 =

* Added Reconnection feature to allow reconnection of site to Patreon to refresh API connection
* Updated creator access token refresh logic to start trying token refresh a week before expiration to prevent service disruptions
* Fixed an issue where creator profile url would go to 404 if vanity url was not being used
* Added an admin page to show messages/errors to admins during flows or other backend procedures

= 1.3.2 =

* Corrected the valid patron footer to use proper Patreon page name or custom Patreon page name
* Added utm parameters to Patreon page link in text over interface and valid patron footer

= 1.3.1 =

* Added error messages and setup re-initiation for 3 cases in which the site may not have been able to connect to Patreon
* Made disconnect button hide itself if any of credentials is missing or empty string

= 1.3.0 =

* Fixed an issue with some PHP versions crashing with error when tier descriptions are considerably formatted

= 1.2.9 =

* Easy setup wizard which allows new installations to easily connect to Patreon added.
* Setup wizard kicks in after activation.
* Informative links to answer questions about integration added to first screen of setup wizard.
* Quickstart, addons info added to setup wizard success screen
* All new installs will be using API v2 from now on
* Existing installations should work with API v2 normally without disruption
* Revamped API connection settings section in options.
* Connection settings in options now hidden in a toggle.
* Disconnect feature added to connection settings to allow disconnecting creator account from a site. This will allow disconnecting a site from a creator account and connecting it to another.
* A bug with saving creator tiers with largely formatted description was fixed. Tiers should now be pulled properly for such creators.
* Mailing list notice removed.

= 1.2.8 =

* Removed unused input parameters from a function - this should fix PHP warnings and other issues at some sites

= 1.2.7 =

* Locked posts now show your Patreon page name instead of full name. Added an option in settings to override the page/creator name. The order for deciding what name to show is as follows: Custom name if set in settings -> Patreon page name if exists -> First name -> 'this creator' default text if all fails.

= 1.2.6 =

* Minor bugfix for tier selection box not loading on some installations - this version will force refreshing of admin js to force tier box to load

= 1.2.5 =

* PW now allows you to lock your posts by your Patreon tiers
* $ based lock input field moved to advanced toggle
* Relevant Patreon tier now shows in locked posts instead of $ amount
* There is now a link to creator's Patreon profile in locked posts
* Notices revamped. Repeating update notice removed for compatibility with upcoming WP org rule. All notices permanently dismissable.
* One time addon info notice added to inform about Patron Pro addon
* Some undefined index notices fixed
* Beta string added to user string in API calls to be used for betas

= 1.2.4 =

* Plugin now automatically acquires Patreon avatar of Patreon users and uses it if they dont already have an avatar
* Addressed reports of client credentials being deleted and forcibly refreshed
* A rare issue which could cause spammy but harmless accounts being created when Patreon API was returning HTML was addressed
* Unused remove_fetch_creator_id was removed

= 1.2.3 =

* Hotfix - addressed a potential issue which could occur during Patreon maintenance, causing some sites to show Patreon maintenance page in admin or to logged in users. This would happen when the plugin attempted to refresh expired creator tokens or update a user's Patreon details or update any info via Patreon_OAuth class. 

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
