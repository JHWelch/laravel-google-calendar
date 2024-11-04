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
}
