<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace JRC\Google\Calendar;

use Google_Client as Client;
use Google_Service_Calendar as CalendarService;

const NOTSET = "NOTSET";

/**
 * Description of ClientBuilder
 *
 * @author jaredclemence
 */
class ClientBuilder {

    private $client;
    private $modifications;

    public function __construct() {
        $this->resetModificationsArray();
    }
    private function resetModificationsArray(){
        $this->modifications = [];
    }
    public function make() : Client {
        $this->initializeClient();
        $this->applyModifications();
        return $this->getClient();
    }
    
    public function setAccessToken( $token ){
        $modification = function( Client $client ) use ($token){
            $client->setAccessToken( $token );
        };
        $this->modifications[] = $modification;
    }
    
    public function setRefreshToken( $token ){
        $modification = function( Client $client ) use ($token){
            $client->refreshToken( $token );
        };
        $this->modifications[] = $modification;
    }

    public function loadOauthConfigFromJsonFile() {
        $self = $this;
        $modification = function( $client ) use ($self) {
            $relativePath = $self->getEnvironmentSetting('GOOGLE_APPLICATION_CREDENTIALS');
            $rootPath = $self->getRootDirectory();
            if (substr($relativePath, 0, 1) !== '/') {
                $relativePath = '/' . $relativePath;
            }
            $path = $rootPath . $relativePath;
            $realpath = realpath($path);
            if (!$realpath) {
                throw new \Exception("Unable to locate OAuth Configuration file at path identified in env('GOOGLE_APPLICATION_CREDENTIALS'): '$path'");
            }

            $client->setAuthConfig($realpath);
        };
        $this->modifications[] = $modification;
    }
    
    public function setCalendarScope() {
        $modification = function( $client ){
            $scope = CalendarService::CALENDAR;
            $client->setScopes($scope);
        };
        $this->modifications[] = $modification;
    }
    
    public function loadRedirectUrlFromEnvironmentFile(){
        $url = $this->getEnvironmentSetting('GOOGLE_REDIRECT_URI');
        $this->setRedirectUrl($url);
    }
    
    public function setPrompt( $type ){
        $modification = function( Client $client ) use ( $type ){
            $client->setPrompt( $type );
        };
        $this->modifications[] = $modification;
    }
    
    public function setRedirectUrl( $uri ){
        $modification = function( $client ) use ( $uri ){
            $client->setRedirectUri($uri);
        };
        $this->modifications[] = $modification;
    }

    private function initializeClient() {
        $this->client = new Client();
    }

    private function applyModifications() {
        $client = $this->getClient();
        foreach ($this->modifications as $modification) {
            $modification($client);
        }
    }

    private function getClient() {
        return $this->client;
    }
    
    private function getEnvironmentSetting( $key ){
        $value = env( $key, NOTSET );
        if( $value == NOTSET ){
            throw new \Exception("Please add a setting for $key in the .env file.");
        }
        return $value;
    }
    
    private function getRootDirectory(){
        $key = "vendor";
        if( $this->isInPackagesPath() ){
            $key = "packages";
        }
        return $this->findRootDirectoryUsingChildFolder( $key );
        
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

    private function isInPackagesPath() {
        return !( strpos( __DIR__, "packages" ) === false );
    }

}
