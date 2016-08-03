/**
 * Provides TinyMCE with a button to insert an Instagram shortcode.
 */

(function (window, $) {
  'use strict';

  window.tinymce.create('tinymce.plugins.InstagramPlugin', {
    init: function (editor, url) {
      this.editor = editor;

      editor.addCommand('insertInstagram', function () {
        editor.execCommand(
          'mceInsertContent',
          false,
          '[instagram username="insert-username-here"]'
        );
      });

      editor.addButton('instagramButton', {
        title: 'Insert Instagram feed',
        image: 'silverstripe-instagram/images/instagram-logo.png',
        cmd: 'insertInstagram',
      });
    }
  });

   window.tinymce.PluginManager.add('instagramButton', window.tinymce.plugins.InstagramPlugin);
}(window, jQuery))
