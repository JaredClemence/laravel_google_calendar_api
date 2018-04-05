<?php

namespace JRC\Google\Calendar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CalendarAuthController extends Controller
{
    public function getAuthUrl(){
        return __DIR__;
    }
    
    /**
     * Keep public for testing.
     * @return string
     */
    public function getFileDir(){
        return __DIR__;
    }
}