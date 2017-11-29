=== Plugin Name ===
Contributors: wordpressorg@patreon.com
Tags: patreon, membership, members
Requires at least: 4.0
Tested up to: 4.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Serve patron-only posts - and give ad-free experiences - directly on your website.

== Description ==

When a patron pledges, they can then head over to www.yourwordpresssite.com and click your “Connect with Patreon” button to let the site know they are a patron. When you create content on WordPress you will be given the option to limit it to only paying patrons.

This plugin is maintained by Patreon and will cover the basic use cases of patronage on WordPress. Looking for additional features? Extend with premium inter-operable WordPress plugins that others have developed, also found in this directory.

A few notes about the sections above:
*   Keep your patrons engaged on your website
*   Convert your fans to patrons by showing them how much content they’d get
*   Publish patron-only posts with the full power of WordPress

== Installation ==

1. Download the latest version of the plugin on this page
2. In your Wordpress admin panel, use the option to ‘Upload Plugin’ on the ‘add new’ plugin screen, image below for reference. When you click ‘upload plugin’, select the plugins ZIP file:
![](https://c5.patreon.com/external/platform/wordpress-install-ss.png)
3. Activate the plugin and then click on ‘Patreon Settings’ to view the options page. It will look something like the below, copy the ‘Redirect URI‘ into the clip board and keep for the next step.
![](https://c5.patreon.com/external/platform/wordpress-install-ss1.jpeg)

### Creating an oAuth client on Patreon

1. Ensure you are logged into Patreon, using your creator account. When it comes to testing this out you will want to have two Patreon accounts, one that acts as a patron and one being the content creator. The oAuth client needs to be created on your content creating account.
2. Visit the [oAuth client page here](https://www.patreon.com/portal/registration/register-clients) and click "Create Client", fill out the form and add in an image URL for the icon that will appear to users when they are connecting to your website.
3. Note the field for ‘Redirect URI‘, this should still be in your clipboard from the previous steps – if not copy/paste it exactly as it appears on the options page.
4. Click the ‘Create Client’ button, and you should now see the new client in the list.
5. Click the downward facing caret on the right side to expose additional information about the client
6. You should see something like this
![](https://uiux.me/wp-content/uploads/2017/06/wordpress-install-ss4.png)
The keys in the image are completely fake, doctored specifically for this screenshot.

### Finishing up
1. Copy and paste the Client ID and Client Secret from this page into the matching fields on the ‘Patreon Settings’ page.
2. Copy the Client ID from the top of this page and paste into the Creator ID field.
3. Hit ‘Update Settings’
4. Then go to wordpress ‘Settings’ -> ‘Permalinks’ and hit ‘Save’. This will ensure your rewrite rules are flushed.

You should now be up and running with the Patreon WordPress plugin!

== Changelog ==

= 1.0 =
* Plugin launched.
