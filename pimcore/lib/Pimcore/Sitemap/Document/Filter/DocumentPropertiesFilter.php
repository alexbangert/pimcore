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

namespace Pimcore\Sitemap\Document\Filter;

use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Sitemap\Document\FilterInterface;

class DocumentPropertiesFilter implements FilterInterface
{
    const PROPERTY_EXCLUDE = 'sitemaps_exclude';
    const PROPERTY_EXCLUDE_CHILDREN = 'sitemaps_exclude_children';

    public function canBeAdded(Document $document, Site $site = null): bool
    {
        if ($this->getBoolProperty($document, self::PROPERTY_EXCLUDE)) {
            return false;
        }

        return true;
    }

    public function handlesChildren(Document $document, Site $site = null): bool
    {
        if (!$this->canBeAdded($document, $site)) {
            return false;
        }

        if ($this->getBoolProperty($document, self::PROPERTY_EXCLUDE_CHILDREN)) {
            return false;
        }

        return true;
    }

    private function getBoolProperty(Document $document, string $property): bool
    {
        if (!$document->hasProperty($property)) {
            return false;
        }

        return (bool)$document->getProperty($property);
    }
}
