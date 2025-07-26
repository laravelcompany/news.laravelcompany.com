<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Parser;
use App\Enums\SourceType;
use App\Jobs\SyncSourceFeed;
use App\Models\Publisher;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportSourcesCommand extends Command
{
    protected $signature = 'import:sources
                           {--force : Force reimport of existing sources}
                           {--path= : Custom path to scan for files (defaults to database/sources)}
                           {--extension=opml : File extension to scan for (defaults to opml)}';
    protected $description = 'Import and creates real sources for system by scanning folder for multiple files';

    /**
     * Execute the console command.
     * @throws \JsonException
     */
    public function handle(): void
    {
        $files = $this->scanForFiles();

        if (empty($files)) {
            $this->error('No files found to process');
            return;
        }

        $this->info("Found " . count($files) . " file(s) to process");

        $totalProcessed = 0;
        $totalSkipped = 0;
        $totalDuplicates = 0;

        foreach ($files as $file) {
            $this->info("\n" . str_repeat('=', 50));
            $this->info("Processing file: " . basename($file));
            $this->info(str_repeat('=', 50));

            try {
                $content = $this->readFile($file);
                $feeds = $this->parseFeeds($content);

                if (empty($feeds)) {
                    $this->warn("No feeds found in file: " . basename($file));
                    continue;
                }

                $this->info("Found " . count($feeds) . " feed(s) in " . basename($file));

                $processed = 0;
                $skipped = 0;
                $duplicates = 0;

                foreach ($feeds as $feed) {
                    if (!$this->isValidFeed($feed)) {
                        $this->warn("Skipped '{$feed['title']}' - missing required fields");
                        $skipped++;
                        continue;
                    }

                    $result = $this->processFeed($feed);

                    switch ($result) {
                        case 'processed':
                            $processed++;
                            break;
                        case 'duplicate':
                            $duplicates++;
                            break;
                        case 'skipped':
                            $skipped++;
                            break;
                    }
                }

                $this->info("File processing complete: {$processed} processed, {$duplicates} duplicates found, {$skipped} skipped");

                $totalProcessed += $processed;
                $totalSkipped += $skipped;
                $totalDuplicates += $duplicates;

            } catch (\Exception $e) {
                $this->error("Error processing file " . basename($file) . ": " . $e->getMessage());
                Log::error("Error processing file", [
                    'file' => $file,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("OVERALL SUMMARY");
        $this->info(str_repeat('=', 50));
        $this->info("Total files processed: " . count($files));
        $this->info("Total feeds processed: {$totalProcessed}");
        $this->info("Total duplicates found: {$totalDuplicates}");
        $this->info("Total skipped: {$totalSkipped}");
    }

    /**
     * Scan for files in the specified directory
     *
     * @return array<string>
     */
    private function scanForFiles(): array
    {
        $path = $this->option('path') ?: database_path('sources');
        $extension = $this->option('extension') ?: 'opml';

        if (!File::isDirectory($path)) {
            $this->error("Directory not found: {$path}");
            return [];
        }

        $this->info("Scanning directory: {$path}");
        $this->info("Looking for files with extension: {$extension}");

        // Get all files with the specified extension
        $files = File::glob($path . "/*.{$extension}");

        // Also check for files without extension if none found
        if (empty($files) && $extension !== '*') {
            $this->warn("No .{$extension} files found, checking for files without extension...");
            $allFiles = File::files($path);

            foreach ($allFiles as $file) {
                if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) === '') {
                    $files[] = $file->getPathname();
                }
            }
        }

        // Sort files for a consistent processing order
        sort($files);

        $this->info("Found files:");
        foreach ($files as $file) {
            $this->line("  - " . basename($file));
        }

        return $files;
    }

    private function processFeed(array $feed): string
    {
        $this->info("Processing: {$feed['title']}");

        // Check if source already exists by URL
        $existingSource = Source::where('url', $feed['xml_url'])->first();
        if ($existingSource && !$this->option('force')) {
            $this->line("  → Source already exists, skipping");
            return 'duplicate';
        }

        $slug = Str::slug($feed['title']);

        // Find or create publisher
        $publisher = Publisher::where('slug', $slug)->first();

        if (!$publisher) {
            // If slug conflicts, make it unique
            $originalSlug = $slug;
            $counter = 1;

            while (Publisher::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $publisher = Publisher::factory()->create([
                'name' => $feed['title'],
                'slug' => $slug
            ]);

            $this->line("  → Created publisher: {$publisher->name} ({$slug})");
        } else {
            $this->line("  → Using existing publisher: {$publisher->name}");
        }

        // Detect source type based on URL
        $sourceType = $this->detectSourceType($feed['xml_url']);
        $this->line("  → Detected source type: {$sourceType->value}");

        // Create or update source
        if ($existingSource && $this->option('force')) {
            $existingSource->update([
                'publisher_id' => $publisher->id,
                'url' => $feed['xml_url'],
                'type' => $sourceType,
            ]);
            $source = $existingSource;
            $this->line("  → Updated existing source");
        } else {
            // Check if source exists for this publisher
            $existingPublisherSource = Source::where('publisher_id', $publisher->id)
                ->where('url', $feed['xml_url'])
                ->first();

            if ($existingPublisherSource) {
                $this->line("  → Source already exists for this publisher");
                return 'duplicate';
            }

            $source = Source::factory()->create([
                'publisher_id' => $publisher->id,
                'url' => $feed['xml_url'],
                'type' => $sourceType,
            ]);

            $this->line("  → Created source");
        }

        // Dispatch sync job
        SyncSourceFeed::dispatch($source);
        $this->line("  → Queued sync job");

        return 'processed';
    }

    /**
     * Detect source type based on URL
     */
    private function detectSourceType(string $url): SourceType
    {
        $url = strtolower($url);

        // YouTube detection
        if (str_contains($url, 'youtube.com') ||
            str_contains($url, 'youtu.be') ||
            (str_contains($url, 'feeds.feedburner.com/') && str_contains($url, 'youtube'))) {
            return SourceType::Youtube;
        }

        // Podcast detection - common podcast hosting platforms and RSS patterns
        $podcastIndicators = [
            // Popular podcast hosting platforms
            'anchor.fm',
            'buzzsprout.com',
            'libsyn.com',
            'soundcloud.com',
            'spotify.com/show',
            'podcasts.apple.com',
            'castbox.fm',
            'stitcher.com',
            'podbean.com',
            'simplecast.com',
            'transistor.fm',
            'spreaker.com',
            'audioboom.com',
            'blubrry.com',
            'castos.com',
            'redcircle.com',
            'podcast.co',
            'pinecast.com',

            // Common podcast RSS patterns
            '/podcast',
            '/podcasts',
            '/feed/podcast',
            '/rss/podcast',

            // File extensions that suggest podcasts
            '.mp3',
            'podcast.rss',
            'podcast.xml',
        ];

        foreach ($podcastIndicators as $indicator) {
            if (str_contains($url, $indicator)) {
                return SourceType::Podcast;
            }
        }

        // Default to Article if no specific type detected
        return SourceType::Article;
    }

    /**
     * @throws FileNotFoundException
     */
    private function readFile(string $filePath): string
    {
        if (!File::exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        return File::get($filePath);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseFeeds(string $content): array
    {
        $parser = new Parser();
        $parser->ParseOPML($content);

        return $parser->getOpmlContents();
    }

    private function isValidFeed(array $feed): bool
    {
        $isValid = isset($feed['title'], $feed['xml_url']) &&
            !empty(trim($feed['title'])) &&
            !empty(trim($feed['xml_url'])) &&
            filter_var($feed['xml_url'], FILTER_VALIDATE_URL);

        if (!$isValid) {
            Log::info("Feed validation failed", [
                'title' => $feed['title'] ?? 'missing',
                'xml_url' => $feed['xml_url'] ?? 'missing',
                'has_title' => isset($feed['title']) && !empty(trim($feed['title'])),
                'has_xml_url' => isset($feed['xml_url']) && !empty(trim($feed['xml_url'])),
                'valid_url' => isset($feed['xml_url']) ? filter_var($feed['xml_url'], FILTER_VALIDATE_URL) : false
            ]);
        }

        return $isValid;
    }
}
