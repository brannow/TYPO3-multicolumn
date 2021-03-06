<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 snowflake productions GmbH
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
final class tx_multicolumn_div {

	/**
	 * Start index of colpos
	 **/
	const colPosStart = 10;

	/**
	 * Get layout configuration options merged between typoscript and flexform options
	 *
	 * @param array $pageUid
	 * @param tx_multicolumn_flexform $flex
	 * @return array
	 */
	public static function getLayoutConfiguration($pageUid, tx_multicolumn_flexform $flex) {
		// load default config
		$config = self::getDefaultLayoutConfiguration();

		$layoutKey = $flex->getFlexValue('preSetLayout', 'layoutKey');
		// remove . from ts string
		if ($layoutKey) {
			$config['layoutKey'] = substr($layoutKey, 0, -1);
		}

		$tsConfig = self::getTSConfig($pageUid);
		if (isset($tsConfig[$layoutKey]['config.'])) {
			$tsConfig = $tsConfig[$layoutKey]['config.'];
		}

		//merge default config with ts config
		if (is_array($tsConfig)) {
			$config = array_merge($config, $tsConfig);
		}

		//merge with flexconfig
		$flexConfig = $flex->getFlexArray('advancedLayout');
		if (is_array($flexConfig)) {
			$config = array_merge($config, $flexConfig);
		}

		return $config;
	}

	/**
	 * Get layout configuration options merged between typoscript and flexform options
	 *
	 * @param array $pageUid
	 * @param tx_multicolumn_flexform $flex
	 * @return array|null
	 */
	public static function getEffectConfiguration($pageUid, tx_multicolumn_flexform $flex) {
		$config = NULL;
		$effect = substr($flex->getFlexValue('effectBox', 'effect'), 0, -1);
		$flexConfig = $flex->getFlexArray('effectBox');
		$tsConfig = self::getTSConfig($pageUid, 'effectBox');

		if (!empty($tsConfig[$effect . '.']['config.'])) {
			$config = $tsConfig[$effect . '.']['config.'];
			$config['effect'] = $effect;
			$tsConfigOptions = (!empty($config['defaultOptions'])) ? $config['defaultOptions'] : NULL;

			// check for options
			if (!empty($flexConfig['effectOptions'])) {
				$addComma = (strpos($flexConfig['effectOptions'], ',') === 0 && $tsConfigOptions) ? NULL : ',';
				$config['options'] = $tsConfigOptions . $addComma . $flexConfig['effectOptions'];
			} else {
				$config['options'] = $tsConfigOptions;
			}

			$config['options'] = \TYPO3\CMS\Core\Utility\GeneralUtility::minifyJavaScript($config['options']);

			unset($flexConfig['effectOptions'], $flexConfig['effect']);
			unset($config['defaultOptions']);

			$config = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge($config, $flexConfig);

		}

		return $config;
	}

	/**
	 * Get preset layout configuration from tsconfig
	 *
	 * @param array $pageUid
	 * @return array Preset layout configuration
	 */
	public static function getTSConfig($pageUid, $tsConfigKey = 'layoutPreset') {
		$tsConfig = isset($GLOBALS['TSFE']->cObj) ? $GLOBALS['TSFE']->getPagesTSconfig() : \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($pageUid);
		$tsConfig = empty($tsConfig['tx_multicolumn.'][$tsConfigKey . '.']) ? $tsConfig['tx_multicolumn.'] : $tsConfig['tx_multicolumn.'][$tsConfigKey . '.'];

		return $tsConfig;
	}

	/**
	 * Calculates the maximal width  of the column in pixel based on {$styles.content.imgtext.colPos0.maxW}
	 *
	 * @param int $columnWidth
	 * @param int $colPosMaxWidth
	 * @param int $numberOfColumns Unused
	 * @return int
	 */
	public static function calculateMaxColumnWidth($columnWidth, $colPosMaxWidth, $numberOfColumns) {
		return floor(($colPosMaxWidth / 100) * $columnWidth);
	}

	/**
	 * Evaluates the total width of padding in colum
	 *
	 * @param string $columnPadding CSS string link 10px 20px 30px;
	 * @return int
	 */
	public static function getPaddingTotalWidth($columnPadding) {
		// FIXME Fails if parts are separated with more than once space.
		$padding = preg_split('/ /', trim($columnPadding));

		// how many css attributes are set?
		$paddingNum = count($padding);

		// calculate total width
		$paddingTotalWidth = ($paddingNum == 2) ? intval($padding[1]) * 2 : (intval($padding[1]) + intval($padding[3]));

		return $paddingTotalWidth;
	}

	/**
	 * Returns default Layout configuration options
	 *
	 * @return array
	 */
	public static function getDefaultLayoutConfiguration() {
		return array(
			'layoutKey' => NULL,
			'layoutCss' => NULL,
			'columns' => 2,
			'containerMeasure' => '%',
			'containerWidth' => 100,
			'columnMeasure' => '%',
			'columnWidth' => NULL,
			'columnMargin' => NULL,
			'columnPadding' => NULL,
			'disableImageShrink' => NULL,
			'disableStyles' => NULL
		);
	}

	/**
	 * Prefix the keys in an array
	 *
	 * @param array $array
	 * @param string $prefix Prefix string (ex: 'LLL:')
	 * @return array Prefixed array
	 */
	public static function prefixArray(array $array, $prefix) {
		$newArray = array();

		foreach ($array as $key => $value) {
			if (is_array($value) && array_key_exists(0, $value)) {
				if (!empty($value[0]['target'])) {
					$newArray[$prefix . $key] = $value[0]['target'];
				} else {
					$newArray[$prefix . $key] = $value[0]['source'];
				}
			} else {
				$newArray[$prefix . $key] = $value;
			}
		}

		return $newArray;
	}

	/**
	 * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found in that file.
	 *
	 * @return array
	 */
	public static function includeBeLocalLang($llFile = NULL) {
		$llFile = $llFile ? $llFile : 'locallang.xml';

		return self::readLLfile(PATH_tx_multicolumn . $llFile, $GLOBALS['LANG']->lang);
	}

	/**
	 * Checks if backend user has the rights to see multicolumn container
	 *
	 * @return boolean true if it has access false if not
	 */
	public static function beUserHasRightToSeeMultiColumnContainer() {
		// FIXME Too many returns, refactor this mess.

		$hasAccess = TRUE;
		$TSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($GLOBALS['SOBE']->id);

		// check remove items
		if (!empty($TSconfig['TCEFORM.']['tt_content.']['CType.']['removeItems'])) {
			$hasAccess = \TYPO3\CMS\Core\Utility\GeneralUtility::inList($TSconfig['TCEFORM.']['tt_content.']['CType.']['removeItems'], 'multicolumn') ? FALSE : TRUE;
			if (!$hasAccess) {
				return FALSE;
			}
		}

		// is admin?
		if (!empty($GLOBALS['BE_USER']->user['admin'])) {
			return $hasAccess;
		}

		// is explicitADmode allow ?
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] === 'explicitAllow') {
			$hasAccess = \TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['BE_USER']->groupData['explicit_allowdeny'], 'tt_content:CType:multicolumn:ALLOW') ? TRUE : FALSE;
		} else {
			$hasAccess = \TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['BE_USER']->groupData['explicit_allowdeny'], 'tt_content:CType:multicolumn:DENY') ? FALSE : TRUE;
		}

		return $hasAccess;
	}

	/**
	 * Reads the language file and returns labels in the format compatible with
	 * TYPO3 4.5. If the runtime cache is available, uses the cache to avoid
	 * reading the same file many times.
	 *
	 * @param string $filePath
	 * @param string $language
	 * @return array
	 */
	static public function readLLfile($filePath, $language) {
		if (is_object($GLOBALS['typo3CacheManager'])) {
			$cacheIdentifier = 'EXT-multicolumn-readLLfile-' . sha1($filePath);
			try {
				$runtimeCache = $GLOBALS['typo3CacheManager']->getCache('cache_runtime');
				$cacheEntry = $runtimeCache->get($cacheIdentifier);
				if ($cacheEntry) {
					return $cacheEntry;
				}
			} catch (Exception $e) {
				// No such cache (old TYPO3). Ignore.
			}
		}
		$labels = \TYPO3\CMS\Core\Utility\GeneralUtility::readLLfile($filePath, $language);
		// We need to flatten labels
		$originalLabels = $labels;
		foreach ($originalLabels as $languageKey => $languageArray) {
			foreach ($languageArray as $stringId => $translationData) {
				$labels[$languageKey][$stringId] = $translationData[0]['target'];
			}
		}
		if (isset($runtimeCache)) {
			$runtimeCache->set($cacheIdentifier, $labels);
		}

		return $labels;
	}
}

?>