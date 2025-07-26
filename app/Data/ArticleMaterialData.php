<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use shweshi\OpenGraph\Facades\OpenGraphFacade;
use SimplePie\Item;

class ArticleMaterialData extends MaterialData
{
    public static function from(Item $item): static
    {
        $openGraph = static::getOpenGraph($item->get_link());

        return new static(
            title: $item->get_title(),
            url: $item->get_link(),
            publishedAt: Carbon::parse($item->get_date())->timezone(config('app.timezone')),
            description: static::getDescription($openGraph, $item),
            body: self::getFullContent() ?? $item->get_content(),
            author: $item->get_author()?->get_name(),
            imageUrl: static::getImageUrl($openGraph, $item),
            feedId: $item->get_id(true),
        );
    }

    private static function getOpenGraph(string $link): array
    {
        return Arr::where(
            OpenGraphFacade::fetch($link, true),
            static fn ($value, $key): bool => filled($value)
        );
    }

    private static function getDescription(array $openGraph, Item $item): string
    {
        return $openGraph['description'] ?? $openGraph['og:description'] ?? $item->get_description();
    }

    private static function getImageUrl(array $openGraph, Item $item): ?string
    {
        $imageUrl = $openGraph['image:secure_url'] ?? $openGraph['image'] ?? $openGraph['twitter:image'] ?? null;

        if (blank($imageUrl)) {
            $imageUrl = $item->get_enclosure()?->get_thumbnail() ?? str($item->get_content())->betweenFirst('img src="', '"')->toString();
        }

        return $imageUrl ?? null;
    }

    private static function getFullContent(string $url):string
    {
        //todo implement this
        return  "";
    }
}
