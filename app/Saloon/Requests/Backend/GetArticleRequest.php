<?php

namespace App\Saloon\Requests\Backend;



use Saloon\Enums\Method;
use Saloon\Http\Request;


class GetArticleRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        private readonly string $link,
        private readonly bool   $cache = true,
        private readonly array  $excludeTypes = [
            'MONEY',
            'LANGUAGE',
            'CARDINAL',
            'ORDINAL',
            'DATE',
            'PERCENT',
            'TIME',
            'QUANTITY'
        ],
        private readonly int $minLength = 1
    ) {}

    public function resolveEndpoint(): string
    {
        $queryParams = [
            'min_length' => $this->minLength
        ];

        foreach ($this->excludeTypes as $type) {
            $queryParams['exclude_types'][] = $type;
        }

        return '/api/v1/nlp/article?' . http_build_query($queryParams);
    }

    protected function defaultBody(): array
    {
        return [
            'cache' => $this->cache,
            'link' => $this->link,
        ];
    }
}
