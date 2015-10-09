<?php

namespace GeoPHPFeatures\Adapters;

use GeoPHPFeatures\Features\Feature;
use GeoPHPFeatures\Features\FeatureCollection;

abstract class Unfeatured extends FeatureAdapter
{

	public function read($input) {

	}

	public function write(Feature $feature) {
		if ($feature instanceof FeatureCollection) {
			
		}
	}

}