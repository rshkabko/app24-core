# App24 Core SDK

This is a Laravel package for creating applications for https://app24.flamix.info/

## Installation

```bash
composer require flamix/app24-core
php artisan app24:install
php artisan migrate
```

Add the following to your .env file:

```dotenv
APP_NAME=company.app24-name
APP24_ID=app.secret.code
APP24_SECRET=super_secret_code
APP24_SCOPE=crm,user,task
```

Add to CRON or in Scheduler:

```bash
# Every DAY
php artisan app24:refresh-token
```

## Usage

Will be added soon!