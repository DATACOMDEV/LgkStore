<?php

namespace Datacom\LgkStore\Console\Command;

class PopulateVerticalMegaMenuCommand extends \Symfony\Component\Console\Command\Command {
    
    protected $_menuGroup;
    protected $_categoryCollectionFactory;
    protected $_categoryRepository;
    protected $_objectManager;

    protected $_lastError;

    public function __construct(
        \Sm\MegaMenu\Model\MenuGroup $menuGroup,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository
	) {
		parent::__construct();
        
        $this->_menuGroup = $menuGroup;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_categoryRepository = $categoryRepository;
        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }
    
    protected function configure()
	{
		$this->setName('datacom:populateverticalmegamenu')->setDescription('Popola il mega menu verticale per il sito lgkstore.it');
    }

    public function createMenuItems(){
		return $this->_objectManager->create('Sm\MegaMenu\Model\MenuItems');
	}
    
    /*public function setDepth($model){
		try {
			if($model->getOrderItem()){
				$itemId = $model->getOrderItem();
				$menuItems = $this->createMenuItems();
				$modelMenuitems = $menuItems->load($itemId);
				$data = $modelMenuitems->getData();
				if($model->getPositionItem() == \Sm\MegaMenu\Model\Config\Source\PositionItem::AFTER){		//after item:
					$depth =  intval($data['depth']);
				}
				elseif($model->getPositionItem() == \Sm\MegaMenu\Model\Config\Source\PositionItem::BEFORE){		//before item:
					$depth =  intval($data['depth']);
				}
			}
			else{
				$itemId = $model->getParentId();
				$menuItems = $this->createMenuItems();
				$modelMenuitems = $menuItems->load($itemId);
				$data = $modelMenuitems->getData();
				$depth =  intval($data['depth'])+1;
			}
			$model->setData('depth', $depth);
			return true;
		} catch (\Exception $e) {
            $this->_lastError = $e->getMessage();
			return false;
		}
    }*/

    public function createMenuItemsCollection(){
		return $this->_objectManager->create('Sm\MegaMenu\Model\ResourceModel\MenuItems\Collection');
	}

    public function setPrioritiesItems($model){
		$menuItems = $this->createMenuItems();
		$menuItemsOrderOld = $this->createMenuItems();
		$menuItemsCollection = $this->createMenuItemsCollection();

		$id = $model->getItemsId();
		$parentId = $model->getParentId();
		$orderItem = $model->getOrderItem();
		$positionItem = $model->getPositionItem();
		$groupId = $model->getGroupId();

		$itemsOld = $menuItemsOrderOld->load($orderItem);
		$parentIdOld = $itemsOld->getParentId();

		$prioritiesOrder = $menuItemsCollection->getPrioritiesParent($orderItem, $groupId);
		$allItems = $menuItemsCollection->getAllItemsMinusItemsId($id, $parentId, $groupId);
		$allItemsOrderOld = $menuItemsCollection->getAllItemsMinusItemsId($orderItem, $parentIdOld, $groupId);
		if ($positionItem == 1)
		{
			$menuItems->setPrioritiesByNewItems($id , $allItems, $groupId, $prioritiesOrder);
			$menuItems->setPrioritiesByNewItems($orderItem , $allItemsOrderOld, $groupId, $prioritiesOrder+1);
		}
		else
		{
			$menuItems->setPrioritiesByNewItems($id , $allItems, $groupId, $prioritiesOrder+1);
		}
    }
    
    public function getFilterData($data, $typeFilter){
		$new = '';
		if($typeFilter == 'text'){
			$new = strip_tags(trim($data));
		}
		return $new;
	}
    
    protected function deleteNodeChildren($node) {
        $childrenNodes = $this->createMenuItems()->getChildsDirectlyByItem(array(
            'items_id' => $node->getId(),
            'depth' => $node->getDepth(),
            'group_id' => $node->getGroupId()
        ));

        $this->createMenuItems()->deleteItemsChildByItemsId($childrenNodes, $node->getGroupId());
    }

    protected function addNodeToNode($storeId, $parentNode, $categories, $depth) {
        $lastItemId = $parentNode->getId();
        foreach ($categories as $cat) {
            $cat = $this->_categoryRepository->get($cat->getId(), $storeId);
            
            $model = $this->createMenuItems();

            $data = array(
                'title' => $this->getFilterData($cat->getName(), 'text'),
                'description' => null,
                'group_id' => $parentNode->getGroupId(),
                'status' => 1,
                'show_title' => $depth < 3 ? 1 : 2,
                'align' => 1,
                'parent_id' => $parentNode->getId(),
                'order_item' => $lastItemId,
                'position_item' => 1,
                'cols_nb' => 6,
                'target' => 3,
                'type' => 4,
                'data_type' => 'category/'.$cat->getId(),
                'show_title_category' => 2,
                'show_sub_category' => 2,
                'depth' => $depth
            );

            $model->setData($data);
            $model->setDepth($depth);
            $model->setData('depth', $depth);
            
            $model->save();

            if($model->getItemsId())
            {
                $this->setPrioritiesItems($model);
            }
            
            $lastItemId = $model->getItemsId();

            $childrenIds = $this->getCategoryChildrenIds($cat);
            
            if (count($childrenIds) > 0) {
                $children = $this->_categoryCollectionFactory->create()
                    ->addAttributeToFilter('entity_id', array('in' => $childrenIds))
                    ->addOrderField('name');

                $this->addNodeToNode($storeId, $model, $children, $depth + 1);
            }
        }
    }

    protected function getCategoryChildrenIds($category) {
        $rootChildrenIds = $category->getChildren();
        $rootChildrenIds = explode(',', $rootChildrenIds);

        return empty($rootChildrenIds) ? array() : $rootChildrenIds;
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        $storeIds = array(
            20 => array(    //Italiano
                'mega_menu_id' => 1,
                'root_item_id' => 1
            ),
            1 => array(    //Inglese
                'mega_menu_id' => 7,
                'root_item_id' => 701
            )
        );
        /*$storeIds = array(
            20 => 1,  //Italiano
            1 => 2,  //Inglese
            2 => /   //Francese
        );*/

        $root = $this->_categoryRepository->get(2, 0);
        
        $rootChildrenIds = $this->getCategoryChildrenIds($root);

        foreach ($rootChildrenIds as $childCatId) {
            $root = $this->_categoryRepository->get($childCatId, 0);
        }

        $categories = $this->_categoryCollectionFactory->create()
            ->addIsActiveFilter()
            ->addLevelFilter(3)
            ->addAttributeToFilter('entity_id', array('nin' => array(2, $root->getId())))
            ->addOrderField('name');

        foreach ($storeIds as $storeId => $megaMenuData) {
            $rootNode = $this->createMenuItems()->load($megaMenuData['root_item_id']);
            
            $this->deleteNodeChildren($rootNode);

            $this->addNodeToNode($storeId, $rootNode, $categories, 1);
        }
	}
}