Patreon Wordpress is a free plugin built and maintained by Patreon. When a patron pledges, they can then head over to www.yourwordpresssite.com and click your “Connect with Patreon” button to let the site know they are a patron. When you create content on WordPress you will be given the option to limit it to only paying patrons.

This plugin is maintained by Patreon and will cover the basic use cases of patronage on WordPress. Looking for additional features? Extend with premium inter-operable WordPress plugins that others have developed, also found in this directory.

### FEATURES

- Keep your patrons engaged on your website
- Convert your fans to patrons by showing them how much content they’d get
- Publish patron-only posts with the full power of WordPress
- Option to provide an ad-free experience to patrons as an added value

### Installing the Patreon WordPress Plugin

1. Install the plugin
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

## Protecting Posts and Pages

When editing a post or page (or a custom post type) you will see a meta box in the right column titled ‘Patreon Level’. This box contains a text field that lets you specify a minimum contribution level in dollars. This could be $1 or $1.40 or even $10000. This is entirely determined by you. It defaults to $0 even if left empty.

![](https://c5.patreon.com/external/platform/wordpress_protecting_posts_pages.png)

## Protecting Images

Using a media management plugin that lets you specify which directory your media items are uploaded to, you can choose to upload them to the ‘patreon_protect’ folder residing within the ‘wp-content/uploads’ directory. Images in this directory can have the Patreon Level specified just as you would protect a post or page. This will not protect your images from screenshots or downloads by patrons themselves.

![](https://c5.patreon.com/external/platform/wordpress_protecting_images.png)

## Protecting Videos

It is  difficult to protect videos due the intensive bandwidth requirements of hosting video  and having to rely on third parties such as Youtube or Vimeo. Youtube allows you to set videos to ‘private’ but Vimeo offers extra controls by only allowing videos to be played on specific domains. Find a guide to protecting your video content with Vimeo and Patreon Connect here.


## Advanced Options

The following sections are for advanced WordPress users and not required for normal use of the plugin.

### WordPress Shortcodes

Including a patrons name inside posts. This will be an empty string if no user is logged in.
```
[patrons_name]
```
Adding a personal message into posts or pages. The below example will render like: ‘Hi there, Spencer, thanks for your support‘
```
[personal_message before_name="Hi there, " after_name=", thanks for your support"]
```
Embedding protected content within posts or pages:
```
[patreon_content slug="example-content-slug"]
```
Embedding protected content for a specific user:
(this works for all patreon_content embeds)
```
[patreon_content slug="example-content-slug" username="john_smith"]
```
Embedding protected content with an embedded post/page/custom post type
```
[patreon_content slug="example-content-slug" paywall_embed_post_slug="pitch-page" paywall_embed_post_type="page"]
```
Embedding protected content with an embedded media preview / pitch:
```
[patreon_content slug="example-content-slug" paywall_embed_url="https://www.youtube.com/watch?v=5qJp6xlKEug"]
```
Embedding protected content with a custom banner image:
```
[patreon_content slug="example-content-slug" paywall_image_url="https://placehold.it/500x500"]
```
Including a ‘Login with Patreon’ button within posts or pages:
(to include this shortcode in a sidebar widget, refer to the next code example)
```
[patreon_register_button]
```
You can include the ‘Login with Patreon’ button in a side bar widget by adding the below line of code to your themes functions.php file to ensure shortcodes are being filtered correctly.
```
add_filter('widget_text','do_shortcode');
```
Wrapping content in a Patrons only tag allows you to target post content to restrict from users. You can also specify ‘patreon content’ to show instead of the protected content, as a custom banner.
```
[[patrons only min_level="200"]]Example Paragraph of text[[/patrons_only]]
```
An example with the embedded patreon content as a banner
```
[[patrons only min_level="200" slug="temp-banner"]]Example Paragraph of text[[/patrons_only]]
```


### Plugin Filter Reference

Login Button HTML
```
ptrn/login_button, $button_html, $href
```
Walled Garden Page ID
Runs when returning the Page ID to the method that will redirect a user to a specific page if the ‘Walled Garden’ is enabled.
```
ptrn/walled_garden_page
```
Example function:
```
add_filter('ptrn/walled_garden_page', 'example_walled_garden_function');

function example_walled_garden_function($walled_garden_page, $user_patronage) {

    // change walled garden page ID to a specific post/page ID - lets just say its post with ID of 22
    // $user_patronage has the patrons contribution amount
    return 22;

}
```
Post/Page Content
```
ptrn/post_content, $content_html, $user_patronage
```

Campaign Banner
```
ptrn/campaign_banner, $campaign_banner_html, $patreon_level
```
Example function:
```
add_filter('ptrn/campaign_banner', 'ptrn_replace_campaign_banner', 10, 2);
function ptrn_replace_campaign_banner($campaign_banner_html, $patreon_level) {

        //this is the $ value to see the content
        //var_dump($patreon_level);

        //this is the html code that will be displayed for the banner
        //var_dump($campaign_banner_html);

        ob_start();

        echo '<p>THE NEW BANNER</p>';

        $campaign_banner_html = ob_get_contents();

        ob_end_clean();

        return $campaign_banner_html;

}
```
Redirect to Page after Login/Register
```
ptrn/ptrn_redirect, $page_id
```
