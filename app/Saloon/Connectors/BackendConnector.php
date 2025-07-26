<?php
namespace App\Saloon\Connectors;

use Saloon\Http\Connector;


class BackendConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return config('services.backend.url') ?? "https://backend.izdrail.com";
    }

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

}
