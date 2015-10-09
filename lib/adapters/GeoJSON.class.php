<?php

/**
 * GeoJSON class : a geojson reader/writer.
 */
class GeoJSON extends FeatureAdapter
{
  /**
   * Given an object or a string, return a Geometry
   *
   * @param mixed $input The GeoJSON string or object
   *
   * @return object Geometry
   */
  public function read($input) {
    if (is_string($input)) {
      $input = json_decode($input);
    }
    if (!is_object($input)) {
      throw new Exception('Invalid JSON');
    }
    if (!is_string($input->type)) {
      throw new Exception('Invalid JSON');
    }

    // Check to see if it's a FeatureCollection
    if ($input->type == 'FeatureCollection') {
      $features = array();
      foreach ($input->features as $feature) {
        $features[] = $this->read($feature);
      }
      $properties = isset($input->properties) ? (array) $input->properties : [];
      return new FeatureCollection($features, $properties, @$input->id);
    }

    $adapter = new GeoJSONAdapter;

    // Check to see if it's a Feature
    if ($input->type == 'Feature') {
      $geometry = $adapter->read($input->geometry);
      $properties = isset($input->properties) ? (array) $input->properties : [];
      return new Feature($geometry, $properties, @$input->id);
    }  

    return $adapter->read($input);
  }

  /**
   * Serializes an object into a geojson string
   *
   *
   * @param Feature $obj The object to serialize
   *
   * @return string The GeoJSON string
   */
  public function write(AbstractFeature $feature, $return_array = FALSE) {
    if ($return_array) {
      return $this->getArray($feature);
    }
    else {
      return json_encode($this->getArray($feature));
    }
  }

  public function getArray($feature) {
    if ($feature instanceof AbstractFeature) {
      if ($feature instanceof FeatureCollection) {
        $feature_array = array();
        foreach ($feature->getFeatures() as $feature) {
          $feature_array[] = $this->getArray($feature);
        }
        return array(
          'type'       => 'FeatureCollection',
          'id'         => $feature->getId(),
          'properties' => (array) $feature->getProperties(),
          'features'   => $feature_array,
          );
      }
      elseif ($feature instanceof Feature) {
        return array(
          'type'       => 'Feature',
          'id'         => $feature->getId(),
          'properties' => (array) $feature->getProperties(),
          'geometry'   => $this->getArray($feature->getGeometry()),
          );
      }
    }
    else {
      $adapter = new GeoJSONAdapter;
      return $adapter->write($feature, true);
    }
  }
}


