<?php

namespace CranachDigitalArchive\Importer\Modules\Graphics\Transformers;

use Error;
use CranachDigitalArchive\Importer\Modules\Main\Entities\AbstractImagesItem;
use CranachDigitalArchive\Importer\Interfaces\Pipeline\ProducerInterface;
use CranachDigitalArchive\Importer\Pipeline\Hybrid;

class RemoteGraphicImageExistenceCheckerV1 extends Hybrid
{
    const REPRESENTATIVE = 'representative';
    const OVERALL = 'overall';
    const REVERSE = 'reverse';
    const IRR = 'irr';
    const X_RADIOGRAPH = 'x-radiograph';
    const UV_LIGHT = 'uv-light';
    const DETAIL = 'detail';
    const PHOTOMICROGRAPH = 'photomicrograph';
    const CONSERVATION = 'conservation';
    const OTHER = 'other';
    const ANALYSIS = 'analysis';
    const RKD = 'rkd';
    const KOE = 'koe';
    const REFLECTED_LIGHT = 'reflected-light';
    const TRANSMITTED_LIGHT = 'transmitted-light';


    private $serverHost = 'http://lucascranach.org';
    private $remoteImageBasePath = 'imageserver/%s/%s';
    private $remoteImageDataPath = 'imageserver/%s/imageData-1.0.json';
    private $remoteImageSubDirectoryName = null;
    private $remoteImageTypeAccessorFunc = null;
    private $cacheDir = null;
    private $cacheFilename = 'remoteGraphicImageExistenceChecker-v1';
    private $cacheFileSuffix = '.cache';
    private $cache = [];


    private function __construct()
    {
    }

    public static function withCacheAt(
        string $cacheDir,
        $remoteImageTypeAccessorFunc,
        string $cacheFilename = ''
    ) {
        $checker = new self;

        if (is_string($remoteImageTypeAccessorFunc) && !empty($remoteImageTypeAccessorFunc)) {
            $imageType = $remoteImageTypeAccessorFunc;
            $checker->remoteImageTypeAccessorFunc = function () use ($imageType) {
                return $imageType;
            };
        }

        if (is_callable($remoteImageTypeAccessorFunc)) {
            $checker->remoteImageTypeAccessorFunc = $remoteImageTypeAccessorFunc;
        }

        if (is_string($cacheDir)) {
            if (!file_exists($cacheDir)) {
                @mkdir($cacheDir, 0777, true);
            }

            $checker->cacheDir = $cacheDir;

            if (!empty($cacheFilename)) {
                $checker->cacheFilename = $cacheFilename;
            }

            $checker->restoreCache();
        }

        if (is_null($checker->cacheDir)) {
            throw new Error('RemoteImageExistenceChecker: Missing cache directory for');
        }

        if (is_null($checker->remoteImageTypeAccessorFunc)) {
            throw new Error('RemoteImageExistenceChecker: Missing remote image type accessor');
        }

        return $checker;
    }


    private function getCachePath(): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . $this->cacheFilename . $this->cacheFileSuffix;
    }


    public function handleItem($item): bool
    {
        if (!($item instanceof AbstractImagesItem)) {
            throw new Error('Pushed item is not of expected class \'AbstractImagesItem\'');
        }

        $id = $item->getImageId();

        if (empty($id)) {
            echo '  Missing imageId for \'' . $item->getId() . "'\n";
            $this->next($item);
            return false;
        }

        /* Fill cache to avoid unnecessary duplicate requests for the same resource */
        if (is_null($this->getCacheFor($id))) {
            $url = $this->buildURLForInventoryNumber($id);
            $result = $this->getRemoteImageDataResource($url);
            $rawImagesData = null;

            if (!is_null($result)) {
                $rawImagesData = $result;
            } else {
                echo '  Missing remote images for \'' . $id . "'\n";
            }

            $dataToCache = $this->createCacheData($rawImagesData);
            $this->updateCacheFor($id, $dataToCache);
        }

        $cachedItem = $this->getCacheFor($id);
        $cachedImagesForObject = $cachedItem['rawImagesData'];

        if (!is_null($cachedImagesForObject)) {
            $imageType = call_user_func_array(
                $this->remoteImageTypeAccessorFunc,
                [$item, $cachedImagesForObject]
            );

            if ($imageType) {
                $preparedImages = $this->prepareRawImages($id, $imageType, $cachedImagesForObject);
                $item->setImages($preparedImages);
            }
        }

        $this->next($item);
        return true;
    }


    private function buildURLForInventoryNumber(string $inventoryNumber): string
    {
        $interpolatedRemoteImageDataPath = sprintf(
            $this->remoteImageDataPath,
            $inventoryNumber,
        );

        return implode('/', [
            $this->serverHost,
            $interpolatedRemoteImageDataPath
        ]);
    }


    public function done(ProducerInterface $producer)
    {
        parent::done($producer);

        $this->storeCache();
        $this->cleanUp();
    }


    private function createCacheData(?array $data)
    {
        return [
            'rawImagesData' => $data,
        ];
    }


    private function updateCacheFor(string $key, $data)
    {
        $this->cache[$key] = $data;
    }


    private function getCacheFor(string $key, $orElse = null)
    {
        return isset($this->cache[$key]) ? $this->cache[$key]: $orElse;
    }


    private function getRemoteImageDataResource(string $url): ?array
    {
        $content = @file_get_contents($url);

        if ($content === false) {
            return null;
        }

        $statusHeader = $http_response_header[0];

        $splitStatusLine = explode(' ', $statusHeader, 3);

        if (count($splitStatusLine) !== 3) {
            throw new Error('Could not get status code for request!');
        }

        $statusCode = $splitStatusLine[1];

        /* @TODO: Check content-type on response */

        return ($statusCode[0] === '2') ? json_decode($content, true) : null;
    }


    private function prepareRawImages(
        string $inventoryNumber,
        string $imageType,
        array $cachedImagesForObject
    ): array {
        $imageTypes = [];

        $imageStack = $cachedImagesForObject['imageStack'];

        if (!isset($imageStack[$imageType])) {
            throw new Error('RemoteImageExistenceChecker: Could not find base stack item ' . $imageType);
        }
        $baseStackItem = $imageStack[$imageType];

        $imageTypes[$imageType] = $this->getPreparedImageType(
            $baseStackItem,
            $inventoryNumber,
            $imageType,
        );

        return $imageTypes;
    }


    private function getPreparedImageType($stackItem, $inventoryNumber, $imageType)
    {
        $destinationTypeStructure = [
            'infos' => [
                'maxDimensions' => [ 'width' => 0, 'height' => 0 ],
            ],
            'variants' => [],
        ];

        $destinationTypeStructure['infos']['maxDimensions'] = [
            'width' => intval($stackItem['maxDimensions']['width']),
            'height' => intval($stackItem['maxDimensions']['height']),
        ];

        $images = [];

        if ($imageType === self::REPRESENTATIVE) {
            /* representative images have no variants, so we have to wrap it in an array */
            $images = [ $stackItem['images'] ];
        } else {
            $images = $stackItem['images'];
        }

        foreach ($images as $image) {
            $destinationTypeStructure['variants'][] = $this->getPreparedImageVariant(
                $image,
                $inventoryNumber,
                $imageType,
            );
        }

        return $destinationTypeStructure;
    }


    private function getPreparedImageVariant($image, $inventoryNumber, $imageType)
    {
        /* Set default values for all supported sizes */
        $variantSizes = array_reduce(
            ['xs', 's', 'm', 'l', 'xl'],
            function ($carry, $sizeCode) {
                $carry[$sizeCode] = [
                    'dimensions' => [ 'width' => 0, 'height' => 0 ],
                    'src' => '',
                ];
                return $carry;
            },
        );

        foreach ($image as $size => $variant) {
            $dimensions = $variant['dimensions'];
            $imageTypePath = isset($variant['path']) ? $variant['path'] : $imageType;
            $src = implode('/', [
                $this->serverHost,
                sprintf($this->remoteImageBasePath, $inventoryNumber, $imageTypePath),
                $variant['src'],
            ]);

            $variantSizes[$size] = [
                'dimensions' => [
                    'width' => intval($dimensions['width']),
                    'height' => intval($dimensions['height']),
                ],
                'src' => $src,
            ];
        }

        return $variantSizes;
    }


    private function storeCache()
    {
        $cacheAsJSON = json_encode($this->cache);
        file_put_contents($this->getCachePath(), $cacheAsJSON);
    }


    private function restoreCache()
    {
        $cacheFilepath = $this->getCachePath();
        if (!file_exists($cacheFilepath)) {
            return;
        }

        $cacheAsJSON = file_get_contents($cacheFilepath);

        $this->cache = json_decode($cacheAsJSON, true);
    }


    private function cleanUp()
    {
        $this->cache = [];
    }
}
