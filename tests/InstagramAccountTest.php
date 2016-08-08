<?php

use \Mockery as Mockery;

class InstagramAccountTest extends SapphireTest
{
    public function tearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getNewInstagramAccountMock()
    {
        return Mockery::mock('InstagramAccount');
    }

    public function testUpdateAccessToken()
    {
        $validToken =
            '{"user":{"username":"somardesignstudios","bio":"","website":"","profile_picture":"",' .
            '"full_name":"","id":"12345678"},"access_token":"","resource_owner_id":""}';

        $invalidToken =
            '{"user":{"username":"somardesignstudios","bio":"","website":"","profile_picture":"",' .
            '"full_name":"","id":"87654321"},"access_token":"","resource_owner_id":""}';

        /*
         * Should throw if the session state is invalid.
         */
        $mock = $this->getNewInstagramAccountMock()->makePartial();
        $mock
            ->shouldReceive('getSessionOAuthState')
            ->once()
            ->withNoArgs()
            ->andReturn('state');

        try {
            $mock->updateAccessToken($validToken, 'invalidState');
        } catch (Exception $e) {
            $this->assertEquals('Trying to set token on wrong InstagramAccount', $e->getMessage());
        }

        /*
         * Should throw if the session state is valid and the token username doesn't match the title.
         */
        $mock = $this->getNewInstagramAccountMock()->makePartial();
        $mock
            ->shouldReceive('getSessionOAuthState')
            ->once()
            ->withNoArgs()
            ->andReturn('state');
        $mock
            ->shouldReceive('getField')
            ->once()
            ->with('Title')
            ->andReturn('otherusername');

        try {
            $mock->updateAccessToken($validToken, 'state');
        } catch (Exception $e) {
            $this->assertEquals('Trying to set token on wrong InstagramAccount', $e->getMessage());
        }

        /*
         * Should set the token if session state is valid and there's no current token.
         */
        $mock = $this->getNewInstagramAccountMock()->makePartial();
        $mock
            ->shouldReceive('getSessionOAuthState')
            ->once()
            ->withNoArgs()
            ->andReturn('state');
        $mock
            ->shouldReceive('getField')
            ->with('Title')
            ->andReturn('somardesignstudios');
        $mock
            ->shouldReceive('getField')
            ->with('AccessToken')
            ->andReturn(null);
        $mock
            ->shouldReceive('setField')
            ->once()
            ->with('AccessToken', $validToken);

        $mock->updateAccessToken($validToken, 'state');

        /*
         * Should throw if the session state is valid, the token username matches the title,
         * and the new token is for another account.
         */
        $mock = $this->getNewInstagramAccountMock()->makePartial();
        $mock
            ->shouldReceive('getSessionOAuthState')
            ->once()
            ->withNoArgs()
            ->andReturn('state');
        $mock
            ->shouldReceive('getField')
            ->with('Title')
            ->andReturn('somardesignstudios');
        $mock
            ->shouldReceive('getField')
            ->with('AccessToken')
            ->andReturn($validToken);

        try {
            $mock->updateAccessToken($invalidToken, 'state');
        } catch (Exception $e) {
            $this->assertEquals('Trying to set token on wrong InstagramAccount', $e->getMessage());
        }

        /*
         * Should update the token if the session state is valid and the token IDs match.
         */
        $mock = $this->getNewInstagramAccountMock()->makePartial();
        $mock
            ->shouldReceive('getSessionOAuthState')
            ->once()
            ->withNoArgs()
            ->andReturn('state');
        $mock
            ->shouldReceive('getField')
            ->with('Title')
            ->andReturn('somardesignstudios');
        $mock
            ->shouldReceive('getField')
            ->with('AccessToken')
            ->andReturn($validToken);
        $mock
            ->shouldReceive('setField')
            ->once()
            ->with('AccessToken', $validToken);

        $mock->updateAccessToken($validToken, 'state');
    }

    public function testGetOAuthStateValueFromLoginURL()
    {
        $instagramAccount = new InstagramAccount();

        /*
         * Should return null if no URL is passed.
         */
        $this->assertEquals(null, $instagramAccount->getOAuthStateValueFromLoginURL());

        /*
         * Should return null if there's no "state" query string.
         */
        $this->assertEquals(
            null,
            $instagramAccount->getOAuthStateValueFromLoginURL(
                'http://example.com/admin/admin/instagram/InstagramAccount/OAuth?code=123'
            )
        );

        /*
         * Should return the value of the "state" query string if it exists.
         */
        $this->assertEquals(
            'abc',
            $instagramAccount->getOAuthStateValueFromLoginURL(
                'http://example.com/admin/admin/instagram/InstagramAccount/OAuth?code=123&state=abc'
            )
        );
    }

    public function testIsValidMediaID()
    {
        /*
         * Should return false if no media ID is passed.
         */
        $instagramAccount = new InstagramAccount();

        $this->assertEquals(false, $instagramAccount->isValidMediaID());

        /*
         * Should return false if the account ID is unavailable.
         */
        $mock = $this->getNewInstagramAccountMock()->makePartial();
        $mock
            ->shouldReceive('getInstagramID')
            ->withNoArgs()
            ->andReturn(null);

        $this->assertEquals(
            false,
            $mock->isValidMediaID('123456789012345678_123')
        );

        /*
         * Should return false if the media ID is less than 18 digits.
         */
        $mock = $this->getNewInstagramAccountMock()->makePartial();
        $mock
            ->shouldReceive('getInstagramID')
            ->withNoArgs()
            ->andReturn('123');

        $this->assertEquals(
            false,
            $mock->isValidMediaID('12345678901234567_123')
        );

        /*
         * Should return false if the media ID is greater than 19 digits.
         */
        $this->assertEquals(
            false,
            $mock->isValidMediaID('12345678901234567890_123')
        );

        /*
         * Should return false if the media ID length is valid but contains non-digits.
         */
        $this->assertEquals(
            false,
            $mock->isValidMediaID('123456789Z12345678_123')
        );

        $this->assertEquals(
            false,
            $mock->isValidMediaID('123456789&12345678_123')
        );

        /*
         * Should return false if a valid media ID is not followed by an underscore.
         */
        $this->assertEquals(
            false,
            $mock->isValidMediaID('123456789012345678-123')
        );

        /*
         * Should return false if the media ID doesn't end with the account ID.
         */
        $this->assertEquals(
            false,
            $mock->isValidMediaID('123456789012345678_321')
        );

        /*
         * Should return true if the media ID is 18 or 19 characters followed by
         * an underscore and the account ID.
         */
        $this->assertEquals(
            true,
            $mock->isValidMediaID('123456789012345678_123')
        );

        $this->assertEquals(
            true,
            $mock->isValidMediaID('1234567890123456789_123')
        );
    }
}
