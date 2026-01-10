#!/bin/bash
echo "安裝套件與初始化資料庫..."
docker-compose run --rm app //usr/bin/composer install

echo "啟動環境中..."
docker-compose up -d db redis web app

docker-compose exec app php artisan storage:link --force
docker-compose exec app cp .env.example .env
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan scribe:generate

docker-compose up -d
docker-compose exec app chmod -R 775 storage

echo "專案已就緒！"
echo "API 文檔地址: http://localhost:8000/docs"
echo "API 測試: http://localhost:8000/api"
