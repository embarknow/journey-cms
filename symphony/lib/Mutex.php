<?php

class Mutex
{
    public static function acquire($id, $ttl = 5)
    {
        $id = md5($id);
        $exists = apc_fetch($id);

        if ($exists) {
            return false;
        }

        return apc_store($id, true, $ttl);
    }

    public static function release($id)
    {
        return apc_delete(md5($id));
    }
}
