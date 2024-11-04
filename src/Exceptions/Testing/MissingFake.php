<?php

namespace Spatie\GoogleCalendar\Exceptions\Testing;

use Exception;

class MissingFake extends Exception
{
    public static function missingGetEvents()
    {
        return new static('No fake get event matches the given parameters.');
    }
}
