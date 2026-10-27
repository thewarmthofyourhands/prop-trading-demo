const HistoryPage = (() => {
  let deps = {};
  let initialized = false;

  let historyContent = null;

  function init(options) {
    deps = options;
    historyContent = document.getElementById('historyContent');
    initialized = true;
  }

  function getSizeClass(value) {
    const num = Number(value);
    if (!Number.isFinite(num) || num === 0) return 'pl-zero';
    return num > 0 ? 'pl-positive' : 'pl-negative';
  }

  function renderTable(items) {
    const columns = [
      {
        label: 'Время (UTC)',
        value: (item) => item.dateUtc ?? item.date,
      },
      {
        label: 'Инструмент',
        value: (item) => item.instrumentName,
      },
      {
        label: 'Тип',
        value: (item) => item.transactionType,
      },
      {
        label: 'Размер',
        value: (item) => item.size,
      },
      {
        label: 'Валюта',
        value: (item) => item.currency,
      },
      {
        label: 'Статус',
        value: (item) => item.status,
      },
      {
        label: 'Примечание',
        value: (item) => item.note,
      },
      {
        label: 'Reference',
        value: (item) => item.reference,
      },
    ];

    const head = `<tr>${columns.map((c) => `<th>${deps.asText(c.label)}</th>`).join('')}</tr>`;
    const body = items.map((item) => {
      const cells = columns.map((c) => {
        const raw = typeof c.value === 'function' ? c.value(item) : item[c.value];
        const text = deps.asText(raw);

        if (c.label === 'Размер') {
          return `<td><span class="${getSizeClass(raw)}">${text}</span></td>`;
        }

        return `<td>${text}</td>`;
      }).join('');

      return `<tr>${cells}</tr>`;
    }).join('');

    return `<table><thead>${head}</thead><tbody>${body}</tbody></table>`;
  }

  async function load() {
    if (!initialized) {
      throw new Error('HistoryPage не инициализирована');
    }

    try {
      deps.setStatus('Загрузка истории…');

      const raw = await deps.api('/api/history');
      const data = deps.getData(raw);

      const transactions = (data && data.transactions) || [];

      if (!Array.isArray(transactions) || transactions.length === 0) {
        historyContent.innerHTML = '<div class="muted">Транзакции отсутствуют.</div>';
      } else {
        historyContent.innerHTML = renderTable(transactions);
      }

      deps.setStatus('Готово.');
    } catch (e) {
      historyContent.textContent = `Ошибка: ${e.message}`;
      historyContent.className = 'error';

      deps.setStatus(`Ошибка загрузки истории: ${e.message}`, 'error');
    }
  }

  return {
    init,
    load,
  };
})();

window.HistoryPage = HistoryPage;
