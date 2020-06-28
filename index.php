<?php

namespace CranachDigitalArchive\Importer;

require_once __DIR__ . '/vendor/autoload.php';

use CranachDigitalArchive\Importer\Modules\Paintings\Transformers\ExtenderWithThesaurus;
use CranachDigitalArchive\Importer\Modules\Thesaurus\Exporters\ThesaurusMemoryExporter;
use CranachDigitalArchive\Importer\Pipeline\Pipeline;

use CranachDigitalArchive\Importer\Modules\Main\Transformers\RemoteImageExistenceChecker;
use CranachDigitalArchive\Importer\Modules\Graphics\Loaders\XML\GraphicsLoader;
use CranachDigitalArchive\Importer\Modules\Graphics\Exporters\GraphicsJSONLangExistenceTypeExporter;
use CranachDigitalArchive\Importer\Modules\Graphics\Exporters\GraphicsElasticsearchLangExporter;
use CranachDigitalArchive\Importer\Modules\Graphics\Transformers\ConditionDeterminer;
use CranachDigitalArchive\Importer\Modules\Restorations\Loaders\XML\RestorationsLoader;
use CranachDigitalArchive\Importer\Modules\Restorations\Exporters\RestorationsJSONLangExporter;
use CranachDigitalArchive\Importer\Modules\LiteratureReferences\Loaders\XML\LiteratureReferencesLoader;
use CranachDigitalArchive\Importer\Modules\LiteratureReferences\Exporters\LiteratureReferencesJSONExporter;
use CranachDigitalArchive\Importer\Modules\Paintings\Loaders\XML\PaintingsLoader;
use CranachDigitalArchive\Importer\Modules\Paintings\Exporters\PaintingsJSONLangExporter;
use CranachDigitalArchive\Importer\Modules\Paintings\Exporters\PaintingsElasticsearchLangExporter;
use CranachDigitalArchive\Importer\Modules\Archivals\Loaders\XML\ArchivalsLoader;
use CranachDigitalArchive\Importer\Modules\Archivals\Exporters\ArchivalsJSONLangExporter;
use CranachDigitalArchive\Importer\Modules\Thesaurus\Loaders\XML\ThesaurusLoader;
use CranachDigitalArchive\Importer\Modules\Thesaurus\Exporters\ThesaurusJSONExporter;

/* Thesaurus */
$thesaurusLoader = ThesaurusLoader::withSourceAt(
    './input/20191122/CDA_Thesaurus_20191021.xml'
);
$thesaurusDestination = ThesaurusJSONExporter::withDestinationAt(
    './output/20191122/cda-thesaurus-v2.json',
);
$thesaurusMemoryDestination = ThesaurusMemoryExporter::new();

$thesaurusLoader->pipe(
    $thesaurusDestination,
);
$thesaurusLoader->pipe(
    $thesaurusMemoryDestination,
);

Pipeline::new()->withNodes(
    $thesaurusLoader,
    $thesaurusDestination,
    $thesaurusMemoryDestination,
)->start();



/* Paintings */
$paintingsLoader = PaintingsLoader::withSourcesAt([
    './input/20200110/CDA_Datenübersicht_P1_20200110.xml',
    './input/20200110/CDA_Datenübersicht_P2_20200110.xml',
    './input/20200110/CDA_Datenübersicht_P3_20200110.xml',
]);
$paintingsRemoteImageExistenceChecker = RemoteImageExistenceChecker::withCacheAt(
    './.cache',
    'pyramid',
    'paintingssRemoteImageExistenceChecker',
);
$paintingsThesaurusExtender = ExtenderWithThesaurus::new($thesaurusMemoryDestination->getData());
$paintingsDestination = PaintingsJSONLangExporter::withDestinationAt(
    './output/20200110/cda-paintings-v2.json',
);
$paintingsElasticsearchBulkDestination = PaintingsElasticsearchLangExporter::withDestinationAt(
    './output/20200110/elasticsearch/cda-paintings-v2.bulk',
);

$paintingsLoader->pipe(
    $paintingsRemoteImageExistenceChecker,
);

$paintingsRemoteImageExistenceChecker->pipe(
    $paintingsDestination,
);

$paintingsRemoteImageExistenceChecker->pipe(
    $paintingsThesaurusExtender,
);
$paintingsThesaurusExtender->pipe(
    $paintingsElasticsearchBulkDestination,
);



/* PaintingsRestorations */
$paintingRestorationsLoader = RestorationsLoader::withSourcesAt([
    './input/20191122/CDA_RestDukomente_P1_20191122.xml',
    './input/20191122/CDA_RestDokumente_P2_20191122.xml',
    './input/20191122/CDA_RestDokumente_P3_20191122.xml',
]);
$paintingRestorationsDestination = RestorationsJSONLangExporter::withDestinationAt(
    './output/20191122/cda-paintings-restoration-v2.json',
);

$paintingRestorationsLoader->pipe(
    $paintingRestorationsDestination,
);


/* Graphics */
$graphicsLoader = GraphicsLoader::withSourceAt(
    './input/20191122/CDA-GR_Datenuebersicht_20191122.xml',
);
$graphicsRemoteImageExistenceChecker = RemoteImageExistenceChecker::withCacheAt(
    './.cache',
    '01_Overall',
    'graphicsRemoteImageExistenceChecker',
);
$graphicsConditionDeterminer = ConditionDeterminer::new();
$graphicsDestination = GraphicsJSONLangExistenceTypeExporter::withDestinationAt(
    './output/20191122/cda-graphics-v2.json',
);
$graphicsElasticsearchBulkDestination = GraphicsElasticsearchLangExporter::withDestinationAt(
    './output/20191122/elasticsearch/cda-graphics-v2.bulk',
);

$graphicsLoader->pipe(
    $graphicsRemoteImageExistenceChecker,
    $graphicsConditionDeterminer,
);

$graphicsConditionDeterminer->pipe(
    $graphicsDestination,
);

$graphicsConditionDeterminer->pipe(
    $graphicsElasticsearchBulkDestination,
);


/* GraphicRestorations */
$graphicRestorationsLoader = RestorationsLoader::withSourcesAt([
    './input/20191122/CDA-GR_RestDokumente_20191122.xml',
]);
$graphicRestorationsDestination = RestorationsJSONLangExporter::withDestinationAt(
    './output/20191122/cda-graphics-restoration-v2.json',
);

$graphicRestorationsLoader->pipe(
    $graphicRestorationsDestination,
);


/* LiteratureReferences */
$literatureReferencesLoader = LiteratureReferencesLoader::withSourceAt(
    './input/20191122/CDA_Literaturverweise_20191122.xml',
);
$literatureReferencesDestination = LiteratureReferencesJSONExporter::withDestinationAt(
    './output/20191122/cda-literaturereferences-v2.json',
);

$literatureReferencesLoader->pipe(
    $literatureReferencesDestination,
);


/* Archivals */
$archivalsLoader = ArchivalsLoader::withSourceAt(
    './input/20191122/CDA-A_Datenübersicht_20191122.xml',
);
$archivalsDestination = ArchivalsJSONLangExporter::withDestinationAt(
    './output/20191122/cda-archivals-v2.json',
);

$archivalsLoader->pipe(
    $archivalsDestination,
);


/* Pipeline */

Pipeline::new()->withNodes(
/* Paintings */
    $paintingsLoader,
    $paintingsRemoteImageExistenceChecker,
    $paintingsDestination,

    $paintingsThesaurusExtender,
    $paintingsElasticsearchBulkDestination,

    /* PaintingRestorations */
    $paintingRestorationsLoader,
    $paintingRestorationsDestination,

    /* Graphics */
    $graphicsLoader,
    $graphicsRemoteImageExistenceChecker,
    $graphicsConditionDeterminer,
    $graphicsDestination,

    /* GraphicRestorations */
    $graphicRestorationsLoader,
    $graphicRestorationsDestination,

    /* LiteratureReferences */
    $literatureReferencesLoader,
    $literatureReferencesDestination,

    /* Archivals */
    $archivalsLoader,
    $archivalsDestination,
)->start();


$thesaurusMemoryDestination->cleanUp();
