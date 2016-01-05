# Installation Instructions

0. Download via the "Download ZIP" link on the right side of this page
1. Register an OAuth client if you haven't already at https://www.patreon.com/platform/documentation/clients
2. Add the plugin to your wordpress
3. When you refresh your admin panel, there should now be a "Patreon settings" on the left side, near the bottom. Fill out your Client ID, Client Secret, Creator Access Token, and Creator Refresh Token and hit Save

# Usage Instructions

* You should now see on your login page a "Connect with Patreon" button (this is the image that is currently very gross... will get a better one shortly). Your patrons can use this to log in to your WordPress with their Patreon account.
  * If the patron didnt have a WordPress account yet, it will make a new one and log them in
  * If the patron did have a WordPress account with a matching email address to the one they used on Patreon, it will keep their existing WordPress account, and add the patronage details to that user
  * Here's the rough part: if the patron did have a WP account, but under a different email address, then it will not know to connect to that different WP account (as it has no way of knowing that), and will instead make a new WP account for them. Eventually, we'll add a "connect your Patreon" button to the WP user's account page (rather than it only being on your login page), and clicking the button there will merge the patronage details even if the emails don't match. Hopefully that explanation makes some sense...
* You should also now see a "Patreon Content" link near the top of your dashboard menu. The plugin is designed to let you have only pieces of posts be hidden from non-patrons, rather than hiding the existence of the post entirely. So the flow is
  * "Add New Patreon Content"
  * Fill out the patron-only content
  * Use the "Patreon Level" widget on the right side to choose what level of patronage to restrict visibility to
  * To use this piece of content in a post, put the slug in your post, like so: `[patreon_content slug="this-content-is-for-patrons-only"]` (the thing in quotes can be found highlighted in yellow under the title field in the Add New Patreon Content page (not the full URL, just the yellow bit))
