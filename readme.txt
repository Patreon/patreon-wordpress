=== Patreon WordPress ===
Contributors: patreon, codebard
Tags: patreon, membership, members
Requires at least: 4.0
Requires PHP: 7.4
Tested up to: 6.5.3
Stable tag: 1.9.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect your WordPress site to Patreon and increase your members and pledges!

== Description ==

With Patreon WordPress, you can bring Patreon features to your WordPress website and integrate them to make them work together. You can even easily import your existing Patreon posts and keep your Patreon posts synced to your WP site automatically! Your patron-only content at your WordPress site will encourage your visitors to become your patrons to unlock your content.

You can lock any single post or all of your posts! You can also lock any custom post type. Your visitors can log into your site via Patreon, making it easier for them to use your site in addition to accessing your locked content.

This plugin is developed and maintained by Patreon. 

= FEATURES FOR CREATORS =

- Choose one of your tiers or a minimum pledge amount necessary to access a post or custom post
- All patrons with pledge at or above that minimum tier will be able to access your post
- Alternatively, you can set a minimum pledge amount to see all posts
- Visitors who are not your patrons can click the "Unlock with Patreon" button on the locked post to pledge to you and access content
- Visitors will be automatically redirected to Patreon, pledge to you and come back to your site to original unlocked post
- Plugin will automatically log in Patreon users
- Import your existing Patreon posts, with Video and images
- Sync your Patreon posts as you go
- Choose the post type, category which posts will be synced to
- Choose the author to be used for synced posts
- Your posts will be automatically updated as you add/update/delete your Patreon posts
- Set custom HTML that non-patrons see instead of the post, prompting them to become a patron
- Patreon WordPress is compatible with Paid Memberships Pro - you can gate your content with either plugin
- Patreon pledges are matched with Paid Memberships Pro monthly memberships - works out of the box with no changes
- Any Patreon patron or Paid Memberships Pro member who qualifies for content via either plugin will access content

> *<b>You can post entirely independently on your WordPress site from your Patreon page.*</b> There is no need for WordPress and Patreon posts to correspond to one another in content or in locked status. The choice is up to you as a creator.

Got ideas? Post them on our [Patreon WordPress Ideas Thread](https://www.patreondevelopers.com/t/wordpress-plugin-feature-ideas/215)

= FEATURES FOR PATRONS =

- This plugin adds a "Unlock with Patreon" button to every post you lock.
- "Unlock with Patreon" takes care of everything: whether they’re not a patron yet, or they need to upgrade their pledge, or if they are already pledging enough, the plugin will guide them through the process and back to your content

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

= 1.9.1 =

* An issue that made it possible to circumvent image locking by sending a specific referrer header was fixed. Now locked images should not allow circumvention of the protection via referer header

= 1.9.0 =

* Now the reconnection wizard can be used to refresh/repair the connection of the site to Patreon without having to disconnect the site even if the site connection is broken or lost
* Updated reconnection wizard info and button text to make it clear that now reconnection can be used to refresh connection or connect the site from scratch
* Updated the routing logic to update the client ids correctly in the new format for both connection and reconnection cases
* Added a Gaussian blur value filter to allow modifying the blur setting of image locking

= 1.8.9 =

* Fixed the issue with Import next batch button not working immedieatly after starting a manual post import
* Added two new cases for error messages for needing admin privileges to start manual post sync and for the case of expired nonce
* Added a Cancel button to the manual post import interface.

* Minor CSRF vulnerability fixed

= 1.8.8 =

* Minor CSRF vulnerability fixed

= 1.8.7 =

* Issue with not being able to save some options in the options page was fixed. (Post sync options etc)
* Security nonces added to various actions and forms
* Fixed a potential warning in the locked post interface

= 1.8.6 =

* Added pledge info cache. Made getUserPatronage use pledge info cache. Added code to use current_user_pledge_amount for compatibility reasons if a user is not provided and current user is being used. Fixes the bug with providing $user to getUserPatronage and still ending up with current user's pledge result instead of the provided user's.
* Added filter to allow modification of app info collection results to be used in setup wizard.
* Alt attribute added to login button (contrib from androidacy-user at Github)
* Added aria labels for screen readers for accessibility

= 1.8.5 =

* Modified lock or not filter to feed more variables to functions.
* Added and calculated relevant variables during unlock process
* Added a check for the timestamp of saved patron info
* Now uses the saved patron info if the timestamp is within 2 seconds of current Unix time and does not call the API
* Now saves the timestamp of the time when a user has returned from any Patreon flow
* getPatreonUser now checks for that timestamp in order to decide whether to call the api or not

= 1.8.4 =

* Made currency sign selection a text input instead of select
* Modified the currency sign option to be currency sign at the front of the amount
* Added a currency sign option to be used at the end of the amount
* All text updated to use the currency sign that is saved in options. $ if default.
* Added call throttling to api calls to avoid spamming of the api by zombie or faulty sites
* Added throttled-return handling to relevant functions
* Added callouts for easy access to plugin upgrade

= 1.8.3 =

* Removed declined payment related checks to match behavior to the behavior at patreon.com. Patrons should keep access until declined payment retries are completed
* Made custom page name sanitization more strict per request from WP org repo

= 1.8.2 =

* Sanitization for arbitrary text and number input fields in options form to prevent against XSS attacks.

= 1.8.1 =

* Enhanced post id detection from attachment url. This will address various issues those who are using image locking were having with smaller size thumbnails of locked images, or non locked images of smaller attachment sizes.
* Hid the PCM addon upsell notification when PP addon is active

= 1.8.0 =

* Made the post syncer not overwrite $ level if an existing post has it. This will prevent overriding of already set post tier values with.
* Added sanitization to custom page name.

= 1.7.9 =

* Adds yearly pledge support from Kyle Warneck's (https://github.com/KyleW) contribution. Now yearly patrons' pledges will be properly calculated when they attempt to see posts locked with monthly tiers
* Misplaced, duplicate but functional post import code removed.

= 1.7.8 =

* Moved the currency replacement filter to lower priority. This will always catch and properly replace any currency text that is put into the interface - by addons or custom code.

= 1.7.7 =

* Added new addon notice. Now checks if notice being shown to avoid showing the same notice twice at a page load.

= 1.7.6 =

* Important bugfix for author select dropdown for post sync in Patreon settings admin page
* Added args to get_user when constructing post author dropdown. Now only gets users down to contributor level and excludes subscribers - this will prevent problems with sites with large number of users
* Added count limit to dropdown to limit the size of the select in the case of roles from super admin to contributor having too many users. 
* Args now sorts the users based on their nicename, ASC.
* Dropdown now shows user display name and nice name together.

= 1.7.5 =

* Minor bugfix in script handle for image script. Now it will not cause JS error in admin

= 1.7.4 =

* Minor bugfix for detecting attachment image id - may alleviate issue with detecting attachments in edge caching setups

= 1.7.3 =

* Corrected required PHP version in readme. It was listed as 5.7 despite being 5.4 and this was causing confusion

= 1.7.2 =

* Improved handling of pledges for patrons who have many pledges. This should address various 'Content cant be unlocked' issues.
* Sanitized input from image locking gating level modal
* Sanitized error messages returned from Patreon

= 1.7.1 =

* Fixed a bug with not being able to save post category for post sync during setup wizard

= 1.7.0 =

* Fixed a bug with post author for synced posts not being possible to set
* Added allowed toggle keys to frontend class. 
* Added allowed key check to advanced options toggle function that receives ajax call.
* Added nonce to advanced options toggle form. 
* Added code to transmit nonce to ajax backend function. 
* Added nonce check to toggle_option receiver function.
* Fixed a bug that prevented reconnect site option from being used in Patreon Options
* Added nonce check to reconnect site option for security
* Added nonce check to disconnect site option for security
* Added nonce check to synced post category saving option for security
* Added nonce check to synced post author saving option for security
* Added a check to see if an image exists in media library before serving an image with image lock feature
* Added clarifications to errors when serving images instead of just returning. Now wp_die's out with message
* Added check to image/file locking feature to see if image/file locking is enabled before allowing use of image/file locking function

= 1.6.9 =

* Fixed an issue with post sync import not deleting expired/lost cursor when detected. Now will automatically restart post import if cursor is lost
* Added fixed/lost cursor deletion info return to the condition that checks for it
* Added front end notice to admin when fixed/lost cursor is deleted.
* Made setup wizard notice dismissable. Now admins who manually saved/updated their app details into plugin can dismiss setup wizard notice

= 1.6.8 =

* Fixed an issue with lite plan creators' patrons not being able to unlock content
* Added info to Patreon level metabox in post editor on needing to upgrade to Pro plan at Patreon to be able to use different tiers
* Added info to Patreon level metabox in post editor on how to use custom pledge input box to gate content
* Prevented 'We must connect your site to Patreon' notice from appearing to non-admin users visiting WP admin pages

= 1.6.7 =

* Added an option to override the imported posts' dates with the dates from Patreon instead of using the date which the post is imported. This will allow syncing your posts with the dates at Patreon if you choose. Defaults to off
* Made image lock button appear only when image feature is enabled

= 1.6.6 =

* Addressed an issue with imported post images being duplicated in WP sites which had 'Organize media by date' on. Images should now import normally. Next import may cause duplicate images once.
* Added an 'Auto publish public posts' option to settings
* Added an 'Auto publish patron only posts' option to settings
* Post import now uses the new auto publish options to decide whether to publish imported posts automatically or not
* Imported public and patron only posts not set to auto publish is set to 'Pending' status

= 1.6.5 =

* Addressed an issue with patrons with custom pledge not being able to access gated content due to currency differences
* Fixed double image import problem when syncing posts. Now uses image hashes to identify unique images. This will cause re-importing of images once if a full import is re-done. Deleting existing Patreon imported images and then doing full-reimport if you synced your posts before is recommended.
* Now shows WordPress, PHP, and Patreon plugins' version info in health check page.
* WP, PHP and plugin version info is added to the support info copied when 'Copy' support info is clicked
* Added a support block with above support info in copy-able form to main settings page with a link to support forum.
* Made the error logging more detailed when logging api related errors and access issues
* Now shows uuid and the caller function when logging api access errors

= 1.6.4 =

* Updated user pledge level check to work with different currencies
* User pledge level check now uses tiers and converts it to $ value to match highest local tier
* Enables currency feature compatibility for all existing installations and v1 and v2 clients

= 1.6.3 =

* Fixed an issue with connecting/reconnecting the site to Patreon using the setup wizard in Multisite installations
* Multisite network admins can now connect subsites to Patreon using the setup wizard or connect/reconnect options
* Subsite admins can now connect subsites to Patreon using the setup wizard or connect/reconnect options
* Disconnect function in multisite now works while using them as Network admins and subsite admins
* Added an exception to locked post interface text for 'Any patron' tier gated content. This fixes the 'You have to be patron of creator from $0.01 or more' issue in interface text

= 1.6.2 =

* Added image locking compatibility code for Jetpack image CDN and lazy loading
* Now tells Jetpack to not use CDN for locked images to allow proper unlocking of locked images
* Added css to turn mouse cursor into hand pointer when a locked image is hovered upon

= 1.6.1 =

* Added manual post import feature
* Start a post import section in settings now transforms to 'Ongoing post import' section when an import is started
* Start Import button transforms into 'Import next batch' button when an import is started
* Can click 'Import next batch' button to manually import next batch of posts - every 10 seconds
* Made possible to manually import all the posts by clicking 'Import next batch' button
* Info on ongoing post import and next batch import is given to in the status section under the setting
* Transforms the setting section to original 'Start a post import' version from 'Ongoing post import' version
* Works alongside automatic import

= 1.6.0 =

* Fixed an issue with image importing stopping post sync
* Fixed an issue with images not being imported properly
* Fixed an issue with saving settings stopping ongoing post import
* Now sets featured image for imported posts properly
* Now uses unique indicator at Patreon cdn to identify and import images
* Now marks images in imported patron only posts as patron only. Requires image lock feature to be active to take effect
* Now uses DOM to detect images in imported post content

= 1.5.8 =

* Fixed an issue with image importing when syncing posts. Images should now import properly.
* Now uses image's Patreon unique id when importing the image. This will allow accurate import of images. May re-import some images.
* Added option to auto-set featured image for imported/synced post from within the images inside the post.
* Added checks to disable post sync functions if site is using api v1
* Added warning to post import section and post import function about upgrading to api v2 to use post sync
* Added admin notification to warn about using post sync with apiv1

= 1.5.7 =

* Added disconnect feature to allow disconnecting the Patreon account connected to local WP account
* Users can disconnect their Patreon accounts from their profile page
* Users can connect their Patreon accounts from their WP profile page
* Admins can disconnect any user's Patreon account from tat user's WP profile page
* Admins cant reconnect another user's Patreon account
* Conditional text for users and admins in connect/disconnect interface
* Added conditional warning to post sync wizard screen to show for installations that still use API v1 about post sync requiring API v2 and v1 causing errors
* Added conditional warning to options about post sync requiring api v2
* Linked to guide from post sync api v2 requirement warnings
* Fixed minor PHP warnings which appeared when a v1 site was not able to connect to v2 during setup

= 1.5.6 =

* Plugin now syncs posts from Patreon to WP site
* Added support for syncing text, video (Youtube, Vimeo), link post types at Patreon. These posts are replicated exactly as they are
* All other post types at Patreon are currently synced with their title and content only
* Gets proper embed info for video posts from Youtube and Vimeo and embeds into proper place in post content
* All images in any given post type is replicated to local media library and inserted into proper places in post content from WP media library
* Syncs patron only status of posts. Tiers currently not supported.
* Syncs paid per post type posts' patron only status
* Added post import functions
* Added Patreon cron job to import posts in the background
* Hooked post import function to Patreon cron job
* Cron job checks if an import is going on and processes the next batch of posts as needed. Currently 20 posts per every 5 minutes
* Added webhooks to sync newly added posts, deleted posts and updated posts without needing to start a post import
* Added intermediary screen to setup wizard to set post import preferences during initial plugin setup
* Intermediary setup wizard screen allows setting of post sync preferences for update/delete, post type and category
* Intermediary setup wizard screen starts an immediate post import if the user chooses to sync posts
* Added options to manage post sync - turn post sync on/off, set updating posts on/off, set deleting posts on/off
* Added options to set which post type and category (or taxonomy) the synced posts should be added
* Added option to set the author to be used for imported posts
* Added an option to start a manual import
* Added status indicators for import progress to option screen
* Made it possible to do manual import of posts without turning on syncing
* Made it possible to unlock PW only gated content with a PMP membership from the same $ level
* Combined category/taxonomy setting code to simpler wp_set_object_terms
* Various bugs about creating/inserting into new category/terms fixed. JS adjusted accordingly.

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

= How can I report security bugs? =

You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/patreon-connect)

== Changelog ==

= 1.9.1 =

* An issue that made it possible to circumvent image locking by sending a specific referrer header was fixed. Now locked images should not allow circumvention of the protection via referer header

= 1.9.0 =

* Now the reconnection wizard can be used to refresh/repair the connection of the site to Patreon without having to disconnect the site even if the site connection is broken or lost
* Updated reconnection wizard info and button text to make it clear that now reconnection can be used to refresh connection or connect the site from scratch
* Updated the routing logic to update the client ids correctly in the new format for both connection and reconnection cases
* Added a Gaussian blur value filter to allow modifying the blur setting of image locking

= 1.8.9 =

* Fixed the issue with Import next batch button not working immedieatly after starting a manual post import
* Added two new cases for error messages for needing admin privileges to start manual post sync and for the case of expired nonce
* Added a Cancel button to the manual post import interface.

= 1.8.8 =

* Minor CSRF vulnerability fixed

= 1.8.7 =

* Issue with not being able to save some options in the options page was fixed. (Post sync options etc)
* Security nonces added to various actions and forms
* Fixed a potential warning in the locked post interface

= 1.8.6 =

* Added pledge info cache. Made getUserPatronage use pledge info cache. Added code to use current_user_pledge_amount for compatibility reasons if a user is not provided and current user is being used. Fixes the bug with providing $user to getUserPatronage and still ending up with current user's pledge result instead of the provided user's.
* Added filter to allow modification of app info collection results to be used in setup wizard.
* Alt attribute added to login button (contrib from androidacy-user at Github)
* Added aria labels for screen readers for accessibility

= 1.8.5 =

* Modified lock or not filter to feed more variables to functions.
* Added and calculated relevant variables during unlock process
* Added a check for the timestamp of saved patron info
* Now uses the saved patron info if the timestamp is within 2 seconds of current Unix time and does not call the API
* Now saves the timestamp of the time when a user has returned from any Patreon flow
* getPatreonUser now checks for that timestamp in order to decide whether to call the api or not

= 1.8.4 =

* Made currency sign selection a text input instead of select
* Modified the currency sign option to be currency sign at the front of the amount
* Added a currency sign option to be used at the end of the amount
* All text updated to use the currency sign that is saved in options. $ if default.
* Added call throttling to api calls to avoid spamming of the api by zombie or faulty sites
* Added throttled-return handling to relevant functions
* Added callouts for easy access to plugin upgrade

= 1.8.3 =

* Removed declined payment related checks to match behavior to the behavior at patreon.com. Patrons should keep access until declined payment retries are completed
* Made custom page name sanitization more strict per request from WP org repo

= 1.8.2 =

* Sanitization for arbitrary text and number input fields in options form to prevent against XSS attacks.

= 1.8.1 =

* Enhanced post id detection from attachment url. This will address various issues those who are using image locking were having with smaller size thumbnails of locked images, or non locked images of smaller attachment sizes.
* Hid the PCM addon upsell notification when PP addon is active

= 1.8.0 =

* Made the post syncer not overwrite $ level if an existing post has it. This will prevent overriding of already set post tier values with.
* Added sanitization to custom page name.

= 1.7.9 =

* Adds yearly pledge support from Kyle Warneck's (https://github.com/KyleW) contribution. Now yearly patrons' pledges will be properly calculated when they attempt to see posts locked with monthly tiers
* Misplaced, duplicate but functional post import code removed.

= 1.7.8 =

* Moved the currency replacement filter to lower priority. This will always catch and properly replace any currency text that is put into the interface - by addons or custom code.

= 1.7.7 =

* Added new addon notice. Now checks if notice being shown to avoid showing the same notice twice at a page load.

= 1.7.6 =

* Important bugfix for author select dropdown for post sync in Patreon settings admin page
* Added args to get_user when constructing post author dropdown. Now only gets users down to contributor level and excludes subscribers - this will prevent problems with sites with large number of users
* Added count limit to dropdown to limit the size of the select in the case of roles from super admin to contributor having too many users. 
* Args now sorts the users based on their nicename, ASC.
* Dropdown now shows user display name and nice name together.

= 1.7.5 =

* Minor bugfix in script handle for image script. Now it will not cause JS error in admin

= 1.7.4 =

* Minor bugfix for detecting attachment image id - may alleviate issue with detecting attachments in edge caching setups

= 1.7.3 =

* Corrected required PHP version in readme. It was listed as 5.7 despite being 5.4 and this was causing confusion

= 1.7.2 =

* Improved handling of pledges for patrons who have many pledges. This should address various 'Content cant be unlocked' issues.
* Sanitized input from image locking gating level modal
* Sanitized error messages returned from Patreon

= 1.7.1 =

* Fixed a bug with not being able to save post category for post sync during setup wizard

= 1.7.0 =

* Fixed a bug with post author for synced posts not being possible to set
* Added allowed toggle keys to frontend class. 
* Added allowed key check to advanced options toggle function that receives ajax call.
* Added nonce to advanced options toggle form. 
* Added code to transmit nonce to ajax backend function. 
* Added nonce check to toggle_option receiver function.
* Fixed a bug that prevented reconnect site option from being used in Patreon Options
* Added nonce check to reconnect site option for security
* Added nonce check to disconnect site option for security
* Added nonce check to synced post category saving option for security
* Added nonce check to synced post author saving option for security
* Added a check to see if an image exists in media library before serving an image with image lock feature
* Added clarifications to errors when serving images instead of just returning. Now wp_die's out with message
* Added check to image/file locking feature to see if image/file locking is enabled before allowing use of image/file locking function

= 1.6.9 =

* Fixed an issue with post sync import not deleting expired/lost cursor when detected. Now will automatically restart post import if cursor is lost
* Added fixed/lost cursor deletion info return to the condition that checks for it
* Added front end notice to admin when fixed/lost cursor is deleted.
* Made setup wizard notice dismissable. Now admins who manually saved/updated their app details into plugin can dismiss setup wizard notice

= 1.6.8 =

* Fixed an issue with lite plan creators' patrons not being able to unlock content
* Added info to Patreon level metabox in post editor on needing to upgrade to Pro plan at Patreon to be able to use different tiers
* Added info to Patreon level metabox in post editor on how to use custom pledge input box to gate content
* Prevented 'We must connect your site to Patreon' notice from appearing to non-admin users visiting WP admin pages

= 1.6.7 =

* Added an option to override the imported posts' dates with the dates from Patreon instead of using the date which the post is imported. This will allow syncing your posts with the dates at Patreon if you choose. Defaults to off
* Made image lock button appear only when image feature is enabled

= 1.6.6 =

* Addressed an issue with imported post images being duplicated in WP sites which had 'Organize media by date' on. Images should now import normally. Next import may cause duplicate images once.
* Added an 'Auto publish public posts' option to settings
* Added an 'Auto publish patron only posts' option to settings
* Post import now uses the new auto publish options to decide whether to publish imported posts automatically or not
* Imported public and patron only posts not set to auto publish is set to 'Pending' status

= 1.6.5 =

* Addressed an issue with patrons with custom pledge not being able to access gated content due to currency differences
* Fixed double image import problem when syncing posts. Now uses image hashes to identify unique images. This will cause re-importing of images once if a full import is re-done. Deleting existing Patreon imported images and then doing full-reimport if you synced your posts before is recommended.
* Now shows WordPress, PHP, and Patreon plugins' version info in health check page.
* WP, PHP and plugin version info is added to the support info copied when 'Copy' support info is clicked
* Added a support block with above support info in copy-able form to main settings page with a link to support forum.
* Made the error logging more detailed when logging api related errors and access issues
* Now shows uuid and the caller function when logging api access errors

= 1.6.4 =

* Updated user pledge level check to work with different currencies
* User pledge level check now uses tiers and converts it to $ value to match highest local tier
* Enables currency feature compatibility for all existing installations and v1 and v2 clients

= 1.6.3 =

* Fixed an issue with connecting/reconnecting the site to Patreon using the setup wizard in Multisite installations
* Multisite network admins can now connect subsites to Patreon using the setup wizard or connect/reconnect options
* Subsite admins can now connect subsites to Patreon using the setup wizard or connect/reconnect options
* Disconnect function in multisite now works while using them as Network admins and subsite admins
* Added an exception to locked post interface text for 'Any patron' tier gated content. This fixes the 'You have to be patron of creator from $0.01 or more' issue in interface text

= 1.6.2 =

* Added image locking compatibility code for Jetpack image CDN and lazy loading
* Now tells Jetpack to not use CDN for locked images to allow proper unlocking of locked images
* Added css to turn mouse cursor into hand pointer when a locked image is hovered upon

= 1.6.1 =

* Added manual post import feature
* Start a post import section in settings now transforms to 'Ongoing post import' section when an import is started
* Start Import button transforms into 'Import next batch' button when an import is started
* Can click 'Import next batch' button to manually import next batch of posts - every 10 seconds
* Made possible to manually import all the posts by clicking 'Import next batch' button
* Info on ongoing post import and next batch import is given to in the status section under the setting
* Transforms the setting section to original 'Start a post import' version from 'Ongoing post import' version
* Works alongside automatic import

= 1.6.0 =

* Fixed an issue with image importing stopping post sync
* Fixed an issue with images not being imported properly
* Fixed an issue with saving settings stopping ongoing post import
* Now sets featured image for imported posts properly
* Now uses unique indicator at Patreon cdn to identify and import images
* Now marks images in imported patron only posts as patron only. Requires image lock feature to be active to take effect
* Now uses DOM to detect images in imported post content

= 1.5.8 =

* Fixed an issue with image importing when syncing posts. Images should now import properly.
* Now uses image's Patreon unique id when importing the image. This will allow accurate import of images. May re-import some images.
* Added option to auto-set featured image for imported/synced post from within the images inside the post.
* Added checks to disable post sync functions if site is using api v1
* Added warning to post import section and post import function about upgrading to api v2 to use post sync
* Added admin notification to warn about using post sync with apiv1

= 1.5.7 =

* Added disconnect feature to allow disconnecting the Patreon account connected to local WP account
* Users can disconnect their Patreon accounts from their profile page
* Users can connect their Patreon accounts from their WP profile page
* Admins can disconnect any user's Patreon account from tat user's WP profile page
* Admins cant reconnect another user's Patreon account
* Conditional text for users and admins in connect/disconnect interface
* Added conditional warning to post sync wizard screen to show for installations that still use API v1 about post sync requiring API v2 and v1 causing errors
* Added conditional warning to options about post sync requiring api v2
* Linked to guide from post sync api v2 requirement warnings
* Fixed minor PHP warnings which appeared when a v1 site was not able to connect to v2 during setup

= 1.5.6 =

* Plugin now syncs posts from Patreon to WP site
* Added support for syncing text, video (Youtube, Vimeo), link post types at Patreon. These posts are replicated exactly as they are
* All other post types at Patreon are currently synced with their title and content only
* Gets proper embed info for video posts from Youtube and Vimeo and embeds into proper place in post content
* All images in any given post type is replicated to local media library and inserted into proper places in post content from WP media library
* Syncs patron only status of posts. Tiers currently not supported.
* Syncs paid per post type posts' patron only status
* Added post import functions
* Added Patreon cron job to import posts in the background
* Hooked post import function to Patreon cron job
* Cron job checks if an import is going on and processes the next batch of posts as needed. Currently 20 posts per every 5 minutes
* Added webhooks to sync newly added posts, deleted posts and updated posts without needing to start a post import
* Added intermediary screen to setup wizard to set post import preferences during initial plugin setup
* Intermediary setup wizard screen allows setting of post sync preferences for update/delete, post type and category
* Intermediary setup wizard screen starts an immediate post import if the user chooses to sync posts
* Added options to manage post sync - turn post sync on/off, set updating posts on/off, set deleting posts on/off
* Added options to set which post type and category (or taxonomy) the synced posts should be added
* Added option to set the author to be used for imported posts
* Added an option to start a manual import
* Added status indicators for import progress to option screen
* Made it possible to do manual import of posts without turning on syncing
* Made it possible to unlock PW only gated content with a PMP membership from the same $ level
* Combined category/taxonomy setting code to simpler wp_set_object_terms
* Various bugs about creating/inserting into new category/terms fixed. JS adjusted accordingly.

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
