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

use Magento\CatalogInventory\Api\Data\StockItemInterface;

class StockStateProviderPlugin
{
    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Bss\QuantityDropdown\Block\QuantityDropdown
     */
    protected $quantityDropdown;

    /**
     * @var \Bss\QuantityDropdown\Helper\Data
     */
    protected $helper;

    /**
     * StockStateProviderPlugin constructor.
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Bss\QuantityDropdown\Block\QuantityDropdown $quantityDropdown
     * @param \Bss\QuantityDropdown\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Bss\QuantityDropdown\Block\QuantityDropdown $quantityDropdown,
        \Bss\QuantityDropdown\Helper\Data $helper
    ) {
        $this->objectFactory = $objectFactory;
        $this->productFactory = $productFactory;
        $this->quantityDropdown = $quantityDropdown;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\CatalogInventory\Model\StockStateProvider $subject
     * @param \Closure $proceed
     * @param StockItemInterface $stockItem
     * @param $qty
     * @param $summaryQty
     * @param int $origQty
     * @return \Magento\Framework\DataObject|mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCheckQuoteItemQty(
        \Magento\CatalogInventory\Api\StockStateInterface $subject,
        \Closure $proceed,
        $productId,
        $itemQty,
        $qtyToCheck,
        $origQty,
        $scopeId = null
    ) {
        $result = $proceed($productId, $itemQty, $qtyToCheck, $origQty, $scopeId);
        $qty = $this->getNumber($itemQty);
        $isQtyOptionEnable = $this->helper->isQtyOptionEnable();
        if ($isQtyOptionEnable == 1) {
            return $result;
        }
        if (!$result->getHasError()) {
            $product = $this->getProduct($productId);
            $validateQty = $this->validateQty($product, $product->getName(), $qty);
            if ($validateQty) {
                $result = $this->objectFactory->create();
                $result->setHasError(true)
                    ->setMessage($validateQty)
                    ->setErrorCode('qty_min')
                    ->setQuoteMessage(__('Please correct the quantity for some products.'))
                    ->setQuoteMessageIndex('qty');
            }
        }
        return $result;
    }

    /**
     * @param $productId
     * @return \Magento\Catalog\Model\Product
     */
    protected function getProduct($productId)
    {
        return $this->productFactory->create()->load($productId);
    }

    /**
     * @param $product
     * @param $productName
     * @param $qty
     * @return bool|\Magento\Framework\Phrase
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function validateQty($product, $productName, $qty)
    {
        $message = false;
        $enableQtyOption = $this->helper->isQtyOptionEnable();
        $type = $this->quantityDropdown->checkProduct($product);
        if ($enableQtyOption != 1 || $type != 'none') {
            $checkTypeDefault = ["general-default", "product-default"];
            $checkTypeCustom = ["general-custom", "product-custom"];
            if (in_array($type, $checkTypeDefault)) {
                $enableQtyIncrements = $product->getEnableQtyIncrements();
                $qtyIncrements = $product->getQtyIncrements();
                if ($enableQtyIncrements == 1 && $qtyIncrements > 0) {
                    $qtyMaxConfig = $this->helper->getDefaultMax();
                    if ($qty > $qtyMaxConfig) {
                        $message = __('%1 is only bought at most %2.', $productName, $qtyMaxConfig);
                    }
                }

            }
            if (in_array($type, $checkTypeCustom)) {
                if ($type == "general-custom") {
                    $cusBefore = explode(',', $this->helper->getCustom());
                    if (empty($cusBefore)) {
                        $cusBefore = explode(',', $product->getCustomValue());
                    }
                } else {
                    $cusBefore = explode(',', $product->getCustomValue());
                    if (empty($cusBefore)) {
                        $cusBefore = explode(',', $this->helper->getCustom());
                    }
                }
                /*$cus = $this->quantityDropdown->validCustom($cusBefore);
                if (!empty($cus) && !in_array($qty, $cus)) {
                    $custom = implode(',', $cus);
                    $message = __('%1 is only sold with quantity is %2. Please update your cart items!', $productName, $custom);
                }*/
            }
        }
        return $message;
    }

    /**
     * Convert quantity to a valid float
     *
     * @param string|float|int|null $qty
     *
     * @return float|null
     */
    private function getNumber($qty)
    {
        if (!is_numeric($qty)) {
            return $this->format->getNumber($qty);
        }

        return $qty;
    }
}
