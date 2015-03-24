<?php

namespace Embark\CMS;
use DateTimeZone;

class UserDateTime extends \DateTime {
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