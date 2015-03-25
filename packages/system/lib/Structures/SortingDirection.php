<?php

namespace Embark\CMS\Structures;

class SortingDirection implements MetadataValueInterface
{
    public function toXML($value)
    {
        return $this->sanitise($value);
    }

    public function fromXML($value)
    {
        return $this->sanitise($value);
    }

    public function sanitise($value)
    {
        switch (strtolower($value)) {
            case 'rand':
            case 'random':
                return 'random';

            case 'desc':
            case 'descending':
                return 'desc';

            case 'asc':
            case 'ascending':
                return 'asc';

            default:
                return $this->default;
        }
    }
}
