<?php

namespace Datacom\LgkStore\Console\Command;

class SaveProductsCommand extends \Symfony\Component\Console\Command\Command {
    
    protected $_productCollectionFactory;
    protected $_productStatus;
    protected $_productRepository;
    protected $_state;
    protected $_stockFilter;
    protected $_stockItemRepository;

    protected $_objectManager;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\State $state,
        \Magento\CatalogInventory\Helper\Stock $stockFilter,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
	) {
        parent::__construct();
        
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_productStatus = $productStatus;
        $this->_productRepository = $productRepository;
        $this->_state = $state;
        $this->_stockFilter = $stockFilter;
        $this->_stockItemRepository = $stockItemRepository;
        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }
    
    protected function configure()
	{
        $options = array(
			new \Symfony\Component\Console\Input\InputOption(
				'id',
				null,
				\Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
				'Id'
			)
        );

		$this->setName('datacom:saveproductscommand')->setDescription('Salva i prodotti per correggere il problema della non disponibilità')->setDefinition($options);
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        $this->_state->setAreaCode('adminhtml');

        $firstId = $input->getOption('id');

        if (!$firstId) {
            $firstId = 0;
        }

        $products = $this->_productCollectionFactory->create()
            //->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()])
            /*->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)*/
            ->addAttributeToFilter('entity_id', ['gteq' => $firstId])
            ->setOrder('entity_id', 'ASC');

        //$this->_stockFilter->addInStockFilterToCollection($products);

        foreach ($products as $curProd) {
            /*if (!$this->_stockItemRepository->get($curProduct->getId())->getIsInStock()) {
                continue;
            }*/

            $curProduct = $this->_productRepository->get($curProd->getSku());

            $newDescr = $curProduct->getDescription();
            $newDescr = strip_tags($newDescr);
            $newDescr = trim($newDescr);

            if (strlen($newDescr) == 0) {
                $curProduct->setDescription('');
            }
            /*if ($curProduct->getStatus() != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                continue;
            }*/

            $newDescr = $curProduct->getShortDescription();
            $newDescr = strip_tags($newDescr);
            $newDescr = trim($newDescr);

            if (strlen($newDescr) == 0) {
                $curProduct->setShortDescription('');
            }

            $output->writeln($curProduct->getId().' - '.$curProduct->getSku());
            try {
                $this->_productRepository->save($curProduct);
            } catch (\Throwable $th) {
                $output->writeln($th->getMessage());
            }
        }
	}
}