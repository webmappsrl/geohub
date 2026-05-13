<?php

namespace App\Console\Concerns;

use Generator;

trait StreamsReerGeoJsonFeatures
{
    /**
     * Legge un FeatureCollection REER grande senza json_decode sull’intero file.
     *
     * @return Generator<int, array<string, mixed>>
     */
    protected function streamReerGeoJsonFeatures(string $path): Generator
    {
        $fp = fopen($path, 'rb');
        if (! $fp) {
            throw new \RuntimeException("Impossibile aprire il file: {$path}");
        }

        $buffer = '';
        $inFeaturesArray = false;
        $inString = false;
        $escape = false;
        $depth = 0;
        $collecting = false;
        $obj = '';

        while (! feof($fp)) {
            $chunk = fread($fp, 1024 * 1024);
            if ($chunk === false) {
                break;
            }
            $buffer .= $chunk;

            $len = strlen($buffer);
            for ($i = 0; $i < $len; $i++) {
                $ch = $buffer[$i];

                if (! $inFeaturesArray) {
                    if (substr($buffer, $i, 10) === '"features"') {
                        $posBracket = strpos($buffer, '[', $i);
                        if ($posBracket !== false) {
                            $inFeaturesArray = true;
                            $i = $posBracket;
                        }
                    }

                    continue;
                }

                if ($collecting) {
                    $obj .= $ch;
                }

                if ($escape) {
                    $escape = false;

                    continue;
                }
                if ($ch === '\\') {
                    if ($inString) {
                        $escape = true;
                    }

                    continue;
                }
                if ($ch === '"') {
                    $inString = ! $inString;

                    continue;
                }
                if ($inString) {
                    continue;
                }

                if (! $collecting) {
                    if ($ch === '{') {
                        $collecting = true;
                        $depth = 1;
                        $obj = '{';
                    } elseif ($ch === ']') {
                        fclose($fp);

                        return;
                    }

                    continue;
                }

                if ($ch === '{') {
                    $depth++;
                } elseif ($ch === '}') {
                    $depth--;
                    if ($depth === 0) {
                        $feature = json_decode($obj, true);
                        if (is_array($feature)) {
                            yield $feature;
                        }
                        $collecting = false;
                        $obj = '';
                    }
                }
            }

            $buffer = $collecting ? '' : substr($buffer, max(0, $len - 1024));
        }

        fclose($fp);
    }
}
