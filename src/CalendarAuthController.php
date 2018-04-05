<?php

namespace JRC\Google\Calendar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Google_Client as Client;
use Google_Service_Calendar as CalendarService;

const NOTSET = "NOTSET";

class CalendarAuthController extends Controller
{
    /** @var Client */
    private $client;

    public function getAuthUrl(){
        $client = $this->makeClient();
        return $client->createAuthUrl();
    }
    
    public function getToken( Request $request ){
        $code = $request->get("code", false );
        if( $code === false ){
            throw new \Exception("Only call CalendarAuthController::getToken( Request $request ) on a redirect page. Google will add a parameter to the Request object that is required by this method.");
        }
        $client = $this->makeClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        $this->inspectTokenForError( $token );
        return $token;
    }
    
    private function getRootDirectory(){
        $key = "vendor";
        if( $this->isInPackagesPath() ){
            $key = "packages";
        }
        return $this->findRootDirectoryUsingChildFolder( $key );
        
    }
    
    private function getEnvironmentSetting( $key ){
        $value = env( $key, NOTSET );
        if( $value == NOTSET ){
            throw new \Exception("Please add a setting for $key in the .env file.");
        }
        return $value;
    }

    private function findRootDirectoryUsingChildFolder($folderName) {
        $dir = __DIR__;
        $pos = \strpos( $dir, $folderName );
        $root = \substr( $dir, 0, $pos );
        $realpath = \realpath($root);
        if( !$realpath ){
            throw new \Exception( "Error in detecting root folder. Path identified as '$root' does not exist." );
        }
        return $realpath;
    }

    private function makeClient() : Client {
        $this->initializeClient();
        $this->loadOauthConfigFromJsonFile();
        $this->setCalendarScope();
        $this->setRedirectUrl();
        return $this->getClient();
    }

    private function loadOauthConfigFromJsonFile() {
        $relativePath = $this->getEnvironmentSetting('GOOGLE_APPLICATION_CREDENTIALS');
        $rootPath = $this->getRootDirectory();
        if( substr( $relativePath, 0, 1 ) !== '/' ) {
            $relativePath = '/' . $relativePath;
        }
        $path = $rootPath . $relativePath;
        $realpath = realpath($path);
        if( !$realpath ){
            throw new \Exception("Unable to locate OAuth Configuration file at path identified in env('GOOGLE_APPLICATION_CREDENTIALS'): '$path'");
        }
        
        $client = $this->getClient();
        $client->setAuthConfig($realpath);
    }

    private function initializeClient() {
        $this->client = new Client();
    }
    
    private function getClient() : Client {
        return $this->client;
    }

    private function setCalendarScope() {
        $scope = CalendarService::CALENDAR;
        $client = $this->getClient();
        $client->setScopes($scope);
    }

    private function setRedirectUrl() {
        $url = $this->getEnvironmentSetting('GOOGLE_REDIRECT_URI');
        $client = $this->getClient();
        $client->setRedirectUri($url);
    }

    private function isInPackagesPath() {
        return !( strpos( __DIR__, "packages" ) === false );
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

}