<?php

namespace Spatie\GoogleCalendar\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Spatie\GoogleCalendar\Events
 */
class Events extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-google-calendar-events';
    }
}
