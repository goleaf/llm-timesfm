#!/usr/bin/env python3
import json
import sys
from typing import Any


def baseline(values: list[float], horizon: int) -> dict[str, Any]:
    last_value = float(values[-1]) if values else 0.0
    point_forecast = [last_value for _ in range(horizon)]

    return {
        "engine": "baseline-last-value",
        "point_forecast": point_forecast,
        "quantile_forecast": [[value * 0.99, value, value * 1.01] for value in point_forecast],
    }


def main() -> int:
    payload = json.load(sys.stdin)
    values = [float(value) for value in payload["values"]]
    horizon = int(payload["horizon"])

    try:
        import numpy as np
        import timesfm
        import torch

        torch.set_float32_matmul_precision("high")

        max_context = int(payload.get("max_context", 512))
        max_horizon = max(horizon, int(payload.get("max_horizon", 256)))
        model_id = payload.get("model_id", "google/timesfm-2.5-200m-pytorch")

        model = timesfm.TimesFM_2p5_200M_torch.from_pretrained(model_id)
        model.compile(
            timesfm.ForecastConfig(
                max_context=max_context,
                max_horizon=max_horizon,
                normalize_inputs=True,
                per_core_batch_size=4,
                use_continuous_quantile_head=True,
                force_flip_invariance=True,
                infer_is_positive=True,
                fix_quantile_crossing=True,
            )
        )

        point_forecast, quantile_forecast = model.forecast(
            horizon=horizon,
            inputs=[np.array(values, dtype=np.float32)],
        )

        print(
            json.dumps(
                {
                    "engine": "timesfm-2.5-200m-pytorch",
                    "point_forecast": point_forecast[0].astype(float).tolist(),
                    "quantile_forecast": quantile_forecast[0].astype(float).tolist(),
                }
            )
        )

        return 0
    except Exception as exc:
        fallback = baseline(values, horizon)
        fallback["warning"] = str(exc)
        print(json.dumps(fallback))

        return 0


if __name__ == "__main__":
    raise SystemExit(main())

