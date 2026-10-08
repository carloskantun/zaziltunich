<?php
declare(strict_types=1);

namespace App\Domain;

/** Recupera las columnas de los bloques importados sin alterar su texto ni la base de datos. */
final class ContentLayout
{
    public static function columns(string $html): string
    {
        if (!str_contains($html, 'class="sec"')) {
            return $html;
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<!doctype html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>');
            $xpath = new \DOMXPath($dom);
            foreach ($xpath->query('//section[contains(concat(" ", normalize-space(@class), " "), " sec ")]') as $section) {
                $media = null;
                $mediaCount = 0;
                $hasCopy = false;
                foreach ($section->childNodes as $child) {
                    if (!$child instanceof \DOMElement) {
                        continue;
                    }
                    if (in_array('slider', explode(' ', $child->getAttribute('class')), true)) {
                        $media = $child;
                        $mediaCount++;
                    } elseif ($child->tagName === 'p' && trim($child->textContent) !== '') {
                        $hasCopy = true;
                    }
                }
                if ($mediaCount !== 1 || !$media || !$hasCopy) {
                    continue;
                }
                $split = $dom->createElement('div');
                $split->setAttribute('class', 'split');
                $picture = $dom->createElement('div');
                $picture->setAttribute('class', 'split-media');
                $copy = $dom->createElement('div');
                $copy->setAttribute('class', 'split-copy');
                $picture->appendChild($media);
                while ($section->firstChild) {
                    $copy->appendChild($section->firstChild);
                }
                $split->appendChild($picture);
                $split->appendChild($copy);
                $section->appendChild($split);
            }
            $result = '';
            foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
                $result .= $dom->saveHTML($node);
            }
            return $result;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
