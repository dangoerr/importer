<?php

namespace CranachDigitalArchive\Importer\Modules\Graphics\Exporters;

use Error;
use CranachDigitalArchive\Importer\Interfaces\Exporters\IFileExporter;
use CranachDigitalArchive\Importer\Interfaces\Entities\IBaseItem;
use CranachDigitalArchive\Importer\Interfaces\Pipeline\ProducerInterface;
use CranachDigitalArchive\Importer\Modules\Graphics\Entities\Graphic;
use CranachDigitalArchive\Importer\Pipeline\Consumer;


/**
 * Graphics exporter on a json flat file base (one file per language)
 */
class GraphicsJSONLangExporter extends Consumer implements IFileExporter {

	private $fileExt = 'json';
	private $filename = null;
	private $dirname = null;
	private $langBuckets = [];
	private $done = false;


	private function __construct()
	{
	}


	public static function withDestinationAt(string $destFilepath)
	{
		$exporter = new self;

		$filename = basename($destFilepath);
		$exporter->dirname = trim(dirname($destFilepath));

		$splitFilename = array_map('trim', explode('.', $filename));

		if (count($splitFilename) === 2 && strlen($splitFilename[1])) {
			$exporter->fileExt = $splitFilename[1];
		}

		$exporter->filename = $splitFilename[0];

		return $exporter;
	}


	function handleItem($item): bool
	{
		if (!($item instanceof Graphic)) {
			throw new Error('Pushed item is not of expected class \'Graphic\'!');
		}

		if ($this->done) {
			throw new Error('Can\'t push more items after done() was called!');
		}

		if (!isset($this->langBuckets[$item->getLangCode()])) {
			$this->langBuckets[$item->getLangCode()] = [];
		}

		$this->langBuckets[$item->getLangCode()][] = $item;

		return true;
	}


	function done(ProducerInterface $producer)
	{
		if (is_null($this->dirname) || empty($this->dirname)
	     || is_null($this->filename) || empty($this->filename)) {
			throw new Error('No filepath for JSON graphics export set!');
		}

		foreach ($this->langBuckets as $langCode => $items) {
			$filename = $this->filename . '.' . $langCode . '.' . $this->fileExt;
			$destFilepath = $this->dirname . DIRECTORY_SEPARATOR . $filename;

			$data = json_encode(array('items' => $items), JSON_PRETTY_PRINT);

			if(!file_exists($this->dirname)) {
				mkdir($this->dirname, 0777, TRUE);
			}

			file_put_contents($destFilepath, $data);
		}

		$this->done = true;
	}


	public function error($error)
	{
		echo get_class($this) . ": Error -> " . $error . "\n";
	}

}