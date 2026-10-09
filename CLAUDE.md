# nisa

README.md の「アーキテクチャ」「テスト方針」に従う。

- Controller / Command は ServiceInterface にだけ依存させる。新しい Service には必ず Interface を作り `ServiceServiceProvider` で bind する
- DB・HTTP に依存しない計算は `app/Domain` に置く
- TDD で進める。テストを先に書き、失敗を確認してから実装する
- DB は MySQL のみ。SQLite は使わない。テストは `nisa_testing`、開発は `nisa_db`
- コマンドは Docker 内で実行する（`make test` / `make pint` / `docker compose exec app php artisan ...`）
- 買い場スコアの閾値・倍率は `config/nisa.php`。変えたら `/backtest` で比較する
