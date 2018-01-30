<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Sitemap\Document;

use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Sitemap\GeneratorInterface;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentTreeGenerator implements GeneratorInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var FilterInterface[]
     */
    private $filters = [];

    /**
     * @var ProcessorInterface[]
     */
    private $processors = [];

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        array $filters = [],
        array $processors = [],
        array $options = []
    )
    {
        $this->urlGenerator = $urlGenerator;

        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }

        foreach ($processors as $processor) {
            $this->addProcessor($processor);
        }

        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);

        $this->options = $optionsResolver->resolve($options);
    }

    public function addFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    public function addProcessor(ProcessorInterface $processor)
    {
        $this->processors[] = $processor;
    }

    protected function configureOptions(OptionsResolver $options)
    {
        $options->setDefaults([
            'rootId'              => 1,
            'handleMainDomain'    => true,
            'handleSites'         => true,
            'urlGeneratorOptions' => []
        ]);

        $options->setAllowedTypes('rootId', 'int');
        $options->setAllowedTypes('handleMainDomain', 'bool');
        $options->setAllowedTypes('handleSites', 'bool');
        $options->setAllowedTypes('urlGeneratorOptions', 'array');
    }

    public function populate(UrlContainerInterface $container, string $section = null)
    {
        if ($this->options['handleMainDomain'] && null === $section || $section === 'default') {
            $rootDocument = Document::getById($this->options['rootId']);

            $this->populateCollection($container, $rootDocument, 'default');
        }

        if ($this->options['handleSites']) {
            /** @var Site[] $sites */
            $sites = (new Site\Listing())->load();
            foreach ($sites as $site) {
                $siteSection = sprintf('site_%s', $site->getId());

                if (null === $section || $section === $siteSection) {
                    $this->populateCollection($container, $site->getRootDocument(), $siteSection, $site);
                }
            }
        }
    }

    private function populateCollection(UrlContainerInterface $container, Document $rootDocument, string $section, Site $site = null)
    {
        $visit = $this->visit($rootDocument, $site);

        foreach ($visit as $document) {
            $url = $this->createUrl($document, $site);
            if (null === $url) {
                continue;
            }

            $container->addUrl($url, $section);
        }
    }

    private function createUrl(Document $document, Site $site = null)
    {
        $url = new UrlConcrete($this->urlGenerator->generateUrl($document, $site, $this->options['urlGeneratorOptions']));

        foreach ($this->processors as $processor) {
            $url = $processor->process($url, $document, $site);
        }

        return $url;
    }

    /**
     * @param Document $document
     * @param Site|null $site
     *
     * @return \Generator|Document[]
     * @throws \Exception
     */
    private function visit(Document $document, Site $site = null): \Generator
    {
        if ($document instanceof Document\Hardlink) {
            $document = Document\Hardlink\Service::wrap($document);
        }

        if ($this->canBeAdded($document, $site)) {
            yield $document;
        }

        if ($this->handlesChildren($document, $site)) {
            foreach ($document->getChildren(false) as $child) {
                yield from $this->visit($child);
            }
        }

        unset($document);
        \Pimcore::collectGarbage();
    }

    private function canBeAdded(Document $document, Site $site = null): bool
    {
        foreach ($this->filters as $filter) {
            if (!$filter->canBeAdded($document, $site)) {
                return false;
            }
        }

        return true;
    }

    private function handlesChildren(Document $document, Site $site = null): bool
    {
        foreach ($this->filters as $filter) {
            if (!$filter->handlesChildren($document, $site)) {
                return false;
            }
        }

        return true;
    }
}
