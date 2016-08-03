<?php

use Larabros\Elogram\Client;

class InstagramAccount extends DataObject
{
    /**
     * @config
     * @var string
     */
    private static $client_id;

    /**
     * @config
     * @var string
     */
    private static $client_secret;

    /**
     * @config
     * @var string
     */
    private static $redirect_path;

    /**
     * @config
     */
    private static $items_per_page = 9;

    /**
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar',
        'AccessToken' => 'Text',
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('AccessToken');

        if ($this->AccessToken) {
            $token = json_decode($this->AccessToken);

            $usernameField = LiteralField::create(
                'Title',
                '<div class="field">' .
                    '<label class="left">' .
                        _t('Instagram.FieldLabelTitle', 'Username') .
                    '</label>' .
                    '<div class="middleColumn" style="padding-top:8px;">' .
                        '<a ' .
                            "href='https://www.instagram.com/{$token->user->username}' " .
                            'title="View on Instagram" ' .
                            'target="_blank">' .
                            $token->user->username .
                        '</a>' .
                    '</div>' .
                '</div>'
            );
        } else {
            $usernameField = Textfield::create('Title', _t('Instagram.FieldLabelTitle', 'Username'));
            $usernameField->setDescription(
                _t(
                    'Instagram.FieldDescriptionTitle',
                    'The Instagram account you want to pull media from.'
                )
            );
        }

        $fields->addFieldToTab('Root.Main', $usernameField);

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * @return FieldList
     */
    public function getCMSActions()
    {
        $actions = parent::getCMSActions();

        if (!$this->ID || $this->AccessToken) {
            $this->extend('updateCMSActions', $actions);
            return $actions;
        }

        $client = self::getNewInstagramClient();
        $loginURL = $client->getLoginUrl();

        $this->setSessionOAuthState($this->getOAuthStateValueFromLoginURL($loginURL));

        $actions->push(
            LiteralField::create(
              'OAuthLink',
              '<a class="ss-ui-button" href="' . $loginURL . '">' .
                _t('Instagram.ButtonLabelAuthoriseAccount', 'Authorise account') .
              '</a>'
            )
        );

        $this->extend('updateCMSActions', $actions);

        return $actions;
    }

    /**
     * @return RequiredFields
     */
    public function getCMSValidator()
    {
        return new RequiredFields('Title');
    }

    /**
     * Ensures the person to authorising the account is logged into Instagram as the correct user
     * before setting the token.
     *
     * For example if the user is logged into Instagram as 'FooUser' and they attempt to set a token
     * for the InstagramAccount record 'InstagramAccount', the token will contain data relating to
     * the 'FooUser' account. So if this happen we display a message telling the user they need to
     * log out of Instagram before they can authorise another account.
     *
     * @param string $token
     */
    public function updateAccessToken($token, $state)
    {
        $newToken = json_decode($token);

        if ($state !== $this->getSessionOAuthState() ||
            $newToken->user->username !== $this->getField('Title')) {
            throw new Exception('Trying to set token on wrong InstagramAccount');
        }

        if (!$currentToken = $this->getField('AccessToken')) {
            $this->setField('AccessToken', $token);
            return;
        }

        $currentToken = json_decode($currentToken);

        if ($newToken->user->id !== $currentToken->user->id) {
            throw new Exception('Trying to set token on wrong InstagramAccount');
        }

        $this->setField('AccessToken', $token);
    }

    /**
     * Create a configured Instagram API interface.
     *
     * @param string $token
     * @return MetzWeb\Instagram\Instagram
     */
    public static function getNewInstagramClient($token = null)
    {
        $client_id = Config::inst()->get('InstagramAccount', 'client_id');
        $client_secret = Config::inst()->get('InstagramAccount', 'client_secret');
        $redirect_path = Config::inst()->get('InstagramAccount', 'redirect_path');

        if (!$client_id) {
            user_error(
                'Add a client_id to config (InstagramAdmin::client_id)',
                E_USER_ERROR
            );
        }

        if (!$client_secret) {
            user_error(
                'Add a client_secret to config (InstagramAdmin::client_secret)',
                E_USER_ERROR
            );
        }

        return new Client(
            $client_id,
            $client_secret,
            $token,
            Director::absoluteBaseURL() . $redirect_path
        );
    }

    /**
     * Gets the 'state' value from an AOuth login URL.
     *
     * @param string $loginURL
     * @return string|null
     */
    private function getOAuthStateValueFromLoginURL($loginURL = null)
    {
        if (!$loginURL) {
            return null;
        }

        $parts = parse_url($loginURL);
        parse_str($parts['query'], $query);

        return array_key_exists('state', $query)
            ? $query['state']
            : null;
    }

    /**
     * Gets the InstsgramAccount's OAuth state from Session.
     *
     * @return string|null
     */
    public function getSessionOAuthState()
    {
        $instagramAccounts = Session::get('InstagramAccounts');

        if (!$this->ID || !$instagramAccounts || !array_key_exists($this->ID, $instagramAccounts)) {
            return null;
        }

        return $instagramAccounts[$this->ID];
    }

    /**
     * Gets the authorised user's Instagram ID from the AccessToken.
     *
     * @return string|null
     */
    public function getInstagramID()
    {
        if (!$token = $this->getField('AccessToken')) {
            return null;
        }

        return json_decode($token)->user->id;
    }

    /**
     * Checks if the passed ID is a valid pattern.
     *
     * @param string $mediaID
     * @return boolean
     */
    public function isValidMediaID($mediaID)
    {
        if (!$instagramID = $this->getInstagramID()) {
            return false;
        }

        $pattern = '/^(\d{18}|\d{19})_' . $instagramID . '$/';

        return preg_match($pattern, $mediaID) === 1;
    }

    /**
     * Sets a Session variable which is used to keep track of
     * the InstagramAccount through the OAuth flow.
     *
     * @param string $state
     */
    private function setSessionOAuthState($state = null)
    {
        if (!$this->ID || !$state) {
            return null;
        }

        $instagramAccounts = Session::get('InstagramAccounts');
        $instagramAccounts = $instagramAccounts ? $instagramAccounts : [];

        $instagramAccounts[$this->ID] = $state;

        Session::set('InstagramAccounts', $instagramAccounts);
    }

    /**
     * Gets a list of media from the user's Instagram.
     *
     * @param string $maxID Return media earlier than this ID
     * @return Larabros\Elogram\Http\Response|null
     */
    public function getMedia($maxID = null)
    {
        if (
            !is_string($this->getField('AccessToken')) ||
            ($maxID && !$this->isValidMediaID($maxID))
        ) {
            return null;
        }

        $client = $this->getNewInstagramClient($this->getField('AccessToken'));
        $client->secureRequests();

        return $client->users()->getMedia('self', $this->config()->items_per_page, null, $maxID);
    }
}
