<?php
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
	function showEditForm($iSummary='') {
		if ($iSummary) {
			$this->summary = $iSummary;
		}
		parent::showEditForm();
	}
}

