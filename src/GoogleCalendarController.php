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
    
    public function getCalendar( $authToken, $calendarId ) : Calendar {
        $this->initializeController($authToken);
        $calendar = $this->service->calendars->get( $calendarId );
        return $calendar;
    }

    /**
     * Get a list of all calendars associated with the authorized account.
     * @param string $authToken
     * @return CalendarList
     */
    public function getCalendarList($authToken) : CalendarList {
        $this->initializeController($authToken);
        $calendarList = $this->service->calendarList->listCalendarList();
        return $calendarList;
    }
    
    /**
     * Get a list of all events associated with the calendar id.
     * @param string $authToken
     * @param string $calendarId
     * @param array $options
     * 
     * For a list of options:
     * @see https://developers.google.com/calendar/v3/reference/events/list
     */
    public function getEvents( string $authToken, string $calendarId, $options = [] ) : Collection {
        $this->initializeController($authToken);
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
     * @param string $calendarId
     * @param string $eventId
     * @param array $options
     * 
     * For a list of options:
     * @see https://developers.google.com/calendar/v3/reference/events/get
     */
    public function getEvent( string $authToken, string $calendarId, string $eventId, $options ) : Event {
        $this->initializeController($authToken);
        return $this->service->events->get( $calendarId, $eventId, $options );
    }
    
    private function initializeController( string $authToken ){
        if( $this->token != $authToken ){
            $this->setTheAuthToken( $authToken );
            $this->buildTheClient();
            $this->buildTheCalendarService();
        }
    }
    
    private function setTheAuthToken($authToken) {
        $this->token = $authToken;
    }

    private function buildTheClient() {
        $builder= new ClientBuilder();
        $builder->loadOauthConfigFromJsonFile();
        $builder->setCalendarScope();
        $builder->setAccessToken( $this->token );
        $client = $builder->make();
        $this->client = $client;
    }

    private function buildTheCalendarService() {
        $client = $this->client;
        $service = new CalendarService($client);
        $this->service = $service;
    }

}