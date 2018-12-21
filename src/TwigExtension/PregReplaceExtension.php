<?php

namespace Drupal\node_transmit\TwigExtension;


/**
 * A test Twig extension that adds a custom function and a custom filter.
 */
class PregReplaceExtension extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   *
   * @return array
   *   A key/value array that defines custom Twig filters. The key denotes the
   *   filter name used in the tag, e.g.:
   *   @code
   *   {{ foo|testfilter }}
   *   @endcode
   *
   *   The value is a standard PHP callback that defines what the filter does.
   */
  public function getFilters() {
    return [
      'reg' => new \Twig_Filter_Function(['Drupal\node_transmit\TwigExtension\PregReplaceExtension', 'pregReplaceFilter']),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   A unique identifier for this Twig extension.
   */
  public function getName() {
    return 'node_transmit.preg_replace_extension';
  }

    /**
     * @param $subject
     * @param $pattern
     * @param $replacement
     * @return mixed
     */
  public static function pregReplaceFilter($subject, $pattern, $replacement) {
      return preg_replace($pattern, $replacement, $subject);
  }

}