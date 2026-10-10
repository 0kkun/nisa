# 概要

- NISA 成長投資枠を「いつ・いくら」入れるかの目安を出すアプリ
- S&P500・VIX・ドル円の日足から買い場スコアを出し、今月の推奨投入額を表示する

## 構成

| カテゴリ | 技術 | バージョン |
|---|---|---|
| 言語 | PHP | 8.5 |
| フレームワーク | Laravel | 13.35 |
| データベース | MySQL | 9.7 (LTS) |
| Webサーバー | Nginx | 1.31 |
| Node.js | Node.js | 26.x |
| フロント | Blade / Tailwind CSS 4 / Vite 8 / Chart.js 4 |
| テスト | PHPUnit | 13 |

## セットアップ

```bash
make init          # .env 作成・ビルド・composer/npm install・APP_KEY・migrate・市場データ取得
open http://localhost:8080
```

MySQL コンテナは `.env` の `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` / `DB_ROOT_PASSWORD` を使う。

| ポート | 用途 |
|---|---|
| 8080 | アプリ |
| 3307 | MySQL |
| 5174 | Vite (HMR) |

| DB | 用途 |
|---|---|
| `nisa_db` | 開発用 |
| `nisa_testing` | テスト用。DB データ初期化時に `.docker/db/init/01-create-testing-db.sh` で自動作成。既存環境は `make create-test-db` |

## 画面

| パス | 内容 |
|---|---|
| `/` | 買い場スコア・各指標・今月の推奨投入額・チャート |
| `/purchases` | 成長投資枠の購入記録と、年間 240万 / 生涯 1,200万 の残り |
| `/backtest` | 一括・毎月均等・スコア連動の3通りを過去データで比較 |

## 買い場スコア

閾値と倍率は `config/nisa.php` で変更できる。

| 指標 | 加点 |
|---|---|
| 52週高値からの下落率 | -5%: 1 / -10%: 2 / -15%: 3 / -20%: 4 |
| 200日線からの乖離 | 0%以下: 1 / -5%: 2 / -10%: 3 |
| VIX | 20以上: 1 / 25以上: 2 / 30以上: 3 |
| ドル円の200日線乖離 | 0%以下（円高）: 1 |

| 合計 | レベル | 倍率 |
|---|---|---|
| 6〜 | 強い買い場 | 3.0 |
| 3〜5 | 買い増し | 2.0 |
| 0〜2 | 通常 | 1.0 |

推奨投入額 = 投資可能額 ÷ 今年の残り月数 × 倍率（1万円単位で切り捨て、12月は残り全額）

## 市場データ

- Yahoo Finance の chart API から取得（`^GSPC` / `^VIX` / `JPY=X`）
- `make fetch` で手動取得。scheduler コンテナが毎日 7:00 に差分を取得する

## アーキテクチャ

```
Controller / Command
    ↓
ServiceInterface（契約）
    ↓
Service（ユースケース・DIで注入）
    ↓                ↘
RepositoryInterface    Domain（純粋な計算: 指標・スコア・投入額）
    ↓
Repository → Model
```

### ディレクトリ構成

```
app/
├── Console/Commands/      # market:fetch
├── Domain/
│   ├── Timing/            # MarketSnapshotBuilder, TimingScorer, InvestmentPlanner
│   ├── Quota/             # QuotaSummary
│   └── Backtest/          # 結果の値オブジェクト
├── Enums/MarketSymbol.php
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Services/
│   ├── MarketData/        # YahooFinanceClient, MarketDataService
│   ├── Quota/             # GrowthQuotaService
│   ├── Timing/            # TimingService
│   └── Backtest/          # BacktestService
├── Repositories/
└── Providers/
    ├── RepositoryServiceProvider.php
    └── ServiceServiceProvider.php   # Service と設定値を持つ Domain の bind
```

### 各層の責務

| 層 | 責務 |
|---|---|
| Controller / Command | リクエスト受付、ServiceInterface の呼び出し、レスポンス返却 |
| ServiceInterface | ユースケースの契約。Controller はこれにだけ依存する |
| Service | ユースケースの実装（Repository と Domain の組み合わせ） |
| Domain | DB や HTTP に依存しない計算。値オブジェクト |
| RepositoryInterface / Repository | データアクセス |
| Model | テーブル定義 |

## テスト方針

TDD（テストを先に書き、Red → Green → Refactor）で開発する。

| 層 | テスト種別 | 方法 |
|---|---|---|
| Controller / Command | Feature Test | ServiceInterface を `createMock()` で差し替え |
| FormRequest | Unit Test | `rules()` を Validator で検証 |
| Service | Unit Test | Repository / Client を `createMock()`、Domain は実物 |
| Domain | Unit Test | 純粋な PHPUnit |
| Repository | Unit Test | MySQL の `nisa_testing` + RefreshDatabase |
| YahooFinanceClient | Unit Test | `Http::fake()` |

```bash
make test
```
