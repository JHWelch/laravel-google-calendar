<?php

namespace Spatie\GoogleCalendar\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Spatie\GoogleCalendar\GoogleCalendar
 */
class GoogleCalendar extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-google-calendar';
    }
}
