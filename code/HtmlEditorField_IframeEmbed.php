<?php

class HtmlEditorField_IframeEmbed extends HtmlEditorField_File
{
    /**
     * @var string
     */
    protected $iframeUrl;

    /**
     * @var ArrayData
     */
    protected $iframeData;

    /**
     * @param string $url
     * @param ArrayData $data
     */
    public function __construct($url, ArrayData $data)
    {
        parent::__construct($url, $data);
        $this->iframeUrl = $url;
        $this->iframeData = $data;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return ($this->iframeData && $this->iframeData->Width) ? $this->iframeData->Width : 500;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return ($this->iframeData && $this->iframeData->Height) ? $this->iframeData->Height : 500;
    }

    /**
     * @return null
     */
    public function getPreview()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->iframeUrl;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'Iframe';
    }

    /**
     * @return string
     */
    public function getOembed()
    {
        return $this->iframeUrl;
    }

    /**
     * @return string
     */
    public function appCategory()
    {
        return 'iframe';
    }

    /**
     * @return null
     */
    public function getInfo()
    {
        return null;
    }
}
