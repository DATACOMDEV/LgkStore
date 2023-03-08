<?php

namespace Datacom\LgkStore\Block\Element;

class Template extends \Magento\Framework\View\Element\Template {
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCacheKeyInfo()
    {
        return [
            'BLOCK_TPL',
            $this->_storeManager->getStore()->getCode(),
            $this->getTemplateFile(),
            //'base_url' => $this->getBaseUrl(),
            'template' => $this->getTemplate()
        ];
    }

    protected function getCacheLifetime()
    {
        return 604800;
    }
}