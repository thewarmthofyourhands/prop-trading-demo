const PositionsPage = (() => {
  let deps = {};
  let initialized = false;

  const elements = {};

  function init(options) {
    deps = options;

    elements.positionsContent = document.getElementById('positionsContent');

    initialized = true;
  }

  function normalizePositions(payload) {
    const data = payload && typeof payload === 'object' ? payload : {};

    const list = Array.isArray(data.positions)
      ? data.positions
      : (Array.isArray(payload) ? payload : []);

    return list.map((item) => {
      const position = item && item.position ? item.position : item;
      const market = item && item.market ? item.market : {};

      return {
        id: position.dealId ?? position.id ?? position.uuid,
        instrument:
          market.instrumentName ??
          market.symbol ??
          position.epic ??
          position.market ??
          position.instrument,
        direction: position.direction,
        size: position.size ?? position.volume,
        level: position.level,
        upl: position.upl ?? position.profit ?? position.pnl,
        currency: position.currency,
        createdAt: position.createdDateUTC ?? position.createdDate,
      };
    });
  }

  function getPlClass(value) {
    const num = Number(value);
    if (!Number.isFinite(num) || num === 0) return 'pl-zero';
    return num > 0 ? 'pl-positive' : 'pl-negative';
  }

  function renderTable(items) {
    const head = [
      'ID',
      'Инструмент',
      'Направление',
      'Размер',
      'Уровень',
      'P/L',
      'Валюта',
      'Создана (UTC)',
      'Действие',
    ].map((label) => `<th>${deps.asText(label)}</th>`).join('');

    const body = items.map((item) => {
      const plClass = getPlClass(item.upl);
      const plText = deps.asText(item.upl);
      const id = item.id;
      const action = id
        ? `<button class="danger" data-action="close" data-id="${deps.asText(id)}">Закрыть</button>`
        : '';

      return (
        '<tr>' +
        `<td>${deps.asText(item.id)}</td>` +
        `<td>${deps.asText(item.instrument)}</td>` +
        `<td>${deps.asText(item.direction)}</td>` +
        `<td>${deps.asText(item.size)}</td>` +
        `<td>${deps.asText(item.level)}</td>` +
        `<td><span class="${plClass}">${plText}</span></td>` +
        `<td>${deps.asText(item.currency)}</td>` +
        `<td>${deps.asText(item.createdAt)}</td>` +
        `<td>${action}</td>` +
        '</tr>'
      );
    }).join('');

    return `<table><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>`;
  }

  function render(payload) {
    const items = normalizePositions(payload);

    if (!items.length) {
      elements.positionsContent.innerHTML = '<div class="muted">Позиции отсутствуют.</div>';
      return;
    }

    elements.positionsContent.innerHTML = renderTable(items);

    elements.positionsContent
      .querySelectorAll('button[data-action="close"][data-id]')
      .forEach((button) => {
        button.addEventListener('click', async () => {
          const id = button.getAttribute('data-id');
          if (!id) return;

          if (!window.confirm(`Закрыть позицию ${id}?`)) return;

          deps.setStatus(`Закрытие позиции ${id}…`);
          button.disabled = true;

          try {
            await deps.api(`/api/positions/${encodeURIComponent(id)}`, { method: 'DELETE' });
            deps.setStatus(`Позиция ${id} закрыта.`, 'success');
            await load();
          } catch (e) {
            deps.setStatus(`Ошибка закрытия позиции ${id}: ${e.message}`, 'error');
            button.disabled = false;
          }
        });
      });
  }

  async function load() {
    if (!initialized) {
      throw new Error('PositionsPage не инициализирована');
    }

    try {
      deps.setStatus('Загрузка позиций…');

      const raw = await deps.api('/api/positions');

      render(deps.getData(raw));

      deps.setStatus('Готово.');
    } catch (e) {
      elements.positionsContent.textContent = `Ошибка: ${e.message}`;
      elements.positionsContent.className = 'error';

      deps.setStatus(`Ошибка загрузки позиций: ${e.message}`, 'error');
    }
  }

  return {
    init,
    load,
    render,
  };
})();

window.PositionsPage = PositionsPage;
