# Patreon Wordpress Plugin
## Development
Before committing any changes, make sure that pre-commit can run.
```
pre-commit install
```

You'll also need PHP and composer to manage associated packages.
```
brew install php@8.3
wget https://raw.githubusercontent.com/composer/getcomposer.org/f3108f64b4e1c1ce6eb462b159956461592b3e3e/web/installer -O - -q | php --
```

Finally install composer dependencies:
```
php composer.phar install
```


Adding new dev dependencies:
```
# Example, how php-cs-fixer was added
php composer.phar require --dev friendsofphp/php-cs-fixer
```

## Releases
Please make sure to follow [Wordpress plugin readme file standard](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/) and update
the readme.txt when necessary.
