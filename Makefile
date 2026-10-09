# ---------- 共通変数 ----------
PHP_CONTAINER=app
DB_CONTAINER=db


# ---------- Commands ----------
.PHONY: help init up down build build_c app db create-test-db test pint fetch npm-dev npm-build


# =============================
# Help
# =============================
help: ## コマンド一覧を表示
	@echo ""
	@echo "📘 Make Commands"
	@echo "----------------------------------------"
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
	awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-30s\033[0m %s\n", $$1, $$2}'
	@echo ""


# =============================
# 初期構築
# =============================
init: ## 初回セットアップ（.env作成・依存インストール・マイグレーション・データ取得）
	test -f .env || cp .env.example .env
	docker compose up -d --build
	docker compose exec $(PHP_CONTAINER) composer install
	docker compose exec $(PHP_CONTAINER) php artisan key:generate
	docker compose exec $(PHP_CONTAINER) php artisan migrate --force
	$(MAKE) create-test-db
	docker compose exec $(PHP_CONTAINER) npm install
	docker compose exec $(PHP_CONTAINER) npm run build
	docker compose exec $(PHP_CONTAINER) php artisan market:fetch


# =============================
# コンテナ管理
# =============================
up: ## コンテナを起動
	docker compose up -d
down: ## コンテナを停止・削除
	docker compose down --remove-orphans
build: ## イメージをビルド
	docker compose build
build_c: ## キャッシュ無効化で再ビルド
	docker compose build --no-cache --force-rm
app: ## PHPコンテナにシェルで入る
	docker compose exec $(PHP_CONTAINER) sh


# =============================
# MySQL
# =============================
db: ## MySQLコンソールを起動
	docker compose exec $(DB_CONTAINER) sh -c 'mysql -u"$$MYSQL_USER" -p"$$MYSQL_PASSWORD" "$$MYSQL_DATABASE"'

create-test-db: ## テスト用DB（nisa_testing）を作成
	docker compose exec $(DB_CONTAINER) /docker-entrypoint-initdb.d/01-create-testing-db.sh


# =============================
# Laravel
# =============================
test: ## テストを実行
	docker compose exec $(PHP_CONTAINER) php artisan test
pint: ## コード整形
	docker compose exec $(PHP_CONTAINER) ./vendor/bin/pint
fetch: ## 市場データ（S&P500・VIX・ドル円）を取得
	docker compose exec $(PHP_CONTAINER) php artisan market:fetch


# =============================
# Front
# =============================
npm-dev: ## npm run dev
	docker compose exec $(PHP_CONTAINER) npm run dev
npm-build: ## npm run build
	docker compose exec $(PHP_CONTAINER) npm run build
