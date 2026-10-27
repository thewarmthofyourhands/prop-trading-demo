### Local START
````
//запускать из текущей папки
docker compose up -d
//дождаться установки composer и поднятия сервера
docker compose logs api
docker compose exec api php ./bin/console db.migrations.migrate
docker compose exec api php ./bin/console app.seed.default

http://localhost:8082/ -- вход
````

