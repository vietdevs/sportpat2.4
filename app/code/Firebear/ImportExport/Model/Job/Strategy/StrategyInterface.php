<?php
/**
 * @copyright: Copyright © 2021 Firebear Studio. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Job\Strategy;

use Firebear\ImportExport\Api\Data\ImportInterface;

/**
 * @api
 */
interface StrategyInterface extends \Iterator
{
    /**
     * Set job
     *
     * @param ImportInterface $job
     * @return $this
     */
    public function setJob(ImportInterface $job);

    /**
     * Checks if strategy is available
     *
     * @return bool
     */
    public function isAvailable();

    /**
     * Return the current element
     *
     * @return ImportInterface
     */
    public function current();

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next();

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key();

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid();

    /**
     * Rewind the \Iterator to the first element
     *
     * @return void
     */
    public function rewind();

    /**
     * @param bool $result
     * @return void
     */
    public function setLastResult(bool $result);
}
