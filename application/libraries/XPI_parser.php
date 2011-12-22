<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class XPI_parser
{
  /**
	 * Get raw metadata from XPI file. Data is ONLY EXTRACTED.
	 * Usually there is no need to access this directly, except you need
	 * to access custom tags for special features within your application.
	 *
	 * @return array of arrays for each metadata, which contain the metadatas
	 * found.
	 **/
  public function getRawMetadata($filename){
    $reader = new XMLReader();
    $reader->open('zip://' . $filename . '#install.rdf');
    
    function fillMeta(&$reader, &$meta){
      while ($reader->read()) {
        if ($reader->nodeType == XMLREADER::ELEMENT) {
          if ($reader->namespaceURI == 'http://www.mozilla.org/2004/em-rdf#'){
            $name = explode(':', $reader->name);
            array_shift($name);
            $nodename = implode(':', $name);
          } else {
            $nodename = "::$reader->name";
          }
          fillMeta($reader, $meta[$nodename]);
        }
        else if ($reader->nodeType == XMLREADER::TEXT){
          $meta[] = htmlentities($reader->value, ENT_QUOTES, 'UTF-8', false);
        }
        else if ($reader->nodeType == XMLREADER::END_ELEMENT){
          return;
        }
      }
    }
    
    function gotoMeta(&$reader){
      while ($reader->read()) {
        if ($reader->nodeType == XMLREADER::ELEMENT) {
          if (($reader->name == 'Description') && ($reader->getAttribute('about') == 'urn:mozilla:install-manifest'))
            return;
        }
      }
    }
    
    gotoMeta($reader);
    fillMeta($reader, $meta);
    
    return $meta;
  }
	
	/**
	 * Get metadata from XPI file including localized descriptions,etc. Only
	 * known, Mozilla-rdf Metadata will be available in the returned array.
	 *
	 * @return array of arrays or values for each metadata, valued for
	 * unlocalizeable fields and arrays with locales for localizeable ones.
	 * Locale 'default' is used for default fields.
	 */
	function getFullMetadata($filename){
	  $result = [];
		
	}
}