<?php
/**
 * Adds the ability to insert iframes through SilverStripe's "Insert Media" form
 */
class HtmlEditorField_Iframe extends Extension {

	// Add new allowed action for getting iframe info
	static $allowed_actions = array(
		'viewiframe'
	);

	protected $templateViewFile = 'HtmlEditorField_viewfile';

	/**
	 * Don't pass $form in by reference, as doing so and adding a field creates both a div and an 
	 * input with identical IDs - which is both invalid HTML and breaks the ability to click on the
	 * label and focus on the input
	 */
	public function updateMediaForm($form) {
		Requirements::javascript(HTMLEDITORIFRAME_BASE . '/javascript/HtmlEditorField_Iframe.js');
		Requirements::css(HTMLEDITORIFRAME_BASE . '/css/HtmlEditorField_Iframe.css');

		// Get the existing form for re-using later
		$fields = $form->Fields();
		$controller = $form->Controller();
		$name = $form->FormName();
		$actions = $form->Actions();

		$numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span>'
			. '<strong class="title">%s</strong></span>';

		$fromIframe = new CompositeField(
			new LiteralField('headerIframe',
				'<h4>' . sprintf($numericLabelTmpl, '1', "Iframe URL") . '</h4>'),
			$iframeURL = new TextField('IframeURL', 'http://'),
			new LiteralField('addIframeImage',
				'<button class="action ui-action-constructive ui-button field add-iframe" data-icon="addMedia"></button>')
		);

		$iframeURL->addExtraClass('iframeurl');
		$fromIframe->addExtraClass('content ss-uploadfield from-web');
		
		// $fields->dataFieldByName() doesn't appear to work
		$tabset = $fields[1]->fieldByName("MediaFormInsertMediaTabs");

		$tabset->push(new Tab('From an Iframe', $fromIframe));

		$form = new Form(
			$controller,
			$name,
			$fields,
			$actions
		);

		return $form;
	}

	/**
	 * View iframe info.
	 *
	 * @see HtmlEditorField_Toolbar::viewfile()
	 */
	public function viewiframe($request) {
		// TODO Would be cleaner to consistently pass URL for both local and remote files,
		// but GridField doesn't allow for this kind of metadata customization at the moment.
		if($url = $request->getVar('FileURL')) {
			$url = $url;
			$file = new File(array(
				'Title' => basename($url),
				'Filename' => $url
			));	
		} else {
			throw new LogicException('Need either "ID" or "FileURL" parameter to identify the file');
		}
	
		$fileWrapper = new HtmlEditorField_IframeEmbed($url, $file);

		$fields = $this->getFieldsForIframe($url, $fileWrapper);
		$data = array('Fields' => $fields);

		return $fileWrapper->customise($data)->renderWith($this->templateViewFile);
	}

	/**
	 * Return field list for iframe insert/update form
	 *
	 * @see HtmlEditorField_Toolbar::getFieldsForOembed()
	 */
	protected function getFieldsForIframe($url, $file) {
		$thumbnailURL = FRAMEWORK_DIR . '/images/default_media.png';
		
		$fields = new FieldList(
			$filePreview = CompositeField::create(
				CompositeField::create(
					new LiteralField(
						"ImageFull",
						"<img id='thumbnailImage' class='thumbnail-preview' "
							. "src='{$thumbnailURL}?r=" . rand(1,100000) . "' alt='{$file->Name}' />\n"
					)
				)->setName("FilePreviewImage")->addExtraClass('cms-file-info-preview'),
				CompositeField::create(
					CompositeField::create(
						new ReadonlyField("FileType", _t('AssetTableField.TYPE','File type') . ':', $file->Type),
						$urlField = ReadonlyField::create('ClickableURL', _t('AssetTableField.URL','URL'),
							sprintf('<a href="%s" target="_blank" class="file">%s</a>', $url, $url)
						)->addExtraClass('text-wrap')
					)
				)->setName("FilePreviewData")->addExtraClass('cms-file-info-data')
			)->setName("FilePreview")->addExtraClass('cms-file-info'),
			new TextField('CaptionText', _t('HtmlEditorField.CAPTIONTEXT', 'Caption text')),
			DropdownField::create(
				'CSSClass',
				_t('HtmlEditorField.CSSCLASS', 'Alignment / style'),
				array(
					'left' => _t('HtmlEditorField.CSSCLASSLEFT', 'On the left, with text wrapping around.'),
					'leftAlone' => _t('HtmlEditorField.CSSCLASSLEFTALONE', 'On the left, on its own.'),
					'right' => _t('HtmlEditorField.CSSCLASSRIGHT', 'On the right, with text wrapping around.'),
					'center' => _t('HtmlEditorField.CSSCLASSCENTER', 'Centered, on its own.'),
				)
			)->addExtraClass('last')
		);
		if($file->Width != null){
			$fields->push(
				FieldGroup::create(
					_t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
					TextField::create(
						'Width', 
						_t('HtmlEditorField.IMAGEWIDTHPX', 'Width'), 
						$file->Width
					)->setMaxLength(5),
					TextField::create(
						'Height', 
						_t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'), 
						$file->Height
					)->setMaxLength(5)
				)->addExtraClass('dimensions last')
			);
		}
		$urlField->dontEscape = true;

		$fields->push(new HiddenField('URL', false, $url));
		
		return $fields;
	}

}