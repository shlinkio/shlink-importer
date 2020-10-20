# Shlink importer

Collection of tools to import links from different sources and map them to a shlink-compliant format.

## Installation

This module can be installed using composer:

    composer require shlinkio/shlink-importer

## Supported import sources

* Bit.ly

## Usage

The module register the `short-urls:import` command, which can be used to import links from different sources.

This command requires the source from which to import to be provided:

    `bin/cli short-urls:import bitly`

The command will ask you some questions about how to import from this source, and then, once the data is there, it will invoke the `Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface` service.

## Requirements

This package expects some services to be registered as dependencies, as they need to be used by some of the tools.

* `Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface`: It has to resolve an object implementing the interface.
* `Psr\Http\Client\ClientInterface`: Required to be able to import from Bit.ly
* `Psr\Http\Message\RequestFactoryInterface`: Required to be able to import from Bit.ly
