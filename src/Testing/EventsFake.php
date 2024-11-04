<?php

namespace Spatie\GoogleCalendar\Testing;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Testing\Fakes\Fake;
use Mockery\MockInterface;
use Spatie\GoogleCalendar\Event;
use Spatie\GoogleCalendar\Events;
use Spatie\GoogleCalendar\Exceptions\InvalidConfiguration;
use Spatie\GoogleCalendar\Exceptions\Testing\MissingFake;
use Spatie\GoogleCalendar\Facades\Events as EventsFacade;
use Spatie\GoogleCalendar\GoogleCalendar;

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

    public Collection $createCalls;

    public Collection $quickCreateCalls;

    public Collection $quickCreateFakes;

    public Collection $findFakes;

    public Collection $getFakes;

    public Collection $calendarsFake;

    public function __construct(Events $actualEvents)
    {
        $this->events = $actualEvents;

        $this->createCalls = collect();
        $this->quickCreateCalls = collect();
        $this->quickCreateFakes = collect();
        $this->findFakes = collect();
        $this->getFakes = collect();
        $this->calendarsFake = collect();
    }

    public function create(array $properties, string $calendarId = null, $optParams = [])
    {
        $event = Event::createFromProperties($properties, $calendarId);

        $this->createCalls->push([
            'properties' => $properties,
            'calendarId' => $calendarId,
            'optParams' => $optParams,
        ]);

        return $event;
    }

    public function quickCreate(string $text)
    {
        $event = null;

        if ($this->quickCreateFakes->has($text)) {
            $event = Event::createFromProperties($this->quickCreateFakes->get($text));
        } elseif ($this->quickCreateFakes->has('|DEFAULT|')) {
            $event = Event::createFromProperties($this->quickCreateFakes->get('|DEFAULT|'));
        } else {
            $event = new Event;

            $event->calendarId = $this->getGoogleCalendarId();
        }

        $this->quickCreateCalls->put($text, $event);

        return $event;
    }

    public function find($eventId, string $calendarId = null): Event
    {
        $fake = $this->findFakes->first(function ($event) use ($eventId, $calendarId) {
            return (is_null($event['eventId']) || $event['eventId'] == $eventId)
                && (is_null($event['calendarId']) || $event['calendarId'] == $calendarId);
        });

        if (is_null($fake)) {
            throw MissingFake::missingFind();
        }

        return Event::createFromProperties($fake['event']);
    }

    public function get(CarbonInterface $startDateTime = null, CarbonInterface $endDateTime = null, array $queryParameters = [], string $calendarId = null): Collection
    {
        $fake = $this->getFakes->first(function ($event) use ($startDateTime, $endDateTime, $queryParameters, $calendarId) {
            return (is_null($event['startDateTime']) || $event['startDateTime']->is($startDateTime))
                && (is_null($event['endDateTime']) || $event['endDateTime']->is($endDateTime))
                && (is_null($event['calendarId']) || $event['calendarId'] == $calendarId)
                && (empty($event['queryParameters']) || $event['queryParameters'] == $queryParameters);
        });

        if (is_null($fake)) {
            throw MissingFake::missingGet();
        }

        return collect($this->mapEvents($fake['events']));
    }

    public function getGoogleCalendarId(string $calendarId = null): string
    {
        try {
            return $this->events->getGoogleCalendarId($calendarId);
        } catch (InvalidConfiguration $e) {
            return 'calendarId';
        }
    }

    public function getGoogleCalendar(string $calendarId = null)
    {
        $calendarId = $this->getGoogleCalendarId($calendarId);

        if ($this->calendarsFake->has($calendarId)) {
            return $this->calendarsFake->get($calendarId);
        }

        return $this->fakeGoogleCalendar($calendarId);
    }

    /**
     * @param iterable $event
     * @param ?string $text
     * @return $this
     */
    public function fakeQuickCreate(iterable $event, mixed $text = null)
    {
        $this->quickCreateFakes->put($text ?? '|DEFAULT|', $event);

        return $this;
    }

    public function fakeFind(
        iterable $event,
        string $eventId = null,
        string $calendarId = null,
    ) {
        $this->findFakes->push([
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
        $this->getFakes[] = [
            'events' => $events,
            'startDateTime' => $startDateTime,
            'endDateTime' => $endDateTime,
            'queryParameters' => $queryParameters,
            'calendarId' => $calendarId,
        ];

        return $this;
    }

    public function fakeGoogleCalendar(string $calendarId)
    {
        $calendar = mock(GoogleCalendar::class);
        $calendar->shouldReceive('getCalendarId')->andReturn($calendarId);
        $calendar->shouldIgnoreMissing();

        $this->calendarsFake->put($calendarId, $calendar);

        return $calendar;
    }

    public function assertCreated(array $properties, string $calendarId = null, $optParams = [])
    {
        $call = $this->createCalls->first(function ($event) use ($properties, $calendarId, $optParams) {
            return $event['properties'] == $properties
                && $event['calendarId'] == $calendarId
                && $event['optParams'] == $optParams;
        });

        assertNotNull($call, 'No fake create event matches the given parameters.');
    }

    public function assertNotCreated(array $properties, string $calendarId = null, $optParams = [])
    {
        $call = $this->createCalls->first(function ($event) use ($properties, $calendarId, $optParams) {
            return $event['properties'] == $properties
                && $event['calendarId'] == $calendarId
                && $event['optParams'] == $optParams;
        });

        assertNull($call, 'A fake create event matches the given parameters.');
    }

    public function assertNothingCreated()
    {
        assertTrue($this->createCalls->isEmpty(), 'An event was created.');
    }

    public function assertQuickCreated(string $text)
    {
        assertTrue($this->quickCreateCalls->has($text), 'No fake quick create event matches the given text.');
    }

    public function assertNotQuickCreated(string $text)
    {
        assertFalse($this->quickCreateCalls->has($text), 'A fake quick create event matches the given text.');
    }

    public function assertNothingQuickCreated()
    {
        assertTrue($this->quickCreateCalls->isEmpty(), 'An event was quick created.');
    }

    protected function mapEvents(array $events): Collection
    {
        return collect($events)->map(function ($event) {
            return Event::createFromProperties($event);
        });
    }
}
