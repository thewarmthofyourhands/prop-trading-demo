## Проверка статусов и запуск воркеров
```
cd public && php index.php start
php index.php start
php server.php start
php server.php start -d
php server.php connections
php server.php stop
php server.php stop -g
php server.php restart
php server.php reload
php server.php reload -g
```

````
docker compose logs api
docker compose exec api composer i
docker compose exec api php ./bin/console list
docker compose exec api php ./bin/console db.migrations.migrate
docker compose exec api php ./bin/console app.dto.creator --sourcePath=/code/config/packages/dto/

docker compose restart api 
````