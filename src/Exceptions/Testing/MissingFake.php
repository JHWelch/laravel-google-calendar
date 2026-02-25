<?php

namespace Spatie\GoogleCalendar\Exceptions\Testing;

use Exception;

class MissingFake extends Exception
{
    public static function missingGet(): self
    {
        return new static('No fake get event matches the given parameters.');
    }

    public static function missingFind(): self
    {
        return new static('No fake find event matches the given parameters.');
    }
}
