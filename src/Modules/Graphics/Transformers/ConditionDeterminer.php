<?php

namespace CranachDigitalArchive\Importer\Modules\Graphics\Transformers;

use CranachDigitalArchive\Importer\Modules\Graphics\Entities\Graphic;
use CranachDigitalArchive\Importer\Interfaces\Pipeline\ProducerInterface;
use CranachDigitalArchive\Importer\Pipeline\Hybrid;
use Error;

class ConditionDeterminer extends Hybrid
{
    private static $conditionLangMappings = [
        'de' => [
            [
                'patterns' => [
                    '/^I\.\s*zustand/i',
                    '/^1\.\s*auflage/i',
                ],
                'value' =>  1,
            ],
            [
                'patterns' => [
                    '/^II\.\s*zustand/i',
                    '/^2\.\s*auflage/i',
                ],
                'value' => 2,
            ],
            [
                'patterns' => [
                    '/^III\.\s*zustand/i',
                    '/^3\.\s*auflage/i',
                ],
                'value' => 3,
            ],
        ],
        'en' => [
            [
                'patterns' => [
                    '/^1st\s*state/i',
                    '/^1st\s*edition/i',
                ],
                'value' =>  1,
            ],
            [
                'patterns' => [
                    '/^2nd\s*state/i',
                    '/^2nd\s*edition/i',
                ],
                'value' => 2,
            ],
            [
                'patterns' => [
                    '/^3rd\s*state/i',
                    '/^3rd\s*edition/i',
                ],
                'value' => 3,
            ],
        ],
    ];
    private $conditionLevelCache = [];


    private function __construct()
    {
    }


    public static function new()
    {
        return new self;
    }

    public function handleItem($item): bool
    {
        if (!($item instanceof Graphic)) {
            throw new Error('Pushed item is not of expected class \'Graphic\'');
        }

        $inventoryNumber = $item->getInventoryNumber();

        if (!isset($this->conditionLevelCache[$inventoryNumber])) {
            $this->conditionLevelCache[$inventoryNumber] = $this->getConditionLevel(
                $item,
                $item->getConditionLevel(),
            );
        }

        $item->setConditionLevel($this->conditionLevelCache[$inventoryNumber]);

        $this->next($item);
        return true;
    }


    private function getConditionLevel(Graphic $graphic, $conditionLevel = 0): int
    {
        $classification = $graphic->getClassification();

        if (
            is_null($classification)
            || !isset(self::$conditionLangMappings[$graphic->getLangCode()])
        ) {
            return $conditionLevel;
        }

        $condition = trim($classification->getCondition());
        $conditionMappings = self::$conditionLangMappings[$graphic->getLangCode()];

        foreach ($conditionMappings as $conditionMapping) {
            foreach ($conditionMapping['patterns'] as $pattern) {
                if (preg_match($pattern, $condition) === 1) {
                    return $conditionMapping['value'];
                }
            }
        }

        return $conditionLevel;
    }


    public function done(ProducerInterface $producer)
    {
        parent::done($producer);
        $this->cleanUp();
    }


    private function cleanUp()
    {
        $this->conditionLevelCache = [];
    }
}
