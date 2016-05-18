<?php
namespace AOE\Newrelic\Cache\Frontend;

use AOE\Newrelic\Service;
use TYPO3\CMS\Core\Cache\Frontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class VariableFrontend extends \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend {

	/**
	 * Saves the value of a PHP variable in the cache. Note that the variable
	 * will be serialized if necessary.
	 *
	 * @param string $entryIdentifier An identifier used for this cache entry
	 * @param mixed $variable The variable to cache
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws \InvalidArgumentException if the identifier or tag is not valid
	 * @api
	 */
	public function set($entryIdentifier, $variable, array $tags = array(), $lifetime = NULL) {
		parent::set($entryIdentifier, $variable, $tags, $lifetime);
		if ($this->getIdentifier() == 'cache_pages') {
			/** @var Service $service */
			$service = GeneralUtility::makeInstance('AOE\Newrelic\Service');
			$service->setCacheInfo(Service::CACHE_INFO_UNCACHED);
		}
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @throws \InvalidArgumentException If $entryIdentifier is invalid
	 * @api
	 */
	public function has($entryIdentifier) {
		$hasEntry = parent::has($entryIdentifier);
		if (!$hasEntry && $this->getIdentifier() == 'cache_pages') {
			/** @var Service $service */
			$service = GeneralUtility::makeInstance('AOE\Newrelic\Service');
			$service->setCacheInfo(Service::CACHE_INFO_UNCACHED);
		}
		return $hasEntry;
	}

	/**
	 * Finds and returns a variable value from the cache.
	 *
	 * @param string $entryIdentifier Identifier of the cache entry to fetch
	 * @return mixed The value
	 * @throws \InvalidArgumentException if the identifier is not valid
	 * @api
	 */
	public function get($entryIdentifier) {
		$rawResult = parent::get($entryIdentifier);
		if (!$rawResult && $this->getIdentifier() == 'cache_pages') {
			/** @var Service $service */
			$service = GeneralUtility::makeInstance('AOE\Newrelic\Service');
			$service->setCacheInfo(Service::CACHE_INFO_UNCACHED);
		} else {
			/** @var Service $service */
			$service = GeneralUtility::makeInstance('AOE\Newrelic\Service');
			$service->setCacheInfo(Service::CACHE_INFO_CACHED);
		}
		return $rawResult;
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with the content of all matching entries. An empty array if no entries matched
	 * @throws \InvalidArgumentException if the tag is not valid
	 * @api
	 */
	public function getByTag($tag) {
		$entries = parent::getByTag($tag);
		if (!$entries && $this->getIdentifier() == 'cache_pages') {
			/** @var Service $service */
			$service = GeneralUtility::makeInstance('AOE\Newrelic\Service');
			$service->setCacheInfo(Service::CACHE_INFO_UNCACHED);
		}
		return $entries;
	}

}

?>