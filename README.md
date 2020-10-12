# shlink importer

Collection of tools to import links from different sources and map them to a shlink-compliant format.

## Installation

## Supported import sources

* Bit.ly

## Usage

## Requirements

This package expects some services to be registered as dependencies, as they need to be used by some of the tools.

* `Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface`: It has to resolve an object implementing the interface.
* `Psr\Http\Client\ClientInterface`: Required by while importing from Bit.ly
