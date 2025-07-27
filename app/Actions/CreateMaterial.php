<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\MaterialData;
use App\Jobs\FetchAndUpdateMaterialImage;
use App\Models\Material;
use App\Models\Source;
use App\Services\BackendService;
use App\Services\FullArticleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class CreateMaterial
{
    public function handle(Source $source, MaterialData $materialData): Material
    {
        if ($material = Material::where('url', $materialData->url)->first()) {
            return $material;
        }



        return DB::transaction(function () use ($source, $materialData): Material {

            $material = $source
                ->materials()
                ->create([
                    'title' => $materialData->title,
                    'description' => $materialData->description,
                    'body' => $materialData->body,
                    'author' => $materialData->author ?? "Laravel Agency",
                    'published_at' => $materialData->publishedAt,
                    'feed_id' => $materialData->feedId,
                    'duration' => $materialData->duration,
                    'is_displayed' => $materialData->isDisplayed,
                    'url' => $materialData->url,
                    'image_url' => $materialData->imageUrl,
                ]);

            if (filled($material->image_url)) {
                FetchAndUpdateMaterialImage::dispatch($material)->afterCommit();
            }

            $this->updateMaterialWithBackendApi($material);

            return $material;
        });
    }


    // do the logic for the extract full article

    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws \JsonException
     */
    public function updateMaterialWithBackendApi(Material $material): void
    {
        $service = new BackendService();
        $response = $service->getFullArticle($material->url);
        Log::info('response', [$response]);
        if (filled($response)) {
            //change this to right field
            $material->update(
                ['body' => $response['data']['html']]);
        }
    }



}
