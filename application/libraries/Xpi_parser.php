<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Xpi_parser
{
  /**
   * Recursive fill raw metadata array with data from reader, parsing using
   * htmlentities and trim.
   * 
   * @return void
   */
  private function fillMeta(&$reader, &$meta){
    while ($reader->read()) {
      if ($reader->nodeType == XMLREADER::ELEMENT) {
        if ($reader->namespaceURI == 'http://www.mozilla.org/2004/em-rdf#'){
          $name = explode(':', $reader->name);
          array_shift($name);
          $nodename = implode(':', $name);
        } else {
          $nodename = "::$reader->name";
        }
        $this->fillMeta($reader, $meta[$nodename]);
      }
      else if ($reader->nodeType == XMLREADER::TEXT){
        $value = trim($reader->value);
        $meta[] = htmlentities($value, ENT_QUOTES, 'UTF-8', false);
      }
      else if ($reader->nodeType == XMLREADER::END_ELEMENT){
        return;
      }
    }
  }
  
  /**
   * Seek reader to metadata node
   *
   * @return void
   */
  private function gotoMeta(&$reader){
    while ($reader->read()) {
      if ($reader->nodeType == XMLREADER::ELEMENT) {
        if (($reader->name == 'Description') && ($reader->getAttribute('about') == 'urn:mozilla:install-manifest'))
          return;
      }
    }
  }

  /**
   * Get raw metadata from XPI file. Data is ONLY EXTRACTED.
   * Usually there is no need to access this directly, except you need
   * to access custom tags for special features within your application.
   * All Data is parsed usign htmlentities and trim.
   *
   * @return array of arrays for each metadata, which contain the metadatas
   * found, or null if the file is invalid.
   **/
  public function getRawMetadata($filename){
    $reader = new XMLReader();
    if (!@$reader->open('zip://' . $filename . '#install.rdf'))
      return null;
    
    $this->gotoMeta($reader);
    $this->fillMeta($reader, $meta);
    
    return $meta;
  }
  
  /**
   * Get metadata from XPI file including localized descriptions,etc. Only
   * known, Mozilla-rdf Metadata will be available in the returned array.
   *
   * @return array of arrays or values for each metadata, valued for
   * unlocalizeable fields and arrays with locales for localizeable ones.
   * Locale 'default' is used for default fields. If the file is invalid,
   * null will get returned.
   */
  public function getMetadata($filename){
    $result = array();
    $raw = $this->getRawMetadata($filename);
    if ($raw === null)
      return null;
    
    $result['id'] = $raw['id'][0];
    $result['type'] = $raw['type'][0];
    $result['version'] = $raw['version'][0];
    if (array_key_exists('iconURL', $raw))
      $result['iconURL'] = $raw['iconURL'][0]; // see getIconURL
    
    $result['targetApplication'] = array();
    $tAppCount = count($raw['targetApplication']['::Description']['id']);
    for ($i = 0; $i < $tAppCount; $i++){
      $tgApp = array();
      $tgApp['id'] = $raw['targetApplication']['::Description']['id'][$i];
      $tgApp['minVersion'] =
          $raw['targetApplication']['::Description']['minVersion'][$i];
      $tgApp['maxVersion'] =
          $raw['targetApplication']['::Description']['maxVersion'][$i];
      $result['targetApplication'][] = $tgApp;
    }
    
    if (array_key_exists('requires', $raw)){
      $result['requires'] = array();
      $reqCount = count($raw['requires']['::Description']['id']);
      for ($i = 0; $i < $reqCount; $i++){
        $rqExt = array();
        $rqExt['id'] = $raw['requires']['::Description']['id'][$i];
        $rqExt['minVersion'] =
            $raw['requires']['::Description']['minVersion'][$i];
        $rqExt['maxVersion'] =
            $raw['requires']['::Description']['maxVersion'][$i];
        $result['requires'][] = $rqExt;
      }
    }
    
    $localizeableTag = array( // false: only one value per locale
        'name' => false,
        'description' => false,
        'creator' => true,
        'contributor' => true,
        'translator'=> true
      );
    foreach ($localizeableTag as $crt => $multiple){
      if (!array_key_exists($crt, $raw)) continue;
      $result[$crt] = array();
      $result[$crt]['default'] = $multiple ? $raw[$crt] : $raw[$crt][0];
      // We don't support localized fields for fields with multiple values yet.
      if ((!$multiple) && array_key_exists('localized', $raw)){
        $localeCount = count($raw['localized']['::Description']['locale']);
        for ($i = 0; $i < $localeCount; $i++){
          $result[$crt][$raw['localized']['::Description']['locale'][$i]] = $raw['localized']['::Description'][$crt][$i];
        }
      }
    }
    return $result;
  }
  
  /**
   * Get an icon by a chrome URL. This will parse the chrome.manifest and
   * return an URL using the zip protocol to access the image file
   * represented by the chrome url given. If the chrome url can't get
   * resolved within the add-on, null will be returned.
   *
   * @return zip url pointing to the image or null
   */
  public function getIconURL($filename, $url){
    // not implemented yet
    return null;
  }
}