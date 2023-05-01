<?php
/*
## Configuration

- `bin/wp` *(optional)*: set WP-CLI binary, automatically detected otherwise.

## Usage

```php
task('deploy:wp-core-download', function() {
    run('cd {{release_or_current_path}} && {{bin/wp}} core download');
});
```

*/
namespace Deployer;

set('bin/wp', function () {
    if (test('[ -f {{release_or_current_path}}/vendor/wp-cli/wp-cli/php/boot-fs.php ]')) {
        return '{{bin/php}} {{release_or_current_path}}/vendor/wp-cli/wp-cli/php/boot-fs.php';
    }

    if (test('[ -f {{deploy_path}}/.dep/wp-cli.phar ]')) {
        return '{{bin/php}} {{deploy_path}}/.dep/wp-cli.phar';
    }

    if (commandExist('wp')) {
        return '{{bin/php}} ' . which('wp');
    }

    warning("WP-CLI binary wasn't found. Installing latest WP-CLI to \"{{deploy_path}}/.dep/wp-cli.phar\".");
    run('cd {{deploy_path}} && curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar');
    run('mv {{deploy_path}}/wp-cli.phar {{deploy_path}}/.dep/wp-cli.phar');
    return '{{bin/php}} {{deploy_path}}/.dep/wp-cli.phar';
});

/**
 * Runs wp-cli subcommand.
 *
 * @param string $command Subcommand with all arguments
 *
 * @return string
 */
function wp($command)
{
    cd('{{release_or_current_path}}');

    return run('{{bin/wp}} '. $command);
}

/**
 * Returns wp-cli subcommand status.
 *
 * @param string $command Subcommand with all arguments
 *
 * @return bool
 */
function wpTest($command)
{
    cd('{{release_or_current_path}}');

    return test('{{bin/wp}} '. $command);
}

/**
 * Checks whether WordPress core installed.
 *
 * @param bool $refresh Refresh
 *
 * @return bool
 */
function wpIsCoreInstalled($refresh = false)
{
    if ($refresh || !has('wp_core_installed')) {
        set('wp_core_installed', wpTest('core is-installed'));
    }

    return get('wp_core_installed');
}

/**
 * Fetches WordPress config.
 *
 * @return array
 */
function wpFetchConfig()
{
    return \json_decode(wp('config list --json'), \JSON_OBJECT_AS_ARRAY);
}

/**
 * Refreshes WordPress config.
 */
function wpRefreshConfig()
{
    set('wp_config', wpFetchConfig());
}

/**
 * Returns WordPress config.
 *
 * @param bool $refresh (optional) Refresh
 *
 * @return array
 */
function wpGetConfig($refresh = false)
{
    if ($refresh || !has('wp_config')) {
        wpRefreshConfig();
    }

    return get('wp_config');
}

/**
 * Returns WordPress config constants.
 *
 * @param bool $refresh (optional) Refresh
 *
 * @return array
 */
function wpGetConstants($refresh = false)
{
    $constants = [];

    $config = wpGetConfig($refresh);

    foreach ($config as $value) {
        if ('constant' === $value['type']) {
            $constants[$value['name']] = $value['value'];
        }
    }

    return $constants;
}

/**
 * Returns WordPress plugins list.
 *
 * @return array
 */
function wpFetchPluginsList()
{
    $list = \json_decode(wp('plugin list --json'), \JSON_OBJECT_AS_ARRAY);

    $plugins = [];
    foreach ($list as $plugin) {
        $plugins[$plugin['name']] = $plugin;
    }

    return $plugins;
}

/**
 * Refreshes WordPress plugins list.
 */
function wpRefreshPluginsList()
{
    set('wp_plugins_list', wpFetchPluginsList());
}

/**
 * Returns installed WordPress plugins.
 *
 * @param bool $refresh (optional) Refresh plugin list
 *
 * @return array
 */
function wpGetPluginsList($refresh = false)
{
    if ($refresh || !has('wp_plugins_list')) {
        wpRefreshPluginsList();
    }

    return get('wp_plugins_list');
}

/**
 * Returns WordPress plugin status.
 *
 * @param string $plugin  Plugin name
 * @param bool   $refresh Refresh plugin list
 *
 * @return string Plugin status (e.g. 'active', 'not-installed')
 */
function wpGetPluginStatus($plugin, $refresh = false)
{
    $plugins = wpGetPluginsList($refresh);

    return $plugins[$plugin]['status'] ?? 'not-installed';
}

/**
 * Checks whether plugin is acvive.
 *
 * @param string $plugin  Plugin name (e.g. 'woocommerce')
 * @param bool   $refresh Refresh plugins list
 *
 * @return bool
 */
function wpIsPluginActive($plugin, $refresh = false)
{
    return 'active' === wpGetPluginStatus($plugin, $refresh);
}
