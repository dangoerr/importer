<?php

namespace CranachDigitalArchive\Importer\Modules\Archivals\Transformers;

use CranachDigitalArchive\Importer\Modules\Archivals\Entities\Archival;
use CranachDigitalArchive\Importer\Interfaces\Pipeline\ProducerInterface;
use CranachDigitalArchive\Importer\Pipeline\Hybrid;
use Error;

class MetadataFiller extends Hybrid
{
    private function __construct()
    {
    }


    public static function new(): self
    {
        return new self;
    }

    public function handleItem($item): bool
    {
        if (!($item instanceof Archival)) {
            throw new Error('Pushed item is not of expected class \'Archival\'');
        }

        $metadata = $item->getMetadata();

        if (is_null($metadata)) {
            $this->next($item);
            return true;
        }

        $summaries = $item->getSummaries();
        $summary = $summaries[0] ?? '';
        $dating = $item->getDating();
        $dated = !is_null($dating) ? $dating->getDated() : '';

        $metadata->setId($item->getId());
        $metadata->setTitle($summary);
        $metadata->setSubtitle('');
        $metadata->setDate($dated);
        $metadata->setClassification('');
        $metadata->setImgSrc('');

        $this->next($item);
        return true;
    }


    /**
     * @return void
     */
    public function done(ProducerInterface $producer)
    {
        parent::done($producer);
    }
}