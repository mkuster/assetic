<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Asset;

use Assetic\Asset\Iterator\AssetCollectionFilterIterator;
use Assetic\Asset\Iterator\AssetCollectionIterator;
use Assetic\Filter\FilterCollection;
use Assetic\Filter\FilterInterface;

/**
 * A collection of assets.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetCollection implements \IteratorAggregate, AssetCollectionInterface
{
    private $assets;
    private $filters;
    private $sourceRoot;
    private $targetPath;
    private $content;
    private $clones;

    /**
     * Constructor.
     *
     * @param array  $assets     Assets for the current collection
     * @param array  $filters    Filters for the current collection
     * @param string $sourceRoot The root directory
     */
    public function __construct($assets = array(), $filters = array(), $sourceRoot = null)
    {
        $this->assets = array();
        foreach ($assets as $asset) {
            $this->add($asset);
        }

        $this->filters = new FilterCollection($filters);
        $this->sourceRoot = $sourceRoot;
        $this->clones = new \SplObjectStorage();
    }

    /**
     * Adds an asset to the current collection.
     *
     * @param AssetInterface $asset An asset
     */
    public function add(AssetInterface $asset)
    {
        $this->assets[] = $asset;
    }

    public function all()
    {
        return $this->assets;
    }

    public function ensureFilter(FilterInterface $filter)
    {
        $this->filters->ensure($filter);
    }

    public function getFilters()
    {
        return $this->filters->all();
    }

    public function clearFilters()
    {
        $this->filters->clear();
    }

    public function load(FilterInterface $additionalFilter = null)
    {
        // loop through leaves and load each asset
        $parts = array();
        foreach ($this as $asset) {
            $asset->load($additionalFilter);
            $parts[] = $asset->getContent();
        }

        $this->content = implode("\n", $parts);
    }

    public function dump(FilterInterface $additionalFilter = null)
    {
        // loop through leaves and dump each asset
        $parts = array();
        foreach ($this as $asset) {
            $parts[] = $asset->dump($additionalFilter);
        }

        return implode("\n", $parts);
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getSourceRoot()
    {
        return $this->sourceRoot;
    }

    public function getSourcePath()
    {
    }

    public function getTargetPath()
    {
        return $this->targetPath;
    }

    public function setTargetPath($targetPath)
    {
        $this->targetPath = $targetPath;
    }

    /**
     * Returns the highest last-modified value of all assets in the current collection.
     *
     * @return integer|null A UNIX timestamp
     */
    public function getLastModified()
    {
        if (!count($this->assets)) {
            return;
        }

        $mapper = function (AssetInterface $asset)
        {
            return $asset->getLastModified();
        };

        return max(array_map($mapper, $this->assets));
    }

    /**
     * Returns an iterator for looping recursively over unique leaves.
     */
    public function getIterator()
    {
        return new \RecursiveIteratorIterator(new AssetCollectionFilterIterator(new AssetCollectionIterator($this, $this->clones)));
    }
}
