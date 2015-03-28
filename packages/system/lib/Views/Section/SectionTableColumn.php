<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\Controller;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;

class SectionTableColumn implements MetadataInterface
{
    use MetadataTrait {
        MetadataTrait::fromXML as baseFromXML;
        MetadataTrait::toXML as baseToXML;
    }

    protected $view;

    public function __construct(SectionView $view)
    {
        $this->view = $view;
    }

    public function fromXML(DOMElement $xml)
    {
        $this->baseFromXML($xml);

        // Merge our field with a copy from the schema:
        if (isset($this['guid']) && ($this['field'] instanceof FieldInterface)) {
            $schema = Controller::read($this->view['schema']);
            $field = $schema->findFieldByGuid($this['guid']);

            foreach ($field->findAll() as $name => $value) {
                if ('resource' === $name) continue;

                $this['field'][$name] = $value;
            }
        }
    }

    public function toXML(DOMElement $xml)
    {
        $this->baseToXML($xml);

        // TODO: We may need to remove the data we added during fromXML.
    }

    public function appendHeader(DOMElement $wrapper)
    {
        $this['field']['column']->appendHeader($wrapper);
    }
}