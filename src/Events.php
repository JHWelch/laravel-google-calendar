<?php

namespace Spatie\GoogleCalendar;

use Carbon\CarbonInterface;
use Google_Service_Calendar_Event;
use Illuminate\Support\Collection;
use Spatie\GoogleCalendar\Exceptions\InvalidConfiguration;

class Events
{
    /**
     * @param array|Event $event
     */
    public function create(
        mixed $event,
        ?string $calendarId = null,
        array $optParams = [],
    ): Event {
        if (is_array($event)) {
            $event = Event::createFromProperties($event, $calendarId);
        }

        $googleCalendar = $this->getGoogleCalendar($event->getCalendarId());

        $optParams = array_merge($optParams, $event->getAdditionalOptParams());

        $googleEvent = $googleCalendar->insertEvent($event, $optParams);

        return Event::createFromGoogleCalendarEvent($googleEvent, $googleCalendar->getCalendarId());
    }

    public function quickCreate(string $text, ?string $calendarId = null): Event
    {
        $googleCalendar = $this->getGoogleCalendar($calendarId);

        $googleEvent = $googleCalendar->insertEventFromText($text);

        return Event::createFromGoogleCalendarEvent($googleEvent, $googleCalendar->getCalendarId());
    }

    public function update(Event $event, array $optParams = []): Event
    {
        $googleCalendar = $this->getGoogleCalendar($event->getCalendarId());

        $optParams = array_merge($optParams, $event->getAdditionalOptParams());

        $googleEvent = $googleCalendar->updateEvent($event, $optParams);

        return Event::createFromGoogleCalendarEvent($googleEvent, $googleCalendar->getCalendarId());
    }

    public function find(string $eventId, string $calendarId = null): Event
    {
        $googleCalendar = $this->getGoogleCalendar($calendarId);

        $googleEvent = $googleCalendar->getEvent($eventId);

        return Event::createFromGoogleCalendarEvent($googleEvent, $calendarId);
    }

    public function get(
        CarbonInterface $startDateTime = null,
        CarbonInterface $endDateTime = null,
        array $queryParameters = [],
        string $calendarId = null
    ): Collection {
        $googleCalendar = $this->getGoogleCalendar($calendarId);

        $googleEvents = $googleCalendar->listEvents($startDateTime, $endDateTime, $queryParameters);

        $googleEventsList = $googleEvents->getItems();

        while ($googleEvents->getNextPageToken()) {
            $queryParameters['pageToken'] = $googleEvents->getNextPageToken();

            $googleEvents = $googleCalendar->listEvents($startDateTime, $endDateTime, $queryParameters);

            $googleEventsList = array_merge($googleEventsList, $googleEvents->getItems());
        }

        $useUserOrder = isset($queryParameters['orderBy']);

        return collect($googleEventsList)
            ->map(function (Google_Service_Calendar_Event $event) use ($calendarId) {
                return Event::createFromGoogleCalendarEvent($event, $calendarId);
            })
            ->sortBy(function (Event $event, $index) use ($useUserOrder) {
                if ($useUserOrder) {
                    return $index;
                }

                return $event->sortDate;
            })
            ->values();
    }

    public function delete(
        string $eventId,
        ?string $calendarId = null,
        array $optParams = [],
    ): void {
        $this->getGoogleCalendar($calendarId)->deleteEvent($eventId, $optParams);
    }

    public function getGoogleCalendar(string $calendarId = null): GoogleCalendar
    {
        $calendarId = $this->getGoogleCalendarId($calendarId);

        return GoogleCalendarFactory::createForCalendarId($calendarId);
    }

    public function getGoogleCalendarId(string $calendarId = null): string
    {
        $id = $calendarId ?? config('google-calendar.calendar_id');

        if (is_null($id)) {
            throw InvalidConfiguration::calendarIdNotSpecified();
        }

        return $id;
    }
}
