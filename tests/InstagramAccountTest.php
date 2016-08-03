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

    public function testGetNewInstagramClient()
    {

    }

    public function testGetOAuthStateValueFromLoginURL()
    {

    }

    public function testGetSessionOAuthState()
    {

    }

    public function testSetSessionOAuthState()
    {

    }
}
