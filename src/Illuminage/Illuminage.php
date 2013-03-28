<?php
namespace Illuminage;

use App;
use Exception;
use Illuminate\Config\Repository as Config;
use Illuminate\Routing\UrlGenerator;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;

/**
 * Handles image creation and caching
 */
class Illuminage
{

  /**
   * The UrlGenerator instance
   *
   * @var UrlGenerator
   */
  protected $url;

  /**
   * The Imagine instance
   *
   * @var Imagine
   */
  protected $imagine;

  /**
   * The Cache instance
   *
   * @var Cache
   */
  protected $cache;

  /**
   * The Config instance
   *
   * @var Config
   */
  protected $config;

  /**
   * Set up Illuminage
   *
   * @param Cache        $cache
   * @param UrlGenerator $url
   * @param Imagine      $imagine
   */
  public function __construct(Config $config, Cache $cache, UrlGenerator $url, $imagine)
  {
    $this->config  = $config;
    $this->cache   = $cache;
    $this->imagine = $imagine;
    $this->url     = $url;
  }

  /**
   * Bind an Imagine image to an Image
   *
   * @param Image $image
   *
   * @return Imagine
   */
  public function bindImagine(Image $image)
  {
    $imagine = $this->imagine->open($this->getPathOf($image));

    // Crop if Thumb
    if ($image instanceof Thumb) {
      $mode = ImageInterface::THUMBNAIL_OUTBOUND;
      $box  = new Box($image->getSalt('width'), $image->getSalt('height'));

      $imagine = $imagine->thumbnail($box, $mode);
    }

    return $imagine;
  }

  /**
   * Renders the final image
   *
   * @param image $image
   *
   * @return string Path to the generated image
   */
  public function cacheAndRender(Image $image)
  {
    // If the image is in cache, return it
    if ($this->cache->isCached($image)) {
      return $this->getUrlTo($image);
    }

    // Save the image
    $image
      ->getImagine()
      ->save($this->cache->getCachePathOf($image));

    return $this->getUrlTo($image);
  }

  ////////////////////////////////////////////////////////////////////
  ////////////////////////////// HELPERS /////////////////////////////
  ////////////////////////////////////////////////////////////////////

  /**
   * Get the path of an image
   *
   * @param Image $image
   *
   * @return string
   */
  protected function getPathOf(Image $image)
  {
    $path = $image->getImagePath();
    if (!file_exists($path)) {
      throw new Exception('The image '.$path. ' does not exist');
    }

    return $path;
  }

  /**
   * Get the URL to an image
   *
   * @param image $image
   *
   * @return string
   */
  protected function getUrlTo(Image $image)
  {
    $cache = $this->config->get('illuminage::cache_folder');

    return $this->url->asset($cache.$this->cache->getHashOf($image));
  }

  /**
   * Get the cache folder
   *
   * @return string
   */
  public function getCacheFolder()
  {
    return App::make('path.public').'/'.$this->config->get('illuminage::cache_folder');
  }

}
