<?php
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\PasswordAuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\User\UserNameUtils;

/**
 * Provide a simple RADIUS Primary Authentification Provider.
 *
 * This extension allows you to delegate authentication to a RADIUS server.
 *
 * Class SimpleRadiusAuthProvider
 */
class SimpleRadiusAuthProvider extends \MediaWiki\Auth\AbstractPasswordPrimaryAuthenticationProvider {
    /**
     * SimpleRadiusAuthProvider constructor.
     *
     * Do some checks.
     */
    public function SimpleRadiusAuthProvider() {
        global $wgSimpleRadiusAuthServer, $wgSimpleRadiusAuthPort, $wgSimpleRadiusAuthSecret, $wgSimpleRadiusAuthTimeout, $wgSimpleRadiusAuthMaxTries;

        parent::__construct();

        // Simple checks
        if( !isset( $wgSimpleRadiusAuthServer ) || strlen( $wgSimpleRadiusAuthServer ) == 0 ) {
            wfDebug("\$wgSimpleRadiusAuthServer is not defined");
        }

        if( !isset( $wgSimpleRadiusAuthPort ) || !is_int( $wgSimpleRadiusAuthPort ) || $wgSimpleRadiusAuthMaxTries < 0 || $wgSimpleRadiusAuthMaxTries > 65535 ) {
            wfDebug("\$$wgSimpleRadiusAuthPort is not defined or is not a valid integer");
        }

        if( !isset( $wgSimpleRadiusAuthSecret ) || strlen( $wgSimpleRadiusAuthSecret ) == 0 ) {
            wfDebug("\$$wgSimpleRadiusAuthSecret is not defined");
        }

        if( !isset( $wgSimpleRadiusAuthTimeout ) || !is_int( $wgSimpleRadiusAuthTimeout ) || $wgSimpleRadiusAuthMaxTries < 0) {
            wfDebug("\$$wgSimpleRadiusAuthTimeout is not defined or is not a valid integer");
        }

        if( !isset( $wgSimpleRadiusAuthMaxTries ) || !is_int( $wgSimpleRadiusAuthMaxTries ) || $wgSimpleRadiusAuthMaxTries < 0) {
            wfDebug("\$$wgSimpleRadiusAuthMaxTries is not defined or is not a valid integer");
        }

        if( function_exists( 'radius_auth_open' ) ) {
            wfDebug("RADIUS extension for PHP is not installed");
        }
    }

    /**
     * Start an authentication flow.
     *
     * The credentials are send to the RADIUS server.
     *
     * @param array $reqs
     * @return AuthenticationResponse
     */
    public function beginPrimaryAuthentication(array $reqs) {
        global $wgSimpleRadiusAuthServer, $wgSimpleRadiusAuthPort, $wgSimpleRadiusAuthSecret, $wgSimpleRadiusAuthTimeout, $wgSimpleRadiusAuthMaxTries, $wsSimpleRadiusAuthIdentifier;

        // Check if the username and password are defined
        $req = AuthenticationRequest::getRequestByClass( $reqs, PasswordAuthenticationRequest::class );
        if ( !$req || $req->username === null || $req->password === null ) {
            return AuthenticationResponse::newAbstain();
        }

        $username = $this->userNameUtils->getCanonical( $req->username, UserNameUtils::RIGOR_USABLE );
        if ( $username === false ) {
            return AuthenticationResponse::newAbstain();
        }

        // Use RADIUS for the authentification
        $radius = radius_auth_open();
        radius_add_server( $radius,
                           $wgSimpleRadiusAuthServer,
                           $wgSimpleRadiusAuthPort,
                           $wgSimpleRadiusAuthSecret,
                           $wgSimpleRadiusAuthTimeout,
                           $wgSimpleRadiusAuthMaxTries);

        radius_create_request( $radius, RADIUS_ACCESS_REQUEST );
        radius_put_attr( $radius, RADIUS_USER_NAME, strtolower( $username ) );
        radius_put_attr( $radius, RADIUS_USER_PASSWORD, $req->password );

        if ( empty($wsSimpleRadiusAuthIdentifier) ) {
            $wsSimpleRadiusAuthIdentifier = gethostname();
        }

        radius_put_attr( $radius, RADIUS_NAS_IDENTIFIER, $wsSimpleRadiusAuthIdentifier );

        // If RADIUS accept the credentials, we're good to go
        if( radius_send_request( $radius ) == RADIUS_ACCESS_ACCEPT )
            return AuthenticationResponse::newPass( $username );
        else
            return AuthenticationResponse::newFail( new Message( 'authmanager-authn-no-primary' ) );
    }

    /**
     * Test whether the named user exists : always returns true because we cannot ask the RADIUS server for that.
     *
     * @param string $username
     * @param int $flags
     * @return bool
     */
    public function testUserExists($username, $flags = User::READ_NORMAL) {
        return true;
    }

    /**
     * The extension doesn't support a change of authentication data.
     *
     * @param AuthenticationRequest $req
     * @param bool $checkData
     * @return StatusValue
     */
    public function providerAllowsAuthenticationDataChange(AuthenticationRequest $req, $checkData = true) {
        return StatusValue::newGood( 'ignored' );
    }

    /**
     * The providerAllowsAuthenticationDataChange() method returns that we don't support that so we don't implement it.
     *
     * Always throw BadMethodCallException.
     *
     * @param AuthenticationRequest $req
     * @throws BadMethodCallException
     */
    public function providerChangeAuthenticationData(AuthenticationRequest $req) {
        throw new \BadMethodCallException( __METHOD__ . ' is not implemented.' );
    }

    /**
     * Fetch the account-creation type, always return TYPE_CREATE.
     *
     * @return string
     */
    public function accountCreationType() {
        return self::TYPE_CREATE;
    }

    /**
     * We're not involved in the creation user process.
     *
     * Always return AuthenticationResponse::newAbstain().
     *
     * @param User $user
     * @param User $creator
     * @param array $reqs
     * @return AuthenticationResponse
     */
    public function beginPrimaryAccountCreation($user, $creator, array $reqs) {
        return AuthenticationResponse::newAbstain();
    }

    /**
     * Returns the applicable list of AuthenticationRequests
     *
     * We only support the ACTION_LOGIN.
     *
     * @param string $action
     * @param array $options
     * @return array
     */
    public function getAuthenticationRequests($action, array $options) {
        switch ( $action ) {
            case AuthManager::ACTION_LOGIN:
                return [ new PasswordAuthenticationRequest() ];

            case AuthManager::ACTION_CHANGE:
            case AuthManager::ACTION_CREATE:
            case AuthManager::ACTION_REMOVE:
            default:
        }

        return [];
    }
}
