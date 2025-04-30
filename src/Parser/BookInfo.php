<?php

namespace Tizis\FB2\Parser;

use DiDom\Element;
use Tizis\FB2\Model\BookInfo as BookInfoModel;

/**
 * Class BookInfo
 * @package FB2\Parser
 */
class BookInfo extends Parser
{
  /**
   * BookInfo constructor.
   * @param $element
   */
  public function __construct(Element $element)
  {
    $this->setXmlElement($element);
    $this->setModel(new BookInfoModel());
  }

  /**
   * @return BookInfoModel
   */
  public function parse(): BookInfoModel
  {
    $this->parseTitle();
    $this->parseAnnotation();
    $this->parseLang();
    $this->parseGenres();
    $this->parseKeywords();
    $this->parseSequences(); // Добавляем парсинг серий
    $this->parsePublishDate(); // Добавляем парсинг даты издания
    return $this->getModel();
  }

  /**
   * set title
   */
  private function parseTitle(): void
  {
    $bookTitleNode = $this->getXmlElement()->first('book-title');
    $bookTitle = $bookTitleNode && $bookTitleNode->text() ? trim($bookTitleNode->text()) : '';
    $this->getModel()->setTitle($bookTitle);
  }

  /**
   * @return BookInfoModel
   */
  public function getModel(): BookInfoModel
  {
    return $this->model;
  }

  /**
   * set annotation
   */
  private function parseAnnotation(): void
  {
    $bookAnnotationNode = $this->getXmlElement()->first('annotation');
    $bookAnnotation = $bookAnnotationNode && $bookAnnotationNode->html() ? trim(strip_tags($bookAnnotationNode->innerHtml(), '<p>')) : '';
    $this->getModel()->setAnnotation($bookAnnotation);
  }

  /**
   * set lang
   */
  private function parseLang(): void
  {
    $xmlDOM = $this->getXmlElement();
    $model = $this->getModel();
    // nodes
    $langNode = $xmlDOM->first('lang');
    $srcLangNode = $xmlDOM->first('src-lang');
    // current lang && original lang
    $lang['lang'] = $langNode && $langNode->text() ? trim($langNode->text()) : null;
    $lang['src'] = $srcLangNode && $srcLangNode->text() ? trim($srcLangNode->text()) : null;
    // set lang
    $model->setLang($lang);
  }

  /**
   * set genres
   */
  private function parseGenres(): void
  {
    $items = (array)$this->getXmlElement()->find('genre');
    $genres = [];
    $model = $this->getModel();
    foreach ($items as $item) {
      $item = trim($item->text());
      if (!empty($item)) {
        $genres[] = $item;
      }
    }
    $model->setGenres($genres);
  }

  /**
   * set keywords
   */
  private function parseKeywords(): void
  {
    $item = $this->getXmlElement()->first('keywords');
    if ($item && $item->text()) {
      $this->getModel()->setKeywords(trim($item->text()));
    }
  }
  /**
   * set sequences
   */
  private function parseSequences(): void
  {
    $items = (array)$this->getXmlElement()->find('sequence');
    $sequences = [];
    $model = $this->getModel();

    foreach ($items as $item) {
        $name = $item->attr('name') ?? null;
        $number = $item->attr('number') ?? null;

        if ($name) {
            $sequences[] = [
                'name' => $name,
                'number' => $number
            ];
        }
    }

    $model->setSequences($sequences);
  }

      /**
     * Получить дату издания
     * @return string|null
     */
    public function getPublishDate(): ?string
    {
        return $this->publishDate;
    }

    private function parsePublishDate(): void
    {
      $dateNodes = $this->getXmlElement()->find('date');

      foreach ($dateNodes as $dateNode) {
          $dateValue = $dateNode->attr('value');
          $dateText = trim($dateNode->text());
  
          if (!empty($dateValue)) {
              $this->getModel()->setPublishDate($dateValue);
              return; // Используем первый корректный <date>
          } elseif (!empty($dateText)) {
              $this->getModel()->setPublishDate($dateText);
              return; // Используем первый корректный <date>
          }
      }
  
      // Если ни один <date> не корректен
      $this->getModel()->setPublishDate(null);
    }
}