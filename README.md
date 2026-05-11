# Drupal CMS

Drupal CMS is a fast-moving open source product that enables site builders to easily create new Drupal sites and extend them with smart defaults, all using their browser.

## Getting started

If you want to use [DDEV](https://ddev.com) to run Drupal CMS locally, follow these instructions:

1. Install DDEV following the [documentation](https://ddev.com/get-started/)
2. Open the command line and `cd` to the root directory of this project
3. Run the following commands:
```shell
ddev config --project-type=drupal11 --docroot=web
ddev start
ddev composer install
ddev composer drupal:recipe-unpack
ddev launch
```

Drupal CMS has the same system requirements as Drupal core, so you can use your preferred setup to run it locally. [See the Drupal User Guide for more information](https://www.drupal.org/docs/user_guide/en/installation-chapter.html) on how to set up Drupal.

### Installation options

The Drupal CMS installer offers a list of features preconfigured with smart defaults. You will be able to customize whatever you choose, and add additional features, once you are logged in.

After the installer is complete, you will land on the dashboard.

## Documentation

* [Drupal CMS User Guide](https://project.pages.drupalcode.org/drupal_cms/)
* Learn more about managing a Drupal-based application in the [Drupal User Guide](https://www.drupal.org/docs/user_guide/en/index.html).

## Contributing

Drupal CMS is developed in the open on [Drupal.org](https://www.drupal.org). We are grateful to the community for reporting bugs and contributing fixes and improvements.

[Report issues in the queue](https://drupal.org/node/add/project-issue/drupal_cms), providing as much detail as you can. You can also join the #drupal-cms-support channel in the [Drupal Slack community](https://www.drupal.org/slack).

Drupal CMS has adopted a [code of conduct](https://www.drupal.org/dcoc) that we expect all participants to adhere to.

To contribute to Drupal CMS development, see the [drupal_cms project](https://www.drupal.org/project/drupal_cms).

## Project Customisations

### Composer Patches (`cweagans/composer-patches` v2)

Patches are defined in `composer.json` under `extra.patches` and tracked in
`patches.lock.json`. After any change to the patch list, run:

```shell
ddev exec composer reinstall <vendor/package>
```

to apply changes to a specific package, or `ddev exec composer install` for a
full dependency install.

#### `drupal/byte_theme`

| File | Purpose |
|------|---------|
| `patches/byte_theme_main.patch` | Adds custom background styles to the front-page hero image in `src/main.css` |

**Fix history:** An earlier `patches/byte_theme_theme.patch` was a duplicate of
`byte_theme_main.patch` and had a malformed diff header (plain `diff` format
with absolute paths instead of `git diff` format with package-relative paths).
This caused `composer install` to fail on the remote server with
`No available patcher was able to apply patch`. The duplicate was removed in
commit `707651f`; the stale entry was also purged from `patches.lock.json` in
commit `b95ae5f`.

**⚠️ Outstanding:** The patch references two CSS custom properties
`--background-alpha` and `--foreground-alpha` that are not yet defined in
`web/themes/contrib/byte_theme/src/theme.css` or any sub-theme. Until they are
defined, the hero background styles will render as transparent. Add the
variable definitions to your custom theme's CSS overrides, for example:

```css
:root {
  --background-alpha: oklch(0.13 0.043 265.132 / 70%);
  --foreground-alpha: oklch(1 0 89.876 / 70%);
}
```

## License

Drupal CMS and all derivative works are licensed under the [GNU General Public License, version 2 or later](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

Learn about the [Drupal trademark and logo policy here](https://www.drupal.com/trademark).
