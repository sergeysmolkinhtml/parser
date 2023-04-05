<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Parsers;

class ParseWebsiteCommand extends Command
{
    protected $signature = 'parse:website {url} {--output=}';

    protected $description = 'Parse website and save result to a file';

    public function handle()
    {
        $url = $this->argument('url');
        $output = $this->option('output');

        try {
            $parser = new YourParser($url);
            $result = $parser->parse();
        } catch (\Exception $e) {
            $this->error('Error occurred while parsing website: ' . $e->getMessage());
            return;
        }

        $outputFile = $output ?? 'result.json';
        file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT));

        $this->info('Parsing completed successfully. Result saved to ' . $outputFile);
    }
}