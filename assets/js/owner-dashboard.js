(() => {
  const dataElement = document.getElementById('dashboardChartData');
  if (!dataElement) return;

  let data;
  try {
    data = JSON.parse(dataElement.textContent || '{}');
  } catch (error) {
    document.querySelectorAll('.chart-state').forEach(state => {
      state.textContent = 'Chart data could not be read.';
      state.classList.add('is-visible', 'is-error');
    });
    return;
  }

  const currency = value => new Intl.NumberFormat('en-PH', {
    style: 'currency', currency: 'PHP', maximumFractionDigits: 2
  }).format(Number(value) || 0);
  const palette = ['#174ea6', '#3578d4', '#5f9bea', '#88b5f1', '#f0a33a', '#2b8a6e', '#7957b5', '#d86c55', '#527a9b', '#a3aab5'];
  const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 350 },
    plugins: {
      legend: { labels: { usePointStyle: true, boxWidth: 8, padding: 16, color: '#475467', font: { family: 'Inter, Arial, sans-serif' } } },
      tooltip: { backgroundColor: '#172033', padding: 11, titleFont: { weight: '600' }, bodyFont: { family: 'Inter, Arial, sans-serif' } }
    }
  };

  function showState(canvas, message, error = false) {
    const state = canvas.parentElement.querySelector('.chart-state');
    canvas.hidden = true;
    state.classList.remove('is-loading');
    state.textContent = message;
    state.classList.add('is-visible');
    if (error) state.classList.add('is-error');
  }

  function mount(id, hasData, message, type, chartData, options = {}) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    if (!window.Chart) {
      showState(canvas, 'Charts could not load. Check your internet connection and refresh.', true);
      return;
    }
    if (!hasData) {
      showState(canvas, message);
      return;
    }
    const state = canvas.parentElement.querySelector('.chart-state');
    state.classList.remove('is-visible', 'is-loading');
    try {
      new Chart(canvas, { type, data: chartData, options: { ...defaultOptions, ...options } });
    } catch (error) {
      showState(canvas, 'This chart could not be displayed.', true);
    }
  }

  const salesHasData = data.salesTrend?.some(value => Number(value) > 0);
  mount('salesTrendChart', salesHasData, 'No completed sales in this date range.', 'line', {
    labels: data.labels,
    datasets: [{ label: 'Sales revenue', data: data.salesTrend, borderColor: '#174ea6', backgroundColor: 'rgba(37, 99, 235, .12)', pointBackgroundColor: '#174ea6', pointRadius: 3, pointHoverRadius: 6, borderWidth: 2.5, fill: true, tension: .3 }]
  }, {
    plugins: { ...defaultOptions.plugins, tooltip: { ...defaultOptions.plugins.tooltip, callbacks: { label: context => ` ${currency(context.parsed.y)}` } } },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#667085', maxTicksLimit: 12 } },
      y: { beginAtZero: true, grid: { color: '#edf1f7' }, ticks: { color: '#667085', callback: value => currency(value) } }
    }
  });

  const financeValues = data.financials?.values || [];
  mount('financialChart', Number(financeValues[0]) > 0, 'No sales with saved product cost data in this date range.', 'bar', {
    labels: data.financials.labels,
    datasets: [{ label: 'PHP', data: financeValues, backgroundColor: ['#174ea6', '#e09a32', '#238267'], borderRadius: 6, maxBarThickness: 62 }]
  }, {
    plugins: { ...defaultOptions.plugins, legend: { display: false }, tooltip: { ...defaultOptions.plugins.tooltip, callbacks: { label: context => ` ${currency(context.parsed.y)}` } } },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#475467' } },
      y: { beginAtZero: true, grid: { color: '#edf1f7' }, ticks: { color: '#667085', callback: value => currency(value) } }
    }
  });

  const best = data.bestProducts || {};
  mount('bestProductsChart', (best.quantities || []).some(value => Number(value) > 0), 'No product sales in this date range.', 'bar', {
    labels: best.labels,
    datasets: [{ label: 'Quantity sold', data: best.quantities, backgroundColor: '#3578d4', borderRadius: 5, maxBarThickness: 30 }]
  }, {
    indexAxis: 'y',
    plugins: { ...defaultOptions.plugins, legend: { display: false }, tooltip: { ...defaultOptions.plugins.tooltip, callbacks: { label: context => [` ${context.parsed.x} sold`, ` Revenue: ${currency(best.revenue[context.dataIndex])}`] } } },
    scales: {
      x: { beginAtZero: true, grid: { color: '#edf1f7' }, ticks: { precision: 0, color: '#667085' } },
      y: { grid: { display: false }, ticks: { color: '#475467' } }
    }
  });

  const inventoryValues = data.inventory?.values || [];
  mount('inventoryChart', inventoryValues.some(value => Number(value) > 0), 'No active inventory to display.', 'doughnut', {
    labels: data.inventory.labels,
    datasets: [{ data: inventoryValues, backgroundColor: ['#238267', '#e09a32', '#d64545'], borderColor: '#fff', borderWidth: 3, hoverOffset: 7 }]
  }, { cutout: '63%', plugins: { ...defaultOptions.plugins, tooltip: { ...defaultOptions.plugins.tooltip, callbacks: { label: context => ` ${context.label}: ${context.parsed} products` } } } });

  const categoryValues = data.categories?.values || [];
  mount('categoryChart', categoryValues.some(value => Number(value) > 0), 'No category sales in this date range.', 'doughnut', {
    labels: data.categories.labels,
    datasets: [{ data: categoryValues, backgroundColor: palette, borderColor: '#fff', borderWidth: 3, hoverOffset: 7 }]
  }, { cutout: '58%', plugins: { ...defaultOptions.plugins, tooltip: { ...defaultOptions.plugins.tooltip, callbacks: { label: context => ` ${currency(context.parsed)}` } } } });

  const staff = data.staff || {};
  mount('staffChart', (staff.values || []).some(value => Number(value) > 0), 'No staff sales in this date range.', 'bar', {
    labels: staff.labels,
    datasets: [{ label: 'Sales', data: staff.values, backgroundColor: '#3578d4', borderRadius: 5, maxBarThickness: 38 }]
  }, {
    plugins: { ...defaultOptions.plugins, legend: { display: false }, tooltip: { ...defaultOptions.plugins.tooltip, callbacks: { label: context => ` Sales: ${currency(context.parsed.y)}`, afterLabel: context => ` Transactions: ${staff.transactions[context.dataIndex]}` } } },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#475467', maxRotation: 35, minRotation: 0 } },
      y: { beginAtZero: true, grid: { color: '#edf1f7' }, ticks: { color: '#667085', callback: value => currency(value) } }
    }
  });
})();
