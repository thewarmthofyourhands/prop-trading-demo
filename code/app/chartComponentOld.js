(function () {
  class ChartComponent {
    constructor(container, options = {}) {
      if (!container) {
        throw new Error('ChartComponent: container is required');
      }

      this.container = container;
      this.options = options;
      this.baseHeight = Number(options.height) > 0 ? Number(options.height) : 320;
      this.data = [];
      this.canvas = document.createElement('canvas');
      this.canvas.style.display = 'block';
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
      if (this.canvas && this.canvas.parentNode) {
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
      const height = this.baseHeight;

      this.canvas.style.width = `${width}px`;
      this.canvas.style.height = `${height}px`;
      this.canvas.width = Math.floor(width * dpr);
      this.canvas.height = Math.floor(height * dpr);

      this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      this.render();
    }

    setData(data) {
      this.data = Array.isArray(data) ? data : [];
      this.render();
    }

    render() {
      if (!this.canvas || !this.ctx) return;

      const ctx = this.ctx;
      const width = this.canvas.width / (window.devicePixelRatio || 1);
      const height = this.canvas.height / (window.devicePixelRatio || 1);

      ctx.clearRect(0, 0, width, height);

      ctx.fillStyle = '#111111';
      ctx.fillRect(0, 0, width, height);

      ctx.strokeStyle = '#2a2a2a';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(0, height - 0.5);
      ctx.lineTo(width, height - 0.5);
      ctx.stroke();

      if (!this.data.length) {
        ctx.fillStyle = '#bdbdbd';
        ctx.font = '12px ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif';
        ctx.fillText('Нет данных для графика', 10, 20);
        return;
      }

      const values = this.data
        .map((d) => Number(d.close))
        .filter((v) => Number.isFinite(v));

      if (!values.length) {
        ctx.fillStyle = '#bdbdbd';
        ctx.font = '12px ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif';
        ctx.fillText('Нет корректных значений для графика', 10, 20);
        return;
      }

      const min = Math.min(...values);
      const max = Math.max(...values);
      const range = max - min || 1;

      const leftPad = 8;
      const rightPad = 8;
      const topPad = 8;
      const bottomPad = 18;
      const plotWidth = Math.max(1, width - leftPad - rightPad);
      const plotHeight = Math.max(1, height - topPad - bottomPad);

      const step = this.data.length > 1 ? plotWidth / (this.data.length - 1) : plotWidth;

      ctx.strokeStyle = '#7c4dff';
      ctx.lineWidth = 1.5;
      ctx.beginPath();

      for (let i = 0; i < this.data.length; i++) {
        const raw = Number(this.data[i].close);
        if (!Number.isFinite(raw)) continue;
        const x = leftPad + i * step;
        const y = topPad + (1 - (raw - min) / range) * plotHeight;
        if (i === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
      }

      ctx.stroke();

      ctx.fillStyle = '#bdbdbd';
      ctx.font = '11px ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif';
      ctx.fillText(`min: ${min}`, leftPad, height - 4);
      const maxText = `max: ${max}`;
      const textWidth = ctx.measureText(maxText).width;
      ctx.fillText(maxText, width - rightPad - textWidth, height - 4);
    }
  }

  window.ChartComponent = ChartComponent;
})();