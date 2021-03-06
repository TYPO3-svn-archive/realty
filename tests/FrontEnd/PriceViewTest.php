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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_PriceViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_PriceView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_PriceView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'currencyUnit' => 'EUR',
				'priceOnlyIfAvailable' => FALSE,
			),
			$GLOBALS['TSFE']->cObj
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}


	///////////////////////////
	// Testing the price view
	///////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsNonEmptyResultForShowUidOfExistingRecord() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
		));

		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForShowUidOfObjectWithInvalidObjectType() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_type' => 2));

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
		));

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertNotEquals(
			'',
			$result
		);
		$this->assertNotContains(
			'###',
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsTheRealtyObjectsBuyingPriceForObjectForSale() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '123',
		));

		$this->assertContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForVacantObjectForSaleReturnsBuyingPrice() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_VACANT
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForReservedObjectForSaleReturnsBuyingPrice() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_RESERVED
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForSoldObjectForSaleReturnsEmptyString() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_SOLD
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsTheRealtyObjectsBuyingPriceForObjectForRenting() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'buying_price' => '123',
		));

		$this->assertNotContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsTheRealtyObjectsRentForObjectForRenting() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
		));

		$this->assertContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForVacantObjectForRentReturnsBuyingPrice() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_VACANT
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForReservedObjectForRentReturnsBuyingPrice() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_RESERVED
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForRentedObjectForRentReturnsEmptyString() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_RENTED
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsTheRealtyObjectsRentForObjectForSale() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'rent_excluding_bills' => '123',
		));

		$this->assertNotContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForEmptyBuyingPriceOfObjectForSale() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '',
		));

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}
}