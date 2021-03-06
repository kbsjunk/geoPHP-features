<?php

abstract class AbstractFeature
{

	protected $feature_type = 'Feature';

	protected $properties;

	protected $id = "";

	public function out() {
		$args = func_get_args();

		$format = array_shift($args);
		$type_map = geoPHPFeatures::getAdapterMap();
		$processor_type = $type_map[$format];
		$processor = new $processor_type();

		array_unshift($args, $this);
		$result = call_user_func_array(array($processor, 'write'), $args);

		return $result;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getProperties()
	{
		return $this->properties;
	}

	public function getExtendedProperties()
	{
		$properties = $this->properties;
		unset($properties['name']);
		unset($properties['description']);
		return $properties;
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

	public function featureType()
	{
		return $this->feature_type;
	}

}