# Shlink importer

Collection of tools to import links from different sources and map them to a shlink-compliant format.

[![Build Status](https://img.shields.io/github/workflow/status/shlinkio/shlink-importer/Continuous%20integration/main?logo=github&style=flat-square)](https://github.com/shlinkio/shlink-importer/actions?query=workflow%3A%22Continuous+integration%22)
[![Code Coverage](https://img.shields.io/codecov/c/gh/shlinkio/shlink-importer/main?style=flat-square)](https://app.codecov.io/gh/shlinkio/shlink-importer)
[![Infection MSI](https://img.shields.io/endpoint?style=flat-square&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fshlinkio%2Fshlink-importer%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/shlinkio/shlink-importer/main)
[![Latest Stable Version](https://img.shields.io/github/release/shlinkio/shlink-importer.svg?style=flat-square)](https://packagist.org/packages/shlinkio/shlink-importer)
[![License](https://img.shields.io/github/license/shlinkio/shlink-importer.svg?style=flat-square)](https://github.com/shlinkio/shlink-importer/blob/main/LICENSE)
[![Paypal donate](https://img.shields.io/badge/Donate-paypal-blue.svg?style=flat-square&logo=paypal&colorA=aaaaaa)](https://slnk.to/donate)

## Installation

This module can be installed using composer:

    composer require shlinkio/shlink-importer

## Supported import sources

#### Bit.ly

It imports using the API v4. The only required param is an [access token](https://bitly.is/accesstoken).

Only the URLs will be imported. Visits/clicks won't be imported yet (See https://github.com/shlinkio/shlink-importer/issues/20).

#### YOURLS

It imports using YOURLS API. However, since it has some missing capabilities, it requires a [dedicated plugin](https://slnk.to/yourls-import) to be installed in YOURLS.

The plugin covers the missing actions in the API, which allow Shlink to list the URLs and all their visits.

It will import short URLs and all their visits, but any information that YOURLS does not track (like the geolocation) cannot be obtained.

#### Kutt.it

It imports using Kutt API.

It will import short URLs but not their visits, as Kutt.it does not expose individual visits but aggregate information, which is coupled with its UI and uses relative times.

#### Shlink

It imports from another Shlink instance using the API v2. Useful if you want to migrate to a different host or change the database engine.

You will have to provide the instance's base URL and a valid API key.

It will import short URLs and all their visits. However, it won't be possible to recalculate the location for those visits, so make sure to calculate the locations on the original instance first, by running `bin/cli visit:locate --retry`.

#### Standard CSV

It parses a CSV file with the `Long URL` and `Short code` columns. It can optionally contain `Domain`, `Title` and `Tags`, being the latter a pipe-separated list of items (`foo|bar|baz`).

Column names can have spaces and have any combination of upper and lowercase.

This method does not allow importing visits due to its one-dimensional nature.

## Usage

The module register the `short-url:import` command, which can be used to import links from different sources.

This command requires the source from which to import to be provided:

    `bin/cli short-url:import bitly`

The command will ask you some questions about how to import from this source, and then, once the data is there, it will invoke the `Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface` service.

## Requirements

This package expects some services to be registered as dependencies, as they need to be used by some provided tools.

* `Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface`: It has to resolve an object implementing the interface.
* `Psr\Http\Client\ClientInterface`: Required to be able to import from Bit.ly, YOURLS, Kutt.it or another Shlink instance.
* `Psr\Http\Message\RequestFactoryInterface`: Required to be able to import from Bit.ly, YOURLS, Kutt.it or another Shlink instance.
