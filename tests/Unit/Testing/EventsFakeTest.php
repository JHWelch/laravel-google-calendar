<?php

namespace Spatie\GoogleCalendar\Tests\Unit\Testing;

use Illuminate\Support\Collection;
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
        $this->eventsFake = Events::fake();
    }

    /** @test */
    public function it_can_fake_return_of_get(): void
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
            ]
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
    public function it_can_fake_a_specific_get(): void
    {
        $this->eventsFake->fakeGet(
            events: [
                [
                    'summary' => 'Event 1',
                ]
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
                ]
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
    public function it_can_only_match_on_fields_specified(): void
    {
        $this->eventsFake->fakeGet(
            events: [
                [
                    'summary' => 'Event 1',
                ],
                [
                    'summary' => 'Event 2',
                ]
            ],
            startDateTime: now(),
            calendarId: 'calendarId'
        );

        $events = Events::get(now(), now()->addHour(), ['orderBy' => 'startTime'], 'calendarId');

        $this->assertCount(2, $events);
    }

    /** @test */
    public function it_will_throw_an_error_if_none_matches(): void
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
    public function it_can_assert_against_created_events(): void
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
    public function it_will_fail_assertion_if_nothing_created(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        $this->eventsFake->assertCreated(
            ['summary' => 'Non-existent Event'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );
    }

    /** @test */
    public function it_will_fail_assertion_if_event_properties_do_not_match(): void
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
    public function it_can_assert_something_not_created(): void
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
    public function it_will_fail_assertion_if_event_is_created(): void
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
    public function it_can_assert_nothing_is_created(): void
    {
        $this->eventsFake->assertNothingCreated();
    }

    /** @test */
    public function it_fails_if_anything_is_created(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);

        Events::create(
            ['summary' => 'Event 1'],
            'calendarId',
            ['sendUpdates' => 'all'],
        );

        $this->eventsFake->assertNothingCreated();
    }
}
