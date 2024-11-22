# composer-patches-regex

A Composer plugin that allows for regex replacements patches
using [composer-patches](https://github.com/cweagans/composer-patches).

## Requirements

- PHP 8.0.0 or higher
- `cweagans/composer-patches`, preferably on commit `5269693119b245e273db052f12ab23d74aca26fc` or later.
    - (note: this *has* to be installed through either `dev-main` or a custom `repositories` entry, as this package
      version isn't tagged on Packagist)

First of all, install the package using `composer require lexisother/composer-patches-regex`.

You should be prompted to allow the plugin to run code, if not, add the following to your `composer.json`:

```json
{
  // ...
  "config": {
    "allow-plugins": {
      // ...
      "lexisother/composer-patches-regex": true
    }
  }
}
```

## Defining patches

A patch looks simple. For the most part they are like regular `composer-patches` entries. You can read more about the
general definition of patches [here](https://docs.cweagans.net/composer-patches/usage/defining-patches).

It is important to note that this plugin only supports patches defined with the *expanded format*.

Let's define a simple patch for a package with the following file in its *root directory* (`vendor/scope/packagename`):

```
I am some original text. I love being original.
```

We want to change the word "some" to "a bit of" and all instances of "original" to "copied". Our patch for this would
look as follows:

```json
{
  // ...
  "extra": {
    "patches": {
      "scope/packagename": [
        {
          "description": "A simple description of your patch here",
          "url": "./.gitignore",
          "extra": {
            "regex": {
              "files": {
                "someFile": [
                  {
                    "find": "/some/",
                    "replace": "a bit of"
                  },
                  {
                    "find": "/original/g",
                    "replace": "copied"
                  }
                ]
              }
            }
          }
        }
      ]
    }
  }
}
```

> [!NOTE]  
> When using the `extra.regex.files` key, please set the patch `url` to some arbitrary local file. `composer-patches`
> requires there to be a `url`, but we don't use it.

After running `composer patches-relock` and `composer patches-repatch`, `vendor/scope/packagename/someFile` would now
look like this:

```
I am a bit of copied text. I love being copied.
```

## Options

The plugin exposes some options to change the behaviour of the patcher. All of these options should be set directly
inside `extra.regex`.

### `fromUrl`

Setting this option to `true` makes the plugin ignore the `files` key and instead download the JSON from the specified
URL. So if you are migrating your local patches to a file that is hosted remotely, please set your patch's `url` field
to a valid URL pointing to your file.

### `ignoreErrors`

Setting this option to `true` prevents `composer-patches` from failing with
`"No available patcher was able to apply patch"` if any of your patches contain errors.

# License

This project is dual-licensed under the [Commons Clause](https://commonsclause.com/) and the [GNU AGPL](https://choosealicense.com/licenses/agpl-3.0/).
