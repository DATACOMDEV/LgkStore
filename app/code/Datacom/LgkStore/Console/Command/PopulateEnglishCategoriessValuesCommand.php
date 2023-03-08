<?php

namespace Datacom\LgkStore\Console\Command;

class PopulateEnglishCategoriessValuesCommand extends \Symfony\Component\Console\Command\Command {
    
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
    }
    
    protected function configure()
	{
		$this->setName('datacom:populateenglishcategoriessvalues')->setDescription('Popola i valori in inglese delle categorie partendo dai csv');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        $mapping = array();
        $rows = file(dirname(__FILE__).'/lgkstore.categories.csv', FILE_IGNORE_NEW_LINES);

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
            $curCat = $this->_categoryRepository->get($curCat->getId(), 1);

            if (array_key_exists($curCat->getName(), $mapping)) {
                if ($curCat->getUrlKey() != $mapping[$curCat->getName()]) {
                    $output->writeln('VECCHIO: "'.$curCat->getUrlKey().'"');
                    $curCat->setUrlKey($mapping[$curCat->getName()]);
                    $output->writeln('NUOVO: "'.$curCat->getUrlKey().'"');
                    
                    try {
                        //$this->_categoryRepository->save($curCat);
                        $output->writeln('INSERT INTO catalog_category_entity_varchar (attribute_id, store_id, entity_id, value) VALUES (120, 1, '.$curCat->getId().', "'.$mapping[$curCat->getName()].'");');
                    } catch (\Throwable $th) {
                        $output->writeln($th->getMessage());
                        continue;
                    }
                }
            }
        }
	}
}