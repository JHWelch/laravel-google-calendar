<?php

namespace Spatie\GoogleCalendar\Testing;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Testing\Fakes\Fake;
use Spatie\GoogleCalendar\Event;
use Spatie\GoogleCalendar\Events;
use Spatie\GoogleCalendar\Exceptions\Testing\MissingFake;
use Spatie\GoogleCalendar\Facades\Events as EventsFacade;

use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;

class EventsFake extends EventsFacade implements Fake
{
    /**
     * The actual events instance.
     */
    public Events $events;

    public Collection $createEvents;

    public Collection $getEvents;

    public function __construct(Events $actualEvents)
    {
        $this->events = $actualEvents;

        $this->getEvents = collect();
        $this->createEvents = collect();
    }

    public function create(array $properties, string $calendarId = null, $optParams = [])
    {
        $event = new Event;

        $event->calendarId = $this->events->getGoogleCalendarId($calendarId);

        foreach ($properties as $name => $value) {
            $event->$name = $value;
        }

        $this->createEvents[] = [
            'properties' => $properties,
            'calendarId' => $calendarId,
            'optParams' => $optParams,
        ];

        return $event;
    }

    public function get(CarbonInterface $startDateTime = null, CarbonInterface $endDateTime = null, array $queryParameters = [], string $calendarId = null): Collection
    {
        $fake = $this->getEvents->first(function ($event) use ($startDateTime, $endDateTime, $queryParameters, $calendarId) {
            return (is_null($event['startDateTime']) || $event['startDateTime']->is($startDateTime))
                && (is_null($event['endDateTime']) || $event['endDateTime']->is($endDateTime))
                && (is_null($event['calendarId']) || $event['calendarId'] == $calendarId)
                && (empty($event['queryParameters']) || $event['queryParameters'] == $queryParameters);
        });

        if (is_null($fake)) {
            throw MissingFake::missingGetEvents();
        }

        return collect($this->mapEvents($fake['events']));
    }

    public function fakeGet(
        iterable $events = [],
        CarbonInterface $startDateTime = null,
        CarbonInterface $endDateTime = null,
        array $queryParameters = [],
        string $calendarId = null
    ){
        $this->getEvents[] = [
            'events' => $events,
            'startDateTime' => $startDateTime,
            'endDateTime' => $endDateTime,
            'queryParameters' => $queryParameters,
            'calendarId' => $calendarId,
        ];

        return $this;
    }

    public function assertCreated(array $properties, string $calendarId = null, $optParams = [])
    {
        $call = $this->createEvents->first(function ($event) use ($properties, $calendarId, $optParams) {
            return $event['properties'] == $properties
                && $event['calendarId'] == $calendarId
                && $event['optParams'] == $optParams;
        });

        assertNotNull($call, 'No fake create event matches the given parameters.');
    }

    public function assertNotCreated(array $properties, string $calendarId = null, $optParams = [])
    {
        $call = $this->createEvents->first(function ($event) use ($properties, $calendarId, $optParams) {
            return $event['properties'] == $properties
                && $event['calendarId'] == $calendarId
                && $event['optParams'] == $optParams;
        });

        assertNull($call, 'A fake create event matches the given parameters.');
    }

    protected function mapEvents(array $events): Collection
    {
        return collect($events)->map(function ($event) {
            return $this->mapEvent($event);
        });
    }

    protected function mapEvent(iterable $event): Event
    {
        $googleEvent = new Event;

        foreach ($event as $name => $value) {
            $googleEvent->$name = $value;
        }

        return $googleEvent;
    }
}
