<?php

abstract class FeatureAdapter
{

  /**
  * Read input and return a Feature or FeatureCollection
  * 
  * @return Feature|FeatureCollection
  */
  abstract public function read($input);

  /**
  * Write out a Feature or FeatureCollection in the adapter's format
  * 
  * @return mixed
  */
  abstract public function write(AbstractFeature $feature);

}