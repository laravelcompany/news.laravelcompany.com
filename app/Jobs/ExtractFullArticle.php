<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateMaterial;
use App\Data\MaterialData;
use App\Models\Source;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use SimplePie\Item;

class ExtractFullArticle implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Item $item
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CreateMaterial $createMaterial): void
    {
        $service = new FullArticleService();
        $fullArticle = $service->getFullArticle($this->item->get_link());
        
        Log::info(json_encode($fullArticle));

        $createMaterial->handle(
            $this->source,
            MaterialData::create($this->source->type, $this->item),
        );
    }
}
