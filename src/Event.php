<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace JRC\Google\Calendar;
use Google_Service_Calendar_Event as GoogleEvent;
/**
 * Description of Event
 *
 * @author jaredclemence
 */
class Event {
    private $source;

    public function __construct( GoogleEvent $source ){
        $this->source = $source;
    }
    
    public function __get( $name ){
        return $this->source->{$name};
    }
    
    public function getId(){
        return $this->source->id;
    }
    
    public function getLastUpdate( \DateTimeZone $zone=null ) : \DateTime {
        $timeString = $this->source->updated;
        $date = new \DateTime( $timeString, new \DateTimeZone("UTC") );
        if( $zone ){
            $date->setTimezone($zone);
        }
        return $date;
    }
    
    public function getStartTime( \DateTimeZone $zone=null ) : \DateTime {
        return $this->makeDateTime( $this->start, $zone );
    }
    
    public function getEndTime( \DateTimeZone $zone=null ) : \DateTime {
        return $this->makeDateTime( $this->end, $zone );
    }
    
    public function getName(){
        return $this->source->summary;
    }
    
    public function getDescription(){
        return $this->source->description;
    }
    
    public function getLocation(){
        return $this->source->location;
    }
    
    public function getStatus(){
        return $this->source->status;
    }
    
    public function getICalUrl(){
        return $this->iCalUID;
    }
    
    public function getRepeatSequence(){
        return $this->sequence;
    }
    
    private function makeDateTime($dateObj, \DateTimeZone $zone = null ){
        if( $dateObj ){
            $date = $dateObj->date;
            $dateTime = $dateObj->dateTime;
            $timeZone = $dateObj->timeZone;
            
            $dateTimeObj = null;
            $localZone = env('LOCAL_ZONE');
            $tempZone = new \DateTimeZone($localZone);
            if( $timeZone ){
                $tempZone = new \DateTimeZone( $timeZone );
            }
            if( $dateTime ){
                $dateTimeObj = new \DateTime( $dateTime, $tempZone );
            }else if( $date ){
                $dateTimeObj = new \DateTime( $date, $tempZone );
            }
            if( $zone ){
                $dateTimeObj->setTimezone($zone);
            }
            if( $dateTimeObj ){
                return $dateTimeObj;
            }
        }
        throw new \Exception("Date object not available.");
    }
}
