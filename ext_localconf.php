<?php

use Tehplague\CdnAssets\Hooks\PageRendererHook;

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['cdn_assets'] =
    PageRendererHook::class . '->renderPreProcess';
