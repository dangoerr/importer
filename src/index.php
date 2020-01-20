<?php

require_once 'Language.php';
require_once 'process/Pipeline.php';

require_once 'importers/xml/PaintingsImporter.php';
require_once 'exporters/PaintingsJSONLangExporter.php';

require_once 'importers/xml/GraphicsImporter.php';
require_once 'exporters/GraphicsJSONLangExistenceTypeExporter.php';
require_once 'postProcessors/graphic/RemoteImageExistenceChecker.php';
require_once 'postProcessors/graphic/ConditionDeterminer.php';

require_once 'importers/xml/GraphicsRestorationImporter.php';
require_once 'exporters/GraphicsRestorationJSONExporter.php';

require_once 'importers/xml/LiteratureReferencesImporter.php';
require_once 'exporters/LiteratureReferencesJSONExporter.php';

use CranachImport\Process\Pipeline;

use CranachImport\Importers\XML\PaintingsImporter;
use CranachImport\Exporters\PaintingsJSONLangExporter;

use CranachImport\Importers\XML\GraphicsImporter;
use CranachImport\Exporters\GraphicsJSONLangExistenceTypeExporter;
use CranachImport\PostProcessors\Graphic\RemoteImageExistenceChecker;
use CranachImport\PostProcessors\Graphic\ConditionDeterminer;

use CranachImport\Importers\XML\GraphicsRestorationImporter;
use CranachImport\Exporters\GraphicsRestorationJSONExporter;

use CranachImport\Importers\XML\LiteratureReferencesImporter;
use CranachImport\Exporters\LiteratureReferencesJSONExporter;

/* @TODO: Use better determination and handling of source- and destination-paths */

function importPaintings() {
	$paintingsXMLSourceFilePaths = [
		'../content/20191122/CDA_Datenübersicht_P1_20191122.xml',
		'../content/20191122/CDA_Datenübersicht_P2_20191122.xml',
		'../content/20191122/CDA_Datenübersicht_P3_20191122.xml',
	];
	$paintingsJSONDestinationPath = '../output/20191122/cda-paintings-v2.json';

	$paintingsXmlImporter = new PaintingsImporter($paintingsXMLSourceFilePaths);
	$paintingsJsonExporter = new PaintingsJSONLangExporter($paintingsJSONDestinationPath);

	$pipe = new Pipeline;
	$pipe->addExporter($paintingsJsonExporter);

	$paintingsXmlImporter->registerPipeline($pipe);

	$paintingsXmlImporter->start();
}

function importGraphics() {
	$graphicsXMLSourceFilePath = '../content/20191122/CDA-GR_Datenuebersicht_20191122.xml';
	$graphicsJSONDestinationPath = '../output/20191122/cda-graphics-v2.json';

	$graphicsXmlImporter = new GraphicsImporter($graphicsXMLSourceFilePath);
	$graphicsJsonExporter = new GraphicsJSONLangExistenceTypeExporter($graphicsJSONDestinationPath);
	$graphicRemoteImageExitenceChecker = new RemoteImageExistenceChecker('../.cache');
	$graphicConditionDeterminer = new ConditionDeterminer();

	$pipe = new Pipeline;
	$pipe->addExporter($graphicsJsonExporter);
	$pipe->addPostProcessors([
		$graphicRemoteImageExitenceChecker,
		$graphicConditionDeterminer,
	]);

	$graphicsXmlImporter->registerPipeline($pipe);

	$graphicsXmlImporter->start();
}

function importGraphicsRestoration() {
	$graphicsRestorationXMLSourceFilePath = '../content/20191122/CDA-GR_RestDokumente_20191122.xml';
	$graphicsRestorationJSONDestinationPath = '../output/20191122/cda-graphics-restoration-v2.json';

	$graphicsRestorationXmlImporter = new GraphicsRestorationImporter(
		$graphicsRestorationXMLSourceFilePath,
	);
	$graphicsRestorationJsonExporter = new GraphicsRestorationJSONExporter(
		$graphicsRestorationJSONDestinationPath,
	);

	$pipe = new Pipeline;
	$pipe->addExporter($graphicsRestorationJsonExporter);

	$graphicsRestorationXmlImporter->registerPipeline($pipe);

	$graphicsRestorationXmlImporter->start();
}

function importLiteratureReferences() {
	$literatureReferencesXMLSourceFilePath = '../content/20191122/CDA_Literaturverweise_20191122.xml';
	$literatureReferencesJSONDestinationPath = '../output/20191122/cda-literaturereferences-v2.json';

	$literatureReferencesXmlImporter = new LiteratureReferencesImporter($literatureReferencesXMLSourceFilePath);
	$literatureReferencesJsonExporter = new LiteratureReferencesJSONExporter($literatureReferencesJSONDestinationPath);

	$pipe = new Pipeline;
	$pipe->addExporter($literatureReferencesJsonExporter);

	$literatureReferencesXmlImporter->registerPipeline($pipe);

	$literatureReferencesXmlImporter->start();
}

importPaintings();
importGraphics();
importGraphicsRestoration();
importLiteratureReferences();