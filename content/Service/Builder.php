<?php

namespace Content\Service;

use Exception;
//use Content\Routing\Route;
use Content\Model\RouteInfo;
use Content\Model\Sitemap;
use Content\ContentManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
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

    /**
     * Constructor
     *
     * @param RouterInterface $router
     * @param HttpKernelInterface $httpKernel
     * @param UrlGeneratorInterface $urlGenerator
     * @param Environment $templating
     * @param ContentManager $contentManager
     * @param Sitemap $sitemap
     * @param string $destination
     */
    public function __construct(
        RouterInterface $router,
        HttpKernelInterface $httpKernel,
        UrlGeneratorInterface $urlGenerator,
        Environment $templating,
        ContentManager $contentManager,
        Sitemap $sitemap,
        string $source,
        string $destination
    ) {
        $this->routes = RouteInfo::createFromRouteCollection($router->getRouteCollection());
        $this->httpKernel = $httpKernel;
        $this->urlGenerator = $urlGenerator;
        $this->templating = $templating;
        $this->contentManager = $contentManager;
        $this->source = $source;
        $this->destination = $destination;
        $this->sitemap = $sitemap;
        $this->files = new Filesystem();
    }

    public function setDestination(string $destination = null)
    {
        if ($destination) {
            $this->destination = $destination;
        }
    }

    public function setHost(string $host)
    {
        $this->urlGenerator->getContext()->setHost($host);
    }


    public function setScheme(string $scheme)
    {
        $this->urlGenerator->getContext()->setScheme($scheme);
    }

    /**
     * Clear destination folder
     */
    public function clear()
    {
        if ($this->files->exists($this->destination)) {
            $this->files->remove($this->destination);
        }

        $this->files->mkdir($this->destination);
    }

    public function count()
    {
        return count($this->routes);
    }

    public function buildAll()
    {
        foreach ($this->routes as $route) {
            if (!$route->isVisible()) {
                continue;
            }

            if (!$route->isGettable()) {
                throw new Exception(sprintf('Only GET method supported, "%s" given.', $route->getName()));
            }

            if ($route->hasContent()) {
                if ($route->isList()) {
                    /*if ($route->isPaginated()) {
                        $this->buildPaginatedRoute($route);
                    } else {*/
                        $this->buildListRoute($route);
                    /*}*/
                } else {
                    $this->buildContentRoutes($route);
                }
            } else {
                //$this->logger->log(sprintf('Building route <comment>%s</comment>'));
                $this->build($route);
            }
        }
    }

    /**
     * Build the given Route into a file
     *
     * @param Route $route
     * @param array $parameters
     */
    public function build(RouteInfo $route, array $parameters = [])
    {
        $url = $this->urlGenerator->generate($route->getName(), $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
        $request = Request::create($url, 'GET', $parameters);
        $response = $this->httpKernel->handle($request);
        list($path, $file) = $this->getFilePath($request->getPathInfo());

        $this->write($response->getContent(), $path, $file);
        //$request->getFormat($response->headers->get('Content-Type', 'text/html')),
    }

    /**
     * Build sitemap xml file from Sitemap
     */
    public function buildSitemap()
    {
        $content = $this->templating->render('@Content/sitemap.xml.twig', ['sitemap' => $this->sitemap]);

        $this->write($content, '/', 'sitemap.xml');
    }

    public function expose()
    {
        $finder = new Finder();
        $files = new Filesystem();

        if (!file_exists($this->source)) {
            return;
        }

        foreach ($finder->files()->in($this->source) as $file) {
            $files->copy(
                $file->getPathName(),
                str_replace($this->source, $this->destination, $file->getPathName()),
                true
            );
        }
    }

    private function getFilePath($path)
    {
        $info = pathinfo($path);

        if (!isset($info['extension'])) {
            return [$path, 'index.html'];
        }

        return [$info['dirname'], $info['basename']];
    }

    /**
     * Build content route
     *
     * @param Route $route
     */
    private function buildContentRoutes(RouteInfo $route)
    {
        $contentType = $route->getContent();
        $contents = $this->contentManager->listContents($contentType);

        /*$this->logger->log(sprintf(
            'Building route <comment>%s</comment> for <info>%s</info> <comment>%s(s)</comment>',
            $route->getName(),
            count($contents),
            $route->getContent()
        ));
        $this->logger->getProgress(count($contents));
        $this->logger->start();*/

        foreach ($contents as $content) {
            $this->build($route, [$contentType => $content]);
            //$this->logger->advance();
        }

        //$this->logger->finish();
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
