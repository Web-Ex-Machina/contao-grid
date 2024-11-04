# PHP Stan
---

To install & configure PHP Stan : [https://phpstan.org/](https://phpstan.org/)

In order to analyse with PHP Stan, you have 2 choices :

## Bundle already installed in a Contao

```shell
# From website's root
phpstan analyse ./vendor/webexmachina/contao-grid/src -c ./vendor/webexmachina/contao-grid/phpstan.website.neon [options]
# eg
phpstan analyse ./vendor/webexmachina/contao-grid/src -c ./vendor/webexmachina/contao-grid/phpstan.website.neon --level 0
```

## Bundle alone

:warning: vendors must be retrieved before thanks to `composer install`.

```shell
# From bundle's root
phpstan analyse ./src -c ./phpstan.bundle.neon [options]
# eg
phpstan analyse ./src -c ./phpstan.bundle.neon --level 0
```