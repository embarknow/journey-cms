<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Schemas\Schema;
use Entry;

interface DataInterface
{
    public function prepare(Entry $entry, $field, $new = null, $old = null);

    public function validate(Entry $entry, $field, $data);

    public function read(Schema $section, Entry $entry, $field);

    public function write(Schema $section, Entry $entry, $field, $data);
}