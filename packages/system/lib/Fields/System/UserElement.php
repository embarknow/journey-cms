<?php

namespace Embark\CMS\Fields\System;

use DOMElement;
use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldElementInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\SystemDateTime;
use User;

class UserElement implements FieldElementInterface
{
    use MetadataTrait;

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, SchemaInterface $schema, EntryInterface $entry, FieldInterface $field = null)
    {
        $document = $wrapper->ownerDocument;
        $user = User::load($entry->user_id);

        $xml = $document->createElement('user', $user->getFullName());
        $xml->setAttribute('id', $entry->user_id);
        $xml->setAttribute('username', $user->username);
        $xml->setAttribute('email-address', $user->email);
        $wrapper->appendChild($xml);
    }
}
