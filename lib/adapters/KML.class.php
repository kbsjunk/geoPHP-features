<?php

class KML extends FeatureAdapter
{
  private $namespace = FALSE;
  private $nss = ''; // Name-space string. eg 'georss:'

  /**
   * Read KML string into geometry objects
   *
   * @param string $kml A KML string
   *
   * @return Feature|FeatureCollection
   */
  public function read($kml) {
    return $this->featureFromText($kml);
  }

  /**
   * Serialize features into a KML string.
   *
   * @param Feature $feature
   *
   * @return string The KML string representation of the input features
   */
  public function write(AbstractFeature $feature, $namespace = FALSE) {
    if ($namespace) {
      $this->namespace = $namespace;
      $this->nss = $namespace.':';
    }
    return $this->featureToKML($feature);
  }

  public function featureFromText($text) {
    // Change tags to lower-case
    preg_match_all('%(</?[^? >]+)%m', $text, $result, PREG_PATTERN_ORDER);
    $result = $result[0];

    $result = array_unique($result);

    foreach ($result as $search) {
      $replace = mb_strtolower($search, mb_detect_encoding($search));
      $text = str_replace($search, $replace, $text);
    }

    // Load into DOMDocument
    $xmlobj = new DOMDocument();
    @$xmlobj->loadXML($text);
    if ($xmlobj === false) {
      throw new Exception("Invalid KML: ". $text);
    }

    $this->xmlobj = $xmlobj;
    try {
      $feature = $this->featureFromXML();
    } catch(InvalidText $e) {
      throw new Exception("Cannot Read Feature From KML: ". $text);
    } catch(Exception $e) {
      throw $e;
    }

    return $feature;
  }

  protected function featureFromXML() {
    $features = [];
    $properties = [];
    $id = null;
    $geom_types = GeoPHP::geometryList();
    $placemark_elements = $this->xmlobj->getElementsByTagName('placemark');
    if ($placemark_elements->length) {
      foreach ($placemark_elements as $placemark) {

        $properties = [];

        foreach ($placemark->childNodes as $child) {
          // Node names are all the same, except for MultiGeometry, which maps to GeometryCollection
          $node_name = $child->nodeName == 'multiGeometry' ? 'geometrycollection' : $child->nodeName;
          
          if (!array_key_exists($node_name, $geom_types)) {
            $properties[$child->nodeName] = $child->textContent;
          }
          else
          {
            $adapter = new KMLAdapter;
            $geometry = $adapter->read($child->saveXML());
          }

        }

        $feature = new Feature($geometry, $properties, $id);

        $features[] = $feature;
      }
    }
    else {
      $adapter = new KMLAdapter;
      return $adapter->read($this->xmlobj->saveXML());
    }
    // $id = null;// $properties = [];

    $collection = new FeatureCollection($features);//, $properties, $id);

  return $collection;
}

protected function childElements($xml, $nodename = '') {
  $children = array();
  if ($xml->childNodes) {
    foreach ($xml->childNodes as $child) {
      if ($child->nodeName == $nodename) {
        $children[] = $child;
      }
    }
  }
  return $children;
}



protected function parseGeometryCollection($xml) {
  $components = array();
  $geom_types = geoPHP::geometryList();
  foreach ($xml->childNodes as $child) {
    $nodeName = ($child->nodeName == 'linearring') ? 'linestring' : $child->nodeName;
    if (array_key_exists($nodeName, $geom_types)) {
      $function = 'parse'.$geom_types[$nodeName];
      $components[] = $this->$function($child);
    }
  }
  return new GeometryCollection($components);
}

private function featureToKML($geom) {
  $type = strtolower($geom->getGeomType());
  switch ($type) {
    case 'point':
    return $this->pointToKML($geom);
    break;
    case 'linestring':
    return $this->linestringToKML($geom);
    break;
    case 'polygon':
    return $this->polygonToKML($geom);
    break;
    case 'multipoint':
    case 'multilinestring':
    case 'multipolygon':
    case 'geometrycollection':
    return $this->collectionToKML($geom);
    break;
  }
}

private function pointToKML($geom) {
  $out = '<'.$this->nss.'Point>';
  if (!$geom->isEmpty()) {
    $out .= '<'.$this->nss.'coordinates>'.$geom->getX().",".$geom->getY().'</'.$this->nss.'coordinates>';
  }
  $out .= '</'.$this->nss.'Point>';
  return $out;
}

public function collectionToKML($geom) {
  $components = $geom->getComponents();
  $str = '<'.$this->nss.'MultiGeometry>';
  foreach ($geom->getComponents() as $comp) {
    $sub_adapter = new KML();
    $str .= $sub_adapter->write($comp);
  }

  return $str .'</'.$this->nss.'MultiGeometry>';
}

}
