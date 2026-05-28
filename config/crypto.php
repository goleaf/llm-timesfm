<?php

return [
    'binance' => [
        'base_url' => env('BINANCE_API_BASE_URL', 'https://api.binance.com'),
        'symbols' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('CRYPTO_MARKET_SYMBOLS', 'BTCUSDT,ETHUSDT,BNBUSDT,SOLUSDT,XRPUSDT,DOGEUSDT,ADAUSDT,AVAXUSDT,LINKUSDT,TRXUSDT,TONUSDT,DOTUSDT,MATICUSDT,LTCUSDT,BCHUSDT,UNIUSDT,ATOMUSDT,ETCUSDT,FILUSDT,APTUSDT')),
        ))),
        'quote_assets' => ['USDT', 'USDC', 'FDUSD', 'BTC', 'ETH', 'BNB'],
        'market_limit' => (int) env('CRYPTO_MARKET_LIMIT', 20),
        'poll_seconds' => (int) env('CRYPTO_MARKET_POLL_SECONDS', 1),
        'history_limit' => (int) env('CRYPTO_MARKET_HISTORY_LIMIT', 240),
        'short_intervals' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('CRYPTO_SHORT_INTERVALS', '1m,5m')),
        ))),
        'max_kline_limit' => (int) env('CRYPTO_MAX_KLINE_LIMIT', 1000),
    ],

    'forecasting' => [
        'analyzers' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('CRYPTO_FORECAST_ANALYZERS', 'trend,moving-average,ema,momentum')),
        ))),
        'periods' => [
            '15m' => ['interval' => '1m', 'horizon' => 15, 'context' => 120],
            '1h' => ['interval' => '1m', 'horizon' => 60, 'context' => 240],
            '4h' => ['interval' => '1m', 'horizon' => 240, 'context' => 512],
            '24h' => ['interval' => '1h', 'horizon' => 24, 'context' => 240],
        ],
        'timesfm' => [
            'enabled' => (bool) env('TIMESFM_ENABLED', false),
            'python' => env('TIMESFM_PYTHON', base_path('.venv/bin/python')),
            'script' => env('TIMESFM_SCRIPT', base_path('python/timesfm_forecast.py')),
            'model_id' => env('TIMESFM_MODEL_ID', 'google/timesfm-2.5-200m-pytorch'),
            'timeout' => (int) env('TIMESFM_TIMEOUT', 180),
            'max_context' => (int) env('TIMESFM_MAX_CONTEXT', 512),
            'max_horizon' => (int) env('TIMESFM_MAX_HORIZON', 256),
        ],
    ],

    'cache' => [
        'enabled' => (bool) env('CRYPTO_CACHE_ENABLED', true),
        'store' => env('CRYPTO_CACHE_STORE', env('CACHE_STORE', 'file')),
        'ttl' => [
            'assets' => (int) env('CRYPTO_CACHE_ASSETS_SECONDS', 1),
            'selected_asset' => (int) env('CRYPTO_CACHE_SELECTED_ASSET_SECONDS', 1),
            'market_history' => (int) env('CRYPTO_CACHE_MARKET_HISTORY_SECONDS', 2),
            'snapshots' => (int) env('CRYPTO_CACHE_SNAPSHOTS_SECONDS', 1),
            'latest_forecast' => (int) env('CRYPTO_CACHE_LATEST_FORECAST_SECONDS', 3),
            'forecast_stats' => (int) env('CRYPTO_CACHE_FORECAST_STATS_SECONDS', 2),
            'prediction_stakes' => (int) env('CRYPTO_CACHE_PREDICTION_STAKES_SECONDS', 1),
        ],
        'warm_after_ticker_sync' => (bool) env('CRYPTO_CACHE_WARM_AFTER_TICKER_SYNC', true),
        'warm_limit' => (int) env('CRYPTO_CACHE_WARM_LIMIT', 3),
        'warm_symbols' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('CRYPTO_CACHE_WARM_SYMBOLS', 'BTCUSDT,ETHUSDT,SOLUSDT')),
        ))),
        'warm_intervals' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('CRYPTO_CACHE_WARM_INTERVALS', '1m,5m')),
        ))),
    ],
];
