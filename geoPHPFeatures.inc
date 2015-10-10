<?php

// Adapters
include_once("lib/adapters/FeatureAdapter.class.php"); // Abtract class
include_once("lib/adapters/GeoJSONFeatures.class.php");
// include_once("lib/adapters/WKT.class.php");
// include_once("lib/adapters/EWKT.class.php");
// include_once("lib/adapters/WKB.class.php");
// include_once("lib/adapters/EWKB.class.php");
include_once("lib/adapters/KMLFeatures.class.php");
// include_once("lib/adapters/GPX.class.php");
// include_once("lib/adapters/GeoRSS.class.php");
// include_once("lib/adapters/GoogleGeocode.class.php");
// include_once("lib/adapters/GeoHash.class.php");
// Geometries
include_once("lib/features/AbstractFeature.class.php"); // Abstract class
include_once("lib/features/Feature.class.php");
include_once("lib/features/FeatureCollection.class.php");

class geoPHPFeatures
{

 static function version() {
    return '0.1';
  }

  // geoPHPFeatures::load($data, $type, $other_args);
  // if $data is an array, all passed in values will be combined into a single geometry
  static function load() {
    $args = func_get_args();

    $data = array_shift($args);
    $type = array_shift($args);

    $type_map = geoPHPFeatures::getAdapterMap();

    // Auto-detect type if needed
    if (!$type) {
      // If the user is trying to load a Geometry from a Geometry... Just pass it back
      if (is_object($data)) {
        if ($data instanceOf Feature) return $data;
      }
      
      $detected = geoPHP::detectFormat($data);
      if (!$detected) {
        return FALSE;
      }

      
      $format = explode(':', $detected);
      $type = array_shift($format);
      $args = $format;
    }

    $processor_type = $type_map[$type];

    if (!$processor_type) {
      throw new Exception('geoPHPFeatures could not find an adapter of type '.htmlentities($type));
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
      // $result = geoPHPFeatures::geometryReduce($geoms);
    }

    return $result;
  }

  static function getAdapterMap() {
    return array (
      // 'wkt' =>  'WKT',
      // 'ewkt' => 'EWKT',
      // 'wkb' =>  'WKB',
      // 'ewkb' => 'EWKB',
      'json' => 'GeoJSONFeatures',
      'geojson' => 'GeoJSONFeatures',
      'kml' =>  'KMLFeatures',
      // 'gpx' =>  'GPX',
      'georss' => 'GeoRSSFeatures',
      // 'google_geocode' => 'GoogleGeocode',
      // 'geohash' => 'GeoHash',
    );
  }

}