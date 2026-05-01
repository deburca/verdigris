# AGENTS.md: AI Agent Guide for Drupal Development with DDEV

**AI Agent Instructions**: This guide provides comprehensive instructions for AI coding agents working on Drupal projects using DDEV. Follow these guidelines for consistent, high-quality contributions. Human contributors should use README.md instead.

## Project Overview
- **Core Technology**: Drupal 11.x+ (verify via `composer show drupal/core`)
- **Development Environment**: DDEV (Docker-based development environment)
- **Key Components**: Custom modules, themes, configuration management, Composer dependencies
- **Environment**: PHP 8.4+, MySQL/MariaDB, Apache-FPM (all managed by DDEV)
- **Development Tools**: Composer, Drush 13+, Git, DDEV CLI
- **Reference Code**: The [Examples for Developers](https://www.drupal.org/project/examples) module is installed as a dev dependency. Browse `web/modules/contrib/examples/` for working implementations of Drupal's core APIs including hooks, plugins, forms, render arrays, entities, caching, and more. Always consult relevant example modules before implementing a new pattern.
- **Important**: All DDEV commands should be run from project root. Use `ddev exec` for Drupal-specific commands.

## DDEV Quick Setup

### Prerequisites
```bash
# Install DDEV (macOS)
brew install ddev/ddev/ddev

# Or download from https://ddev.readthedocs.io/en/stable/users/installation/
# Verify installation
ddev --version
```

### Initialize DDEV Project
```bash
# Clone the repository
git clone <repository-url> my-drupal-project
cd my-drupal-project

# Initialize DDEV configuration
ddev config --project-type=drupal --docroot=web --php-version=8.4

# Start DDEV environment
ddev start

# Install Composer dependencies
ddev composer install

# Install Drupal
ddev exec drush site:install standard \
  --db-url=mysql://db:db@db/db \
  --account-name=admin \
  --account-pass=admin \
  --yes

# Enable development modules
ddev exec drush pm:enable devel kint webprofiler -y

# Clear caches
ddev exec drush cr

# Launch site in browser
ddev launch
```

### Essential DDEV Commands
```bash
# Environment management
ddev start                # Start development environment
ddev stop                 # Stop environment
ddev restart              # Restart environment
ddev delete               # Delete environment (careful!)

# Database operations
ddev snapshot             # Create database snapshot
ddev restore-snapshot     # Restore database snapshot
ddev import-db            # Import database from file
ddev export-db            # Export database to file

# Development tools
ddev exec <command>       # Execute command in container
ddev ssh                  # SSH into web container
ddev logs                 # View container logs
ddev describe             # Show environment details
ddev launch               # Open site in browser
```

### DDEV Configuration
Create `.ddev/config.yaml` for project-specific settings:

```yaml
# .ddev/config.yaml
name: my-drupal-project
type: drupal11
docroot: web
webserver_type: apache-fpm
xdebug_enabled: false
additional_hostnames: []
additional_fqdns: []
database:
  type: mariadb
  version: "10.11"
performance_mode: none
mutagen_enabled: false
use_dns_when_possible: false
composer_version: "2"
corepack_enable: true
web_environment:
  - "STORYBOOK_SERVER_RENDER_URL='https://${DDEV_PROJECT}.${DDEV_TLD}/storybook/stories/render'"
  - DRUSH_OPTIONS_URI=https://$(DDEV_PROJECT).$(DDEV_TLD)/
php_version: "8.4"
nodejs_version: "20"
dbimage_extra_packages: [sudo]
webimage_extra_packages:
  - php${DDEV_PHP_VERSION}-yaml
  - php${DDEV_PHP_VERSION}-tidy
  - php${DDEV_PHP_VERSION}-gmp
  - xdg-utils
  - pkg-config
  - libpixman-1-dev
  - libcairo2-dev
  - libpango1.0-dev
  - make
web_extra_exposed_ports:
  - name: storybook
    container_port: 6006
    http_port: 6007
    https_port: 6006
web_extra_daemons:
  - name: node.js
    command: "tail -F package.json > /dev/null"
    directory: /var/www/html
hooks:
  post-start:
    - exec: yarn set version berry
disable_settings_management: false
```

## Code Style and Standards
Adhere to Drupal coding standards (PSR-12 with Drupal extensions). Use Coder and PHPCS for enforcement.

- **PHP**:
  - Indentation: 2 spaces (no tabs)
  - Line length: ≤ 80 characters
  - Naming: CamelCase classes/methods, snake_case variables/functions
  - Always use braces; prefer early returns
  - Full PHPDoc blocks with `@param`, `@return`, `@throws`

- **YAML**: 2-space indentation, lowercase keys
- **Twig**: `{{ }}` for output, `{% %}` for logic; always escape with `|e`

- **Linting**:
  ```bash
  ddev exec vendor/bin/phpcs --standard=Drupal --extensions=php,inc,module,install,info,yml web/modules/custom/<module_name>
  ddev exec vendor/bin/phpcs --standard=DrupalPractice --extensions=php,inc,module,install,info,yml web/modules/custom/<module_name>
  ddev exec vendor/bin/phpcs --standard=Drupal --fix web/modules/custom/<module_name>
  ```

**Reject any code that fails Drupal Coder sniffs.**

## Drupal Development Patterns

### Services & Dependency Injection
- **Create services** in `modulename.services.yml` file for reusable logic
- **Use dependency injection** to inject services into controllers, forms, and plugins
- **Core services** like `@current_user`, `@entity_type.manager`, `@database` are available
- **Best practice**: Avoid static `\Drupal::` calls in favor of dependency injection
- **Service discovery**: Use `drush php:eval "print_r(\Drupal::getContainer()->getServiceIds());"` to see available services
- **Location**: Place service classes in `src/` directory with proper namespace
- **Reference**: See `web/modules/contrib/examples/modules/events_example` and `web/modules/contrib/examples/modules/stream_wrapper_example` for service registration patterns

### Entity API & Queries
- **Entity loading**:
```php
// Prefer dependency injection
$entity = $this->entityTypeManager->getStorage('node')->load($id);

// Only use static when DI unavailable (hooks, etc.)
$entity = \Drupal::entityTypeManager()->getStorage('node')->load($id);
```
- **Entity queries**: Use `\Drupal::entityQuery()` for database operations instead of raw SQL
- **Query conditions**: Chain multiple conditions with `->condition()`, `->sort()`, `->range()`
- **Entity creation**: Create entities with `Entity::create(['type' => 'bundle_name'])`
- **Field access**: Use entity field API instead of direct property access
- **Performance**: Use entity query cache tags and contexts for optimal caching

### Plugin System
- **Plugin types**: Blocks, field formatters, field widgets, menu links, and more
- **Plugin discovery**: Use annotation-based discovery in docblocks
- **Plugin configuration**: Define plugin ID, label, and other metadata in annotations
- **Plugin base classes**: Extend appropriate base classes (BlockBase, FormatterBase, etc.)
- **Plugin placement**: Place plugins in `src/Plugin/Type/` directory structure
- **Derivative plugins**: Use for creating multiple plugins from one definition
- **Reference**: See `web/modules/contrib/examples/modules/block_example` and `web/modules/contrib/examples/modules/plugin_type_example` for working plugin implementations

### Hooks
- **Hook implementation**: Implement hooks in `modulename.module` file
- **Hook naming**: Implement hooks as `modulename_hookname()` (e.g., `mymodule_form_alter()`). Define custom hooks as `hook_modulename_action()` in documentation only.
- **Hook parameters**: Use type hints and proper parameter documentation
- **Core hooks**: Common hooks include `hook_form_alter()`, `hook_theme()`, `hook_menu_links_discovered_alter()`
- **Hook order**: Hooks fire in module weight order (lowest first)
- **Best practice**: Keep hook implementations focused and use services for complex logic
- **Reference**: See `web/modules/contrib/examples/modules/hooks_example` and `web/modules/contrib/examples/modules/form_api_example` for hook implementation patterns

### Forms API
- **Form classes**: Extend `FormBase` for simple forms or `ConfigFormBase` for configuration forms
- **Form structure**: Use render array structure with `#type`, `#title`, `#description` properties
- **Form validation**: Implement `validateForm()` method for custom validation
- **Form submission**: Implement `submitForm()` method for processing form data
- **Form elements**: Use proper form element types (textfield, select, checkbox, etc.)
- **AJAX forms**: Add `#ajax` property to form elements for dynamic behavior
- **Form caching**: Forms are automatically cached with CSRF protection
- **Reference**: See `web/modules/contrib/examples/modules/form_api_example` and `web/modules/contrib/examples/modules/ajax_example` for form and AJAX patterns

### Routes & Controllers
- **Routing file**: Define routes in `modulename.routing.yml` with path, defaults, and requirements
- **Controllers**: Create controller classes extending `ControllerBase` in `src/Controller/`
- **Route parameters**: Use `{parameter}` placeholders in paths and inject into controller methods
- **Access control**: Implement `_permission`, `_role`, or custom access callbacks
- **Route naming**: Use `modulename.action` naming convention for clarity
- **Controller injection**: Use constructor injection for dependencies
- **Return values**: Return render arrays or Symfony Response objects
- **Reference**: See `web/modules/contrib/examples/modules/page_example` and `web/modules/contrib/examples/modules/render_example` for routing, controllers, and render array patterns

## Security & Performance Guidelines

### Security Requirements
- **Always sanitize user input**: Use `#plain_text` for untrusted content
- **CSRF protection**: Include `#token` for forms with side effects
- **Permissions**: Implement proper access checks and route requirements
- **SQL Injection**: Use Entity Query or proper parameter binding
- **XSS Prevention**: Always use `|e` filter in Twig, `#markup` for trusted HTML only

### Performance Best Practices
- **Render caching**: Always add `#cache` array to render arrays with appropriate `tags` and `contexts`
- **Cache tags**: Use entity-based tags like `['node:123']` or list-based tags like `['node_list']`
- **Cache contexts**: Apply user-specific contexts like `['user.roles']` for personalized content
- **Lazy loading**: Use `#lazy_builder` for expensive operations that can be loaded separately
- **Placeholder strategy**: Set `#create_placeholder: TRUE` for lazy builders to improve initial page load
- **Cache max-age**: Set appropriate `max-age` values based on content freshness requirements
- **Avoid premature optimization**: Profile first, then optimize based on actual bottlenecks
- **Reference**: See `web/modules/contrib/examples/modules/render_example` and `web/modules/contrib/examples/modules/cache_example` for caching and render array patterns
- **Database queries**: Use entity queries instead of raw SQL for better caching and security
- **Entity loading**: Load multiple entities at once when possible for better performance

### Caching Strategies
- **Render cache**: Cache complex markup with proper tags/contexts
- **Dynamic page cache**: Configure for anonymous users
- **Internal page cache**: Enable for authenticated users
- **Entity cache**: Leverage core entity caching
- **Redis/Memcache**: Configure for distributed caching

## DDEV Development Workflow

### Project Structure
- **Modules** → `web/modules/custom/<module_name>`
- **Themes** → `web/themes/custom/<theme_name>`
- **Configuration** → Export with `ddev exec drush config:export`
- **Profiles** → `web/profiles/custom/<profile_name>`

### Essential Development Commands
```bash
# Cache management (run inside DDEV)
ddev exec drush cr                    # Clear all caches
ddev exec drush cache:rebuild         # Alternative cache clear

# Configuration management
ddev exec drush config:export         # Export configuration
ddev exec drush config:import         # Import configuration

# Database operations
ddev snapshot                         # Create snapshot before changes
ddev exec drush updatedb              # Run database updates
```

### Debugging in DDEV

#### Core Debugging & Information Commands
| Command                          | Purpose                                                                 | Why it's useful for debugging                                      |
|----------------------------------|-------------------------------------------------------------------------|--------------------------------------------------------------------|
| `ddev exec drush status`         | Shows Drupal root, site path, database connection, Drush version, etc. | Quickly verify that DDEV is pointing to the correct site and DB is connected. |
| `ddev exec drush core-status`    | Same as above but more detailed in newer versions.                      |                                                                    |
| `ddev exec drush watchdog:show`  | Lists recent log messages (dblog entries).                              | Primary command to read the Drupal error/log messages without going to /admin/reports/dblog. Supports filters: `--severity=Error`, `--type=php`, etc. |
| `ddev exec drush watchdog:delete all` | Clears the watchdog log.                                                | Useful when logs become huge and slow down watchdog operations. |
| `ddev exec drush sql:query "SELECT * FROM watchdog ORDER BY wid DESC LIMIT 50"` | Direct SQL access to logs when the database is very large.             | Faster than watchdog:show on sites with millions of log entries. |

#### Cache Debugging
| Command                          | Purpose                                                                 |
|----------------------------------|-------------------------------------------------------------------------|
| `ddev exec drush cache:rebuild`  | Rebuilds all caches (equivalent to "drush cc all" in D7).              |
| `ddev exec drush cr`             | Alternative cache rebuild command.                                      |
| `ddev exec drush cache:get <bin>:<cid>` | Retrieve a specific cache item (e.g., `ddev exec drush cache:get config:core.extension`). |
| `ddev exec drush cache:clear <bin>` | Clear only one cache bin (render, config, discovery, etc.).            |

#### Configuration Debugging
| Command                                      | Purpose                                                                 |
|----------------------------------------------|-------------------------------------------------------------------------|
| `ddev exec drush config:get <name>`          | Show a single configuration value (e.g., `ddev exec drush config:get system.site`). |
| `ddev exec drush config:set <name> <key> <value>` | Temporarily change a config value without using the UI.                 |
| `ddev exec drush config:export`              | Export active config to sync directory.                                 |
| `ddev exec drush config:import`              | Import config – very useful to test if config issues cause errors.      |
| `ddev exec drush config:delete <name>`       | Remove a config object (helps when orphaned config causes fatal errors).|

#### Module/Theming Debugging
| Command                                | Purpose                                                                 |
|----------------------------------------|-------------------------------------------------------------------------|
| `ddev exec drush pm:list --type=module --status=enabled` | List enabled modules.                                                  |
| `ddev exec drush pm:enable <module>`   | Enable a module.                                                       |
| `ddev exec drush pm:uninstall <module>` | Fully uninstall a module (removes config and data).                    |
| `ddev exec drush pm:uninstall`          | Interactive mode - excellent for disabling suspected problematic modules quickly. |
| `ddev exec drush theme:debug`           | Lists all theme suggestions for a given route or render array (Drupal 9.4+). |

#### Database & Entity Debugging
| Command                                      | Purpose                                                                 |
|----------------------------------------------|-------------------------------------------------------------------------|
| `ddev exec drush sql:connect`                | Outputs the CLI command to connect to the DB (useful for manual queries). |
| `ddev exec drush sql:query`                  | Run arbitrary SQL.                                                      |
| `ddev exec drush entity:info`                | Show entity type definitions (useful when entity schema errors occur). |
| `ddev exec drush php`                        | Opens an interactive PHP shell with Drupal bootstrapped (like `ddev exec drush php:eval`). |
| `ddev exec drush php:eval "code"`            | Execute arbitrary PHP code in Drupal context (great for quick debugging). Example: `ddev exec drush php:eval "dpm(\Drupal::state()->get('system.cron_last'));"` (with Devel) |

#### Development & Error Reproduction
| Command                                | Purpose                                                                 |
|----------------------------------------|-------------------------------------------------------------------------|
| `ddev exec drush php:eval "var_dump(function_exists('my_problematic_function'));"` | Quick test if a function exists or what it returns.                     |
| `ddev exec drush state:edit` / `ddev exec drush state:get/set/delete` | Inspect or override Drupal state values (often used by broken modules).|
| `ddev exec drush variable:get/set/delete` (D7 only) | Legacy equivalent of state commands.                                    |
| `ddev exec drush twig:debug`           | Turn Twig debugging on/off and verify template suggestions.            |
| `ddev exec drush eval`                 | Same as above (alias of php:eval).                                      |

#### Performance & Query Debugging
| Command                                | Purpose                                                                 |
|----------------------------------------|-------------------------------------------------------------------------|
| `ddev exec drush sql:query --db-prefix` | See queries with table prefixes expanded (helps reading raw SQL).      |

**Debugging with Devel module**: Use `kint($variable)` or `dpm($variable)` in your PHP code, then view output in browser or via `ddev exec drush watchdog:show`.

#### DDEV-Specific Debugging
```bash
# Enable Xdebug debugging
# Add to .ddev/config.yaml:
# xdebug_enabled: true

# DDEV container debugging
ddev logs -f web                       # Follow web container logs
ddev logs -f db                        # Follow database container logs
ddev describe                          # Show environment details and status

# Access PHP error logs
ddev exec tail -f /var/log/apache2/error.log

# Debugging functions (use with devel module in PHP code)
# Add kint($variable) or dpm($variable) in your code, then view in browser
ddev exec drush php:eval "print_r(\Drupal::config('system.site')->get());"

# Database connection debugging
ddev exec drush sql:connect            # Test database connection
ddev describe                          # Check environment status
```

### Performance Profiling in DDEV
```bash
# Performance analysis
ddev exec drush cr                     # Rebuild caches
ddev exec drush sql:query "EXPLAIN ANALYZE SELECT ..."  # Query analysis
ddev exec drush site:status           # System status check

# Use Webprofiler module for detailed profiling
# Access at https://my-drupal-project.ddev.site/admin/config/development/devel/webprofiler
```

### Version Control Workflow
- **Commit messages**: Format `[#123456] Brief descriptive title`
- **Branch from**: `develop` branch for features
- **Atomic commits**: One logical change per commit
- **Before pushing**: Run linting and tests

## Testing & Quality Assurance

### PHPUnit Testing Framework
Aim for ≥ 80% code coverage. Drupal provides multiple test types:

```bash
# Run all tests with coverage
ddev exec vendor/bin/phpunit -v --coverage-html coverage/

# Run specific test suites
ddev exec vendor/bin/phpunit --testsuite unit          # Unit tests (fast)
ddev exec vendor/bin/phpunit --testsuite kernel         # Kernel tests
ddev exec vendor/bin/phpunit --testsuite functional     # Functional tests (slower)
ddev exec vendor/bin/phpunit --testsuite javascript     # JavaScript tests

# Run specific tests
ddev exec vendor/bin/phpunit --filter MyModuleUnitTest
ddev exec vendor/bin/phpunit web/modules/custom/my_module/tests/src/Unit/

# Run with custom configuration
SIMPLETEST_DB=sqlite://localhost/tmp.sqlite ddev exec vendor/bin/phpunit
```

### Test Types and Examples

#### Unit Tests (fastest)
- **Purpose**: Test individual classes and methods in isolation
- **Base class**: Extend `UnitTestCase` from `Drupal\Tests\UnitTestCase`
- **Speed**: Fastest test type, no Drupal bootstrap required
- **Isolation**: Test one piece of functionality at a time
- **Dependencies**: Mock external dependencies and services
- **Location**: Place in `tests/src/Unit/` directory
- **Use cases**: Service logic calculations, utility functions, data transformations
- **Best practices**: Keep tests small, focused, and deterministic

#### Kernel Tests (with database)
- **Purpose**: Test Drupal interactions with minimal Drupal environment
- **Base class**: Extend `KernelTestBase` from `Drupal\KernelTests\KernelTestBase`
- **Environment**: Partial Drupal bootstrap with in-memory database
- **Modules**: Declare required modules in `$modules` static property
- **Database**: Uses SQLite in-memory database for speed
- **Location**: Place in `tests/src/Kernel/` directory
- **Use cases**: Entity CRUD operations, configuration validation, service registration
- **Setup**: Install modules and configuration in `setUp()` method

#### Functional Tests (with browser)
- **Purpose**: Test complete user interactions through browser simulation
- **Base class**: Extend `BrowserTestBase` from `Drupal\Tests\BrowserTestBase`
- **Environment**: Full Drupal bootstrap with real browser
- **Speed**: Slowest test type, full page loads required
- **Theme**: Set `$defaultTheme` property (usually 'stark' or 'claro')
- **Location**: Place in `tests/src/Functional/` directory
- **Use cases**: Form submissions, page access, user permissions, JavaScript interactions
- **Browser simulation**: Uses Goutte/ChromeDriver for browser automation
- **Assertions**: Use `$this->assertSession()` for web assertions

### Code Quality Tools in DDEV
```bash
# Static analysis (add to composer require)
ddev exec vendor/bin/phpstan analyse                      # PHPStan analysis
ddev exec vendor/bin/psalm                               # Psalm analysis

# Security scanning
ddev exec vendor/bin/drupal-check                        # Check for deprecated code
ddev exec composer audit                                 # Check for security advisories

# Accessibility testing
ddev exec vendor/bin/phpunit --group accessibility       # Accessibility tests
```

### JavaScript Testing
```bash
# Install JavaScript dependencies
ddev exec npm install

# Run JavaScript tests
ddev exec npm run test                                   # Jest tests
ddev exec npm run test:a11y                             # Accessibility tests
```

### Before Submitting Code
```bash
# Quality checklist
ddev exec vendor/bin/phpcs --standard=Drupal .          # Code style
ddev exec vendor/bin/phpunit                             # Run tests
ddev exec drush cr                                       # Clear caches
ddev exec drush updatedb                                 # Run updates
```

## DDEV-Specific Troubleshooting

### Common DDEV Issues
```bash
# DDEV won't start
ddev poweroff && ddev start

# Port conflicts
# Edit .ddev/config.yaml to change ports
router_http_port: "8080"
router_https_port: "8443"

# Memory issues
# Increase PHP memory in .ddev/php/php.ini
memory_limit = 512M

# Composer memory issues
ddev exec php -d memory_limit=-1 /usr/local/bin/composer install

# Database connection issues
ddev describe    # Check environment status
ddev exec drush sql:connect  # Test database connection
```

### Performance Issues in DDEV
```bash
# Identify slow queries
ddev exec drush sql:query "SELECT * FROM watchdog WHERE type = 'php' ORDER BY wid DESC LIMIT 10"

# Check cache settings
ddev exec drush config:get system.performance

# Enable performance modules
ddev exec drush pm:enable memcache redis -y
```

### Module/Theme Development Issues in DDEV
```bash
ddev exec drush cr

# Service not found
ddev exec drush config:get core.extension

# Twig template not loading
ddev exec drush cr

# Cron issues
ddev exec drush cron
ddev exec drush watchdog:show --type=cron
```

### Testing Issues in DDEV
```bash
# PHPUnit configuration problems
# Ensure phpunit.xml.dist exists and is configured
cp web/core/phpunit.xml.dist phpunit.xml

# Database setup for testing
# Edit phpunit.xml for SIMPLETEST_DB and SIMPLETEST_BASE_URL
SIMPLETEST_DB=mysql://db:db@db/db_test
SIMPLETEST_BASE_URL=http://my-drupal-project.ddev.site

# Browser tests failing
# Install Selenium or ChromeDriver
# Ensure test environment variables are set
```

## Advanced Development Patterns

### Batch API for Long Operations
- **Purpose**: Process large datasets without PHP timeout issues
- **Use cases**: Data migration, bulk updates, file processing, API calls
- **Batch structure**: Create associative array with title, operations, and finished callback
- **Operations**: Array of callable methods and their arguments
- **Progress tracking**: Automatically shows progress bar to users
- **Error handling**: Implement proper exception handling in batch operations
- **User experience**: Provides real-time feedback during long operations
- **Memory management**: Processes data in chunks to prevent memory exhaustion
- **Reference**: See `web/modules/contrib/examples/modules/batch_example` for batch processing patterns

### Queue API for Background Processing
- **Purpose**: Process tasks in the background without blocking user interaction
- **Queue creation**: Use `\Drupal::queue('queue_name')` to get queue instance
- **Item addition**: Use `createItem()` to add tasks to the queue
- **Processing**: Claim items with `claimItem()` and delete with `deleteItem()`
- **Cron integration**: Process queue items during cron runs for regular background tasks
- **Reliability**: Failed items can be released back to the queue
- **Worker plugins**: Create QueueWorker plugins for structured queue processing
- **Logging**: Implement proper logging for queue processing monitoring
- **Performance**: Process multiple items per cron run for efficiency
- **Reference**: See `web/modules/contrib/examples/modules/queue_example` for queue worker plugin patterns

### AJAX Forms
- **Trigger elements**: Add `#ajax` property to form elements (select, checkbox, button)
- **Callback method**: Reference callback method using `::methodName` syntax
- **Wrapper element**: Specify target element ID for AJAX response replacement
- **Response format**: Return form element or render array from callback
- **Event types**: Use 'change', 'click', 'blur' events as needed
- **Progress indicator**: Automatically shows loading indicator during AJAX requests
- **Error handling**: Implement try-catch blocks in AJAX callbacks
- **Form state**: Use `$form_state->getTriggeringElement()` to identify trigger
- **Multiple triggers**: Can have multiple AJAX elements in same form
- **Dynamic forms**: Update form options, show/hide fields based on user input
- **Reference**: See `web/modules/contrib/examples/modules/ajax_example` for AJAX form patterns

## Additional Resources

### DDEV Documentation
- **DDEV Official Docs**: https://ddev.readthedocs.io
- **DDEV Quick Start**: https://ddev.readthedocs.io/en/stable/users/quickstart/
- **DDEV Drupal Guide**: https://ddev.readthedocs.io/en/stable/users/topics/drupal/

### Drupal Documentation
- **Drupal API**: https://api.drupal.org
- **Developer Guide**: https://www.drupal.org/docs/develop
- **Coding Standards**: https://www.drupal.org/docs/develop/standards
- **Security Best Practices**: https://www.drupal.org/docs/develop/security
- **Examples for Developers**: https://www.drupal.org/project/examples (installed at `web/modules/contrib/examples/`)

### Community Resources
- **DrupalAtYourFingertips**: https://www.drupalatyourfingertips.com
- **Drupal Answers**: https://drupal.stackexchange.com
- **Drupal.org**: https://www.drupal.org
- **Drupal Slack**: https://drupal.slack.com

### Environment Variable Configuration
Environment variables can be set in DDEV and accessed in Drupal:

**Setting variables in `.ddev/config.yaml`:**
```yaml
web_environment:
  - MY_API_KEY=${MY_API_KEY:-default_value}
  - ENVIRONMENT=development
```

**Setting variables in `.ddev/.env` (for secrets - add to .gitignore):**
```bash
MY_API_KEY=your_secret_key_here
SMTP_PASSWORD=smtp_secret
```

**Accessing in Drupal settings.php:**
```php
// web/sites/default/settings.php or settings.local.php
$settings['my_api_key'] = getenv('MY_API_KEY');
$config['smtp.settings']['smtp_password'] = getenv('SMTP_PASSWORD');
```

**Accessing in PHP code:**
```php
$api_key = getenv('MY_API_KEY');
// Or via settings
$api_key = \Drupal\Core\Site\Settings::get('my_api_key');
```

### Multisite Setup Guidance
DDEV supports Drupal multisite configurations:

**Directory structure:**
```
web/sites/
├── default/          # Primary site
├── site1.example/    # Additional site
└── site2.example/    # Additional site
```

**Configure `.ddev/config.yaml`:**
```yaml
additional_hostnames:
  - site1
  - site2
additional_fqdns:
  - site1.example.ddev.site
  - site2.example.ddev.site
```

**Create sites.php:**
```php
// web/sites/sites.php
$sites['site1.ddev.site'] = 'site1.example';
$sites['site2.ddev.site'] = 'site2.example';
```

**Drush commands for multisite:**
```bash
# Target specific site with --uri
ddev exec drush --uri=site1.ddev.site cr
ddev exec drush --uri=site2.ddev.site config:export
```

### Composer Patches Workflow
Use `cweagans/composer-patches` to apply patches to contrib modules:

**Install the plugin:**
```bash
ddev composer require cweagans/composer-patches
```

**Configure in composer.json:**
```json
{
  "extra": {
    "patches": {
      "drupal/module_name": {
        "Issue #1234567: Brief description": "https://www.drupal.org/files/issues/2024-01-15/module_name-issue-1234567-15.patch"
      }
    },
    "composer-exit-on-patch-failure": true,
    "enable-patching": true
  }
}
```

**Local patches (stored in project):**
```json
{
  "extra": {
    "patches": {
      "drupal/module_name": {
        "Custom fix for XYZ": "patches/module_name-custom-fix.patch"
      }
    }
  }
}
```

**Apply patches:**
```bash
ddev composer install
# Or to reapply
ddev composer reinstall drupal/module_name
```

### Translation/Localization Patterns
**Enable translation modules:**
```bash
ddev exec drush pm:enable locale content_translation config_translation -y
```

**Using `t()` for translatable strings:**
```php
// In procedural code
$message = t('Welcome, @name!', ['@name' => $username]);

// In classes (inject string_translation service)
$message = $this->t('Welcome, @name!', ['@name' => $username]);
```

**Placeholder types:**
- `@variable` - Sanitized text (escaped HTML)
- `%variable` - Sanitized and emphasized (wrapped in `<em>`)
- `:variable` - URL (escaped for href attributes)

**Twig translation:**
```twig
{{ 'Welcome, @name!'|t({'@name': username}) }}
{% trans %}Hello World{% endtrans %}
```

**Export/import translations:**
```bash
# Export translations
ddev exec drush locale:export fr > translations/fr.po

# Import translations
ddev exec drush locale:import fr translations/fr.po

# Update translations from remote sources
ddev exec drush locale:check
ddev exec drush locale:update
```

### Trusted Host Patterns Configuration
Configure `trusted_host_patterns` in `settings.php` to prevent HTTP Host header attacks:

```php
// web/sites/default/settings.php
$settings['trusted_host_patterns'] = [
  // DDEV local development
  '^.+\.ddev\.site$',
  // Production domain
  '^www\.example\.com$',
  '^example\.com$',
  // Staging
  '^staging\.example\.com$',
];
```

**For DDEV specifically (settings.ddev.php):**
```php
// This is typically auto-configured by DDEV
$settings['trusted_host_patterns'] = ['.*'];
```

**Note**: In production, always use specific patterns. The `['.*']` pattern should only be used in development.

### Input Sanitization Examples
**Render arrays (preferred method):**
```php
// Safe - Drupal handles escaping
$build['message'] = [
  '#plain_text' => $user_input,  // Escaped, no HTML allowed
];

$build['formatted'] = [
  '#markup' => $trusted_html,  // Only for trusted HTML
];

// With allowed tags
use Drupal\Component\Utility\Xss;
$build['filtered'] = [
  '#markup' => Xss::filter($user_input),
];
```

**Form API (automatic CSRF and sanitization):**
```php
$form['name'] = [
  '#type' => 'textfield',
  '#title' => $this->t('Name'),
  '#required' => TRUE,
  '#maxlength' => 255,
];
```

**Database queries (parameterized):**
```php
// Safe - uses placeholders
$result = $connection->query(
  'SELECT * FROM {users} WHERE name = :name',
  [':name' => $user_input]
);

// Entity queries (always safe)
$query = \Drupal::entityQuery('node')
  ->condition('title', $user_input)
  ->accessCheck(TRUE);
```

**Twig templates:**
```twig
{# Automatically escaped #}
{{ user_input }}

{# Explicitly escape (redundant but clear) #}
{{ user_input|e }}

{# Allow specific HTML tags #}
{{ content|striptags('<p><br><strong>') }}

{# DANGEROUS - only for trusted content #}
{{ trusted_html|raw }}
```

**URL handling:**
```php
use Drupal\Component\Utility\UrlHelper;

// Validate external URLs
if (UrlHelper::isValid($url, TRUE)) {
  // Safe to use
}

// In Twig, use :variable placeholder
$message = t('Visit <a href=":url">our site</a>', [':url' => $url]);
```

### OWASP Top 10 Considerations for Drupal
Address common vulnerabilities when developing Drupal modules:

**A01: Broken Access Control**
- Always use `->accessCheck(TRUE)` on entity queries
- Define granular permissions in `modulename.permissions.yml`
- Use `_permission` or `_custom_access` in route definitions
- Check access in controllers: `$entity->access('view')`

**A02: Cryptographic Failures**
- Never store sensitive data in plain text
- Use Drupal's Key module for API keys
- Store secrets in environment variables, not config

**A03: Injection**
- Use Entity Query API instead of raw SQL
- Use parameterized queries when SQL is necessary
- Use `#plain_text` for user-supplied content

**A05: Security Misconfiguration**
- Configure `trusted_host_patterns`
- Disable PHP display_errors in production
- Remove development modules (devel, webprofiler) in production
- Set proper file permissions (files: 755, settings.php: 444)

**A06: Vulnerable Components**
- Run `ddev composer audit` regularly
- Subscribe to Drupal security advisories
- Keep core and contrib modules updated

**A07: Authentication Failures**
- Use Drupal's built-in flood control
- Implement password policies
- Enable TFA (Two-Factor Authentication) module

### DDEV Command Reference Card

**Environment Control**
| Command | Description |
|---------|-------------|
| `ddev start` | Start containers |
| `ddev stop` | Stop containers |
| `ddev restart` | Restart containers |
| `ddev poweroff` | Stop all DDEV projects |
| `ddev delete` | Remove project (keeps files) |
| `ddev describe` | Show project info & URLs |
| `ddev launch` | Open site in browser |

**Executing Commands**
| Command | Description |
|---------|-------------|
| `ddev exec <cmd>` | Run command in web container |
| `ddev ssh` | SSH into web container |
| `ddev composer <cmd>` | Run Composer |
| `ddev drush <cmd>` | Run Drush (shortcut) |
| `ddev npm <cmd>` | Run npm |
| `ddev yarn <cmd>` | Run Yarn |

**Database Operations**
| Command | Description |
|---------|-------------|
| `ddev snapshot` | Create DB snapshot |
| `ddev snapshot -n <name>` | Create named snapshot |
| `ddev snapshot --list` | List snapshots |
| `ddev snapshot restore` | Restore latest snapshot |
| `ddev snapshot restore -n <name>` | Restore named snapshot |
| `ddev export-db -f backup.sql.gz` | Export database |
| `ddev import-db -f backup.sql.gz` | Import database |

**Drush Essentials**
| Command | Description |
|---------|-------------|
| `ddev drush cr` | Clear all caches |
| `ddev drush cex` | Export config |
| `ddev drush cim` | Import config |
| `ddev drush updb` | Run database updates |
| `ddev drush uli` | Generate login link |
| `ddev drush pm:list --status=enabled` | List enabled modules |
| `ddev drush watchdog:show` | View recent logs |
| `ddev drush cron` | Run cron |

**Debugging & Logs**
| Command | Description |
|---------|-------------|
| `ddev logs` | View container logs |
| `ddev logs -f` | Follow logs (live) |
| `ddev logs -s db` | View database logs |
| `ddev xdebug on` | Enable Xdebug |
| `ddev xdebug off` | Disable Xdebug |
| `ddev drush status` | Check Drupal status |
| `ddev drush ws --severity=error` | View error logs |

**Configuration**
| Command | Description |
|---------|-------------|
| `ddev config` | Interactive configuration |
| `ddev config --php-version=8.4` | Set PHP version |
| `ddev config --nodejs-version=20` | Set Node.js version |
| `ddev get <addon>` | Install DDEV addon |
