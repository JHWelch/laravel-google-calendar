<?php

namespace Spatie\GoogleCalendar\Facades;

use Illuminate\Support\Facades\Facade;
use Spatie\GoogleCalendar\Testing\EventsFake;

/**
 * @see \Spatie\GoogleCalendar\Events
 */
class Events extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-google-calendar-events';
    }

    public static function fake(): EventsFake
    {
        return tap(new EventsFake(static::getFacadeApplication()), function ($fake) {
            static::swap($fake);
        });
    }
}
