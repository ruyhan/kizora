<?php

namespace App\Console\Commands;

use App\Support\PageContent;
use Illuminate\Console\Command;

class ContentInit extends Command
{
    protected $signature = 'content:init {--force : Overwrite existing files with defaults}';

    protected $description = 'Write default page-content JSON files into the data/ folder';

    public function handle(PageContent $content): int
    {
        foreach (array_keys($content->schema()) as $group) {
            $path = $content->path($group);
            if (is_file($path) && ! $this->option('force')) {
                $this->line("skip   {$group}.json (exists)");
                continue;
            }
            $content->save($group, $content->defaults($group));
            $this->info("write  {$group}.json");
        }

        $this->newLine();
        $this->info('Content initialised in: ' . base_path('data'));

        return self::SUCCESS;
    }
}
