<?php

namespace Datacom\LgkStore\Block\Login;

class Index extends \Magento\Framework\View\Element\Template
{

    protected $_request;
    protected $_urlDecoder;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Url\DecoderInterface $urlDecoder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_request = $request;
        $this->_urlDecoder = $urlDecoder;
    }

    public function getCustomReferer() {
        $retval = $this->_request->getParam(\Datacom\LgkStore\Model\Constants::CUSTOM_REFERER_QUERY_PARAM);
        
        if ($retval) {
            $retval = $this->_urlDecoder->decode($retval);
        }

        return $retval;
    }
}