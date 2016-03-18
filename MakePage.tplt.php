 <?php
 class clsStringTemplate_MWRequest extends clsStringTemplate {
// This version is for handling $wgRequest data in MediaWiki code
    private $Keys;

    function __construct($iStartMark, $iFinishMark) {
	parent::__construct($iStartMark, $iFinishMark);

	// index the POST variables in all-lowercase, just to avoid problems
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
	    $out = '"!TIMESTAMP" has been renamed "?TIMESTAMP" -- please update the page template.';
	    break;
	  case '?TIMESTAMP':
	    $strFmt = $this->GetValue('!TIMEFMT');
	    //$strFmt = $wgRequest->getText('!TIMEFMT');
	    if ($strFmt == '') {
		$strNow = 'must specify !TIMEFMT';
	    } else {
		$strNow = date($strFmt);
	    }
	    $out = $strNow;
	    break;
	  default:
	    $sName = strtolower($iName);
	    if (fcArray::Exists($this->Keys,$sName)) {
		// TODO: add some way to make specific fields mandatory
		$sKey = $this->Keys[$sName];	// get $_POST key
		$sVal = $_POST[$sKey];		// get $_POST value
	    } else {
		global $wgOut;

		$sMsg = "Internal error: caller requested value of [$sName], but it was not found in the submitted form data. That data was:"
		  .fcArray::Render($_POST)
		  ;
		$wgOut->AddHTML($sMsg);
		$sVal = NULL;
	    }
	    $out = $sVal;
	    break;
	}
	$wxgDebug .= '['.$out.']';
	return $out;
    }
}
