<?php defined('_MEGALIB') or die("No direct access") ;
/*
 * Basic function for FileSystem manipulations
 * 
 * TODO: change preg_match in find files for using PExpression
 * 
 * filebasenames
 *   occurrence basenames 
 *   find . -type f -exec basename '{}' ';' | sort | uniq -c | sort -r
 * 
 * 
 */

require_once 'Strings.php' ;
require_once 'Structures.php' ;
require_once 'Environment.php' ;
require_once 'HTML.php' ;
require_once 'PExpressionConcrete.php' ;     // TODO This should be removed. The dependency is because of usage of pattern in find


/**
 * Indicates if a path is absolute.
 * @param String! $url The path or url to test
 * @return Boolean true if this a absolute path, false otherwise.
 */
function isAbsolutePath($url) {
  return preg_match('%^(http://|file://|/|([a-zA-Z]:)?(\\\|/))%',$url)===1 ;
}

/**
 * Indicates if a path is relative.
 * @param String! $url The path or url to test
 * @return Boolean true if this a relative path, false otherwise.
 */
function isRelativePath($url) {
  return !isAbsolutePath($url) ;
}

/**
 * If the path is absolute, let's it untouched. Otherwise concatenate the base path
 * (or current directory if not specified) and the path. If the option makeItReal is 
 * selected then the path is replaced by the real path with the php function realpath.
 * Return null in case of error.
 * @param String! $path a path either relative or absolute
 * @param String? $path base path to add to a relative path. If not specified the
 * base is the current ditectory.
 * @return String? An absolute path corresponding to the path or null in case of error.
 */
function makePathAbsolute($path,$base=null,$makeItReal=false) {
  if (isRelativePath($path)) {
    // this is a relative path. Adds the base to it.
    if (! isset($base)) {
      $base = getcwd() ;
      if ($base === false) {
        return null ;
      }
    }
    $path = addToPath($base,$path) ;
  }
  if ($makeItReal===true) {
    $path = realpath($path) ;
    if ($path === false) {
      return null ;
    }
  }
  return $path ;
}

/**
 * Return the extension of an URL (the last part after .)
 * TODO testing
 * @param String! $url
 * @return string the extension without dot?
 */
function fileExtension($url) {
  $name=basename($url) ;
  $dotpos = strrpos($name,'.') ;
  if ($dotpos===false) {
    $extension = "" ;
  } else {
    $extension = substr($name,$dotpos+1) ;
  }
  return $extension ;
}


/**
 * The core of a file name
 * @param String! $url A filename with potentially its extension and a path
 * @return String! the filename without its extension and path
 */
function fileCoreName($url) {
  $name = basename($url) ;
  $dotpos = strrpos($name,'.') ;
  if ($dotpos===false) {
    $corename = $name ;
  } else {
    $corename = substr($name,0,$dotpos) ;
  }
  return $corename ;
}


/**
 * Test if a plain file exists.
 * @param String! $url A filename
 * @return Boolean! true if the parameter is an existing file
 */
function isFile($url) {
  if (startsWith($url,"http:") || startsWith($url,"file:")) {
    assert("false") ; // not implemented.
  } else {
    @ $type = filetype($url) ;
    return $type==="file" ;
  }
}


/**
 * Test if a plain file exists and is readable.
 * @param String! $url A filename
 * @return Boolean! true if the parameter is a file that can be read
 */
function isReadableFile($url) {
  @ $handle = fopen($url,"r") ;
  if ($handle === false) {
    return false ;
  } else {
    fclose($handle);
    return true ;
  }
}

/**
 * Test if  this is a readable directory.
 * @param String! $url A pathname
 * @return Boolean! true this is a readable directory
 */

function isReadableDirectory($url) {
  if (startsWith($url,"http:") || startsWith($url,"file:")) {
    assert("false") ; // not implemented. We should read the content of the directory
  } else {
    @ $type = filetype($url) ;
    return $type==="dir" ;
  }
}

// return NULL for the / directory
/**
 * Return a path corresponding to the parent directory
 * @param String! $url
 * @return String|NULL A path to the parent directory or NULL for /
 */
function /*String?*/ parentDirectory(/*Path!*/ $url) {
  if (startsWith($url,"http:") || startsWith($url,"file:")) {
    assert("false") ; // not implemented. We should read the content of the directory
  } else {
    if ($url==='.') {
      return ".." ;
    } else if ($url==="/") {
      return NULL ;
    } else {
      // if the path ends with a / remove it first
      if (preg_match('/\/$/',$url)) {
        $path = substr($url,0,-1) ;
      } else {
        $path = $url ;
      }
      // get the position of the the last /
      $pos = strrpos($path,'/') ;
      if ($pos === FALSE) {
        // this is a relative path
        return "." ;
      } else {
        return addToPath("",substr($path,0,$pos)) ;
      }      
    }
  } 
}


/**
 * Concat two path (e.g. a directory / a file, by adding / only if needed)
 * @param String! $path
 * @param String! $path2
 * @return String! the concatenated path with only a directory separator
 */
function addToPath($path,$path2) {
  return 
    $path 
    . (endsWith($path,'/') ? "" : "/")
    . (startsWith($path2,'/') ? substr($path2,1) : $path2) ;
}


/**
 * @deprecated use findFiles instead.
 * 
 * List all filenames directly under a given directory or return null if the parameter
 * is not a readable directory. Never return '.' or '..'.
 * 
 * @param String! $directory the directory containing the item to list
 * 
 * @param Seq('dir','file','link','|')? $typeFilter the type of items to select separated by |
 * if various types are accepted. By default all types are accepted.
 * 
 * @param Pattern? $namePattern if not null a pattern (see TExpression.php) that will be used as
 * a filter on the item name. If not specified otherwise, the pattern applied to basename. 
 * $nameRegExpr should be a string of the form '/.../' or 'suffix:.c', 'etc.'. Default to null.
 * 
 * @param Boolean? $ignoreDotFiles indicates if hidden items (.xxx) should be ignored. 
 * Default to true, so hidden items are ignored by default. 
 *
 * @param Boolean? $prefixWithDirectory indicates if the resulting item names should be
 * prefixed with the directory name. Default to true.
 * 
 * @param Boolean? $ignoreDotDirectories indicates if dot directories should be totally
 * ignored.

 *
 * @return List*(String!)? The list of selected items or NULL if $directory cannot be opened
 */
function /*Set*<String!>?*/ listFileNames(
    $directory, 
    $types="dir|file|link|error",
    $pattern=NULL,
    $excludeDotFiles=TRUE,
    $prefixWithDirectory=TRUE,
    $excludeDotDirectories=TRUE) {
      return 
        findDirectFiles($directory,array(
            'types'                 => $types,
            'pattern'               => isset($pattern) ? 'basename | matches '.$pattern : null,
            'excludeDotFiles'       => $excludeDotFiles,
            'excludeDotDirectories' => $excludeDotDirectories,
            'apply'                 => ($prefixWithDirectory ? 'nop' : 'basename')
          )) ;
}
      
      
// null if error      
function findDirectFiles($directory,$params) {
  $excludeDotFiles = isset($params['excludeDotFiles']) ? $params['excludeDotFiles'] : true;
  $excludeDotDirectories = isset($params['excludeDotDirectories']) ? $params['excludeDotDirectories'] : true;
  $types = isset($params['types']) ? $params['types'] : "dir|file|link|error" ;
  $pattern = isset($params['pattern']) ? $params['pattern'] : null ;
  $exclude = isset($params['$exclude']) ? $params['$exclude'] : null ;
  $predicate = isset($params['predicate']) ? $params['predicate'] : null ;
  $init = isset($params['init']) ? isset($params['init']) : array() ;
  $apply = isset($params['apply']) ? $params['apply'] : "path" ;
  $action = isset($params['action']) ? $params['action'] : "collect" ;
  
  if (!in_array  ($apply,array('basename','path','dirname'))) {
    $applyPExpression = new ConcretePExpression($apply) ; 
  }
  
  $allowedTypes=explode('|',$types) ;
  
  $accumulator = $init ;
  if (isReadableDirectory($directory) && $dh = opendir($directory)) {
    // this is a readable directory:
    // process each directory item
    while (($file = readdir($dh)) !== false) {
      $path = addToPath($directory,$file) ;
      $matches = array() ;
      // the filetype generates a warning for a broken link so use @
      @ $type = filetype($path) ;
      // a broken link returns false. Not obvious to know which other cases
      // false is returned. But we therefore use the type "unknown".
      if ($type===false) {
        $type="error" ;
      }
      
      $selected =    $file!=='.' 
                  && $file!=='..'
                  && in_array($type,$allowedTypes) 
                  && ($type!=='file' || $excludeDotFiles!==TRUE || substr($file,0,1)!='.')
                  && ($type!=='dir' || $excludeDotDirectories!==TRUE || substr($file,0,1)!='.')
                  && (!isset($predicate) || $predicate($file))                   
                  
      // TODO use AbstractPExpression instead as the analysis of the expression will be done only once!            
                  && (!isset($exclude) || !matchPattern($exclude,$path))                 
                  && (!isset($pattern) || matchPattern($pattern,$path,$matches)) ;
                  
      if ($selected) {
        
        switch ($apply) {
          // some fast tracks
          case "basename":
            $value = $file ;
            break ;
          case "path":
            $value = $path ;
            break ;
          case "dirname";
            $value = dirname($value) ;
            break ;
          // general case, this is a pexpression
          default:
            // TODO use AbstractPExpression instead as the analysis of the expression will be done only once!
            $value = $applyPExpression->doEval($path,$matches) ;
        }
        
        switch ($action) {
          case 'collect':
          default:
            $accumulator[] = $value ;
        }
      }
    }
    return $accumulator ;
  } else {
    return null ;
  }
}




// null if error at the first level. Errors at lower levels are ignored.
function findFiles($root,$param,$currenLevel=1) {
  $exploreDotDirectories = isset($param['exploreDotDirectories']) ? $param['exploreDotDirectories'] : false ;
  $levels = isset($param['levels']) ? $param['levels'] : 1000 ;
           
  // Start with adding the direct files.
  $accumulator = findDirectFiles($root,$param) ; 
  if ($accumulator===null) {
    return null ;  // there was an error
  }
  
  if ($currenLevel < $levels) {
    // Get the subdirectories that should be explored.
    // Do not use parameters related to file filtering
    // We should also collect paths
    $subdirectories =
      findDirectFiles($root, array(
            'types'=>'dir',
            'exploreDotDirectories'=>$exploreDotDirectories,
            'apply'=>'path')) ;
    if ($subdirectories===null) {
      return null ;  // there was an error
    }      
    // now process the subdirectories to explore
    foreach ($subdirectories as $subdirectory) {
      $subdirectorySelectedChildren = findFiles($subdirectory,$param,$currenLevel+1)  ;
      if ($subdirectorySelectedChildren !== null) {
        // push the result at the end. They can be duplicate if $prefixWithDirectory is false
        // because the same name could be found in different directory
        array_append($accumulator,$subdirectorySelectedChildren) ;
      }
    }
  }
  return $accumulator ;
}

/**
 * List all filenames recursively in a given directory. Does not return the root directory itself.
 * If this not a readable directory, it returns null. The non readable sub directories are not
 * explored, but otherwise the whole subtree is explored indepedently from filters.
 * 
 * @param String! $directory the root directory where to start
 * 
 * @param Seq('dir','file','link','error','|')? $typeFilter the type of items to select separated by |
 * if various types are accepted. By default all types are accepted.
 * 
 * @param Pattern? $namePattern if not null a pattern (see TExpression.php) that will be used as
 * a filter on the item name. The matching is done only on the file name, without the path.
 * $nameRegExpr should be a string of the form '/.../' or 'suffix:.c', 'etc.'. Default to null.
 * 
 * @param Boolean? $ignoreDotFiles indicates if hidden items (.xxx) should be ignored. 
 * Default to true, so hidden items are ignored by default. 
 * 
 * @param Boolean? $prefixWithDirectory indicates if the resulting item names should be
 * prefixed with the directory name. Default to true.
 * 
 * @param Boolean? $ignoreDotDirectories indicates if dot directories should be totally
 * ignored. If so, they will not be explored. Default to true.
 * 
 * @return List*(String!)? The list of selected items or NULL if $url cannot be opened
 */

function listAllFileNames(
    $root,
    $types="dir|file|link|error",
    $pattern=NULL,
    $excludeDotFiles=TRUE,
    $prefixWithDirectory=TRUE,
    $followLinks=false,
    $excludeDotDirectories=true) {
  return
  findFiles($root,array(
      'types'                 => $types,
      'pattern'               => isset($pattern) ? 'basename | matches '.$pattern : null,
      'excludeDotFiles'       => $excludeDotFiles,
      'excludeDotDirectories' => $excludeDotDirectories,
      'apply'                 => ($prefixWithDirectory ? 'nop' : 'basename')
  )) ;
}









/**
 * @param unknown_type $filenames
 */
function extensionFrequencies($filenames){
  $distrib = array() ;
  foreach ($filenames as $filename) {
    $extension=fileExtension($filename) ;
    if (isset($distrib[$extension]) ){
      $distrib[$extension]++ ; 
    } else {
      //echo $extension." " ;
      $distrib[$extension] = 1 ;
    }
      
  } 
  return $distrib ;
}

/** 
 * 
 * file 
 *   'name' => String!,
 *   'path' => String!,
 *   'extension' => String!,
 *   'isHidden' => Boolean!,
 *   'size' => Integer>=0!,
 *   'lineNb' => Integer>=0?
 *    
 * directory = Map (
 *   'name' => String!,
 *   'path' => String!,
 *   'extension' => String!,
 *   'isHidden' => String!,
 *   'depth'=>Integer>=0!,
 *   'cumulatedSize' => Integer>=0!,
 *   'cumulatedLineNb' => I 
 */



/**
 * Save a file in a given place and create directories if necessary.
 * @param Filename $out the file in which to save
 * @param String! $content the content to save in the file
 * @param inout>Map(Filename,Integer|String) $results an array in which
 * results are accumulated. That is if the filename is save then
 * its name will be added in the map with the number of byte saved
 * otherwise an error message will be returned. Use is_string to
 * check if an error occured.
 * @return Boolean! true if the file as been saved successfully,
 * false otherwise. It is not necessary to test this value after
 * each file save as the result is keep anyway in $results.
 */
function saveFile($filename,$content,&$results=array()) {
  $dir = dirname($filename) ;
  if (!is_dir($dir)) {
    if (! mkdir($dir,0777,true)) {
      $results[$filename]="error: can't create directory $dir" ;
    }
  }
  $n = file_put_contents($filename,$content) ;
  $results[$filename]=($n?$n:"error: cannot create file") ;
  return $n!==false ;
}

/**
 * Load the content of a file or die if the file does not exist.
 * @param Filename $filename
 * @return String! the content of the file
 */
function loadFile($filename,&$results=array()) {
  $content = file_get_contents($filename) ;
  if ($content === false) {
    die('loadFile: cannot read file '.$filename) ;
  }
  return $content ;
}


/**
 * Compute a new path for a filename according to a base and
 * a new base.
 * 
 * @param Pathname $srcpath either a filename or directoryname
 * 
 * @param Pathname $newbase a directory that will prefix the
 * results in all cases.
 * 
 * @param Boolean|String! $base 
 * if true the basename of the file will be added to $newbase
 * if false the filename will be added directly to $newbase
 * if $base is a string then it will be removed if possible
 * from the filename and the result will be added to $newbase.
 * Default to true.  
 * 
 * @return String! $newbase plus some fragment of $filename
 * according to the rule specifed with $base
 */
function rebasePath($filename,$newbase,$base=true) {
  if ($base===true) {
    $filepart = basename($filename) ;
  } elseif ($base===false) {
    $filepart = $filename ;
  } elseif (is_string($base)) {
    if (startsWith($filename,$base)) {
      $filepart = substr($filename,strlen($base)) ;
    } else {
      $filepart = $filename ;
    }
  }
  return addToPath($newbase,$filepart) ;
  
}

/**
 * Compute the information about a link. Note that if the link is broken or
 * in case of another error, then only the 'link' field will be returned. 
 * In fact it seems that there is no way to make the difference between 
 * a broken link and another error. Moreover broken link cannot be removed.
 * 
 * type LinkInfo! == Map(
 *   'link' => String!,
 *   'linkParent' => String?, 
 *   'realLinkParent' => String?,
 *   'isRealLink' => Boolean?,
 *   'realLinkPath' => String?,
 *   'targetValue' => String?,
 *   'isRelativeTarget' => Boolean?,
 *   'isRealTarget' => Boolean?,
 *   'targetPath' => String?,
 *   'isBroken' => Boolean?,
 *   'realTargetPath' => String?
 * )
 * 'link' is the value being analyzed. If this is not a link or the link is broken
 * then all other value are unset. So the way to test this is to check if count == 1
 * 'linkParent' the directory containing the link
 * 'realLinkParent' the real path of the parent directory
 * 'isRealLink' indicates if the link (not its value!) given corresponds to its real path.
 * 'realLinkPath' is the real and absolute location of the link after potential link resolution.
 * 'targetValue' is the direct value of the link without any computation.
 * 'isRelativeTarget' indicates if the target value is a relative path
 * 'isRealTarget' indicates if the target value is equal to the real target (see below).
 * 'targetPath' is the value of the link computed according to the position of the link.
 * Same value as 'targetValue' for absolute link, but for relative link it returns the
 * path of the link computed in the context of the original link. For instance a link
 * 'realTargetPath' the absolute path to the real target. 
 * ../link -> value  corresponds to ../value target path.
 * 'isBroken' indicates if the link is broken. In fact this value may not be computed
 * in case of broken link because some error poping out before.
 * 
 * @param String! $link the path to inspect
 * @return LinkInfo! A map with the properties defined above. If only one property is
 * defined (this will be 'link') then the parameter is not a link or is broken.
 */
function linkInformation($link) {
  $r['link']=$link ;
  if (@ !is_link($link)) {
    $r['isBroken'] = true ;
    return $r ;
  } else {
    $r['linkParent'] = dirname($link) ;
    $r['realLinkParent'] = realpath($r['linkParent']);
    $r['realLinkPath'] = addToPath($r['realLinkParent'],basename($link)) ;
    $r['isRealLink'] = ($link === $r['realLinkPath']) ;
    
    @ $target=readlink($link) ;
    if($target===false) {
      $r['isBroken'] = true ;
    } else {
       $r['targetValue'] = $target ;
       $r['isRelativeTarget'] = isRelativePath($target) ;
       $r['targetPath'] = makePathAbsolute($target,$r['linkParent']) ;
       $realpath = realpath($r['targetPath']) ;
       if (isset($r['targetPath']) && $realpath!==false) {
         $r['realTargetPath'] = $realpath ;
         $r['isBroken'] = false ;
         $r['isRealTarget'] = ($r['targetValue'] === $r['realTargetPath']) ;
       } else {
         $r['isBroken'] = true ;
       }
    }
    // var_dump($r) ;
    return $r ;
  }    
}




/**
 * Return a formatted path information
 * @param 'simple'|'real'|'detail' $realPathMode
 * In 'simple' mode the path is displayed as is
 * In 'real' mode the real path is displayed. 
 * In 'detail' mode the path is display along with the real path
 * in parenthesis if this one is different.
 */
function formatPath($path,$realPathMode="detail") {
  if ($realPathMode==='simple') {
    return $path ;
  } else {
    @ $real = realpath($path) ;
    if ($realPathMode==='real') {
      $r = $real ;
    } else {
      if ($real===$path || !$real) {
        $r = $path ;
      } else {
        $r = $path .' ('.$real.')' ;
      }
    }
    return $r ;
  }
}

/**
 * Return a formatted string representation of a link information
 * @see the linkInformation function
 * @param LinkInfo! $linkInfo
 * @param 'simple'|'real'|'detail' $realPathMode 
 * In mode 'real' the real paths are displayed both for the link and the target instead
 * of their values. In mode 'detail' the real paths are displayed only if there are different
 * from the values 
 * @return String! the formatted representation
 */
function formatLinkInformation($linkInfo,$realPathMode="detail") {
  $formats=array('simple'=>'1','real'=>'2','detail'=>'1 (2?)') ;
  $format=$formats[$realPathMode] ;
  $real = isset($linkInfo['realLinkPath']) ? $linkInfo['realLinkPath'] : "" ;
  $r = format12($linkInfo['link'],$real,$format) ;
  if (count($linkInfo)<2) {
    return $r . " is not a valid link" ;
  } else {
    $r .= ' -> '. ($linkInfo['isBroken']?'BROKEN ':'') ;
    $real = isset($linkInfo['realTargetPath']) ? $linkInfo['realTargetPath'] : "" ;
    $r .= format12($linkInfo['targetValue'],$real,$format) ;
    return $r ;
  }
}

/**
 * @param unknown_type $link
 * @param unknown_type $pattern
 * @param unknown_type $replacement
 * @return multitype:string |multitype:|multitype:mixed unknown 
 */
function relink($link,$pattern,$replacement) {
  $info=linkInformation($link) ;
  if (count($info)===0 || !isset($info['targetValue']) ) {
    return array('error'=>"$link is not a link") ;
  } else {
    $target=$info['targetValue'] ;
    $newtarget = preg_replace($pattern,$replacement,$target) ;
    if ($newtarget===null) {
      // an error during replacement
      return array('error'=>"preg_replace returned null") ;
    } elseif ($newtarget===$target) {
      // the pattern does not match anything. Do nothing
      return array() ;
    } else {
      // delete the current link and create replace it by the new link
      $r = unlink($link) ;
      if ($r===false) {
        if (is_link($link)) {
          return array('error'=>"unable to remove $link. This link still exist.") ;
        } else {
          return array('error'=>"unable to remove $link. This is not a link?") ; 
        }
      } else {
        if (symlink($newtarget,$link)) {
          return array('old'=>$target,'new'=>$newtarget) ;
        } else {
          // the new link haven't been created. Try to restore the old one.
          if (symlink($target,$link)) {
            return array('error'=>"link $newtarget cannot be created,"
                         . " but $link -> $target has been restored") ;
          }
            return array('error'=>"link $newtarget cannot be created,"
                         . " but $link -> $target has been deleted and cannot be restored") ;
        }
      
      }
    }
  }
}

/**
 * Return the list of links in a given directory along with their informations.
 * @param String! $root The root directory in which to search recursively
 * @param Boolean? $isBroken if set returns only the broken links (true) or non broken links (false)
 * @param Boolean? $isRelative if set returns only the relative links (true) or the absolute links (false)
 * @param String? $nameRegExpr should be a string of the form '/.../'. Default to null
 * @param Boolean? $ignoreDotFiles $ignoreDotFiles indicates if hidden items (.xxx) should be ignored. 
 * @return List*(Map('targetValue'=>String?,'isRelative'=>Boolean?,'targetPath'=>String?,'isBroken'=>Boolean!,'realTarget'=>String!))
 */
function listAllLinksWithInfo(
    $root,
    $isBroken=null,
    $isRelative=null,
    $nameRegExpr=NULL,
    $ignoreDotFiles=TRUE    
    ) {
  $links = listAllFileNames($root,'link|error',$nameRegExpr,$ignoreDotFiles,true)  ;
  $selectedLinks = array() ;
  foreach($links as $link) {
    $info = linkInformation($link) ;
    if ((!isset($isRelative) || ($isRelative===$info['isRelative']))
        && (!isset($isBroken) || ($isBroken===$info['isBroken']) )) {
      $selectedLinks[$link] = $info ; 
    }
  } 
  return $selectedLinks ;
}

function relinkAbsoluteLinks(
    $root,
    $pattern,
    $replacement,
    $nameRegExpr=NULL,
    $ignoreDotFiles=TRUE   
    ) {
  $absoluteLinks=listAllLinksWithInfo($root,null,false,$nameRegExpr,$ignoreDotFiles) ;
  $results=array() ;
  foreach($absoluteLinks as $absoluteLink) {
    $results[] = relink($root,$pattern,$replacement);
  }
  return $results ;
}

/**
 * @param unknown_type $path
 * @param unknown_type $compareTo
 * @return string
 */
function getRelativePath( $path, $compareTo ) {
  // clean arguments by removing trailing and prefixing slashes
  if ( substr( $path, -1 ) == '/' ) {
    $path = substr( $path, 0, -1 );
  }
  if ( substr( $path, 0, 1 ) == '/' ) {
    $path = substr( $path, 1 );
  }

  if ( substr( $compareTo, -1 ) == '/' ) {
    $compareTo = substr( $compareTo, 0, -1 );
  }
  if ( substr( $compareTo, 0, 1 ) == '/' ) {
    $compareTo = substr( $compareTo, 1 );
  }

  // simple case: $compareTo is in $path
  if ( strpos( $path, $compareTo ) === 0 ) {
    $offset = strlen( $compareTo ) + 1;
    return substr( $path, $offset );
  }

  $relative  = array(  );
  $pathParts = explode( '/', $path );
  $compareToParts = explode( '/', $compareTo );

  foreach( $compareToParts as $index => $part ) {
    if ( isset( $pathParts[$index] ) && $pathParts[$index] == $part ) {
      continue;
    }

    $relative[] = '..';
  }

  foreach( $pathParts as $index => $part ) {
    if ( isset( $compareToParts[$index] ) && $compareToParts[$index] == $part ) {
      continue;
    }

    $relative[] = $part;
  }

  return implode( '/', $relative );
}

/**
 * This function is realized by a system process. TODO document it. 
 * @param String! $rootDirectory the directory in which to search the string
 * @param RegExpr! $pattern the perl regexpr used by the grep command
 * @param 'files'|'lines' $mode controling the output of the function. 
 * If 'files' is specified only filenames of files that contains the pattern
 * are return in a list. 
 * In the 'lines' mode then a XXX. TODO
 */

function grepDirectory($rootDirectory,$pattern,$mode='files') {
  assert('$mode="files"') ;
  // get the real directory
  $directory = makePathAbsolute($rootDirectory,null,true) ;
  if ($directory === null) {
    return null ;
  }
  $out = systemGetOutput(ENV_GREPDIR_CMD,array($mode,$directory,$pattern),$errcode,'lines',"\n") ;
  if (isset($out)) {
    // TODO deal with other modes 
    return $out ;
  } else {
    return null ;
  }
  
}

