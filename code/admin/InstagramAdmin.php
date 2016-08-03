<?php

use League\OAuth2\Client\Provider\Exception\InstagramIdentityProviderException;

class InstagramAdmin extends ModelAdmin
{
    /**
     * @var string
     */
    private static $url_segment = 'instagram';

    /**
     * @var string
     */
    private static $menu_title = 'Instagram';

    /**
     * @var array
     */
    private static $managed_models = [
        'InstagramAccount',
    ];

    /**
     * @var array
     */
    private static $allowed_actions = [
        'ImportForm',
        'SearchForm',
        'OAuth',
    ];

    /**
     * @var string
     */
    private static $menu_icon = 'silverstripe-instagram/images/instagram-logo.png';

    /**
     * @param int $id
     * @param FieldList $fields
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $gridFieldName = $this->sanitiseClassName($this->modelClass);
        $gridFieldConfig = $form->Fields()->fieldByName($gridFieldName)->getConfig();

        $gridFieldConfig->removeComponentsByType('GridFieldPrintButton');
        $gridFieldConfig->removeComponentsByType('GridFieldExportButton');
        $gridFieldConfig
            ->getComponentByType('GridFieldAddNewButton')
            ->setButtonName(_t('Instagram.ButtonLabelAddAccount', 'Add account'));
        $gridFieldConfig
            ->getComponentByType('GridFieldDetailForm')
            ->setItemRequestClass('InstagramAdminRequestHandler');

        return $form;
    }

    /**
     * Gets a InstagramAccount ID from the Session.
     *
     * @param string $state
     * @return int|null
     */
    private function getInstagramAccountIDFromSession($state = null)
    {
        if (!$state) {
            return null;
        }

        $instagramAccounts = Session::get('InstagramAccounts');

        foreach ($instagramAccounts as $key => $value) {
            if ($value == $state) {
                return $key;
            }
        }
    }

    /**
     * Handles failed AOuth attempts.
     *
     * @param Form $form
     * @return Controller
     */
    private function handleOAuthError($form, $message = null)
    {
        $message = $message ? $message : _t(
            'Instagram.MessageOAuthErrorResponse',
            'Unable to authorise account. Please try again.'
        );

        $form->sessionMessage($message, 'bad');

        return Controller::curr()->redirect($this->Link());
    }

    /**
     * OAuth callback handler.
     *
     * @param SS_HTTPRequest $request
     */
    public function OAuth($request)
    {
        $code = $request->getVar('code');
        $state = $request->getVar('state');

        if (!$code || !$state) {
            return Controller::curr()->redirect($this->Link());
        }

        $client = InstagramAccount::getNewInstagramClient();
        $form = $this->getEditForm();

        try {
            $token = $client->getAccessToken($code);

            $instagramAccountID = $this->getInstagramAccountIDFromSession($state);

            // Find the matching InstagramAccount.
            if (!$instagramAccountID ||
                !$instagramAccount = InstagramAccount::get()->byId($instagramAccountID)
            ) {
                return $this->handleOAuthError($form);
            }

            try {
                $instagramAccount->updateAccessToken(Convert::raw2json($token), $state);
                $instagramAccount->write();

                $form->sessionMessage(
                    _t(
                        'Instagram.MessageOAuthSuccess',
                        'Successfully authorised your account.'
                    ),
                    'good'
                );

                return Controller::curr()->redirect($this->Link());
            } catch (Exception $e) {
                return $this->handleOAuthError($form, _t(
                    'Instagram.MessageOAuthErrorUserConflict',
                    'Unable to authorise account. Make sure you are logged out of Instagram and ' .
                    'your username is spelled correctly.'
                ));
            }
        } catch (InstagramIdentityProviderException $e) {
            return $this->handleOAuthError($form);
        }
    }
}

class InstagramAdminRequestHandler extends GridFieldDetailForm_ItemRequest
{
    private static $allowed_actions = [
        'edit',
        'view',
        'ItemEditForm'
    ];

    /**
     * @return Form
     */
    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();

        $formActions = $form->Actions();

        if ($actions = $this->record->getCMSActions()) {
            foreach ($actions as $action) {
                $formActions->push($action);
            }
        }

        return $form;
    }
}
