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

### Session Handling in iFrames with Laravel

In certain scenarios, your Laravel application may be running within an iFrame. Some browsers have security measures in place that block cookies within iFrames, which can disrupt session handling as session IDs are typically passed through cookies. 

To ensure seamless session handling across pages, it's important to pass the session ID within the URL itself. We've prepared the necessary functions to do this without disrupting Laravel's standard operations.

Instead of using Laravel's standard url or route functions to generate URLs, use the following custom functions:
    
```php
// This function works similarly to Laravel's url function, but it also appends the session ID to the URL as a query parameter.
$url = app24_url('/ui', ['param' => 'value']);
// This function works similarly to Laravel's route function, but it also appends the session ID to the URL as a query parameter.
$route = app24_route('route.name', ['param' => 'value']);
```
In these examples, the resulting URL will include the session ID as a query parameter, ensuring that the session is maintained even when cookies are blocked.