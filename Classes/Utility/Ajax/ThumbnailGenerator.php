<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Thumbnail generator
 */
class Tx_ExtbaseHijax_Utility_Ajax_ThumbnailGenerator extends Tx_ExtbaseHijax_Utility_Ajax_Dispatcher {
	/**
	 * @var t3lib_cache_frontend_VariableFrontend
	 */
	protected $cache;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->cache = $GLOBALS['typo3CacheManager']->getCache('extbase_hijax_img_storage');
	}

	/**
	 * Called by ajax.php / eID.php
	 * Builds an extbase context and returns the response.
	 * 
	 * @return void
	 */
	public function dispatch() {
		$src = t3lib_div::_GP('src');
		$hash = t3lib_div::_GP('hash');
		$conf = json_decode(rawurldecode(t3lib_div::_GP('conf')), true);

		/* @var $cacheHash t3lib_cacheHash */
		$cacheHash = t3lib_div::makeInstance('t3lib_cacheHash');
		$calculatedHash = $cacheHash->calculateCacheHash(array(
			'encryptionKey' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
			'src' => $src,
			'conf' => $conf
		));
		$allowProcessing = $hash == $calculatedHash;

		/**
		 * check hash
		 */
		if (!$allowProcessing) {
			header('HTTP/1.1 503 Service Unavailable');
			header('Status: 503 Service Unavailable');

			exit;
		}

		/**
		 * check if source file exists
		 */
		if (!@file_exists($src)) {
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");

			exit;
		}

		/**
		 * check if target file exists and is up to date
		 */
		if ($this->cache->has($hash)) {
			list($target, $sourceMDate) = $this->cache->get($hash);
			if (@file_exists($target) && @filemtime($src) === $sourceMDate) {
				header("Location:".$target);

				exit;
			}
		}

		/**
		 * generate image
		 */
		$this->initialize();
		$target = $this->generateImage($src, $conf['width'], $conf['height'], $conf['minWidth'], $conf['minHeight'], $conf['maxWidth'], $conf['maxHeight']);
		$sourceMDate = @filemtime($src);
		$this->cache->set($hash, array($target, $sourceMDate));
		header("Location:".$target);

		exit;
	}

	/**
	 * @param $src
	 * @param null $width
	 * @param null $height
	 * @param null $minWidth
	 * @param null $minHeight
	 * @param null $maxWidth
	 * @param null $maxHeight
	 */
	public function getFallbackImageUrl($src, $width = NULL, $height = NULL, $minWidth = NULL, $minHeight = NULL, $maxWidth = NULL, $maxHeight = NULL) {
		$conf = array(
			'width' => $width,
			'height' => $height,
			'minWidth' => $minWidth,
			'minHeight' => $minHeight,
			'maxWidth' => $maxWidth,
			'maxHeight' => $maxHeight,
		);

		/* @var $cacheHash t3lib_cacheHash */
		$cacheHash = t3lib_div::makeInstance('t3lib_cacheHash');
		$hash = $cacheHash->calculateCacheHash(array(
			'encryptionKey' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
			'src' => $src,
			'conf' => $conf
		));

		return 'index.php?eID=extbase_hijax_thumb&src='.rawurlencode($src).'&hash='.rawurlencode($hash).'&conf='.rawurlencode(json_encode($conf));
	}

	/**
	 * @return void
	 */
	public function flushCache() {
		$this->cache->flush();
	}

	/**
	 * @param $src
	 * @param null $width
	 * @param null $height
	 * @param null $minWidth
	 * @param null $minHeight
	 * @param null $maxWidth
	 * @param null $maxHeight
	 * @return mixed
	 */
	protected function generateImage($src, $width = NULL, $height = NULL, $minWidth = NULL, $minHeight = NULL, $maxWidth = NULL, $maxHeight = NULL) {
		$conf = array(
			'width' => $width,
			'height' => $height,
			'minW' => $minWidth,
			'minH' => $minHeight,
			'maxW' => $maxWidth,
			'maxH' => $maxHeight
		);

		$imageInfo = $GLOBALS['TSFE']->cObj->getImgResource($src, $conf);
		$GLOBALS['TSFE']->lastImageInfo = $imageInfo;
		if (!is_array($imageInfo)) {
			///throw new exception('Could not get image resource for "' . htmlspecialchars($src) . '".' , 1253191060);
		}
		$imageInfo[3] = t3lib_div::png_to_gif_by_imagemagick($imageInfo[3]);
		$GLOBALS['TSFE']->imagesOnPage[] = $imageInfo[3];
		$imageSource = $GLOBALS['TSFE']->absRefPrefix . t3lib_div::rawUrlEncodeFP($imageInfo[3]);

		if (!@file_exists(PATH_site.$imageSource) || !@is_file(PATH_site.$imageSource)) {
			error_log('The following image is (most likely) corrupted: "' . $src . '".');

			$imageSource = 'typo3conf/ext/extbase_hijax/Resources/Public/Images/trans.gif';
		}

		return $imageSource;
	}
}

?>