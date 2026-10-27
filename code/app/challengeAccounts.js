(function (window, document) {
  'use strict';

  const content = document.getElementById('challengeAccountsContent');

  let dependencies = null;

  function init(options) {
    if (!options || typeof options !== 'object') {
      throw new TypeError('ChallengeAccountsPage.init ожидает объект зависимостей.');
    }

    const required = [
      'api',
      'getData',
      'asText',
      'setStatus',
      'loadOverview',
      'loadTopBalance',
      'formatMoney',
    ];

    const missing = required.filter(
      (name) => typeof options[name] !== 'function'
    );

    if (missing.length > 0) {
      throw new TypeError(
        `Не переданы зависимости: ${missing.join(', ')}`
      );
    }

    if (!content) {
      throw new Error('Не найден элемент #challengeAccountsContent.');
    }

    dependencies = options;
  }

  function render(list) {
    const { asText, formatMoney } = dependencies;
    const items = Array.isArray(list) ? list : [];

    if (!items.length) {
      content.innerHTML = '<div class="muted">Аккаунты отсутствуют.</div>';
      return;
    }

    const rows = items.map((item) => {
      const id = item.id ?? item.uuid;
      const status = item.status ?? item.state;
      const challengeName =
        (item.challenge && item.challenge.name) || item.name || '—';

      const tradingAccount = item.trading_account || {};

      const tradingAccountLabel = tradingAccount.id
        ? `${tradingAccount.platform ?? ''} #${tradingAccount.id}`.trim()
        : '—';

      const balance =
        tradingAccount.balance !== null &&
        tradingAccount.balance !== undefined
          ? formatMoney(tradingAccount.balance, tradingAccount.currency)
          : '—';

      const createdAt = item.created_at ?? '—';

      const canActivate =
        String(status || '').toLowerCase() === 'inactive';

      const actionHtml = canActivate
        ? `<button class="action" data-activate-id="${asText(id)}">Активировать</button>`
        : '—';

      return `
        <tr>
          <td>${asText(id)}</td>
          <td>${asText(challengeName)}</td>
          <td>${asText(status)}</td>
          <td>${asText(createdAt)}</td>
          <td>${asText(tradingAccountLabel)}</td>
          <td>${asText(balance)}</td>
          <td>${actionHtml}</td>
        </tr>
      `;
    }).join('');

    content.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Челлендж</th>
            <th>Статус</th>
            <th>Создан</th>
            <th>Торговый аккаунт</th>
            <th>Баланс</th>
            <th>Действие</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    `;

    content.querySelectorAll('button[data-activate-id]').forEach((button) => {
      button.addEventListener('click', async () => {
        await activate(button);
      });
    });
  }

  async function activate(button) {
    const {
      api,
      setStatus,
      loadOverview,
      loadTopBalance,
    } = dependencies;

    const id = button.getAttribute('data-activate-id');

    button.disabled = true;
    button.textContent = 'Активация…';

    try {
      await api(
        `/api/challenge-accounts/${encodeURIComponent(id)}/activate`,
        { method: 'POST' }
      );

      await load();

      await loadOverview();
      await loadTopBalance();
    } catch (error) {
      button.disabled = false;
      button.textContent = 'Активировать';

      setStatus(
        `Ошибка активации аккаунта ${id}: ${error.message}`,
        'error'
      );
    }
  }

  async function load() {
    if (!dependencies) {
      throw new Error('Сначала вызовите ChallengeAccountsPage.init().');
    }

    const { api, getData, setStatus } = dependencies;

    try {
      setStatus('Загрузка challenge аккаунтов…');

      const raw = await api('/api/challenge-accounts');

      render(getData(raw));

      setStatus('Готово.');
    } catch (error) {
      content.textContent = `Ошибка: ${error.message}`;
      content.className = 'error';

      setStatus(
        `Ошибка загрузки challenge аккаунтов: ${error.message}`,
        'error'
      );
    }
  }

  window.ChallengeAccountsPage = {
    init,
    load,
    render,
  };
})(window, document);