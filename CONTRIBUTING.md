# Contributing

Found a bug or something's broken? Easy raise an issue or even better fix it and send pull request. Any contributions are welcome.

 - Coding standard is PSR 1, PSR 2 and PSR 12. There are some other useful rules from Slevomat coding standards. They are enforced with phpcs. See `ruleset.xml`
 - The project aims to follow most [object calisthenics](https://www.slideshare.net/guilhermeblanco/object-calisthenics-applied-to-php)
 - Any contribution must provide tests for newly introduced conditions
 - Any un-confirmed issue needs a failing test case before being accepted
 - Pull requests must be sent from a new hotfix/feature branch, not from `master`
 - All GitHub workflow checks must pass before PR can be accepted

## Building

You can run specific `make` or `composer` targets to help your development process.

### With Docker

Initially you need to have Docker installed on your system. Afterwards run `make deps` in order to download dependencies.

- `make deps` - downloads dependencies
- `make test` - run tests and static-analysis
- `make fmt-check` - check code style
- `make fmt` - automatically fix your code style

### Without Docker

You need to have PHP and Composer installed globally on your system. Afterwards run `composer update` in order to download dependencies.

- `composer update` - downloads dependencies
- `composer test` - run tests and static-analysis
- `composer phpstan` - run static-analysis
- `composer phpunit` - run tests
- `composer phpcs` - check code style
- `composer phpcfb` - automatically fix your code style

