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
];
