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
    // 左右の軸はそれぞれ別に積まれるため、空の軸で上下 3:1 に揃え、価格を上段・スコアを下段に置く
    const spacer = { type: 'linear', axis: 'y', min: 0, max: 1, ticks: { display: false }, grid: { display: false }, border: { display: false } };

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
                // 左側の軸は定義と逆順に積まれる
                priceSpacer: { ...spacer, position: 'left', stack: 'left', stackWeight: 1 },
                price: { position: 'left', stack: 'left', stackWeight: 3 },
                scoreSpacer: { ...spacer, position: 'right', stack: 'right', stackWeight: 3 },
                score: { position: 'right', min: 0, max: market.maxScore, stack: 'right', stackWeight: 1, ticks: { stepSize: market.maxScore }, grid: { drawOnChartArea: false } },
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
