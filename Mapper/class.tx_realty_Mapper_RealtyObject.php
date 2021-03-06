<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Saskia Metzler <saskia@merlin.owl.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This class represents a mapper for realty objects.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_RealtyObject extends tx_oelib_DataMapper {
	/**
	 * @var string the name of the database table for this mapper
	 */
	protected $tableName = 'tx_realty_objects';

	/**
	 * @var string the model class name for this mapper, must not be empty
	 */
	protected $modelClassName = 'tx_realty_Model_RealtyObject';

	/**
	 * the (possible) relations of the created models in the format
	 * DB column name => mapper name
	 *
	 * @var array
	 */
	protected $relations = array();

	/**
	 * cache by object number, OpenImmo object ID and language, using values
	 * from createCacheKeyFromObjectNumberAndObjectIdAndLanguage as keys
	 *
	 * @var tx_realty_Model_RealtyObject[]
	 */
	private $cacheByObjectNumberAndObjectIdAndLanguage = array();

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		$this->cacheByObjectNumberAndObjectIdAndLanguage = array();

		parent::__destruct();
	}

	/**
	 * Returns the number of realty objects in the city $city.
	 *
	 * @param tx_realty_Model_City $city the city for which to count the objects
	 *
	 * @return integer the number of objects in the given city, will be >= 0
	 */
	public function countByCity(tx_realty_Model_City $city) {
		return tx_oelib_db::count(
			$this->tableName,
			'(city = ' . $city->getUid() . ') AND ' .
				$this->getUniversalWhereClause()
		);
	}

	/**
	 * Returns the number of realty objects in the district $district.
	 *
	 * @param tx_realty_Model_District $district
	 *        the district for which to count the objects
	 *
	 * @return integer the number of objects in the given district, will be >= 0
	 */
	public function countByDistrict(tx_realty_Model_District $district) {
		return tx_oelib_db::count(
			$this->tableName,
			'(district = ' . $district->getUid() . ') AND ' .
				$this->getUniversalWhereClause()
		);
	}

	/**
	 * Finds a realty object by its object number, OpenImmo object ID and
	 * language.
	 *
	 * @throws tx_oelib_Exception_NotFound
	 *         if there is no realty object with the provided data
	 *
	 * @param string $objectNumber
	 *        the object number of the object to find, may be empty
	 * @param string $openImmoObjectId
	 *        the OpenImmo Object ID of the object to find, must not be empty
	 * @param string $language
	 *        the language code (any format) of the object to find, may be empty
	 *
	 * @return tx_realty_Model_RealtyObject
	 *         the realty object that matches all three parameters
	 */
	public function findByObjectNumberAndObjectIdAndLanguage(
		$objectNumber, $openImmoObjectId, $language = ''
	) {
		try {
			$model = $this->findByObjectNumberAndObjectIdAndLanguageFromCache(
				$objectNumber, $openImmoObjectId, $language
			);
		} catch (tx_oelib_Exception_NotFound $exception) {
			$model = $this->findByObjectNumberAndObjectIdAndLanguageFromDatabase(
				$objectNumber, $openImmoObjectId, $language
			);
		}

		return $model;
	}

	/**
	 * Finds a realty object by its object number, OpenImmo object ID and
	 * language from the cache.
	 *
	 * @throws tx_oelib_Exception_NotFound
	 *         if there is no realty object with the provided data in the cache
	 *
	 * @param string $objectNumber
	 *        the object number of the object to find, may be empty
	 * @param string $openImmoObjectId
	 *        the OpenImmo Object ID of the object to find, must not be empty
	 * @param string $language
	 *        the language code (any format) of the object to find, may be empty
	 *
	 * @return tx_realty_Model_RealtyObject
	 *         the realty object that matches all three parameters
	 */
	private function findByObjectNumberAndObjectIdAndLanguageFromCache(
		$objectNumber, $openImmoObjectId, $language
	) {
		$cacheKey = $this->createCacheKeyFromObjectNumberAndObjectIdAndLanguage(
			$objectNumber, $openImmoObjectId, $language
		);
		if (!isset($this->cacheByObjectNumberAndObjectIdAndLanguage[$cacheKey])) {
			throw new tx_oelib_Exception_NotFound('No model found.', 1333035741);
		}

		return $this->cacheByObjectNumberAndObjectIdAndLanguage[$cacheKey];
	}

	/**
	 * Creates a unique cache key for an object number, an OpenImmo object ID
	 * and a language code.
	 *
	 * @param string $objectNumber
	 *        an object number, may be empty
	 * @param string $openImmoObjectId
	 *        an OpenImmo Object ID, may be empty
	 * @param string $language
	 *        a language code (any format), may be empty
	 *
	 * @return string
	 *         a cache key, will be unique for the provided triplet, will not be
	 *         empty
	 */
	private function createCacheKeyFromObjectNumberAndObjectIdAndLanguage(
		$objectNumber, $openImmoObjectId, $language
	) {
		return $objectNumber . ':' . $openImmoObjectId . ':' . $language;
	}

	/**
	 * Caches a model by additional combined keys.
	 *
	 * @param tx_oelib_Model $model the model to cache
	 * @param array $data the data of the model as it is in the DB, must not be empty
	 *
	 * @return void
	 */
	protected function cacheModelByCombinedKeys(
		tx_oelib_Model $model, array $data
	) {
		$objectNumber = isset($data['object_number'])
			? $data['object_number'] : '';
		$openImmoObjectId = isset($data['openimmo_obid'])
			? $data['openimmo_obid'] : '';
		$language = isset($data['language']) ? $data['language'] : '';

		$cacheKey = $this->createCacheKeyFromObjectNumberAndObjectIdAndLanguage(
			$objectNumber, $openImmoObjectId, $language
		);
		$this->cacheByObjectNumberAndObjectIdAndLanguage[$cacheKey] = $model;
	}

	/**
	 * Finds a realty object by its object number, OpenImmo object ID and
	 * language from the database.
	 *
	 * @throws tx_oelib_Exception_NotFound
	 *         if there is no realty object with the provided data in the
	 *         database
	 *
	 * @param string $objectNumber
	 *        the object number of the object to find, may be empty
	 * @param string $openImmoObjectId
	 *        the OpenImmo Object ID of the object to find, must not be empty
	 * @param string $language
	 *        the language code (any format) of the object to find, may be empty
	 *
	 * @return tx_realty_Model_RealtyObject
	 *         the realty object that matches all three parameters
	 */
	private function findByObjectNumberAndObjectIdAndLanguageFromDatabase(
		$objectNumber, $openImmoObjectId, $language
	) {
		return $this->findSingleByWhereClause(array(
			'object_number' => $objectNumber,
			'openimmo_obid' => $openImmoObjectId,
			'language' => $language
		));
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_RealtyObject.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_RealtyObject.php']);
}