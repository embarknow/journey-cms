<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Schemas\Schema;
use Embark\CMS\SystemDateTime;
use DOMElement;
use Entry;
use Field;
use General;
use Section;
use User;

class UserElement implements MetadataInterface
{
    use MetadataTrait;

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, Schema $section, Entry $entry)
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
