<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_configcheck' for the 'realty' extension.
 *
 * This class checks the Realty Manager configuration for basic sanity.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_configcheck extends tx_oelib_configcheck {
	/**
	 * Checks the configuration for the gallery of the Realty Manager.
	 */
	public function check_tx_realty_pi1_gallery() {
		$this->checkCommonFrontEndSettings();
		$this->checkImageSizeValuesForGallery();
	}

	/**
	 * Checks the configuration for the city selector of the Realty Manager.
	 */
	public function check_tx_realty_pi1_city_selector() {
		$this->checkCommonFrontEndSettings();
		$this->checkFilterTargetPid();
	}

	/**
	 * Checks the configuration for the filter form of the Realty Manager.
	 */
	public function check_tx_realty_pi1_filter_form() {
		$this->checkCommonFrontEndSettings();
		$this->checkFilterTargetPid();
		$this->checkShowSiteSearchInFilterForm();
		$this->checkPriceRangesForFilterForm();
		$this->checkShowIdSearchInFilterForm();
	}

	/**
	 * Checks the configuration for the list view of the Realty Manager.
	 */
	public function check_tx_realty_pi1_realty_list() {
		$this->checkListViewRelatedConfiguration();
		$this->checkFavoritesPid();
	}

	/**
	 * Checks all list view related configuration of the Realty Manager.
	 */
	public function checkListViewRelatedConfiguration() {
		$this->checkCommonFrontEndSettings();
		$this->checkCheckboxesFilter();
		$this->checkImageSizeValuesForListView();
		$this->checkPagesToDisplay();
		$this->checkRecursive();
		$this->checkOrderBy();
		$this->checkSortCriteria();
		$this->checkNumberOfDecimals();
		$this->checkCurrencyUnit();
		$this->checkSingleViewPid();
		$this->checkGoogleMaps();
	}

	/**
	 * Checks the configuration for the favorites view of the Realty Manager.
	 */
	public function check_tx_realty_pi1_favorites() {
		$this->check_tx_realty_pi1_realty_list();
		$this->checkFavoriteFieldsInSession();
		$this->checkImageSizeValuesForListView();
		$this->checkShowContactPageLink();
		$this->checkContactPid();
	}

	/**
	 * Checks the configuration for the single view of the Realty Manager.
	 */
	public function check_tx_realty_pi1_single_view() {
		$this->checkCommonFrontEndSettings();
		$this->checkNumberOfDecimals();
		$this->checkCurrencyUnit();
		$this->checkRequireLoginForSingleViewPage();
		$this->checkGalleryType();
		if ($this->objectToCheck->getConfValueString('galleryType') != 'lightbox') {
			$this->checkGalleryPid();
		}
		if ($this->objectToCheck->getConfValueBoolean(
			'requireLoginForSingleViewPage', 's_template_special'
		)) {
			$this->checkLoginPid();
		}
		$this->checkImageSizeValuesForSingleView();
		$this->checkObjectsByOwnerPid();
		$this->checkUserGroupsForOffererList();
		$this->checkDisplayedContactInformation();
		$this->checkDisplayedContactInformationSpecial();
		$this->checkGroupsWithSpeciallyDisplayedContactInformation();
		$this->checkShowContactPageLink();
		$this->checkContactPid();
		$this->checkFieldsInSingleView();
		$this->checkFavoritesPid();
		$this->checkGoogleMaps();
	}

	/**
	 * Checks the configuration for the contact form of the Realty Manager.
	 */
	public function check_tx_realty_pi1_contact_form() {
		$this->checkCommonFrontEndSettings();
		$this->checkDefaultContactEmail();
		$this->checkBlindCarbonCopyAddress();
		$this->checkRequiredContactFormFields();
	}

	/**
	 * Checks the configuration for the my objects view of the Realty Manager.
	 */
	public function check_tx_realty_pi1_my_objects() {
		$this->checkListViewRelatedConfiguration();
		$this->checkEditorPid();
		$this->checkLoginPid();
		$this->checkImageUploadPid();
		$this->checkAdvertisementPid();
		if ($this->objectToCheck->hasConfValueInteger(
			'advertisementPID', 's_advertisements'
		)) {
			$this->checkAdvertisementParameterForObjectUid();
			$this->checkAdvertisementExpirationInDays();
		}
	}

	/**
	 * Checks the configuration for the Realty Manager's list view of objects by
	 * a certain owner.
	 */
	public function check_tx_realty_pi1_objects_by_owner() {
		$this->check_tx_realty_pi1_realty_list();
	}

	/**
	 * Checks the configuration for the Realty Manager's offerer list.
	 */
	public function check_tx_realty_pi1_offerer_list() {
		$this->checkCommonFrontEndSettings();
		$this->checkObjectsByOwnerPid(false);
		$this->checkUserGroupsForOffererList();
		$this->checkDisplayedContactInformation(false);
		$this->checkDisplayedContactInformationSpecial();
		$this->checkGroupsWithSpeciallyDisplayedContactInformation();
	}

	/**
	 * Checks the configuration for the FE editor of the Realty Manager.
	 */
	public function check_tx_realty_pi1_fe_editor() {
		// TODO: Check the FE editor template file once we can check other
		// templates than the default template.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=2061
		$this->checkCommonFrontEndSettings();
		$this->checkSysFolderForFeCreatedRecords();
		$this->checkSysFolderForFeCreatedAuxiliaryRecords();
		$this->checkFeEditorRedirectPid();
		$this->checkFeEditorNotifyEmail();
		$this->checkLoginPid();
	}

	/**
	 * Checks the configuration for the FE editor of the Realty Manager.
	 */
	public function check_tx_realty_pi1_image_upload() {
		// TODO: Check the FE editor template file once we can check other
		// templates than the default template.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=2061
		$this->checkCommonFrontEndSettings();
		$this->checkSysFolderForFeCreatedRecords();
		$this->checkFeEditorRedirectPid();
		$this->checkLoginPid();
		$this->checkImageUploadThumbnailConfiguration();
	}

	/**
	 * Checks the settings that are common to all FE plug-in variations of this
	 * extension: CSS styled content, static TypoScript template included,
	 * template file, CSS file, salutation mode, CSS class names and the locale.
	 */
	private function checkCommonFrontEndSettings() {
		$this->checkStaticIncluded();
		$this->checkCssStyledContent();
		$this->checkTemplateFile();
		$this->checkSalutationMode();
		$this->checkCssFileFromConstants();
		$this->checkCssClassNames();
		$this->checkDateFormat();
		$this->checkWhatToDisplay();
		$this->checkLocale();
	}

	/**
	 * Checks the settings for Google Maps.
	 */
	private function checkGoogleMaps() {
		$this->checkShowGoogleMaps();
		if ($this->objectToCheck->getConfValueBoolean(
			'showGoogleMaps', 's_googlemaps'
		)) {
			$this->checkGoogleMapsApiKey();
			$this->checkDefaultCountry();
		}
	}

	/**
	 * Checks the setting of the configuration value what_to_display.
	 */
	private function checkWhatToDisplay() {
		$this->checkIfSingleInSetNotEmpty(
			'what_to_display',
			true,
			'sDEF',
			'This value specifies the type of the realty plug-in to display. '
				.'If it is not set correctly, it is ignored and the list view '
				.'is displayed.',
			array(
				'realty_list',
				'single_view',
				'gallery',
				'favorites',
				'city_selector',
				'filter_form',
				'contact_form',
				'my_objects',
				'offerer_list',
				'objects_by_owner',
				'fe_editor',
				'image_upload',
			)
		);
	}

	/**
	 * Checks the setting for the currency unit.
	 */
	private function checkCurrencyUnit() {
		$this->checkForNonEmptyString(
			'currencyUnit',
			false,
			'',
			'This value specifies the currency of displayed prices. ' .
				'If this value is empty, prices of objects that do not provide' .
				'their own currency will be displayed without a currency.'
		);
	}

	/**
	 * Checks the setting for the date format.
	 */
	private function checkDateFormat() {
		$this->checkForNonEmptyString(
			'dateFormat',
			false,
			'',
			'This determines the way dates and times are displayed. '
				.'If this is not set correctly, dates and times might '
				.'be mangled or not get displayed at all.'
		);
	}

	private function checkNumberOfDecimals() {
		$this->checkIfPositiveIntegerOrZero(
			'numberOfDecimals',
			true,
			'sDEF',
			'This value specifies the number of decimal digits for formatting '
				.'prices. If this value is invalid, the standard value of the '
				.'current locale is taken.'
		);
	}

	/**
	 * Checks whether values for image sizes in the list view are set.
	 */
	private function checkImageSizeValuesForListView() {
		$imageSizeItems =  array (
			'listImageMaxX',
			'listImageMaxY'
		);

		foreach ($imageSizeItems as $fieldName) {
			$this->checkIfPositiveInteger(
				$fieldName,
				false,
				'',
				'This value specifies image dimensions. Images will not be '
					.'displayed correctly if this value is invalid.'
			);
		}
	}

		/**
	 * Checks whether values for image sizes in the single view are set.
	 */
	private function checkImageSizeValuesForSingleView() {
		$imageSizeItems =  array (
			'singleImageMaxX',
			'singleImageMaxY'
		);

		foreach ($imageSizeItems as $fieldName) {
			$this->checkIfPositiveInteger(
				$fieldName,
				false,
				'',
				'This value specifies image dimensions. Images will not be '
					.'displayed correctly if this value is invalid.'
			);
		}
	}

	/**
	 * Checks whether values for image sizes in the gallery are set.
	 */
	private function checkImageSizeValuesForGallery() {
		$imageSizeItems =  array (
			'galleryFullSizeImageX',
			'galleryFullSizeImageY',
			'galleryThumbnailX',
			'galleryThumbnailY'
		);

		foreach ($imageSizeItems as $fieldName) {
			$this->checkIfPositiveInteger(
				$fieldName,
				false,
				'',
				'This value specifies image dimensions. Images will not be '
					.'displayed correctly if this value is invalid.'
			);
		}
	}

	/**
	 * Checks the settings of fields in single view.
	 */
	private function checkFieldsInSingleView() {
		$this->checkIfMultiInSetNotEmpty(
			'fieldsInSingleViewTable',
			false,
			'',
			'This value specifies the fields which should be displayed in '
				.'single view. If this value is empty, the single view only '
				.'shows the title of an object.',
			$this->getDbColumnNames(REALTY_TABLE_OBJECTS)
		);
	}

	/**
	 * Checks the settings of favorite fields which should be stored in the
	 * session.
	 */
	private function checkFavoriteFieldsInSession() {
		$this->checkIfMultiInSetOrEmpty(
			'favoriteFieldsInSession',
			false,
			'',
			'This value specifies the field names that will be stored in the '
				.'session when displaying the favorites list. This value may be '
				.'empty. Wrong values cause empty fields in the session data '
				.'array.',
			$this->getDbColumnNames(REALTY_TABLE_OBJECTS)
		);
	}

	/**
	 * Checks the setting of the configuration value
	 * requireLoginForSingleViewPage.
	 */
	private function checkRequireLoginForSingleViewPage() {
		$this->checkIfBoolean(
			'requireLoginForSingleViewPage',
			false,
			'',
			'This value specifies whether a login is required to access the '
				.'single view page. It might be interpreted incorrectly if no '
				.'logical value was set.'
		);
	}

	/**
	 * Checks the setting for the login PID.
	 */
	private function checkLoginPid() {
		$this->checkIfSingleFePageNotEmpty(
			'loginPID',
			false,
			'',
			'This value specifies the login page and is needed if a login ' .
				'is required. Users could not be directed to the login ' .
				'page if this value is invalid.'
		);
	}

	/**
	 * Checks the setting of the configuration value showContactPageLink.
	 */
	private function checkShowContactPageLink() {
		$this->checkIfBoolean(
			'showContactPageLink',
			true,
			'sDEF',
			'This value specifies whether a link to the contact form should be ' .
				'displayed in the current view. A misconfigured value might lead ' .
				'to undesired results.'
		);
	}

	/**
	 * Checks the setting for the contact PID.
	 */
	private function checkContactPid() {
		if (!$this->objectToCheck->getConfValueBoolean('showContactPageLink')) {
			return;
		}

		$this->checkIfSingleFePageNotEmpty(
			'contactPID',
			false,
			'',
			'This value specifies the contact page which will be linked from ' .
				'the current page. The link to the contact form will not work ' .
				'as long as this value is misconfigured.'
		);
	}

	/**
	 * Checks the setting for whether to show the site search in the filter form.
	 */
	private function checkShowSiteSearchInFilterForm() {
		$this->checkIfSingleInSetNotEmpty(
			'showSiteSearchInFilterForm',
			true,
			's_searchForm',
			'This value specifies whether to show the input to search for ZIP ' .
				'code or city in the filter form. It might be interpreted ' .
				'incorrectly if a value out of range was set.',
			array('show', 'hide')
		);
	}

	/**
	 * Checks the setting for the price ranges for the filter form.
	 */
	private function checkPriceRangesForFilterForm() {
		$this->checkRegExp(
			'priceRangesForFilterForm',
			true,
			's_searchForm',
			'This value defines the ranges to be displayed in the filter ' .
				'form\'s selectbox for prices. With an invalid configuration, ' .
				'price ranges will not be displayed correctly.',
			'/^(((\d+-\d+|-\d+|\d+-), *)*(\d+-\d+|-\d+|\d+-))?$/'
		);
	}

	/**
	 * Checks the setting for whether to show the UID or object number search
	 * in the search form.
	 */
	private function checkShowIdSearchInFilterForm() {
		$this->checkIfSingleInSetOrEmpty(
			'showIdSearchInFilterForm',
			true,
			's_searchForm',
			'This value specifies which ID search to show in the search form. ' .
			'If an incorrect value is set, the ID search form will be displayed ' .
			'with an incorrect label and the search will not work.',
			array('uid', 'objectNumber')
		);
	}

	/**
	 * Checks the setting of the pages that contain realty records to be
	 * displayed.
	 */
	private function checkPagesToDisplay() {
		$this->checkIfPidListNotEmpty(
			'pages',
			true,
			'sDEF',
			'This value specifies the list of PIDs that contain the realty '
				.'records to be displayed. If this list is empty, there is only '
				.'a message about no search results displayed.'
		);
	}

	/**
	 * Checks the setting for the recursion level for the pages list.
	 */
	private function checkRecursive() {
		$this->checkIfPositiveIntegerOrZero(
			'recursive',
			true,
			'sDEF',
			'This value specifies the recursion level for the pages list. The '
				.'recursion can only be set to include subfolders of the '
				.'folders in "pages". It is impossible to access superior '
				.'folders with this option.'
		);
	}

	/**
	 * Checks the setting of the configuration value objectsByOwnerPID.
	 *
	 * @param boolean true if the configuration may be empty
	 */
	private function checkObjectsByOwnerPid($mayBeEmpty = true) {
		if ($mayBeEmpty) {
			$checkFunction = checkIfSingleFePageOrEmpty;
			$errorText = 'This value specifies the page ID of the list of ' .
				'objects by one offerer. The link to this list might not work ' .
				'correctly if this value is misconfigured.';
		} else {
			$checkFunction = checkIfSingleFePageNotEmpty;
			$errorText = 'This value specifies the page ID of the list of ' .
				'objects by one offerer. The link to this list will not be ' .
				'displayed if this value is empty. The link might not work ' .
				'correctly if this value is misconfigured.';
		}

		$this->$checkFunction(
			'objectsByOwnerPID', true, 's_offererInformation', $errorText
		);
	}

	/**
	 * Checks the setting of the configuration value userGroupsForOffererList.
	 */
	private function checkUserGroupsForOffererList() {
		$this->checkIfPidListOrEmpty(
			'userGroupsForOffererList',
			true,
			's_offererInformation',
			'This value specifies the group from which the users are displayed ' .
				'in the offerer list. The list will be empty if this value is ' .
				'invalid. All front-end user will be displayed if this value is ' .
				'empty.'
		);
	}

	/**
	 * Checks the setting for displayedContactInformation.
	 *
	 * @param boolean true if the configuration may be empty
	 */
	private function checkDisplayedContactInformation($mayBeEmpty = true) {
		if ($mayBeEmpty) {
			$checkFunction = checkIfMultiInSetOrEmpty;
		} else {
			$checkFunction = checkIfMultiInSetNotEmpty;
		}

		$this->$checkFunction(
			'displayedContactInformation',
			true,
			's_offererInformation',
			'This value specifies which contact data to display in the front-end. ' .
				'The contact data will not be displayed at all if this value is ' .
				'empty or contains only invalid keys.',
			array(
				'company', 'offerer_label', 'usergroup', 'street', 'city',
				'telephone', 'email', 'www', 'objects_by_owner_link'
			)
		);
	}

	/**
	 * Checks the setting for displayedContactInformationSpecial.
	 */
	private function checkDisplayedContactInformationSpecial() {
		$this->checkIfMultiInSetOrEmpty(
			'displayedContactInformationSpecial',
			true,
			's_offererInformation',
			'This value specifies which contact data to display in the front-end. ' .
				'This value only defines which contact data to display of ' .
				'offerers which are members in the front-end user groups for ' .
				'which to display special contact data. The contact data will ' .
				'not be displayed at all if this value is empty or contains only' .
				'invalid keys.',
			array(
				'company', 'offerer_label', 'usergroup', 'street', 'city',
				'telephone', 'email', 'www', 'objects_by_owner_link'
			)
		);
	}

	/**
	 * Checks the setting for displayedContactInformationSpecial.
	 */
	private function checkGroupsWithSpeciallyDisplayedContactInformation() {
		// checkIfPidListOrEmpty checks for a comma separated list of integers
		$this->checkIfPidListOrEmpty(
			'groupsWithSpeciallyDisplayedContactInformation',
			true,
			's_offererInformation',
			'This value specifies of which front-end user group\'s offerers ' .
				'special contact data should be displayed. If this value is ' .
				'empty or invalid, the special contact data will not be displayed ' .
				'for any owner.'
		);
	}

	/**
	 * Checks the setting for the default contact e-mail address.
	 */
	private function checkDefaultContactEmail() {
		$this->checkIsValidEmailNotEmpty(
			'defaultContactEmail',
			true,
			's_contactForm',
			true,
			'This value specifies the recipient for requests on objects. ' .
				'This address is always used if direct requests for objects ' .
				'are disabled and it is used if a direct request is not ' .
				'possible because an object\'s contact data cannot be found.'
		);
	}

	/**
	 * Checks the setting for the BCC e-mail address.
	 */
	private function checkBlindCarbonCopyAddress() {
		$this->checkIsValidEmailOrEmpty(
			'blindCarbonCopyAddress',
			true,
			's_contactForm',
			true,
			'This value specifies the recipient for for a blind carbon copy of ' .
				'each request on objects and may be left empty.'
		);
	}

	/**
	 * Checks the configuration for requiredContactFormFields.
	 */
	private function checkRequiredContactFormFields() {
		$this->checkIfMultiInSetOrEmpty(
			'requiredContactFormFields',
			true,
			's_contactForm',
			'This value specifies which fields are required to be filled when ' .
				'committing a contact request. Some fields will be not be ' .
				'required if this configuration is incorrect.',
			array('name', 'street', 'zip', 'city', 'telephone')
		);
	}

	/**
	 * Checks the setting of the checkboxes filter.
	 */
	private function checkCheckboxesFilter() {
		$this->checkIfSingleInTableOrEmpty(
			'checkboxesFilter',
			true,
			's_searchForm',
			'This value specifies the name of the DB field to create the search ' .
				'filter checkboxes from. Searching will not work properly if ' .
				'non-database fields are set.',
			REALTY_TABLE_OBJECTS
		);
	}

	/**
	 * Checks the setting for orderBy.
	 */
	private function checkOrderBy() {
		$this->checkIfSingleInSetOrEmpty(
			'orderBy',
			true,
			'sDEF',
			'This value specifies the database field name by which the list view ' .
				'should be sorted initially. Displaying the list view might not ' .
				'work properly if this value is misconfigured.',
			array(
				'object_number',
				'title',
				'city',
				'district',
				'buying_price',
				'rent_excluding_bills',
				'number_of_rooms',
				'living_area',
				'tstamp',
				'random',
			)
		);
	}

	/**
	 * Checks the settings for the sort criteria.
	 */
	private function checkSortCriteria() {
		$this->checkIfMultiInSetOrEmpty(
			'sortCriteria',
			true,
			'sDEF',
			'This value specifies the database field names by which a FE user ' .
				'can sort the list view. This value is usually set via ' .
				'flexforms.',
			array(
				'object_number',
				'title',
				'city',
				'district',
				'buying_price',
				'rent_excluding_bills',
				'number_of_rooms',
				'living_area',
				'tstamp',
				'random',
			)
		);
	}

	/**
	 * Checks the settings for the PID for the single view.
	 */
	private function checkSingleViewPid() {
		$this->checkIfSingleFePageNotEmpty(
			'singlePID',
			true,
			'sDEF',
			'This value specifies the PID of the page for the single view. If '
				.'this value is empty or invalid, the single view is shown on '
				.'the same page as the list view.'
		);
	}

	/**
	 * Checks the settings for the PID for the gallery.
	 */
	private function checkGalleryPid() {
		$this->checkIfSingleFePageNotEmpty(
			'galleryPID',
			true,
			'sDEF',
			'This value specifies the PID of the page with the gallery. If this '
				.'value is empty, the gallery will be disabled.'
		);
	}

	/**
	 * Checks the settings for the PID for the favorites view.
	 */
	private function checkFavoritesPid() {
		$this->checkIfSingleFePageNotEmpty(
			'favoritesPID',
			true,
			'sDEF',
			'This value specifies the PID of the page for the favorites view. '
				.'Favorites cannot be displayed if this value is invalid.'
		);
	}

	/**
	 * Checks the settings for the PID for the FE editor.
	 */
	private function checkEditorPid() {
		$this->checkIfSingleFePageNotEmpty(
			'editorPID',
			true,
			'sDEF',
			'This value specifies the PID of the page for the FE editor. '
				.'This page cannot be displayed if this value is invalid.'
		);
	}

	/**
	 * Checks the settings for the target PID for the filter form and the
	 * city selector.
	 */
	private function checkFilterTargetPid() {
		$this->checkIfSingleFePageNotEmpty(
			'filterTargetPID',
			true,
			's_searchForm',
			'This value specifies the PID of the target page for the filter '
				.'form and the city selector. These forms will not direct to '
				.'the correct page after submit if this value is invalid.'
		);
	}

	/**
	 * Checks the settings for the PID for the FE image upload.
	 */
	private function checkImageUploadPid() {
		$this->checkIfSingleFePageNotEmpty(
			'imageUploadPID',
			true,
			'sDEF',
			'This value specifies the PID of the page with the image upload for '
				.'the FE editor. The image upload cannot be displayed if this '
				.'value is invalid.'
		);
	}

	/**
	 * Checks the settings for the PID of the system folder for FE-created
	 * records.
	 */
	private function checkSysFolderForFeCreatedRecords() {
		$this->checkIfSingleSysFolderNotEmpty(
			'sysFolderForFeCreatedRecords',
			true,
			's_feeditor',
			'This value specifies the PID of the system folder for FE-created '
				.'records. New records will be stored on the root page if this '
				.'value is invalid.'
		);
	}

	/**
	 * Checks the settings for the PID of the system folder for FE-created
	 * records.
	 */
	private function checkSysFolderForFeCreatedAuxiliaryRecords() {
		$this->checkIfSingleSysFolderNotEmpty(
			'sysFolderForFeCreatedAuxiliaryRecords',
			true,
			's_feeditor',
			'This value specifies the PID of the system folder for FE-created '
				.'auxiliary records. New cities and districts will be stored on'
				.'the root page if this value is invalid.'
		);
	}

	/**
	 * Checks the settings for the PID of the FE page where to redirect to after
	 * saving a FE-created record.
	 */
	private function checkFeEditorRedirectPid() {
		$this->checkIfSingleFePageNotEmpty(
			'feEditorRedirectPid',
			true,
			's_feeditor',
			'This value specifies the PID of the FE page to which users will ' .
				'be redirected after a FE-created record or an image was saved. ' .
				'This redirecting will not proceed correctly if this value is ' .
				'invalid or empty.'
		);
	}

	/**
	 * Checks the setting for the FE editor's notification e-mail address.
	 */
	private function checkFeEditorNotifyEmail() {
		$this->checkIsValidEmailNotEmpty(
			'feEditorNotifyEmail',
			true,
			's_feeditor',
			true,
			'This value specifies the recipient for a notification when a new '
				.'record has been created in the FE. No e-mail will be send if '
				.'this value is not configured correctly.'
		);
	}

	/**
	 * Checks the default country.
	 */
	private function checkDefaultCountry() {
		$this->checkIfPositiveInteger(
			'defaultCountryUID',
			true,
			's_googlemaps',
			'This value specifies the UID of the default country for realty ' .
				'objects. If this value is not configured correctly, the ' .
				'objects will be mislocated in Google Maps.'
		);
	}

	/**
	 * Checks the configuration value showGoogleMaps.
	 */
	private function checkShowGoogleMaps() {
		$this->checkIfBoolean(
			'showGoogleMaps',
			true,
			's_googlemaps',
			'This value specifies whether a Google Map of an object should be ' .
				'shown. If this value is not set correctly, the map might not ' .
				'get shown although it should be shown (or vice versa).'
		);
	}

	/**
	 * Checks the settings for the Google Maps API key.
	 */
	private function checkGoogleMapsApiKey() {
		$this->checkForNonEmptyString(
			'googleMapsApiKey',
			true,
			's_googlemaps',
			'This determines the Google Maps API key. If this is not set ' .
				'correctly, Google Maps will produce an error message.'
		);
	}

	/**
	 * Checks the settings for the gallery/Lightbox display.
	 */
	private function checkGalleryType() {
		$this->checkIfSingleInSetNotEmpty(
			'galleryType',
			true,
			'sDEF',
			'This setting determines wether the gallery makes use of Lightbox. ' .
				'If this is not set correctly, the gallery will fall back to ' .
				'the old gallery and will not use the Lightbox.',
			array(
				'lightbox',
				'classic',
			)
		);
	}

	/**
	 * Checks the settings for the thumbnail images in the front-end image
	 * upload.
	 */
	private function checkImageUploadThumbnailConfiguration() {
		$this->checkImageUploadThumbnailWidth();
		$this->checkImageUploadThumbnailHeight();
	}

	/**
	 * Checks the settings of the maximum width of the thumbnail images at the
	 * front-end image upload.
	 */
	private function checkImageUploadThumbnailWidth() {
		$this->checkIfPositiveInteger(
			'imageUploadThumbnailWidth',
			false,
			'',
			'This value specifies the width of the thumbnails in the image ' .
				'upload. If it is not configured properly, the image will be ' .
				'shown at original size.'
		);
	}

	/**
	 * Checks the settings of the maximum height of the thumbnail images at the
	 * front-end image upload.
	 */
	private function checkImageUploadThumbnailHeight() {
		$this->checkIfPositiveInteger(
			'imageUploadThumbnailHeight',
			false,
			'',
			'This value specifies the height of the thumbnails in the image ' .
				'upload. If it is not configured properly, the image will be ' .
				'shown at original size.'
		);
	}

	/**
	 * Checks the configuration value advertisementPID.
	 */
	private function checkAdvertisementPid() {
		$this->checkIfSingleFePageOrEmpty(
			'advertisementPID',
			true,
			's_advertisements',
			'This value specifies the page that contains the advertisement ' .
				'form. If this value is incorrect, the link to the form ' .
				'will not work.'
		);
	}

	/**
	 * Checks the configuration value advertisementParameterForObjectUid.
	 */
	private function checkAdvertisementParameterForObjectUid() {
		// Nothing to do - every string is allowed.
	}

	/**
	 * Checks the configuration value advertisementExpirationInDays.
	 */
	private function checkAdvertisementExpirationInDays() {
		$this->checkIfPositiveIntegerOrZero(
			'advertisementExpirationInDays',
			true,
			's_advertisements',
			'This value specifies the period after which an advertisement ' .
				'expires. If this value is invalid, advertisements will ' .
				'not expire at all.'
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/class.tx_realty_configcheck.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/class.tx_realty_configcheck.php']);
}
?>