<?php

namespace Datacom\LgkStore\Block\BackupListing;

class Index extends \Magento\Backend\Block\Template {
    
    protected $_filesystem;
    protected $_driverFile;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $driverFile,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->_filesystem = $filesystem;
        $this->_driverFile = $driverFile;
    }

    private function getAvailableCompletedImports() {
        $varDirectory = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $completedPath = $varDirectory->getAbsolutePath('import/pricelists/completed');

        $files = $this->_driverFile->readDirectory($completedPath);
        return $files;
    }

    public function getAvailableTimes($requestedDate) {
        $retval = [];
        $files = $this->getAvailableCompletedImports();
        foreach ($files as $f) {
            $filename = basename($f);
            $date = substr($filename, 0, 8);
            if ($date != $requestedDate) continue;
            $time = substr($filename, 8, 6);
            $file = substr($filename, 14);
            $file = explode('.', $file)[0];
            $file = trim($file);
            $file = trim($file, '_');
            $file = str_replace('_', ' ', $file);
            if (!array_key_exists($time, $retval)) {
                $retval[$time] = [];
            }
            $retval[$time][] = $file;
        }

        return $retval;
    }

    public function getAvailableDates() {
        $retval = [];
        $files = $this->getAvailableCompletedImports();
        foreach ($files as $f) {
            $filename = basename($f);
            $date = substr($filename, 0, 8);
            if (array_key_exists($date, $retval)) continue;
            $retval[$date] = 0;
            /*$time = substr($filename, 8, 6);
            if (!array_key_exists($date, $retval)) {
                $retval[$date] = [];
            }
            $retval[$date][] = $time;*/
        }

        $retval = array_keys($retval);
        return $retval;
    }
}