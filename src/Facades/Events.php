<?php

namespace Spatie\GoogleCalendar\Facades;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Spatie\GoogleCalendar\Event;
use Spatie\GoogleCalendar\GoogleCalendar;
use Spatie\GoogleCalendar\Testing\EventsFake;

/**
 * @see \Spatie\GoogleCalendar\Events
 *
 * @method static Event create(array|Event $event, ?string $calendarId = null, array $optParams = [])
 * @method static Event quickCreate(string $text, ?string $calendarId = null)
 * @method static Event update(Event $event, array $optParams = [])
 * @method static Event find(string $eventId, string $calendarId = null)
 * @method static Collection get(?CarbonInterface $startDateTime = null, ?CarbonInterface $endDateTime = null, array $queryParameters = [], ?string $calendarId = null)
 * @method static void delete(string $eventId, string $calendarId = null)
 * @method static GoogleCalendar getGoogleCalendar(string $calendarId = null)
 * @method static string getGoogleCalendarId(string $calendarId = null)
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
