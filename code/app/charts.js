
const ChartsPage = (() => {
  let deps = {};
  let initialized = false;

  let chartComponentInstance = null;
  let chartSocket = null;
  let chartSocketReconnectTimer = null;
  let chartSocketGeneration = 0;

  let state = null;
  const elements = {};

  function show(el) {
    el?.classList.remove('hidden');
  }

  function hide(el) {
    el?.classList.add('hidden');
  }

  function getSelectedPair() {
    return localStorage.getItem(state.selectedPairKey) || '';
  }

  function setSelectedPair(id) {
    localStorage.setItem(state.selectedPairKey, id);
    updateSelectedPairLabel();
  }

  function updateSelectedPairLabel() {
    const id = getSelectedPair();

    if (elements.selectedPairLabel) {
      elements.selectedPairLabel.textContent = id
        ? `${id}`
        : 'Пара не выбрана';
    }
  }

  function setChartsSocketState(message, kind = 'muted') {
    if (!elements.chartsSocketState) return;

    elements.chartsSocketState.textContent = `WS: ${message}`;
    elements.chartsSocketState.className = `${kind} small`;
  }

  // Все времена приводим к миллисекундам Unix.
  // Строки snapshotTimeUTC без суффикса Z считаем UTC.
  function normalizeTime(value) {
    if (value === null || value === undefined || value === '') {
      return null;
    }

    if (typeof value === 'number' && Number.isFinite(value)) {
      return value < 1e12 ? value * 1000 : value;
    }

    const text = String(value).trim();

    if (/^-?\d+(\.\d+)?$/.test(text)) {
      const numeric = Number(text);

      if (Number.isFinite(numeric)) {
        return numeric < 1e12 ? numeric * 1000 : numeric;
      }
    }

    const normalized = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?$/.test(text)
      ? `${text}Z`
      : text;

    const parsed = Date.parse(normalized);

    return Number.isNaN(parsed) ? null : parsed;
  }

  function getMinute(time) {
    return Math.floor(time / 60000) * 60000;
  }

  let lastQuotePrices = {
    bid: null,
    ask: null,
  };

  function updateCreatePositionButtonLabel() {
    if (!elements.createPositionBtn) {
      return;
    }

    const size = Number(elements.positionSize?.value);
    const direction = elements.positionDirection?.value || 'BUY';
    const entryPrice = direction === 'BUY'
      ? lastQuotePrices.ask
      : lastQuotePrices.bid;

    const hasValue = Number.isFinite(size) && size > 0 && Number.isFinite(entryPrice);
    const baseLabel = 'Открыть позицию';

    if (!hasValue) {
      elements.createPositionBtn.textContent = baseLabel;
      return;
    }

    const notionalUsd = size * entryPrice;
    const marginUsd = notionalUsd / 20;

    const formatUsd = (value) => value.toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });

    elements.createPositionBtn.textContent = `${baseLabel} ($${formatUsd(marginUsd)}, $${formatUsd(notionalUsd)})`;
  }

  function getQuotePrice(quote, side = 'bid') {
    const value = side === 'ask'
      ? (quote.ask ?? quote.ofr)
      : quote.bid;

    const price = Number(value);

    return value !== null &&
      value !== undefined &&
      value !== '' &&
      Number.isFinite(price)
      ? price
      : null;
  }

  function formatPrice(value) {
    if (value === null || value === undefined || !Number.isFinite(Number(value))) {
      return '—';
    }

    return Number(value).toLocaleString('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 8,
    });
  }

  // Обновляем последнюю полученную котировку отдельно от графика.
  function updateQuotePrices(quote) {
    const bid = getQuotePrice(quote, 'bid');
    const ask = getQuotePrice(quote, 'ask');

    lastQuotePrices = {
      bid,
      ask,
    };

    if (elements.currentBid) {
      elements.currentBid.textContent = formatPrice(bid);
    }

    if (elements.currentAsk) {
      elements.currentAsk.textContent = formatPrice(ask);
    }

    updateCreatePositionButtonLabel();
  }

  function renderChart() {
    if (chartComponentInstance) {
      chartComponentInstance.setData(state.chartData);
    }
  }

  function clearChartData() {
    state.chartData = [];
    state.chartDataTimes = new Set();
    renderChart();
  }

  // Формируем минутную OHLC-свечу из одной котировки.
  function appendChartPointFromQuote(quote, fallbackTime) {
    const epic = getSelectedPair();

    if (!epic || !quote || typeof quote !== 'object') {
      return;
    }

    // Обновляем bid/ask даже если timestamp котировки некорректный.
    updateQuotePrices(quote);

    const timestamp = normalizeTime(quote.timestamp ?? fallbackTime);
    const price = getQuotePrice(quote, 'bid');

    if (timestamp === null || price === null) {
      return;
    }

    const minute = getMinute(timestamp);
    const minuteKey = String(minute);

    const point = {
      time: minute,
      open: price,
      high: price,
      low: price,
      close: price,
      volume: null,
      _firstTimestamp: timestamp,
      _lastTimestamp: timestamp,
    };

    if (!state.chartData.length) {
      state.chartData.push(point);
      state.chartDataTimes.add(minuteKey);
      renderChart();
      return;
    }

    const last = state.chartData[state.chartData.length - 1];
    const lastMinute = normalizeTime(last.time);

    // Котировка относится к текущей минуте.
    if (lastMinute === minute) {
      last.high = Math.max(Number(last.high), price);
      last.low = Math.min(Number(last.low), price);

      // При одинаковом timestamp последняя обработанная котировка
      // становится close.
      if (timestamp >= (last._lastTimestamp ?? lastMinute)) {
        last.close = price;
        last._lastTimestamp = timestamp;
      }

      renderChart();
      return;
    }

    // Игнорируем запоздавшие котировки из предыдущих минут.
    if (minute < lastMinute) {
      return;
    }

    // Началась новая минута: создаём новую свечу.
    if (!state.chartDataTimes.has(minuteKey)) {
      state.chartData.push(point);
      state.chartDataTimes.add(minuteKey);
      renderChart();
    }
  }

  function disconnectChartSocket() {
    if (chartSocketReconnectTimer) {
      clearTimeout(chartSocketReconnectTimer);
      chartSocketReconnectTimer = null;
    }

    // Инвалидируем обработчики старого соединения.
    chartSocketGeneration++;

    const socket = chartSocket;
    chartSocket = null;

    if (socket) {
      socket.onopen = null;
      socket.onmessage = null;
      socket.onerror = null;
      socket.onclose = null;

      try {
        socket.close();
      } catch (e) {
        // Соединение уже могло закрыться.
      }
    }

    setChartsSocketState('отключен');
  }

  function connectChartSocket() {
    if (
      chartSocket &&
      (
        chartSocket.readyState === WebSocket.CONNECTING ||
        chartSocket.readyState === WebSocket.OPEN
      )
    ) {
      return;
    }

    const generation = ++chartSocketGeneration;
    const protocol = location.protocol === 'https:' ? 'wss:' : 'ws:';
    const url = `${protocol}//${location.host}/ws/`;

    setChartsSocketState('подключение…');

    const socket = new WebSocket(url);
    chartSocket = socket;

    socket.addEventListener('open', () => {
      if (generation !== chartSocketGeneration) return;

      setChartsSocketState('подключен', 'success');
    });

    socket.addEventListener('message', (event) => {
      if (generation !== chartSocketGeneration) return;

      let payload;

      try {
        payload = JSON.parse(event.data);
      } catch (e) {
        return;
      }

      if (
        payload.type !== 'market' ||
        !payload.data ||
        typeof payload.data !== 'object'
      ) {
        return;
      }

      const epic = getSelectedPair();

      if (!epic || !Object.prototype.hasOwnProperty.call(payload.data, epic)) {
        return;
      }

      appendChartPointFromQuote(payload.data[epic], payload.time);
    });

    socket.addEventListener('close', () => {
      if (generation !== chartSocketGeneration) return;

      chartSocket = null;
      setChartsSocketState('отключен');

      if (state.currentView === 'charts') {
        chartSocketReconnectTimer = setTimeout(() => {
          chartSocketReconnectTimer = null;

          if (
            state.currentView === 'charts' &&
            generation === chartSocketGeneration
          ) {
            connectChartSocket();
          }
        }, 3000);
      }
    });

    socket.addEventListener('error', () => {
      if (generation !== chartSocketGeneration) return;

      setChartsSocketState('ошибка', 'error');
    });
  }

  async function loadPrices(id) {
    if (!id) {
      elements.chartsContent.innerHTML =
        '<div class="muted">Пара не выбрана. Откройте рынки и выберите инструмент.</div>';

      clearChartData();
      return;
    }

    elements.chartsContent.innerHTML =
      '<div class="muted">Загрузка цен…</div>';

    try {
      deps.setStatus(`Загрузка цен для рынка ${id}…`);

      const raw = await deps.api(
        `/api/markets/${encodeURIComponent(id)}/prices`
      );

      // Если пользователь успел переключить рынок во время запроса,
      // не подменяем историю уже выбранного инструмента.
      if (getSelectedPair() !== id) return;

      const data = deps.getData(raw);

      const prices =
        (data && data.prices) ||
        (Array.isArray(data) ? data : []);

      const rows = Array.isArray(prices)
        ? prices.map((p) => {
            const open = Number(p.openPrice?.bid);
            const close = Number(p.closePrice?.bid);
            const high = Number(p.highPrice?.bid);
            const low = Number(p.lowPrice?.bid);

            return {
              time: normalizeTime(p.snapshotTimeUTC ?? p.snapshotTime),
              open,
              high,
              low,
              close,
              volume: p.lastTradedVolume ?? null,
            };
          }).filter((p) =>
            p.time !== null &&
            Number.isFinite(p.open) &&
            Number.isFinite(p.high) &&
            Number.isFinite(p.low) &&
            Number.isFinite(p.close)
          )
        : [];

      // Защита от некорректного порядка данных от API.
      rows.sort((a, b) => a.time - b.time);

      // REST возвращает минутные свечи. Нормализуем их время к началу минуты.
      const candlesByMinute = new Map();

      for (const row of rows) {
        const minute = getMinute(row.time);
        const key = String(minute);
        const existing = candlesByMinute.get(key);

        if (!existing) {
          candlesByMinute.set(key, {
            ...row,
            time: minute,
          });
        } else {
          existing.high = Math.max(existing.high, row.high);
          existing.low = Math.min(existing.low, row.low);
          existing.close = row.close;
          existing.volume = row.volume;
        }
      }

      state.chartData = Array.from(candlesByMinute.values())
        .sort((a, b) => a.time - b.time);

      state.chartDataTimes = new Set(
        state.chartData.map((item) => String(item.time))
      );

      elements.chartsContent.innerHTML = '';

      if (!state.chartData.length) {
        elements.chartsContent.innerHTML =
          '<div class="muted">Цены недоступны.</div>';
      }

      renderChart();
      deps.setStatus('Готово.');
    } catch (e) {
      if (getSelectedPair() !== id) return;

      elements.chartsContent.textContent = `Ошибка: ${e.message}`;
      elements.chartsContent.className = 'error';

      clearChartData();
      deps.setStatus(`Ошибка загрузки цен: ${e.message}`, 'error');
    }
  }

  function openMarketsModal() {
    if (!state.markets.length) {
      elements.marketsModalContent.innerHTML =
        '<div class="muted">Список рынков пуст. Откройте раздел Рынки для загрузки.</div>';

      show(elements.marketsModal);
      return;
    }

    const items = state.markets.map((m) => {
      const instrument = m.instrument || {};

      const id =
        instrument.epic ??
        instrument.symbol ??
        instrument.name ??
        'unknown';

      const name =
        instrument.name ??
        instrument.symbol ??
        `Market ${id}`;

      // Экранируем значения, попадающие в HTML.
      const safeId = String(id).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      })[char]);

      const safeName = String(name).replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      })[char]);

      return `
        <div class="flex">
          <button class="action" data-pair-id="${safeId}">Выбрать</button>
          <div class="mono">${safeName} (${safeId})</div>
        </div>
      `;
    }).join('');

    elements.marketsModalContent.innerHTML = items;

    elements.marketsModalContent
      .querySelectorAll('button[data-pair-id]')
      .forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-pair-id');

          setSelectedPair(id);
          hide(elements.marketsModal);

          // Сбрасываем старую историю и запрашиваем новую.
          clearChartData();

          if (elements.currentBid) elements.currentBid.textContent = '—';
          if (elements.currentAsk) elements.currentAsk.textContent = '—';

          window.location.hash = '#charts';
        });
      });

    show(elements.marketsModal);
  }

  async function ensureMarketsLoaded() {
    if (state.markets && state.markets.length) {
      return;
    }

    await deps.loadMarkets();
  }

  async function load() {
    if (!initialized) {
      throw new Error('ChartsPage не инициализирована');
    }

    if (!chartComponentInstance) {
      chartComponentInstance = new window.ChartComponent(
        elements.chartsCanvasContainer
      );
    }

    await ensureMarketsLoaded();

    updateSelectedPairLabel();

    await loadPrices(getSelectedPair());

    connectChartSocket();
  }

  function init(options) {
    deps = options;
    state = deps.state;

    elements.chartsContent = document.getElementById('chartsContent');

    elements.chartsCanvasContainer =
      document.getElementById('chartsCanvasContainer');

    elements.openMarketsModalBtn =
      document.getElementById('openMarketsModalBtn');

    elements.closeMarketsModalBtn =
      document.getElementById('closeMarketsModalBtn');

    elements.marketsModal = document.getElementById('marketsModal');

    elements.marketsModalContent =
      document.getElementById('marketsModalContent');

    elements.selectedPairLabel =
      document.getElementById('selectedPairLabel');

    elements.chartsSocketState =
      document.getElementById('chartsSocketState');

    elements.createPositionBtn =
      document.getElementById('createPositionBtn');

    elements.createPositionState =
      document.getElementById('createPositionState');

    elements.positionDirection =
      document.getElementById('positionDirection');

    elements.positionSize =
      document.getElementById('positionSize');

    // Эти элементы добавляются в HTML. Они необязательны для работы графика.
    elements.currentBid = document.getElementById('currentBid');
    elements.currentAsk = document.getElementById('currentAsk');

    elements.openMarketsModalBtn.addEventListener(
      'click',
      openMarketsModal
    );

    elements.closeMarketsModalBtn.addEventListener('click', () => {
      hide(elements.marketsModal);
    });

    elements.createPositionBtn?.addEventListener(
      'click',
      createPosition
    );

    elements.positionSize?.addEventListener('input', updateCreatePositionButtonLabel);
    elements.positionDirection?.addEventListener('change', updateCreatePositionButtonLabel);

    updateCreatePositionButtonLabel();

    initialized = true;
  }

  async function createPosition() {
    const epic = getSelectedPair();

    if (!epic) {
      elements.createPositionState.textContent =
        'Сначала выберите рынок.';

      elements.createPositionState.className = 'error small';
      return;
    }

    const size = Number(elements.positionSize?.value);
    const direction = elements.positionDirection?.value || 'BUY';

    if (!Number.isFinite(size) || size <= 0) {
      elements.createPositionState.textContent =
        'Некорректный размер позиции.';

      elements.createPositionState.className = 'error small';
      return;
    }

    const entryPrice = direction === 'BUY'
      ? lastQuotePrices.ask
      : lastQuotePrices.bid;

    if (!Number.isFinite(entryPrice)) {
      elements.createPositionState.textContent =
        'Цена недоступна. Дождитесь котировки и попробуйте снова.';

      elements.createPositionState.className = 'error small';
      return;
    }

    elements.createPositionState.textContent = 'Создание позиции…';
    elements.createPositionState.className = 'muted small';

    updateCreatePositionButtonLabel();

    try {
      await deps.api('/api/positions', {
        method: 'POST',
        body: JSON.stringify({
          epic,
          direction,
          size,
        }),
      });

      elements.createPositionState.textContent = '';
      elements.createPositionState.className = 'small muted';
      alert('Позиция успешно создана.');
    } catch (e) {
      elements.createPositionState.textContent = `Ошибка: ${e.message}`;
      elements.createPositionState.className = 'error small';
    }
  }

  return {
    init,
    load,
    disconnect: disconnectChartSocket,
    setSelectedPair,
    getSelectedPair,
  };
})();

window.ChartsPage = ChartsPage;
