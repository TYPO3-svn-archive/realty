<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2013 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * This class provides a form to enter filter criteria for the realty list in the realty plugin.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_filterForm extends tx_realty_pi1_FrontEndView {
	/**
	 * @var array Filter form data array with the the fields for which a filter
	 *            is applicable. "priceRange" keeps a string of the format
	 *            "number-number" and "site" has any string, directly
	 *            derived from the form data. Fields initialized with 0 refer to
	 *            integer values and fields initialized with '' to strings.
	 */
	private $filterFormData = array(
		'uid' => 0, 'objectNumber' => '', 'site' => '', 'city' => 0,
		'district' => 0, 'houseType' => 0, 'priceRange' => '', 'rentFrom' => 0,
		'rentTo' => 0, 'livingAreaFrom' => 0, 'livingAreaTo' => 0,
		'objectType' => '', 'numberOfRoomsFrom' => 0, 'numberOfRoomsTo' => 0,
	);

	/**
	 * @var array the search fields which should be displayed in the search form
	 */
	private $displayedSearchFields = array();

	/**
	 * Returns the filter form in HTML.
	 *
	 * @param array $filterFormData
	 *        current piVars, the elements "priceRange" and "site" will be used if they are available, may be empty
	 *
	 * @return string HTML of the filter form, will not be empty
	 */
	public function render(array $filterFormData = array()) {
		$this->extractValidFilterFormData($filterFormData);
		$this->displayedSearchFields = t3lib_div::trimExplode(
			',',
			$this->getConfValueString(
				'displayedSearchWidgetFields', 's_searchForm'),
			TRUE
		);

		$this->includeJavaScript();

		$this->setTargetUrlMarker();
		$this->fillOrHideUidSearch();
		$this->fillOrHideObjectNumberSearch();
		$this->fillOrHideSiteSearch();
		$this->fillOrHideCitySearch();
		$this->fillOrHideDistrictSearch();
		$this->fillOrHideHouseTypeSearch();
		$this->fillOrHidePriceRangeDropDown();
		$this->fillOrHideFromToSearchField('rent', 'rent');
		$this->fillOrHideFromToSearchField('livingArea', 'living_area');
		$this->fillOrHideFromToSearchField('numberOfRooms', 'number_of_rooms');
		$this->fillOrHideObjectTypeSelect();

		return $this->getSubpart('FILTER_FORM');
	}

	/**
	 * Includes the extension's main JavaScript and Prototype in the page header
	 * if this is needed.
	 *
	 * @return void
	 */
	private function includeJavaScript() {
		if ($this->hasSearchField('city') && $this->hasSearchField('district')) {
			tx_realty_lightboxIncluder::includePrototype();
			tx_realty_lightboxIncluder::includeMainJavaScript();
		}
	}

	/**
	 * Returns a WHERE clause part derived from the provided form data.
	 *
	 * The table on which this WHERE clause part can be applied must be
	 * "tx_realty_objects INNER JOIN tx_realty_cities
	 * ON tx_realty_objects.city = tx_realty_cities.uid";
	 *
	 * @param array $filterFormData filter form data, may be empty
	 *
	 * @return string WHERE clause part for the current filters beginning
	 *                with " AND", will be empty if none were provided
	 */
	public function getWhereClausePart(array $filterFormData) {
		$this->extractValidFilterFormData($filterFormData);

		return $this->getUidWhereClausePart() .
			$this->getObjectNumberWhereClausePart() .
			$this->getSiteWhereClausePart() .
			$this->getCityWhereClausePart() .
			$this->getDistrictWhereClausePart() .
			$this->getHouseTypeWhereClausePart() .
			$this->getRentOrPriceRangeWhereClausePart() .
			$this->getLivingAreaWhereClausePart() .
			$this->getObjectTypeWhereClausePart() .
			$this->getNumberOfRoomsWhereClausePart();
	}

	/**
	 * Stores the provided data derived from the form. In case invalid data was
	 * provided, an empty string will be stored.
	 *
	 * @param array $formData filter form data, may be empty
	 *
	 * @return void
	 */
	private function extractValidFilterFormData(array $formData) {
		foreach ($formData as $key => $rawValue) {
			switch($key) {
				case 'uid':
					// The fallthrough is intended.
				case 'city':
					// The fallthrough is intended.
				case 'district':
					// The fallthrough is intended.
				case 'houseType':
					// The fallthrough is intended.
				case 'rentFrom':
					// The fallthrough is intended.
				case 'rentTo':
					// The fallthrough is intended.
				case 'livingAreaFrom':
					// The fallthrough is intended.
				case 'livingAreaTo':
					$this->filterFormData[$key] = intval($rawValue);
					break;
				case 'objectNumber':
					// The fallthrough is intended.
				case 'site':
					$this->filterFormData[$key] = $rawValue;
					break;
				case 'objectType':
					$this->filterFormData['objectType'] = in_array(
						$rawValue, array('forSale', 'forRent')
					) ?  $rawValue : '';
					break;
				case 'priceRange':
					$this->filterFormData['priceRange'] = preg_match(
						'/^(\d+-\d+|-\d+|\d+-)$/', $rawValue
					) ? $rawValue : '';
					break;
				case 'numberOfRoomsFrom':
					// The fallthrough is intended.
				case 'numberOfRoomsTo':
					$commaFreeValue = $this->replaceCommasWithDots($rawValue);
					if (floatval($commaFreeValue) == intval($commaFreeValue)) {
						$formattedValue = $commaFreeValue;
					} else {
						$formattedValue = number_format(
							$commaFreeValue, 1, $localeConvention['decimal_point'], ''
						);
					}
					$this->filterFormData[$key] = $formattedValue;
				default:
					break;
			}
		}
	}

	/**
	 * Formats one price range.
	 *
	 * @param string $priceRange price range of the format "number-number", may be empty
	 *
	 * @return array array with one price range, consists of the two elements
	 *               "upperLimit" and "lowerLimit", will be empty if no price
	 *               range was provided in the form data
	 */
	private function getFormattedPriceRange($priceRange) {
		if ($priceRange == '') {
			return array();
		}

		$rangeLimits = t3lib_div::intExplode('-', $priceRange);

		// intval() converts an empty string to 0. So for "-100" zero and 100
		// will be stored as limits.
		return array(
			'lowerLimit' => $rangeLimits[0],
			'upperLimit' => $rangeLimits[1],
		);
	}

	/**
	 * Returns the priceRange data stored in priceRange.
	 *
	 * @return array array with one price range, consists of the two elements
	 *               "upperLimit" and "lowerLimit", will be empty if no price
	 *               range or rent data was set
	 */
	private function getPriceRange() {
		$rentData = $this->processRentFilterFormData();
		$priceRange = ($rentData != '')
			? $rentData
			: $this->filterFormData['priceRange'];

		return $this->getFormattedPriceRange($priceRange);
	}

	/**
	 * Formats the values of rentFrom and rentTo, to fit into the
	 * price ranges schema and then stores it in the member variable priceRange.
	 *
	 * @return string the rent values formatted as priceRange, will be empty if
	 *                rentTo and rentFrom are empty
	 */
	private function processRentFilterFormData() {
		$rentFrom = (!intval($this->filterFormData['rentFrom']))
			? ''
			: intval($this->filterFormData['rentFrom']);
		$rentTo = (!intval($this->filterFormData['rentTo']))
			? ''
			: intval($this->filterFormData['rentTo']);

		return (($rentFrom != '') || ($rentTo != ''))
			? $rentFrom . '-' . $rentTo
			: '';
	}

	/**
	 * Sets the target URL marker.
	 *
	 * @return void
	 */
	private function setTargetUrlMarker() {
		$this->setMarker(
			'target_url',
			htmlspecialchars(t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL(array(
				'parameter' => $this->getConfValueInteger(
					'filterTargetPID', 's_searchForm'
				),
			))))
		);
	}


	////////////////////////////////////////////////////////////////////
	// Functions concerning the hiding or filling of the search fields
	////////////////////////////////////////////////////////////////////

	/**
	 * Fills the input box for zip code or city if there is data for it. Hides
	 * the input if it is disabled by configuration.
	 *
	 * @return void
	 */
	private function fillOrHideSiteSearch() {
		if ($this->hasSearchField('site')) {
			$this->setMarker(
				'site', htmlspecialchars($this->filterFormData['site'])
			);
		} else {
			$this->hideSubparts('wrapper_site_search');
		}
	}

	/**
	 * Fills the price range drop-down with the configured ranges if it is
	 * enabled in the configuration, hides it otherwise.
	 *
	 * @return void
	 */
	private function fillOrHidePriceRangeDropDown() {
		if (!$this->hasSearchField('priceRanges')) {
			$this->hideSubparts('wrapper_price_range_options');
			return;
		}

		$priceRanges = $this->getPriceRangesFromConfiguration();
		$optionTags = '';

		foreach ($priceRanges as $range) {
			$priceRangeString = implode('-', $range);
			$label = $this->getPriceRangeLabel($range);
			$selectedAttribute
				= ($this->filterFormData['priceRange'] == $priceRangeString)
					? ' selected="selected"'
					: '';

			$optionTags .= '<option value="' . $priceRangeString .
				'" label="' . $label . '" ' . $selectedAttribute . '>' .
				$label . '</option>';
		}
		$this->setMarker('price_range_options', $optionTags);

		$this->setMarker(
			'price_range_on_change', $this->getOnChangeForSingleField()
		);
	}

	/**
	 * Fills the input box for the UID search if it is configured to be
	 * displayed. Hides the form element if it is disabled by
	 * configuration.
	 *
	 * @return void
	 */
	private function fillOrHideUidSearch() {
		if (!$this->hasSearchField('uid')) {
			$this->hideSubparts('wrapper_uid_search');
			return;
		}

		$this->setMarker(
			'searched_uid',
			((intval($this->filterFormData['uid']) == 0)
				? ''
				: intval($this->filterFormData['uid'])
			)
		);
	}

	/**
	 * Fills the input box for the object number search if it is configured to
	 * be displayed. Hides the form element if it is disabled by configuration.
	 *
	 * @return void
	 */
	private function fillOrHideObjectNumberSearch() {
		if (!$this->hasSearchField('objectNumber')) {
			$this->hideSubparts('wrapper_object_number_search');
			return;
		}

		$this->setMarker(
			'searched_object_number',
			htmlspecialchars($this->filterFormData['objectNumber'])
		);
	}

	/**
	 * Shows the city selector if enabled via configuration, otherwise hides it.
	 *
	 * @return void
	 */
	private function fillOrHideCitySearch() {
		$onChange = $this->hasSearchField('district')
			? ' onchange="updateDistrictsInSearchWidget();"'
			: $this->getOnChangeForSingleField();
		$this->createAndSetDropDown('city', $onChange);
	}

	/**
	 * Shows the district selector if enabled via configuration, otherwise
	 * hides it.
	 *
	 * @return void
	 */
	private function fillOrHideDistrictSearch() {
		$this->createAndSetDropDown(
			'district', $this->getOnChangeForSingleField()
		);

		$this->setMarker(
			'hide_district_selector',
			$this->hasSearchField('city') ?
			' style="display: none;"' : ' style="display: block;"'
		);
	}

	/**
	 * Fills a search drop-down from a list of models in the current template.
	 *
	 * If the drop-down is configured to be hidden, this function hides it in
	 * the template.
	 *
	 * Note that the object mapper must have a matching count function for
	 * $type, e.g. for $type = "city", it must have a "countByCity" function.
	 *
	 * @param string $type
	 *        the type of the selector, for example "city", must not be empty
	 * @param string $onChange
	 *        onchange attribute, must either start with " onchange" (including
	 *        the leading space) or be empty
	 *
	 * @return void
	 */
	private function createAndSetDropDown($type, $onChange = '') {
		if (!$this->hasSearchField($type)) {
			$this->hideSubparts('wrapper_' . $type . '_search');
			return;
		}

		$this->setMarker(
			'options_' . $type . '_search',
			$this->createDropDownItems($type, $this->filterFormData[$type])
		);

		$this->setMarker(
			$type . '_select_on_change', $onChange
		);
	}

	/**
	 * Creates the items HTML for a drop down.
	 *
	 * @param string $type
	 *        the type of the selector, for example "city", must not be empty
	 * @param integer $selectedUid
	 *        the UID of the item that should be selected, must be >= 0,
	 *        set to 0 to select no item
	 *
	 * @return string the created HTML, will contain at least an empty option
	 */
	public function createDropDownItems($type, $selectedUid = 0) {
		if (!in_array($type, array('city', 'district'))) {
			throw new InvalidArgumentException('"' . $type . '" is not a valid type.', 1333036086);
		}

		$objectMapper =
			tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');
		$countFunction = 'countBy' . ucfirst($type);
		$models = tx_oelib_MapperRegistry::
			get('tx_realty_Mapper_' . ucfirst($type))->findAll('title ASC');

		$options = '';
		foreach ($models as $model) {
			$numberOfMatches = $objectMapper->$countFunction($model);
			if ($numberOfMatches == 0) {
				continue;
			}

			$selected = ($selectedUid == $model->getUid())
					? ' selected="selected"' : '';

			$options .= '<option value="' . $model->getUid() . '"' .
				$selected . '>' . htmlspecialchars($model->getTitle()) . ' (' .
				$numberOfMatches . ')</option>' . LF;
		}

		return $options;
	}

	/**
	 * Shows a drop down menu for selecting house types if enabled via
	 * configuration, otherwise hides it.
	 *
	 * @return void
	 */
	private function fillOrHideHouseTypeSearch() {
		$this->fillOrHideAuxiliaryRecordSearch(
			'houseType', REALTY_TABLE_HOUSE_TYPES, 'house_type'
		);
	}

	/**
	 * Shows or hides a drop-down box of auxiliary records to filter the list
	 * for. Whether the box is hidden or shown depends on the configuration.
	 *
	 * @param string $searchKey
	 *        key used in the search from for the auxiliary records to get, must
	 *        be an exiting search key corresponding to the provided table name,
	 *        must not be empty
	 * @param string $tableName
	 *        name of the database table of which to use the records for the
	 *        drop-down, must not be empty
	 * @param string $columnName
	 *        column name in the realty records table which corresponds to the
	 *        provided table name, must not be empty
	 *
	 * @return void
	 */
	private function fillOrHideAuxiliaryRecordSearch(
		$searchKey, $tableName, $columnName
	) {
		if (!$this->hasSearchField($searchKey)) {
			$this->hideSubparts('wrapper_' . $columnName . '_search');
			return;
		}

		$records = tx_oelib_db::selectMultiple(
			$tableName . '.uid, ' . $tableName . '.title',
			REALTY_TABLE_OBJECTS . ',' . $tableName,
			REALTY_TABLE_OBJECTS . '.' . $columnName .
				' = ' . $tableName . '.uid' .
				tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS) .
				tx_oelib_db::enableFields($tableName),
			'uid',
			$tableName . '.title'
		);

		$options = '';
		foreach ($records as $record) {
			$options .= '<option value="' . $record['uid'] . '" ' .
				(($this->filterFormData[$searchKey] == $record['uid'])
					? 'selected="selected"' : '') .
				'>' . htmlspecialchars($record['title']) . '</option>' . LF;
		}
		$this->setMarker(
			'options_' . $columnName . '_search', $options
		);

		$this->setMarker(
			$columnName . '_select_on_change', $this->getOnChangeForSingleField()
		);
	}

	/**
	 * Shows the rent/sale radiobuttons if enabled via configuration, otherwise
	 * hides them.
	 *
	 * @return void
	 */
	private function fillOrHideObjectTypeSelect() {
		if (!$this->hasSearchField('objectType')) {
			$this->hideSubparts('wrapper_object_type_selector');
			return;
		}

		foreach(array('forRent' => 'rent', 'forSale' => 'sale')
			as $key => $markerPrefix
		) {
			$this->setMarker($markerPrefix . '_attributes',
				(($this->filterFormData['objectType'] == $key)
					? ' checked="checked"'
					: ''
				) . $this->getOnChangeForSingleField()
			);
		}
	}

	/**
	 * Fills the input box for the given search field if it is configured to be
	 * displayed. Hides the form element if it is disabled by configuration.
	 *
	 * @param string $searchField the name of the search field, to hide or show, must be 'livingArea' or 'rent'
	 * @param string $fieldMarkerPart the name of the field name part of the searched marker, must not be empty
	 *
	 * @return void
	 */
	private function fillOrHideFromToSearchField(
		$searchField, $fieldMarkerPart
	) {
		if (!$this->hasSearchField($searchField)) {
			$this->hideSubparts('wrapper_' . $fieldMarkerPart . '_search');
			return;
		}

		foreach (array('From', 'To') as $suffix) {
			$this->setMarker(
				'searched_' . $fieldMarkerPart . '_' . $suffix,
				($this->filterFormData[$searchField . $suffix])
					? $this->filterFormData[$searchField . $suffix]
					: ''
			);
		}
	}

	/**
	 * Returns an array of configured price ranges.
	 *
	 * @return array Two-dimensional array of the possible price ranges. Each
	 *               inner array consists of two elements with the keys
	 *               "lowerLimit" and "upperLimit". Note that the zero element
	 *               will always be empty because the first option in the
	 *               selectbox remains empty. If no price ranges are configured,
	 *               this array will be empty.
	 */
	private function getPriceRangesFromConfiguration() {
		if (!$this->hasConfValueString(
			'priceRangesForFilterForm', 's_searchForm')
		) {
			return array();
		}

		// The first element is empty because the first selectbox element should
		// remain empty.
		$priceRanges = array(array());

		$priceRangeConfiguration = t3lib_div::trimExplode(
			',',
			$this->getConfValueString('priceRangesForFilterForm','s_searchForm')
		);

		foreach ($priceRangeConfiguration as $range) {
			$priceRanges[] = $this->getFormattedPriceRange($range);
		}

		return $priceRanges;
	}

	/**
	 * Returns a formatted label for one price range according to the configured
	 * currency unit.
	 *
	 * @param array $range
	 *        range for which to receive the label, must have the elements "upperLimit" and "lowerLimit",
	 *        both must have integers as values, only one of the elements' values may be 0,
	 *        for an empty array the result will always be "&nbsp;"
	 *
	 * @return string formatted label for the price range, will be "&nbsp;"
	 *                if an empty array was provided (an empty string
	 *                would break the XHTML output's validity)
	 */
	private function getPriceRangeLabel(array $range) {
		if (empty($range)) {
			return '&nbsp;';
		}

		$currency = $this->getConfValueString('currencyUnit');

		$priceViewHelper = t3lib_div::makeInstance(
			'tx_oelib_ViewHelper_Price'
		);
		$priceViewHelper->setCurrencyFromIsoAlpha3Code($currency);

		if ($range['lowerLimit'] == 0) {
			$priceViewHelper->setValue($range['upperLimit']);
			$result = $this->translate('label_less_than') . ' ' . $priceViewHelper->render();
		} elseif ($range['upperLimit'] == 0) {
			$priceViewHelper->setValue($range['lowerLimit']);
			$result = $this->translate('label_greater_than') . ' ' . $priceViewHelper->render();
		} else {
			$priceViewHelper->setValue($range['lowerLimit']);
			$result = $priceViewHelper->render() . ' ' . $this->translate('label_to') . ' ';
			$priceViewHelper->setValue($range['upperLimit']);
			$result .= $priceViewHelper->render();
		}

		return htmlentities($result, ENT_QUOTES, 'utf-8');
	}


	//////////////////////////////////////////////////////////////////////////////
	// Functions concerning the building of the WHERE clauses for the list view.
	//////////////////////////////////////////////////////////////////////////////

	/**
	 * Returns a WHERE clause part for one price range.
	 *
	 * @return string WHERE clause part for the price range, will be build from
	 *                      "rentTo" and "rentFrom" fields if they are empty it
	 *                      will be build from "priceRange" field, if all three
	 *                      fields are empty an empty string will be returned
	 */
	private function getRentOrPriceRangeWhereClausePart() {
		$priceRange = $this->getPriceRange();
		if (empty($priceRange)) {
			return '';
		}

		if ($priceRange['lowerLimit'] == 0) {
			// Zero as lower limit must be excluded of the range because each
			// non-set price will be identified as zero. Many objects either
			// have a buying price or a rent which would make searching for
			// zero-prices futile.
			$equalSign = '';
			// Additionally to the objects that have at least one non-zero price
			// inferior to the lower lower limit, objects which have no price at
			// all need to be found.
			$whereClauseForObjectsForFree = ' OR (' . REALTY_TABLE_OBJECTS .
				'.rent_excluding_bills = 0 AND ' . REALTY_TABLE_OBJECTS .
				'.buying_price = 0)';
		} else {
			$equalSign = '=';
			$whereClauseForObjectsForFree = '';
		}
		// The WHERE clause part for the lower limit is always set, even if no
		// lower limit was provided. The lower limit will just be zero then.
		$lowerLimitRent = REALTY_TABLE_OBJECTS . '.rent_excluding_bills ' .
			'>' . $equalSign . ' ' . $priceRange['lowerLimit'];
		$lowerLimitBuy = REALTY_TABLE_OBJECTS . '.buying_price ' .
			'>' . $equalSign . ' ' . $priceRange['lowerLimit'];

		// The upper limit will be zero if no upper limit was provided. So zero
		// means infinite here.
		if ($priceRange['upperLimit'] != 0) {
			$upperLimitRent = ' AND ' . REALTY_TABLE_OBJECTS .
				'.rent_excluding_bills <= ' . $priceRange['upperLimit'];
			$upperLimitBuy = ' AND ' . REALTY_TABLE_OBJECTS .
				'.buying_price <= ' . $priceRange['upperLimit'];
		} else {
			$upperLimitRent = '';
			$upperLimitBuy = '';
		}

		return ' AND ((' . $lowerLimitRent . $upperLimitRent . ') OR (' .
			$lowerLimitBuy . $upperLimitBuy . ')' .
			$whereClauseForObjectsForFree . ')';
	}

	/**
	 * Returns the WHERE clause part for one site.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the site
	 */
	private function getSiteWhereClausePart() {
		if ($this->filterFormData['site'] == '') {
			return '';
		}

		// only the first two characters are used for a zip code search
		$zipSearchString = $GLOBALS['TYPO3_DB']->quoteStr(
			$GLOBALS['TYPO3_DB']->escapeStrForLike(
				substr($this->filterFormData['site'], 0, 2),
				REALTY_TABLE_OBJECTS
			),
			REALTY_TABLE_OBJECTS
		);
		$citySearchString = $GLOBALS['TYPO3_DB']->quoteStr(
			$GLOBALS['TYPO3_DB']->escapeStrForLike(
				$this->filterFormData['site'],
				REALTY_TABLE_CITIES
			),
			REALTY_TABLE_CITIES
		);

		return ' AND (' . REALTY_TABLE_OBJECTS . '.zip LIKE "' .
			$zipSearchString . '%" OR ' . REALTY_TABLE_CITIES .
			'.title LIKE "%' . $citySearchString . '%")';
	}

	/**
	 * Returns the WHERE clause part for the object number.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the object number
	 */
	private function getObjectNumberWhereClausePart() {
		if ($this->filterFormData['objectNumber'] == '') {
			return '';
		}

		return ' AND ' . REALTY_TABLE_OBJECTS . '.object_number="' .
			$GLOBALS['TYPO3_DB']->quoteStr(
				$this->filterFormData['objectNumber'], REALTY_TABLE_OBJECTS
			) . '"';
	}

	/**
	 * Returns the WHERE clause part for the UID.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the UID
	 */
	private function getUidWhereClausePart() {
		if ($this->filterFormData['uid'] == 0) {
			return '';
		}

		return ' AND ' . REALTY_TABLE_OBJECTS . '.uid=' .
			$this->filterFormData['uid'];
	}

	/**
	 * Returns the WHERE clause part for the objectType selector.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the objectType
	 *                selector
	 */
	private function getObjectTypeWhereClausePart() {
		if ($this->filterFormData['objectType'] == '') {
			return '';
		}

		$objectType = ($this->filterFormData['objectType'] == 'forRent')
			? REALTY_FOR_RENTING
			: REALTY_FOR_SALE;

		return ' AND ' . REALTY_TABLE_OBJECTS . '.object_type = ' . $objectType;
	}

	/**
	 * Returns the WHERE clause part for the city selection.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the city
	 *                selector
	 */
	private function getCityWhereClausePart() {
		if ($this->filterFormData['city'] == 0) {
			return '';
		}

		return ' AND ' . REALTY_TABLE_OBJECTS . '.city = ' .
			$this->filterFormData['city'];
	}

	/**
	 * Returns the WHERE clause part for the district selection.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the city
	 *                selector
	 */
	private function getDistrictWhereClausePart() {
		if ($this->filterFormData['district'] == 0) {
			return '';
		}

		return ' AND ' . REALTY_TABLE_OBJECTS . '.district = ' .
			$this->filterFormData['district'];
	}

	/**
	 * Returns the WHERE clause part for the house type selection.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the house type
	 *                selector
	 */
	private function getHouseTypeWhereClausePart() {
		if ($this->filterFormData['houseType'] == 0) {
			return '';
		}

		return ' AND ' . REALTY_TABLE_OBJECTS . '.house_type = ' .
			$this->filterFormData['houseType'];
	}

	/**
	 * Returns the WHERE clause part for the living area search fields.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the living area
	 *                search fields
	 */
	private function getLivingAreaWhereClausePart() {
		return (($this->filterFormData['livingAreaFrom'] != 0)
				? ' AND (' . REALTY_TABLE_OBJECTS . '.living_area >= '
					. $this->filterFormData['livingAreaFrom'] . ')'
				: '') .
			(($this->filterFormData['livingAreaTo'] != 0)
				? ' AND (' . REALTY_TABLE_OBJECTS . '.living_area <= '
					. $this->filterFormData['livingAreaTo'] . ')'
				: '');
	}

	/**
	 * Checks whether a given search field ID is set in displayedSearchFields
	 *
	 * @param string $fieldToCheck the search field name to check, must not be empty
	 *
	 * @return boolean TRUE if the given field should be displayed as set per
	 *                 configuration, FALSE otherwise
	 */
	private function hasSearchField($fieldToCheck) {
		return in_array($fieldToCheck, $this->displayedSearchFields);
	}

	/**
	 * Returns an onChange attribute for the search wigdet fields.
	 *
	 * @return string attribute which sends the search widget on change event
	 *                handler, will be empty if more than one field is shown
	 */
	private function getOnChangeForSingleField() {
		if (count($this->displayedSearchFields) == 1) {
			$result = ' onchange="document.' .
				'forms[\'tx_realty_pi1_searchWidget\'].submit();"';
		} else {
			$result = '';
		}

		return $result;
	}

	/**
	 * Returns the WHERE clause part for the number of rooms search fields.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the number of rooms
	 *                search fields
	 */
	private function getNumberOfRoomsWhereClausePart() {
		$result = '';

		$roomsFromWithDots = $this->replaceCommasWithDots(
			$this->filterFormData['numberOfRoomsFrom']
		);
		if ($roomsFromWithDots != 0) {
			$result .= ' AND (' . REALTY_TABLE_OBJECTS . '.number_of_rooms >= '
				. $roomsFromWithDots . ')';
		}

		$roomsToWithDots = $this->replaceCommasWithDots(
			$this->filterFormData['numberOfRoomsTo']
		);
		if ($roomsToWithDots != 0) {
			$result .= ' AND (' . REALTY_TABLE_OBJECTS . '.number_of_rooms <= '
				. $roomsToWithDots . ')';
		}

		return $result;
	}

	/**
	 * Replaces every comma in a given string with a dot.
	 *
	 * @param string $rawValue the string with commas, may be empty
	 *
	 * @return string the string, with every comma replaced by a dot, will be
	 *                empty if the input string was empty.
	 */
	private function replaceCommasWithDots($rawValue) {
		return str_replace(',', '.', $rawValue);
	}

	/**
	 * Returns the allowed filter form piVar keys.
	 *
	 * @return array the allowed filter form piVar keys, will not be empty
	 */
	static public function getPiVarKeys() {
		return array(
			'uid', 'objectNumber', 'site', 'city', 'district', 'houseType',
			'priceRange', 'rentFrom', 'rentTo', 'livingAreaFrom', 'livingAreaTo',
			'objectType', 'numberOfRoomsFrom', 'numberOfRoomsTo'
		);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_filterForm.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_filterForm.php']);
}