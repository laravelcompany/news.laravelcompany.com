<?php

namespace Modules\News\Saloon\Requests\Git;


use Saloon\Enums\Method;
use Saloon\Http\Request;


class CreatePullRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        protected string $title,
        protected string $body,
        protected string $head,
        protected string $base = 'main'
    ) {}

    public function resolveEndpoint(): string
    {
        return "/repos/{$this->owner}/{$this->repo}/pulls";
    }

    protected function defaultBody(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'head' => $this->head,
            'base' => $this->base,
        ];
    }
}
