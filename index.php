<?php

namespace CranachDigitalArchive\Importer;

require_once __DIR__ . '/vendor/autoload.php';

use CranachDigitalArchive\Importer\Modules\Main\Transformers\RemoteImageExistenceChecker;
use CranachDigitalArchive\Importer\Modules\Graphics\Loaders\XML\GraphicsLoader;
use CranachDigitalArchive\Importer\Modules\Graphics\Exporters\GraphicsJSONLangExistenceTypeExporter;
use CranachDigitalArchive\Importer\Modules\Graphics\Exporters\GraphicsElasticsearchLangExporter;
use CranachDigitalArchive\Importer\Modules\Graphics\Transformers\ConditionDeterminer;
use CranachDigitalArchive\Importer\Modules\Graphics\Transformers\ExtenderWithThesaurus as GraphicsExtenderWithThesaurus;
use CranachDigitalArchive\Importer\Modules\Graphics\Transformers\ExtenderWithRestorations as GraphicsExtenderWithRestorations;
use CranachDigitalArchive\Importer\Modules\Restorations\Loaders\XML\RestorationsLoader;
use CranachDigitalArchive\Importer\Modules\Restorations\Exporters\RestorationsMemoryExporter;
use CranachDigitalArchive\Importer\Modules\LiteratureReferences\Loaders\XML\LiteratureReferencesLoader;
use CranachDigitalArchive\Importer\Modules\LiteratureReferences\Exporters\LiteratureReferencesJSONExporter;
use CranachDigitalArchive\Importer\Modules\Paintings\Loaders\XML\PaintingsLoader;
use CranachDigitalArchive\Importer\Modules\Paintings\Exporters\PaintingsJSONLangExporter;
use CranachDigitalArchive\Importer\Modules\Paintings\Exporters\PaintingsElasticsearchLangExporter;
use CranachDigitalArchive\Importer\Modules\Paintings\Transformers\ExtenderWithThesaurus as PaintingsExtenderWithThesaurus;
use CranachDigitalArchive\Importer\Modules\Paintings\Transformers\ExtenderWithRestorations as PaintingsExtenderWithRestorations;
use CranachDigitalArchive\Importer\Modules\Archivals\Loaders\XML\ArchivalsLoader;
use CranachDigitalArchive\Importer\Modules\Archivals\Exporters\ArchivalsJSONLangExporter;
use CranachDigitalArchive\Importer\Modules\Thesaurus\Loaders\XML\ThesaurusLoader;
use CranachDigitalArchive\Importer\Modules\Thesaurus\Exporters\ThesaurusJSONExporter;
use CranachDigitalArchive\Importer\Modules\Thesaurus\Exporters\ThesaurusMemoryExporter;

/* Thesaurus */
$thesaurusMemoryDestination = ThesaurusMemoryExporter::new(); /* needed later for graphics and paintings */

ThesaurusLoader::withSourceAt(
    './input/20200716/CDA_Thesaurus_20200716.xml'
)->pipe(
    ThesaurusJSONExporter::withDestinationAt(
        './output/20200716/cda-thesaurus-v2.json',
    ),
    $thesaurusMemoryDestination,
)->run(); /* and we have to run it directly */


/* PaintingsRestorations */
$paintingsRestorationMemoryDestination = RestorationsMemoryExporter::new();

RestorationsLoader::withSourcesAt([
    './input/20200716/CDA_RestDokumente_P1_20200716.xml',
    './input/20200716/CDA_RestDokumente_P2_20200716.xml',
    './input/20200716/CDA_RestDokumente_P3_20200716.xml',
])->pipe(
    $paintingsRestorationMemoryDestination,
)->run(); /* and we have to run it directly */


/* Paintings */
$paintingsRemoteImageExistenceChecker = RemoteImageExistenceChecker::withCacheAt(
    './.cache',
    'pyramid',
    'paintingssRemoteImageExistenceChecker',
);
$paintingsRestorationExtender = PaintingsExtenderWithRestorations::new($paintingsRestorationMemoryDestination);
$paintingsThesaurusExtender = PaintingsExtenderWithThesaurus::new($thesaurusMemoryDestination);
$paintingsDestination = PaintingsJSONLangExporter::withDestinationAt(
    './output/20200716/cda-paintings-v2.json',
);
$paintingsElasticsearchBulkDestination = PaintingsElasticsearchLangExporter::withDestinationAt(
    './output/20200716/elasticsearch/cda-paintings-v2.bulk',
);

$paintingsLoader = PaintingsLoader::withSourcesAt([
    './input/20200716/CDA_Datenübersicht_P1_20200716.xml',
    './input/20200716/CDA_Datenübersicht_P2_20200716.xml',
    './input/20200716/CDA_Datenübersicht_P3_20200716.xml',
])->pipe(
    $paintingsRemoteImageExistenceChecker->pipe(
        $paintingsRestorationExtender->pipe(
            $paintingsDestination,
            $paintingsThesaurusExtender->pipe(
                $paintingsElasticsearchBulkDestination,
            ),
        ),
    ),
);


/* GraphicRestorations */
$graphicsRestorationMemoryDestination = RestorationsMemoryExporter::new();

RestorationsLoader::withSourcesAt([
    './input/20200716/CDA-GR_RestDokumente_20200716.xml',
])->pipe(
    $graphicsRestorationMemoryDestination,
)->run(); /* and we have to run it directly */


/* Graphics */
$graphicsRemoteImageExistenceChecker = RemoteImageExistenceChecker::withCacheAt(
    './.cache',
    '01_Overall',
    'graphicsRemoteImageExistenceChecker',
);
$graphicsConditionDeterminer = ConditionDeterminer::new();
$graphicsRestorationExtender = GraphicsExtenderWithRestorations::new($graphicsRestorationMemoryDestination);
$graphicsThesaurusExtender = GraphicsExtenderWithThesaurus::new($thesaurusMemoryDestination);
$graphicsDestination = GraphicsJSONLangExistenceTypeExporter::withDestinationAt(
    './output/20200716/cda-graphics-v2.json',
);
$graphicsElasticsearchBulkDestination = GraphicsElasticsearchLangExporter::withDestinationAt(
    './output/20200716/elasticsearch/cda-graphics-v2.bulk',
);

$graphicsLoader = GraphicsLoader::withSourceAt(
    './input/20200716/CDA-GR_Datenübersicht_20200716.xml',
)->pipe(
    $graphicsRemoteImageExistenceChecker->pipe(
        $graphicsConditionDeterminer->pipe(
            $graphicsRestorationExtender->pipe(
                $graphicsDestination,
                $graphicsThesaurusExtender->pipe(
                    $graphicsElasticsearchBulkDestination,
                ),
            ),
        ),
    ),
);


/* LiteratureReferences */
$literatureReferencesLoader = LiteratureReferencesLoader::withSourceAt(
    './input/20200716/CDA_Literaturverweise_20200716.xml',
)->pipe(
    LiteratureReferencesJSONExporter::withDestinationAt(
        './output/20200716/cda-literaturereferences-v2.json',
    ),
);


/* Archivals */
$archivalsLoader = ArchivalsLoader::withSourceAt(
    './input/20200716/CDA-A_Datenübersicht_20200716.xml',
)->pipe(
    ArchivalsJSONLangExporter::withDestinationAt(
        './output/20200716/cda-archivals-v2.json',
    ),
);


/* Trigger loaders and final exit routines */
$loaders = [
    $paintingsLoader,
    $graphicsLoader,
    $literatureReferencesLoader,
    $archivalsLoader,
];

foreach ($loaders as $loader) {
    $loader->run();
}

$thesaurusMemoryDestination->cleanUp();
$paintingsRestorationMemoryDestination->cleanUp();
$graphicsRestorationMemoryDestination->cleanUp();
