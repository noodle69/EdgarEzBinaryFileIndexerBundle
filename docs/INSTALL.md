# EdgarEzBinaryFileIndexerBundle

## Installation

### Get the bundle using composer

Add EdgarEzBinaryFileIndexerBundle by running this command from the terminal at the root of
your symfony project:

```bash
composer require edgar/ez-binaryfileindexer-bundle
```

## Enable the bundle

To start using the bundle, register the bundle in your application's kernel class:

```php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Edgar\EzBinaryFileIndexerBundle\EdgarEzBinaryFileIndexerBundle(),
        // ...
    );
}
```

## Configure eZ Platform using search_engine 'solr'

Follow the ezplatform-solr-search-engine [README](https://github.com/ezsystems/ezplatform-solr-search-engine/blob/master/README.md) file
to install and configure Solr, ezplatform parameters ...
