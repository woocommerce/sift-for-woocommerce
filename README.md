# Sift for WooCommerce

This plugin will integrate with Sift Science's fraud detection suite and WooCommerce's decisions API.

## Folder Structure

This plugin has the following folder structure:

```ignorelang
sift-for-woocommerce/
├── languages/
├── src/
│   ├ ...
│   └── inc/
└── sift-for-woocommerce.php
```

- languages: Contains the translation files for your plugin.
- src: A folder for organizing the plugin's main PHP classes or code components, such as integrations with other plugins or services. These classes should be organized into subfolders following the [PSR-4](https://www.php-fig.org/psr/psr-4/) convention. `Composer` will handle the autoloading for these classes.
- plugin-name.php: The main PHP file containing the plugin header and bootstraping functionality.

## Documentation

As you develop your plugin, update the README.md file with detailed information about your plugin's features, usage, installation, and any other pertinent information.

## Decisions API

Sift should be set to send decisions webhooks towards this endpoint:
`GET /sift-for-woocommerce/v1/decision/`

You will also need to set a `Signature Key` in your Sift console and save it in your WooCommerce settings under `Sift for WooCommerce > Sift Signature / Webhook Key`.

## REST API

### Routes
Get the latest decision applied to a user in wocommerce.  The decision is retrieved from user_meta.  
`GET /sift-for-woocommerce/v1/fraud/users/{$woocommerce_user_id}/decision`

Manually apply a decision applied to a user in wocommerce:  
`POST /sift-for-woocommerce/v1/fraud/users/{$woocommerce_user_id}/decision`

If a decision is applied, it is sent to sift and stored locally in user_meta.
Three body parameters are expected:
 - `decision_id`: REQUIRED: The decision to be applied to the user
 - `description` A description of the change which will be logged and sent to sift.
 - `analyst`:  The username of the user making the request which will be logged and sent to sift.

### Authentication
API Requests must contain a valid `authorization` header.  Use the following to generate the hash:  
`hash_hmac( 'sha256', "{$endpoint}|{$time}", $api_secret )`

#### The following .env var is used as the `$api_secret` in the hash above:  
`SIFT_FOR_WOOCOMMERCE_API_SECRET`

## Local Development

1. `npm install`
2. `composer install`
3. `npm start`
4. See `Test with WooPayments or any other gateway` to test


### Run unit tests

Once the local environment is up, simply launch 

1.`npm test`

You can select a test with:

1.`npm test -- --filter=SOMETEST`

### Test with WooPayments or any other gateway

1. Add a link to the gateway in `.wp-env.json` in the "plugins" list (WooPayments is provided by default as well as a dummy Simple_Test_Gateway)
2. Start ngrok with "ngrok http 80 --host-header=rewrite" and grab the new address in `https://0000-00-00-00.ngrok-free.app`
3. Copy the .wp-env.json file and paste it as .wp-env.override.json. Modify WP_DOMAIN, WP_SITEURL and WP_HOME with the new URL 0000-00-00-00.ngrok-free.app. You can leave the other values as they are, or if you are comfortable with editing JSON, remove everything aside from the "config" key and the three modified keys within.
4. Run `npm start` to reload the config
5. Go to `https://0000-00-00-00.ngrok-free.app/wp-admin/` (don't miss the last /)
6. Setup WooCommerce 
7. Set up your gateway
8. (optional) for WooPayments, you can use the Sandbox mode in the setup with "I'm setting up a store for someone else."

Note: Remember to modify the URLs and run a `npm start` everytime you restart ngrok.

### Alternative Testing

You can run the tests using the local PHP environment by running the database on docker.

1. `docker run -d --rm -p 3306:3306 -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=wordpress_tests --name db mariadb`
2. `bin/install-wp-tests.sh wordpress_tests root root 127.0.0.1 latest true`
3. Install woocommerce somewhere on your machine and set the WOO_TEST_DIR environment variable to point to it.
4. `WOO_TEST_DIR=~/.wp-env/d4a3fd8a8a0a78829498afef8ce99c2c/woocommerce vendor/bin/phpunit`

### Using XDEBUG

Start environment with XDEBUG enabled: `npx wp-env start --xdebug`.  Configure your IDE to listen for XDEBUG connections on port 9003. When you browse in the browser, XDEBUG should connect to your IDE.

To get tests working with XDEBUG, it requires a little more work.  Configure your IDE server name to be something like `XDEBUG_OMATTIC` and then launch the tests by running `npx wp-env run tests-cli --env-cwd=wp-content/plugins/sift-for-woocommerce bash`. At the new prompt you need to run: `PHP_IDE_CONFIG=serverName=XDEBUG_OMATTIC vendor/bin/phpunit`.

### Troubleshooting

#### `Error response from daemon: error while creating mount source path`

Restart docker.

### Pagination issues (page links different than site)

Add this line (substituting your own public URL) to your `wp-config.php`:

`$_SERVER['HTTP_HOST'] = '0000-00-00-00.ngrok-free.app';`

### Modify a variable in wp-config

`wp-env run cli wp config set JETPACK_DEV_DEBUG false --raw`

### Access PHP error

1. `wp-env run cli bash`
2. `tail -f /var/www/html/wp-content/debug.log`
