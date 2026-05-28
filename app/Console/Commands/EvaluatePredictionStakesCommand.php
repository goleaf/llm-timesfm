<?php

namespace App\Console\Commands;

use App\Actions\Crypto\EvaluatePredictionStakesAction;
use App\Http\Requests\Crypto\EvaluatePredictionStakesRequest;
use Illuminate\Console\Command;

class EvaluatePredictionStakesCommand extends Command
{
    protected $signature = 'crypto:evaluate-prediction-stakes {--limit=1000 : Maximum pending prediction stakes to evaluate}';

    protected $description = 'Compare due manual prediction stakes with actual candle closes.';

    public function handle(EvaluatePredictionStakesAction $stakes): int
    {
        $request = EvaluatePredictionStakesRequest::fromConsole($this->option('limit'));
        $summary = $stakes->handle($request->limit);

        $this->info("Evaluated {$summary['stakes']} prediction stakes.");

        return self::SUCCESS;
    }
}
