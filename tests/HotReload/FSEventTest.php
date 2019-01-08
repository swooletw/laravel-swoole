<?php

namespace SwooleTW\Http\Tests\HotReload;

use Carbon\Carbon;
use SwooleTW\Http\HotReload\FSEvent;
use SwooleTW\Http\Tests\TestCase;

/**
 * Class FSEventTest
 */
class FSEventTest extends TestCase
{
    public function testObjectIsCorrect()
    {
        $date = Carbon::parse('Mon Dec 31 01:18:34 2018');
        $path = '/Some/Path/To/File/File.php';
        $events = explode(' ', 'Renamed OwnerModified IsFile');
        $events = array_intersect(FSEvent::getPossibleTypes(), $events);
        asort($events);
        $event = new FSEvent($date, $path, $events);

        $this->assertTrue(array_diff($event->getTypes(), [FSEvent::Renamed, FSEvent::OwnerModified]) === []);
        $this->assertTrue((new Carbon('Mon Dec 31 01:18:34 2018'))->eq($event->getWhen()));
        $this->assertEquals('/Some/Path/To/File/File.php', $event->getPath());
        $this->assertTrue($event->isType(FSEvent::Renamed));
        $this->assertTrue($event->isType(FSEvent::OwnerModified));
    }
}