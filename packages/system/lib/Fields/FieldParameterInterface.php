<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Schemas\SchemaInterface;

interface FieldParameterInterface extends MetadataInterface
{
    public function appendParameter(array &$parameters, DatasourceInterface $datasource, SchemaInterface $section, EntryInterface $entry);
}
