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

}