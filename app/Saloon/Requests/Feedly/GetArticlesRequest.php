<?php
declare(strict_types=1);
namespace Modules\News\Saloon\Requests\Feedly;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\CachePlugin\Contracts\Driver;


class GetArticlesRequest extends Request
{
    protected Method $method = Method::GET;
    public function __construct(
        protected string $feed,
    ){}


    public function resolveEndpoint(): string
    {
        return '/streams/contents';
    }


    protected function defaultQuery(): array
    {
        return [
            'streamId' => $this->feed
        ];

    }
    protected function getCacheableMethods(): array
    {
        return [Method::GET];
    }
}
