<?php

// these should eventually be user-configurable
$wgOptCP_SubstStart = '[$';
$wgOptCP_SubstFinish = '$]';
$wgOptCP_SubstSetVal = '=';

class SpecialMakePage extends SpecialPageApp {
    public function __construct() {
	parent::__construct('MakePage');
    }
    function execute( $par ) {
	    //$request = $this->getRequest();
	    //$output = $this->getOutput();

	    $this->doCreate();
    }
    public function doCreate() {
	global $wgRequest, $wgOut, $wgTitle;

	$wgOut->setPageTitle( 'Special:MakePage' );

	// collect required arguments:
	$arArgs['!TITLETPLT']	= $wgRequest->getText('!TITLETPLT');	// template for new page's title
	$arArgs['!TPLTPAGE']	= $wgRequest->getText('!TPLTPAGE');	// page to use as a template
	$arArgs['!TPLTTEXT']	= $wgRequest->getText('!TPLTTEXT');	// text to use as a template

	if (!$wgRequest->getBool('!TITLETPLT')) {
	    $out = '<p>This page requires specific arguments to be submitted from a form.'
	      .' See <a href="http://htyp.org/MediaWiki/Special/MakePage">the documentation</a> for details.</p>'
	      .'<p>Required arguments, as received:'.clsArray::Render($arArgs,1).'</p>'
	      .'<p>You must include !TITLETPLT and either !TPLTPAGE or !TPLTTEXT.';

	    $wgOut->AddHTML($out);	// display error diagnostic
	} else {

	    // collect optional arguments:
	    $arArgs['!TPLTSTART']	= $wgRequest->getText('!TPLTSTART');	// optional starting marker
	    $arArgs['!TPLTSTOP']	= $wgRequest->getText('!TPLTSTOP');	// optional stopping marker
	    $arArgs['!IMMEDIATE']	= $wgRequest->getBool('!IMMEDIATE');	// optional: create page immediately

	    $this->API_Create($arArgs);
	}
    }
    function API_Create(array $iArgs) {
	global $wgOut, $wgTitle, $wgUser;
	global $wgOptCP_SubstStart;
	global $wgOptCP_SubstFinish;
	global $wgOptCP_SubstSetVal;
	global $wgErrorText;
	global $wxgDebug;

	$this->setHeaders();

	$strNewTitle	= $iArgs['!TITLETPLT'];	// template for new page's title
	$in_tpltpg	= $iArgs['!TPLTPAGE'];	// page to use as a template
	$in_tplttxt	= $iArgs['!TPLTTEXT'];	// text to use as a template
	$strDataStart	= $iArgs['!TPLTSTART'];	// optional starting marker
	$strDataStop	= $iArgs['!TPLTSTOP'];	// optional stopping marker
	$doImmediate	= $iArgs['!IMMEDIATE'];	// optional: create page immediately

	$referer	= $_SERVER['HTTP_REFERER'];		// name of page which sent the form data
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
	$mwoNewTitle = Title::newFromText( $strNewTitle );
	if (is_object($mwoNewTitle)) {
	    $sEditSumm = 'new page from form at '.$referer;

	    $doPreview = !$doImmediate;	// if not creating page immediately, preview it first
	    if ($doPreview) {
		$mwoNewArticle = new Article($mwoNewTitle);
		$mwoNewArticle->mContent = $strNewText;
		$mwoxNewEdit = new EditOtherPage($mwoNewArticle);
		$strNewTitle_check = $mwoxNewEdit->mTitle->getPrefixedText();
		$wgOut->AddWikiText("'''New Title''': [[$strNewTitle_check]]<br>'''Template''': [[$in_tpltpg]]<br>");
		$wgOut->AddWikiText("'''Preview''': <hr>\n$strNewText<hr>");

		$mwoxNewEdit->textbox1 = $strNewText;
		$mwoxNewEdit->preview = $doPreview;
		$wgOut->AddWikiText("'''Make final changes to the text here, and save to create the page:'''");
		$wgTitle = $mwoNewTitle;	// make the form post to the page we want to create
		$mwoxNewEdit->action = 'submit';
		$mwoxNewEdit->showEditForm($sEditSumm);
	    } else {

		//* 2015-09-15 This code does create a new page with the correct contents,
		  // but it has to be manually purged before tags are parsed.

		$mwoPage = new WikiPage($mwoNewTitle);
		$mwoPage->doEdit($strNewText,$sEditSumm,EDIT_NEW);

		// these additional lines don't seem to change anything:

		//$mwoRevision = $mwoPage->getRevision();
		//$arOpt = array('created' => TRUE);
		//$mwoPage->doEditUpdates($mwoRevision,$wgUser,$arOpt);

		// nor does this line:
		//$mwoNewTitle->invalidateCache();

		/**/

		/* 2015-09-15 [ADAPTED FROM FORMLINKER] The following code doesn't seem to make any db changes at all.
		  It displays $strNewTitle as the SpecialPage's title, and that's all.

		$params['user_id'] = $wgUser->getId();	// TODO: Allow edits to be done by a bot, so regular users can create protected pages.
		$params['page_text'] = $strNewText;	// contents for new page
		$params['edit_summary'] = $sEditSumm;	// TODO: Make the edit summary settable from the template.
		$mwoxJob = new mwxcMakePageJob( $mwoNewTitle, $params );

		// ++ COPIED (with minor modification) from SF_FormLinker.php (part of Semantic Forms, which is part of Semantic MediaWiki)
		// 2015-09-15 THIS STILL DOESN'T WORK. No page is inserted.

		$jobs = array( $mwoxJob );
		if ( class_exists( 'JobQueueGroup' ) ) {
		    JobQueueGroup::singleton()->push( $jobs );
		} else {
		    // MW <= 1.20
		    Job::batchInsert( $jobs );
		}

		// -- COPIED
		*/

		// VESTIGES OF EARLIER ATTEMPTS, none any more successful:

		//$url = $mwoNewTitle->getLocalURL();
		//$wgOut->redirect( $url );

		// redirect to the new page (TODO: allow redirection to a different page if specified)
		//$url = $mwoNewTitle->getLocalURL(array('action'=>'purge'));
		//wfGetDB(DB_MASTER)->doPreOutputCommit();	// wrong object type -- needs to be a MediaWiki object, but don't know how to get one
		// we could also try this:
		//$mwoNewTitle->invalidateCache();
		// this doesn't work regardless of whether it comes before or after the redirect
		//$mwoPage->doPurge();	// make sure the page gets parsed (TODO: make this optional?)
	    }
	} else {
	    $txtOut = 'There seems to be a problem with the title: [['.$strNewTitle.']]';
	    if ($wgErrorText) {
		    $txtOut .= ': ' . $wgErrorText;
	    }
	    $txtOut .= "\n\n".$wxgDebug;
	    $wgOut->AddWikiText($txtOut);
	}
	$wgOut->returnToMain( false );
    }
}
