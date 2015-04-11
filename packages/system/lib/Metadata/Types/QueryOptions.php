<?php

namespace Embark\CMS\Metadata\Types;

use Context;
use Datasource;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;

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
