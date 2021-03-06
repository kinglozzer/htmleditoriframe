<?php

class HtmlEditorField_Iframe extends Extension
{
    private static $allowed_actions = array(
        'viewiframe'
    );

    protected $templateViewFile = 'HtmlEditorField_viewfile';

    /**
     * @param Form &$form
     */
    public function updateMediaForm(Form &$form)
    {
        Requirements::javascript(HTMLEDITORIFRAME_BASE . '/javascript/HtmlEditorField_Iframe.js');
        Requirements::css(HTMLEDITORIFRAME_BASE . '/css/HtmlEditorField_Iframe.css');

        $numericLabelTmpl = <<<HTML
<h4>
    <span class="step-label">
        <span class="flyout">1</span><span class="arrow"></span>
        <strong class="title">Iframe URL</strong>
    </span>
</h4>
HTML;

        $actionButton = <<<HTML
<button class="action ui-action-constructive ui-button field add-iframe" data-icon="addMedia">
    Add url
</button>
HTML;

        $fields = $form->Fields();
        // HtmlEditorField_Toolbar::MediaForm() creates a FieldList consists of two composite fields,
        // the first containing headings and the second containing the fields we need
        $actualFields = $fields->last();
        $tabset = $actualFields->fieldByName("MediaFormInsertMediaTabs");

        $tabset->push(
            Tab::create(
                'From an Iframe',
                CompositeField::create(
                    LiteralField::create('headerIframe', $numericLabelTmpl),
                    TextField::create('IframeURL', 'Enter URL')
                        ->addExtraClass('iframeurl'),
                    LiteralField::create('addIframeImage', $actionButton)
                )->addExtraClass('content ss-uploadfield from-web')
            )->addExtraClass('htmleditorfield-from-iframe')
        );
    }

    /**
     * View iframe info.
     *
     * @see HtmlEditorField_Toolbar::viewfile()
     * @param SS_HTTPRequest $request
     * @return HTMLText
     * @throws LogicException
     */
    public function viewiframe(SS_HTTPRequest $request)
    {
        if ($url = $request->getVar('IframeURL')) {
            $url = $url;
            $data = ArrayData::create(array(
                'Title' => $url,
                'Width' => $request->getVar('Width') ?: null,
                'Height' => $request->getVar('Height') ?: null
            ));
        } else {
            throw new LogicException('Need an "IframeURL" parameter to identify the iframe');
        }

        $fileWrapper = new HtmlEditorField_IframeEmbed($url, $data);

        $fields = $this->getFieldsForIframe($url, $fileWrapper);
        $data = array('Fields' => $fields);

        return $fileWrapper->customise($data)->renderWith($this->templateViewFile);
    }

    /**
     * Return field list for iframe insert/update form
     *
     * @param string $url
     * @param  HtmlEditorField_IframeEmbed $iframe
     * @return FieldList
     */
    protected function getFieldsForIframe($url, HtmlEditorField_IframeEmbed $iframe)
    {
        $thumbnailURL = FRAMEWORK_DIR . '/images/default_media.png';

        $fields = FieldList::create(
            CompositeField::create(
                CompositeField::create(
                    LiteralField::create(
                        'ImageFull',
                        '<img class="thumbnail-preview" src="' . $thumbnailURL . '" alt="" />'
                    )
                )->setName('FilePreviewImage')->addExtraClass('cms-file-info-preview'),
                CompositeField::create(
                    CompositeField::create(
                        ReadonlyField::create('FileType', _t('AssetTableField.TYPE', 'File type') . ':', $iframe->Type),
                        $urlField = ReadonlyField::create('ClickableURL', _t('AssetTableField.URL', 'URL'),
                            sprintf('<a href="%s" target="_blank" class="file">%s</a>', $url, $url)
                        )->addExtraClass('text-wrap')
                    )
                )->setName('FilePreviewData')->addExtraClass('cms-file-info-data')
            )->setName('FilePreview')->addExtraClass('cms-file-info'),
            TextField::create('CaptionText', _t('HtmlEditorField.CAPTIONTEXT', 'Caption text')),
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

        $urlField->dontEscape = true;

        if ($iframe->Width != null) {
            $fields->push(
                FieldGroup::create(
                    _t('HtmlEditorField.IMAGEDIMENSIONS', 'Dimensions'),
                    TextField::create(
                        'Width',
                        _t('HtmlEditorField.IMAGEWIDTHPX', 'Width'),
                        $iframe->Width
                    )->setMaxLength(5),
                    TextField::create(
                        'Height',
                        _t('HtmlEditorField.IMAGEHEIGHTPX', 'Height'),
                        $iframe->Height
                    )->setMaxLength(5)
                )->addExtraClass('dimensions last')
            );
        }

        $fields->push(new HiddenField('URL', false, $url));

        $this->owner->extend('updateFieldsForIframe', $fields, $url, $iframe);

        return $fields;
    }
}
