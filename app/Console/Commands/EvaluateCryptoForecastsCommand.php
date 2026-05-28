<?php

namespace App\Console\Commands;

use App\Actions\Crypto\EvaluateForecastAccuracyAction;
use Illuminate\Console\Command;

class EvaluateCryptoForecastsCommand extends Command
{
    protected $signature = 'crypto:evaluate-forecasts {--limit=1000 : Maximum pending forecast points to evaluate}';

    protected $description = 'Compare stored forecast points with actual candle closes and update accuracy metrics.';

    public function handle(EvaluateForecastAccuracyAction $accuracy): int
    {
        $summary = $accuracy->handle((int) $this->option('limit'));

        $this->info("Evaluated {$summary['points']} forecast points across {$summary['forecasts']} forecasts.");

        return self::SUCCESS;
    }
}
