# Amazon Wish Lister

This package was inspired by and forked from [Justin Carpeti's Amazon Wish Lister](https://github.com/doitlikejustin/amazon-wish-lister).
However it was completely rewritten. Still a big thank you and credits go out to Justin.

## What it does

Amazon offers no API to export their user's wish lists. That's why we need a scraper to read the wish lists.
This Scraper accepts a wish list url and (after a few seconds of thinking about it) will return the scraped data:

* Item name
* Item link
* <del>Price of item when added to wish list</del>
* Current price of item
* Date added to wish list
* Priority (set by you)
* <del>Item rating</del>
* <del>Total ratings</del>
* Comments on item (set by you)
* Picture of item

## How to use

I recommend to include it via composer. e.g.:

    {
      "require": {
        "appzap/amazon-wish-lister": "dev-master",
      }
    }

The scraper itself requires phpQuery, which will be loaded automatically when using composer.

    $scraper = new \AppZap\AmazonWishLister\Scraper();
    $scrapedData = $scraper->scrape('http://www.amazon.de/registry/wishlist/69OVP6YE0HWO');

Have fun!