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
    public static function create(array $properties, string $calendarId = null, $optParams = [])
    {
        $event = new Event;

        $event->calendarId = static::getGoogleCalendarId($calendarId);

        foreach ($properties as $name => $value) {
            $event->$name = $value;
        }

        return $event->save('insertEvent', $optParams);
    }

    public static function quickCreate(string $text)
    {
        $event = new Event;

        $event->calendarId = static::getGoogleCalendarId();

        return $event->quickSave($text);
    }

    public static function find($eventId, string $calendarId = null): Event
    {
        $googleCalendar = static::getGoogleCalendar($calendarId);

        $googleEvent = $googleCalendar->getEvent($eventId);

        return Event::createFromGoogleCalendarEvent($googleEvent, $calendarId);
    }

    public static function get(CarbonInterface $startDateTime = null, CarbonInterface $endDateTime = null, array $queryParameters = [], string $calendarId = null): Collection
    {
        $googleCalendar = static::getGoogleCalendar($calendarId);

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

    public static function getGoogleCalendar(string $calendarId = null): GoogleCalendar
    {
        $calendarId = static::getGoogleCalendarId($calendarId);

        return GoogleCalendarFactory::createForCalendarId($calendarId);
    }

    public static function getGoogleCalendarId(string $calendarId = null): string
    {
        return $calendarId ?? config('google-calendar.calendar_id');
    }
}
