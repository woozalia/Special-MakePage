 <?php
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
		  case '?TIMESTAMP':
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
