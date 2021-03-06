<?php defined('_MEGALIB') or die("No direct access") ;
require_once 'GraphML.php' ;


/**
 * Deals with the HTML file exported by the yEd editor.
 * 
 * type ImageArea == Map {
 *     "id"      => String!,    // redundant in an ImageAreaMap as this is the key
 *     "shape"   => 'rect'|'poly',
 *     "coords"  => String!,
 *     "url"     => URL!,
 *     "target"  => String?,
 *     "alt"     => String?,
 *     ...
 *   }  
 * type ImageAreaMap == Map( String!, ImageArea! ) !  
 * 
 */
class YEdHTML {
  const REGEXPR_AREA_LINE = '#<area shape="([a-z]*)" coords="([0-9,]*)" (href=".*" )?alt="".* onmouseover="showTooltip\(\'(.*)\'\)#' ;
  //  "$D/ -> ../repo
  //  target="_blank"  ->  target="detail"
  
  
  /**
   * 
   * @param HTML $html The html as generated by YEd export function with the format "HTML ImageMap"
   * @return ImageAreaMap!  
   */
  public static function getImageAreaMap($html) {
    $areamap = array() ;
    preg_match_all(self::REGEXPR_AREA_LINE,$html,$matches,PREG_SET_ORDER) ;
    foreach ($matches as $match) {
      if (preg_match('/href="(.*)"/',$match[3],$urlmatch)) {
        $url=$urlmatch[1] ;
      } else {
        $url="" ;
      }
      $area["shape"]  = $match[1] ;
      $area["coords"] = $match[2] ;
      $area["href"]   = $url ;
      $area["id"]     = $match[4];
      $areamap[$area["id"]]=$area ;
    }
    return $areamap ;
  }
  
  /**
   * Generate a HTML <map> with $mapid as id and with all area defined by the
   * attribute in $areaMap.
   * @param AreaMap! $areaMap
   * @return HTML
   */
  public static function imageAreaMapAsHTML($mapid,$areaMap) {
    $out='<map name="'.$mapid.'">'."\n" ;
    foreach($areaMap as $id => $area) {
      $out .= '  <area' ;
      foreach($area as $attname => $attvalue) {
        $out .= ' '.$attname.'="'.$attvalue.'"' ;
      }
      $out .= " />\n" ;
    }
    $out .= '</map>' ;
    return $out ;
  } 
  
  /**
   * @param unknown_type $mapid
   * @param unknown_type $areadMap
   * @return JSON
   */
  public static function imageAreaMapAsJson($mapid,$areaMap) {
    return json_encode($areaMap) ;
  }
}




/**
 * Describes a Yed palette.
 * The corresponding file is saved in the user application directory
 * (e.g. C:\Documents and Settings\<theUser>\Application Data\)
 * in the palette directory ((e.g. yWorks\yEd\palette) as a regular
 * graphml file.
 */
class YedPalette extends NAGraph {

}

/**
 * Describes a Yed palette set.
 * The corresponding file is saved in the user application directory
 * (e.g. C:\Documents and Settings\<theUser>\Application Data\)
 * in the palette directory as palette_info.xml
 */
class YedPaletteSet {


}

class YedPropertyMapper {

}