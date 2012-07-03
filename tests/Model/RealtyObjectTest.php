<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2012 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_Model_RealtyObject class in the "realty"
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Model_RealtyObjectTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_Model_RealtyObjectChild
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var tx_oelib_templatehelper
	 */
	private $templateHelper;

	/**
	 * @var integer UID of a dummy realty object
	 */
	private $objectUid = 0;
	/**
	 * @var integer page UID of a dummy FE page
	 */
	private $pageUid = 0;
	/**
	 * @var integer page UID of another dummy FE page
	 */
	private $otherPageUid = 0;
	/**
	 * @var string object number of a dummy realty object
	 */
	private static $objectNumber = '100000';
	/**
	 * @var string object number of a dummy realty object
	 */
	private static $otherObjectNumber = '100001';

	/**
	 * @var integer static_info_tables UID of Germany
	 */
	const DE = 54;

	/**
	 * @var float latitude
	 */
	const LATITUDE = 50.7;

	/**
	 * @var float longitude
	 */
	const LONGITUDE = 7.1;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->createDummyRecords();

		tx_oelib_MapperRegistry::getInstance()
			->activateTestingMode($this->testingFramework);

		$geoFinder = new tx_realty_tests_fixtures_FakeGoogleMapsLookup();
		$geoFinder->setCoordinates(self::LATITUDE, self::LONGITUDE);
		tx_realty_googleMapsLookup::setInstance($geoFinder);

		$this->templateHelper = $this->getMock(
			'tx_oelib_templatehelper', array('hasConfValueString', 'getConfValueString')
		);

		$this->fixture = new tx_realty_Model_RealtyObjectChild(TRUE);

		$this->fixture->setRequiredFields(array());
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForRealtyObjectsAndImages', $this->pageUid);
	}

	public function tearDown() {
		$this->cleanUpDatabase();

		tx_realty_googleMapsLookup::purgeInstance();
		$this->templateHelper->__destruct();
		$this->fixture->__destruct();
		unset($this->fixture, $this->templateHelper, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy system folders and realty objects in the DB.
	 */
	private function createDummyRecords() {
		$this->pageUid = $this->testingFramework->createSystemFolder();
		$this->otherPageUid = $this->testingFramework->createSystemFolder();
		$this->objectUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'foo',
				'object_number' => self::$objectNumber,
				'pid' => $this->pageUid,
				'language' => 'foo',
				'openimmo_obid' => 'test-obid',
			)
		);
	}

	/**
	 * Cleans up the tables in which dummy records are created during the tests.
	 */
	private function cleanUpDatabase() {
		// Inserting images causes an entry to 'sys_refindex' which is currently
		// not cleaned up automatically by the testing framework.
		if (in_array(
			REALTY_TABLE_IMAGES, $this->testingFramework->getListOfDirtyTables()
		)) {
			tx_oelib_db::delete(
				'sys_refindex', 'ref_string = "' . tx_realty_Model_Image::UPLOAD_FOLDER . 'bar"'
			);
		}

		$this->testingFramework->cleanUp();
	}

	/**
	 * Loads a realty object into the fixture and sets the owner of this object.
	 *
	 * @param integer $ownerSource
	 *        the source of the owner data for the object,
	 *        must be REALTY_CONTACT_FROM_OWNER_ACCOUNT or REALTY_CONTACT_FROM_REALTY_OBJECT
	 * @param array $userData
	 *        additional data which should be stored into the owners data, may be empty
	 * @param array $additionalObjectData
	 *        additional data which should be stored into the object, may be empty
	 */
	private function loadRealtyObjectAndSetOwner(
		$ownerSource,
		array $userData = array() ,
		array $additionalObjectData = array()
	) {
		$objectData = array_merge(
			$additionalObjectData,
			array(
				'contact_data_source' => $ownerSource,
				'owner' =>
					tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser')
						->getLoadedTestingModel($userData)->getUid(),
			)
		);

		$this->fixture->loadRealtyObject($objectData);
	}


	///////////////////////////////
	// Testing the realty object.
	///////////////////////////////

	public function testRecordExistsInDatabaseIfNoExistingObjectNumberGiven() {
		$this->assertFalse(
			$this->fixture->recordExistsInDatabase(
				array('object_number' => '99999')
			)
		);
	}

	public function testRecordExistsInDatabaseIfExistingObjectNumberGiven() {
		$this->assertTrue(
			$this->fixture->recordExistsInDatabase(
				array('object_number' => self::$objectNumber)
			)
		);
	}

	public function testLoadDatabaseEntryWithValidUid() {
		$this->assertEquals(
			tx_oelib_db::selectSingle(
				'*', REALTY_TABLE_OBJECTS, 'uid = ' . $this->objectUid
			),
			$this->fixture->loadDatabaseEntry($this->objectUid)
		);
	}

	public function testLoadDatabaseEntryWithInvalidUid() {
		$this->assertEquals(
			array(),
			$this->fixture->loadDatabaseEntry('99999')
		);
	}

	public function testLoadDatabaseEntryOfAnNonHiddenObjectIfOnlyVisibleAreAllowed() {
		$this->fixture->loadRealtyObject($this->objectUid, FALSE);
		$this->assertEquals(
			tx_oelib_db::selectSingle(
				'*', REALTY_TABLE_OBJECTS, 'uid = ' . $this->objectUid
			),
			$this->fixture->loadDatabaseEntry($this->objectUid)
		);
	}

	public function testLoadDatabaseEntryDoesNotLoadAHiddenObjectIfOnlyVisibleAreAllowed() {
		$this->fixture->loadRealtyObject($this->objectUid, FALSE);
		$uid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('hidden' => 1)
		);
		$this->assertEquals(
			array(),
			$this->fixture->loadDatabaseEntry($uid)
		);
	}

	public function testLoadDatabaseEntryLoadsAHiddenObjectIfHiddenAreAllowed() {
		$this->fixture->loadRealtyObject($this->objectUid, TRUE);
		$uid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('hidden' => 1)
		);
		$this->assertEquals(
			tx_oelib_db::selectSingle(
				'*', REALTY_TABLE_OBJECTS, 'uid = ' . $uid
			),
			$this->fixture->loadDatabaseEntry($uid)
		);
	}

	public function testGetDataTypeWhenArrayGiven() {
		$this->assertEquals(
			'array',
			$this->fixture->getDataType(array('foo'))
		);
	}

	public function testLoadRealtyObjectWithValidArraySetDataForGetProperty() {
		$this->fixture->loadRealtyObject(array('title' => 'foo'));

		$this->assertEquals(
			'foo',
			$this->fixture->getProperty('title')
		);
	}

	public function testLoadRealtyObjectFromAnArrayWithNonZeroUidIsAllowed() {
		$this->fixture->loadRealtyObject(array('uid' => 1234));
	}

	public function testLoadRealtyObjectFromArrayWithZeroUidIsAllowed() {
		$this->fixture->loadRealtyObject(array('uid' => 0));
	}

	public function testLoadHiddenRealtyObjectIfHiddenObjectsAreNotAllowed() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->objectUid, array('hidden' => 1)
		);
		$this->fixture->loadRealtyObject($this->objectUid, FALSE);

		$this->assertTrue(
			$this->fixture->isRealtyObjectDataEmpty()
		);
	}

	public function testLoadHiddenRealtyObjectIfHidddenObjectsAreAllowed() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->objectUid, array('hidden' => 1)
		);
		$this->fixture->loadRealtyObject($this->objectUid, TRUE);

		$this->assertFalse(
			$this->fixture->isRealtyObjectDataEmpty()
		);
	}

	public function testCreateNewDatabaseEntryIfAValidArrayIsGiven() {
		$this->fixture->createNewDatabaseEntry(
			array('object_number' => self::$otherObjectNumber)
		);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$otherObjectNumber . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function createNewDatabaseEntryForArrayWithNonZeroUidThrowsException() {
		$this->fixture->createNewDatabaseEntry(array('uid' => 1234));
	}

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function createNewDatabaseEntryForArrayWithZeroUidThrowsException() {
		$this->fixture->createNewDatabaseEntry(array('uid' => 0));
	}

	public function testGetDataTypeWhenIntegerGiven() {
		$this->assertEquals(
			'uid',
			$this->fixture->getDataType(1)
		);
	}

	public function testSetDataSetsTheRealtyObjectsTitle() {
		$this->fixture->setData(array('title' => 'foo'));

		$this->assertEquals(
			'foo',
			$this->fixture->getTitle()
		);
	}


	////////////////////////////////
	// Tests concerning the images
	////////////////////////////////

	public function testLoadRealtyObjectByUidAlsoLoadsImages() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'object' => $this->objectUid
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			'foo',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	public function testSetDataSetsTheImageDataForImageFromDatabase() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'object' => $this->objectUid
			)
		);
		$this->fixture->setData(array('uid' => $this->objectUid, 'images' => 1));

		$this->assertEquals(
			'foo',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataSetsImagePositionForImageFromDatabase() {
		$this->testingFramework->createRecord(
			'tx_realty_images',
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'object' => $this->objectUid,
				'position' => 4,
			)
		);
		$this->fixture->setData(array('uid' => $this->objectUid, 'images' => 1));

		$this->assertEquals(
			4,
			$this->fixture->getImages()->first()->getPosition()
		);
	}

	public function testSetDataSetsTheImageDataForImageFromArray() {
		$this->fixture->setData(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(
					array('caption' => 'test', 'image' => 'test.jpg')
				)
			)
		);

		$this->assertEquals(
			'test',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataWithDocumentAndImageSetsTheDataForImagesFromArray() {
		$this->fixture->setData(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(
					array('caption' => 'test image', 'image' => 'test.jpg')
				),
				'documents' => array(
					array('title' => 'test document', 'filename' => 'test.pdf')
				),
			)
		);

		$this->assertEquals(
			'test image',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getImagesReturnsTheCurrentObjectsImagesOrderedBySorting() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 2)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'second',
				'image' => 'second.jpg',
				'object' => $this->objectUid,
				'sorting' => 2,
			)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'first',
				'image' => 'first.jpg',
				'object' => $this->objectUid,
				'sorting' => 1,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$titles = array();
		foreach ($this->fixture->getImages() as $image) {
			$titles[] = $image->getTitle();
		}
		$this->assertEquals(
			array('first', 'second'),
			$titles
		);
	}


	///////////////////////////////////
	// Tests concerning the documents
	///////////////////////////////////

	/**
	 * @test
	 */
	public function loadRealtyObjectByUidLoadsDocuments() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('documents' => 1)
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'foo',
				'filename' => 'foo.pdf',
				'object' => $this->objectUid,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			'foo',
			$this->fixture->getDocuments()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataSetsTheDataForDocumentFromDatabase() {
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'foo',
				'filename' => 'foo.pdf',
				'object' => $this->objectUid,
			)
		);
		$this->fixture->setData(
			array('uid' => $this->objectUid, 'documents' => 1)
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getDocuments()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataSetsTheDataForDocumentFromArray() {
		$this->fixture->setData(
			array(
				'object_number' => self::$otherObjectNumber,
				'documents' => array(
					array('title' => 'test', 'filename' => 'test.pdf')
				),
			)
		);

		$this->assertEquals(
			'test',
			$this->fixture->getDocuments()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDataWithDocumentAndImageSetsTheDataForDocumentFromArray() {
		$this->fixture->setData(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(
					array('caption' => 'test image', 'image' => 'test.jpg')
				),
				'documents' => array(
					array('title' => 'test document', 'filename' => 'test.pdf')
				),
			)
		);

		$this->assertEquals(
			'test document',
			$this->fixture->getDocuments()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getDocumentsReturnsTheCurrentObjectsDocumentsOrderedBySorting() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('documents' => 2)
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'second',
				'filename' => 'second.pdf',
				'object' => $this->objectUid,
				'sorting' => 2,
			)
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'first',
				'filename' => 'first.pdf',
				'object' => $this->objectUid,
				'sorting' => 1,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);

		$titles = array();
		foreach ($this->fixture->getDocuments() as $document) {
			$titles[] = $document->getTitle();
		}
		$this->assertEquals(
			array('first', 'second'),
			$titles
		);
	}


	/////////////////////////////////////
	// Tests concerning writeToDatabase
	/////////////////////////////////////

	public function testWriteToDatabaseUpdatesEntryIfUidExistsInDb() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('title', 'new title');
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$objectNumber . '" AND title="new title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseUpdatesEntryIfObjectMatchesObjectNumberLanguageAndObidOfADbEntry() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'language' => 'foo',
				'openimmo_obid' => 'test-obid',
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$objectNumber . '" AND title="new title"'
			)
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndObidExistOfADbEntryButNotLanguage() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'language' => 'bar',
				'openimmo_obid' => 'test-obid',
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$objectNumber
			)
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndLanguageExistOfADbEntryButNotObid() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'language' => 'foo',
				'openimmo_obid' => 'another-test-obid',
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$objectNumber
			)
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndObidOfADbEntryAndLanguageIsEmpty() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'language' => '',
				'openimmo_obid' => 'test-obid',
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$objectNumber
			)
		);
	}

	public function testWriteToDatabaseUpdatesEntryIfObjectMatchesObjectNumberOfADbEntryAndNoLanguageAndNoObidAreSet() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid . ' AND title="new title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseUpdatesEntryIfObjectMatchesObjectNumberAndObidOfADbEntryAndNoLanguageIsSet() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'openimmo_obid' => 'test-obid',
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid . ' AND title="new title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseUpdatesEntryIfObjectMatchesObjectNumberAndLanguageOfADbEntryAndNoObidIsSet() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$objectNumber,
				'language' => 'foo',
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid . ' AND title="new title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectNumberButNoLanguageExistsInTheDbAndLanguageIsSet() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
			)
		);
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
				'language' => 'bar',
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$objectNumber . '" AND title="this is a title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectNumberButNoObidExistsInTheDbAndObidIsSet() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
			)
		);
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
				'openimmo_obid' => 'another-test-obid',
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$objectNumber . '" AND title="this is a title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectNumberButObidExistsInTheDbAndObidIsSet() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
			)
		);
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'this is a title',
				'object_number' => self::$objectNumber,
				'openimmo_obid' => 'another-test-obid',
			)
		);
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . self::$objectNumber . '" AND title="this is a title"'
			)
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testWriteToDatabaseCreatesNewEntryIfObjectMatchesLanguageAndObidOfADbEntryButNotObjectNumber() {
		$this->fixture->loadRealtyObject(
			array(
				'title' => 'new title',
				'object_number' => self::$otherObjectNumber,
				'openimmo_obid' => 'test-obid',
				'language' => 'foo',
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'language="foo" AND openimmo_obid="test-obid"'
			)
		);
	}

	public function testWriteToDatabaseReturnsRequiredFieldsMessageIfTheRequiredFieldsAreNotSet() {
		$this->fixture->setRequiredFields(array('city'));
		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$otherObjectNumber,
				'title' => 'new entry',
			)
		);

		$this->assertEquals(
			'message_fields_required',
			$this->fixture->writeToDatabase()
		);
	}

	public function testWriteToDatabaseReturnsObjectNotLoadedMessageIfTheCurrentObjectIsEmpty() {
		$this->fixture->loadRealtyObject(array());

		$this->assertEquals(
			'message_object_not_loaded',
			$this->fixture->writeToDatabase()
		);
	}

	public function testWriteToDatabaseCreatesNewDatabaseEntry() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number="' . (self::$otherObjectNumber) . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testWriteToDatabaseCreatesNewDatabaseEntryForObjectWithQuotedData() {
		$this->fixture->loadRealtyObject(
			array(
				'object_number' => '"' . self::$otherObjectNumber . '"',
				'openimmo_obid' => '"foo"',
				'title' => '"bar"'
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS, 'uid=' . $this->fixture->getUid()
			)
		);
	}

	public function testWriteToDatabaseCreatesNewRealtyRecordWithRealtyRecordPid() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->pageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_OBJECTS,
				'object_number = ' . self::$otherObjectNumber .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testWriteToDatabaseCanOverrideDefaultPidForNewRecords() {
		$systemFolderPid = $this->testingFramework->createSystemFolder();

		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase($systemFolderPid);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$otherObjectNumber .
					' AND pid=' . $systemFolderPid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testWriteToDatabaseUpdatesAndCannotOverrideDefaultPid() {
		$systemFolderPid = $this->testingFramework->createSystemFolder();
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber)
		);
		$this->fixture->writeToDatabase($systemFolderPid);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid='.$this->objectUid
				.' AND pid='.$this->pageUid
			)
		);
	}

	public function testWriteToDatabaseCreatesNewCityRecordWithAuxiliaryRecordPid() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->otherPageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_CITIES,
				'title = "foo"' .
					tx_oelib_db::enableFields(REALTY_TABLE_CITIES)
			)
		);
	}

	public function testWriteToDatabaseCreatesNewCityRecordWithRealtyRecordPidIfAuxiliaryRecordPidNotSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForAuxiliaryRecords', 0);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->pageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_CITIES,
				'title = "foo"' .
					tx_oelib_db::enableFields(REALTY_TABLE_CITIES)
			)
		);
	}

	public function testGetPropertyWithNonExistingKeyWhenObjectLoaded() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			'',
			$this->fixture->getProperty('foo')
		);
	}

	public function testGetPropertyWithExistingKeyWhenObjectLoaded() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->set('city', 'foo');

		$this->assertEquals(
			'foo',
			$this->fixture->getProperty('city')
		);
	}

	public function testSetPropertyWhenKeyExists() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');

		$this->assertEquals(
			'foo',
			$this->fixture->getProperty('city')
		);
	}

	public function testSetPropertyWhenValueOfBoolean() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('pets', TRUE);

		$this->assertEquals(
			TRUE,
			$this->fixture->getProperty('pets')
		);
	}

	public function testSetPropertyWhenValueIsNumber() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('zip', 100);

		$this->assertEquals(
			100,
			$this->fixture->getProperty('zip')
		);
	}

	public function testSetPropertyWhenKeyNotExists() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('foo', 'bar');

		$this->assertEquals(
			'',
			$this->fixture->getProperty('foo')
		);
	}

	public function testSetPropertyDoesNotSetTheValueWhenTheValuesTypeIsInvalid() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('pets', array('bar'));

		$this->assertEquals(
			$this->objectUid,
			$this->fixture->getUid()
		);
	}

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function setPropertyKeySetToUidThrowsException() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->fixture->setProperty('uid', 12345);
	}

	public function testIsRealtyObjectDataEmptyReturnsFalseIfObjectLoaded() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->assertFalse(
			$this->fixture->isRealtyObjectDataEmpty()
		);
	}

	public function testIsRealtyObjectDataEmptyReturnsTrueIfNoObjectLoaded() {
		$this->assertTrue(
			$this->fixture->isRealtyObjectDataEmpty()
		);
	}

	public function testCheckForRequiredFieldsIfNoFieldsAreRequired() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			array(),
			$this->fixture->checkForRequiredFields()
		);
	}

	public function testCheckForRequiredFieldsIfAllFieldsAreSet() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setRequiredFields(
			array(
				'title',
				'object_number'
			)
		);

		$this->assertEquals(
			array(),
			$this->fixture->checkForRequiredFields()
		);
	}

	public function testCheckForRequiredFieldsIfOneRequriredFieldIsMissing() {
		$this->fixture->loadRealtyObject(array('title' => 'foo'));
		$this->fixture->setRequiredFields(array('object_number'));

		$this->assertContains(
			'object_number',
			$this->fixture->checkForRequiredFields()
		);
	}

	public function testPrepareInsertionAndInsertRelationsWritesUidOfInsertedPropertyToRealtyObjectData() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertTrue(
			$this->fixture->getProperty('city') > 0
		);
	}

	public function testPrepareInsertionAndInsertRelationsInsertsPropertyIntoItsTable() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	public function testPrepareInsertionAndInsertRelationsInsertsPropertyWithQuotesInTitleIntoItsTable() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo "bar"');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	public function testPrepareInsertionAndInsertRelationsCreatesRelationToAlreadyExistingPropertyWithMatchingPid() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => 'test city', 'pid' => $this->otherPageUid)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'test city');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			$cityUid,
			$this->fixture->getProperty('city')
		);
	}

	public function testPrepareInsertionAndInsertRelationsCreatesRelationToAlreadyExistingPropertyWithMismatchingPid() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid + 1);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => 'test city', 'pid' => $this->otherPageUid)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'test city');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			$cityUid,
			$this->fixture->getProperty('city')
		);
	}

	public function testPrepareInsertionAndInsertDoesNotUpdateThePidOfAnAlreadyExistingPropertyForMismatchingPids() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid + 1);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => 'test city', 'pid' => $this->otherPageUid)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'test city');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				 REALTY_TABLE_CITIES,
				'uid=' . $cityUid . ' AND pid='. $this->otherPageUid
			)
		);
	}

	public function testPrepareInsertionAndInsertRelationsDoesNotCreateARecordForAnInteger() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', '12345');
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	public function testPrepareInsertionAndInsertRelationsDoesNotCreateARecordForZeroPropertyFromTheDatabase() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	public function testPrepareInsertionAndInsertRelationsDoesNotCreateARecordForZeroPropertyFromLoadedArray() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber, 'city' => 0)
		);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	public function testPrepareInsertionAndInsertRelationsReturnsZeroForEmptyPropertyFetchedFromLoadedArray() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber, 'city' => '')
		);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	public function testPrepareInsertionAndInsertRelationsReturnsZeroIfThePropertyNotExists() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$objectNumber)
		);
		$this->fixture->prepareInsertionAndInsertRelations();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(REALTY_TABLE_CITIES)
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordInsertsNewEntryWithParentUid() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('image' => 'foo.jpg'),
			tx_oelib_db::selectSingle(
				'image',
				REALTY_TABLE_IMAGES,
				'object = ' . $this->objectUid
			)
		);
	}

	public function testInsertImageEntriesInsertsNewImageWithCaptionWithQuotationMarks() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo "bar"', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('image' => 'foo.jpg'),
			tx_oelib_db::selectSingle(
				'image',
				REALTY_TABLE_IMAGES,
				'object = ' . $this->objectUid
			)
		);
	}

	/**
	 * @test
	 */
	public function insertImageEntriesInsertsImageWithEmptyTitleIfNoTitleIsSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('caption' => '', 'image' => 'foo.jpg'),
			tx_oelib_db::selectSingle(
				'caption, image',
				REALTY_TABLE_IMAGES,
				'object = ' . $this->objectUid
			)
		);
	}

	public function testDeleteFromDatabaseRemovesRelatedImage() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();
		$this->fixture->setToDeleted();
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES, 'deleted = 1'
			)
		);
		$this->assertEquals(
			'message_deleted_flag_causes_deletion',
			$message
		);
	}

	public function testDeleteFromDatabaseRemovesSeveralRelatedImages() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo1', 'foo1.jpg');
		$this->fixture->addImageRecord('foo2', 'foo2.jpg');
		$this->fixture->addImageRecord('foo3', 'foo3.jpg');
		$this->fixture->writeToDatabase();
		$this->fixture->setToDeleted();
		$message = $this->fixture->writeToDatabase();

		$this->assertEquals(
			3,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES, 'deleted = 1'
			)
		);
		$this->assertEquals(
			'message_deleted_flag_causes_deletion',
			$message
		);
	}

	public function testWriteToDatabaseInsertsCorrectPageUidForNewRecord() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			array('pid' => $this->pageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_OBJECTS,
				'object_number = "' . self::$otherObjectNumber . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testWriteToDatabaseInsertsCorrectPageUidForNewRecordIfOverridePidIsSet() {
		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber)
		);
		$this->fixture->writeToDatabase($this->otherPageUid);

		$this->assertEquals(
			array('pid' => $this->otherPageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_OBJECTS,
				'object_number = "' . self::$otherObjectNumber . '"' .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testImagesReceiveTheCorrectPageUidIfOverridePidIsSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(array('caption' => 'foo', 'image' => 'bar'))
			)
		);
		$this->fixture->writeToDatabase($this->otherPageUid);

		$this->assertEquals(
			array('pid' => $this->otherPageUid),
			tx_oelib_db::selectSingle(
				'pid',
				REALTY_TABLE_IMAGES,
				'is_dummy_record = 1'
			)
		);
	}

	public function testUpdatingAnExistingRecordDoesNotChangeThePageUid() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('title', 'new title');

		tx_oelib_configurationProxy::getInstance('realty')->
			setAsInteger('pidForRealtyObjectsAndImages', $this->otherPageUid);
		$message = $this->fixture->writeToDatabase();

		$result = tx_oelib_db::selectSingle(
			'pid',
			REALTY_TABLE_OBJECTS,
			'object_number = "' . self::$objectNumber . '"' .
				tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
		);

		$this->assertEquals(
			array('pid' => $this->pageUid),
			$result
		);
		$this->assertEquals(
			'',
			$message
		);
	}

	public function testCreateANewRealtyRecordAlthoughTheSameRecordWasSetToDeletedInTheDatabase() {
		$uid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => self::$otherObjectNumber,
				'deleted' => 1,
			)
		);

		$this->fixture->loadRealtyObject(
			array('object_number' => self::$otherObjectNumber), TRUE
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$otherObjectNumber .
					' AND uid!=' . $uid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testWriteToDatabaseDeletesAnExistingNonHiddenRealtyRecordIfTheDeletedFlagIsSet() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setToDeleted();
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testWriteToDatabaseDeletesAnExistingHiddenRealtyRecordIfTheDeletedFlagIsSet() {
		$this->fixture->loadRealtyObject($this->objectUid, TRUE);
		$this->fixture->setProperty('hidden', 1);
		$this->fixture->writeToDatabase();

		$this->fixture->setToDeleted();
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid=' . $this->objectUid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS, 1)
			)
		);
	}

	public function testDeleteAnExistingRealtyRecordAndImportItAgainIfTheDeletedFlagIsSetExplicitly() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setToDeleted();
		$this->fixture->writeToDatabase();

		$realtyObject = new tx_realty_Model_RealtyObjectChild(TRUE);
		$realtyObject->setRequiredFields(array());
		$realtyObject->loadRealtyObject(
			array('object_number' => self::$objectNumber, 'deleted' => 0), TRUE
		);
		$realtyObject->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$objectNumber .
					' AND uid!=' . $this->objectUid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testDeleteAnExistingRealtyRecordAndImportItAgainIfTheDeletedFlagIsNotSetExplicitly() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setToDeleted();
		$this->fixture->writeToDatabase();

		$realtyObject = new tx_realty_Model_RealtyObjectChild(TRUE);
		$realtyObject->setRequiredFields(array());
		$realtyObject->loadRealtyObject(
			array('object_number' => self::$objectNumber), TRUE
		);
		$realtyObject->writeToDatabase();
		$realtyObject->__destruct();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'object_number=' . self::$objectNumber .
					' AND uid!=' . $this->objectUid .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testLoadingAnExistingRecordWithAnImageAndWritingItToTheDatabaseDoesNotDuplicateTheImage() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('object' => $this->objectUid, 'image' => 'test.jpg')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->writeToDatabase();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES, 'deleted = 0 AND image="test.jpg"'
			)
		);
	}

	public function testLoadingAnExistingRecordWithAnImageByArrayAndWritingItWithAnotherImageToTheDatabaseDeletesTheExistingImage() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('object' => $this->objectUid, 'image' => 'test.jpg')
		);
		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$objectNumber,
				'images' => array(
					array('caption' => 'test', 'image' => 'test2.jpg')
				)
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES, 'deleted = 1 AND image="test.jpg"'
			)
		);
	}

	public function testImportARecordWithAnImageThatAlreadyExistsForAnotherRecordDoesNotChangeTheOriginalObjectUid() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'object' => $this->objectUid,
				'image' => 'test.jpg',
				'caption' => 'test',
			)
		);
		$this->fixture->loadRealtyObject(
			array(
				'object_number' => self::$otherObjectNumber,
				'images' => array(
					array('caption' => 'test', 'image' => 'test.jpg')
				)
			)
		);
		$this->fixture->writeToDatabase();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES,
				'object=' . $this->objectUid . ' AND image="test.jpg"'
			)
		);
	}

	public function testRecreateAnAuxiliaryRecord() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_CITIES);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array(
				'title' => 'foo',
				'deleted' => 1,
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'foo');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_CITIES,
				'title="foo" AND uid!=' . $cityUid .
					tx_oelib_db::enableFields(REALTY_TABLE_CITIES)
			)
		);
	}


	////////////////////////////////////
	// Tests concerning addImageRecord
	////////////////////////////////////

	public function testAddImageRecordForLoadedObject() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');

		$this->assertEquals(
			'foo',
			$this->fixture->getImages()->first()->getTitle()
		);
	}

	public function testAddImageRecordForLoadedObjectReturnsKeyWhereTheRecordIsStored() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			0,
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException BadMethodCallException
	 */
	public function addImageRecordForNoObjectLoadedThrowsException() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->addImageRecord('foo', 'foo.jpg');
	}

	public function testAddImagesRecordsUpdatesTheNumberOfCurrentlyAppendedImagesForTheRealtyObject() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo1', 'foo1.jpg');
		$this->fixture->addImageRecord('foo2', 'foo2.jpg');
		$this->fixture->addImageRecord('foo3', 'foo3.jpg');

		$this->assertEquals(
			3,
			$this->fixture->getProperty('images')
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordByDefaultSetsPositionToZero() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');

		$this->assertEquals(
			0,
			$this->fixture->getImages()->first()->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordCanSetPositionZero() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg', 0);

		$this->assertEquals(
			0,
			$this->fixture->getImages()->first()->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordCanSetPositionOne() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg', 1);

		$this->assertEquals(
			1,
			$this->fixture->getImages()->first()->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordCanSetPositionFour() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg', 4);

		$this->assertEquals(
			4,
			$this->fixture->getImages()->first()->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordByDefaultSetsEmptyThumbnailFileName() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');

		$this->assertEquals(
			'',
			$this->fixture->getImages()->first()->getThumbnailFileName()
		);
	}

	/**
	 * @test
	 */
	public function addImageRecordCanSetNonEmptyThumbnailFileName() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg', 0, 'bar.jpg');

		$this->assertEquals(
			'bar.jpg',
			$this->fixture->getImages()->first()->getThumbnailFileName()
		);
	}


	//////////////////////////////////////////////
	// Tests concerning markImageRecordAsDeleted
	//////////////////////////////////////////////

	public function testMarkImageRecordAsDeletedUpdatesTheNumberOfCurrentlyAppendedImagesForTheRealtyObject() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo1', 'foo1.jpg');
		$this->fixture->addImageRecord('foo2', 'foo2.jpg');
		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);

		$this->assertEquals(
			2,
			$this->fixture->getProperty('images')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException BadMethodCallException
	 */
	public function markImageRecordAsDeletedForNoObjectLoadedThrowsException() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException tx_oelib_Exception_NotFound
	 */
	public function markImageRecordAsDeletedForNonExistingRecordThrowsException() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg') + 1
		);
	}


	/////////////////////////////////////////////////
	// Tests concerning writeToDatabase with images
	/////////////////////////////////////////////////

	public function testWriteToDatabaseMarksImageRecordToDeleteAsDeleted() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$imageUid = $this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'object' => $this->objectUid
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->markImageRecordAsDeleted(0);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'uid='.$imageUid.' AND deleted=1'
			)
		);
	}

	public function testWriteToDatabaseCreatesNewImageRecordIfTheSameRecordExistsButIsDeleted() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg',
				'object' => $this->objectUid,
				'deleted' => 1,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			2,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES, 'image = "foo.jpg"'
			)
		);
	}

	public function testWriteToDatabaseDeletesExistingImageFromTheFileSystem() {
		$fileName = $this->testingFramework->createDummyFile('foo.jpg');
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('images' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'image' => basename($fileName),
				'object' => $this->objectUid
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->markImageRecordAsDeleted(0);
		$this->fixture->writeToDatabase();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	public function testWriteToDatabaseNotAddsImageRecordWithDeletedFlagSet() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->markImageRecordAsDeleted(
			$this->fixture->addImageRecord('foo', 'foo.jpg')
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES, 'deleted = 1'
			)
		);
	}

	public function testImportANewRecordWithImagesAndTheDeletedFlagBeingSetReturnsMarkedAsDeletedMessageKey() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject(
			array('object_number' => 'foo-bar', 'deleted' => 1)
		);
		$this->fixture->addImageRecord('foo', 'foo.jpg');

		$this->assertEquals(
			'message_deleted_flag_set',
			$this->fixture->writeToDatabase()
		);
	}


	/////////////////////////////////
	// Tests concerning addDocument
	/////////////////////////////////

	/**
	 * @test
	 */
	public function numberOfAppendedDocumentsInitiallyIsZero() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('documents')
		);
	}

	/**
	 * @test
	 */
	public function addDocumentMakesDocumentAvailableViaGetDocuments() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addDocument('foo', 'foo.pdf');

		$this->assertEquals(
			'foo',
			$this->fixture->getDocuments()->first()->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function addDocumentForFirstDocumentsReturnsZeroIndex() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			0,
			$this->fixture->addDocument('foo', 'foo.pdf')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException BadMethodCallException
	 */
	public function addDocumentForNoObjectLoadedThrowsException() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->addDocument('foo', 'foo.pdf');
	}

	/**
	 * @test
	 */
	public function addDocumentUpdatesTheNumberOfAppendedDocuments() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addDocument('foo1', 'foo1.pdf');
		$this->fixture->addDocument('foo2', 'foo2.pdf');
		$this->fixture->addDocument('foo3', 'foo3.pdf');

		$this->assertEquals(
			3,
			$this->fixture->getProperty('documents')
		);
	}


	////////////////////////////////////
	// Tests concerning deleteDocument
	////////////////////////////////////

	/**
	 * @test
	 */
	public function deleteDocumentUpdatesTheNumberOfAppendedDocuments() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addDocument('foo1', 'foo1.pdf');
		$this->fixture->addDocument('foo2', 'foo2.pdf');
		$this->fixture->deleteDocument(
			$this->fixture->addDocument('foo', 'foo.pdf')
		);

		$this->assertEquals(
			2,
			$this->fixture->getProperty('documents')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException BadMethodCallException
	 */
	public function deleteDocumentForNoObjectLoadedThrowsException() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->deleteDocument(
			$this->fixture->addDocument('foo', 'foo.pdf')
		);
	}

	/**
	 * @test
	 *
	 * @expectedException tx_oelib_Exception_NotFound
	 */
	public function deleteDocumentForNonExistingRecordThrowsException() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject($this->objectUid);
		$documentKey = $this->fixture->addDocument('foo', 'foo.pdf') + 1;

		$this->fixture->deleteDocument($documentKey);
	}


	////////////////////////////////////////////////////
	// Tests concerning writeToDatabase with documents
	////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function writeToDatabaseMarksDocumentRecordToDeleteAsDeleted() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('documents' => 1)
		);
		$documentUid = $this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'foo',
				'filename' => 'foo.pdf',
				'object' => $this->objectUid
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->deleteDocument(0);
		$this->fixture->writeToDatabase();

		$this->assertTrue(
			$this->testingFramework->existsRecord(
				'tx_realty_documents',
				'uid = ' . $documentUid . ' AND deleted = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseCreatesNewDocumentRecordIfTheSameRecordExistsButIsDeleted() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('documents' => 1)
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'foo',
				'filename' => 'foo.pdf',
				'object' => $this->objectUid,
				'deleted' => 1,
			)
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->addDocument('foo', 'foo.pdf');
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_documents', 'filename = "foo.pdf" AND deleted = 0'
			)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseDeletesExistingDocumentFromFileSystem() {
		$fileName = $this->testingFramework->createDummyFile('foo.pdf');
		$this->testingFramework->changeRecord(
			'tx_realty_objects', $this->objectUid, array('documents' => 1)
		);
		$this->testingFramework->createRecord(
			'tx_realty_documents',
			array(
				'title' => 'foo',
				'filename' => basename($fileName),
				'object' => $this->objectUid,
			)
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->deleteDocument(0);
		$this->fixture->writeToDatabase();

		$this->assertFalse(
			file_exists($fileName)
		);
	}

	/**
	 * @test
	 */
	public function writeToDatabaseNotAddsDeletedDocumentRecord() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->deleteDocument(
			$this->fixture->addDocument('foo', 'foo.pdf')
		);
		$this->fixture->writeToDatabase();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				'tx_realty_documents', 'deleted = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function importANewRecordWithDocumentsAndTheDeletedFlagBeingSetReturnsMarkedAsDeletedMessageKey() {
		$this->testingFramework->markTableAsDirty('tx_realty_documents');

		$this->fixture->loadRealtyObject(
			array('object_number' => 'foo-bar', 'deleted' => 1)
		);
		$this->fixture->addDocument('foo', 'foo.pdf');

		$this->assertEquals(
			'message_deleted_flag_set',
			$this->fixture->writeToDatabase()
		);
	}


	/////////////////////////////////////
	// Tests for processing owner data.
	/////////////////////////////////////

	public function testUidOfFeUserWithMatchingAnidIsAddedAsOwnerForExistingObjectIfAddingTheOwnerIsAllowed() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			$feUserUid,
			$this->fixture->getProperty('owner')
		);
	}

	public function testUidOfFeUserWithMatchingAnidIsAddedAsOwnerForNewObjectIfAddingTheOwnerIsAllowed() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject(array('openimmo_anid' => 'test anid'));
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			$feUserUid,
			$this->fixture->getProperty('owner')
		);
	}

	public function testUidOfFeUserWithMatchingAnidIsNotAddedAsOwnerIfThisIsForbidden() {
		$this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, FALSE);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('owner')
		);
	}

	public function testNoOwnerIsAddedForARealtyRecordWithoutOpenImmoAnid() {
		$this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('owner')
		);
	}

	public function testOwnerIsNotChangedAlthoughTheAnidOfARecordIsUpdatedAndDoesNotMatchAnymore() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid 1')
		);

		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid 1');
		$this->fixture->writeToDatabase(0, TRUE);
		$this->fixture->setProperty('openimmo_anid', 'test anid 2');
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			$feUserUid,
			$this->fixture->getProperty('owner')
		);
		$this->assertEquals(
			'test anid 2',
			$this->fixture->getProperty('openimmo_anid')
		);
	}

	public function testOwnerIsNotChangedAlthoughTheAnidOfARecordIsUpdatedAndMatchesAnotherFeUser() {
		$feUserGroup = $this->testingFramework->createFrontEndUserGroup();
		$uidOfFeUserOne = $this->testingFramework->createFrontEndUser(
			$feUserGroup, array('tx_realty_openimmo_anid' => 'test anid 1')
		);
		$this->testingFramework->createFrontEndUser(
			$feUserGroup, array('tx_realty_openimmo_anid' => 'test anid 2')
		);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid 1');
		$this->fixture->writeToDatabase(0, TRUE);
		$this->fixture->setProperty('openimmo_anid', 'test anid 2');
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			$uidOfFeUserOne,
			$this->fixture->getProperty('owner')
		);
		$this->assertEquals(
			'test anid 2',
			$this->fixture->getProperty('openimmo_anid')
		);
	}

	public function testUseFeUserDataFlagIsSetIfThisOptionIsEnabledByConfiguration() {
		$this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsBoolean(
				'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
			);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			1,
			$this->fixture->getProperty('contact_data_source')
		);
	}

	public function testUseFeUserDataFlagIsNotSetIfThisOptionIsDisabledByConfiguration() {
		$this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsBoolean(
				'useFrontEndUserDataAsContactDataForImportedRecords', FALSE
			);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('openimmo_anid', 'test anid');
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('contact_data_source')
		);
	}

	public function testUseFeUserDataFlagIsNotSetIfNoOwnerWasSetAlthoughOptionIsEnabledByConfiguration() {
		$this->testingFramework->createFrontEndUser(
			'', array('tx_realty_openimmo_anid' => 'test anid')
		);
		tx_oelib_configurationProxy::getInstance('realty')->
			setAsBoolean(
				'useFrontEndUserDataAsContactDataForImportedRecords', TRUE
			);
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->writeToDatabase(0, TRUE);

		$this->assertEquals(
			0,
			$this->fixture->getProperty('contact_data_source')
		);
	}


	//////////////////////////////////
	// Tests for retrieveCoordinates
	//////////////////////////////////

	/**
	 * @test
	 */
	public function retrieveCoordinatesForValidAddressWithCityStringWritesObjectToDatabase() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => 'Bonn',
			'country' => self::DE,
		));
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertGreaterThan(
			0,
			$this->fixture->getUid()
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesForValidAddressWithCityUidWritesObjectToDatabase() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => self::DE,
		));
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertGreaterThan(
			0,
			$this->fixture->getUid()
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesForValidAddressWithCityUidAsStringWritesObjectToDatabase() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => (string) $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => self::DE,
		));
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertGreaterThan(
			0,
			$this->fixture->getUid()
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesForInvalidAddressDoesNotWriteObjectToDatabase() {
		tx_realty_googleMapsLookup::getInstance($this->getMock('tx_oelib_templatehelper'))->clearCoordinates();
		$this->fixture->loadRealtyObject(array(
			'street' => 'asgtqbt4q3 mkb 431',
			'city' => 'Allk3q4öklbj',
			'country' => self::DE,
		));
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertSame(
			0,
			$this->fixture->getUid()
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesForInvalidAddressWithoutCoordinatesReturnsEmptyArray() {
		tx_realty_googleMapsLookup::getInstance($this->getMock('tx_oelib_templatehelper'))->clearCoordinates();
		$this->fixture->loadRealtyObject(array(
			'street' => 'asgtqbt4q3 mkb 431',
			'city' => 'Allk3q4öklbj',
			'country' => self::DE,
			'has_coordinates' => 0,
			'coordinates_problem' => 0,
		));

		$this->assertSame(
			array(),
			$this->fixture->retrieveCoordinates($this->templateHelper)
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesForInvalidAddressSetsGeoErrorToTrue() {
		tx_realty_googleMapsLookup::getInstance($this->getMock('tx_oelib_templatehelper'))->clearCoordinates();
		$this->fixture->loadRealtyObject(array(
			'street' => 'asgtqbt4q3 mkb 431',
			'city' => 'Allk3q4öklbj',
			'country' => self::DE,
		));
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertTrue(
			$this->fixture->hasGeoError()
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesForInvalidAddressAndExistingCoordinatesDoesNotChangeExistingCoordinates() {
		$latitude = 2.3;
		$longitude = 3.4;

		tx_realty_googleMapsLookup::getInstance($this->getMock('tx_oelib_templatehelper'))->clearCoordinates();
		$this->fixture->loadRealtyObject(array(
			'street' => 'asgtqbt4q3 mkb 431',
			'city' => 'Allk3q4öklbj',
			'country' => self::DE,
			'has_coordinates' => TRUE,
			'latitude' => $latitude,
			'longitude' => $longitude,
		));
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertSame(
			array('latitude' => $latitude, 'longitude' => $longitude),
			$this->fixture->getGeoCoordinates($this->templateHelper)
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesForValidAddressAndShowAddressTrueRetrievesExactCoordinates() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => self::DE,
			'show_address' => TRUE,
		));
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertSame(
			array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE),
			$this->fixture->getGeoCoordinates($this->templateHelper)
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesForValidAddressAndShowAddressTrueSetsHasCoordinatesToTrue() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => self::DE,
			'show_address' => TRUE,
		));
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertTrue(
			$this->fixture->hasGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesForValidAddressAndShowAddressFalseRetrievesCoordinates() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => self::DE,
			'show_address' => FALSE,
		));
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertSame(
			array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE),
			$this->fixture->getGeoCoordinates($this->templateHelper)
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesForValidAddressAndShowAddressFalseSetsHasCoordinatesToTrue() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => self::DE,
			'show_address' => FALSE,
		));
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertTrue(
			$this->fixture->hasGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function retrieveCoordinatesDoesNotChangeImagesPidWhenAddingCoordinatesToTheDatabase() {
		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);

		$this->fixture->loadRealtyObject(array(
			'street' => 'Am Hof 1',
			'zip' => '53111',
			'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES, array('title' => 'Bonn')),
			'country' => self::DE,
		));
		$this->fixture->addImageRecord('foo', 'foo.jpg');
		$this->fixture->writeToDatabase($this->otherPageUid);
		$this->fixture->retrieveCoordinates($this->templateHelper);

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES,
				'image="foo.jpg" AND pid=' . $this->otherPageUid
			)
		);
	}


	////////////////////////////
	// Tests concerning getUid
	////////////////////////////

	public function testGetUidReturnsZeroForObjectWithoutUid() {
		$realtyObject = new tx_realty_Model_RealtyObjectChild(TRUE);

		$this->assertEquals(
			0,
			$realtyObject->getUid()
		);

		$realtyObject->__destruct();
	}

	public function testGetUidReturnsCurrentUidForObjectWithUid() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			$this->objectUid,
			$this->fixture->getUid()
		);
	}


	//////////////////////////////
	// Tests concerning getTitle
	//////////////////////////////

	public function testGetTitleReturnsEmptyStringForObjectWithoutTitle() {
		$realtyObject = new tx_realty_Model_RealtyObjectChild(TRUE);
		$realtyObject->loadRealtyObject(0);

		$this->assertEquals(
			'',
			$realtyObject->getTitle()
		);

		$realtyObject->__destruct();
	}

	public function testGetTitleReturnsFullTitleForObjectWithTitle() {
		$this->fixture->loadRealtyObject(
			array('title' => 'foo title filltext-filltext-filltext-filltext')
		);

		$this->assertEquals(
			'foo title filltext-filltext-filltext-filltext',
			$this->fixture->getTitle()
		);
	}


	/////////////////////////////////////
	// Tests concerning getCroppedTitle
	/////////////////////////////////////

	public function testGetCroppedTitleReturnsEmptyStringForObjectWithoutTitle() {
		$realtyObject = new tx_realty_Model_RealtyObjectChild(TRUE);
		$realtyObject->loadRealtyObject(0);

		$this->assertEquals(
			'',
			$realtyObject->getCroppedTitle()
		);

		$realtyObject->__destruct();
	}

	public function testGetCroppedTitleReturnsFullShortTitleForObjectWithTitle() {
		$this->fixture->loadRealtyObject(
			array('title' => '12345678901234567890123456789012')
		);

		$this->assertEquals(
			'12345678901234567890123456789012',
			$this->fixture->getCroppedTitle()
		);
	}

	public function testGetCroppedTitleReturnsLongTitleCroppedAtDefaultCropSize() {
		$this->fixture->loadRealtyObject(
			array('title' => '123456789012345678901234567890123')
		);

		$this->assertEquals(
			'12345678901234567890123456789012…',
			$this->fixture->getCroppedTitle()
		);
	}

	public function testGetCroppedTitleReturnsLongTitleCroppedAtGivenCropSize() {
		$this->fixture->loadRealtyObject(
			array('title' => '123456789012345678901234567890123')
		);

		$this->assertEquals(
			'1234567890…',
			$this->fixture->getCroppedTitle(10)
		);
	}

	public function testGetCroppedTitleWithZeroGivenReturnsLongTitleCroppedAtDefaultLength() {
		$this->fixture->loadRealtyObject(
			array('title' => '123456789012345678901234567890123')
		);

		$this->assertEquals(
			'12345678901234567890123456789012…',
			$this->fixture->getCroppedTitle(0)
		);
	}

	public function testGetCroppedTitleWithStringGivenReturnsLongTitleCroppedAtDefaultLength() {
		$this->fixture->loadRealtyObject(
			array('title' => '123456789012345678901234567890123')
		);

		$this->assertEquals(
			'12345678901234567890123456789012…',
			$this->fixture->getCroppedTitle('foo')
		);
	}


	/////////////////////////////////////////////
	// Tests concerning getForeignPropertyField
	/////////////////////////////////////////////

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function getForeignPropertyFieldForNonAllowedFieldThrowsException() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->getForeignPropertyField('floor');
	}

	public function testGetForeignPropertyFieldReturnsNonNumericFieldContentForAllowedField() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('city', 'test city');

		$this->assertEquals(
			'test city',
			$this->fixture->getForeignPropertyField('city')
		);
	}

	public function testGetForeignPropertyFieldReturnsEmptyStringIfThereIsNoPropertySetForAllowedField() {
		$this->fixture->loadRealtyObject($this->objectUid);

		$this->assertEquals(
			'',
			$this->fixture->getForeignPropertyField('city')
		);
	}

	public function testGetForeignPropertyFieldReturnsACitysTitle() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('title' => 'foo')
		);
		$this->fixture->setProperty('city', $cityUid);

		$this->assertEquals(
			'foo',
			$this->fixture->getForeignPropertyField('city')
		);
	}

	public function testGetForeignPropertyFieldReturnsADistrictsTitle() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$districtUid = $this->testingFramework->createRecord(
			REALTY_TABLE_DISTRICTS, array('title' => 'foo')
		);
		$this->fixture->setProperty('district', $districtUid);

		$this->assertEquals(
			'foo',
			$this->fixture->getForeignPropertyField('district')
		);
	}

	public function testGetForeignPropertyFieldReturnsACountrysShortLocalName() {
		$this->fixture->loadRealtyObject($this->objectUid);
		$this->fixture->setProperty('country', self::DE);

		$this->assertEquals(
			'Deutschland',
			$this->fixture->getForeignPropertyField('country', 'cn_short_local')
		);
	}


	//////////////////////////////////////
	// Tests concerning getAddressAsHtml
	//////////////////////////////////////

	public function testGetAddressAsHtmlReturnsFormattedPartlyAddressIfAllDataProvidedAndShowAddressFalse() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'12345 Test Town District<br />Deutschland',
			$this->fixture->getAddressAsHtml()
		);
	}

	public function testGetAddressAsHtmlReturnsFormattedCompleteAddressIfAllDataProvidedAndShowAddressTrue() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'Main Street<br />12345 Test Town District<br />Deutschland',
			$this->fixture->getAddressAsHtml()
		);
	}

	public function testGetAddressAsHtmlReturnsFormattedAddressForAllDataButCountryProvidedAndShowAddressTrue() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
		));

		$this->assertEquals(
			'Main Street<br />12345 Test Town District',
			$this->fixture->getAddressAsHtml()
		);
	}

	public function testGetAddressAsHtmlReturnsFormattedAddressForAllDataButStreetProvidedAndShowAddressTrue() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'12345 Test Town District<br />Deutschland',
			$this->fixture->getAddressAsHtml()
		);
	}

	public function testGetAddressAsHtmlReturnsFormattedAddressForOnlyStreetProvidedAndShowAddressTrue() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1, 'street' => 'Main Street',
		));

		$this->assertEquals(
			'Main Street<br />',
			$this->fixture->getAddressAsHtml()
		);
	}


	////////////////////////////////////////////
	// Tests concerning getAddressAsSingleLine
	////////////////////////////////////////////

	public function test_getAddressAsSingleLine_ForShowAddressFalse_ReturnsAddressWithoutStreet() {
		$this->fixture->loadRealtyObject(array(
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'12345 Test Town District, Deutschland',
			$this->fixture->getAddressAsSingleLine()
		);
	}

	public function test_getAddressAsSingleLine_ForShowAddressTrue_ReturnsCompleteAddress() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'Main Street, 12345 Test Town District, Deutschland',
			$this->fixture->getAddressAsSingleLine()
		);
	}

	public function test_getAddressAsSingleLine_ForNoCountrySetAndShowAddressTrue_ReturnsAddressWithoutCountry() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
		));

		$this->assertEquals(
			'Main Street, 12345 Test Town District',
			$this->fixture->getAddressAsSingleLine()
		);
	}

	public function test_getAddressAsSingleLine_ForNoStreetSetAndShowAddressTrue_ReturnsAddressWithoutStreet() {
			$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertEquals(
			'12345 Test Town District, Deutschland',
			$this->fixture->getAddressAsSingleLine()
		);
	}

	public function test_getAddressAsSingleLine_ForShowAddressTrue_ReturnsCompleteAddressWithoutHtmlTags() {
		$this->fixture->loadRealtyObject(array(
			'show_address' => 1,
			'street' => 'Main Street',
			'zip' => '12345',
			'city' => 'Test Town',
			'district' => 'District',
			'country' => self::DE,
		));

		$this->assertNotContains(
			'<',
			$this->fixture->getAddressAsSingleLine()
		);
	}


	/////////////////////////////
	// Tests for isAllowedKey()
	/////////////////////////////

	public function testIsAllowedKeyReturnsTrueForRealtyObjectField() {
		$this->assertTrue(
			$this->fixture->isAllowedKey('title')
		);
	}

	public function testIsAllowedKeyReturnsFalseForNonRealtyObjectField() {
		$this->assertFalse(
			$this->fixture->isAllowedKey('foo')
		);
	}

	public function testIsAllowedKeyReturnsFalseForEmptyKey() {
		$this->assertFalse(
			$this->fixture->isAllowedKey('')
		);
	}


	//////////////////////////////
	// Tests concerning getOwner
	//////////////////////////////

	public function test_getOwner_ForObjectWithOwner_ReturnsFrontEndUserModel() {
		$this->fixture->loadRealtyObject(
			array(
				'owner' => $this->testingFramework->createFrontEndUser()
			)
		);

		$this->assertTrue(
			$this->fixture->getOwner() instanceof tx_realty_Model_FrontEndUser
		);
	}


	////////////////////////////////////////////
	// Tests concerning the owner data getters
	////////////////////////////////////////////

	////////////////////////////////////
	// Tests concerning getContactName
	////////////////////////////////////

	public function test_getContactName_ForOwnerFromObjectAndWithoutName_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactName()
		);
	}

	public function test_getContactName_ForOwnerFromFeUserWithName_ReturnsOwnerName() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('name' => 'foo')
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getContactName()
		);
	}

	public function test_getContactName_ForOwnerFromObjectWithName_ReturnsOwnerName() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT, array(),
			array('contact_person' => 'foo')
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getContactName()
		);
	}


	////////////////////////////////////////////
	// Tests concerning getContactEMailAddress
	////////////////////////////////////////////

	public function test_getContactEMailAddress_ForOwnerFromFeUserAndWithoutEMailAddress_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactEMailAddress()
		);
	}

	public function test_getContactEMailAddress_ForOwnerFromObjectAndWithoutEMailAddress_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactEMailAddress()
		);
	}

	public function test_getContactEMailAddress_ForOwnerFromFeUserWithEMailAddress_ReturnsEMailAddress() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('email' => 'foo@bar.com')
		);

		$this->assertEquals(
			'foo@bar.com',
			$this->fixture->getContactEMailAddress()
		);
	}

	public function test_getContactEMailAddress_ForOwnerFromObjectWithContactEMailAddress_ReturnsContactEMailAddress() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT, array(),
			array('contact_email' => 'bar@foo.com')
		);

		$this->assertEquals(
			'bar@foo.com',
			$this->fixture->getContactEMailAddress()
		);
	}


	////////////////////////////////////
	// Tests concerning getContactCity
	////////////////////////////////////

	public function test_getContactCity_ForOwnerFromFeUserAndWithoutCity_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactCity()
		);
	}

	public function test_getContactCity_ForOwnerFromObject_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactCity()
		);
	}

	public function test_getContactCity_ForOwnerFromFeUserWithCity_ReturnsCity() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('city' => 'footown')
		);

		$this->assertEquals(
			'footown',
			$this->fixture->getContactCity()
		);
	}


	//////////////////////////////////////
	// Tests concerning getContactStreet
	//////////////////////////////////////

	public function test_getContactStreet_ForOwnerFromFeUserAndWithoutStreet_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactStreet()
		);
	}

	public function test_getContactStreet_ForOwnerFromObject_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactStreet()
		);
	}

	public function test_getContactStreet_ForOwnerFromFeUserWithStreet_ReturnsStreet() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('address' => 'foo')
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getContactStreet()
		);
	}


	///////////////////////////////////
	// Tests concerning getContactZip
	///////////////////////////////////

	public function test_getContactZip_ForOwnerFromFeUserAndWithoutZip_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactZip()
		);
	}

	public function test_getContactZip_ForOwnerFromObject_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactZip()
		);
	}

	public function test_getContactZip_ForOwnerFromFeUserWithZip_ReturnsZip() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('zip' => '12345')
		);

		$this->assertEquals(
			'12345',
			$this->fixture->getContactZip()
		);
	}


	////////////////////////////////////////
	// Tests concerning getContactHomepage
	////////////////////////////////////////

	public function test_getContactHomepage_ForOwnerFromFeUserAndWithoutHomepage_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactHomepage()
		);
	}

	public function test_getContactHomepage_ForOwnerFromObject_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactHomepage()
		);
	}

	public function test_getContactHomepage_ForOwnerFromFeUserWithHomepage_ReturnsHomepage() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('www' => 'www.foo.de')
		);

		$this->assertEquals(
			'www.foo.de',
			$this->fixture->getContactHomepage()
		);
	}


	///////////////////////////////////////////
	// Tests concerning getContactPhoneNumber
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactPhoneNumber_ForOwnerFromFeUserAndWithoutPhoneNumber_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_OWNER_ACCOUNT);

		$this->assertEquals(
			'',
			$this->fixture->getContactPhoneNumber()
		);
	}

	/**
	 * @test
	 */
	public function getContactPhoneNumber_ForOwnerFromObjectAndWithoutPhoneNumber_ReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(REALTY_CONTACT_FROM_REALTY_OBJECT);

		$this->assertEquals(
			'',
			$this->fixture->getContactPhoneNumber()
		);
	}

	/**
	 * @test
	 */
	public function getContactPhoneNumber_ForOwnerFromFeUserWithPhoneNumber_ReturnsPhoneNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array('telephone' => '555-123456')
		);

		$this->assertEquals(
			'555-123456',
			$this->fixture->getContactPhoneNumber()
		);
	}

	/**
	 * @test
	 */
	public function getContactPhoneNumberForOwnerFromObjectWithDirectExtensionPhoneNumberReturnsThisNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT,
			array(),
			array('phone_direct_extension' => '555-123456')
		);

		$this->assertEquals(
			'555-123456',
			$this->fixture->getContactPhoneNumber()
		);
	}

	/**
	 * @test
	 */
	public function getContactPhoneNumberForOwnerFromObjectWithSwitchboardAndWithoutDirectExtensionPhoneNumberReturnsSwitchboardNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT,
			array(),
			array('phone_switchboard' => '555-123456')
		);

		$this->assertEquals(
			'555-123456',
			$this->fixture->getContactPhoneNumber()
		);
	}

	/**
	 * @test
	 */
	public function getContactPhoneNumberForOwnerFromObjectWithSwitchboardAndDirectExtensionPhoneNumberReturnsDirectExtensionNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT,
			array(),
			array(
				'phone_switchboard' => '123456',
				'phone_direct_extension' => '654321'
			)
		);

		$this->assertEquals(
			'654321',
			$this->fixture->getContactPhoneNumber()
		);
	}


	///////////////////////////////////////////
	// Tests concerning getContactSwitchboard
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactSwitchboardForNoSwitchboardSetReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT, array(), array()
		);

		$this->assertEquals(
			'',
			$this->fixture->getContactSwitchboard()
		);
	}

	/**
	 * @test
	 */
	public function getContactSwitchboardForSwitchboardSetReturnsSwitchboardNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT,
			array(),
			array('phone_switchboard' => '555-123456')
		);

		$this->assertEquals(
			'555-123456',
			$this->fixture->getContactSwitchboard()
		);
	}


	///////////////////////////////////////////////
	// Tests concerning getContactDirectExtension
	///////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getContactDirectExtensionForNoDirectExtensionSetReturnsEmptyString() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_REALTY_OBJECT, array(), array()
		);

		$this->assertEquals(
			'',
			$this->fixture->getContactDirectExtension()
		);
	}

	/**
	 * @test
	 */
	public function getContactDirectExtensionForDirectExtensionSetReturnsDirectExtensionNumber() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT,
			array(),
			array('phone_direct_extension' => '555-123456')
		);

		$this->assertEquals(
			'555-123456',
			$this->fixture->getContactDirectExtension()
		);
	}


	////////////////////////////////
	// Tests concerning the status
	////////////////////////////////

	/**
	 * @test
	 */
	public function getStatusForNoStatusSetReturnsVacant() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(), array()
		);

		$this->assertEquals(
			tx_realty_Model_RealtyObject::STATUS_VACANT,
			$this->fixture->getStatus()
		);
	}

	/**
	 * @test
	 */
	public function getStatusForStatusSetReturnsStatus() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(),
			array('status' => tx_realty_Model_RealtyObject::STATUS_RENTED)
		);

		$this->assertEquals(
			tx_realty_Model_RealtyObject::STATUS_RENTED,
			$this->fixture->getStatus()
		);
	}

	/**
	 * @test
	 */
	public function isRentedOrSoldForStatusVacantReturnsFalse() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(),
			array('status' => tx_realty_Model_RealtyObject::STATUS_VACANT)
		);

		$this->assertFalse(
			$this->fixture->isRentedOrSold()
		);
	}

	/**
	 * @test
	 */
	public function isRentedOrSoldForStatusReservedReturnsFalse() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(),
			array('status' => tx_realty_Model_RealtyObject::STATUS_RESERVED)
		);

		$this->assertFalse(
			$this->fixture->isRentedOrSold()
		);
	}

	/**
	 * @test
	 */
	public function isRentedOrSoldForStatusSoldReturnsTrue() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(),
			array('status' => tx_realty_Model_RealtyObject::STATUS_SOLD)
		);

		$this->assertTrue(
			$this->fixture->isRentedOrSold()
		);
	}

	/**
	 * @test
	 */
	public function isRentedOrSoldForStatusRentedReturnsTrue() {
		$this->loadRealtyObjectAndSetOwner(
			REALTY_CONTACT_FROM_OWNER_ACCOUNT, array(),
			array('status' => tx_realty_Model_RealtyObject::STATUS_RENTED)
		);

		$this->assertTrue(
			$this->fixture->isRentedOrSold()
		);
	}


	/*
	 * Tests concerning the address
	 */

	/**
	 * @test
	 */
	public function getStreetForEmptyStreetReturnsEmptyString() {
		$this->fixture->setData(array());

		$this->assertEquals(
			'',
			$this->fixture->getStreet()
		);
	}

	/**
	 * @test
	 */
	public function getStreetForNonEmptyStreetReturnsStreet() {
		$this->fixture->setData(array('street' => 'foo'));

		$this->assertSame(
			'foo',
			$this->fixture->getStreet()
		);
	}

	/**
	 * @test
	 */
	public function hasStreetForEmptyStreetReturnsFalse() {
		$this->fixture->setData(array('street' => ''));

		$this->assertFalse(
			$this->fixture->hasStreet()
		);
	}

	/**
	 * @test
	 */
	public function hasStreetForNonEmptyStreetReturnsTrue() {
		$this->fixture->setData(array('street' => 'foo'));

		$this->assertTrue(
			$this->fixture->hasStreet()
		);
	}

	/**
	 * @test
	 */
	public function getZipForEmptyZipReturnsEmptyString() {
		$this->fixture->setData(array());

		$this->assertSame(
			'',
			$this->fixture->getZip()
		);
	}

	/**
	 * @test
	 */
	public function setStreetSetsStreet() {
		$this->fixture->setData(array());
		$this->fixture->setStreet('bar');

		$this->assertSame(
			'bar',
			$this->fixture->getStreet()
		);
	}

	/**
	 * @test
	 */
	public function getZipForNonEmptyZipReturnsZip() {
		$this->fixture->setData(array('zip' => '12345'));

		$this->assertSame(
			'12345',
			$this->fixture->getZip()
		);
	}

	/**
	 * @test
	 */
	public function setZipSetsZip() {
		$this->fixture->setData(array());
		$zip = '16432';
		$this->fixture->setZip($zip);

		$this->assertSame(
			$zip,
			$this->fixture->getZip()
		);
	}

	/**
	 * @test
	 */
	public function hasZipForEmptyZipReturnsFalse() {
		$this->fixture->setData(array('zip' => ''));

		$this->assertFalse(
			$this->fixture->hasZip()
		);
	}

	/**
	 * @test
	 */
	public function hasZipForNonEmptyZipReturnsTrue() {
		$this->fixture->setData(array('zip' => '12345'));

		$this->assertTrue(
			$this->fixture->hasZip()
		);
	}


	/*
	 * Tests concerning the geo coordinates
	/*

	/**
	 * @test
	 */
	public function getGeoCoordinatesForHasCoordinatesReturnsLatitudeAndLongitude() {
		$this->fixture->setData(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
				'has_coordinates' => TRUE,
			)
		);

		$this->assertSame(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
			),
			$this->fixture->getGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function getGeoCoordinatesForNotHasCoordinatesReturnsEmptyArray() {
		$this->fixture->setData(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
				'has_coordinates' => FALSE,
			)
		);

		$this->assertSame(
			array(),
			$this->fixture->getGeoCoordinates()
		);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setGeoCoordinatesWithoutLatitudeThrowsException() {
		$this->fixture->setGeoCoordinates(array('longitude' => 42.0));
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function setGeoCoordinatesWithoutLongitudeThrowsException() {
		$this->fixture->setGeoCoordinates(array('latitude' => -42.7));
	}

	/**
	 * @test
	 */
	public function setGeoCoordinatesSetsCoordinates() {
		$this->fixture->setData(array());

		$this->fixture->setGeoCoordinates(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
			)
		);

		$this->assertSame(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
			),
			$this->fixture->getGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function setGeoCoordinatesSetsHasCoordinatesToTrue() {
		$this->fixture->setData(array('has_coordinates' => FALSE));

		$this->fixture->setGeoCoordinates(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
			)
		);

		$this->assertTrue(
			$this->fixture->hasGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function setGeoCoordinatesSetsHasGeoErrorToFalse() {
		$this->fixture->setData(array('coordinates_problem' => TRUE));

		$this->fixture->setGeoCoordinates(
			array(
				'latitude' => -42.7,
				'longitude' => 42.0,
			)
		);

		$this->assertFalse(
			$this->fixture->hasGeoError()
		);
	}

	/**
	 * @test
	 */
	public function hasGeoCoordinatesForHasCoordinatesTrueReturnsTrue() {
		$this->fixture->setData(array('has_coordinates' => TRUE));

		$this->assertTrue(
			$this->fixture->hasGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function hasGeoCoordinatesForHasCoordinatesFalseReturnsFalse() {
		$this->fixture->setData(array('has_coordinates' => FALSE));

		$this->assertFalse(
			$this->fixture->hasGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function clearGeoCoordinatesSetsHasCoordinatesToFalse() {
		$this->fixture->setData(array('has_coordinates' => TRUE));

		$this->fixture->clearGeoCoordinates();

		$this->assertFalse(
			$this->fixture->hasGeoCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function hasGeoErrorForProblemTrueReturnsTrue() {
		$this->fixture->setData(array('coordinates_problem' => TRUE));

		$this->assertTrue(
			$this->fixture->hasGeoError()
		);
	}

	/**
	 * @test
	 */
	public function hasGeoErrorForProblemFalseReturnsFalse() {
		$this->fixture->setData(array('coordinates_problem' => FALSE));

		$this->assertFalse(
			$this->fixture->hasGeoError()
		);
	}

	/**
	 * @test
	 */
	public function setGeoErrorSetsGeoErrorToTrue() {
		$this->fixture->setData(array('coordinates_problem' => FALSE));

		$this->fixture->setGeoError();

		$this->assertTrue(
			$this->fixture->hasGeoError()
		);
	}

	/**
	 * @test
	 */
	public function clearGeoErrorSetsGeoErrorToFalse() {
		$this->fixture->setData(array('coordinates_problem' => TRUE));

		$this->fixture->clearGeoError();

		$this->assertFalse(
			$this->fixture->hasGeoError()
		);
	}
}
?>