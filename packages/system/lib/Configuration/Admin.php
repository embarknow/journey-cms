<?php

namespace Embark\CMS\Configuration;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;

class Admin implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
        	'minify-assets' => [
        		'filter' =>		new Boolean(),
        		'required' =>	true
        	]
        ]);
    }
}
