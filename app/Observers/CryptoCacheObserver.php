<?php

namespace App\Observers;

use App\Services\Crypto\CryptoCache;
use Illuminate\Database\Eloquent\Model;

class CryptoCacheObserver
{
    public function saved(Model $model): void
    {
        $this->flush();
    }

    public function deleted(Model $model): void
    {
        $this->flush();
    }

    public function restored(Model $model): void
    {
        $this->flush();
    }

    public function forceDeleted(Model $model): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        app(CryptoCache::class)->flush();
    }
}
