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

class DropdownRenderPlugin
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
     * @var \Bss\QuantityDropdown\Helper\CheckHidePrice
     */
    protected $hidePriceHelper;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * CategoryPlugin constructor.
     * @param \Bss\QuantityDropdown\Block\QuantityDropdown $dropdown
     * @param \Bss\QuantityDropdown\Helper\Data $helper
     */
    public function __construct(
        \Bss\QuantityDropdown\Block\QuantityDropdown $dropdown,
        \Bss\QuantityDropdown\Helper\Data $helper,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->dropdown = $dropdown;
        $this->helper = $helper;
        $this->request = $request;
    }

    /**
     * @param \Magento\Framework\Pricing\Render $subject
     * @param \Closure $proceed
     * @param $priceCode
     * @param $saleableItem
     * @param array $arguments
     * @return mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundRender(
        \Magento\Framework\Pricing\Render $subject,
        \Closure $proceed,
        $priceCode,
        $saleableItem,
        array $arguments = []
    ) {
        $result = $proceed($priceCode, $saleableItem, $arguments);
        $enable = $this->helper->isEnable();
        $display = $this->helper->isDisplay();
        $handle = $this->request->getFullActionName();
        $handleList = ['cartquickpro_catalog_product_options', 'catalog_product_view', 'checkout_cart_configure'];
        if ($enable == 1) {
            if (in_array($handle, $handleList) || $display) {
                $product = $saleableItem;
                $hidePrice = $this->helper->checkHidePrice($product);
                $callHidePrice = $product->getActiveCallHidePrice();
                if (!$callHidePrice && !$hidePrice) {
                    $insertHtml = $this->dropdown->getHtml($product, false);
                    $result = $result . $insertHtml;
                }
            }
        }
        return $result;
    }
}
