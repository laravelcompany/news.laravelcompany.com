<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncSourceFeed;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ImportArticlesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:articles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull content from sources';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $delay = Carbon::now(); // 当前时间点
        $i = 0;

        foreach (Source::tracked()->get() as $source) {
            SyncSourceFeed::dispatch($source)->delay($delay->copy()->addMinutes($i));
            $this->info("🚀 Dispatched job for source #{$source->id} with delay: {$delay->copy()->addMinutes($i)}");
            $i++;
        }

        $this->info('✅ All jobs scheduled with 1 min intervals, брат.');
    }
}
