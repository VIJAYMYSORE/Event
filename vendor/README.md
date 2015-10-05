# composer-managed `vendor` directory

The contents of this directory are managed by [composer](http://getcomposer.org/).
In general, you shouldn't have to interact directly with the files here.

## Adding a new package

See packagist.org to browse packages. Other repositories can be added as well -
consult composer documentation for instructions.

**Please use specific versions of packages**. Wildcards and especially
'dev-master' versions can cause hard-to-debug issues that are easily avoided by simply using
a particular version of a package.

### Installing
`composer require vendor-name/project-name:1.0.0`

This will do a few things.

- Add the relevant entry to `composer.json`
- Download and unpack v1.0.0 of vendor-name/project-name into the `vendor/vendor-name/project-name` directory.
- Download and unpack any dependencies of the package into their respective locations in `vendor/`
- Add the relevant entries of the package and all of its dependencies to `composer.lock`
- Generate new autoloader files in `vendor/`

### Generate optimized autoloader
The default behavior of composer generates non-optimized autoloader files, which
is fine for dev but causes unacceptable performance issues in production.

`composer dump-autoload -o`

This will generate optimized autoloader files in `vendor/`.

### Ensure no .git directories exist in vendor/
Some packages may depend on source versions of packages, which will manifest themselves
as git checkouts in `vendor/`. These checkouts confuse the heck out of git, making the
following step behave incorrectly. Avoid this issue by removing all .git directories
inside `vendor/`:

For linux/osx:
`find vendor/ -iname '.git' -type d | xargs rm -rf`

### Add everything to git
```bash
git add composer.json composer.lock vendor/
git commit -m "Add library vendor-name/project-name@1.0.0"
```
