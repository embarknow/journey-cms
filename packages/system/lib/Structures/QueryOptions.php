<?php

namespace Embark\CMS\Structures;

use Context;
use Datasource;

class QueryOptions implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setFilters([
            'distinct-select' =>    new Boolean(),
            'reduce-right-joins' => new Boolean()
        ]);
        $this->setDefaults([
            'distinct-select' =>    true,
            'reduce-right-joins' => false
        ]);
    }
}
