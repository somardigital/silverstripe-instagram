<?php

use MetzWeb\Instagram\Instagram as Instagram;

class InstagramPageExtension extends DataExtension
{
    /**
     * @var array
     */
    private static $casting = [
        'instagramShortcodeHandler' => 'HTMLText',
    ];

    /**
     * Gets a list of media from the cache.
     *
     * @param InstagramAccount $instagramAccount
     * @param string $maxID
     * @return array|null
     */
    public static function getMediaFromCache($instagramAccount, $maxID)
    {
        $cache = SS_Cache::factory('InstagramPage');

        if (!($result = $cache->load("{$instagramAccount->getField('Title')}_{$maxID}"))) {
            return null;
        }

        return unserialize($result);
    }

    /**
     * Gets a list of media from Instagram.
     *
     * @param InstagramAccount $instagramAccount
     * @param string $maxID
     * @return array|null
     */
    public static function getMediaFromInstagram($instagramAccount, $maxID)
    {
        $response = $instagramAccount->getMedia($maxID);

        if (!$response) {
            return null;
        }

        $raw = $response->getRaw();

        if (!$raw || !array_key_exists('meta', $raw) || $raw['meta']['code'] !== 200) {
            user_error('Unable to fetch media from Instagram', E_USER_WARNING);
            return null;
        }

        return $raw['data'];
    }

    /**
     * Updates the cache with media fetched from Instagram.
     *
     * @param InstagramAccount $instagramAccount
     * @param string $maxID
     * @param array $media
     * @return null
     */
    public static function updateCachedMedia($instagramAccount, $maxID, $media)
    {
        $cache = SS_Cache::factory('InstagramPage');
        $cache->save(serialize($media), "{$instagramAccount->getField('Title')}_{$maxID}");
    }

    /**
     * Gets a list of media from the cache if available. Falls back to making an API request.
     *
     * @param InstagramAccount $instagramAccount
     * @param string $maxID
     * @return array|null
     */
    public static function getMedia($instagramAccount, $maxID) {
        // Return media from the cache if it's available.
        if ($media = self::getMediaFromCache($instagramAccount, $maxID)) {
            return $media;
        }

        // Fetch media from Instagram and cache it.
        if ($media = self::getMediaFromInstagram($instagramAccount, $maxID)) {
            self::updateCachedMedia($instagramAccount, $maxID, $media);
        }

        return $media;
    }

    /**
     * Handler for the `instagram` Shortcode.
     *
     * @return string|null
     */
    public static function instagramShortcodeHandler($args, $content = null, $parser = null, $tag)
    {
        if (!array_key_exists('username', $args)) {
            return null;
        }

        $instagramAccount = InstagramAccount::get()->filter('Title', $args['username'])->First();

        // Make sure the InstagramAccount is valid and has been authorised.
        if (!$instagramAccount || !$instagramAccount->getField('AccessToken')) {
            return null;
        }

        $controller = Controller::curr();
        $link = $controller->Link();

        // Valid IDs only have digits and an underscore.
        $maxID = preg_replace('/[^\d_]/', '', $controller->getRequest()->getVar('start'));

        $media = self::getMedia($instagramAccount, $maxID);

        if (!$media) {
            return null;
        }

        // If there are less items returned than the maximum allowed per page,
        // we're on the last page, so don't include a 'next' button.
        //
        // TODO:
        // Handle edge case where it's the last page and exactly "items_per_page" are returned.
        $loadMoreLink = count($media) < Config::inst()->get('InstagramAccount', 'items_per_page')
            ? null
            : $link . '?start=' . array_pop((array_slice($media, -1)))['id'];

        // Make tags iterable in the template.
        foreach ($media as &$item) {
            $item['tags'] = ArrayList::create(
                array_map(function ($tag) {
                    return ArrayData::create(['name' => $tag]);
                }, $item['tags'])
            );
        }

        $data = ArrayData::create([
            'loadMoreLink' => $loadMoreLink,
            'instagramLink' => "https://www.instagram.com/{$args['username']}"
        ]);

        $data->setField('media', ArrayList::create($media));

        return $data->renderWith('InstagramPage');
    }
}
