<?php

namespace Datacom\LgkStore\Console\Command;

class PopulateProductPricesCommand extends \Symfony\Component\Console\Command\Command {
    
    protected $_productCollectionFactory;
    protected $_taxCalculation;
    protected $_scopeConfigInterface;
    protected $_productRepository;
    protected $_taxHelper;
    protected $_productAction;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Tax\Api\TaxCalculationInterface $taxCalculation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Helper\Data $taxHelper,
        \Magento\Catalog\Model\Product\Action $productAction,
        \Magento\Framework\App\State $state
	) {
        parent::__construct();
        
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_taxCalculation = $taxCalculation;
        $this->_scopeConfigInterface = $scopeConfigInterface;
        $this->_productRepository = $productRepository;
        $this->_taxHelper = $taxHelper;
        $this->_productAction = $productAction;
        $this->_state = $state;
    }
    
    protected function configure()
	{
        $this->setName('datacom:populateproductpricescommand')->setDescription('Valorizza i campi con il prezzo iva inclusa');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        $this->_state->setAreaCode('adminhtml');

        $priceInStoreIncludeTax = $this->_scopeConfigInterface->getValue(\Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX);

        $products = $this->_productCollectionFactory->create()
            ->addAttributeToSelect('*');
        foreach ($products as $curProd) {
            //$curProd = $this->_productRepository->getById($curProd->getId());
            //$output->writeln($curProd->getSku());
            $price = $curProd->getPrice();
            /*$taxPercentage = $this->_taxCalculation->getCalculatedRate($curProd->getTaxClassId());

            $priceWithTax = 0;
            if ($priceInStoreIncludeTax) {
                $priceWithTax = ($price / ((100 + $taxPercentage)/100));
            } else {
                $priceWithTax = ($price * ((100 + $taxPercentage)/100));
            }*/

            $priceWithTax = $this->_taxHelper->getTaxPrice($curProd, $curProd->getFinalPrice(), true);
            $priceWithTax = number_format($priceWithTax, 6, '.', '');

            $output->writeln($curProd->getSku(). ' prezzo: '.$price.' - iva inclusa: '.$priceWithTax);

            if ($curProd->getData('prezzo_iva_inclusa') != $priceWithTax) {
                $this->_productAction->updateAttributes([$curProd->getId()], ['prezzo_iva_inclusa' => $priceWithTax], 0);
                $output->writeln('+ Aggiornato');
            } else {
                $output->writeln('+ Invariato');
            }
        }
	}
}