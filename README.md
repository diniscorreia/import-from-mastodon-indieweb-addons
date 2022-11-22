# Import From Mastodon IndieWeb Addons
This plugin makes [Jan Boddez](https://github.com/janboddez/)’s [Import From Mastodon](https://github.com/janboddez/import-from-mastodon) WordPress plugin play nicely with the [Post Kinds](https://github.com/dshanske/indieweb-post-kinds) and [Syndication Links](https://github.com/dshanske/syndication-links) [IndieWeb](https://indieweb.org/) plugins.

## What does it do?
— Adds a syndication link to a toot or reply when it’s imported
— Sets the correct posts kind for favourites (“likes”), boosts (“reposts”) and replies
— Saves microformat data for each post kind
— Clean up the post content (because we are storing the microformat data, when can use Post Kinds to display it instead of having it on the content)

## Some opinionated choices
— The content of boosts and favourites is removed, only a link and some meta data on the toot is kept (author, date, etc.)
— Titles are removed as well, as it seems unnecessary for such short content
— Because of the above, some posts end up having no title or content, just the microformat data stored in the post meta
— Replies to your own toots are also removed; ideally these should be backfed to your site and published as comments on the original post (using something like [Brid.gy](https://brid.gy/), for instance)

## Installation
Please bear in mind this was written for personal use and there might be errors. For this to work you’ll obviously need the [Import From Mastodon](https://github.com/janboddez/import-from-mastodon), [Post Kinds](https://github.com/dshanske/indieweb-post-kinds) and [Syndication Links](https://github.com/dshanske/syndication-links) plugins.

Just download the [ZIP file](https://github.com/janboddez/import-from-mastodon/archive/refs/heads/master.zip), upload to `wp-content/mu-plugins` (if you don’t see a `mu-plugins` folder you can just create it) and unzip.

That’s it! Since this is a [Must Use Plugin](https://wordpress.org/support/article/must-use-plugins/) it will be automatically enabled. 

## Acknowledgements 
Huge thanks to [Jan Boddez](https://github.com/janboddez/), who built the Import From Mastodon plugin, provided snippets and pointed me in the right direction.
