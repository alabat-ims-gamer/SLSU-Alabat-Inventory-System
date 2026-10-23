/**
 * SLSU-Alabat IMS — Centralized Chart Factory Engine
 * Reusable, high-performance Chart.js wrappers adhering to the Stripi-inspired design system.
 * Enforces unified typography, HSL/hex palettes, 60fps animations, responsive scaling, and automatic memory cleanup.
 */
(function (root, factory) {
  if (typeof define === 'function' && define.amd) {
    define([], factory);
  } else if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.ChartFactory = factory();
  }
}(typeof self !== 'undefined' ? self : this, function () {
  'use strict';

  // Design Tokens & Harmonious Palettes
  const TOKENS = {
    fontFamily: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
    colors: {
      primary: '#533afd',
      primaryDeep: '#4434d4',
      primarySoft: 'rgba(83, 58, 253, 0.14)',
      primaryHover: 'rgba(83, 58, 253, 0.22)',
      
      success: '#059669',
      successSoft: 'rgba(5, 150, 105, 0.14)',
      
      warning: '#d97706',
      warningSoft: 'rgba(217, 119, 6, 0.14)',
      
      danger: '#dc2626',
      dangerSoft: 'rgba(220, 38, 38, 0.14)',
      
      info: '#0284c7',
      infoSoft: 'rgba(2, 132, 199, 0.14)',
      
      purple: '#8b5cf6',
      purpleSoft: 'rgba(139, 92, 246, 0.14)',
      
      slate: '#64748d',
      slateSoft: 'rgba(100, 116, 139, 0.14)',
      
      hairline: '#e3e8ee',
      gridLines: '#f1f5f9',
      ink: '#0d253d',
      inkMute: '#64748d',
    },
    paletteCycle: [
      '#533afd', // Indigo
      '#059669', // Emerald
      '#d97706', // Amber
      '#0284c7', // Sky
      '#8b5cf6', // Violet
      '#ea2261', // Ruby
      '#64748d', // Slate
    ],
  };

  // Internal instance tracker for automatic memory and canvas cleanup
  const instances = new Map();

  /**
   * Helper: Get canvas context safely or return null
   */
  function getCanvas(target) {
    if (typeof target === 'string') {
      return document.getElementById(target);
    }
    return target;
  }

  /**
   * Safe Destroy Instance
   */
  function destroy(target) {
    const el = getCanvas(target);
    if (!el) return;
    const id = el.id || el;
    if (instances.has(id)) {
      try {
        instances.get(id).destroy();
      } catch (e) {
        console.warn('ChartFactory: error during instance cleanup', e);
      }
      instances.delete(id);
    }
  }

  /**
   * Format tooltips nicely with design tokens
   */
  function getDefaultTooltipConfig() {
    return {
      backgroundColor: '#0d253d',
      titleFont: { family: TOKENS.fontFamily, size: 12, weight: '500' },
      bodyFont: { family: TOKENS.fontFamily, size: 12, weight: '300' },
      padding: { top: 8, bottom: 8, left: 12, right: 12 },
      cornerRadius: 6,
      displayColors: true,
      boxPadding: 4,
      usePointStyle: true,
    };
  }

  return {
    tokens: TOKENS,

    /**
     * Get chart instance by canvas element ID
     */
    get(target) {
      const el = getCanvas(target);
      return el ? instances.get(el.id || el) : null;
    },

    /**
     * Non-destructive live dataset updater
     * Mutates chart.data in-place and calls chart.update() for 60fps animations without canvas memory churn
     */
    updateDataset(target, mutatorFn) {
      const chart = this.get(target);
      if (!chart || typeof mutatorFn !== 'function') return false;
      try {
        mutatorFn(chart.data, chart);
        chart.update();
        return true;
      } catch (err) {
        console.warn('ChartFactory: error during live update', err);
        return false;
      }
    },

    /**
     * Destroy chart instance
     */
    destroy: destroy,

    /**
     * 1. Doughnut Chart (Operational Status / Conditions / Severity)
     */
    createDoughnut(target, options = {}) {
      const el = getCanvas(target);
      if (!el || typeof Chart === 'undefined') return null;
      destroy(el);

      const labels = options.labels || [];
      const data = options.data || [];
      const colors = options.colors || [
        TOKENS.colors.success,
        TOKENS.colors.primary,
        TOKENS.colors.warning,
        TOKENS.colors.danger,
        TOKENS.colors.slate,
      ];
      const cutout = options.cutout || '70%';
      const legendPosition = options.legendPosition || (window.innerWidth < 640 ? 'bottom' : 'right');

      const chart = new Chart(el, {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: colors,
            borderWidth: 0,
            hoverOffset: 6,
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: cutout,
          animation: {
            duration: 750,
            easing: 'easeOutQuart',
          },
          plugins: {
            legend: {
              display: options.showLegend !== false,
              position: legendPosition,
              labels: {
                font: { family: TOKENS.fontFamily, size: 11, weight: '400' },
                color: TOKENS.colors.inkMute,
                padding: 10,
                usePointStyle: true,
                pointStyleWidth: 8,
              }
            },
            tooltip: getDefaultTooltipConfig(),
          }
        }
      });

      instances.set(el.id || el, chart);
      return chart;
    },

    /**
     * 2. Bar Chart (Vertical, Stacked, or Multiple Datasets with Solid Colors)
     */
    createBar(target, options = {}) {
      const el = getCanvas(target);
      if (!el || typeof Chart === 'undefined') return null;
      destroy(el);

      const labels = options.labels || [];
      let datasets = options.datasets || [];

      // If single data array provided directly
      if (options.data && datasets.length === 0) {
        const solidBg = options.backgroundColor || TOKENS.colors.primary;
        datasets = [{
          label: options.label || 'Volume',
          data: options.data,
          backgroundColor: solidBg,
          borderColor: options.borderColor || solidBg,
          borderWidth: 0,
          borderRadius: 6,
          maxBarThickness: options.maxBarThickness || 38,
          barPercentage: options.barPercentage || 0.65,
          hoverBackgroundColor: options.hoverBackgroundColor || TOKENS.colors.primaryDeep,
        }];
      } else {
        datasets = datasets.map(function(ds, idx) {
          const defaultSolid = idx === 0 ? TOKENS.colors.primary : (idx === 1 ? TOKENS.colors.success : TOKENS.paletteCycle[idx % TOKENS.paletteCycle.length]);
          return Object.assign({
            borderWidth: 0,
            borderRadius: 6,
            maxBarThickness: options.maxBarThickness || 36,
            barPercentage: options.barPercentage || 0.65,
            backgroundColor: defaultSolid,
            hoverBackgroundColor: defaultSolid,
          }, ds);
        });
      }

      const chart = new Chart(el, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: datasets,
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            duration: 650,
            easing: 'easeOutQuart',
          },
          plugins: {
            legend: {
              display: datasets.length > 1 || options.showLegend === true,
              position: 'top',
              align: 'end',
              labels: {
                font: { family: TOKENS.fontFamily, size: 11, weight: '400' },
                color: TOKENS.colors.inkMute,
                boxWidth: 8,
                boxHeight: 8,
                usePointStyle: true,
              }
            },
            tooltip: getDefaultTooltipConfig(),
          },
          scales: {
            x: {
              stacked: options.stacked || false,
              grid: { display: false },
              ticks: {
                font: { family: TOKENS.fontFamily, size: 11 },
                color: TOKENS.colors.inkMute,
              }
            },
            y: {
              stacked: options.stacked || false,
              grid: { color: TOKENS.colors.gridLines },
              ticks: {
                font: { family: TOKENS.fontFamily, size: 11 },
                color: TOKENS.colors.inkMute,
                precision: 0,
                stepSize: options.stepSize || undefined,
              },
              beginAtZero: true,
            }
          }
        }
      });

      instances.set(el.id || el, chart);
      return chart;
    },

    /**
     * 3. Horizontal Bar Chart (Category / Location Distribution with Solid Colors)
     */
    createHorizontalBar(target, options = {}) {
      const el = getCanvas(target);
      if (!el || typeof Chart === 'undefined') return null;
      destroy(el);

      const labels = options.labels || [];
      const data = options.data || [];
      const solidColor = options.color || options.backgroundColor || TOKENS.colors.primary;

      const chart = new Chart(el, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: options.label || 'Count',
            data: data,
            backgroundColor: solidColor,
            borderColor: solidColor,
            borderWidth: 0,
            borderRadius: { topRight: 6, bottomRight: 6, topLeft: 0, bottomLeft: 0 },
            maxBarThickness: options.maxBarThickness || 22,
            barPercentage: options.barPercentage || 0.7,
            hoverBackgroundColor: options.hoverBg || options.hoverBackgroundColor || TOKENS.colors.primaryDeep,
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            duration: 650,
            easing: 'easeOutQuart',
          },
          plugins: {
            legend: { display: false },
            tooltip: getDefaultTooltipConfig(),
          },
          scales: {
            x: {
              grid: { color: TOKENS.colors.gridLines },
              ticks: {
                font: { family: TOKENS.fontFamily, size: 11 },
                color: TOKENS.colors.inkMute,
                precision: 0,
              },
              beginAtZero: true,
            },
            y: {
              grid: { display: false },
              ticks: {
                font: { family: TOKENS.fontFamily, size: 11 },
                color: TOKENS.colors.ink,
              }
            }
          }
        }
      });

      instances.set(el.id || el, chart);
      return chart;
    },

    /**
     * 4. Line / Area Chart (Time-Series Velocity: Borrowing vs Returns)
     */
    createLineArea(target, options = {}) {
      const el = getCanvas(target);
      if (!el || typeof Chart === 'undefined') return null;
      destroy(el);

      const labels = options.labels || [];
      let datasets = options.datasets || [];

      // Normalize default line styling if not provided
      datasets = datasets.map(function (ds, idx) {
        const defaultColor = idx === 0 ? TOKENS.colors.primary : TOKENS.colors.success;
        const defaultFill = idx === 0 ? 'rgba(83, 58, 253, 0.08)' : 'rgba(5, 150, 105, 0.08)';
        return Object.assign({
          fill: options.fill !== false,
          tension: 0.35,
          borderWidth: 2,
          pointRadius: 2,
          pointHoverRadius: 5,
          borderColor: defaultColor,
          backgroundColor: defaultFill,
        }, ds);
      });

      const chart = new Chart(el, {
        type: 'line',
        data: {
          labels: labels,
          datasets: datasets,
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            mode: 'index',
            intersect: false,
          },
          animation: {
            duration: 700,
            easing: 'easeOutQuart',
          },
          plugins: {
            legend: {
              display: datasets.length > 1 || options.showLegend === true,
              position: 'top',
              align: 'end',
              labels: {
                font: { family: TOKENS.fontFamily, size: 11, weight: '400' },
                color: TOKENS.colors.inkMute,
                boxWidth: 8,
                boxHeight: 8,
                usePointStyle: true,
              }
            },
            tooltip: getDefaultTooltipConfig(),
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: {
                font: { family: TOKENS.fontFamily, size: 11 },
                color: TOKENS.colors.inkMute,
                maxRotation: 0,
                autoSkip: true,
                maxTicksLimit: 12,
              }
            },
            y: {
              grid: { color: TOKENS.colors.gridLines },
              ticks: {
                font: { family: TOKENS.fontFamily, size: 11 },
                color: TOKENS.colors.inkMute,
                precision: 0,
              },
              beginAtZero: true,
            }
          }
        }
      });

      instances.set(el.id || el, chart);
      return chart;
    },

    /**
     * 5. Smooth Live Update (Transition without rebuilding DOM)
     */
    update(target, newLabels, newDatasets) {
      const chart = this.get(target);
      if (!chart) return;

      if (newLabels) {
        chart.data.labels = newLabels;
      }

      if (newDatasets && Array.isArray(newDatasets)) {
        if (chart.data.datasets.length === newDatasets.length) {
          chart.data.datasets.forEach(function (ds, i) {
            if (Array.isArray(newDatasets[i])) {
              ds.data = newDatasets[i];
            } else if (newDatasets[i] && newDatasets[i].data) {
              Object.assign(ds, newDatasets[i]);
            }
          });
        } else {
          chart.data.datasets = newDatasets;
        }
      }

      chart.update('active');
    }
  };
}));
