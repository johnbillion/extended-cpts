[![Build Status](https://img.shields.io/github/workflow/status/johnbillion/extended-cpts/Test/develop?style=flat-square)](https://github.com/johnbillion/extended-cpts/actions)

# Contributing to Extended CPTs

Code contributions and bug reports are very welcome. These should be submitted through [the GitHub repository](https://github.com/johnbillion/extended-cpts). Development happens in the `develop` branch, and any pull requests should be made against that branch please.

* [Setting up Locally](#setting-up-locally)
* [Running the Tests](#running-the-tests)

## Setting up Locally

If you want to contribute to Extended CPTs, you should install the developer dependencies in order to run the tests.

### Prerequisites

* [Composer](https://getcomposer.org/)

### Setup

1. Install the PHP dependencies:

       composer install

2. Check the MySQL database credentials in the `tests/.env` file and amend them if necessary.

## Running the Tests

To run the whole test suite which includes PHPUnit and linting:

	composer test

To run just the PHPUnit tests:

	composer test:ut

To run just the code sniffer:

	composer test:cs
