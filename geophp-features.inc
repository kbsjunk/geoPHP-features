<?php

namespace GeoPHPFeatures;

use GeoPHP\GeoPHP;

class GeoPHPFeatures
{

 static function version() {
    return '0.1';
  }

  // GeoPHPFeatures::load($data, $type, $other_args);
  // if $data is an array, all passed in values will be combined into a single geometry
  static function load() {
    $args = func_get_args();

    $data = array_shift($args);
    $type = array_shift($args);

    $type_map = GeoPHPFeatures::getAdapterMap();

    // Auto-detect type if needed
    if (!$type) {
      // If the user is trying to load a Geometry from a Geometry... Just pass it back
      if (is_object($data)) {
        if ($data instanceOf Feature) return $data;
      }
      
      $detected = GeoPHP::detectFormat($data);
      if (!$detected) {
        return FALSE;
      }

      
      $format = explode(':', $detected);
      $type = array_shift($format);
      $args = $format;
    }

    $processor_type = 'GeoPHPFeatures\\Adapters\\'.$type_map[$type];

    if (!$processor_type) {
      throw new Exception('GeoPHP could not find an adapter of type '.htmlentities($type));
    }

    $processor = new $processor_type();

    // Data is not an array, just pass it normally
    if (!is_array($data)) {
      $result = call_user_func_array(array($processor, "read"), array_merge(array($data), $args));
    }
    // Data is an array, combine all passed in items into a single geometry
    else {
      die('DATA IS AN ARRAY');
      // $geoms = array();
      // foreach ($data as $item) {
      //   $geoms[] = call_user_func_array(array($processor, "read"), array_merge(array($item), $args));
      // }
      // $result = GeoPHPFeatures::geometryReduce($geoms);
    }

    return $result;
  }

  static function getAdapterMap() {
    return GeoPHP::getAdapterMap();
  }

}