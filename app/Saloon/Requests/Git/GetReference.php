<?php


namespace Modules\News\Saloon\Requests\Git;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetReference extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $owner,
        protected string $repo,
        protected string $reference
    ) {}

    public function resolveEndpoint(): string
    {
        // Format the reference to use the refs/heads/ format if it's a branch name
        $ref = $this->reference;
        if (!str_starts_with($ref, 'refs/')) {
            $ref = "heads/{$ref}";
        }

        return "/repos/{$this->owner}/{$this->repo}/git/ref/refs/{$ref}";
    }
}
