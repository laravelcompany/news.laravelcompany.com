<?php

namespace Modules\News\Saloon\Requests\News;

use Carbon\Carbon;

use Saloon\Http\Request;
use Saloon\Enums\Method;

class AllNewsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $topic,
    ){}

    public function resolveEndpoint(): string
    {
        return '/everything';
    }

    protected function defaultQuery(): array
    {
        return [
            'q' => $this->topic,
            'sortBy' => 'publishedAt',
            'apiKey' => config('news.api.key'),
            'language' => 'en',
            'from' => Carbon::now()->subDays(1)->format('Y-m-d'),
        ];
    }


    public function cacheExpiryInSeconds(): int
    {
        return 3600*24; // One Day
    }

    protected function getCacheableMethods(): array
    {
        return [Method::GET];
    }
}
