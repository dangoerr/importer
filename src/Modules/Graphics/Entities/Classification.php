<?php

namespace CranachDigitalArchive\Importer\Modules\Graphics\Entities;

/**
 * Representing a single classifcation, including the condition
 */
class Classification
{
    public $classification = '';
    public $condition = '';


    public function __construct()
    {
    }


    public function setClassification(string $classification)
    {
        $this->classification = $classification;
    }


    public function getClassification(): string
    {
        return $this->classification;
    }


    public function setCondition(string $condition)
    {
        $this->condition = $condition;
    }


    public function getCondition(): string
    {
        return $this->condition;
    }
}
