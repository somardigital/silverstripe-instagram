# Documentation

## Installation

`composer require somardesignstudios/silverstripe-instagram`

## Configuration

### Instagram

First you need to
[register a new Instagram Client](https://www.instagram.com/developer/clients/register/). The client
acts as a proxy between your site and the Instagram API.

In the __Valid redirect URIs__ field enter your local, development, and production domains followed
by the __/admin/instagram/InstagramAccount/OAuth__ path. This is where CMS users are redirected
after granting access to an Instagram account.

You might enter three domains like this:

http://mysite.local/admin/instagram/InstagramAccount/OAuth (development)
http://dev.mysite.com/admin/instagram/InstagramAccount/OAuth (UAT)
http://mysite.com/admin/instagram/InstagramAccount/OAuth (production)

After creating the client, click the __Edit__ button to update some more details.

On the __Security__ tab there is an checkbox for __Enforce signed requests__.
Make sure you have this checked.

Take note of you __Client ID__ and __Client Secret__ as we'll need them in the next step.

### SilverStripe

Add your Instagram client details and extend the `Page` class.

__mysite/_config/config.yml__

```yml
InstagramAccount:
  client_id: 'YOUR_INSTAGRAM_CLIENT_ID'
  client_secret: 'YOUR_INSTAGRAM_CLIENT_SECRET'

Page:
  extensions:
    - InstagramPageExtension
```

Instagram feeds are inserted using a Shortcode, which you need to register. You can also add a button to TinyMCE which inserts the Shortcode into your content.

__mysite/_config.php__

```php
ShortcodeParser::get('default')
  ->register('instagram', ['InstagramPageExtension', 'instagramShortcodeHandler']);

HtmlEditorConfig::get('cms')->insertButtonsAfter('fullscreen', 'instagramButton');
HtmlEditorConfig::get('cms')->enablePlugins([
  'instagramButton' => '../../../silverstripe-instagram/javascript/instagramPlugin.js',
]);
```

Run a `dev/build` and everything is ready to go. See the [User Guide](user-guide.md) for
instructions on setting up and authorising an account in the CMS.

## Gotchas

Instagram clients are created in [sandbox mode](https://www.instagram.com/developer/sandbox/). This
put restrictions on how many items you can fetch and has reduced API limits.
