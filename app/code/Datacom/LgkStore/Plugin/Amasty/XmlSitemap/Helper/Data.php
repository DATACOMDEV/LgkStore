<?php

namespace Datacom\LgkStore\Plugin\Amasty\XmlSitemap\Helper;

class Data {
    public function __construct(
        
    )
    {
        
    }

    public function aroundGetCorrectUrl(\Amasty\XmlSitemap\Helper\Data $subject, $callable, $path, $storeId) {
        $retval = $callable($path, $storeId);

        if (empty($retval)) return $retval;

        return str_replace('http://', 'https://', $retval);
    }
}