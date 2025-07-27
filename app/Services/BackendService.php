<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class BackendService
{
    public function getFullArticle(string $link): array
    {
        Log::info("📄 Getting full article for link: " . $link);

        $connector = new \App\Saloon\Connectors\BackendConnector();

        return $connector->send(new \App\Saloon\Requests\Backend\GetArticleRequest($link))->json();

    }

    public function pingBlog(string $sourceUrl, string $targetUrl): void
    {
        $client = new Client([
            'timeout' => 10,
            'headers' => ['Content-Type' => 'text/xml']
        ]);

        $xmlPayload = $this->buildPingbackXml($sourceUrl, $targetUrl);

        try {
            $response = $client->post('https://laravelagency.wordpress.com/xmlrpc.php', [
                'body' => $xmlPayload
            ]);

            Log::info("✅ Pingback successful: " . $response->getBody()->getContents());

        } catch (RequestException $e) {
            Log::error("❌ Pingback failed: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("⚠️ Unexpected error in pingBlog: " . $e->getMessage());
        }
    }

    private function buildPingbackXml(string $sourceUrl, string $targetUrl): string
    {
        return "<?xml version='1.0'?>
        <methodCall>
          <methodName>pingback.ping</methodName>
          <params>
            <param>
              <value><string>{$sourceUrl}</string></value>
            </param>
            <param>
              <value><string>{$targetUrl}</string></value>
            </param>
          </params>
        </methodCall>";
    }
}
