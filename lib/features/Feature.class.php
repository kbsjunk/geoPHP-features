<?php

class Feature extends AbstractFeature
{

	protected $feature_type = 'Feature';

	protected $properties;

	protected $geometry;

	protected $id;

	public function __construct(Geometry $geometry = null, $properties = [], $id = "")
	{
		$this->geometry = $geometry;
		$this->properties = (array) $properties;
		$this->id = $id;
	}

	public function getGeometry()
	{
		return $this->geometry;
	}

	public function setGeometry(Geometry $geometry)
	{
		$this->geometry = $geometry;
	}

	public function getProperties()
	{
		return $this->properties;
	}

	public function setProperties(array $properties)
	{
		$this->properties = $properties;
	}

	public function addProperties(array $properties)
	{
		$this->properties = array_merge($this->properties, $properties);
	}

	public function getProperty($key)
	{
		return isset($this->properties[$key]) ? $this->properties[$key] : null;
	}

	public function setProperty($key, $value)
	{
		$this->properties[$key] = $value;
	}

	public function getFeatures()
	{
		return [$this];
	}

	public function asArray() {

		return [
			'type'       => $this->featureType(),
			'id'         => $this->getId(),
			'geometry'   => $this->getGeometry()->asArray(),
			'properties' => (array) $this->getProperties(),
		];

	}

}