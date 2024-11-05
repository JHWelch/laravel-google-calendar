<?php

namespace Spatie\GoogleCalendar;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTime;
use Google_Service_Calendar_ConferenceData;
use Google_Service_Calendar_ConferenceSolutionKey;
use Google_Service_Calendar_CreateConferenceRequest;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventAttendee;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Calendar_EventSource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\GoogleCalendar\Facades\Events;

class Event
{
    /** @var \Google_Service_Calendar_Event */
    public $googleEvent;

    /** @var ?string */
    protected $calendarId;

    /** @var array */
    protected $attendees;

    /** @var bool */
    protected $hasMeetLink = false;

    public function __construct()
    {
        $this->attendees = [];
        $this->googleEvent = new Google_Service_Calendar_Event;
    }

    /**
     * @param string|null $calendarId
     * @return static
     */
    public static function createFromProperties(iterable $properties, string $calendarId = null): self
    {
        $event = new static;

        $event->calendarId = Events::getGoogleCalendarId($calendarId);

        foreach ($properties as $name => $value) {
            $event->$name = $value;
        }

        return $event;
    }

    /**
     * @param $calendarId
     * @return static
     */
    public static function createFromGoogleCalendarEvent(Google_Service_Calendar_Event $googleEvent, $calendarId): self
    {
        $event = new static;

        $event->googleEvent = $googleEvent;
        $event->calendarId = $calendarId;

        return $event;
    }

    public function __get($name)
    {
        $name = $this->getFieldName($name);

        if ($name === 'sortDate') {
            return $this->getSortDate();
        }

        if ($name === 'source') {
            return [
                'title' => $this->googleEvent->getSource()->title,
                'url' => $this->googleEvent->getSource()->url,
            ];
        }

        $value = Arr::get($this->googleEvent, $name);

        if (in_array($name, ['start.date', 'end.date']) && $value) {
            $value = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        if (in_array($name, ['start.dateTime', 'end.dateTime']) && $value) {
            $value = Carbon::createFromFormat(DateTime::RFC3339, $value);
        }

        return $value;
    }

    public function __set($name, $value)
    {
        $name = $this->getFieldName($name);

        if (in_array($name, ['start.date', 'end.date', 'start.dateTime', 'end.dateTime'])) {
            $this->setDateProperty($name, $value);

            return;
        }

        if ($name == 'source') {
            $this->setSourceProperty($value);

            return;
        }

        Arr::set($this->googleEvent, $name, $value);
    }

    public static function create(array $properties, ?string $calendarId = null, array $optParams = []): Event
    {
        return Events::create($properties, $calendarId, $optParams);
    }

    public static function quickCreate(string $text)
    {
        return Events::quickCreate($text);
    }

    public static function find($eventId, string $calendarId = null): Event
    {
        return Events::find($eventId, $calendarId);
    }

    public static function get(
        CarbonInterface $startDateTime = null,
        CarbonInterface $endDateTime = null,
        array $queryParameters = [],
        ?string $calendarId = null
    ): Collection {
        return Events::get($startDateTime, $endDateTime, $queryParameters, $calendarId);
    }

    public static function getGoogleCalendar(string $calendarId = null): GoogleCalendar
    {
        return Events::getGoogleCalendar($calendarId);
    }

    public static function getGoogleCalendarId(string $calendarId = null): string
    {
        return Events::getGoogleCalendarId($calendarId);
    }

    public function exists(): bool
    {
        return $this->id != '';
    }

    public function isAllDayEvent(): bool
    {
        return is_null($this->googleEvent['start']['dateTime']);
    }

    public function save($optParams = []): self
    {
        if ($this->exists()) {
            return Events::update($this, $optParams);
        }

        return Events::create($this, $this->calendarId, $optParams);
    }

    public function quickSave(string $text): self
    {
        return Events::quickCreate($text, $this->calendarId);
    }

    public function update(array $attributes, $optParams = []): self
    {
        foreach ($attributes as $name => $value) {
            $this->$name = $value;
        }

        return $this->save($optParams);
    }

    public function delete(array $optParams = []): void
    {
        if (! $this->exists()) {
            return;
        }

        Events::delete($this->calendarId)->deleteEvent($this->id, $optParams);
    }

    public function addAttendee(array $attendee): void
    {
        $this->attendees[] = new Google_Service_Calendar_EventAttendee([
            'email' => $attendee['email'],
            'comment' => $attendee['comment'] ?? null,
            'displayName' => $attendee['name'] ?? null,
            'responseStatus' => $attendee['responseStatus'] ?? 'needsAction',
        ]);

        $this->googleEvent->setAttendees($this->attendees);
    }

    public function addMeetLink(): void
    {
        $conferenceData = new Google_Service_Calendar_ConferenceData([
            'createRequest' => new Google_Service_Calendar_CreateConferenceRequest([
                'requestId' => Str::random(10),
                'conferenceSolutionKey' => new Google_Service_Calendar_ConferenceSolutionKey([
                    'type' => 'hangoutsMeet',
                ]),
            ]),
        ]);

        $this->googleEvent->setConferenceData($conferenceData);

        $this->hasMeetLink = true;
    }

    public function getSortDate(): string
    {
        if ($this->startDate) {
            return $this->startDate;
        }

        if ($this->startDateTime) {
            return $this->startDateTime;
        }

        return '';
    }

    public function getCalendarId(): ?string
    {
        return $this->calendarId;
    }

    public function is(Event $event): bool
    {
        return $this->id === $event->id
            && $this->getCalendarId() === $event->getCalendarId();
    }

    protected function setDateProperty(string $name, CarbonInterface $date)
    {
        $eventDateTime = new Google_Service_Calendar_EventDateTime;

        if (in_array($name, ['start.date', 'end.date'])) {
            $eventDateTime->setDate($date->format('Y-m-d'));
            $eventDateTime->setTimezone((string) $date->getTimezone());
        }

        if (in_array($name, ['start.dateTime', 'end.dateTime'])) {
            $eventDateTime->setDateTime($date->format(DateTime::RFC3339));
            $eventDateTime->setTimezone((string) $date->getTimezone());
        }

        if (Str::startsWith($name, 'start')) {
            $this->googleEvent->setStart($eventDateTime);
        }

        if (Str::startsWith($name, 'end')) {
            $this->googleEvent->setEnd($eventDateTime);
        }
    }

    protected function setSourceProperty(array $value)
    {
        $source = new Google_Service_Calendar_EventSource([
            'title' => $value['title'],
            'url' => $value['url'],
        ]);

        $this->googleEvent->setSource($source);
    }

    public function setColorId(int $id): void
    {
        $this->googleEvent->setColorId($id);
    }

    public function getAdditionalOptParams(): array
    {
        $optParams = [];

        if ($this->hasMeetLink) {
            $optParams['conferenceDataVersion'] = 1;
        }

        return $optParams;
    }

    protected function getFieldName(string $name): string
    {
        return [
            'name' => 'summary',
            'startDate' => 'start.date',
            'endDate' => 'end.date',
            'startDateTime' => 'start.dateTime',
            'endDateTime' => 'end.dateTime',
        ][$name] ?? $name;
    }
}
