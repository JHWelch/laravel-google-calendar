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
        $actualEvents = static::isFake()
            ? static::getFacadeRoot()->events
            : static::getFacadeRoot();

        return tap(new EventsFake($actualEvents), function ($fake) {
            static::swap($fake);
        });
    }
}
