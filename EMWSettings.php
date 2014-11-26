<?php
/**
 * Inputs from LocalSettings
 * =========================
 * 
 * @var string $emwgGroupName: The name of the group/organization containing
 * the primary users of the wiki, e.g. "Accounting" or "HR"
 * @var bool $emwgDebug: whether or not to display debug bar
 * 
 *
 *		$emwgSettings = new EMWSettings( array(
 *			'groupName' => 'EVA',
 *			'debug'     => false,
 *			'groupsFolder' => 'Groups/',
 *		) );
 *
 */


/* name changes:

$emwgSettings->groupName			was: $emwgGroupName
$emwgSettings->groupPathName		was: $emwgGroupPathName, $egJSCMOD_GroupPathName

*/
class EMWSettings {

	protected $emwIP;
	protected $emwScriptPath;
	protected $groupName;
	protected $groupPathName;
	protected $groupsFolder;

	public function __construct( $settings = array() ) {
		global $wgScriptPath;
		
		$this->emwIP = __DIR__;
		$this->emwScriptPath = $wgScriptPath . '/extensions/JSCMOD';

		if ( ! isset( $settings[ 'groupName' ] ) ) {
			// FIXME: make this an Exception, not die()
			die( 'EMWSettings needs a group name' );
		}
		
		$this->setDebug( $settings[ 'debug' ] );
		$this->setNames( $settings[ 'groupName' ], $settings[ 'groupsFolder' ] );
		$this->addHookFunctions();


		## The following included script gets programmatically modified 
		## during backup operations to set read-only prior to backup and
		## unset when backup is complete
		include $this->emwIP . '/wgReadOnly.php';
		
	}
	
	protected function setDebug ( $debug = false ) {

		// development: error reporting
		if ( $debug ) {

			// turn error logging on
			error_reporting( -1 );
			ini_set( 'display_errors', 1 );
			ini_set( 'log_errors', 1 );
			
			// Output errors to log file
			ini_set( 'error_log', '$emwgIP/php.log' );

			// MediaWiki Debug Tools
			$GLOBALS['wgShowExceptionDetails'] = true;
			$GLOBALS['wgDebugToolbar'] = true;
			
			// Puts a bunch of debug info at the bottom of the page, which is also in
			// the debug toolbar I think
			// $wgShowDebug = true;

		}

		// production: no error reporting
		else {

			error_reporting( 0 );
			ini_set( 'display_errors', 0 );

		}
		
	}
	
	protected function setNames ( $groupName, $groupsFolder = 'Groups' ) {
		
		$this->groupName = $groupName;
		$this->groupPathName = str_replace( ' ', '', $groupName );

		## The URL base path to the directory containing the wiki;
		## defaults for all runtime URL paths are based off of this.
		## For more information on customizing the URLs please see:
		## http://www.mediawiki.org/wiki/Manual:Short_URL
		$GLOBALS['wgScriptPath']       = '/wiki/' . $groupName;
		$GLOBALS['wgScriptExtension']  = '.php';

		## The relative URL path to the skins directory
		$GLOBALS['wgStylePath']        = $GLOBALS['wgScriptPath'] . '/skins';

		$GLOBALS['wgSitename'] = $groupName . ' Wiki';
		$GLOBALS['wgMetaNamespace'] = str_replace( ' ', '_', $GLOBALS['wgSitename'] );
		$GLOBALS['wgEmergencyContact'] = str_replace( ' ', '-', $wgSitename ) . '-Wiki@mod2.jsc.nasa.gov'; // FIXME: remove JSC
		$GLOBALS['wgPasswordSender'] = $GLOBALS['wgEmergencyContact'];

		# Location of icons, logos, etc
		// FIXME: make this work with a separate repo for JSC group images
		$groupIconPath = $this->emwScriptPath . "/$groupsFolder/$groupName";
		$GLOBALS['wgLogo']             = $groupIconPath . '/logo.png';
		$GLOBALS['wgFavicon']          = $groupIconPath . '/favicon.ico';
		$GLOBALS['wgAppleTouchIcon']   = $groupIconPath . '/apple-touch-icon.png';
		
	}
	
	protected function addHookFunctions() {
	
		/**
		 * JSC-MOD specific javascript modifications
		 * FIXME: replace with ResourceLoader
		 **/
		$GLOBALS['wgHooks']['AjaxAddScript'][] = function ( $out ) {
			global $wgScriptPath;
			// $out->addScriptFile( $wgScriptPath .'/resources/session.min.js' );
			$out->addScriptFile( $wgScriptPath .'/extensions/JSCMOD/script.js' );

			return true;
		};
		
		// FIXME: this should probably go into the Auth extension
		// see http://www.mediawiki.org/wiki/Manual:Hooks/SpecialPage_initList
		// and http://www.mediawiki.org/w/Manual:Special_pages
		// and http://lists.wikimedia.org/pipermail/mediawiki-l/2009-June/031231.html
		// disable login and logout functions for all users
		$GLOBALS['wgHooks'][ 'SpecialPage_initList' ][] = function ( &$list ) {
			unset( $list[ 'Userlogout' ] );
			unset( $list[ 'Userlogin' ] );
			return true;
		};
		 
		// FIXME: this should probably go into the Auth extension
		// http://www.mediawiki.org/wiki/Extension:Windows_NTLM_LDAP_Auto_Auth
		// remove login and logout buttons for all users
		$GLOBALS['wgHooks'][ 'PersonalUrls' ][] = function ( &$personal_urls, &$wgTitle ) {  
			unset( $personal_urls["login"] );
			unset( $personal_urls["logout"] );
			unset( $personal_urls['anonlogin'] );
			return true;
		};

	}

	protected function applyUserRights () {
		/**
		 * AUTH SETTINGS
		 * 
		 * FIXME: This should probably move to the auth extension...
		 * 
		 */
		$GLOBALS['wgGroupPermissions']['*']['createaccount'] = false;
		$GLOBALS['wgGroupPermissions']['*']['read'] = false;
		$GLOBALS['wgGroupPermissions']['*']['edit'] = false;

		$GLOBALS['wgGroupPermissions']['user']['talk'] = true; 
		$GLOBALS['wgGroupPermissions']['user']['read'] = true;
		$GLOBALS['wgGroupPermissions']['user']['edit'] = false;

		// Viewer group is used by the Auth_remoteuser extension to allow only those in
		// group "Viewer" to view the wiki. This allows anyone with NDC auth to get to the
		// wiki (which auto-creates an account for them), but doesn't allow those users to
		// see any of the wiki (besided the "access denied" page and "request access" page)
		$GLOBALS['wgGroupPermissions']['Viewer']['talk'] = true; 
		$GLOBALS['wgGroupPermissions']['Viewer']['read'] = true;
		$GLOBALS['wgGroupPermissions']['Viewer']['edit'] = false;
		$GLOBALS['wgGroupPermissions']['Viewer']['movefile'] = true;

		$GLOBALS['wgGroupPermissions']['Contributor'] = $GLOBALS['wgGroupPermissions']['user'];
		$GLOBALS['wgGroupPermissions']['Contributor']['edit'] = true;
		$GLOBALS['wgGroupPermissions']['Contributor']['unwatchedpages'] = true;

		#
		#   CURATORs: people with delete permissions for now
		#
		$GLOBALS['wgGroupPermissions']['Curator']['delete'] = true; // Delete pages
		$GLOBALS['wgGroupPermissions']['Curator']['bigdelete'] = true; // Delete pages with large histories
		$GLOBALS['wgGroupPermissions']['Curator']['suppressredirect'] = true; // Not create redirect when moving page
		$GLOBALS['wgGroupPermissions']['Curator']['browsearchive'] = true; // Search deleted pages
		$GLOBALS['wgGroupPermissions']['Curator']['undelete'] = true; // Undelete a page
		$GLOBALS['wgGroupPermissions']['Curator']['deletedhistory'] = true; // View deleted history w/o associated text
		$GLOBALS['wgGroupPermissions']['Curator']['deletedtext'] = true; // View deleted text/changes between deleted revs

		#
		#   MANAGERs: can edit user rights, plus used in MediaWiki:Approvedrevs-permissions
		#   to allow managers to give managers the ability to approve pages (lesson plans, ESOP, etc)
		#
		$GLOBALS['wgGroupPermissions']['Manager']['userrights'] = true; // Edit all user rights

		
		#
		# FIXME: what was this from? how does this apply with the settings above?
		# DELETE THESE WHEN ENABLED (commented it out when enabled auto-auth) 
		#$GLOBALS['wgGroupPermissions']['*']['createaccount'] = false;
		#$GLOBALS['wgGroupPermissions']['*']['edit'] = false;
		#$GLOBALS['wgGroupPermissions']['Contributor'] = $GLOBALS['wgGroupPermissions']['user'];
		#
		
		// for Extension:AdminLinks
		$GLOBALS['wgGroupPermissions']['sysop']['adminlinks'] = true;
		
		// for Extension:Interwiki
		$GLOBALS['wgGroupPermissions']['sysop']['interwiki'] = true;


	}

	protected function applySettings () {
	
		// keep this at 1 except when usership will be low and maintenance is being performed.
		$GLOBALS['wgJobRunRate'] = 1;

		 
		// shows number of watchers in recent changes
		$GLOBALS['wgRCShowWatchingUsers'] = true;

		// AJAX check for file overwrite pre-upload
		// $GLOBALS['wgAjaxUploadDestCheck'] = true;

		 // show number watching users on bottom of page...turn this on if not using WhoIsWatching
		// $GLOBALS['wgPageShowWatchingUsers'] = true;




		$GLOBALS['wgEnotifUserTalk']      = true;
		$GLOBALS['wgEnotifWatchlist']     = true;
		$GLOBALS['wgEmailAuthentication'] = true;

		// FIXME: probably encapsulate MySQL specific things in function such that
		// users can choose database

		# MySQL specific settings
		$GLOBALS['wgDBtype']           = 'mysql';
		$GLOBALS['wgDBprefix']         = "";

		# MySQL table options to use during installation or update
		$GLOBALS['wgDBTableOptions']   = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

		# Experimental charset support for MySQL 4.1/5.0.
		$GLOBALS['wgDBmysql5'] = false;

		## Shared memory settings
		$GLOBALS['wgMainCacheType']    = CACHE_NONE;
		$GLOBALS['wgMemCachedServers'] = array();


		## Disable all forms of MediaWiki caching
		// TAKEN FROM: http://thinkhole.org/wp/2006/09/13/disabling-caching-in-mediawiki/
		$GLOBALS['wgMainCacheType'] = CACHE_NONE;
		$GLOBALS['wgMessageCacheType'] = CACHE_NONE;
		$GLOBALS['wgParserCacheType'] = CACHE_NONE;
		//$GLOBALS['wgEnableParserCache'] = false;
		$GLOBALS['wgCachePages'] = false;



		## To enable image uploads, make sure the 'images' directory
		## is writable, then set this to true:
		$GLOBALS['wgEnableUploads']  = true;
		#$GLOBALS['wgUseImageMagick'] = true;
		#$GLOBALS['wgImageMagickConvertCommand'] = "/usr/bin/convert";

		# maximum size of an image that will generate a thumbnail. Not sure if larger images will be
		# prevented from being uploaded. If the images already were uploaded, then this number is reduced
		# the wiki will display "error creating thumbnail" in place of the thumbnail.
		// $GLOBALS['wgMaxImageArea'] = "100000000";

		// added this... was just allowing images without it...
		$GLOBALS['wgFileExtensions'] = array('png','gif','jpg','jpeg','mpp','pdf','tiff','bmp','docx', 'xlsx', 'pptx','ps','odt','ods','odp','odg','zip');
		$GLOBALS['wgStrictFileExtensions'] = false;

		// remove "this file type may contain malicious code" warning
		$GLOBALS['wgTrustedMediaFormats'][] = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
		$GLOBALS['wgTrustedMediaFormats'][] = "application/vnd.openxmlformats-officedocument.presentationml.presentation";
		$GLOBALS['wgTrustedMediaFormats'][] = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";

		// Due to issues uploading .docx, this variable was changed to the default from
		// MW 1.18. The only change was the removal of 'application/x-opc+zip' from the
		// blacklist.
		$GLOBALS['wgMimeTypeBlacklist'] = array(
			# HTML may contain cookie-stealing JavaScript and web bugs
			'text/html', 'text/javascript', 'text/x-javascript', 'application/x-shellscript',
			# PHP scripts may execute arbitrary code on the server
			'application/x-php', 'text/x-php',
			# Other types that may be interpreted by some servers
			'text/x-python', 'text/x-perl', 'text/x-bash', 'text/x-sh', 'text/x-csh',
			# Client-side hazards on Internet Explorer
			'text/scriptlet', 'application/x-msdownload',
			# Windows metafile, client-side vulnerability on some systems
			'application/x-msmetafile',
		);

		# InstantCommons allows wiki to use images from http://commons.wikimedia.org
		$GLOBALS['wgUseInstantCommons']  = false;

		## If you use ImageMagick (or any other shell command) on a
		## Linux server, this will need to be set to the name of an
		## available UTF-8 locale
		$GLOBALS['wgShellLocale'] = "en_US.utf8";

		## If you want to use image uploads under safe mode,
		## create the directories images/archive, images/thumb and
		## images/temp, and make them all writable. Then uncomment
		## this, if it's not already uncommented:
		#$GLOBALS['wgHashedUploadDirectory'] = false;

		## If you have the appropriate support software installed
		## you can enable inline LaTeX equations:
		$GLOBALS['wgUseTeX']           = false;

		## Set $GLOBALS['wgCacheDirectory'] to a writable directory on the web server
		## to make your wiki go slightly faster. The directory should not
		## be publically accessible from the web.
		#$GLOBALS['wgCacheDirectory'] = "$IP/cache";

		# Site language code, should be one of ./languages/Language(.*).php
		$GLOBALS['wgLanguageCode'] = "en";

		## Default skin: you can change the default skin. Use the internal symbolic
		## names, ie 'standard', 'nostalgia', 'cologneblue', 'monobook', 'vector':
		$GLOBALS['wgDefaultSkin'] = "vector";


		# Path to the GNU diff3 utility. Used for conflict resolution.
		$GLOBALS['wgDiff3'] = 'C:/Program Files (x86)/GnuWin32/bin/diff3.exe';

		# Use external mime detector
		// $GLOBALS['wgMimeDetectorCommand'] = "C:/Program Files (x86)/GnuWin/bin/file.exe -bi";


		# Query string length limit for ResourceLoader. You should only set this if
		# your web server has a query string length limit (then set it to that limit),
		# or if you have suhosin.get.max_value_length set in php.ini (then set it to
		# that value)
		$GLOBALS['wgResourceLoaderMaxQueryLength'] = -1;

		# End of automatically generated settings.
		# Add more configuration options below.

		// allows users to remove the page title.
		$GLOBALS['wgRestrictDisplayTitle'] = false;

		$GLOBALS['wgUseRCPatrol'] = false;
		$GLOBALS['wgUseNPPatrol'] = false;

		
		// Enable subpages on Main namespace
		$GLOBALS['wgNamespacesWithSubpages'][NS_MAIN] = true;


		## For attaching licensing metadata to pages, and displaying an
		## appropriate copyright notice / icon. GNU Free Documentation
		## License and Creative Commons licenses are supported so far.
		#$GLOBALS['wgEnableCreativeCommonsRdf'] = true;
		$GLOBALS['wgRightsPage'] = ""; # Set to the title of a wiki page that describes your license/copyright
		$GLOBALS['wgRightsUrl']  = "";
		$GLOBALS['wgRightsText'] = "";
		$GLOBALS['wgRightsIcon'] = "";
		# $GLOBALS['wgRightsCode'] = ""; # Not yet used





		require_once( "$IP/extensions/ParserFunctions/ParserFunctions.php" );
		$GLOBALS['wgPFEnableStringFunctions'] = true;
		require_once("$IP/extensions/StringFunctionsEscaped/StringFunctionsEscaped.php");

		require_once ( 'extensions/LabeledSectionTransclusion/lst.php' );
		require_once ( 'extensions/LabeledSectionTransclusion/lsth.php' );


		// use external data instead
		//require_once("$IP/extensions/DataTransclusion/DataTransclusion.php");

		include_once("$IP/extensions/ExternalData/ExternalData.php");

		// I think this is for web api url caching
		//$edgCacheTable = 'ed_url_cache';
		//$edgCacheExpireTime = 0;

		// opens external links in new window
		$GLOBALS['wgExternalLinkTarget'] = '_blank';

		require_once("$IP/extensions/Cite/Cite.php");
		$GLOBALS['wgCiteEnablePopups'] = true;


		// added this line to allow linking. specifically to Imagery Online.
		$GLOBALS['wgAllowExternalImages'] = true;
		$GLOBALS['wgAllowImageTag'] = true;

		// allow pipes (i.e. "|") as parameters in template calls
		require_once("$IP/extensions/PipeEscape/PipeEscape.php");


		// enable string functions in ParserFunctions extension
		$GLOBALS['wgPFEnableStringFunctions'] = true;


		require_once("$IP/extensions/intersection/DynamicPageList.php");

		//require_once( "$IP/extensions/StubManager/StubManager.php" );
		require_once( "$IP/extensions/HeaderFooter2/HeaderFooter.php" ); //  requires StubManager. Place below in LocalSettings.php

		// EXT REMOVED:
		require_once($IP.'/extensions/WhoIsWatching/WhoIsWatching.php');
		$GLOBALS['wgPageShowWatchingUsers'] = true;	

		// require_once( "$IP/extensions/Vector/Vector.php" );
		$GLOBALS['wgVectorUseSimpleSearch'] = true;

		//$GLOBALS['wgDefaultUserOptions']['useeditwarning'] = 1;
		// disable page edit warning (edit warning affect Semantic Forms)
		$GLOBALS['wgVectorFeatures']['editwarning']['global'] = false;

		//$GLOBALS['wgDefaultUserOptions']['vector-collapsiblenav'] = 1;
			// 'collapsiblenav' => array( 'global' => true, 'user' => true ),
			// 'collapsibletabs' => array( 'global' => true, 'user' => false ),
			// 'editwarning' => array( 'global' => false, 'user' => true ),
			// 'expandablesearch' => array( 'global' => false, 'user' => false ),
			// 'footercleanup' => array( 'global' => false, 'user' => false ),
			// 'simplesearch' => array( 'global' => false, 'user' => true ),


		$GLOBALS['wgDefaultUserOptions']['rememberpassword'] = 1;

		// users watch pages by default (they can override in settings)
		$GLOBALS['wgDefaultUserOptions']['watchdefault'] = 1;
		$GLOBALS['wgDefaultUserOptions']['watchmoves'] = 1;
		$GLOBALS['wgDefaultUserOptions']['watchdeletion'] = 1;
		$GLOBALS['wgDefaultUserOptions']['watchcreations'] = 1;


		#
		#	SEMANTIC MEDIAWIKI !
		#
		// require_once( "$IP/extensions/Validator/Validator.php" );
		// include_once( "$IP/extensions/SemanticMediaWiki/SemanticMediaWiki.php" );
		enableSemantics('MOD.EVA.LIBRARY.GOV');
		//$smwgShowFactbox = SMW_FACTBOX_NONEMPTY;



		require_once("$IP/extensions/CharInsert/CharInsert.php");

		include_once("$IP/extensions/SemanticForms/SemanticForms.php");

		// Semantic Internal Objects: this version is BETA.
		include_once("$IP/extensions/SemanticInternalObjects/SemanticInternalObjects.php");

		include_once("$IP/extensions/SemanticCompoundQueries/SemanticCompoundQueries.php");

		// SMW Settings Overrides:
		$smwgQMaxSize = 5000;
		 
		 # Arrays
		require_once( "$IP/extensions/Arrays/Arrays.php" );

		// case-insensitive search
		require_once( "$IP/extensions/TitleKey/TitleKey.php" );
		$GLOBALS['wgEnableMWSuggest'] = true;

		// fixes login issue for some users (login issue fixed in MW version 1.18.1 supposedly)
		$GLOBALS['wgDisableCookieCheck'] = true;

		#Set Default Timezone
		$GLOBALS['wgLocaltimezone'] = "America/Chicago";
		$oldtz = getenv("TZ");
		putenv("TZ=$GLOBALS['wgLocaltimezone']");


		# Maps
		// require_once( "$IP/extensions/Maps v2.0.1/Maps.php" );

		$GLOBALS['wgMaxUploadSize'] = 1024*1024*40;
		//$GLOBALS['wgUploadSizeWarning'] = 1024*1024*100;

		// allows groups with 'talk' privelege to create/edit talk pages (but not normal pages)
		require_once("$IP/extensions/TalkRight/TalkRight_1.4.1.php");


		#### BEGIN USER AUTH ####


		// Auth_remoteuser extension, updated by James Montalvo, blocks remote users not
		// part of the group defined by $GLOBALS['wgAuthRemoteuserViewerGroup']
		$GLOBALS['wgAuthRemoteuserViewerGroup'] = "Viewer"; // set to false to allow all valid REMOTE_USER to view; set to group name to restrict viewing to particular group
		$GLOBALS['wgAuthRemoteuserDeniedPage'] = "Access_Denied"; // redirect non-viewers to this page (namespace below)
		$GLOBALS['wgAuthRemoteuserDeniedNS'] = NS_PROJECT; // redirect non-viewers to page in this namespace


		require_once("$IP/extensions/Auth_remoteuser/Auth_remoteuser.php");
		$GLOBALS['wgAuth'] = new Auth_remoteuser();
		// see extension JSCMOD for auth settings


		include_once("$IP/extensions/AdminLinks/AdminLinks.php"); // see addition to $wgGroupPermissions

		#### END USER AUTH AND PERMISSIONS ####


		// Restrict Access by Category and Group
		// require_once("$IP/extensions/rabcg/rabcg.php");
		$GLOBALS['wgWhitelistRead'] = array('Special:UserLogin');

		require_once( "$IP/extensions/DismissableSiteNotice/DismissableSiteNotice.php" );


		require_once ( "$IP/extensions/BatchUserRights/BatchUserRights.php" );
		$GLOBALS['wgBatchUserRightsGrantableGroups'] = array(
			'Viewer',
			'Contributor',
			'CX3',
		);

		require_once("$IP/extensions/ImportUsers/ImportUsers.php");
		$GLOBALS['wgShowExceptionDetails'] = true;

		// require_once("$IP/extensions/SemanticResultFormats/SemanticResultFormats.php");
		$srfgFormats = array(
			'calendar', 'timeline', 
			//'exhibit', 
			'eventline', 'tree', 'oltree', 
			'ultree', 'tagcloud', 'sum', 'pagewidget');

		require_once("$IP/extensions/HeaderTabs/HeaderTabs.php");
		$htEditTabLink = false;
		$htRenderSingleTab = true;

		require_once("$IP/extensions/EditUser/EditUser.php");

		require_once("$IP/extensions/WikiEditor/WikiEditor.php");
		# Enables use of WikiEditor by default but still allow users to disable it in preferences
		$GLOBALS['wgDefaultUserOptions']['usebetatoolbar'] = 1;
		$GLOBALS['wgDefaultUserOptions']['usebetatoolbar-cgd'] = 1;

		# displays publish button
		$GLOBALS['wgDefaultUserOptions']['wikieditor-publish'] = 1;

		# Displays the Preview and Changes tabs
		$GLOBALS['wgDefaultUserOptions']['wikieditor-preview'] = 1;

		require_once("$IP/extensions/CopyWatchers/CopyWatchers.php");

		require_once("$IP/extensions/SyntaxHighlight_GeSHi/SyntaxHighlight_GeSHi.php");

		require_once("$IP/extensions/Wiretap/Wiretap.php");


		# Displays the Publish and Cancel buttons on the top right side
		//$GLOBALS['wgDefaultUserOptions']['wikieditor-publish'] = 1;

		// require_once("$IP/extensions/PdfExport/PdfExport.php");
		# DomPDF
		// $GLOBALS['wgPdfExportDomPdfConfigFile'] = $IP . '/extensions/PdfExport/dompdf6/dompdf_config.inc.php'; // Path to the DomPdf config file
		# HTMLDoc
		// $GLOBALS['wgPdfExportHtmlDocPath'] = 'C:/Progra~1/HTMLDOC/htmldoc.exe';

		//require_once("$IP/extensions/Collection/Collection.php");

		//EXT REMOVED: require_once("$IP/extensions/ConfirmUsersEmail/ConfirmUsersEmail.php");


		// allows adding semantic properties to Templates themselves
		// (not just on pages via templates). 
		// ENABLE THIS AFTER ALL TEMPLATES HAVE BEEN CHECKED FOR PROPER FORM
		// i.e. using <noinclude> and <includeonly> properly
		// $smwgNamespacesWithSemanticLinks[NS_TEMPLATE] = true;


		require_once( "$IP/extensions/ApprovedRevs/ApprovedRevs.php" );
		$egApprovedRevsAutomaticApprovals = false;

		require_once "$IP/extensions/InputBox/InputBox.php";

		require_once "$IP/extensions/ReplaceText/ReplaceText.php";

		// require_once "$IP/extensions/SubPageList/SubPageList.php";

		require_once "$IP/extensions/Interwiki/Interwiki.php"; // see addition to $wgGroupPermissions 

		require_once "$IP/extensions/IMSQuery/IMSQuery.php";

		// $GLOBALS['wgEnableScaryTranscluding'] = true;

		$GLOBALS['wgFileExtensions'][] = 'mp3';
		$GLOBALS['wgFileExtensions'][] = 'aac';
		$GLOBALS['wgFileExtensions'][] = 'msg';

		require_once "$IP/extensions/MasonryMainPage/MasonryMainPage.php";

		require_once "$IP/extensions/MeetingMinutes/MeetingMinutes.php";
		require_once "$IP/extensions/Synopsize/Synopsize.php";

		require_once "$IP/extensions/WatchAnalytics/WatchAnalytics.php";
		$egPendingReviewsEmphasizeDays = 14; // makes Pending Reviews shake after X days


		require_once "$IP/extensions/NumerAlpha/NumerAlpha.php";
		require_once "$IP/extensions/Variables/Variables.php";
		require_once "$IP/extensions/SummaryTimeline/SummaryTimeline.php";


		// Increase from default setting for large form
		// See https://www.mediawiki.org/wiki/Extension_talk:Semantic_Forms/Archive_April_to_June_2012#Error:_Backtrace_limit_exceeded_during_parsing
		ini_set('pcre.backtrack_limit',10000000); //10million

		// $GLOBALS['wgMemoryLimit'] = 500000000; //Default is 50M. This is 500M.

		require_once "$IP/extensions/YouTube/YouTube.php";
	
	}

}
