<?php

namespace GeoPHPFeatures\Features;

class FeatureCollection extends AbstractFeature
{

	protected $feature_type = 'FeatureCollection';

	protected $properties;

	protected $features;

	protected $id;

	public function __construct($features = [], $properties = [], $id = null)
	{

		$this->setFeatures($features);
		$this->properties = $properties;
		$this->id = $id;
	}

	public function getFeatures()
	{
		return $this->features;
	}

	public function setFeatures(array $features)
	{
		foreach ($features as $feature) {
			if (!$feature instanceof Feature) {
				throw new \InvalidArgumentException('FeatureCollection may only contain Feature objects');
			}
		}

		$this->features = $features;
	}

	public function addFeature(Feature $feature)
	{
		$this->features[] = $feature;
	}

	public function asArray() {

		$features = array();
		foreach ($this->features as $feature) {
			$features[] = $feature->asArray();
		}

		return [
			'type'       => $this->featureType(),
			'id'         => $this->getId(),
			'features'   => $features,
			'properties' => (array) $this->getProperties(),
		];

	}

}