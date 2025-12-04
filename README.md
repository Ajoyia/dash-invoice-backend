# Setup and configuring the project

-   Run `composer install`
-   Run `cp .env.example .env`
-   Run `php artisan key:generate`




Also update the redis credentials as well in the .env file

-   Run `php artisan optimize:clear`
-   Run `php artisan migrate`
-   Run `php artisan db:seed`
-   Run `php artisan serve`