# Forma

This is a PHP-based form processing library named "Forma". It provides a set of functions to handle form submissions, validate data, handle file uploads, and send email notifications using PHPMailer. The project is designed to be simple and easily integrated into existing PHP sites.

## Setup

Dependencies are managed with Composer. To install the required dependencies (like PHPMailer), run:

```bash
composer install
```

## Code Style

The project uses `php-cs-fixer` to maintain a consistent code style. Before committing any changes, please format your code by running:

```bash
php-cs-fixer fix
```

## Architecture

The main logic is encapsulated in `forma.php`. This file contains all the helper functions for form processing.

The project uses a modular approach for different forms. Each form has its own directory (e.g., `contact/`, `callme/`) which contains:
- `handler.php`: The server-side script that processes the form submission. It includes `forma.php` and uses its functions.
- `email.php`: The email template for the notifications sent by the handler.

Configuration is stored in `config.php`. This file is not version-controlled and must be created manually. It should contain sensitive information like email credentials and API keys.

The frontend is handled by `js/forma.js`, which defines a custom HTML element `<forma-form>` for AJAX-based form submission.

## Testing

JavaScript tests are run with Bun. To execute the test suite, run:

```bash
bun test
```

For the PHP code, there is no formal testing suite (like PHPUnit) set up. Testing is done manually.

The `index.php` file serves as a demonstration page. It contains a sample form that submits to `test/handler.php`. You can use this page to test the form submission functionality.

When making changes, please test them by updating `index.php` and `test/handler.php` to exercise the modified code.

## Documentation

For documentation use Russian language.
