# Serious Request Crawler

A simple HTML crawler made for Serius Request 2021.
The tool downlolads all track pages for a given timespan, and converts those to
`Track` models which you can use in your application.

Tracks can be exported as JSON (for later seeding) and as an ODS file.

## License

This software is licensed under the [GNU Affero General Public License](./LICENSE.md).

## Installation

This application requires PHP 8.0+ with curl and dom, composer and sqlite or mysql drivers.

1. `cp .env.example .env`
1. `composer install`
1. `php artisan key:generate`
1. `php artisan migrate --seed`

## Running a full export

1. `php artisan app:determine-pages` to determine the min and max page numbers.
1. `php artisan app:download-tracks` to download all pages and create Track models.
1. `php artisan app:report` to create the ODS file.
