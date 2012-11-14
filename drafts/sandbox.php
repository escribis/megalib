<?php
require_once '../tests/main.config.local.php' ;

require_once '../Symbols.php' ;
$json = file_get_contents('../tests/data/tmp/Wiki101Full.json') ;
if ($json===false) {
  echo "not found" ;
} else {
  $g = json_decode($json,true) ;
}

function collect($array,$x) {
  $r=array() ;
  foreach ($array as $v) {
    $r[]=$v[$x] ;
  }
  return $r ;
}

foreach (array('Concept','Language','Technology','Implementation','Feature','Page','Category') as $type) {
  $names = collect($g[$type],'name') ;
  echo '<h2>'.count($names).' '.$type.'</h2>' ; 
  echo implode(' | ',collect($g[$type],'name')) ;
}


//--------- create symbol indexes  ---------------
$decomposer = new RegExprBasedSymbolDecomposer() ;
$indexes = new SymbolIndexes() ;

// load the all texts from source files and construct the text map

foreach($g['Implementation'] as $name=>$x) {
  $text = $g['Implementation'][$name]['motivation'] ;
  $indexes->addText($name,$text,$decomposer) ;
}
echo $indexes->getCloud(null,'A') ;


exit (0) ;

$req_url = 'https://fireeagle.yahooapis.com/oauth/request_token';
$authurl = 'https://fireeagle.yahoo.net/oauth/authorize';
$acc_url = 'https://fireeagle.yahooapis.com/oauth/access_token';
$api_url = 'https://fireeagle.yahooapis.com/api/0.1';
$conskey = 'your_consumer_key';
$conssec = 'your_consumer_secret';

session_start();

// In state=1 the next request should include an oauth_token.
// If it doesn't go back to 0
if(!isset($_GET['oauth_token']) && $_SESSION['state']==1) $_SESSION['state'] = 0;
try {
  $oauth = new OAuth($conskey,$conssec,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
  $oauth->enableDebug();
  if(!isset($_GET['oauth_token']) && !$_SESSION['state']) {
    $request_token_info = $oauth->getRequestToken($req_url);
    $_SESSION['secret'] = $request_token_info['oauth_token_secret'];
    $_SESSION['state'] = 1;
    header('Location: '.$authurl.'?oauth_token='.$request_token_info['oauth_token']);
    exit;
  } else if($_SESSION['state']==1) {
    $oauth->setToken($_GET['oauth_token'],$_SESSION['secret']);
    $access_token_info = $oauth->getAccessToken($acc_url);
    $_SESSION['state'] = 2;
    $_SESSION['token'] = $access_token_info['oauth_token'];
    $_SESSION['secret'] = $access_token_info['oauth_token_secret'];
  }
  $oauth->setToken($_SESSION['token'],$_SESSION['secret']);
  $oauth->fetch("$api_url/user.json");
  $json = json_decode($oauth->getLastResponse());
  print_r($json);
} catch(OAuthException $E) {
  print_r($E);
}
?>
