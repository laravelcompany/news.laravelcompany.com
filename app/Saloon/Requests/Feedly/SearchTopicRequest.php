<?php
declare(strict_types=1);
namespace Modules\News\Saloon\Requests\Feedly;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\CachePlugin\Contracts\Driver;


class SearchTopicRequest extends Request
{

    protected Method $method = Method::GET;
    public function __construct(
        protected string $topic,
    ){}


    public function resolveEndpoint(): string
    {
        return '/recommendations/topics/'  . $this->topic;
    }

    protected function defaultQuery(): array
    {
        return [
            'locale' => "en",
        ];
    }

    protected function getCacheableMethods(): array
    {
        return [Method::GET];
    }
}
