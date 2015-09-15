<?php
/*
  PURPOSE: Job for creating a page based on form contents, without further user intervention
  HISTORY:
    2015-09-15 copied from SF_CreatePageJob.php in SemanticForms (part of Semantic MediaWiki)
*/
class mwxcMakePageJob extends Job {

	function __construct( $title, $params = '', $id = 0 ) {
		parent::__construct( 'createPage', $title, $params, $id );
		// 'createPage' seems to be a MediaWiki-defined constant (documentation not found)
	}

	/*----
	  RETURNS: TRUE on success, FALSE on failure
	*/
	function run() {

		// ++ COPIED VERBATIM from SFCreatePageJob

		if ( is_null( $this->title ) ) {
			$this->error = "createPage: Invalid title";
			return false;
		}
		$article = new Article( $this->title, 0 );
		if ( !$article ) {
			$this->error = 'createPage: Article not found "' . $this->title->getPrefixedDBkey() . '"';
			return false;
		}

		$page_text = $this->params['page_text'];
		// change global $wgUser variable to the one
		// specified by the job only for the extent of this
		// replacement
		global $wgUser;
		$actual_user = $wgUser;
		$wgUser = User::newFromId( $this->params['user_id'] );
		$edit_summary = '';
		if( array_key_exists( 'edit_summary', $this->params ) ) {
			$edit_summary = $this->params['edit_summary'];
		}
		$article->doEdit( $page_text, $edit_summary );
		$wgUser = $actual_user;

		return true;

		// -- COPIED VERBATIM
	}
}