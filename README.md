
## Codeception тесты

##### Поднять селениум сервер в докере:
```bash
sudo docker run --name selenium-server -p 4444:4444 -v /dev/shm:/dev/shm -d -it --rm --network host  selenium/standalone-chrome
```

##### Запустить тесты:
```bash
./bin/test-runner.php run [options]
```
``
-e, --env=ENV          Окружение на котором будут ходить тесты (local, test, staging, mtest, production)  
-b, --branch[=BRANCH]  Ветка на которой будут ходить тесты  
-g, --group[=GROUP]    Группа тестов. Например тесты только по созданию ордера.  

Пример:
```bash
./bin/test-runner.php run -e local -g [tests/acceptance/SiteCom/SomeCest]
```
Запустит один тест на окружение .local


##### Сделать diff тестов и кейсов:
```bash
./bin/test-codebase.php run -b develop
```

##### Поднять консьюмер тест раннера:
```bash
./bin/consumer.php run -c TestRunner
```

### Конфиг supervisor для консьюмеров
etc/supervisor/conf.d/consume.conf

[program:consume]  
directory=/var/www/tests  
command=/usr/bin/php bin/consumer.php run -c TestRunner  
autostart=true  
autorestart=true  
stopsignal=KILL  
numprocs=1  
stderr_logfile=/var/log/consume.err.log  
stdout_logfile=/var/log/consume.out.log  

etc/supervisor/conf.d/remote-executing.conf
[program:remoteExecuting]
directory=/var/www/tests
command=/usr/bin/php bin/consumer.php run -c RemoteExecuting
autostart=true
autorestart=true
stopsignal=KILL
numprocs=1
stderr_logfile=/var/log/consume.err.log
stdout_logfile=/var/log/consume.out.log

## RabbitMQ

Для тестировани в lib/RabbitMQ/configs/config.local.ini нужно заменить  

