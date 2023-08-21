# Contributing to Extended CPTs

Bug reports, code contributions, and general feedback are very welcome. These should be submitted through [the GitHub repository](https://github.com/johnbillion/extended-cpts). Development happens in the `develop` branch, and any pull requests should be made against that branch please.

## Reporting Security Issues

If you discover a security issue in Extended CPTs, please report it to [the security program on HackerOne](https://hackerone.com/johnblackbourn). Do not report security issues on GitHub. Thank you.

## Setting up Locally

If you want to contribute to Extended CPTs, you should install the developer dependencies in order to run the tests.

### Prerequisites

* [Composer](https://getcomposer.org/)
* [Docker Desktop](https://www.docker.com/desktop) to run the tests

### Setup

Install the PHP dependencies:

	composer install

## Running the Tests

The test suite includes integration tests which run in a Docker container. Ensure Docker Desktop is running, then start the containers with:

	composer test:start

To run the whole test suite which includes integration tests, linting, and static analysis:

	composer test

To run tests individually, run one of:

	composer test:integration
	composer test:phpcs
	composer test:phpstan

To stop the Docker containers:

	composer test:stop
