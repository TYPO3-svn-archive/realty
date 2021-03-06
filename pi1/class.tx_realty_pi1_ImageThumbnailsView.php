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
 * This class renders the images for one realty object as thumbnails.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_ImageThumbnailsView extends tx_realty_pi1_FrontEndView {
	/**
	 * @var integer UID of the realty object to show
	 */
	private $showUid = 0;

	/**
	 * size and lightbox configuration for the images using the image position
	 * number (0...n) as first-level array keys
	 *
	 * @var array[]
	 */
	private $imageConfiguration = array();

	/**
	 * the number of image subparts in the default HTML template which will be
	 * be hidden if there are no images for that position
	 *
	 * @var integer
	 */
	const IMAGE_POSITIONS_IN_DEFAULT_TEMPLATE = 4;

	/**
	 * Returns the image thumbnails for one realty object as HTML.
	 *
	 * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
	 *
	 * @return string HTML for the image thumbnails, will be empty if there are
	 *                no images to render
	 */
	public function render(array $piVars = array()) {
		$this->showUid = $piVars['showUid'];

		$this->createImageConfiguration();

		return ($this->renderImages() > 0)
			? $this->getSubpart('FIELD_WRAPPER_IMAGETHUMBNAILS') : '';
	}

	/**
	 * Creates all images that are attached to the current record and puts them
	 * in their particular subparts.
	 *
	 * @return integer the total number of rendered images, will be >= 0
	 */
	private function renderImages() {
		tx_realty_lightboxIncluder::includeLightboxFiles(
			$this->prefixId, $this->extKey
		);

		$allImages = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->getUid())->getImages();

		$imagesByPosition = array();
		$usedPositions = max(
			self::IMAGE_POSITIONS_IN_DEFAULT_TEMPLATE,
			$this->findHighestConfiguredPositionIndex()
		);
		for ($i = 0; $i <= $usedPositions; $i++) {
			$imagesByPosition[$i] = array();
		}

		foreach ($allImages as $image) {
			$position = $image->getPosition();

			$imagesByPosition[$position][] = $image;
		}

		foreach ($imagesByPosition as $position => $images) {
			$this->renderImagesInPosition($position, $images);
		}

		return $allImages->count();
	}

	/**
	 * Renders all images for a given position and fills the corresponding
	 * subpart in the template.
	 *
	 * @param integer $position the zero-based position index of the images
	 * @param array<tx_realty_Model_Image> $images
	 *        the images to render, must all be in position $position
	 *
	 * @return void
	 */
	private function renderImagesInPosition($position, array $images) {
		$containerSubpartName = ($position > 0)
			? 'IMAGES_POSITION_' . $position : 'ONE_IMAGE_CONTAINER';
		if (empty($images)) {
			$this->hideSubparts($containerSubpartName);
			return;
		}

		$itemSubpartName = ($position > 0)
			? 'ONE_IMAGE_CONTAINER_' . $position : 'ONE_IMAGE_CONTAINER';

		$result = '';
		foreach ($images as $image) {
			$configuration = $this->getImageConfigurationForContainer(
				$position
			);
			$currentImage = $configuration['enableLightbox']
				? $this->createLightboxThumbnail($image)
				: $this->createThumbnail($image);
			$this->setMarker('one_image_tag', $currentImage);
			$result .= $this->getSubpart($itemSubpartName);
		}

		$this->setSubpart($itemSubpartName, $result);
	}

	/**
	 * Creates a thumbnail (without Lightbox) of $image sized as per the
	 * configuration.
	 *
	 * @param tx_realty_Model_Image $image
	 *        the image to render
	 *
	 * @return string
	 *         image tag, will not be empty
	 */
	protected function createThumbnail(tx_realty_Model_Image $image) {
		$configuration = $this->getImageConfigurationForContainer(
			$image->getPosition()
		);

		$fileName = ($image->hasThumbnailFileName())
			? $image->getThumbnailFileName() : $image->getFileName();

		return $this->createRestrictedImage(
			tx_realty_Model_Image::UPLOAD_FOLDER . $fileName,
			$image->getTitle(),
			$configuration['thumbnailSizeX'],
			$configuration['thumbnailSizeY'],
			0,
			$image->getTitle()
		);
	}

	/**
	 * Creates a Lightboxed thumbnail of $image sized as per the configuration.
	 *
	 * @param tx_realty_Model_Image $image
	 *        the image to render
	 *
	 * @return string
	 *         image tag wrapped in a Lightbox link, will not be empty
	 */
	protected function createLightboxThumbnail(tx_realty_Model_Image $image) {
		$thumbnail = $this->createThumbnail($image);

		$position = $image->getPosition();
		$configuration = $this->getImageConfigurationForContainer($position);

		$imagePath = array();
		$imageWithTag = $this->createRestrictedImage(
			tx_realty_Model_Image::UPLOAD_FOLDER . $image->getFileName(),
			'',
			$configuration['lightboxSizeX'],
			$configuration['lightboxSizeY']
		);
		preg_match('/src="([^"]*)"/', $imageWithTag, $imagePath);
		$fullSizeImageUrl = $imagePath[1];

		$lightboxGallerySuffix = ($position > 0) ? '_' . $position : '';
		$linkAttribute = ' rel="lightbox[objectGallery' . $lightboxGallerySuffix .
			']" title="' . htmlspecialchars($image->getTitle()) . '"';

		return '<a href="' . $fullSizeImageUrl . '"' . $linkAttribute . '>' .
			$thumbnail . '</a>';
	}

	/**
	 * Returns the current "showUid".
	 *
	 * @return integer UID of the realty record to show
	 */
	private function getUid() {
		return $this->showUid;
	}

	/**
	 * Gathers the image configuration for all configured image containers in
	 * $this->imageConfiguration.
	 *
	 * @return void
	 */
	private function createImageConfiguration() {
		$configuration = tx_oelib_ConfigurationRegistry
			::get('plugin.tx_realty_pi1');

		$highestPositionIndex = $this->findHighestConfiguredPositionIndex();
		for ($position = 0; $position <= $highestPositionIndex; $position++) {
			$accumulatedConfiguration = array(
				'enableLightbox'
					=> $configuration->getAsBoolean('enableLightbox'),
				'thumbnailSizeX'
					=> $configuration->getAsInteger('singleImageMaxX'),
				'thumbnailSizeY'
					=> $configuration->getAsInteger('singleImageMaxY'),
				'lightboxSizeX'
					=> $configuration->getAsInteger('lightboxImageWidthMax'),
				'lightboxSizeY'
					 => $configuration->getAsInteger('lightboxImageHeightMax'),
			);

			if ($position > 0) {
				$specificConfiguration = tx_oelib_ConfigurationRegistry
					::get('plugin.tx_realty_pi1.images')
					->getAsMultidimensionalArray($position . '.');
				if (isset($specificConfiguration['enableLightbox'])) {
					$accumulatedConfiguration['enableLightbox'] =
					(boolean) $specificConfiguration['enableLightbox'];
				}
				if (isset($specificConfiguration['singleImageMaxX'])) {
					$accumulatedConfiguration['thumbnailSizeX'] =
						intval($specificConfiguration['singleImageMaxX']);
				}
				if (isset($specificConfiguration['singleImageMaxY'])) {
					$accumulatedConfiguration['thumbnailSizeY'] =
						intval($specificConfiguration['singleImageMaxY']);
				}
				if (isset($specificConfiguration['lightboxImageWidthMax'])) {
					$accumulatedConfiguration['lightboxSizeX'] =
						intval($specificConfiguration['lightboxImageWidthMax']);
				}
				if (isset($specificConfiguration['lightboxImageHeightMax'])) {
					$accumulatedConfiguration['lightboxSizeY'] =
						intval($specificConfiguration['lightboxImageHeightMax']);
				}
			}

			$this->imageConfiguration[$position] = $accumulatedConfiguration;
		}
	}

	/**
	 * Gets the image configuration for the image container with the index
	 * $containerIndex.
	 *
	 * @param integer $containerIndex
	 *        index of the image container, must be >= 0
	 *
	 * @return array
	 *         the configuration for the image container with the requested
	 *         index using the array keys "enableLightbox", "singleImageMaxX",
	 *         "singleImageMaxY", "lightboxImageWidthMax" and
	 *         "lightboxImageHeightMax"
	 *
	 */
	private function getImageConfigurationForContainer($containerIndex) {
		return $this->imageConfiguration[$containerIndex];
	}

	/**
	 * Finds the highest position index that has been configured via TS setup.
	 *
	 * @return integer the highest container index in use, will be >= 0
	 */
	private function findHighestConfiguredPositionIndex() {
		$highestIndex = 0;

		$imageConfigurations = tx_oelib_ConfigurationRegistry
			::get('plugin.tx_realty_pi1')->getAsMultidimensionalArray('images.');

		foreach (array_keys($imageConfigurations) as $key) {
			$index = intval($key);
			if ($index > $highestIndex) {
				$highestIndex = $index;
			}
		}

		return $highestIndex;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ImageThumbnailsView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ImageThumbnailsView.php']);
}