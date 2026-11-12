# WordPress Playground preview

Every pull request gets a "Test the build" comment with a [WordPress Playground](https://wordpress.org/playground/) link, which runs WordPress in the visitor's browser with the build of that pull request, so a reviewer needs no site of their own.

The link is built in `.github/workflows/pr-build-live-branch.yml`. It is a Blueprint that installs WooCommerce, downloads one workflow artifact holding the plugin zip and `setup.php` from this directory, installs the zip and runs `setup.php`.

The store opens on WooCommerce > Settings > Sift for WooCommerce, logged in as an administrator. It has one product ("Playground test product"), cash on delivery, a "Shortcode Checkout" page next to the Blocks "Checkout" page, and every event the settings screen offers switched on.

## What the preview cannot do

- **Send events to Sift, or get decisions back.** The store has no Sift keys. Events are queued and stay under WooCommerce > Status > Scheduled Actions > Pending, because Playground switches WP-Cron off. "Run" on a queued event builds its full payload and writes it to the `sift-for-woocommerce` log as a failed send.
- **Receive decision webhooks.** Nothing on the internet can reach a site that runs in a browser.
- **Show payment method details.** Stripe, WooPayments and PayPal are not installed, so order events have none.

To test against Sift, save the keys of a Sift sandbox account in a site of your own, such as the wp-env one from `DEVELOPMENT.md`.

## Why there are no Sift keys

Playground downloads the build through its artifact proxy, which picks the build by branch name. A pull request from a fork whose branch has the same name can therefore have its build served by the link, and the code that runs in a preview can read what is typed into it.

So the Blueprint and `setup.php` MUST NOT contain Sift keys, the comment never asks for them, and a preview is not the place to enter real ones.

## Changing the preview

`setup.php` is copied from the pull request's own checkout into the artifact, so a pull request that changes it changes its own preview. It calls `get_sift_for_woocommerce_events_settings()` to find the events to switch on.

The workflow builds the plugin zip from the checkout and leaves out `bin`, `tests` and the other development files, so nothing in this directory is in it.
