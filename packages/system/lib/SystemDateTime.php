<?php

namespace Embark\CMS;

use DateTime;
use DateTimeZone;
use Embark\CMS\UserDateTime;

class SystemDateTime extends DateTime
{
    public function __construct($time = 'now')
    {
        parent::__construct($time, new DateTimeZone('UTC'));
    }

    public function toUserDateTime()
    {
        $date = new UserDateTime();

        $date->setTimestamp($this->getTimestamp());

        return $date;
    }
}
