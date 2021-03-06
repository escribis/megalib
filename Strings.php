<?php defined('_MEGALIB') or die("No direct access") ;

require_once('Structures.php') ;

/**
 * Return a string representation of the value interpreted as a boolean and possibly null.
 * @param Any $value A boolean or null or in fact any value
 * @param Seq(String!,',') $strings a sequence of strings separated by , where the first one
 * is used for 'false', the second one for 'true' and the third one if exist for 'null'.
 * If the third one doesn't exist the 'false' value will be taken.
 * Default to 'false,true,null'.
 * @return String! One of the values indicated as parameter $strings
 */
function boolStr($value,$strings="false,true,null") {
  $strs=explode(",",$strings);
  return 
    $value===null 
      ? (isset($strs[2])?$strs[2]:$strs[0])
      : ($value ?$strs[1]:$strs[0]) ;
}


/**
 * Convert a range expresions of the form "10-12,4,28" to an list of integer.
 * For instance "1-2,5,6-7" returns array(1,2,5,6,7)
 * @param RangeString! $expression
 * @return List*(Integer)
 */
function rangesExpression($expression){
  $ranges=explode(',',$expression) ;
  $values = array() ;
  foreach($ranges as $range) {
    if ($range!='') {
      $bounds=explode('-',$range) ;
      switch (count($bounds)) {
        case 1:
          $values[] = intval($bounds[0]);
          break ;
        case 2:
          $values = union($values,range(intval($bounds[0]),intval($bounds[1]))) ;
          break ;
        default:
      }
    }
  }
  sort($values) ;
  return $values ;
}

function startsWith($haystack, $needleOrNeedles){
  if (is_string($needleOrNeedles)) {
    $needles = array($needleOrNeedles) ;
  } else {
    $needles = $needleOrNeedles ;
  }
  foreach ($needles as $needle) {
    if (substr($haystack, 0, strlen($needle)) === $needle) {
      return true ;
    }
  }
  return false ;
}

function endsWith($haystack, $needle)
{
  return (substr($haystack, - strlen($needle)) === $needle);
}

function scontains($haystack,$needle) {
  if (strpos($haystack,$needle)===false) {
    return false ;
  } else { 
    return true ;
  }
}

function prefixIfNeeded($str, $prefix) {
  return startsWith($str,$prefix) ? $str : $prefix . $str ;
}

function withoutOptionalPrefix($str, $prefix) {
  return startsWith($str,$prefix) ? substr($str,strlen($prefix)) : $str  ;
}

function withoutOptionalSuffix($str, $suffix) {
  return endsWith($str,$suffix) ? substr($str,0,strlen($str)-strlen($suffix)) : $str  ;
}

function ftrim($str) {
  return trim(preg_replace('/  /',' ',$str,-1)) ;
}

preg_match('/(?U:(.).*\1)/',"|dskjfh,sdf| fsqdfkj|" ,$matches) ;

function shiftWord(&$str,$delimiters='`"\'') {
  $str = ltrim($str) ;
  if ($str === "") {
    return null ;
  } elseif (preg_match('=^(['.$delimiters.'])(?:\\\\\1|.)*?\1=',$str,$matches)) {
    // this is the case of a words delimited by one of the delimiters.
    // inside they can be the delimiter with \
    $str = withoutOptionalPrefix($str,$matches[0]) ;
    $word = substr($matches[0],1,strlen($matches[0])-2) ;
    $word = str_replace('\\','',$word) ;
    return $word ;
  } elseif (preg_match('=^(?:\\\\ |[^ ])+=',$str,$matches)) {
    $str = withoutOptionalPrefix($str,$matches[0]) ;    
    $word = $matches[0] ;
    $word = str_replace('\\','',$word) ;
    return $word ;
  } else {
    die(__FUNCTION__.": unexpected case") ;
  }
}

function words($str,$delimiters='`"\'') {
  $words = array() ;
  $word=shiftWord($str,$delimiters) ;
  while ($word !== null) {
    $words[] = $word ;
    $word=shiftWord($str,$delimiters) ;
  }
  return $words ;  
}


/**
 * Compose two strings according by a given mode.
 * @param String! $s1
 * @param String! $s2
 * @param $mode
 * '1,2' means is $s1 and $s2 with the separator in between if necessaryc $s1 is not empty
 * '1>2' means $s1 if not empty otherwise $s2
 * '1 (2?)' means $s1 ($s2) if 2!=1, 1 if 2==1
 * othewise 1 is replaced by $s1 and 2 by $s2
 * @param String! $separator used as a separator in the mode '1,2'. Default to ' '.
 * @return String!
 */
function format12($s1,$s2,$mode="1,2",$separator=' ') {
  switch ($mode) {
    case '1':
      return $s1 ;
      break ;
    case '2' :
      return $s1 ;
      break ;
    case "1,2":
      return strlen($s1)===0 ? $s1.$separator.$s2 : $s2 ;
      break ;
    case "1>2":
      return strlen($s1)===0 ? $s1 : $s2 ;
      break ;
    case '1 (2?)':
      if ($s1===$s2) {
        return $s1 ;
      } else {
        return $s1.' ('.$s2.')' ;
      }
      break ;
    default:
      $r = str_replace('1',$s1,$mode) ;
    $r = str_replace('2',$s2,$mode) ;
    return $r ;
  }
}
/**
 * Remove comments and replace them by a given string.
 * @param String! $expr The string to clean.
 * @param RegExpr? $commentRegExpr the regular expression corresponding to a comment.
 * Default to C_LINE_COMMENT_REGEXPR for suppressing // comments.
 * @param String? $replacement Replacement string (default to a newline).
 */

define ('LINE_COMMENT_SUFFIX','[^\n]*\n') ;
define ('SHELL_LINE_COMMENT_REGEXPR','/#'.LINE_COMMENT_SUFFIX.'/') ;
define ('C_LINE_COMMENT_REGEXPR','/\/\/'.LINE_COMMENT_SUFFIX.'/') ;
define ('ADA_LINE_COMMENT_REGEXPR','/--'.LINE_COMMENT_SUFFIX.'/') ;

function removeComments($expr,$commentRegExpr=C_LINE_COMMENT_REGEXPR,$replacement="\n") {
  return preg_replace($commentRegExpr,$replacement,$expr) ;
}



// from http://php.net/manual/en/function.strtr.php
$_TRANSLATE['removeDiacritics'] = array(
    'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
    'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
    'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
    'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
    'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
    'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
    'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
);

// Added this to handle umlaut in german, but also some other stuff. The rules below are to be checked
// According to wikipedia the rules depend on the language and and not so precise, so...
// see http://en.wikipedia.org/wiki/Diaeresis_%28diacritic%29
// The table below might help in some approximate string matching giving more alternatives than the one in "removeDiacritics"
$_TRANSLATE['alternativeToDiacritics'] = array(
    'Ð'=>'D', 'Ä'=>'Ae','Æ'=>'Ae','Ï'=>'Ie', 'Ö'=>'Oe','Ø'=>'O', 'Ü'=>'Ue','ß'=>'Ss',
    'ä'=>'ae','æ'=>'ae','ë'=>'ee','ï'=>'i','ö'=>'oe','ÿ'=>'ye'
);

function removeDiacritics($text) {
  return strtr($text,$_TRANSLATE['removeDiacritics']) ;
}

function alternativeToDiacritics($textWithAccents) {
  return strtr($text,$_TRANSLATE['alternativeToDiacritics']) ;
}


