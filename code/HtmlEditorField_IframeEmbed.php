<?php

class HtmlEditorField_IframeEmbed extends HtmlEditorField_File
{

    protected $iframe;

    public function __construct($url, $file = null)
    {
        parent::__construct($url, $file);
        $this->iframe = $url;
    }

    public function getWidth()
    {
        return 500;
    }

    public function getHeight()
    {
        return 500;
    }

    public function getPreview()
    {
        return false;
    }

    public function getName()
    {
        return $this->iframe;
    }

    public function getType()
    {
        return 'Iframe';
    }

    public function getOembed()
    {
        return $this->iframe;
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
