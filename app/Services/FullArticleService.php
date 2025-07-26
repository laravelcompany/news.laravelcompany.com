<?php

namespace App\Services;

use App\Saloon\Connectors\BackendConnector;
use App\Saloon\Requests\Backend\GetArticleRequest;

class FullArticleService
{

    public function getFullArticle(string $link)
    {
        $connector = new BackendConnector();

        $response = $connector->send(new GetArticleRequest($link));

        return $response->json();
    }
}