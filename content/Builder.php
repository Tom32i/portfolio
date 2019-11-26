<?php

namespace Content;

use Content\Builder\AssetList;
use Content\Builder\PageList;
use Content\Builder\RouteInfo;
use Content\Builder\Sitemap;
use Content\ContentManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Twig\Environment;

/**
 * Static route builder
 */
class Builder
{
    /**
     * HTTP Kernel
     *
     * @var HttpKernelInterface
     */
    private $httpKernel;

    /**
     * Url Generator
     *
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * Encore webpack
     *
     * @var EntrypointLookupInterface
     */
    private $entrypointLookup;

    /**
     * Path to build route to
     *
     * @var string
     */
    private $destination;

    /**
     * File system
     *
     * @var FileSystem
     */
    private $files;

    public function __construct(
        RouterInterface $router,
        HttpKernelInterface $httpKernel,
        UrlGeneratorInterface $urlGenerator,
        EntrypointLookupInterface $entrypointLookup,
        Environment $templating,
        AssetList $assetList,
        PageList $pageList,
        Sitemap $sitemap,
        string $source,
        string $destination,
        string $assets
    ) {
        $this->httpKernel = $httpKernel;
        $this->urlGenerator = $urlGenerator;
        $this->templating = $templating;
        $this->entrypointLookup = $entrypointLookup;
        $this->assetList = $assetList;
        $this->pageList = $pageList;
        $this->sitemap = $sitemap;
        $this->source = $source;
        $this->destination = $destination;
        $this->assets = $assets;
        $this->files = new Filesystem();

        $this->initUrls($router);
    }

    private function initUrls(RouterInterface $router) {
        foreach (RouteInfo::createFromRouteCollection($router->getRouteCollection()) as $name => $route) {
            if ($route->isVisible() && $route->isGettable()) {
                try {
                    $url = $this->urlGenerator->generate($name, [], UrlGeneratorInterface::ABSOLUTE_URL);
                } catch (\Exception $exception) {
                    continue;
                }

                $this->pageList->add($url);
            }
        }
    }

    public function setDestination(string $destination = null)
    {
        $this->destination = $destination;
    }

    public function setHost(string $host)
    {
        $this->urlGenerator->getContext()->setHost($host);
    }

    public function setScheme(string $scheme)
    {
        $this->urlGenerator->getContext()->setScheme($scheme);
    }

    public function build(bool $sitemap = true, bool $assets = true)
    {
        $this->clear();

        $this->buildPages();

        if ($sitemap) {
            $this->buildSitemap();
        }

        if ($assets) {
            $this->buildAssets();
        }
    }

    /**
     * Clear destination folder
     */
    private function clear()
    {
        if ($this->files->exists($this->destination)) {
            $this->files->remove($this->destination);
        }

        $this->files->mkdir($this->destination);
    }

    /**
     * Build all pages
     */
    private function buildPages()
    {
        while ($url = $this->pageList->getNext()) {
            $this->buildUrl($url);
            $this->pageList->markAsDone($url);
        }
    }

    /**
     * Build sitemap xml file from Sitemap
     */
    private function buildSitemap()
    {
        $content = $this->templating->render('@Content/sitemap.xml.twig', ['sitemap' => $this->sitemap]);

        $this->write($content, '/', 'sitemap.xml');
    }

    /**
     * Expose assets
     */
    private function buildAssets()
    {
        foreach ($this->assetList as $path) {
            $this->files->copy(
                implode('/', [$this->assets, $path]),
                implode('/', [$this->destination, $path]),
                true
            );
        }
    }

    /**
     * Build the given Route into a file
     */
    private function buildUrl(string $url)
    {
        $request = Request::create($url, 'GET');
        $response = $this->httpKernel->handle($request);

        $this->httpKernel->terminate($request, $response);
        $this->entrypointLookup->reset();

        list($path, $file) = $this->getFilePath($request->getPathInfo());

        $this->write($response->getContent(), $path, $file);
    }

    /**
     * Get file path from URL
     */
    private function getFilePath(string $url): array
    {
        $info = pathinfo($url);

        if (!isset($info['extension'])) {
            return [$url, 'index.html'];
        }

        return [$info['dirname'], $info['basename']];
    }

    /**
     * Write a file
     *
     * @param string $content The file content
     * @param string $path The directory to put the file in (in the current destination)
     * @param string $file The file name
     */
    private function write(string $content, string $path, string $file)
    {
        $directory = sprintf('%s/%s', $this->destination, trim($path, '/'));

        if (!$this->files->exists($directory)) {
            $this->files->mkdir($directory);
        }

        $this->files->dumpFile(sprintf('%s/%s', $directory, $file), $content);
    }
}
