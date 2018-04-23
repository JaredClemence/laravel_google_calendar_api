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
     * Call this method and pass in the Request object that Laravel creates at the Google API callback endpoint.
     * 
     * You define this endpoint in your Google API JSON file, and you must remember to add a route that 
     * supports this endpoint in your own web.php file. For example, I have Google calling my application at 
     * {myserver}/google_auth. In my route file, I would then add:
     * 
     * Route::get( '/google_auth', function( Request $request ){
     *      $controller = new JRC\Google\Calendar\CalendarAuthController();
     *      $tokenString = $controller->getToken( $request );
     * 
     *      //save this token or use it now... it's your choice
     * } );
     * 
     * @param Request $request
     * @return string
     * @throws \Exception
     */
    public function getToken( Request $request ){
        $this->initializeClient();
        $client = $this->client;
        $code = $request->get("code", false );
        if( $code === false ){
            throw new \Exception("Only call CalendarAuthController::getToken( Request $request ) on a redirect page. Google will add a parameter to the Request object that is required by this method.");
        }
        $tokenArray = $client->fetchAccessTokenWithAuthCode($code);
        $this->inspectTokenForError( $tokenArray );
        $this->saveLastTokenArray( $tokenArray );
        return $tokenArray['access_token'];
    }
    
    /**
     * 
     * @param Request $request
     * @todo Find out why the refresh token is not working... We have not been successful in getting a refresh token with the standard token thus far.
     */
    public function getRefreshToken( Request $request ){
        $this->initializeClient();
        $code = $request->get("code", false );
        $refresh = $this->client->fetchAccessTokenWithRefreshToken($code);
        dd($refresh);
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