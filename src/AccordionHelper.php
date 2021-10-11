<?php

namespace Drupal\utexas_migrate;

use Drupal\Core\Database\Database;

/**
 * Helper functions for migration.
 */
class AccordionHelper {

  public static function appendHTML(DOMNode $parent, $source) {
      $tmpDoc = new DOMDocument();
      $tmpDoc->loadHTML($source);
      foreach ($tmpDoc->getElementsByTagName('body')->item(0)->childNodes as $node) {
          $node = $parent->ownerDocument->importNode($node, true);
          $parent->appendChild($node);
      }
  }

  public static function DOMinnerHTML(DOMNode $element) { 
    $innerHTML = ""; 
    $children  = $element->childNodes;
    foreach ($children as $child) { 
      $innerHTML .= $element->ownerDocument->saveHTML($child);
    }
    return $innerHTML;
  }

  public static function convertFoundationAccordion($text) {
    $original = $text;
    // LibXML requires that the html is wrapped in a root node.
    $text = '<root>' . $text . '</root>';
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = FALSE;
    libxml_use_internal_errors(TRUE);
    $dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $uls = $dom->getElementsByTagName('ul');
    if ($uls->length === 0) {
      return $original;
    }
    foreach ($uls as $ul) {
      $classes = $ul->getAttribute('class');
      if (strpos($classes, 'accordion') === FALSE) {
        continue;
      }
      $lis = $ul->getElementsByTagName('li');
      if ($lis->length === 0) {
        continue;
      }
      $convert = TRUE;
      $content = '';
      $titleObjects = [];
      foreach ($lis as $key => $li) {
        if (!$convert) {
          continue;
        }
        $classes = $li->getAttribute('class');
        if (strpos($classes, 'accordion-navigation') === FALSE) {
          continue;
        }
        $titleObjects[$key] = $li->getElementsByTagName('a');
        $href = $titleObjects[$key][0]->getAttribute('href');
        $href = trim($href, '#');
        if (!isset($href)) {
          continue;
        }
        $body = $dom->getElementById($href);
        if ($body && $titleObjects[$key][0]) {
          if (isset($body->nodeValue) && isset($titleObjects[$key][0]->nodeValue)) {
            $content .= '<details><summary>' . $titleObjects[$key][0]->nodeValue . '</summary>' . self::DOMInnerHTML($body) . '</details>';
          }
          else {
            $convert = FALSE;
          }
        }
        else {
          $convert = FALSE;
        }
      }
      if ($convert) {
        $elem = $dom->createElement('div');
        self::appendHTML($elem, $content);
        $ul->parentNode->replaceChild($elem, $ul);
      }
    }
    // Get innerHTML of root node.
    $html = "";
    foreach ($dom->getElementsByTagName('root')->item(0)->childNodes as $child) {
      // Re-serialize the HTML.
      $html .= $dom->saveHTML($child);
    }
    return $html;
  }

}
