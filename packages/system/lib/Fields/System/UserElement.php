<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldElementInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DOMElement;
use User;

class UserElement implements FieldElementInterface
{
    use MetadataTrait;

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, SchemaInterface $schema, EntryInterface $entry)
    {
        $document = $wrapper->ownerDocument;
        $user = User::load($entry->user);

        $xml = $document->createElement('user', $user->getFullName());
        $xml->setAttribute('id', $entry->user);
        $xml->setAttribute('username', $user->username);
        $xml->setAttribute('email-address', $user->email);
        $wrapper->appendChild($xml);
    }
}
