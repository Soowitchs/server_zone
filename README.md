# PHP Robot Navigation Project

This project implements robot navigation and escape logic in PHP, with a modern workflow for both local and containerized development. It includes automated tests (using PHPUnit, php-mock, and Guzzle for HTTP mocking), code coverage, and a Makefile for streamlined commands.

## Features
- Robot navigation and escape logic (see `src/RobotNavigator.php`)
- Local and Docker/Docker Compose development
- Automated tests with PHPUnit, php-mock, and Guzzle
- Code coverage with Xdebug
- Makefile for all common tasks
- Live code updates in Docker

## Quick Start

### Local (Host) Development
1. Ensure you have PHP 8.2+, Composer, and (optionally) Docker installed.
2. Install dependencies:
    
        composer install
3. Run the app:
    
        php src/RobotNavigator.php

### Docker Compose Workflow (Recommended)
1. Build and start the container:
    
        make build
        make start
2. Run the app inside the container:
    
        make run
3. Run tests:
    
        make test
4. Generate code coverage:
    
        make coverage

All code changes are live in the container (thanks to volume mounting).

## Makefile Commands
- `make build`      – Build the Docker image
- `make start`      – Start the Docker Compose service
- `make stop`       – Stop the service
- `make restart`    – Restart the service
- `make run`        – Run the app in the container
- `make test`       – Run PHPUnit tests in the container
- `make coverage`   – Generate code coverage report (HTML in `coverage/`)
- `make composer`   – Run Composer in the container
- `make remove`     – Remove the Docker Compose service

## Testing & Code Coverage
- Tests are in `tests/RobotNavigatorTest.php` and use PHPUnit, php-mock, and Guzzle for fast, isolated tests.
- Run tests: `make test`
- Generate coverage: `make coverage` (see `coverage/index.html`)

## Troubleshooting
- **Container name conflict:** If you see errors about container names, run `make remove` before `make start`.
- **Code coverage driver:** Ensure `XDEBUG_MODE=coverage` is set (handled in Dockerfile/Makefile).
- **Live code updates:** Code is mounted into the container; changes are reflected instantly.

## Project Structure
- `src/RobotNavigator.php` – Main robot logic (class-based)
- `tests/RobotNavigatorTest.php` – PHPUnit tests (with php-mock and Guzzle)
- `images/Dockerfile`      – PHP 8.2 CLI, Composer, PHPUnit, Xdebug
- `docker-compose.yml`     – Orchestrates PHP service
- `Makefile`               – Workflow automation
- `composer.json`          – Dependencies
- `phpunit.xml`            – PHPUnit config (coverage filter)

## Credits
- Uses [Guzzle](https://docs.guzzlephp.org/) for HTTP mocking in tests
- Uses [php-mock/php-mock](https://github.com/php-mock/php-mock) for mocking PHP built-ins (like curl)
- Uses [PHPUnit](https://phpunit.de/) for testing

---
For more details, see comments in each file and the Makefile for all available commands.
