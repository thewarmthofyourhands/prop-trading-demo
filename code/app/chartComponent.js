
(function () {
  class ChartComponent {
    constructor(container, options = {}) {
      if (!container) {
        throw new Error('ChartComponent: container is required');
      }

      this.container = container;
      this.options = options;
      this.baseHeight = Number(options.height) > 0
        ? Number(options.height)
        : 320;

      this.priceDivisions = 6;

      // Настройки временной шкалы.
      this.timeStep = 5 * 60 * 1000;
      this.candleDuration = 60 * 1000;
      this.visibleTimeLabels = 8;

      this.data = [];

      this.canvas = document.createElement('canvas');
      this.canvas.style.display = 'block';
      this.canvas.style.width = '100%';

      this.ctx = this.canvas.getContext('2d');

      this.container.innerHTML = '';
      this.container.appendChild(this.canvas);

      this.resizeObserver = new ResizeObserver(() => this.resize());
      this.resizeObserver.observe(this.container);

      this.resize();
    }

    destroy() {
      if (this.resizeObserver) {
        this.resizeObserver.disconnect();
        this.resizeObserver = null;
      }

      if (this.canvas?.parentNode) {
        this.canvas.parentNode.removeChild(this.canvas);
      }

      this.data = [];
      this.canvas = null;
      this.ctx = null;
    }

    resize() {
      if (!this.canvas || !this.ctx) return;

      const dpr = window.devicePixelRatio || 1;
      const width = Math.max(1, this.container.clientWidth);
      const height = Math.max(1, this.container.clientHeight || this.baseHeight);
      console.log(height, Math.floor(height * dpr));

      this.canvas.style.width = `${width}px`;
      this.canvas.style.height = `${height}px`;

      this.canvas.width = Math.floor(width * dpr);
      this.canvas.height = Math.floor(height * dpr);

      this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

      this.render();
    }

    setData(data) {
      this.data = Array.isArray(data)
        ? data.filter((candle) =>
            candle &&
            Number.isFinite(Number(candle.open)) &&
            Number.isFinite(Number(candle.high)) &&
            Number.isFinite(Number(candle.low)) &&
            Number.isFinite(Number(candle.close)) &&
            Number.isFinite(Number(candle.time))
          )
        : [];

      this.render();
    }

    formatPrice(value) {
      return value.toLocaleString('en-US', {
        maximumFractionDigits: 4,
      });
    }

    formatTime(value) {
      const timestamp = Number(value);

      if (!Number.isFinite(timestamp)) return '';

      return new Date(timestamp).toLocaleTimeString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: 'UTC',
      });
    }

    render() {
      if (!this.canvas || !this.ctx) return;

      const ctx = this.ctx;
      const dpr = window.devicePixelRatio || 1;

      const width = this.canvas.width / dpr;
      const height = this.canvas.height / dpr;

      ctx.clearRect(0, 0, width, height);

      ctx.fillStyle = '#000000';
      ctx.fillRect(0, 0, width, height);

      if (!this.data.length) {
        ctx.fillStyle = '#ffffff';
        ctx.font = '12px system-ui, sans-serif';
        ctx.fillText('Нет данных для графика', 10, 20);
        return;
      }

      const candles = [...this.data].sort(
        (a, b) => Number(a.time) - Number(b.time)
      );

      const lastCandle = candles[candles.length - 1];
      const lastTime = Number(lastCandle.time);

      // Окно графика движется вместе с последними данными.
      // Последняя свеча остаётся около правого края.
      const windowEnd = lastTime + this.candleDuration;
      const windowDuration =
        this.timeStep * (this.visibleTimeLabels - 1);
      const windowStart = windowEnd - windowDuration;

      // Отображаем только свечи, попадающие в видимое окно.
      const visibleCandles = candles.filter((candle) => {
        const time = Number(candle.time);

        return (
          time + this.candleDuration >= windowStart &&
          time <= windowEnd
        );
      });

      if (!visibleCandles.length) return;

      const values = [];

      for (const candle of visibleCandles) {
        values.push(
          Number(candle.open),
          Number(candle.high),
          Number(candle.low),
          Number(candle.close)
        );
      }

      const min = Math.min(...values);
      const max = Math.max(...values);
      const range = max - min || Math.max(Math.abs(max) * 0.001, 1);

      const leftPad = 80;
      const leftPricePad = -76;
      const rightPad = 12;
      const topPad = 16;
      const bottomPad = 42;

      const plotLeft = leftPad;
      const plotRight = width - rightPad;
      const plotWidth = Math.max(1, plotRight - plotLeft);

      const plotHeight = Math.max(
        1,
        height - topPad - bottomPad
      );

      const paddedMin = min - range * 0.05;
      const paddedMax = max + range * 0.05;
      const paddedRange = paddedMax - paddedMin || 1;

      const priceToY = (price) => {
        return topPad +
          ((paddedMax - price) / paddedRange) * plotHeight;
      };

      // Горизонтальная сетка цены.
      ctx.strokeStyle = '#252525';
      ctx.lineWidth = 1;

      for (let i = 0; i <= this.priceDivisions; i++) {
        const y = topPad +
          (plotHeight / this.priceDivisions) * i;

        ctx.beginPath();
        ctx.moveTo(plotLeft, y);
        ctx.lineTo(plotRight, y);
        ctx.stroke();
      }

      // Временная сетка: фиксированные отметки каждые 5 минут.
      const firstTimeMark =
        Math.ceil(windowStart / this.timeStep) * this.timeStep;

      ctx.strokeStyle = '#202020';
      ctx.lineWidth = 1;

      for (
        let time = firstTimeMark;
        time <= windowEnd;
        time += this.timeStep
      ) {
        const x = plotLeft +
          ((time - windowStart) / windowDuration) * plotWidth;

        ctx.beginPath();
        ctx.moveTo(x, topPad);
        ctx.lineTo(x, height - bottomPad);
        ctx.stroke();
      }

      // Свечи позиционируются по времени, а не по индексу.
      const candleWidth = Math.max(
        1,
        Math.min(
          14,
          (this.candleDuration / windowDuration) * plotWidth * 0.65
        )
      );

      // Ограничиваем рисование областью графика.
      ctx.save();
      ctx.beginPath();
      ctx.rect(plotLeft, topPad, plotWidth, plotHeight);
      ctx.clip();

      for (const candle of visibleCandles) {
        const time = Number(candle.time);

        const open = Number(candle.open);
        const high = Number(candle.high);
        const low = Number(candle.low);
        const close = Number(candle.close);

        // Центр свечи находится посередине её минутного интервала.
        const x = plotLeft +
          ((time + this.candleDuration / 2 - windowStart) /
            windowDuration) * plotWidth;

        const openY = priceToY(open);
        const closeY = priceToY(close);
        const highY = priceToY(high);
        const lowY = priceToY(low);

        const isBullish = close >= open;
        const candleColor = isBullish ? '#26a69a' : '#ef5350';

        // Фитиль.
        ctx.strokeStyle = candleColor;
        ctx.lineWidth = 1;

        ctx.beginPath();
        ctx.moveTo(x, highY);
        ctx.lineTo(x, lowY);
        ctx.stroke();

        // Тело.
        const bodyTop = Math.min(openY, closeY);
        const bodyHeight = Math.max(1, Math.abs(closeY - openY));

        ctx.fillStyle = candleColor;
        ctx.fillRect(
          x - candleWidth / 2,
          bodyTop,
          candleWidth,
          bodyHeight
        );
      }

      // Линия последней цены.
      const lastPrice = Number(lastCandle.close);
      const lastY = priceToY(lastPrice);

      ctx.strokeStyle = '#7c4dff';
      ctx.setLineDash([4, 3]);

      ctx.beginPath();
      ctx.moveTo(plotLeft, lastY);
      ctx.lineTo(plotRight, lastY);
      ctx.stroke();

      ctx.setLineDash([]);
      ctx.restore();

      // Ценовые подписи в отдельной области слева.
      ctx.fillStyle = '#ffffff';
      ctx.font = '10px system-ui, sans-serif';
      ctx.textAlign = 'left';
      ctx.textBaseline = 'middle';

      for (let i = 0; i <= this.priceDivisions; i++) {
        const price = paddedMax -
          (paddedRange / this.priceDivisions) * i;

        const y = topPad +
          (plotHeight / this.priceDivisions) * i;

        ctx.fillText(
          this.formatPrice(price),
          leftPad + leftPricePad,
          y,
          leftPad - 12
        );
      }

      // Метка последней цены справа.
      const labelY = Math.max(
        topPad,
        Math.min(height - bottomPad - 16, lastY - 8)
      );

      ctx.fillStyle = '#7c4dff';
      ctx.fillRect(
        Math.max(plotLeft, plotRight - 90),
        labelY,
        Math.min(90, plotWidth),
        16
      );

      ctx.fillStyle = '#ffffff';
      ctx.font = '10px system-ui, sans-serif';
      ctx.textAlign = 'right';
      ctx.textBaseline = 'middle';

      ctx.fillText(
        this.formatPrice(lastPrice),
        plotRight - 4,
        labelY + 8,
        Math.max(1, plotWidth - 8)
      );

      // Нижняя граница графика.
      const timeY = height - 12;

      ctx.strokeStyle = '#252525';
      ctx.lineWidth = 1;

      ctx.beginPath();
      ctx.moveTo(plotLeft, height - bottomPad);
      ctx.lineTo(plotRight, height - bottomPad);
      ctx.stroke();

      // Подписи времени совпадают с пятиминутными отметками.
      ctx.fillStyle = '#ffffff';
      ctx.font = '10px system-ui, sans-serif';
      ctx.textBaseline = 'middle';

      const timeMarks = [];

      for (
        let time = firstTimeMark;
        time <= windowEnd;
        time += this.timeStep
      ) {
        timeMarks.push(time);
      }

      for (let i = 0; i < timeMarks.length; i++) {
        const time = timeMarks[i];

        const x = plotLeft +
          ((time - windowStart) / windowDuration) * plotWidth;

        // Крайние подписи выравниваем внутрь графика.
        if (i === 0) {
          ctx.textAlign = 'left';
        } else if (i === timeMarks.length - 1) {
          ctx.textAlign = 'right';
        } else {
          ctx.textAlign = 'center';
        }

        ctx.fillText(
          this.formatTime(time),
          x,
          timeY
        );
      }

      ctx.textAlign = 'left';
      ctx.textBaseline = 'alphabetic';
    }
  }

  window.ChartComponent = ChartComponent;
})();
