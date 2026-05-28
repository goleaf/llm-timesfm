<?php

namespace App\Actions\Crypto;

use App\Http\Requests\Crypto\SyncCryptoTickersRequest;
use App\Http\Requests\Crypto\WatchCryptoTickersRequest;
use Throwable;

class WatchCryptoTickersAction
{
    public function __construct(
        private readonly SyncConfiguredCryptoTickersAction $syncTickers,
    ) {}

    /**
     * @param  callable(string): void  $line
     * @param  callable(string): void  $warning
     */
    public function handle(WatchCryptoTickersRequest $request, callable $line, callable $warning): void
    {
        $startedAt = time();

        do {
            try {
                $summary = $this->syncTickers->handle(SyncCryptoTickersRequest::fromConsole($request->limit));

                $line(now()->format('H:i:s')." stored {$summary['snapshots']} snapshots; warmed {$summary['warmed_reads']} reads");
            } catch (Throwable $exception) {
                $warning(now()->format('H:i:s').' '.$exception->getMessage());
            }

            if ($request->seconds > 0 && (time() - $startedAt) >= $request->seconds) {
                break;
            }

            sleep($request->pollSeconds());
        } while (true);
    }
}
