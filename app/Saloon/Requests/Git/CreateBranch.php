<?php

namespace Modules\News\Saloon\Requests\Git;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class CreateBranch extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        protected string $owner,
        protected string $repo,
        protected string $branchName,
        protected string $sha
    ) {}

    public function resolveEndpoint(): string
    {
        return "/repos/{$this->owner}/{$this->repo}/git/refs";
    }

    protected function defaultBody(): array
    {
        return [
            'ref' => "refs/heads/{$this->branchName}",
            'sha' => $this->sha,
        ];
    }
}
