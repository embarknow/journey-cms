<?php

namespace Embark\CMS;

use DateTime;
use DateTimeZone;
use Embark\CMS\SystemDateTime;

class UserDateTime extends DateTime
{
    public function __construct($time = 'now')
    {
        parent::__construct($time, new DateTimeZone(date_default_timezone_get()));
    }

    public function toSystemDateTime()
    {
        $date = new SystemDateTime();

        $date->setTimestamp($this->getTimestamp());

        return $date;
    }
}
