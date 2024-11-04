<?php

namespace Spatie\GoogleCalendar\Tests\Unit;

use Spatie\GoogleCalendar\Facades\Events;
use Spatie\GoogleCalendar\Tests\TestCase;

class EventsTest extends TestCase
{
    /** @test */
    public function getGoogleCalendarId_returns_passed_id(): void
    {
        $id = Events::getGoogleCalendarId('test_id');

        $this->assertEquals('test_id', $id);
    }

    /** @test */
    public function getGoogleCalendarId_returns_config_default_if_null_passed(): void
    {
        config()->set('google-calendar.calendar_id', 'config_id');

        $id = Events::getGoogleCalendarId();

        $this->assertEquals('config_id', $id);
    }

    /** @test */
    public function getGoogleCalendarId_throws_error_if_no_config_set(): void
    {
        $this->expectExceptionMessage('No default calendar id set.');

        Events::getGoogleCalendarId();
    }
}
