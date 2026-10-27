const ChartsPage = (() => {
  let deps = {};
  let initialized = false;

  let chartComponentInstance = null;
  let chartSocket = null;
  let chartSocketReconnectTimer = null;

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

    elements.selectedPairLabel.textContent = id
      ? `Выбрано: ${id}`
      : 'Пара не выбрана';
  }

  function setChartsSocketState(message, kind = 'muted') {
    elements.chartsSocketState.textContent = `WS: ${message}`;
    elements.chartsSocketState.className = `${kind} small`;
  }

  function normalizeTime(value) {
    if (value === null || value === undefined) {
      return null;
    }

    if (typeof value === 'number' && Number.isFinite(value)) {
      return value;
    }

    const asNumber = Number(value);

    if (Number.isFinite(asNumber)) {
      return asNumber;
    }

    const parsed = Date.parse(String(value));

    return Number.isNaN(parsed) ? null : parsed;
  }

  function appendChartPointFromQuote(quote, fallbackTime) {
    const epic = getSelectedPair();

    if (!epic || !quote || typeof quote !== 'object') {
      return;
    }

    const time = normalizeTime(quote.timestamp ?? fallbackTime);
    const price = Number(quote.bid ?? quote.ask);

    if (time === null || !Number.isFinite(price)) {
      return;
    }

    const timeKey = String(time);

    const point = {
      time,
      open: price,
      high: price,
      low: price,
      close: price,
      volume: null,
    };

    if (!state.chartData.length) {
      state.chartData.push(point);
      state.chartDataTimes.add(timeKey);

      if (chartComponentInstance) {
        chartComponentInstance.setData(state.chartData);
      }

      return;
    }

    const last = state.chartData[state.chartData.length - 1];
    const lastTimeKey = String(last.time);

    if (lastTimeKey === timeKey) {
      last.close = price;
      last.high = Math.max(Number(last.high ?? price), price);
      last.low = Math.min(Number(last.low ?? price), price);

      if (chartComponentInstance) {
        chartComponentInstance.setData(state.chartData);
      }

      return;
    }

    if (state.chartDataTimes.has(timeKey)) {
      return;
    }

    state.chartData.push(point);
    state.chartDataTimes.add(timeKey);

    if (chartComponentInstance) {
      chartComponentInstance.setData(state.chartData);
    }
  }

  function disconnectChartSocket() {
    if (chartSocketReconnectTimer) {
      clearTimeout(chartSocketReconnectTimer);
      chartSocketReconnectTimer = null;
    }

    if (chartSocket) {
      try {
        chartSocket.close();
      } catch (e) {
        // Сокет мог уже закрыться.
      }
    }

    chartSocket = null;
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

    const protocol = location.protocol === 'https:' ? 'wss:' : 'ws:';
    const url = `${protocol}//${location.host}/ws/`;

    setChartsSocketState('подключение…');

    chartSocket = new WebSocket(url);

    chartSocket.addEventListener('open', () => {
      setChartsSocketState('подключен', 'success');
    });

    chartSocket.addEventListener('message', (event) => {
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

      if (!epic || !(epic in payload.data)) {
        return;
      }

      appendChartPointFromQuote(payload.data[epic], payload.time);
    });

    chartSocket.addEventListener('close', () => {
      setChartsSocketState('отключен');

      if (state.currentView === 'charts') {
        chartSocketReconnectTimer = setTimeout(() => {
          chartSocketReconnectTimer = null;
          connectChartSocket();
        }, 3000);
      }
    });

    chartSocket.addEventListener('error', () => {
      setChartsSocketState('ошибка', 'error');
    });
  }

  async function loadPrices(id) {
    if (!id) {
      elements.chartsContent.innerHTML =
        '<div class="muted">Пара не выбрана. Откройте рынки и выберите инструмент.</div>';

      state.chartData = [];
      state.chartDataTimes = new Set();

      if (chartComponentInstance) {
        chartComponentInstance.setData([]);
      }

      return;
    }

    elements.chartsContent.innerHTML =
      '<div class="muted">Загрузка цен…</div>';

    try {
      deps.setStatus(`Загрузка цен для рынка ${id}…`);

      const raw = await deps.api(
        `/api/markets/${encodeURIComponent(id)}/prices`
      );

      const data = deps.getData(raw);

      const prices =
        (data && data.prices) ||
        (Array.isArray(data) ? data : []);

      const rows = Array.isArray(prices)
        ? prices.map((p) => {
            const openBid = p.openPrice && p.openPrice.bid;
            const closeBid = p.closePrice && p.closePrice.bid;
            const highBid = p.highPrice && p.highPrice.bid;
            const lowBid = p.lowPrice && p.lowPrice.bid;

            return {
              time: p.snapshotTimeUTC ?? p.snapshotTime,
              open: openBid,
              high: highBid,
              low: lowBid,
              close: closeBid,
              volume: p.lastTradedVolume,
            };
          })
        : [];

      if (!rows.length) {
        elements.chartsContent.innerHTML =
          '<div class="muted">Цены недоступны.</div>';

        state.chartData = [];
        state.chartDataTimes = new Set();

        if (chartComponentInstance) {
          chartComponentInstance.setData([]);
        }
      } else {
        elements.chartsContent.innerHTML = '';

        state.chartData = rows;
        state.chartDataTimes = new Set(
          rows.map((item) => String(item.time))
        );

        if (chartComponentInstance) {
          chartComponentInstance.setData(state.chartData);
        }
      }

      deps.setStatus('Готово.');
    } catch (e) {
      elements.chartsContent.textContent = `Ошибка: ${e.message}`;
      elements.chartsContent.className = 'error';

      state.chartData = [];
      state.chartDataTimes = new Set();

      if (chartComponentInstance) {
        chartComponentInstance.setData([]);
      }

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

      return `
        <div class="flex">
          <button class="action" data-pair-id="${id}">Выбрать</button>
          <div class="mono">${name} (${id})</div>
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

    elements.createPositionState.textContent = 'Создание позиции…';
    elements.createPositionState.className = 'muted small';

    try {
      await deps.api('/api/positions', {
        method: 'POST',
        body: JSON.stringify({
          epic,
          direction,
          size,
        }),
      });

      elements.createPositionState.textContent = 'Позиция создана.';
      elements.createPositionState.className = 'success small';
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
