<?php

class Extension_DS_Sections implements ExtensionInterface
{
	public function about()
	{
		return (object)[
			'name' =>			'Section Actors',
			'version' =>		'1.0.0',
			'type' => 			['Actors', 'Core'],
			'description' =>	'Create data sources from an XML string.'
		];
	}

	public function getActorTypes()
	{
		return [
			new Embark\CMS\Actors\Section\Datasource()
		];
	}
}

return 'Extension_DS_Sections';