<?php

namespace Spatie\GoogleCalendar\Testing;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Testing\Fakes\Fake;
use Spatie\GoogleCalendar\Event;
use Spatie\GoogleCalendar\Events;
use Spatie\GoogleCalendar\Exceptions\Testing\MissingFake;
use Spatie\GoogleCalendar\Facades\Events as EventsFacade;

use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

class EventsFake extends EventsFacade implements Fake
{
    /**
     * The actual events instance.
     */
    public Events $events;

    public Collection $createEvents;

    public Collection $quickCreateEvents;

    public Collection $findEvents;

    public Collection $getEvents;

    public function __construct(Events $actualEvents)
    {
        $this->events = $actualEvents;

        $this->createEvents = collect();
        $this->quickCreateEvents = collect();
        $this->findEvents = collect();
        $this->getEvents = collect();
    }

    public function create(array $properties, string $calendarId = null, $optParams = [])
    {
        $event = Event::createFromProperties($properties, $calendarId);

        $this->createEvents->push([
            'properties' => $properties,
            'calendarId' => $calendarId,
            'optParams' => $optParams,
        ]);

        return $event;
    }

    public function quickCreate(string $text)
    {
        $event = new Event;

        $event->calendarId = $this->events->getGoogleCalendarId();

        $this->quickCreateEvents->put($text, $event);

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

    public function getGoogleCalendarId(string $calendarId = null): string
    {
        return $this->events->getGoogleCalendarId($calendarId);
    }

    public function find($eventId, string $calendarId = null): Event
    {
        $fake = $this->findEvents->first(function ($event) use ($eventId, $calendarId) {
            return (is_null($event['eventId']) || $event['eventId'] == $eventId)
                && (is_null($event['calendarId']) || $event['calendarId'] == $calendarId);
        });

        if (is_null($fake)) {
            throw MissingFake::missingFindEvents();
        }

        return Event::createFromProperties($fake['event']);
    }

    public function fakeFind(
        iterable $event,
        string $eventId = null,
        string $calendarId = null,
    ) {
        $this->findEvents->push([
            'event' => $event,
            'eventId' => $eventId,
            'calendarId' => $calendarId,
        ]);

        return $this;
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

    public function assertNothingCreated()
    {
        assertTrue($this->createEvents->isEmpty(), 'An event was created.');
    }

    public function assertQuickCreated(string $text)
    {
        assertTrue($this->quickCreateEvents->has($text), 'No fake quick create event matches the given text.');
    }

    public function assertNotQuickCreated(string $text)
    {
        assertFalse($this->quickCreateEvents->has($text), 'A fake quick create event matches the given text.');
    }

    public function assertNothingQuickCreated()
    {
        assertTrue($this->quickCreateEvents->isEmpty(), 'An event was quick created.');
    }

    protected function mapEvents(array $events): Collection
    {
        return collect($events)->map(function ($event) {
            return Event::createFromProperties($event);
        });
    }
}
