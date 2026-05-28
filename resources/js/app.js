const payloadCache = new WeakMap();
const chartViewports = new Map();
let lastPointer = null;
let activePan = null;
const defaultViewport = {
    x: 0,
    y: 0,
    width: 720,
    height: 260,
};

const parsePayload = (chart) => {
    const source = chart.querySelector('[data-chart-payload]');

    if (!source) {
        return { series: [] };
    }

    const raw = source.textContent?.trim() ?? '';
    const cached = payloadCache.get(source);

    if (cached?.raw === raw) {
        return cached.payload;
    }

    try {
        const payload = raw ? JSON.parse(raw) : { series: [] };
        payloadCache.set(source, { raw, payload });

        return payload;
    } catch {
        return { series: [] };
    }
};

const chartPoints = (payload) => (payload.series ?? [])
    .flatMap((series) => (series.points ?? []).map((point) => ({
        ...point,
        series: series.label,
        color: series.color,
    })))
    .filter((point) => Number.isFinite(Number(point.x)) && Number.isFinite(Number(point.y)));

const setHidden = (element, hidden) => {
    if (!element) {
        return;
    }

    element.classList.toggle('hidden', hidden);
};

const chartForTarget = (target) => {
    if (!(target instanceof Element)) {
        return null;
    }

    return target.closest('[data-interactive-chart]');
};

const chartKey = (chart) => chart.dataset.chartKey ?? 'market';

const chartViewport = (chart) => chartViewports.get(chartKey(chart)) ?? { ...defaultViewport };

const clampViewport = (viewport) => {
    const width = Math.min(Math.max(viewport.width, 90), defaultViewport.width);
    const height = Math.min(Math.max(viewport.height, 40), defaultViewport.height);

    return {
        x: Math.min(Math.max(viewport.x, defaultViewport.x), defaultViewport.width - width),
        y: Math.min(Math.max(viewport.y, defaultViewport.y), defaultViewport.height - height),
        width,
        height,
    };
};

const applyChartViewport = (chart) => {
    const svg = chart.querySelector('svg');

    if (!svg) {
        return;
    }

    const viewport = clampViewport(chartViewport(chart));
    chartViewports.set(chartKey(chart), viewport);
    svg.setAttribute('viewBox', `${viewport.x} ${viewport.y} ${viewport.width} ${viewport.height}`);

    const label = chart.querySelector('[data-chart-zoom-label]');

    if (label) {
        label.textContent = `${Math.round(defaultViewport.width / viewport.width * 100)}%`;
    }
};

const pointerInChart = (chart, event) => {
    const svg = chart.querySelector('svg');

    if (!svg) {
        return null;
    }

    const bounds = svg.getBoundingClientRect();
    const viewport = chartViewport(chart);

    return {
        x: viewport.x + ((event.clientX - bounds.left) / bounds.width) * viewport.width,
        y: viewport.y + ((event.clientY - bounds.top) / bounds.height) * viewport.height,
    };
};

const zoomChart = (chart, factor, anchor = null) => {
    const viewport = chartViewport(chart);
    const focus = anchor ?? {
        x: viewport.x + viewport.width / 2,
        y: viewport.y + viewport.height / 2,
    };
    const width = viewport.width * factor;
    const height = viewport.height * factor;
    const next = clampViewport({
        x: focus.x - ((focus.x - viewport.x) * (width / viewport.width)),
        y: focus.y - ((focus.y - viewport.y) * (height / viewport.height)),
        width,
        height,
    });

    chartViewports.set(chartKey(chart), next);
    applyChartViewport(chart);
};

const resetChartZoom = (chart) => {
    chartViewports.set(chartKey(chart), { ...defaultViewport });
    applyChartViewport(chart);
};

const tooltipMarkup = (points) => {
    const header = points
        .map((point) => `
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full" style="background:${point.color ?? '#f8fafc'}"></span>
                <span class="font-semibold text-white">${escapeHtml(point.series ?? point.title ?? 'Point')}</span>
                <span class="text-zinc-400">${escapeHtml(point.time ?? '')}</span>
            </div>
        `)
        .join('');
    const rows = points
        .flatMap((point) => point.rows ?? [])
        .map((row) => `
            <div class="grid grid-cols-[6.5rem_minmax(0,1fr)] gap-2 border-t border-white/10 py-1.5">
                <span class="text-zinc-500">${escapeHtml(row.label ?? '')}</span>
                <span class="text-right font-medium text-zinc-100">${escapeHtml(row.value ?? '')}</span>
            </div>
        `)
        .join('');

    return `<div class="space-y-2">${header}<div>${rows}</div></div>`;
};

const escapeHtml = (value) => String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const moveTooltip = (chart, tooltip, event) => {
    const bounds = chart.getBoundingClientRect();
    let left = event.clientX - bounds.left + 14;
    let top = event.clientY - bounds.top + 14;

    tooltip.style.left = `${left}px`;
    tooltip.style.top = `${top}px`;

    const tooltipBounds = tooltip.getBoundingClientRect();

    if (tooltipBounds.right > bounds.right - 8) {
        left = event.clientX - bounds.left - tooltipBounds.width - 14;
    }

    if (tooltipBounds.bottom > bounds.bottom - 8) {
        top = event.clientY - bounds.top - tooltipBounds.height - 14;
    }

    tooltip.style.left = `${Math.max(left, 8)}px`;
    tooltip.style.top = `${Math.max(top, 8)}px`;
};

const showChartPoint = (event) => {
    lastPointer = {
        clientX: event.clientX,
        clientY: event.clientY,
    };

    const chart = chartForTarget(event.target);

    if (!chart) {
        return;
    }

    const svg = chart.querySelector('svg');
    const tooltip = chart.querySelector('[data-chart-tooltip]');
    const guide = chart.querySelector('[data-chart-guide]');
    const marker = chart.querySelector('[data-chart-marker]');
    const payload = parsePayload(chart);
    const points = chartPoints(payload);

    if (!svg || !tooltip || points.length === 0) {
        return;
    }

    const bounds = svg.getBoundingClientRect();
    const viewBox = svg.viewBox.baseVal;
    const pointerX = viewBox.x + ((event.clientX - bounds.left) / bounds.width) * viewBox.width;
    const nearest = points.reduce((best, point) => (
        Math.abs(point.x - pointerX) < Math.abs(best.x - pointerX) ? point : best
    ), points[0]);
    const grouped = points.filter((point) => Math.abs(point.x - nearest.x) < 0.8);

    tooltip.innerHTML = tooltipMarkup(grouped.length > 0 ? grouped : [nearest]);
    setHidden(tooltip, false);
    moveTooltip(chart, tooltip, event);

    if (guide) {
        guide.setAttribute('x1', nearest.x);
        guide.setAttribute('x2', nearest.x);
        setHidden(guide, false);
    }

    if (marker) {
        marker.setAttribute('cx', nearest.x);
        marker.setAttribute('cy', nearest.y);
        marker.setAttribute('fill', nearest.color ?? '#f8fafc');
        setHidden(marker, false);
    }
};

const handleChartZoomClick = (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const button = event.target.closest('[data-chart-zoom]');
    const chart = button ? chartForTarget(button) : null;

    if (!button || !chart) {
        return;
    }

    const action = button.getAttribute('data-chart-zoom');

    if (action === 'in') {
        zoomChart(chart, 0.75);
    } else if (action === 'out') {
        zoomChart(chart, 1.25);
    } else {
        resetChartZoom(chart);
    }
};

const handleChartWheel = (event) => {
    const chart = chartForTarget(event.target);

    if (!chart) {
        return;
    }

    event.preventDefault();
    zoomChart(chart, event.deltaY < 0 ? 0.85 : 1.15, pointerInChart(chart, event));
};

const startChartPan = (event) => {
    if (event.button !== 0 || !(event.target instanceof Element)) {
        return;
    }

    if (event.target.closest('[data-chart-control]')) {
        return;
    }

    const chart = chartForTarget(event.target);

    if (!chart) {
        return;
    }

    activePan = {
        chart,
        startX: event.clientX,
        startY: event.clientY,
        viewport: chartViewport(chart),
    };
};

const moveChartPan = (event) => {
    if (!activePan) {
        return;
    }

    const svg = activePan.chart.querySelector('svg');

    if (!svg) {
        activePan = null;

        return;
    }

    const bounds = svg.getBoundingClientRect();
    const deltaX = ((event.clientX - activePan.startX) / bounds.width) * activePan.viewport.width;
    const deltaY = ((event.clientY - activePan.startY) / bounds.height) * activePan.viewport.height;
    const next = clampViewport({
        ...activePan.viewport,
        x: activePan.viewport.x - deltaX,
        y: activePan.viewport.y - deltaY,
    });

    chartViewports.set(chartKey(activePan.chart), next);
    applyChartViewport(activePan.chart);
};

const stopChartPan = () => {
    activePan = null;
};

const hideChartPoint = (event) => {
    const chart = chartForTarget(event.target);

    if (!chart) {
        return;
    }

    if (event.relatedTarget && chart.contains(event.relatedTarget)) {
        return;
    }

    lastPointer = null;
    setHidden(chart.querySelector('[data-chart-tooltip]'), true);
    setHidden(chart.querySelector('[data-chart-guide]'), true);
    setHidden(chart.querySelector('[data-chart-marker]'), true);
};

const applyAllChartViewports = () => {
    document.querySelectorAll('[data-interactive-chart]').forEach(applyChartViewport);
};

const refreshHoveredChart = () => {
    if (!lastPointer) {
        return;
    }

    const target = document.elementFromPoint(lastPointer.clientX, lastPointer.clientY);

    if (!chartForTarget(target)) {
        return;
    }

    showChartPoint({
        target,
        clientX: lastPointer.clientX,
        clientY: lastPointer.clientY,
    });
};

document.addEventListener('pointermove', showChartPoint);
document.addEventListener('pointermove', moveChartPan);
document.addEventListener('pointerdown', startChartPan);
document.addEventListener('pointerup', stopChartPan);
document.addEventListener('pointercancel', stopChartPan);
document.addEventListener('pointerleave', hideChartPoint, true);
document.addEventListener('click', handleChartZoomClick);
document.addEventListener('wheel', handleChartWheel, { passive: false });

document.addEventListener('livewire:init', () => {
    window.Livewire?.hook('morphed', () => {
        requestAnimationFrame(() => {
            applyAllChartViewports();
            refreshHoveredChart();
        });
    });
});

applyAllChartViewports();
