<?php

class KMLFeatures extends FeatureAdapter
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
    return $this->featureCollectionToKML($feature);
  }

  public function featureFromText($text) {
    // Change tags to lower-case
    preg_match_all('%(</?[^? ><![]+)%m', $text, $result, PREG_PATTERN_ORDER);
    $result = $result[0];

    $result = array_unique($result);
    sort($result);
    $result = array_reverse($result);

    foreach ($result as $search) {
      $replace = mb_strtolower($search, mb_detect_encoding($search));
      $text = str_replace($search, $replace, $text);
    }

    // Load into DOMDocument
    $xmlobj = new DOMDocument();
    //@
    $xmlobj->loadXML($text);
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
    $id = "";
    $geom_types = geoPHP::geometryList();
    $placemark_elements = $this->xmlobj->getElementsByTagName('placemark');

    if ($placemark_elements->length) {
      foreach ($placemark_elements as $placemark) {

        $properties = [];

        foreach ($placemark->childNodes as $child) {
          // Node names are all the same, except for MultiGeometry, which maps to GeometryCollection
          $node_name = $child->nodeName == 'multiGeometry' ? 'geometrycollection' : $child->nodeName;
          
          if (array_key_exists($node_name, $geom_types))
          {
            $adapter = new KML;
            $geometry = $adapter->read($child->ownerDocument->saveXML($child));
          }
          elseif ($node_name == 'extendeddata')
          {
            foreach ($child->childNodes as $data) {
              if ($data->nodeName != '#text') {
                if ($data->nodeName == 'data') {
                  $value = $data->getElementsByTagName('value')[0];
                  $properties[$data->getAttribute('name')] = preg_replace('/\n\s+/',' ',trim($value->textContent));
                }
                elseif ($data->nodeName == 'schemadata')
                {
                  foreach ($data->childNodes as $schemadata) {
                    if ($schemadata->nodeName != '#text') {
                      $properties[$schemadata->getAttribute('name')] = preg_replace('/\n\s+/',' ',trim($schemadata->textContent));
                    }
                  }
                }

              }
            }
          }
          elseif (!in_array($node_name, ['#text', 'lookat', 'style', 'styleurl']))
          {
            $properties[$child->nodeName] = preg_replace('/\n\s+/',' ',trim($child->textContent));
          }

        }

        $feature = new Feature($geometry, $properties, $id);

        $features[] = $feature;

      }
    }
    else {
      throw new Exception("Cannot Read Feature From KML");
    }

    $id = "";
    $properties = [];

    $collection = new FeatureCollection($features, $properties, $id);

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

private function featureToKML($feature) {
  $out = '<'.$this->nss.'Placemark>';
  
  $out .= '<name>'.$feature->getProperty('name').'</name>';
  $out .= '<description>'.$feature->getProperty('description').'</description>';

  $out .= $feature->getGeometry()->out('kml');

  if ($feature->getExtendedProperties()) {
    $out .= '<ExtendedData>';
    foreach ($feature->getExtendedProperties() as $key => $value) {
      $out .= '<Data name="'.$key.'">';
      $out .= '<value>';
      $out .= $feature->getProperty('name');
      $out .= '</value>';
      $out .= '</Data>';
    }
    $out .= '</ExtendedData>';
  }

  $out .= '</'.$this->nss.'Placemark>';
  return $out;
}

// private function pointToKML($geom) {
//   $out = '<'.$this->nss.'Point>';
//   if (!$geom->isEmpty()) {
//     $out .= '<'.$this->nss.'coordinates>'.$geom->getX().",".$geom->getY().'</'.$this->nss.'coordinates>';
//   }
//   $out .= '</'.$this->nss.'Point>';
//   return $out;
// }

public function featureCollectionToKML($feature) {
  $out = '<'.$this->nss.'Folder>';
  $out .= '<name>'.$feature->getProperty('name').'</name>';
  $out .= '<description>'.$feature->getProperty('description').'</description>';
  foreach ($feature->getFeatures() as $comp) {
    $out .= $this->featureToKML($comp);
  }
  return $out .'</'.$this->nss.'Folder>';
}

}
