(function (window, document) {
  'use strict';

  const marketsContent = document.getElementById('marketsContent');

  let dependencies = null;
  let markets = [];

  function init(options) {
    if (!options || typeof options !== 'object') {
      throw new TypeError('MarketsPage.init ожидает объект зависимостей.');
    }

    const required = [
      'api',
      'getData',
      'asText',
      'setStatus',
      'setSelectedPair',
    ];

    const missing = required.filter(
      (name) => typeof options[name] !== 'function'
    );

    if (missing.length > 0) {
      throw new TypeError(
        `Не переданы зависимости: ${missing.join(', ')}`
      );
    }

    if (!marketsContent) {
      throw new Error('Не найден контейнер списка рынков.');
    }

    dependencies = options;
  }

  function renderTable(items, columns) {
    const { asText } = dependencies;

    if (!Array.isArray(items) || items.length === 0) {
      return '<div class="muted">Нет данных</div>';
    }

    const header = `
      <tr>
        ${columns.map((column) => `<th>${column.label}</th>`).join('')}
      </tr>
    `;

    const body = items.map((item) => {
      const cells = columns.map((column) => {
        const value = typeof column.value === 'function'
          ? column.value(item)
          : item[column.value];

        return `<td>${asText(value)}</td>`;
      }).join('');

      return `<tr>${cells}</tr>`;
    }).join('');

    return `
      <table>
        <thead>${header}</thead>
        <tbody>${body}</tbody>
      </table>
    `;
  }

  function openMarket(epic) {
    if (!epic) {
      return;
    }

    dependencies.setSelectedPair(epic);
    window.location.hash = '#charts';
  }

  function render(payload) {
    const items = Array.isArray(payload)
      ? payload
      : (
          Array.isArray(payload && payload.marketDetails)
            ? payload.marketDetails
            : []
        );

    markets = items;

    if (!items.length) {
      marketsContent.innerHTML =
        '<div class="muted">Рынки недоступны.</div>';
      return;
    }

    const rows = items.map((market) => {
      const instrument = market.instrument || {};
      const snapshot = market.snapshot || {};

      const id =
        instrument.epic ??
        instrument.symbol ??
        instrument.name;

      return {
        epic: id,
        name: instrument.name,
        currency: instrument.currency,
        status: snapshot.marketStatus,
        bid: snapshot.bid,
        offer: snapshot.offer,
        change: snapshot.percentageChange,
        action: id
          ? `<button class="action" data-action="open-chart" data-epic="${dependencies.asText(id)}">Открыть</button>`
          : '',
      };
    });

    marketsContent.innerHTML = renderTable(rows, [
      { label: 'Epic', value: 'epic' },
      { label: 'Название', value: 'name' },
      { label: 'Валюта', value: 'currency' },
      { label: 'Статус', value: 'status' },
      { label: 'Bid', value: 'bid' },
      { label: 'Offer', value: 'offer' },
      { label: '%', value: 'change' },
      { label: 'Charts', value: 'action' },
    ]);

    marketsContent
      .querySelectorAll('button[data-action="open-chart"][data-epic]')
      .forEach((button) => {
        button.addEventListener('click', () => {
          openMarket(button.getAttribute('data-epic'));
        });
      });
  }

  async function load() {
    if (!dependencies) {
      throw new Error('Сначала вызовите MarketsPage.init().');
    }

    const { api, getData, setStatus } = dependencies;

    try {
      setStatus('Загрузка рынков…');

      const raw = await api('/api/markets');

      render(getData(raw));

      setStatus('Готово.');
    } catch (error) {
      marketsContent.textContent = `Ошибка: ${error.message}`;
      marketsContent.className = 'error';

      setStatus(
        `Ошибка загрузки рынков: ${error.message}`,
        'error'
      );
    }
  }

  function getMarkets() {
    return markets;
  }

  window.MarketsPage = {
    init,
    load,
    render,
    getMarkets,
  };
})(window, document);
