#!/bin/bash
# テスト用 DB を作成する。DB データ初期化時に自動実行され、既存環境では make create-test-db で実行する
# DB 名は phpunit.xml の DB_DATABASE と合わせる
set -eu

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS nisa_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
GRANT ALL PRIVILEGES ON nisa_testing.* TO '${MYSQL_USER}'@'%';
SQL
