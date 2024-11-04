<?php

namespace Spatie\GoogleCalendar\Tests\Unit\Testing;

use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Spatie\GoogleCalendar\Event;
use Spatie\GoogleCalendar\Facades\Events;
use Spatie\GoogleCalendar\Testing\EventsFake;
use Spatie\GoogleCalendar\Tests\TestCase;

class EventsFakeTest extends TestCase
{
    public EventsFake $eventsFake;

    public function setUp(): void
    {
        parent::setUp();
        config()->set('google-calendar.calendar_id', 'defaultCalendarId');
        $this->eventsFake = Events::fake();
    }

    /** @test */
    public function fakeGet_can_fake_return_of_get(): void
    {
        $this->eventsFake->fakeGet([
            [
                'summary' => 'Event 1',
                'startDateTime' => now(),
                'endDateTime' => now()->addHour(),
            ],
            [
                'summary' => 'Event 2',
                'startDateTime' => now()->addDay(),
                'endDateTime' => now()->addDay()->addHour(),
            ],
        ]);

        $events = Events::get();

        $this->assertInstanceOf(Collection::class, $events);
        $this->assertCount(2, $events);
        [$event1, $event2] = $events;
        $this->assertInstanceOf(Event::class, $event1);
        $this->assertEquals('Event 1', $event1->summary);
        $this->assertEquals(now()->toDateTimeString(), $event1->startDateTime);
        $this->assertEquals(now()->addHour()->toDateTimeString(), $event1->endDateTime);
        $this->assertInstanceOf(Event::class, $event2);
        $this->assertEquals('Event 2', $event2->summary);
        $this->assertEquals(now()->addDay()->toDateTimeString(), $event2->startDateTime);
        $this->assertEquals(now()->addDay()->addHour()->toDateTimeString(), $event2->endDateTime);
    }

    /** @test */
    public function fakeGet_can_fake_a_specific_get(): void
    {
        $this->eventsFake->fakeGet(
            events: [
                [
                    'summary' => 'Event 1',
                ],
            ],
            startDateTime: now()->addDay(),
            endDateTime: now()->addDay()->addHour(),
            queryParameters: ['orderBy' => 'endTime'],
            calendarId: 'calendarId'
        );
        $this->eventsFake->fakeGet(
            events: [
                [
                    'summary' => 'Event 1',
                ],
                [
                    'summary' => 'Event 2',
                ],
            ],
            startDateTime: now(),
            endDateTime: now()->addHour(),
            queryParameters: ['orderBy' => 'startTime'],
            calendarId: 'calendarId'
        );

        $events = Events::get(now(), now()->addHour(), ['orderBy' => 'startTime'], 'calendarId');

        $this->assertCount(2, $events);
    }

    /** @test */
    public function fakeGet_can_only_match_on_fields_specified(): void
    {
        $this->eventsFake->fakeGet(
            events: [
                [
                    'summary' => 'Event 1',
                ],
                [
                    'summary' => 'Event 2',
                ],
            ],
            startDateTime: now(),
            calendarId: 'calendarId'
        );

        $events = Events::get(now(), now()->addHour(), ['orderBy' => 'startTime'], 'calendarId');

        $this->assertCount(2, $events);
    }

    /** @test */
    public function fakeGet_will_throw_an_error_if_none_matches(): void
    {
        $this->eventsFake->fakeGet(
            events: [],
            startDateTime: now(),
            endDateTime: now()->addHour(),
            queryParameters: ['orderBy' => 'startTime'],
            calendarId: 'calendarId'
        );

        $this->expectExceptionMessage('No fake get event matches the given parameters.');

        Events::get(now()->subHour(), now()->addHour(), ['orderBy' => 'startTime'], 'calendarId');
    }

    /** @test */
    public function assertCreated_can_assert_against_created_events(): void
    {
        Events::create(
            ['summary' => 'Event 1'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );

        $this->eventsFake->assertCreated(
            ['summary' => 'Event 1'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );
    }

    /** @test */
    public function assertCreated_will_fail_assertion_if_nothing_created(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $this->eventsFake->assertCreated(
            ['summary' => 'Non-existent Event'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );
    }

    /** @test */
    public function assertCreated_will_fail_assertion_if_event_properties_do_not_match(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        Events::create(
            ['summary' => 'Event 1'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );

        $this->eventsFake->assertCreated(
            ['summary' => 'Non-existent Event'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );
    }

    /** @test */
    public function assertNotCreated_can_assert_something_not_created(): void
    {
        Events::create(
            ['summary' => 'Event 1'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );

        $this->eventsFake->assertNotCreated(
            ['summary' => 'Non-existent Event'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );
    }

    /** @test */
    public function assertNotCreated_will_fail_assertion_if_event_is_created(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        Events::create(
            ['summary' => 'Event 1'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );

        $this->eventsFake->assertNotCreated(
            ['summary' => 'Event 1'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );
    }

    /** @test */
    public function assertNothingCreated_can_assert_nothing_is_created(): void
    {
        $this->eventsFake->assertNothingCreated();
    }

    /** @test */
    public function assertNothingCreated_fails_if_anything_is_created(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        Events::create(
            ['summary' => 'Event 1'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );

        $this->eventsFake->assertNothingCreated();
    }

    /** @test */
    public function fakeQuickCreate_will_mock_returned_event(): void
    {
        $this->eventsFake->fakeQuickCreate([
            'summary' => 'Event 1',
            'startDateTime' => today(),
            'endDateTime' => today()->addHour(),
        ], 'Event 1');

        $event = Events::quickCreate('Event 1');

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('Event 1', $event->summary);
        $this->assertEquals(today(), $event->startDateTime);
        $this->assertEquals(today()->addHour(), $event->endDateTime);
    }

    /** @test */
    public function fakeQuickCreate_can_mock_default(): void
    {
        $this->eventsFake->fakeQuickCreate([
            'summary' => 'Event 1',
            'startDateTime' => today(),
            'endDateTime' => today()->addHour(),
        ]);

        $event = Events::quickCreate('Event 1');

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('Event 1', $event->summary);
        $this->assertEquals(today(), $event->startDateTime);
        $this->assertEquals(today()->addHour(), $event->endDateTime);
    }

    /** @test */
    public function assertQuickCreated_can_assert_against_quick_created_events(): void
    {
        Events::quickCreate('Event 1');

        $this->eventsFake->assertQuickCreated('Event 1');
    }

    /** @test */
    public function assertQuickCreated_will_fail_assertion_if_nothing_quick_created(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $this->eventsFake->assertQuickCreated('Non-existent Event');
    }

    /** @test */
    public function assertQuickCreated_will_fail_assertion_if_event_properties_do_not_match(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        Events::quickCreate('Event 1');

        $this->eventsFake->assertQuickCreated('Non-existent Event');
    }

    /** @test */
    public function assertQuickCreated_can_still_assert_against_faked_event(): void
    {
        $this->eventsFake->fakeQuickCreate([
            'summary' => 'Event 1',
            'startDateTime' => today(),
            'endDateTime' => today()->addHour(),
        ]);

        Events::quickCreate('Event 1');

        $this->eventsFake->assertQuickCreated('Event 1');
    }

    /** @test */
    public function assertNotQuickCreated_can_assert_something_not_quick_created(): void
    {
        Events::quickCreate('Event 1');

        $this->eventsFake->assertNotQuickCreated('Non-existent Event');
    }

    /** @test */
    public function assertNotQuickCreated_will_fail_assertion_if_event_is_quick_created(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        Events::quickCreate('Event 1');

        $this->eventsFake->assertNotQuickCreated('Event 1');
    }

    /** @test */
    public function assertNothingQuickCreated_can_assert_nothing_is_quick_created(): void
    {
        $this->eventsFake->assertNothingQuickCreated();
    }

    /** @test */
    public function assertNothingQuickCreated_fails_if_anything_is_quick_created(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        Events::quickCreate('Event 1');

        $this->eventsFake->assertNothingQuickCreated();
    }

    /** @test */
    public function fakeFind_can_fake_return_of_event(): void
    {
        $this->eventsFake->fakeFind([
            'summary' => 'Event 1',
            'startDateTime' => now(),
            'endDateTime' => now()->addHour(),
        ]);

        $event = Events::find('eventId');

        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('Event 1', $event->summary);
        $this->assertEquals(now()->toDateTimeString(), $event->startDateTime);
        $this->assertEquals(now()->addHour()->toDateTimeString(), $event->endDateTime);
    }

    /** @test */
    public function fakeFind_can_fake_a_specific_event(): void
    {
        $this->eventsFake->fakeFind(
            event: [
                'summary' => 'Non Matching',
            ],
            eventId: 'eventId1',
            calendarId: 'calendarId1'
        );
        $this->eventsFake->fakeFind(
            event: [
                'summary' => 'Matching',
            ],
            eventId: 'eventId2',
            calendarId: 'calendarId2'
        );

        $event = Events::find('eventId2', 'calendarId2');

        $this->assertEquals('Matching', $event->summary);
    }

    /** @test */
    public function fakeFind_can_only_match_on_fields_specified(): void
    {
        $this->eventsFake->fakeFind(
            event: [
                'summary' => 'Event 1',
            ],
            eventId: 'eventId',
        );

        $event = Events::find('eventId', 'calendarId');

        $this->assertEquals('Event 1', $event->summary);
    }

    /** @test */
    public function fakeFind_will_throw_an_error_if_none_matches(): void
    {
        $this->eventsFake->fakeFind(
            event: [
                'summary' => 'Event 1',
            ],
            eventId: 'eventId',
        );

        $this->expectExceptionMessage('No fake find event matches the given parameters.');

        Events::find('nonExistentEventId');
    }

    /** @test */
    public function getGoogleCalendar_returns_mocked_calendar_object(): void
    {
        $calendar = $this->eventsFake->getGoogleCalendar();

        $this->assertInstanceOf(MockInterface::class, $calendar);
        $this->assertEquals('defaultCalendarId', $calendar->getCalendarId());
    }

    /** @test */
    public function getGoogleCalendar_returns_mocked_calendar_object_for_specific_calendar_id(): void
    {
        $calendar = $this->eventsFake->getGoogleCalendar('calendarId');

        $this->assertInstanceOf(MockInterface::class, $calendar);
        $this->assertEquals('calendarId', $calendar->getCalendarId());
    }

    /** @test */
    public function getGoogleCalendar_returns_same_mocked_calendar_object_for_same_calendar_id(): void
    {
        $calendar1 = $this->eventsFake->getGoogleCalendar('calendarId');
        $calendar2 = $this->eventsFake->getGoogleCalendar('calendarId');

        $this->assertSame($calendar1, $calendar2);
    }

    /** @test */
    public function fakeGoogleCalendar_pre_mocks_a_calendar_before_get_is_called(): void
    {
        $calendar = $this->eventsFake->fakeGoogleCalendar('calendarId');

        $this->assertSame($calendar, $this->eventsFake->getGoogleCalendar('calendarId'));
    }
}
