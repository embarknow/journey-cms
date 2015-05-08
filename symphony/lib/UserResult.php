<?php

use ResultIterator;

class UserResult extends ResultIterator
{
    public function current()
    {
        $record = parent::current();

        $user = new User;

        foreach ($record as $key => $value) {
            $user->$key = $value;
        }

        return $user;
    }
}
