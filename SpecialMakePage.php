<?php
/*
 NAME: SpecialMakePage
 PURPOSE: Special page for creating a new page from a form
	Other extensions can do this, but they don't make it at all easy
	to base the title on variables which are substituted from fields on the form.
 AUTHOR: Woozle Staddon
 VERSION:
	2007-11-23 (Wzl) Writing started
	2007-11-24 (Wzl) More or less working; doesn't work with _POST data,
		and I don't understand why not.
	2007-11-29 (Wzl) Adding variable for defining new title name
	2008-01-22 (Wzl) Put in some friendly debugging text for when title can't be created
	2008-06-25 (Wzl) Added "!TIMESTAMP" internal variable, and framework for adding more internal variables
		Future: If it ever turns out that we really want to be able to base these on the contents of a page,
		I suggest a syntax like <<@page_name>>. The page would need to be parsed in some cases but possibly
		not in others; perhaps <<@@page_name>> to indicate the page should be parsed?
	2008-07-24 (Wzl) Minor tweak so it will work with MW v1.12
	2008-09-29 (Wzl) - 0.4 - Made keys case-insensitive so it would work again. Don't know why this is suddenly a problem.
	2009-02-11 (Wzl) clsStringTemplate is now in a separate php file
	2009-02-13 (Wzl) $wgOptCP_SubstFinish "]" -> "$]" so links and other bracketed stuff don't confuse the var parser
	2009-02-26 (Wzl) - 0.5 - "$objNewEdit->action = 'submit';" before "->showEditForm()" fixes MW 1.14 problem
	2009-06-10 (Wzl) - 0.51 - $wgOptCP_SubstSetVal had no default value, which could cause warning msg in "strict" mode
	2010-08-18 (Wzl) - 0.6 - adding API calls
	2014-10-25 (Wzl) - 0.7 - this has definitely been modified since 2010, though I don't know how specifically.
	  Also, replacing old libmgr with config-libs.
	2014-12-15 (Wzl) - 0.71 - no longer invokes Ferreteria config-libs from here
*/
//clsLibMgr::Load('StringTemplate',__FILE__,__LINE__);

$wgSpecialPages['MakePage'] = 'SpecialMakePage'; # Let MediaWiki know about your new special page.
$wgExtensionCredits['other'][] = array(
	'name' => 'Special:MakePage',
	'url' => 'http://htyp.org/MediaWiki/Special/MakePage',
	'description' => 'special page for making new pages using form data plus a template',
	'author' => '[http://htyp.org/User:Woozle Woozle (Nick) Staddon]',
	'version' => '0.71 2014-12-15'
);
$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['MakePage'] = $dir . 'SpecialMakePage.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles['MakePage'] = $dir . 'SpecialMakePage.i18n.php'; # Location of a messages file

// LIBRARIES
//require(KFP_LIB.'/config-libs.php');
//require(KFP_LIB_MW.'/config-libs.php');

// Options which can be overridden in LocalSettings:
// -- these strings indicate the start and end of a template variable name
/* old defaults
$wgOptCP_SubstStart = '<<';
$wgOptCP_SubstFinish = '>>';
*/
$wgOptCP_SubstStart = '[$';
$wgOptCP_SubstFinish = '$]';
$wgOptCP_SubstSetVal = '=';

function wfSpecialMakePage() {
// This registers the page's class. I think.
	global $wgRequest;

	$app = new SpecialMakePage($wgRequest);

	$app->doCreate();
}

// 2012-01-21 are these needed anymore?
//require_once( 'includes/SpecialPage.php' );
//require_once( 'includes/EditPage.php' );

class SpecialMakePage extends SpecialPageApp {
    public function __construct() {
//	global $wgMessageCache;

//	SpecialPage::SpecialPage( 'MakePage','edit' );
	parent::__construct('MakePage');
	//$this->includable( true );	// not sure what replaces this
        //$wgMessageCache->addMessage('makepage', 'Make new pages from form data');
    }
    public function doCreate() {
	global $wgRequest, $wgOut, $wgTitle;
 
	$arArgs['!TITLETPLT']	= $wgRequest->getText('!TITLETPLT');	// template for new page's title
	$arArgs['!TPLTPAGE']	= $wgRequest->getText('!TPLTPAGE');	// page to use as a template
	$arArgs['!TPLTTEXT']	= $wgRequest->getText('!TPLTTEXT');	// text to use as a template
	$arArgs['!TPLTSTART']	= $wgRequest->getText('!TPLTSTART');	// optional starting marker
	$arArgs['!TPLTSTOP']	= $wgRequest->getText('!TPLTSTOP');	// optional stopping marker
	$this->API_Create($arArgs);
    }
    function API_Create(array $iArgs) {
	global $wgOut, $wgTitle;
	global $wgOptCP_SubstStart;
	global $wgOptCP_SubstFinish;
	global $wgOptCP_SubstSetVal;
	global $wgErrorText;
	global $wxgDebug;

	$this->setHeaders();
/*
	$strNewTitle	= $wgRequest->getText('!TITLETPLT');	// template for new page's title
	$in_tpltpg	= $wgRequest->getText('!TPLTPAGE');	// page to use as a template
	$in_tplttxt	= $wgRequest->getText('!TPLTTEXT');	// text to use as a template
	$strDataStart	= $wgRequest->getText('!TPLTSTART');	// optional starting marker
	$strDataStop	= $wgRequest->getText('!TPLTSTOP');	// optional stopping marker
/*/
	$strNewTitle	= $iArgs['!TITLETPLT'];	// template for new page's title
	$in_tpltpg	= $iArgs['!TPLTPAGE'];	// page to use as a template
	$in_tplttxt	= $iArgs['!TPLTTEXT'];	// text to use as a template
	$strDataStart	= $iArgs['!TPLTSTART'];	// optional starting marker
	$strDataStop	= $iArgs['!TPLTSTOP'];	// optional stopping marker
//*/
	$referer	= $_SERVER['HTTP_REFERER'];		// name of page which sent the form data
//	$strNewTitle	= $in_linkdate.' '.$in_newtitle;
//	$strNewTitleTest = '<<date>> <<pagetitle>>';	// debug
//	$in_NewTitle	= $wgRequest->getText('tplttext');	// text to use as a template

// get the text to be used as a template:
	if ($in_tpltpg) {
	// if it's a page, load that (overrules direct specification):
		$objTplt = Title::newFromText($in_tpltpg);
		$objArtcl = new Article($objTplt);
		$strNewText = $objArtcl->getContent();
	} else {
		$strNewText = $in_tplttxt;
	}
// truncate before/after markers, if any:
	if ($strDataStop != '') {
		$posSt = strpos ( $strNewText, $strDataStop );
		if ($posSt !== FALSE) {
			$strNewText = substr($strNewText,0,$posSt);
		}
	}
	if ($strDataStart != '') {
		$posSt = strpos ( $strNewText, $strDataStart );
		if ($posSt !== FALSE) {
			$strNewText = substr($strNewText,$posSt+strlen($strDataStart));
		}
	}

// do variable swapout:
	$objTplt = new clsStringTemplate_MWRequest($wgOptCP_SubstStart,$wgOptCP_SubstFinish,$wgOptCP_SubstSetVal);
	// calculate contents for new page
	$objTplt->Value = $strNewText;
	$strNewText = $objTplt->Replace();

	// calculate title for new page
	$strOldTitle = $strNewTitle;
	$objTplt->Value = $strOldTitle;
	$strNewTitle = $objTplt->Replace();

	$wgOut->setPageTitle( $strNewTitle );
/*
2008-01-22 For future reference: we might want to just remove any characters not found in $wgLegalTitleChars (=Title::legalChars())
	Sometimes CRs or TABs get pasted invisibly into the title, causing mysterious inability to create the page.
*/
	$objNewTitle = Title::newFromText( $strNewTitle );

/*
// Debugging:
	$wgOut->AddWikiText("GET DATA:");
	foreach ($_GET AS $key => $value) {
		$wgOut->AddWikiText("'''$key''': $value<br>");
	}

	$wgOut->AddWikiText("POST DATA:");
	foreach ($_POST AS $key => $value) {
		$wgOut->AddWikiText("'''$key''': $value<br>");
	}
	$wgOut->AddWikiText("--END--");
/**/

	$doPreview = true;	// always preview, for now
	if ($doPreview) {
		if (is_object($objNewTitle)) {
			$objNewArticle = new Article($objNewTitle);
			$objNewArticle->mContent = $strNewText;
			$objNewEdit = new EditOtherPage($objNewArticle);
			$strNewTitle_check = $objNewEdit->mTitle->getPrefixedText();
			$wgOut->AddWikiText("'''New Title''': [[$strNewTitle_check]]<br>'''Template''': [[$in_tpltpg]]<br>");
			$wgOut->AddWikiText("'''Preview''': <hr>\n$strNewText<hr>");
	
			$objNewEdit->textbox1 = $strNewText;
			$objNewEdit->preview = true;
			$wgOut->AddWikiText("'''Make final changes to the text here, and save to create the page:'''");
			$wgTitle = $objNewTitle;	// make the form post to the page we want to create
			$objNewEdit->action = 'submit';
			$objNewEdit->showEditForm('new page from form at '.$referer);
		} else {
			$txtOut = 'There seems to be a problem with the title: [['.$strNewTitle.']]';
			if ($wgErrorText) {
				$txtOut .= ': ' . $wgErrorText;
			}
			$txtOut .= "\n\n".$wxgDebug;
			$wgOut->AddWikiText($txtOut);
		}
	} else {
		$objNewArticle->doEdit( $strNewText, 'new page from form at '.$referer, EDIT_NEW );
	}
	$wgOut->returnToMain( false );
    }
}

class EditOtherPage extends EditPage {
	function EditOtherPage( $article ) {
		$this->mArticle =& $article;
		$this->mTitle =& $article->mTitle;

		# Placeholders for text injection by hooks (empty per default)
		$this->editFormPageTop =
		$this->editFormTextTop =
		$this->editFormTextAfterWarn =
		$this->editFormTextAfterTools =
		$this->editFormTextBottom = "";
	}
/*	private function getContent() {
	// content already loaded by SetParams, so do nothing
	}
	function initialiseForm() {
	// overrides parent
		$this->edittime = $this->mArticle->getTimestamp();
		$this->textbox1 = $this->getContent();
		if ( !$this->mArticle->exists() && $this->mArticle->mTitle->getNamespace() == NS_MEDIAWIKI )
			$this->textbox1 = wfMsgWeirdKey( $this->mArticle->mTitle->getText() ) ;
		wfProxyCheck();
	}
*/
	function showEditForm($iSummary='') {
		if ($iSummary) {
			$this->summary = $iSummary;
		}
		parent::showEditForm();
	}
}
/* 2010-09-16 This class moved to StringTemplate.php
class clsStringTemplate_array extends clsStringTemplate {
// This version can be used if the values are in an associative array
	public $List;

	protected function GetValue($iName) {
		return $this->List[$iName];
	}
}
*/
class clsStringTemplate_MWRequest extends clsStringTemplate {
// This version is for $wgRequest in MediaWiki code
	private $Keys;

	function __construct($iStartMark, $iFinishMark) {
		parent::__construct($iStartMark, $iFinishMark);
	
		foreach ($_POST AS $key => $value) {
			$strKey = strtolower($key);
			$this->Keys[$strKey] = $key;
		}	
	}
	protected function GetValue($iName) {
		global $wgRequest, $wxgDebug;

		$wxgDebug .= '* ['.$iName.'] = ';
		switch ($iName) {
		  case '!TIMESTAMP':
			$strFmt = $this->GetValue('!TIMEFMT');
			//$strFmt = $wgRequest->getText('!TIMEFMT');
			if ($strFmt == '') {
				$strNow = 'must specify !TIMEFMT';
			} else {
				$strNow = date($strFmt);
			}
			$strOut = $strNow;
			break;
		  default:
//			$strVal = $wgRequest->getText($iName);
//			if ($strVal == '') {
				$strName = strtolower($iName);
				$strKey = $this->Keys[$strName];
				$strVal = $_POST[$strKey];
//			}
			$strOut = $strVal;
			break;
		}
		$wxgDebug .= '['.$strOut.']';
		return $strOut;
	}
}
