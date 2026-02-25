<?php

namespace Spatie\GoogleCalendar\Tests\Integration;

use Carbon\Carbon;
use DateTime;
use PHPUnit\Framework\Attributes\Test;
use Spatie\GoogleCalendar\Event;
use Spatie\GoogleCalendar\Facades\Events;
use Spatie\GoogleCalendar\Tests\TestCase;

class EventTest extends TestCase
{
    /** @var \Spatie\GoogleCalendar\Event */
    protected $event;

    public function setUp(): void
    {
        parent::setUp();

        $this->event = new Event;
    }

    #[Test]
    public function it_can_set_a_start_date()
    {
        $now = Carbon::now();

        $this->event->startDate = $now;

        $this->assertEquals($now->startOfDay()->format('Y-m-d'), $this->event->googleEvent['start']['date']);

        $this->assertEquals($now, $this->event->startDate);
    }

    #[Test]
    public function it_can_set_a_end_date()
    {
        $now = Carbon::now();

        $this->event->endDate = $now;

        $this->assertEquals($now->format('Y-m-d'), $this->event->googleEvent['end']['date']);
    }

    #[Test]
    public function it_can_set_a_start_date_time()
    {
        $now = Carbon::now();

        $this->event->startDateTime = $now;

        $this->assertEquals($now->format(DateTime::RFC3339), $this->event->googleEvent['start']['dateTime']);
    }

    #[Test]
    public function it_can_set_an_end_date_time()
    {
        $now = Carbon::now();

        $this->event->endDateTime = $now;

        $this->assertEquals($now->format(DateTime::RFC3339), $this->event->googleEvent['end']['dateTime']);
    }

    #[Test]
    public function it_can_determine_a_sort_date()
    {
        $now = Carbon::now();

        $event = new Event;

        $this->assertEmpty($event->getSortDate());

        $event->startDateTime = $now;

        $this->assertEquals($now, $event->getSortDate());
    }

    #[Test]
    public function it_can_set_a_name()
    {
        $this->event->name = 'testname';

        $this->assertEquals('testname', $this->event->googleEvent['summary']);
    }

    #[Test]
    public function it_can_set_a_color()
    {
        $this->event->googleEvent->setColorId(11);

        $this->assertEquals(11, $this->event->googleEvent['colorId']);
    }

    #[Test]
    public function it_can_set_a_description()
    {
        $this->event->description = 'Test Description';

        $this->assertEquals('Test Description', $this->event->googleEvent['description']);
    }

    #[Test]
    public function it_can_set_a_location()
    {
        $this->event->location = 'Test Location';

        $this->assertEquals('Test Location', $this->event->googleEvent->getLocation());
    }

    #[Test]
    public function it_can_set_a_source()
    {
        $this->event->source = [
            'title' => 'Test Source Title',
            'url' => 'http://testsource.url',
        ];

        $this->assertEquals('Test Source Title', $this->event->googleEvent->getSource()->title);
        $this->assertEquals('http://testsource.url', $this->event->googleEvent->getSource()->url);
    }

    #[Test]
    public function it_can_set_multiple_attendees()
    {
        $attendees = [
            [
                'name' => 'Spatie',
                'email' => 'spatie@example.com',
                'comment' => "I'm ready for this meeting",
            ],
            [ 'email' => 'devgummibeer@example.com' ],
        ];

        $this->event->addAttendee($attendees[0]);
        $this->event->addAttendee($attendees[1]);

        $this->assertCount(2, $this->event->googleEvent->getAttendees());
        $this->assertInstanceOf(\Google_Service_Calendar_EventAttendee::class, $this->event->googleEvent->getAttendees()[0]);
        $this->assertEquals($attendees[0]['email'], $this->event->googleEvent->getAttendees()[0]->getEmail());
        $this->assertEquals($attendees[0]['name'], $this->event->googleEvent->getAttendees()[0]->getDisplayName());
        $this->assertEquals($attendees[0]['comment'], $this->event->googleEvent->getAttendees()[0]->getComment());
        $this->assertEquals($attendees[1]['email'], $this->event->googleEvent->getAttendees()[1]->getEmail());
    }

    #[Test]
    public function it_can_set_a_meet_link()
    {
        $this->event->addMeetLink();

        $this->assertNotNull($this->event->googleEvent->getConferenceData());
        $this->assertEquals('hangoutsMeet', $this->event->googleEvent->getConferenceData()->getCreateRequest()->getConferenceSolutionKey()->getType());
    }

    #[Test]
    public function it_can_determine_if_an_event_is_an_all_day_event()
    {
        $event = new Event;

        $event->startDate = Carbon::now();

        $this->assertTrue($event->isAllDayEvent());

        $event->startDateTime = Carbon::now();

        $this->assertFalse($event->isAllDayEvent());
    }

    #[Test]
    public function it_can_create_an_event_based_on_a_text_string()
    {
        $eventsFake = Events::fake();

        $event = $this->event->quickSave('Appointment at Somewhere on April 25 10am-10:25am');

        $eventsFake->assertQuickCreated('Appointment at Somewhere on April 25 10am-10:25am');
        $this->assertInstanceOf(Event::class, $event);
        $this->assertNotSame($this->event, $event);
    }

    #[Test]
    public function it_can_create_an_event_based_on_a_text_string_statically()
    {
        $eventsFake = Events::fake();

        $event = Event::quickCreate('Appointment at Somewhere on April 25 10am-10:25am');

        $eventsFake->assertQuickCreated('Appointment at Somewhere on April 25 10am-10:25am');
        $this->assertInstanceOf(Event::class, $event);
    }

    #[Test]
    public function it_can_set_a_timezone_that_is_a_string()
    {
        $now = Carbon::now()->setTimezone('Indian/Reunion');

        $this->event->endDateTime = $now;

        $this->assertEquals((string) $now->getTimezone(), 'Indian/Reunion');
    }

    /** @test */
    public function it_can_see_if_it_is_the_same_event(): void
    {
        $event1 = Event::createFromProperties(['id' => '123'], '456');
        $event2 = Event::createFromProperties(['id' => '123'], '456');

        $this->assertTrue($event1->is($event2));
    }

    /** @test */
    public function it_will_not_match_on_just_id(): void
    {
        $event1 = Event::createFromProperties(['id' => '123'], '456');
        $event2 = Event::createFromProperties(['id' => '123'], '789');

        $this->assertFalse($event1->is($event2));
    }

    /** @test */
    public function it_will_not_match_on_just_calendar_id(): void
    {
        $event1 = Event::createFromProperties(['id' => '123'], '456');
        $event2 = Event::createFromProperties(['id' => '456'], '456');

        $this->assertFalse($event1->is($event2));
    }

}
