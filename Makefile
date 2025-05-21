build:
	docker-compose build

init:
	docker-compose up -d

start:
	docker-compose start

stop:
	docker-compose stop

restart:
	docker-compose restart

exec:
	docker-compose exec server_zone bash

composer-install:
	docker-compose exec server_zone composer install

composer-update:
	docker-compose exec server_zone composer update

remove:
	docker-compose down

run:
	docker-compose exec server_zone php src/RobotNavigator.php

phpunit:
	docker-compose exec server_zone vendor/bin/phpunit --testdox tests

.PHONY: coverage
coverage:
	docker-compose exec -e XDEBUG_MODE=coverage server_zone vendor/bin/phpunit --coverage-html coverage --testdox tests
