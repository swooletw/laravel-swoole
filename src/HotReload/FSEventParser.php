<?php

namespace SwooleTW\Http\HotReload;

use Carbon\Carbon;

/**
 * Class FSEventParser
 */
class FSEventParser
{
    protected const REGEX = '/^([\S+]{3}\s+[^\/]*)\s(\/[\S+]*)\s+([\S+*\s+]*)/mi';

    protected const DATE = 1;
    protected const PATH = 2;
    protected const EVENTS = 3;

    /**
     * @param string $event
     *
     * @return \SwooleTW\Http\HotReload\FSEvent
     */
    public static function toEvent(string $event): ?FSEvent
    {
        if (preg_match(static::REGEX, $event, $matches)) {
            $date = Carbon::parse($matches[static::DATE]);
            $path = $matches[static::PATH];
            $events = explode(' ', $matches[static::EVENTS]);
            $events = array_intersect(FSEvent::getPossibleTypes(), $events);
            asort($events);

            return new FSEvent($date, $path, $events);
        }

        return null;
    }
}