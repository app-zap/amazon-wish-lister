<?php
namespace AppZap\AmazonWishLister;

class Scraper {

  public function scrape($url) {
    $content = \phpQuery::newDocumentFile($url);
    $count = count(\phpQuery::pq('tbody.itemWrapper', $content));
    $count = strtolower($count);
  }

}