<?php



namespace Modules\News\Saloon\Requests\Git;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class CheckBranchExists extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $owner,
        protected string $repo,
        protected string $branch
    ) {}

    public function resolveEndpoint(): string
    {
        return "/repos/{$this->owner}/{$this->repo}/branches/{$this->branch}";
    }
}
