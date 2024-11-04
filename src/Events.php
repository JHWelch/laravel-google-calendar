<?php

namespace Spatie\GoogleCalendar;

use Carbon\CarbonInterface;
use Google_Service_Calendar_Event;
use Illuminate\Support\Collection;

class Events
{
    /**
     * @param array $properties
     * @param string|null $calendarId
     *
     * @return mixed
     */
    public function create(array $properties, string $calendarId = null, $optParams = [])
    {
        $event = new Event;

        $event->calendarId = $this->getGoogleCalendarId($calendarId);

        foreach ($properties as $name => $value) {
            $event->$name = $value;
        }

        return $event->save('insertEvent', $optParams);
    }

    public function quickCreate(string $text)
    {
        $event = new Event;

        $event->calendarId = $this->getGoogleCalendarId();

        return $event->quickSave($text);
    }

    public function find($eventId, string $calendarId = null): Event
    {
        $googleCalendar = $this->getGoogleCalendar($calendarId);

        $googleEvent = $googleCalendar->getEvent($eventId);

        return Event::createFromGoogleCalendarEvent($googleEvent, $calendarId);
    }

    public function get(CarbonInterface $startDateTime = null, CarbonInterface $endDateTime = null, array $queryParameters = [], string $calendarId = null): Collection
    {
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

    public function getGoogleCalendar(string $calendarId = null): GoogleCalendar
    {
        $calendarId = $this->getGoogleCalendarId($calendarId);

        return GoogleCalendarFactory::createForCalendarId($calendarId);
    }

    public function getGoogleCalendarId(string $calendarId = null): string
    {
        return $calendarId ?? config('google-calendar.calendar_id');
    }
}
