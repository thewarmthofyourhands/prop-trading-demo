(function (window, document) {
  'use strict';

  /**
   * Логика страницы «Обзор».
   *
   * Подключение:
   *   <script src="overview.js"></script>
   *
   * Инициализация из основного файла:
   *   window.OverviewPage.init({ api, getData, asText, setStatus });
   *   await window.OverviewPage.load();
   *
   * Общие функции передаются из основного приложения, чтобы не дублировать
   * авторизацию, обработку API-ответов и отображение общего статуса.
   */

  const content = document.getElementById('overviewContent');

  let dependencies = null;

  function init(options) {
    if (!options || typeof options !== 'object') {
      throw new TypeError('OverviewPage.init ожидает объект с общими функциями приложения.');
    }

    const required = ['api', 'getData', 'asText', 'setStatus', 'formatMoney'];
    const missing = required.filter((name) => typeof options[name] !== 'function');

    if (missing.length > 0) {
      throw new TypeError(`OverviewPage: не переданы зависимости: ${missing.join(', ')}`);
    }

    if (!content) {
      throw new Error('Не найден элемент #overviewContent.');
    }

    dependencies = options;
  }

  async function load() {
    if (!dependencies) {
      throw new Error('Сначала вызовите OverviewPage.init().');
    }

    if (!content) {
      throw new Error('Не найден элемент #overviewContent.');
    }

    const { api, getData, asText, setStatus, formatMoney } = dependencies;

    try {
      setStatus('Загрузка активного челленджа…');

      const raw = await api('/api/challenge-accounts/1');
      const data = getData(raw) || {};

      const challenge = data.challenge || {};
      const trading = data.trading_account || {};
      const progress = data.progress || {};

      content.innerHTML = [
        '<div class="grid">',
        `<div class="card"><div class="small muted">Челлендж</div><div><strong>${asText(challenge.name)}</strong> (${asText(challenge.type)})</div></div>`,
        `<div class="card"><div class="small muted">Статус</div><div class="mono">${asText(data.status)}</div></div>`,
        `<div class="card"><div class="small muted">Размер аккаунта</div><div class="mono">${formatMoney(challenge.account_size, asText(challenge.currency))}</div></div>`,
        `<div class="card"><div class="small muted">Торговый аккаунт</div><div class="mono">${asText(trading.platform)} #${asText(trading.id)}</div></div>`,
        `<div class="card"><div class="small muted">Баланс</div><div class="mono">${formatMoney(trading.balance, asText(trading.currency))}</div></div>`,
        `<div class="card"><div class="small muted">Profit</div><div class="mono">${formatMoney(progress.profit, asText(trading.currency))}</div></div>`,
        `<div class="card"><div class="small muted">Торговых дней</div><div class="mono">${asText(progress.trading_days_completed)}</div></div>`,
        '</div>',
      ].join('');

      setStatus('Готово.');
    } catch (error) {
      content.textContent = `Ошибка: ${error.message}`;
      content.className = 'error';
      setStatus(`Ошибка загрузки активного челленджа: ${error.message}`, 'error');
    }
  }

  window.OverviewPage = {
    init,
    load,
  };
})(window, document);