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

namespace Pimcore\Sitemap\Document\Processor;

use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Sitemap\Document\ProcessorInterface;
use Presta\SitemapBundle\Sitemap\Url\Url;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;

class DocumentPropertiesProcessor implements ProcessorInterface
{
    const PROPERTY_CHANGE_FREQUENCY = 'sitemaps_changefreq';
    const PROPERTY_PRIORITY = 'sitemaps_priority';

    public function process(Url $url, Document $document, Site $site = null): Url
    {
        if (!$url instanceof UrlConcrete) {
            return $url;
        }

        $url->setLastmod(\DateTime::createFromFormat('U', (string)$document->getModificationDate()));

        $changeFreq = $document->getProperty(self::PROPERTY_CHANGE_FREQUENCY);
        if (!empty($changeFreq)) {
            $url->setChangefreq($changeFreq);
        }

        $priority = $document->getProperty(self::PROPERTY_PRIORITY);
        if (!empty($priority)) {
            $url->setPriority($priority);
        }

        return $url;
    }
}
