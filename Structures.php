<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'Files.php' ;


/*----------------------------------------------------------------------------------
 *     Type management
 *----------------------------------------------------------------------------------
 */

/**
 * Get the type of a variable
 * @param Mixed $value
 * @return 'null'|'string'|'bool'|'integer'|'float'|'array'|'resource'|classname|null
 */
function typeOf($var) {
  if(is_string($var)) return 'string';
  if(is_int($var)) return 'integer';
  if(is_bool($var)) return 'boolean';
  if(is_null($var)) return 'null';
  if(is_float($var)) return 'float';
  if(is_object($var)) return get_class($var);
  if(is_array($var)) return 'array';
  if(is_resource($var)) return 'resource';
  return null ;
}

/**
 * Indicates if the parameter is an empty array  or with only integer as keys
 * @param Any $x the parameter to test
 * @return boolean false if this is not an array or if there is at lease one not integer key
 */
function is_int_map($x) {
  if (is_array($x)) {
    foreach ($x as $key => $value) {
      if (!is_int($key)) {
        return false ;
      } 
    }
    return true ;
  } else {
    return false ;
  }
}

/**
 * Indicates if the parameter is an empty array or with only string as keys
 * @param Any $x the parameter to test
 * @return boolean false if this is not an array or if there is at lease one not string key
 */
function is_string_map($x) {
  if (is_array($x)) {
    foreach ($x as $key => $value) {
      if (!is_string($key)) {
        return false ;
      }
    }
    return true ;
  } else {
    return false ;
  }
}

function is_map_to_string($x) {
  if (is_array($x)) {
    foreach ($x as $key => $value) {
      if (!is_string($value)) {
        return false ;
      }
    }
    return true ;
  } else {
    return false ;
  }    
}

/**
 * Indicates if the parameter is a map of map
 * @param Any $x a value to test
 * @return boolean true if $x an array with all elements being an array 
 */
function is_map_of_map($x) {
  if (is_array($x)) {
    foreach ($x as $key => $value) {
      if (!is_array($value)) {
        return false ;
      }
    }
    return true ;
  } else {
    return false ;
  }
}

/**
 * Merge flat arrays and remove duplicates. Does not work with nested arrays 
 * because it uses array_unique. 
 * @param List*(Any) $array2
 * @param List*(Any) $array2
 * @return Set*(Any)
 */
function union($array1, $array2) {
  //var_dump(array_unique(array_merge($array1,$array2))) ;
  return array_unique(array_merge($array1,$array2)) ;
}


/**
 * The second array is append at the end of the first one.
 * @param List*(Any) $array1 the array to modify 
 * @param List*(Any) $array2 the list to append
 * @return non. This is an in place modification of array1
 */
function array_append(&$array1,$array2) {
  array_splice($array1, count($array1), 0, $array2) ;
}




/**
 * Flatten an array by distributing the keys
 * @param Map(Scalar,NestedArray(Mixed) $map
 * @param unknown_type $keySeparator
 * @return Map(String,Mixed)
 */
function unnest_array($map,$keySeparator='.') {
  $r = array() ;
  foreach($map as $key => $value) {
    if (! is_array($value)) {
      $r[$key]=$value ;
    } else {
      $unnested = unnest_array($value,$keySeparator) ;
      foreach($unnested as $nestedkey=>$atomicValue) {
        $r[$key.$keySeparator.$nestedkey]=$atomicValue ;
      }
    }
  }
  return $r ;
}

/**
 * Group a map of map by a given key creating a indexed
 * map of map.
 * @param Scalar! $key key on which to group
 * @param Map*(Scalar!,Map*(Scalar!,Any!))! $mapOfMap
 * @param Boolean? $removeKey
 * @param String? $defaultGroupValue the value to use when
 * the row has no value for the key. If not set then the
 * rows that do not have this value set, will be removed.
 * @return Map*(Scalar!,Map*(Scalar!,Map*(Scalar!,Any!))!)
 */
function groupedBy($key,$mapOfMap,$removeKey=true,$defaultGroupValue=null) {
  $results = array() ;
  foreach($mapOfMap as $keyRow => $row) {
    if (isset($row[$key])) {
      $keyGroupValue = $row[$key] ;
      if ($removeKey) {
        unset($row[$key]) ;
      }
      if (is_array($keyGroupValue)) {
        var_dump($keyGroupValue) ;
        var_dump($row) ;
        die('groupedBy: attempt to group by '.$key.' failed. The value above is not a scalar') ;
      }
      $results[$keyGroupValue][$keyRow] = $row ;
    } else {
      if (isset($defaultGroupValue)) {
        $results[$defaultGroupValue]=$row ;
      }
    }
  }
  return $results ;
}

function project($keys,$mapOfMap,$defaultValue=null) {
  $results = array() ;
  foreach($mapOfMap as $keyRow => $row) {
    foreach($keys as $key) {
      if (isset($row[$key])) {
        $results[$keyRow][$key] = $row[$key] ;
      } else {
        if (isset($defaultValue)) {
          $results[$keyRow][$key] = $defaultValue ;
        }
      }
    }
  }
  return $results ;
}


function groupAndProject($groupSpecs,$mapOfMap) {
  $results = array() ;
  foreach ($groupSpecs as $groupName => $groupSpec) {
    $groupKey = $groupSpec['groupedBy'] ;
    $selectKeys = $groupSpec['select'] ;
    $groups = groupedBy($groupKey,$mapOfMap) ;
    foreach ($groups as $groupKeyValue => $mapOfMapSubset) {
      $results[$groupName][$groupKeyValue] = project($selectKeys,$mapOfMapSubset) ;
    }
  }
  return $results ;
}


/*----------------------------------------------------------------------------------
 *     Map of maps
 *----------------------------------------------------------------------------------
 */

/**
 * The map of map is seen as a table with each inside map beeing
 * a row and each of its elements forming a column. Return both
 * the set of all row keys and the set of all column keys.
 * 
 * MapOfMapKeysInfo == Map{
 *   'columnKeys' => List*(Scalar!)!,
 *   'rowKeys' => List*(Scalar!)!),
 *   'isFilled' => Boolean
 * }
 * 
 * @param Map*(Scalar,(Scalar,Any!)! $mapOfMap
 * @return MapOfMapKeyInfo
 * the list set of all column kys and all row keys and an
 * indicator if the mapOfMap is filled (i.e. homogeneous).
 */

function mapOfMapKeysInfo($mapOfMap) {
  $columnKeys = array() ;
  $rowKeys = array() ;
  $n = 0 ;
  foreach ($mapOfMap as $rowKey=>$row) {
    $n++ ;
    if ($n==1) {
      $columnNbOfFirstRow = count($row) ;
    }
    $rowKeys[]=$rowKey ;
    $columnKeys = array_unique(array_merge($columnKeys,array_keys($row))) ;
  }
  // because all columns are collected, if the first column as the same
  // number of columns that all columns, then the array is filled
  // that is homogeneous
  $isFilled=$columnNbOfFirstRow===count($columnKeys) ;
  return array(
      'columnKeys'=>$columnKeys,
      'rowKeys'=>$rowKeys,
      'isFilled'=>$isFilled) ;
}


/**
 * Fill a MapOfMap from a potentialy heterogeneous MapOfMap,
 * that is one in which nested amy not have allways the same keys.
 * All keys for all rows are first computed, and then each a value is
 * attributed for each row using the filler value if necessary.
 * @param inout:Map*(Scalar!,Map*(Scalar!,Any!)! $mapOfMap 
 * The map of map to fill. The map of map is changed in place.
 * 
 * type MapOfMapKeyAndHoleInfo == Map{
 *   'columnKeys' => List*(Scalar!)!,
 *   'rowKeys' => List*(Scalar!)!),
 *   'isFilled' => Boolean,
 *   'nbHolesFilled' => Integer>=0
 * }
 * 
 * @param Any? $filler a value to fill undefined cells (if any).
 * Default to an empty string.
 * 
 * @return  MapOfMapKeyAndHoleInfo
 */
function fillMapOfMap(&$mapOfMap,$filler='') {
  $nb=0 ;
  $r = mapOfMapKeysInfo($mapOfMap) ;
  if ($r['isFilled']) {
    $r['nbOfHolesFilled'] = $nb ;
    return $r ;
  } else {
    $allColumnKeys = $r['columnKeys'] ;
    foreach ($mapOfMap as $keyRow => $row){
      foreach($allColumnKeys as $columnKey) {
        if (!isset($mapOfMap[$keyRow][$columnKey])) {
          $mapOfMap[$keyRow][$columnKey] = $filler  ;
          $nb++ ;
        }
      }
    }
    $r['nbOfHolesFilled'] = $nb ;
    return $r ;
  }
}

/**
 * Transform map of map to into a two dimentsional array indexed by integers and
 * with optional column names and row names.
 * Each inside map becomes a row (with the key if $printKeys is selected).
 * Each key in a inside map leads to a colum. The first row is the table header
 * if $addRowKeys is selected. 
 * 
 * @param Map*(Scalar!,Map*(Scalar!,Any!)!)! $mapOfMap A map of map not necessarily
 * filled (homogeneous) and with arbitrary scalar keys.
 * 
 * @param String? $filler an optional filler that will be used if a cell has no value.
 * 
 * @param false|true|RegExp|List*(String!*)? $columnSpec 
 * If false there will be no header (no special first row) but all columns are included.
 * If true the first row is a header, and all columns are included.
 * If a string is provided then it is assumbed to be a regular expression. Only matching
 * column names will be added to the table. 
 * If $displayFilter is a list, this list will constitute the list of columns headers. 
 * Default is true. 
 * 
 * @param Boolean? $rowSpec 
 * If true then the first column will contains the key of rows.
 * Default to true. 

 * @return List*(List*(Any!)) the resulting table.
 */
function mapOfMapToTable($mapOfMap,$filler='',$columnSpec=true,$rowSpec=true) {
  if (count($mapOfMap) == 0) {
    return array() ;
  } else {
    // fill the map if necessary
    $r = fillMapOfMap($mapOfMap,$filler) ;
    $allExistingHeaders = $r['columnKeys'] ;

    // compute the list of headers for which there will be a column.
    // This does not include the column for the keys
    if ($columnSpec===false) {
      // the headers will not be displayed, but the columns will still be there
      $header = $allExistingHeaders ;
    }
    if ($columnSpec===true) {
      $headers = $allExistingHeaders ;
    } elseif (is_array($columnSpec)) {
      $headers = $columnSpec ;
    } elseif (is_string($columnSpec)) {
      $headers=array() ;
      foreach($allExistingHeaders as $header) {
        if (preg_match($columnSpec,$header)) {
          $headers[]=$header ;
        }
      }
    } else {
      die('wrong argument for homoMapOfMapToHTMLTable: displayFilter='.$displayfilter) ;
    }

    $table=array() ;

    // add an headerRow if required
    if ($columnSpec!==false) {
      if ($rowSpec===true) {
        $headerRow=array('') ;
      } else {
        $headerRow=array() ;
      }
      array_append($headerRow,$headers) ;
      $table[]=$headerRow ;
    }
    
    // add the table "body"
    foreach ($mapOfMap as $keyRow=>$row) {
      $tableRow=array() ;
      if ($rowSpec===true) {
        $tableRow[]=$keyRow;
      }
      foreach ($headers as $keyColumn) {
        if (isset($mapOfMap[$keyRow][$keyColumn])) {
          $tableRow[]=$mapOfMap[$keyRow][$keyColumn] ;
        }
      }
      $table[]=$tableRow ;
    }
    return $table ;
  }
}
















/**
 * 
 * @param List*(Map(String!,Any!)!)! $arrayMap
 * @param String! $key
 * @param Boolean! $distinct
 */
function columnValuesFromArrayMap($arrayMap,$key,$distinct=false) {
  $result = array() ;
  foreach($arrayMap as $map) {
    $result[] = $map[$key] ;
  }
  return ($distinct ? array_unique($result) : $result) ;
}







/*----------------------------------------------------------------------------------
 *     Json processing
 *----------------------------------------------------------------------------------
 */


/**
 * Return the last error message produced by json_encode and json_decode.
 * @return String! Error message.
 */
function jsonLastErrorMessage() {
  $JSON_ERRORS = array(
      JSON_ERROR_NONE => 'No errors|',
      JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
      JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
      JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
      JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
      JSON_ERROR_UTF8 =>'Malformed UTF-8 characters, possibly incorrectly encoded'
  );
  return $JSON_ERRORS[json_last_error()];
}



/**
 * Decode a json string and die if the result is not a map
 * @param JSON! $json
 * @param $die 
 * @return Map$(Scalar!,Any!)! the map
 * @die if the results is not an map
 */
function jsonDecodeAsMap($json,$dieIfInvalidJson=true) {
  $result = json_decode($json,true) ;
  if ($dieIfInvalidJson && !is_array($result)) {
    die('jsonDecodeAsMap: cannot be decoded as a map : '.$json) ;
  }
  return $result ;
}


/**
 * Load a json file and decoded it as a map. Die in case of error.
 * @param Filename! $jsonFilename
 * @return Map$(Scalar!,Any!)! the map
 * @die if the file doesn't exist or is not a valid json, or is not an map
 */
function jsonLoadFileAsMap($jsonFilename,$dieIfInvalidJson=true) {
  $json = loadFile($jsonFilename,$results) ;
  return jsonDecodeAsMap($json,$dieIfInvalidJson) ;
}

/**
 * Indents a flat JSON string to make it more human-readable.
 * @param string $json The original JSON string to process.
 * @return string Indented version of the original JSON string.
 */
function jsonBeautifier($json) {

  $result      = '';
  $pos         = 0;
  $strLen      = strlen($json);
  $indentStr   = '  ';
  $newLine     = "\n";
  $prevChar    = '';
  $outOfQuotes = true;

  for ($i=0; $i<=$strLen; $i++) {

    // Grab the next character in the string.
    $char = substr($json, $i, 1);

    // Are we inside a quoted string?
    if ($char == '"' && $prevChar != '\\') {
      $outOfQuotes = !$outOfQuotes;

      // If this character is the end of an element,
      // output a new line and indent the next line.
    } else if(($char == '}' || $char == ']') && $outOfQuotes) {
      $result .= $newLine;
      $pos --;
      for ($j=0; $j<$pos; $j++) {
        $result .= $indentStr;
      }
    }

    // Add the character to the result string.
    $result .= $char;

    // If the last character was the beginning of an element,
    // output a new line and indent the next line.
    if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
      $result .= $newLine;
      if ($char == '{' || $char == '[') {
        $pos ++;
      }

      for ($j = 0; $j < $pos; $j++) {
        $result .= $indentStr;
      }
    }

    $prevChar = $char;
  }
  return $result;
}

/**
 * Encode a value in json and beautify it if requested
 * @param Any $value
 * @param Boolean? $beautify whether the json results should be indented or not.
 * Default to false.
 * @return JSON! The json string.  
 * @Die in case of error.
 */
function jsonEncode($value, $beautify=false) {
  $json = json_encode($value) ;
  if ($json===null) {
    $msg = jsonLastErrorMessage() ;
    die('jsonEncode: '.msg) ;
  }
  if ($beautify) {
    $json=jsonBeautifier($json) ;
  }
  return $json ;
}

/**
 * Save a value (typically a map) as a json file.
 * @param Filename! $filename The name of the file to save. 
 * Directory will be created recursively if necessary. 
 * @param Any! $value the value to save. Typically a map.
 * @param inout>Map(Filename,Integer|String) $results an array in which
 * results are accumulated. That is if the filename is save then
 * its name will be added in the map with the number of byte saved
 * otherwise an error message will be returned. Use is_string to
 * check if an error occured.
 * @param Boolean? $beautify whether the json results should be indented or not.
 * Default to false.
 * @return Boolean! true if the file as been saved successfully,
 * false otherwise. It is not necessary to test this value after
 * each file save as the result is keep anyway in $results.
 */
function saveAsJsonFile($filename,$value,&$results=array(),$beautify=false) {
  $json = jsonEncode($value,$beautify) ;
  return saveFile($filename,$json,$results) ;
}


/**
 * Save a map to as as json file or merge this map to the existing map if the file already
 * exist. By contrast to saveAsJsonFile that override an potential existing file, here the
 * previous and current value are merged.
 * @param Filename! $filename name of the file to save or to merge
 * @param Map*(Scalar!,Any!)! $map map to save or to merge
 * @param Function? $merger the function to merge the two arrays. Default to "array_merge_recursive"
 * but could be set also to "array_merge" or any other functions taking two maps and returning a map. 
 * @param Boolean? $beautify whether the json results should be indented or not.
 * Default to false.
 * @return Boolean! true in case of successof the file writing, false otherwise. It is not
 * necessary to test the result directly as it is recorded in $results anyway.
 * @die if the file exist and is not a valid json map.
 */
function saveOrMergeJsonFile($filename,$map,$merger='array_merge_recursive',&$results=array(),$beautify=false) {
  if (file_exists($filename)) {
    // the file exist, so load the existing structure
    $existingMap = jsonLoadFileAsMap($filename) ;
    $newMap = $merger($existingMap,$map) ;
    $result = saveAsJsonFile($filename,$newMap,$results,$beautify) ;
  } else {
    $result = saveAsJsonFile($filename,$map,$results,$beautify) ;
  }
  return $result ;
}


/**
 * 
 * @param unknown_type $root
 * @param unknown_type $nameRegExpr
 * @param unknown_type $keyTemplate
 * @param unknown_type $ignoreDotFiles
 * @param unknown_type $followLinks
 * @param unknown_type $ignoreDotDirectories
 * @die if the directory is not readable
 * @die if one of the files found is not a json map
 */
function mapFromJsonDirectory(
    $root,
    $recursive=true,
    $namePattern='/\.json$/',
    $keyTemplate='${0}',
    $ignoreDotFiles=false,
    $followLinks=false,
    $ignoreDotDirectories=true) {
  // get the list of all filenames with the parameters above
  // except that we defintively need the full file name 
  
  // TODO this should be changed in listAllFileNames
  if ($recursive) {
    $jsonFullFilenames = listAllFileNames($root,'file',$namePattern,$ignoreDotFiles,true,$followLinks,$ignoreDotDirectories) ;
  } else {
    $jsonFullFilenames = listFileNames($root,'file',$namePattern,$ignoreDotFiles,true,$ignoreDotDirectories) ;
  }
  
  if ($jsonFullFilenames === null) {
    die('jsonFromJsonDirectory: directory '.$root.' cannot be read') ;
  }
  $results = array () ;
  foreach ($jsonFullFilenames as $jsonFullFilename) {
    $map = jsonLoadFileAsMap($jsonFullFilename,false) ;
    
    if ($map===null) {
      echo "<li>File $jsonFullFilename contains is not a valid json</li>" ;
      $map = array("ERROR") ;
    } else 
    $key = matchToTemplate($namePattern,$jsonFullFilename,$keyTemplate) ;
    $results[$key] = $map ;
  }
  return $results ;
}

/*----------------------------------------------------------------------------------
 *     Summary
*----------------------------------------------------------------------------------
*/

/**
 * Create a summary of a map of map. That is create a structure
 * with cardinalities, domains, ranges, etc.
 * type MapOfMapSummary == Map{
 *   'kind' => 'mapOfMap',
 *   'domain1Card' => Integer,
 *   'domain1'     => Set*(Scalar) ?, // only if $returnSets
 *   'domain2'     => Set*(Scalar) ?, // only if $returnSets
 *   'domain2Card' => Map{
 *     'min' => Integer,
 *     'max' => Integer,
 *     'sum' => Integer,
 *     'unique' => Integer,
 *     'map' => Map(Scalar => Integer) ?  // only if $returnMaps
 *   }
 *   'range'       => Set*(Scalar) ?, // defined if $returnSets
 *   'rangeCard'   => Integer
 * }
 * 
 * @param Map(Scalar,Map(Scalar,Value)) $mapmap A map of map
 * 
 * @param Any? $valueIfEmpty If specified this value returned if the map
 * is empty. Default to null, so if nothing is provided, the summary
 * will be performed as usual but cardinalities will be 0, sets will be
 * empty, etc. 

 * @param Boolean! $returnKind indicated if the kind attribute should be
 * returned.
 *
 * @param Boolean! $returnSets indicates if domains and range should be
 * returned. These may contains many values. Default is false.
 * 
 * @param Boolean! $returnMaps indicates if the domain2Card map is returned.
 *  
 * @return MapOfMapSummary|$valueIfEmpty
 */
function mapOfMapSummary($mapmap,$valueIfEmpty=null,$returnKind=false,$returnSets=false,$returnMaps=false) {
  if (count($mapmap)===0 && isset($valueIfEmpty)) {
    return $valueIfEmpty ;
  } else {
    $r = array() ;
    if ($returnKind) {
      $r['kind']='mapOfMap' ;
    }
    $r['domain1card']=count($mapmap) ;
    $r['domain1']=array_keys($mapmap) ;
    $r['domain2']=array() ;
    $r['range']=array() ;
    $r['domain2Card']=array() ;
    $r['domain2Card']['sum']=0 ;
    foreach($mapmap as $key1 => $map2) {
      $n = count($map2) ;
      if ($returnMaps) {
        $r['domain2Card']['map'][$key1] = count($map2) ;
      }
      if (!isset($r['domain2Card']['min']) || ($n < $r['domain2Card']['min'])) {
        $r['domain2Card']['min'] = $n ;
      }
      if (!isset($r['domain2Card']['max']) || ($n > $r['domain2Card']['max'])) {
        $r['domain2Card']['max'] = $n ;
      }
      $r['domain2Card']['sum'] += $n ;
      $r['domain2']=union($r['domain2'],array_keys($map2)) ;
      $r['range']=union($r['range'],array_values($map2)) ;
    }
    $r['domain2Card']['unique'] = count($r['domain2']) ;
    $r['rangeCard']=count($r['range']) ;
    if (!$returnSets) {
      unset($r['domain1']) ;
      unset($r['domain2']) ;
      unset($r['range']) ;
    }
    return $r ;
  }
}



/**
 * Create a summary of a map. For map of map it may be better to use
 * mapOfMapSummary as it provides more information. 
 *
 * type MapSummary == Map{
 *   'kind' => 'map' ?,              // only if $returnKind
 *   'domain'     => Set*(Scalar) ?, // defined if $returnSets
 *   'domainCard' => Integer,
 *   'range'       => Set*(Scalar) ?, // defined if $returnSets
 *   'rangeCard'   => Integer
 * }
 * 
 * @param Map*(Scalar,Any!) $map a map
 * 
 * @param Any? $valueIfEmpty If specified this value returned if the map
 * is empty. Default to null, so if nothing is provided, the summary
 * will be performed as usual but cardinalities will be 0, sets will be
 * empty, etc. 
 * 
 * @param Boolean! $returnKind indicated if the kind attribute should be
 * returned.
 *
 * @param Boolean! $returnSets indicates if domain and range should be
 * returned. These may contains many values.
 * 
 * @return MapSummary|$valueIfEmpty
 * 
 */
function mapSummary($map,$valueIfEmpty=null,$returnKind=false,$returnSets=false) {
  if (count($map)===0 && isset($valueIfEmpty)) {
    return $valueIfEmpty ;
  } else {
    $r = array() ;
    if($returnKind) {
      $r['kind']='map' ;
    }
    $range=array_unique(array_values($map)) ;
    if ($returnSets) {
      $r['domain'] = array_keys($map);
      $r['range']=$range ;
    }
    $r['domainCard']=count($map) ;
    $r['rangeCard']=count($range) ;
    return $r ;
  }
}





/**
 * @param Any? $value a value or null
 */
function mixedValueSummary($value,$valueIfEmpty=null,$returnKind=false) {
  $valueToReturn = $value 
                      ? $value 
                      : (isset($valueIfEmpty)?$valueIfEmpty : $value) ;
  if (!$returnKind) {
    return $valueToReturn ;
  } else {
    $r = array() ;
    $r['kind']=typeOf($valueToReturn) ;
    $r['value']=$valueToReturn ;
    return $r ;
  }
}

/**
 * Return a summary for a given value according to its type.
 */
function valueSummary($value,$valueIfEmpty=null,$returnKind=false,$returnSets=false,$returnMaps=false) {
  if (is_map_of_map($value)) {
    return mapOfMapSummary($value,$valueIfEmpty,$returnKind,$returnSets,$returnMaps) ;
  } elseif (is_array($value)) {
    return mapSummary($value,$valueIfEmpty,$returnKind,$returnSets) ;
  } else {
    return mixedValueSummary($value,$valueIfEmpty,$returnKind) ;
  }
}




function array_change_keys($map,$prefix,$suffix="") {
  $result = array() ;
  foreach($map as $key=>$value) {
    $result[$prefix.$key.$suffix] = $value ;
  }
  return $result ;
}


/**
 * Concat an list of string with an optional separators,
 * begining string and trailing string.
 * @param List*(String) $list An array of strings
 * @param String? $separator default to ""
 * @param String? $begin defaut to ""
 * @param String? $end default to ""
 * @return String! the concatenation of the string 
 */
function array_concat($list,$separator='',$begin='',$end='') {
  return implode('',$list) ;
}

function array_avg($list) {
  $n = count($list) ;
  if ($n===0) {
    return null ;
  } else {
    return array_sum($list)/$n ;
  }
}

function array_fusion($map1,$map2,$recursive=true) {
  $result = $map1 ;
  foreach($map2 as $key=>$val2) {
    if (!isset($map1[$key])) {
      $result[$key] = $val2 ; 
    } else {
      if (is_integer($key)) {
        $result[] = $val2 ;
      } else {
        $val1 = $map1[$key] ;
        if (is_int_map($val1) && is_int_map($val2)) {
          $result[$key] = array_merge($val1,$val2) ;
        } elseif (is_string_map($val1) && is_string_map($val2)) {
          if ($recursive) {
            $result[$key] = array_fusion($val1,$val2,$recursive) ;
          } else {
            $result[$key] = $val2 ;
          }
        } else {
          $result[$key] = $val2 ;
        }
      }
    } 
  }
  return $result ;
}

function array_fold_list($list,$fun,$init) {
  $acc = $init ;
  foreach($list as $elem) {
    $acc = $fun($acc,$elem) ;
  }
  return $acc ;
}

function array_fusion_all($listOfMap) {
  return array_fold_list($listOfMap,"array_fusion",array()) ;
}

function array_merge_all($listOfMap) {
  return array_fold_list($listOfMap,"array_merge",array()) ;
}

function array_replace_all($listOfMap) {
  return array_fold_list($listOfMap,"array_replace",array()) ;
}

function array_count_all($listOfMap) {
  $acc = 0 ;
  foreach($listOfMap as $map) {
    $acc += count($map) ;
  }
  return $acc ;
}



/*----------------------------------------------------------------------------------
 *     Synthesis of trees of maps
 *----------------------------------------------------------------------------------
 */


class Synthesizer {

  /*----------------------------------------------------------------------------------
   *     Aggregating functions.
   *----------------------------------------------------------------------------------
   */
  
  
  /**
   * @param Fun:List*(Any1)->Any2 $aggregator
   * @param unknown_type $rootKey
   * @param unknown_type $value
   * @param unknown_type $childValues
   */
  public static function aggregate($aggregator,$rootKey,$value,$childValues) {
    $values = array($value) ;
    foreach ($childValues as $childId => $childValue) {
      $values[] = $childValue ;
    }
    return $aggregator($values) ;
  }
  
  public static function count($rootKey,$value,$childValues) {
    return self::aggregate('count',$rootKey,$value,$childValues);
  }
  
  public static function sum($rootKey,$value,$childValues) {
    return self::aggregate('array_sum',$rootKey,$value,$childValues) ;
  }
  
  public static function product($rootKey,$value,$childValues) {
    return self::aggregate('array_product',$rootKey,$value,$childValues) ;
  }
  
  public static function concat($rootKey,$value,$childValues) {
    return self::aggregate('array_concat',$rootKey,$value,$childValues) ;
  }
  
  public static function min($rootKey,$value,$childValues) {
    return self::aggregate('min',$rootKey,$value,$childValues) ;
  }
  
  public static function max($rootKey,$value,$childValues) {
    return self::aggregate('max',$rootKey,$value,$childValues) ;
  }
  
  public static function avg($rootKey,$value,$childValues) {
    return self::aggregate('array_avg',$rootKey,$value,$childValues) ;
  }
  
  public static function mergeAll($rootKey,$value,$childValues) {
    return self::aggregate('array_merge_all',$rootKey,$value,$childValues) ;
  }
  
  public static function replaceAll($rootKey,$value,$childValues) {
    return self::aggregate('array_replace_all',$rootKey,$value,$childValues) ;
  }
  
  public static function fusionAll($rootKey,$value,$childValues) {
    return self::aggregate('array_fusion_all',$rootKey,$value,$childValues) ;
  }
  
  public static function countAll($rootKey,$value,$childValues) {
    return self::aggregate('array_count_all',$rootKey,$value,$childValues);
  }  
  
  
  
  /*----------------------------------------------------------------------------------
   *     
   *----------------------------------------------------------------------------------
   */
  
  public static function prefixAll($rootKey,$map,$childMaps,$separator='/') {
    $results = $map ;
    foreach ($childMaps as $childId => $childMap) {
      foreach ($childMap as $key => $value) {
        $results[$childId.$separator.$key] = $value ;
      }
    }
    return $results ;
  }
  
}


function synthesizeMap($rootMap,$childMaps,$attibuteSynthesizer) {
  $result=array() ;
  foreach($rootMap as $rootKey => $rootValue) {
    $childValues = array() ;
    foreach($childMaps as $childId => $childMap) {
      $childValues[$childId]=$childMap[$rootKey] ;
    }
    $newmap = $attibuteSynthesizer($rootKey,$rootValue,$childValues) ;
    $result=array_fusion($result,$newmap) ;
  }
  return $result ;
}
