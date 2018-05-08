<?php

namespace JRC\Google\Calendar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Google_Client as Client;
use JRC\Google\Calendar\ClientBuilder;

class CalendarAuthController extends Controller
{
    /** @var ClientBuilder */
    private $builder;
    
    /** @var Client */
    private $client;
    
    /** @var array */
    private $authModifications;
    
    /** @var array */
    private $lastTokenArray;
    
    /** @var string */
    private $access_token;
    /** @var string */
    private $token_type;
    /** @var number seconds until expires */
    private $expires_in;
    /** @var string */
    private $refresh_token;
    /** @var number timestamp */
    private $created;

    /**
     * This method returns a url that your user can travel to in order to grant your application authorization 
     * based on the data in your JSON configuration file.
     * 
     * For this to work correctly, you must set up your Google API credentials in the JSON file, 
     * and you must tell the application where to find that JSON file by setting the 
     * environment value 'GOOGLE_APPLICATION_CREDENTIALS' to the path of the JSON file.
     * 
     * You may customize some aspects of the URL, such as state and type (offline vs online) by calling 
     * other methods on this controller before this method. Please see setState(...) and setAuthType(...) for more detail.
     * 
     * @return type
     */
    public function getAuthUrl(){
        $client = $this->makeClient();
        $this->applyAuthModifications($client);
        return $client->createAuthUrl();
    }
    
    /**
     * Use this method to inspect attributes of a token in more detail.
     * 
     * A valid token will include attributes that are not returned by the getToken(...) method.
     * If you need to see the token_type, the expires_in, or the created values, 
     * then call this method after passing the callback response to the getToken(...) method.
     * 
     * @return array
     */
    public function getLastTokenArray() : array {
        $array = $this->lastTokenArray;
        if( !is_array( $array ) ){
            $array = [];
        }
        return $array;
    }
    
    /**
     * Get the token parsed from the last request.
     */
    public function getToken(){
        return $this->access_token;
    }
    
    /**
     * Call this method first.
     * 
     * This method should be run in on the callback page with the page request.
     * The callback page is loaded with a 'code' that allows the google client 
     * to fetch auth codes and refresh tokens.
     * 
     * Once this method has parsed the request, any available data will be available 
     * by calling getToken() or getRefreshToken()
     * 
     * If it exists!!!!, save the refresh token.  It will never be re-issued.
     * 
     * If a user needs to have it re-issued, the
     * @param Request $request
     */
    public function parseResponseForTokens( Request $request ){
        $access_token = null;
        $token_type = null;
        $expires_in = null;
        $refresh_token = null;
        $created = null;
        $tokenArray = $this->convertRequestToTokenArray( $request );
        extract($tokenArray, EXTR_OVERWRITE);
        $this->setAccessToken( $access_token );
        $this->setTokenType( $token_type );
        $this->setExpiresIn( $expires_in );
        $this->setRefreshToken( $refresh_token );
        $this->setCreated( $created );
        $this->inspectTokenForError( $tokenArray );
    }
    
    protected function convertRequestToTokenArray( Request $request ){
        $this->initializeClient();
        $client = $this->client;
        $code = $request->get("code", false );
        if( $code === false ){
            throw new \Exception("Only call CalendarAuthController::getToken( Request $request ) on a redirect page. Google will add a parameter to the Request object that is required by this method.");
        }
        $tokenArray = $client->fetchAccessTokenWithAuthCode($code);
        return $tokenArray;
    }
    
    protected function setAccessToken($access_token) {
        $this->access_token = $access_token;
    }
    
    protected function setTokenType( $type ){
        $this->token_type = $type;
    }
    protected function setExpiresIn( $expires ){
        $this->expires_in = $expires;
    }
    protected function setRefreshToken( $refresh ){
        $this->refresh_token = $refresh;
    }
    protected function setCreated( $created ){
        $this->created = $created;
    }
    
    /**
     * Get the refresh token from the previous request
     */
    public function getRefreshToken(){
        return $this->refresh_token;
    }

    /**
     * Call this method with 'offline' or 'online' to customize the token type.
     * 
     * Call this method BEFORE calling getAuthURL(...)
     * 
     * @param string $type | set to 'offline' or 'online'
     * @see https://developers.google.com/identity/protocols/OAuth2WebServer#offline
     */
    public function setAccessType(string $type) {
        $modifier = function( Client $client ) use ( $type ){
            $client->setAccessType($type);
        };
        $this->addAuthModification( $modifier );
    }
    
    /**
     * Call this method with a urlencodede query string. It will be passed back to your callback endpoint in the state variable of the Request object.
     * 
     * At the callback location, you can obtain the state that you set by calling 
     * `$state = $request->get('state')`.  From there, you must parse the state variable
     * accordingly. Google does not modify this string. What you pass in, you will get out, so 
     * feel free to customize the app state serialization in a way that makes sense to you.
     * 
     * Call this method BEFORE calling getAuthURL(...)
     * 
     * @param string $type | set to 'offline' or 'online'
     * @see https://developers.google.com/identity/protocols/OAuth2WebServer#creatingclient
     */
    public function setState( string $queryParams ){
        $modifier = function( Client $client ) use ( $queryParams ){
            $client->setState($queryParams);
        };
        $this->addAuthModification( $modifier );
    }

    /**
     * Use this method if you want to add custom authorization parameters before 
     * generating the auth URL.
     * 
     * The reference documentation is linked below. If you want to make a customization 
     * to the client before generating the authUrl, you can make a `callable` that receives 
     * a `Client` object for modification.
     * 
     * See setAccessType and setState for examles on how this is used.
     * 
     * Call this method BEFORE calling getAuthURL(...)
     * 
     * @param string $type | set to 'offline' or 'online'
     * @see https://developers.google.com/identity/protocols/OAuth2WebServer#creatingclient
     */
    public function addAuthModification(callable $modifier) {
        if( !is_array( $this->authModifications ) ) $this->authModifications = [];
        $this->authModifications[] = $modifier;
    }

    private function makeClient() : Client {
        $builder = new ClientBuilder();
        $builder->loadOauthConfigFromJsonFile();
        $builder->setCalendarScope();
        $builder->setPrompt('consent');
        $builder->loadRedirectUrlFromEnvironmentFile();
        $client = $builder->make();
        return $client;
    }

    private function inspectTokenForError($token) {
        if( is_array($token) ){
            $error = null;
            $error_description = null;
            extract( $token, EXTR_OVERWRITE );
            if( $error != null ){
                $msg = "GoogleApi Exception: $error -- $error_description";
                throw new \Exception( $msg );
            }
        }
    }

    private function applyAuthModifications($client) {
        while( $modifier = array_shift( $this->authModifications ) ){
            $modifier( $client );
        }
    }

    private function saveLastTokenArray($tokenArray) {
        $this->lastTokenArray = $tokenArray;
    }

    private function initializeClient() {
        $client = $this->makeClient();
        $this->client = $client;
        return $client;
    }

}