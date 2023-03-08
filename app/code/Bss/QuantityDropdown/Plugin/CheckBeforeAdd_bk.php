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

use Bss\QuantityDropdown\Helper\Data;
use Magento\Catalog\Model\ProductRepository;

class CheckBeforeAdd
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Checkout\Model\SessionFactory
     */
    protected $checkoutSessionFactory;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    protected $configurable;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Bss\QuantityDropdown\Block\QuantityDropdown
     */
    protected $quantityDropdown;

    /**
     * CheckBeforeAdd constructor.
     * @param Data $helper
     * @param \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable
     * @param ProductRepository $productRepository
     * @param \Bss\QuantityDropdown\Block\QuantityDropdown $quantityDropdown
     */
    public function __construct(
        Data $helper,
        \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        ProductRepository $productRepository,
        \Bss\QuantityDropdown\Block\QuantityDropdown $quantityDropdown
    ) {
        $this->helper = $helper;
        $this->checkoutSessionFactory = $checkoutSessionFactory;
        $this->request = $request;
        $this->configurable = $configurable;
        $this->productRepository = $productRepository;
        $this->quantityDropdown = $quantityDropdown;
    }

    /**
     * @param \Magento\Checkout\Model\Cart $subject
     * @param $productInfo
     * @param null $requestInfo
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function beforeAddProduct(
        \Magento\Checkout\Model\Cart $subject,
        $productInfo,
        $requestInfo = null
    ) {
        if (!$this->helper->isEnable()) {
            return [$productInfo, $requestInfo];
        }
        $isQtyOptionEnable = $this->helper->isQtyOptionEnable();
        if ($isQtyOptionEnable == 1) {
            return [$productInfo, $requestInfo];
        }
        $product = $this->productRepository->getById($requestInfo['product']);
        $productName = $product->getName();
        $check = $this->request->getParam('qtys', -1);
        $this->returnProductPage($product);
        $cartItems = $this->getItems();
        $qty = (int)$this->request->getParam('qty', 1);
        if ($product->getTypeId() == 'configurable') {
            $childProductId = $this->getRealId($product, $requestInfo);
            if ($check == -1 && $childProductId) {
                $childProduct = $this->productRepository->getById($childProductId);
                $productType = $childProduct->getTypeId();
                if ($productType == 'simple') {
                    if (isset($cartItems[$childProduct->getId()])) {
                        $qty = $qty + (int)$cartItems[$childProduct->getId()];
                    }
                    $validate = $this->validateQty($childProduct, $childProduct->getName(), $qty);
                    if ($validate != '') {
                        $this->returnProductPage($product);
                        throw new \Magento\Framework\Exception\LocalizedException(__($validate));
                    }
                }
            }
        }
        if ($product->getTypeId() == 'simple'
            || $product->getTypeId() == 'downloadable'
            || $product->getTypeId() == 'vitual') {
            $productId = $product->getId();
            if (isset($cartItems[$productId])) {
                $qty = $qty + (int)$cartItems[$productId];
            }
            $validate = $this->validateQty($product, $productName, $qty);
            if ($validate != '') {
                $this->returnProductPage($product);
                throw new \Magento\Framework\Exception\LocalizedException(__($validate));
            }
        }
        return [$productInfo, $requestInfo];
    }

    /**
     * @param \Magento\Checkout\Model\Cart $subject
     * @param $data
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeUpdateItems(
        \Magento\Checkout\Model\Cart $subject,
        $data
    ) {
        $isQtyOptionEnable = $this->helper->isQtyOptionEnable();
        if ($isQtyOptionEnable == 1) {
            return [$data];
        }
        foreach ($data as $itemId => $qty) {
            $item = $subject->getQuote()->getItemById($itemId);
            $productType = $item->getProduct()->getTypeId();
            switch ($productType) {
                case "configurable":
                    $productSku = $item->getSku();
                    try {
                        $product = $this->productRepository->get($productSku);
                    } catch (\Exception $e) {
                        throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
                    }
                    break;
                default:
                    $productId = $item->getProductId();
                    try {
                        $product = $this->productRepository->getById($productId);
                    } catch (\Exception $e) {
                        throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
                    }
            }
            $validate = $this->validateQty($product, $product->getName(), $qty['qty']);
            if ($validate != '') {
                throw new \Magento\Framework\Exception\LocalizedException(__($validate));
            }
        }
        return [$data];
    }

    /**
     * @param $product
     * @param $productName
     * @param $qty
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function validateQty($product, $productName, $qty)
    {
        $message = '';
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
                $cus = $this->quantityDropdown->validCustom($cusBefore);
                if (!empty($cus) && !in_array($qty, $cus)) {
                    $custom = implode(',', $cus);
                    $message = __('%1 is only sold with quantity is %2. Please update your cart items!', $productName, $custom);
                }
            }
        }
        return $message;
    }

    /**
     * @param $product
     * @param $requestInfo
     * @return bool|int
     */
    protected function getRealId($product, $requestInfo)
    {
        $childProduct = $this->configurable
            ->getProductByAttributes($requestInfo['super_attribute'], $product);
        if ($childProduct) {
            return $childProduct->getId();
        }
        return false;
    }

    /**
     * @param $product
     */
    protected function returnProductPage($product)
    {
        $urlProduct = $product->getUrlModel()->getUrl($product);
        $this->checkoutSessionFactory->create()->setRedirectUrl($urlProduct);
    }

    /**
     * Get active quote
     *
     * @return mixed
     */
    protected function getQuote()
    {
        return $this->checkoutSessionFactory->create()->getQuote();
    }

    /**
     * @return array
     */
    protected function getItems()
    {
        $result = $this->getQuote()->getAllItems();
        $itemsParent = [];
        $cartItems = [];
        foreach ($result as $item) {
            $product = $item->getProduct();
            $productType = $product->getTypeId();
            $parentItem = $item->getParentItemId();
            $itemId = $item->getId();
            $qtyOrdered = $item->getQty();
            $productId = $item->getProductId();
            switch ($productType) {
                case "configurable":
                    $itemsParent[$itemId] = $qtyOrdered;
                    break;
                case "grouped":
                    break;
                case "bundle":
                    break;
                default:
                    if ($parentItem && $parentItem != '' && isset($itemsParent[$parentItem])) {
                        $qtyOrdered = $itemsParent[$parentItem];
                    }
                    $cartItems[$productId] = $qtyOrdered;
            }
        }
        return $cartItems;
    }
}
