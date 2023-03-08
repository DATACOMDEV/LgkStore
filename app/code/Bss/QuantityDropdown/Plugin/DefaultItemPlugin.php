<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_QuantityDropdown
 * @author     Extension Team
 * @copyright  Copyright (c) 2018-2019 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */

namespace Bss\QuantityDropdown\Plugin;

class DefaultItemPlugin
{
    /**
     * @var QuantityDropdown
     */
    protected $dropdown;

    /**
     * @var \Bss\QuantityDropdown\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * CategoryPlugin constructor.
     * @param \Bss\QuantityDropdown\Block\QuantityDropdown $dropdown
     * @param \Bss\QuantityDropdown\Helper\Data $helper
     */
    public function __construct(
        \Bss\QuantityDropdown\Block\QuantityDropdown $dropdown,
        \Bss\QuantityDropdown\Helper\Data $helper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->dropdown = $dropdown;
        $this->helper = $helper;
        $this->productRepository = $productRepository;
    }

    /**
     * @param \Magento\Checkout\CustomerData\AbstractItem $subject
     * @param $result
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetItemData(
        \Magento\Checkout\CustomerData\AbstractItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item $item
    ) {
        $enable = $this->helper->isEnable();
        $result = $proceed($item);
        $productType = $result['product_type'];
        $data['dropdownQty'] = '';
        $enableType = ['simple', 'virtual', 'downloadable', 'configurable'];

        $product = null;
        if ($enable == 1 && in_array($productType, $enableType)) {
            $productSku = $result['product_sku'];
            
            try {
                $product = $this->productRepository->get($productSku);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $ex) {
                $productSku = explode('-', $productSku);
                $productSku = $productSku[0];

                try {
                    $product = $this->productRepository->get($productSku);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $ex) {
                    $product = null;
                }
            }
        }

        if (!empty($product)) {
            $insertHtml = $this->dropdown->getHtml($product, $item->getQty());
            if ($insertHtml && $insertHtml != '') {
                $data['dropdownQty'] = $insertHtml;
            }
        }

        return \array_merge(
            $result,
            $data
        );
    }
}
