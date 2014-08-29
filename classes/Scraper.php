<?php
namespace AppZap\AmazonWishLister;

class Scraper {

  const WISHLIST_VERSION_VERYOLD = 10;
  const WISHLIST_VERSION_OLD = 20;
  const WISHLIST_VERSION_08_2014 = 1409295586;

  /**
   * @var string
   */
  protected $baseUrl;

  /**
   * @var int
   */
  protected $wishlistVersion;

  public function scrape($url) {
    $parsedUrl = parse_url($url);
    $this->baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
    $scrapedItems = [];
    $mainPageContent = \phpQuery::newDocumentFile($url);
    $this->determineWishlistVersion($mainPageContent);

    $pagesSelector = [
        self::WISHLIST_VERSION_VERYOLD => '.pagDiv .pagPage',
        self::WISHLIST_VERSION_OLD => '#wishlistPagination li[data-action="pag-trigger"]',
    ];
    $pages = count($this->q($pagesSelector, $mainPageContent));
    if (empty($pages)) {
      $pages = 1;
    }

    $i = 0;
    for ($page_num = 1; $page_num <= $pages; $page_num++) {
      $pageContent = \phpQuery::newDocumentFile($url . '?page=' . $page_num);

      $itemsSelector = [
        self::WISHLIST_VERSION_VERYOLD => 'tbody.itemWrapper',
        self::WISHLIST_VERSION_OLD => '.g-items-section div[id^="item_"]',
      ];
      $items = $this->q($itemsSelector, $pageContent);
      foreach ($items as $item) {
        $item = $this->q($item, $pageContent);
        /** @var \phpQueryObject $item */
        $regular = $item->find('span.commentBlock nobr');
        if ($regular) {
          $scrapedItem = $this->scrapeItem($item);
          if ($scrapedItem['name'] && $scrapedItem['link']) {
            $scrapedItem['num'] = $i + 1;
            $scrapedItem['page'] = $page_num;
            $scrapedItems[] = $scrapedItem;
            $i++;
          }
        }
      }
    }
    return $scrapedItems;
  }

  /**
   * @param \phpQueryObject $item
   * @return array
   */
  protected function scrapeItem(\phpQueryObject $item) {
    $nameSelector = [
        self::WISHLIST_VERSION_VERYOLD => 'span.productTitle strong a',
        self::WISHLIST_VERSION_OLD => 'a[id^="itemName_"]',
    ];
    $name = $this->q($nameSelector, $item)->html();

    $linkSelector = [
        self::WISHLIST_VERSION_VERYOLD => 'span.productTitle a',
        self::WISHLIST_VERSION_OLD => 'a[id^="itemName_"]',
    ];
    $link = $this->q($linkSelector, $item)->attr('href');
    if (strpos($link, 'amazon.') === FALSE) {
      $link = $this->baseUrl . $link;
    }

    $oldPrice = $item->find('span.strikeprice')->html();

    $newPriceSelector = [
        self::WISHLIST_VERSION_VERYOLD => 'span.wlPriceBold strong',
        self::WISHLIST_VERSION_OLD => 'div.a-spacing-small div.a-row span.a-size-medium.a-color-price',
        self::WISHLIST_VERSION_08_2014 => 'div.price-section span',
    ];
    $newPrice = $this->q($newPriceSelector, $item)->html();
    if (strpos($newPrice, '-') !== FALSE) {
      $newPrice = explode('-', $newPrice)[0];
    }

    $dateAddedSelector = [
        self::WISHLIST_VERSION_VERYOLD => 'span.commentBlock nobr',
        self::WISHLIST_VERSION_OLD => 'div[id^="itemAction_"] .a-size-small',
    ];
    $dateAdded = explode('"', $this->q($dateAddedSelector, $item)->html())[1];

    $prioritySelector = [
      self::WISHLIST_VERSION_VERYOLD => 'span.priorityValueText',
      self::WISHLIST_VERSION_OLD => 'span[id^="itemPriorityLabel_"]',
    ];
    $priority = $this->q($prioritySelector, $item)->html();

    $rating = $item->find('span.asinReviewsSummary a span span')->html();

    $totalRatings = $this->q('span.crAvgStars a:nth-child(2)', $item)->html();

    $commentSelector = [
      self::WISHLIST_VERSION_VERYOLD => 'span.commentValueText',
      self::WISHLIST_VERSION_OLD => 'span[id^="itemComment_"]',
    ];
    $comment = $this->q($commentSelector, $item)->html();

    $pictureSelector = [
      self::WISHLIST_VERSION_VERYOLD => 'td.productImage a img',
      self::WISHLIST_VERSION_OLD => 'div[id^="itemImage_"] img',
    ];
    $picture = $this->q($pictureSelector, $item)->attr('src');

    $scrapedItem = [
      'name' => $name,
      'link' => $link,
      'old-price' => $oldPrice,
      'new-price' => $newPrice,
      'date-added' => $dateAdded,
      'priority' => $priority,
      'rating' => $rating,
      'total-ratings' => $totalRatings,
      'comment' => $comment,
      'picture' => $picture,
    ];
    return array_map('trim', $scrapedItem);
  }

  /**
   * @param \phpQueryObject $mainPageContent
   */
  protected function determineWishlistVersion(\phpQueryObject $mainPageContent) {
    if (count($this->q('#control-bar', $mainPageContent))) {
      $this->wishlistVersion = self::WISHLIST_VERSION_08_2014;
    } elseif (count($this->q('tbody.itemWrapper', $mainPageContent))) {
      $this->wishlistVersion = self::WISHLIST_VERSION_VERYOLD;
    } else {
      $this->wishlistVersion = self::WISHLIST_VERSION_OLD;
    }
  }

  /**
   * @param $selector
   * @return \phpQueryObject
   * @throws \Exception
   */
  protected function q($selector, $context) {
    if (is_array($selector)) {
      $versions = array_keys($selector);
      sort($versions, SORT_NUMERIC);
      $versions = array_reverse($versions);
      foreach ($versions as $version) {
        if ($version <= $this->wishlistVersion) {
          $selector = $selector[$version];
          break;
        }
      }
    }
    return \phpQuery::pq($selector, $context);
  }

}