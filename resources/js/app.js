import Chart from 'chart.js/auto';

const css = getComputedStyle(document.documentElement);
const color = (name) => css.getPropertyValue(`--color-${name}`).trim();
const yen = (value) => `¥${Math.round(value).toLocaleString('ja-JP')}`;

const readJson = (id) => {
    const el = document.getElementById(id);
    return el ? JSON.parse(el.textContent) : null;
};

Chart.defaults.font.family = css.getPropertyValue('--font-sans');
Chart.defaults.color = color('muted');
Chart.defaults.borderColor = color('line');

const levelColors = {
    normal: color('level-normal'),
    buy: color('level-buy'),
    strong: color('level-strong'),
};

// ダッシュボード: S&P500・200日線とスコア
const market = readJson('market-data');
if (market) {
    new Chart(document.getElementById('market-chart'), {
        data: {
            labels: market.dates,
            datasets: [
                {
                    type: 'line',
                    label: 'S&P500',
                    data: market.sp500,
                    borderColor: color('ink'),
                    borderWidth: 1.5,
                    pointRadius: 0,
                    yAxisID: 'price',
                },
                {
                    type: 'line',
                    label: '200日線',
                    data: market.ma,
                    borderColor: color('accent'),
                    borderWidth: 1.5,
                    borderDash: [4, 3],
                    pointRadius: 0,
                    yAxisID: 'price',
                },
                {
                    type: 'bar',
                    label: 'スコア',
                    data: market.scores,
                    backgroundColor: market.levels.map((level) => levelColors[level] + '99'),
                    barPercentage: 1,
                    categoryPercentage: 1,
                    yAxisID: 'score',
                },
            ],
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { ticks: { maxTicksLimit: 8 }, grid: { display: false } },
                price: { position: 'left', weight: 2, stack: 'chart', stackWeight: 3 },
                score: { position: 'left', min: 0, max: market.maxScore, stack: 'chart', stackWeight: 1, ticks: { stepSize: market.maxScore } },
            },
            plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 12 } } },
        },
    });
}

// バックテスト: スコア連動の毎月の投入額
const purchases = readJson('timing-data');
if (purchases) {
    new Chart(document.getElementById('timing-chart'), {
        type: 'bar',
        data: {
            labels: purchases.map((p) => p.date.slice(0, 7)),
            datasets: [{
                label: '投入額',
                data: purchases.map((p) => p.amount),
                backgroundColor: purchases.map((p) => levelColors[p.level]),
                barPercentage: 1,
                categoryPercentage: 0.9,
            }],
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: { ticks: { maxTicksLimit: 12 }, grid: { display: false } },
                y: { ticks: { callback: (value) => `${value / 10000}万` } },
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => `${yen(ctx.raw)}（スコア ${purchases[ctx.dataIndex].score}）` } },
            },
        },
    });
}
