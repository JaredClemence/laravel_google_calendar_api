<?php

namespace JRC\Google\Calendar;

use App\Http\Controllers\Controller;
use Google_Client as Client;
use Google_Service_Calendar as CalendarService;
use JRC\Google\Calendar\ClientBuilder;
use Illuminate\Support\Collection;
use Google_Service_Calendar_CalendarList as CalendarList;
use Google_Service_Calendar_Events as Events;
use Google_Service_Calendar_Calendar as Calendar;
use Google_Service_Calendar_Event as GoogleEvent;
use JRC\Google\Calendar\Event;

class GoogleCalendarController extends Controller
{
    private $token;
    private $client;
    private $service;
    private $refreshToken;

    public function getCalendar( $authToken, $calendarId ) : Calendar {
        $this->initializeController($authToken);
        $calendar = $this->service->calendars->get( $calendarId );
        return $calendar;
    }

    /**
     * Get a list of all calendars associated with the authorized account.
     * @param string $authToken
     * @param string $refreshToken  Pass in 'null' if a refresh token is not being used.
     * @return CalendarList
     */
    public function getCalendarList($authToken, $refreshToken) : CalendarList {
        $this->initializeController($authToken, $refreshToken);
        $calendarList = $this->service->calendarList->listCalendarList();
        return $calendarList;
    }
    
    /**
     * Get a list of all events associated with the calendar id.
     * @param string $authToken
     * @param string $refreshToken  Pass in 'null' if a refresh token is not being used.
     * @param string $calendarId
     * @param array $options
     * 
     * For a list of options:
     * @see https://developers.google.com/calendar/v3/reference/events/list
     */
    public function getEvents( string $authToken, string $refreshToken, string $calendarId, $options = [] ) : Collection {
        $this->initializeController($authToken, $refreshToken);
        $all = $this->service->events->listEvents( $calendarId, $options );
        $collection = collect();
        foreach( $all as $one ){
            $event = new Event( $one );
            $collection->push( $event );
        }
        return $collection;
    }
    
    /**
     * Get a single event from a single calendar.
     * @param string $authToken
     * @param string $refreshToken  Pass in 'null' if a refresh token is not being used.
     * @param string $calendarId
     * @param string $eventId
     * @param array $options
     * 
     * For a list of options:
     * @see https://developers.google.com/calendar/v3/reference/events/get
     */
    public function getEvent( string $authToken, string $refreshToken, string $calendarId, string $eventId, $options ) : Event {
        $this->initializeController($authToken, $refreshToken);
        return $this->service->events->get( $calendarId, $eventId, $options );
    }
    
    private function initializeController( string $authToken, string $refreshToken ){
        if( $this->token != $authToken ){
            $this->setTheAuthToken( $authToken );
            $this->setTheRefreshToken( $refreshToken );
            $this->buildTheClient();
            $this->buildTheCalendarService();
        }
    }
    
    private function setTheAuthToken($authToken) {
        $this->token = $authToken;
    }
    
    private function setTheRefreshToken( $token ){
        $this->refreshToken = $token;
    }

    private function buildTheClient() {
        $builder= new ClientBuilder();
        $builder->loadOauthConfigFromJsonFile();
        $builder->setCalendarScope();
        if( $this->token ){
            $builder->setAccessToken( $this->token );
        }
        if( $this->refreshToken ){
            $builder->setRefreshToken( $this->refreshToken );
        }
        $client = $builder->make();
        $this->client = $client;
    }

    private function buildTheCalendarService() {
        $client = $this->client;
        $service = new CalendarService($client);
        $this->service = $service;
    }

}