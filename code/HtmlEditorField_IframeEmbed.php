<?php

class HtmlEditorField_IframeEmbed extends HtmlEditorField_File
{
    protected $iframeUrl;

    protected $iframeData;

    public function __construct($url, $data = null)
    {
        parent::__construct($url, $data);
        $this->iframeUrl = $url;
        $this->iframeData = $data;
    }

    public function getWidth()
    {
        return ($this->iframeData && $this->iframeData->Width) ? $this->iframeData->Width : 500;
    }

    public function getHeight()
    {
        return ($this->iframeData && $this->iframeData->Height) ? $this->iframeData->Height : 500;
    }

    public function getPreview()
    {
        return false;
    }

    public function getName()
    {
        return $this->iframeUrl;
    }

    public function getType()
    {
        return 'Iframe';
    }

    public function getOembed()
    {
        return $this->iframeUrl;
    }

    public function appCategory()
    {
        return 'iframe';
    }

    public function getInfo()
    {
        return false;
    }
}
