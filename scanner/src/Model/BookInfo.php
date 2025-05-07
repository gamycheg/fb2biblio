<?php

namespace Tizis\FB2\Model;
/**
 * Class BookInfo
 * @package FB2\Model
 */
class BookInfo implements IModel
{
  /**
   * @var string
   */
  private $title = '';
  /**
   * @var string
   */
  private $annotation = '';
  /**
  * @var string|null
  */
  private $publishDate;

  /**
   * @var array
   */
  private $genres = [];
  /**
   * @var string
   */
  private $keywords = '';
  /**
   * @var array
   */
  private $lang = [
    'lang' => null,
    'src' => null
  ];
      /**
     * @var array
     */
    private $sequences = [];

  /**
   * @return array
   */
  public function getLang(): array
  {
    return $this->lang;
  }

  /**
   * @param array $values
   */
  public function setLang(array $values): void
  {
    $this->lang = $values;
  }


  /**
   * @return string
   */
  public function getKeywords(): string
  {
    return $this->keywords;
  }

  /**
   * @param $values
   */
  public function setKeywords(string $values): void
  {
    $this->keywords = $values;
  }


  /**
   * @return array
   */
  public function getGenres(): array
  {
    return $this->genres;
  }

  /**
   * @param array $values
   */
  public function setGenres(array $values): void
  {
    $this->genres = $values;
  }

  /**
   * @return string
   */
  public function getAnnotation(): string
  {
    return $this->annotation;
  }

  /**
   * @param string $value
   */
  public function setAnnotation(string $value): void
  {
    $this->annotation = $value;
  }


  /**
   * @return string
   */
  public function getTitle(): string
  {
    return $this->title;
  }

  /**
   * @param string $value
   */
  public function setTitle(string $value): void
  {
    $this->title = $value;
  }
    /**
     * Установить серии книги
     * @param array $sequences
     */
    public function setSequences(array $sequences): void
    {
        $this->sequences = $sequences;
    }

    /**
     * Получить серии книги
     * @return array
     */
    public function getSequences(): array
    {
        return $this->sequences;
    }

        /**
     * Установить дату издания
     * @param string|null $date
     */
    public function setPublishDate(?string $date): void
    {
        $this->publishDate = $date;
    }

    /**
     * Получить дату издания
     * @return string|null
     */
    public function getPublishDate(): ?string
    {
        return $this->publishDate;
    }
}