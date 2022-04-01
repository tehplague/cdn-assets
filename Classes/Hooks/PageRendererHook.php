<?php

namespace Tehplague\CdnAssets\Hooks;

use Symfony\Component\Config\Definition\Processor;
use Tehplague\CdnAssets\Configuration\Configuration;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class PageRendererHook
 * @package Tehplague\CdnAssets\Hooks
 * @author Christian Spoo <cs@marketing-factory.de>
 */
class PageRendererHook
{
    /**
     * @var array
     */
    private $extensionConfig;

    /**
     * @param string $url
     * @param string $newHostname
     * @param bool $useSSL
     * @return string
     */
    private static function replaceHost(string $url, string $newHostname, bool $useSSL = false): string
    {
        $components = parse_url($url);
        $components['host'] = $newHostname;
        $components['scheme'] = $useSSL ? 'https' : 'http';

        $scheme   = isset($components['scheme']) ? $components['scheme'] . '://' : '';
        $host     = isset($components['host']) ? $components['host'] : '';
        $port     = isset($components['port']) ? ':' . $components['port'] : '';
        $user     = isset($components['user']) ? $components['user'] : '';
        $pass     = isset($components['pass']) ? ':' . $components['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($components['path']) ? $components['path'] : '';
        $query    = isset($components['query']) ? '?' . $components['query'] : '';
        $fragment = isset($components['fragment']) ? '#' . $components['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * @param array $params
     * @param PageRenderer $pageRenderer
     * @throws \Exception
     */
    public function renderPreProcess(array $params, PageRenderer $pageRenderer)
    {
        if (TYPO3_MODE !== 'FE') {
            return;
        }

        $tsfe = $this->getTypoScriptFrontendController();
        if (!$tsfe instanceof TypoScriptFrontendController) {
            return;
        }

        /** @var FrontendInterface $cache */
        $cache = GeneralUtility::makeInstance(CacheManager::class)
            ->getCache('cache_hash');

        $tsfe->getConfigArray();
        $configCacheIdentifier = sha1('cdn_assets:configuration|' . $tsfe->id . '|' . json_encode($tsfe->config));

        if (($extensionConfig = $cache->get($configCacheIdentifier)) === false) {
            $systemConfig = $tsfe->config;

            if (isset($systemConfig['config']) && isset($systemConfig['config']['cdn_assets.'])) {
                $extensionConfig = $systemConfig['config']['cdn_assets.'];
            }

            try {
                // Remove dots from TypoScript array
                $extensionConfig = GeneralUtility::removeDotsFromTS($extensionConfig);

                // Map extension config under virtual root node (needed for Symfony Config)
                $extensionConfig = [
                    'cdn_assets' => $extensionConfig
                ];

                // Initialize Symfony Config
                $configuration = new Configuration();
                $processor = new Processor();

                // Load and process configuration
                $extensionConfig = $processor->processConfiguration($configuration, $extensionConfig);

                $cache->set($configCacheIdentifier, $extensionConfig, [], 86400);
            } catch (\Exception $ex) {
                throw $ex;
            }
        }

        $this->extensionConfig = $extensionConfig;

        $this->processMappings($pageRenderer);
    }

    /**
     * May return the TSFE or not (e.g. in BE mode), thus the nullable return type.
     *
     * @return TypoScriptFrontendController|null
     */
    private function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @param PageRenderer $pageRenderer
     */
    private function processMappings(PageRenderer $pageRenderer)
    {
        $assetSections = [
            'css' => 'cssPrefix|addCssFile',
            'js' => 'jsPrefix|addJsFile',
            'jsFooter' => 'jsPrefix|addJsFooterFile'
        ];

        /** @var array $mapping */
        foreach ($this->extensionConfig['mappings'] as $mapping) {
            $manifestFile = GeneralUtility::getFileAbsFileName($mapping['jsonAssetManifest']);
            if (file_exists($manifestFile) && is_readable($manifestFile)) {
                $manifestJson = file_get_contents($manifestFile);
                $manifest = json_decode($manifestJson, true);
            } else {
                $manifest = [];
            }

            $cssPrefix = rtrim($mapping['resources']['cssPrefix'], '/');
            $jsPrefix = rtrim($mapping['resources']['jsPrefix'], '/');

            foreach ($assetSections as $assetType => $assetConfig) {
                list ($prefix, $addFunction) = explode('|', $assetConfig);

                foreach ($mapping['resources'][$assetType] as $file) {
                    $file = ltrim($file, '/');
                    if (isset($manifest[$file])) {
                        $file = '/' . ltrim($manifest[$file], '/');
                    } else {
                        $file = ${$prefix} . '/' . $file;
                    }

                    if ($mapping['enableCDN']) {
                        $file = self::replaceHost($file, $mapping['cdnHost'], $mapping['cdnUseSSL']);
                    }
                    $pageRenderer->$addFunction($file);
                }
            }
        }
    }
}
