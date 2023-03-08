<?php

namespace Datacom\LgkStore\Console\Command;

class PopulateItalianCategoriessValuesCommand extends \Symfony\Component\Console\Command\Command {
    
    protected $_categoryCollectionFactory;
    protected $_categoryRepository;

    protected $_objectManager;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository
	) {
        parent::__construct();
        
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_categoryRepository = $categoryRepository;
        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_productCollectionFactory = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
    }
    
    protected function configure()
	{
		$this->setName('datacom:populateitaliancategoriessvalues')->setDescription('Popola i valori in italiano delle categorie partendo dai csv');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        $categories = $this->_categoryCollectionFactory->create()
            ->addIsActiveFilter();

        $prodIds = [];
        foreach ($categories as $curCat) {
            $curCat = $this->_categoryRepository->get($curCat->getId(), 0);
            $subCats = $curCat->getChildrenCategories();

            if ($subCats->getSize()) {
                $collection = $this->_productCollectionFactory->create();
                $collection->addAttributeToSelect('*');
                $collection->addCategoriesFilter(['in' => [$curCat->getId()]]);
                
                foreach ($collection as $prod) {
                    if (!in_array($prod->getId(), $prodIds)) {
                        $prodIds[] = $prod->getSku();
                    }
                }
            }
        }
        
        foreach ($prodIds as $id) {
            $output->writeln($id);
        }
        /*$mapping = array();
        $rows = file(dirname(__FILE__).'/lgkstore.categories.it.csv', FILE_IGNORE_NEW_LINES);

        foreach ($rows as $row) {
            $data = explode('","', $row);
            $data = array_map(function($item) {
                $retval = trim($item, '"');
                $retval = trim($retval);

                return $retval;
            }, $data);

            if (empty($data[0]) || count($data) < 2) {
                continue;
            }

            $mapping[$data[0]] = $data[1];
        }

        $categories = $this->_categoryCollectionFactory->create()
            ->addIsActiveFilter();

        foreach ($categories as $curCat) {
            $curCat = $this->_categoryRepository->get($curCat->getId(), 0);

            if (array_key_exists($curCat->getName(), $mapping)) {
                if ($curCat->getUrlKey() != $mapping[$curCat->getName()]) {
                    $output->writeln('VECCHIO: "'.$curCat->getUrlKey().'"');
                    $curCat->setUrlKey($mapping[$curCat->getName()]);
                    $output->writeln('NUOVO: "'.$curCat->getUrlKey().'"');
                    
                    try {
                        $this->_categoryRepository->save($curCat);
                    } catch (\Throwable $th) {
                        $output->writeln($th->getMessage());
                        continue;
                    }
                }
            }
        }*/
	}
}