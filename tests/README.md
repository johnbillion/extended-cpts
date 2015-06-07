[![Build Status](https://travis-ci.org/johnbillion/extended-cpts.svg?branch=master)](https://travis-ci.org/johnbillion/extended-cpts)
[![Coverage Status](https://coveralls.io/repos/johnbillion/extended-cpts/badge.svg)](https://coveralls.io/r/johnbillion/extended-cpts)

# Extended CPTs Unit Tests

## Setting up

1. Clone this git repository on your local machine.
2. Install [Composer](https://getcomposer.org/) if you don't already have it.
3. Run `composer install` to fetch all the dependencies.
4. Install the test environment by executing:

        ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass>

  Ensure you use a separate test database (eg. `wp_tests`) because, just like the WordPress test suite, the database will be wiped clean with every test run.

## Running the tests

To run the unit tests, just execute:

    ./vendor/bin/phpunit
