(function () {
  if (typeof window === 'undefined') return;

  const data = window.__dashboardData || {};
  const brandHex = data.brandHex || '#0137A1';
  window.jssCharts = window.jssCharts || {};

  function destroyChart(key) {
    if (window.jssCharts[key]) {
      try { window.jssCharts[key].destroy(); } catch (e) {}
      window.jssCharts[key] = null;
    }
  }

  function createLineChart(ctx, labels, values, options = {}) {
    if (!ctx) return null;
    return new Chart(ctx, {
      type: 'line',
      data: { labels: labels, datasets: [{ data: values, borderColor: brandHex, borderWidth: 2, tension: 0.4, fill: false, pointRadius: 0 }] },
      options: Object.assign({
        responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false } }
      }, options),
    });
  }

  function createTrendChart(ctx, labels, values) {
    if (!ctx) return null;
    return new Chart(ctx, {
      type: 'line',
      data: { labels: labels, datasets: [{
        label: 'Shipment', data: values, borderColor: brandHex, backgroundColor: brandHex + '20', borderWidth: 3, tension: 0.4, fill: true, pointRadius: 4, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderColor: brandHex, pointBorderWidth: 2,
      }]},
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', padding: 12, cornerRadius: 8, callbacks: { label: function(ctx) { return ctx.parsed.y + ' shipment'; } } }
        },
        scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 10, autoSkip: true } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { precision: 0 } } }
      }
    });
  }

  function createDonut(ctx, labels, values, colors = []) {
    if (!ctx) return null;
    return new Chart(ctx, {
      type: 'doughnut',
      data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }] },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: '70%',
        plugins: {
          legend: { display: true, position: 'bottom', labels: { boxWidth: 12, padding: 12, font: { size: 12 } } },
          tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', padding: 12, cornerRadius: 8, callbacks: { label: function(ctx) { const total = ctx.dataset.data.reduce((a,b)=>a+b,0); const val = ctx.parsed; const pct = total>0?((val/total)*100).toFixed(1):0; return ctx.label + ': ' + val + ' (' + pct + '%)'; } } }
        }
      }
    });
  }

  function initChartsFromDOM() {
    const sparkCanvas = document.getElementById('spark-activity');
    const spark = (window.__dashboardData && window.__dashboardData.spark) || [];
    const sparkValues = spark.map(r => r.value || 0);
    const sparkLabels = spark.map(r => r.label || '');
    destroyChart('spark-activity');
    if (sparkCanvas && sparkValues.length) {
      window.jssCharts['spark-activity'] = createLineChart(sparkCanvas, sparkLabels, sparkValues);
    }

    const trendCanvas = document.getElementById('trendChart');
    const trendLabels = (window.__dashboardData && window.__dashboardData.trend && window.__dashboardData.trend.labels) || [];
    const trendValues = (window.__dashboardData && window.__dashboardData.trend && window.__dashboardData.trend.series) || [];
    destroyChart('trendChart');
    if (trendCanvas && trendLabels.length) {
      window.jssCharts['trendChart'] = createTrendChart(trendCanvas, trendLabels, trendValues);
    }

    const distCanvas = document.getElementById('statusDistChart');
    const distLabels = window.__statusDistLabels || [];
    const distValues = window.__statusDistValues || [];
    destroyChart('statusDistChart');
    if (distCanvas && distLabels.length) {
      window.jssCharts['statusDistChart'] = createDonut(distCanvas, distLabels, distValues, ['#94A3B8', '#FCD34D', '#60A5FA', '#818CF8', '#34D399', '#FB923C', '#F87171']);
    }

    const tamCanvas = document.getElementById('tamTotalDonut');
    destroyChart('tamTotalDonut');
    if (tamCanvas) {
      const ok = (window.__dashboardData && window.__dashboardData.tamEval && window.__dashboardData.tamEval.total && window.__dashboardData.tamEval.total.ok) || 0;
      const ng = (window.__dashboardData && window.__dashboardData.tamEval && window.__dashboardData.tamEval.total && window.__dashboardData.tamEval.total.ng) || 0;
      if (ok || ng) {
        window.jssCharts['tamTotalDonut'] = createDonut(tamCanvas, ['OK','NG'], [ok, ng], ['#22C55E','#EF4444']);
      }
    }
  }

  document.addEventListener('DOMContentLoaded', initChartsFromDOM);

  if (window.Livewire && window.Livewire.hook) {
    window.Livewire.hook('message.processed', () => {
      setTimeout(initChartsFromDOM, 50);
    });
  }
})();
