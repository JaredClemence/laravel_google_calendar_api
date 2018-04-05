<?php

namespace JRC\Google\Calendar;

use Illuminate\Http\Request;

class CalendarAuthController extends Controller
{
    public function getAuthUrl(){
        return __DIR__;
    }
}