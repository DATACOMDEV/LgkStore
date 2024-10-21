<?php

namespace Datacom\LgkStore\Console\Command;

class PopulateNewPriceListsCommand extends \Symfony\Component\Console\Command\Command {
    
    protected $_filesystem;
    protected $_driverFile;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $driverFile
	) {
        parent::__construct();
        
        $this->_state = $state;
        $this->_filesystem = $filesystem;
        $this->_driverFile = $driverFile;
    }
    
    protected function configure()
	{
        $this->setName('datacom:populatenewpriceListscommand')->setDescription('Elabora i file di aggiornamento listini caricati da backoffice');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
	{
        $this->_state->setAreaCode('adminhtml');

        $file = dirname(__FILE__).'/PopulateNewPriceListsCommand.lock';

        if (file_exists($file)) return;

        touch($file);

        $err = null;

        try {
            $this->_execute($input, $output);
        } catch (\Exception $ex) {
            $err = $ex;
        }

        if (file_exists($file)) {
            unlink($file);
        }

        if (!is_null($err)) throw $err;
	}

    private function _execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
        $varDirectory = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $workingPath = $varDirectory->getAbsolutePath('import/pricelists');
        $completedPath = $varDirectory->getAbsolutePath('import/pricelists/completed');

        $files = $this->_driverFile->readDirectory($workingPath);
        foreach ($files as $file) {
            $filename = basename($file);
            $newFile = sprintf('%s.lock', $file);
            $completedFile = sprintf('%s/%s', $completedPath, $filename);
            rename($file, $newFile);
            try {
                $this->manageFile($newFile, $completedFile);
            } catch (\Exception $ex) {
                rename($newFile, $file);
                throw $ex;
            }
        }
    }

    private function manageFile($file, $completedFile) {
        //TODO: riportare la logica di agg.to prodotti dal controller import
    }
}