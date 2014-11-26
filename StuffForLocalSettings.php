<?php



require_once "$IP/extensions/JSCMOD/JSCMOD.php";
$emwgSettings = new EMWSettings( array(
	'groupName' => 'EVA',
	'debug'     => $_GET['emw_debug'],
) );


## Database settings
$wgDBserver         = 'jsc-mod-db1.ndc.nasa.gov';
$wgDBname           = 'wiki_eva';
$wgDBuser           = 'MediaWikiUser';
$wgDBpassword       = 'mOd_fordxw1k1';


$wgEnableEmail      = true; // FIXME: update to "true" for release
$wgEnableUserEmail  = true; # UPO

$wgSMTP = array(
	'host'   => "mrelay.jsc.nasa.gov", // could also be an IP address
	'IDHost' => "mod2.jsc.nasa.gov", // Generally the domain name of the website (aka mywiki.org)
	'port'   => 25,    // Port to use when connecting to the SMTP server
	'auth'   => false  // mrelay.jsc.nasa.gov doesn't require auth
);

// FIXME: should this be different for each wiki?
$wgSecretKey = "457183063186fdd958cbc35354106464624f13da5a00d598270da1442a1ea6aa";


// on MOD servers can't access the desired "C:\\Windows\TEMP" directory
$wgTmpDirectory     = "d:\PHP\uploadtemp";



$wgJobRunRate = 1;
$wgPhpCli = 'd:\php\php.exe';
$wgRunJobsAsync = false;

// FIXME: JSC-specific info...in fact, EVA-specific (wouldn't be in ROBO wiki)
$wgGroupPermissions['CX3'] = $wgGroupPermissions['user'];



if ( false ) { //$_SERVER["REMOTE_USER"] == 'NDC\ejmontal' ) {
	
	// MediaWiki Debug Tools
	$wgShowExceptionDetails = false;
	$wgDebugToolbar = true;
	$wgShowDebug = false;
	
	// $wgEnableProfileInfo = true;
	// error_reporting( 1 );
	// ini_set( 'display_errors', 1 );

	// $wgDebugLogFile = "$IP/DebugLogFile.log";
}