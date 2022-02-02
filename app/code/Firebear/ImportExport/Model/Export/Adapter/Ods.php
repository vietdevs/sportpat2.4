<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Export\Adapter;

use Box\Spout\Writer\WriterInterface;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Ods Export Adapter
 */
class Ods extends AbstractAdapter
{
    /**
     * Spreadsheet Writer
     *
     * @var WriterInterface
     */
    private $writer;

    /**
     * File Path
     *
     * @var string|bool
     */
    private $filePath;

    /**
     * Ods constructor
     *
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param null $destination
     * @param string $destinationDirectoryCode
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger,
        $destination = null,
        $destinationDirectoryCode = DirectoryList::VAR_DIR,
        array $data = []
    ) {
        if (empty($data['export_source']['file_path'])) {
            throw new LocalizedException(__('Export File Path is Empty.'));
        }

        parent::__construct(
            $filesystem,
            $logger,
            $destination,
            $destinationDirectoryCode,
            $data
        );
    }

    /**
     * Write row data to source file
     *
     * @param array $rowData
     * @return AbstractAdapter
     * @throws LocalizedException
     */
    public function writeRow(array $rowData)
    {
        if (null === $this->_headerCols) {
            $this->setHeaderCols(array_keys($rowData));
        }

        $this->addRow(
            array_merge(
                $this->_headerCols,
                array_intersect_key($rowData, $this->_headerCols)
            )
        );
        return $this;
    }

    /**
     * Prepare Row Data
     *
     * @param array $rowData
     * @return array $rowData
     */
    private function prepareRow(array $rowData)
    {
        $rowData = array_map(function ($value) {
            return (string)$value;
        }, $rowData);

        return WriterEntityFactory::createRowFromArray($rowData);
    }

    /**
     * Add Row Data
     *
     * @param array $rowData
     * @return void
     */
    private function addRow(array $rowData)
    {
        $this->writer->addRow(
            $this->prepareRow($rowData)
        );
    }

    /**
     * Get export file name
     *
     * @return string
     */
    private function getFile()
    {
        return $this->_directoryHandle->getAbsolutePath(
            $this->_destination
        );
    }

    /**
     * Set column names
     *
     * @param array $headerColumns
     * @return AbstractAdapter
     * @throws LocalizedException
     */
    public function setHeaderCols(array $headerColumns)
    {
        if (null !== $this->_headerCols) {
            throw new LocalizedException(__('The header column names are already set.'));
        }
        if ($headerColumns) {
            foreach ($headerColumns as $columnName) {
                $this->_headerCols[$columnName] = false;
            }
            $this->addRow(array_keys($this->_headerCols));
        }
        return $this;
    }

    /**
     * Get contents of export file
     *
     * @return string
     */
    public function getContents()
    {
        $this->writer->close();
        return parent::getContents();
    }

    /**
     * MIME-type for 'Content-Type' header
     *
     * @return string
     */
    public function getContentType()
    {
        return 'application/vnd.oasis.opendocument.spreadsheet';
    }

    /**
     * Return file extension for downloading
     *
     * @return string
     */
    public function getFileExtension()
    {
        return 'ods';
    }

    /**
     * Method called as last step of object instance creation
     *
     * @return AbstractAdapter
     */
    protected function _init()
    {
        $this->writer = WriterEntityFactory::createODSWriter();
        $this->writer->openToFile($this->getFile());

        return $this;
    }
}
