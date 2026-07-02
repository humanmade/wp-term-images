# WP Term Images

[Forked from stuttter/wp-term-images](https://github.com/stuttter/wp-term-images)

Images for categories, tags, and other taxonomy terms.

WP Term Images allows users to assign images to any visible category, tag, or taxonomy term using the media library, providing a customized look for their taxonomy terms.

# Installation

* Download and install using the built in WordPress plugin installer.
* Activate in the "Plugins" area of your admin by clicking the "Activate" link.
* No further setup or configuration is necessary.

# FAQ

### Does this plugin depend on any others?

Not since WordPress 4.4.

### Does this create new database tables?

No. There are no new database tables with this plugin.

### Does this modify existing database tables?

No. All of WordPress's core database tables remain untouched.

### How do I get the image for a term?

With WordPress's `get_term_meta()` function

```
// image id is stored as term meta
$image_id = get_term_meta( 7, 'image', true );

// image data stored in array, second argument is which image size to retrieve
$image_data = wp_get_attachment_image_src( $image_id, 'full' );

// image url is the first item in the array (aka 0)
$image = $image_data[0];

if ( ! empty( $image ) ) {
	echo '<img src="' . esc_url( $image ) . '" />';
}
```

### How do I bind a term's image to an Image block?

For WordPress 6.7+, plugin registers a [Block Bindings](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/) source `wp-term-images/term-image` that can bind properties on the core Image block to properties of the image assigned to a term. Rendering is done on frontend render; the editor shows that the image is bound but as of 7.0 does not preview any term image properties.

The source answers whichever Image attribute you bind:

Attribute | Value
---- | ----
`url` | The attachment URL, **required** in order for the image to display.
`alt` | The attachment alt text.
`title` | The attachment title.
`id` | The attachment ID. Updates the `wp-image-{id}` class only; it does not change `src`, so `url` must be bound for the image to display.

The term is taken from the `termId` arg when given. When the `termId` argument is not provided, the binding will use the associated image when on a taxonomy archive for a term that has an image assigned.

Optional args: `termId` (defaults to the queried term) and `size` (image size for `url`, defaults to `full`).

When `url` is bound, the plugin also adds the `wp-image-{id}` class and computes responsive `srcset`/`sizes` for the term's image, so the bound image renders with the same responsive markup as a natively-inserted one. Block Bindings replace scalar attributes only, so this is done in a `render_block_core/image` filter rather than through the binding itself.

**Post content** — pin a specific term:

```html
<!-- wp:image {"metadata":{"bindings":{
	"url":{"source":"wp-term-images/term-image","args":{"termId":7,"size":"large"}},
	"alt":{"source":"wp-term-images/term-image","args":{"termId":7}}
}}} -->
<figure class="wp-block-image"><img src="" alt=""/></figure>
<!-- /wp:image -->
```

**Template** — bind to the current term on an archive (no `termId`):

```html
<!-- wp:image {"metadata":{"bindings":{
	"url":{"source":"wp-term-images/term-image"},
	"alt":{"source":"wp-term-images/term-image"}
}}} -->
<figure class="wp-block-image"><img src="" alt=""/></figure>
<!-- /wp:image -->
```

## Local Environment

This project uses [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) to run a lightweight, containerized WordPress instance at [localhost:3003](http://localhost:3003) for testing purposes. The default username for the localhost environment is `admin`, with the password `password`.

These commands can be used to interact with the environment:

Command | Purpose
---- | ----
`npm run env:start` | Start the local environment at http://localhost:3003
`npm run env:stop` | Turn off the local environment
`npm run env:cli -- wp ...` | Run WP-CLI commands within the environment
`npm run env:logs` | Open (and tail) the error logs for the application<sup>&ddagger;</sup>
`npm run env:db` | Open the database in the mysql command line
`npm run env:destroy` | Fully destroy the local environment (deletes container database)

<sup>&ddagger;</sup> This command deliberately filters out GET/OPTIONS/HEAD/POST/PUT access log entries

## Release Process

> [!NOTE]
> This plugin is currently distributed via Composer as `humanmade/wp-term-images`, not via the plugins repository.

Merges to `main` automatically [build](https://github.com/humanmade/wp-term-images/actions/workflows/build-release-branch.yml) to the `release` branch. A project may track the `release` branch using [Composer](https://getcomposer.org/) to pull in the latest built beta version.

Commits on the `release` branch may be tagged for installation via [Packagist](https://packagist.org/packages/humanmade/wp-term-images) and marked as releases in GitHub for manual download, using a manually-dispatched ["Tag and Release" GH Actions workflow](https://github.com/humanmade/wp-term-images/actions/workflows/tag-and-release.yml).

To tag a new release:

1. Choose the target version number using [semantic versioning](https://semver.org/).
2. Check out a `prepare-v#.#.#` branch.
3. Bump the `Version` in the [wp-term-images.php](./wp-term-images.php) PHPDoc header.
4. Update the changelog entries in [readme.txt](./readme.txt).
5. Open a pull request titled "Prepare release v#.#.#".
6. Review and merge the "Prepare release" pull request.
7. Wait for the `release` branch to [update](https://github.com/humanmade/wp-term-images/actions/workflows/build-release-branch.yml) with the build that includes the new version number.
8. On the ["Tag and Release" GH Action page](https://github.com/humanmade/wp-term-images/actions/workflows/tag-and-release.yml):
   - Click "Run workflow" in the `workflow_dispatch` banner.
   - Fill out the "Version tag" field with your target version number. This must match the `Version` in `wp-term-images.php`. Use the format `v#.#.#`.
   - Click "Run workflow" to apply the specified tag to the `release` branch.

Once the workflow completes, the new version is [tagged](https://github.com/humanmade/wp-term-images/tags) and listed in [releases](https://github.com/humanmade/wp-term-images/releases).

### Where can I get support?

* Basic: https://wordpress.org/support/plugin/wp-term-images/
* Priority: https://chat.flox.io/support/channels/wp-term-images/

### Can I contribute?

Yes, please! The number of users needing more robust taxonomy visuals is growing fast. Having an easy-to-use UI and powerful set of functions is critical to managing complex WordPress installations. If this is your thing, please help us out!
