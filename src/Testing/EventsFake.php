<?php

namespace Spatie\GoogleCalendar\Testing;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Testing\Fakes\Fake;
use Spatie\GoogleCalendar\Event;
use Spatie\GoogleCalendar\Facades\Events;

class EventsFake extends Events implements Fake
{
    public Collection $getEvents;

    public function __construct()
    {
        $this->getEvents = collect();
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
}
