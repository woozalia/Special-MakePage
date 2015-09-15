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
	2015-09-11 (Wzl) a little tidying; no code changes
*/

$wgSpecialPages['MakePage'] = 'SpecialMakePage'; # Let MediaWiki know about your new special page.
$wgExtensionCredits['other'][] = array(
	'name' => 'Special:MakePage',
	'url' => 'http://htyp.org/MediaWiki/Special/MakePage',
	'description' => 'special page for making new pages using form data plus a template',
	'author' => '[http://htyp.org/User:Woozle Woozle (Nick) Staddon]',
	'version' => '0.71 2015-09-11'
);
$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['SpecialMakePage'] = $dir . 'MakePage.main.php'; # Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgExtensionMessagesFiles['MakePage'] = $dir . 'SpecialMakePage.i18n.php'; # Location of a messages file

$wgOptCP_SubstStart = '[$';
$wgOptCP_SubstFinish = '$]';
$wgOptCP_SubstSetVal = '=';

function wfSpecialMakePage() {
// This registers the page's class. I think.
	global $wgRequest;

	$app = new SpecialMakePage($wgRequest);

	$app->doCreate();
}


