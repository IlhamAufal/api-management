<?php

namespace App\Libraries;

/**
 * Parser Markdown ringan (subset GFM) tanpa dependensi eksternal —
 * dipakai untuk menampilkan dokumen tutorial (docs/*.md) di halaman
 * form. Output selalu HTML yang sudah di-escape; hanya tag aman
 * yang dihasilkan (heading, paragraf, list, tabel, code fence,
 * blockquote, hr, bold, inline code, link http/https).
 */
class MarkdownLite
{
    /**
     * Konversi Markdown ke HTML.
     */
    public static function toHtml(string $markdown): string
    {
        $lines = preg_split('/\R/', str_replace("\r\n", "\n", $markdown)) ?: [];
        $count = count($lines);
        $html  = [];
        $i     = 0;

        while ($i < $count) {
            $line = $lines[$i];

            // Code fence: ```lang ... ```
            if (preg_match('/^```(\w*)\s*$/', $line, $m)) {
                $lang = $m[1];
                $code = [];
                $i++;

                while ($i < $count && ! preg_match('/^```\s*$/', $lines[$i])) {
                    $code[] = $lines[$i];
                    $i++;
                }

                $i++; // tutup fence
                $attr = $lang !== ''
                    ? ' class="language-' . htmlspecialchars($lang, ENT_QUOTES) . '"'
                    : '';

                $html[] = '<pre class="tutorial-pre"><code' . $attr . '>'
                    . htmlspecialchars(implode("\n", $code), ENT_QUOTES)
                    . '</code></pre>';

                continue;
            }

            // Heading: # s/d ######
            if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
                $level  = strlen($m[1]);
                $html[] = '<h' . $level . ' class="tutorial-h">' . self::inline($m[2]) . '</h' . $level . '>';
                $i++;

                continue;
            }

            // Horizontal rule: --- (pemisah section)
            if (preg_match('/^-{3,}\s*$/', $line)) {
                $html[] = '<hr class="tutorial-hr" />';
                $i++;

                continue;
            }

            // Tabel: baris header ber-pipe + baris separator
            if (strpos($line, '|') !== false
                && $i + 1 < $count
                && preg_match('/^\s*\|?[\s:\-|]+\|[\s:\-|]*$/', $lines[$i + 1])
            ) {
                $header = self::splitRow($line);
                $i     += 2;
                $rows   = [];

                while ($i < $count && trim($lines[$i]) !== '' && strpos($lines[$i], '|') !== false) {
                    $rows[] = self::splitRow($lines[$i]);
                    $i++;
                }

                $html[] = self::table($header, $rows);

                continue;
            }

            // Blockquote: > ...
            if (preg_match('/^>\s?(.*)$/', $line, $m)) {
                $quote = [$m[1]];
                $i++;

                while ($i < $count && preg_match('/^>\s?(.*)$/', $lines[$i], $m)) {
                    $quote[] = $m[1];
                    $i++;
                }

                $html[] = '<blockquote class="tutorial-quote">'
                    . self::inline(implode(' ', $quote))
                    . '</blockquote>';

                continue;
            }

            // List bernomor: 1. ...
            if (preg_match('/^\s*\d+\.\s+(.*)$/', $line)) {
                $items = [];

                while ($i < $count && preg_match('/^\s*\d+\.\s+(.*)$/', $lines[$i], $m)) {
                    $items[] = '<li>' . self::inline($m[1]) . '</li>';
                    $i++;
                }

                $html[] = '<ol class="tutorial-list">' . implode('', $items) . '</ol>';

                continue;
            }

            // List biasa / checklist: - item | - [ ] item | - [x] item
            if (preg_match('/^\s*[-*]\s+(.*)$/', $line)) {
                $items = [];

                while ($i < $count && preg_match('/^\s*[-*]\s+(.*)$/', $lines[$i], $m)) {
                    $text = $m[1];

                    if (preg_match('/^\[([ xX])\]\s+(.*)$/', $text, $c)) {
                        $checked = strtolower($c[1]) === 'x' ? ' checked' : '';
                        $items[] = '<li class="tutorial-check">'
                            . '<input type="checkbox" disabled' . $checked . ' /> '
                            . '<span>' . self::inline($c[2]) . '</span></li>';
                    } else {
                        $items[] = '<li>' . self::inline($text) . '</li>';
                    }

                    $i++;
                }

                $html[] = '<ul class="tutorial-list">' . implode('', $items) . '</ul>';

                continue;
            }

            // Baris kosong
            if (trim($line) === '') {
                $i++;

                continue;
            }

            // Paragraf: kumpulkan sampai baris kosong / blok lain
            $para = [$line];
            $i++;

            while ($i < $count
                && trim($lines[$i]) !== ''
                && ! preg_match('/^(#{1,6}\s|```|>\s?|\s*\d+\.\s|\s*[-*]\s|-{3,}\s*$)/', $lines[$i])
                && ! self::isTableHeader($lines, $i)
            ) {
                $para[] = $lines[$i];
                $i++;
            }

            $html[] = '<p class="tutorial-p">' . self::inline(implode(' ', $para)) . '</p>';
        }

        return implode("\n", $html);
    }

    /**
     * Apakah baris ini header tabel (baris ini + baris berikutnya separator)?
     *
     * @param string[] $lines
     */
    private static function isTableHeader(array $lines, int $i): bool
    {
        return isset($lines[$i], $lines[$i + 1])
            && strpos($lines[$i], '|') !== false
            && preg_match('/^\s*\|?[\s:\-|]+\|[\s:\-|]*$/', $lines[$i + 1]) === 1;
    }

    /**
     * Pecah satu baris tabel jadi sel-sel mentah (tanpa escape).
     *
     * @return string[]
     */
    private static function splitRow(string $row): array
    {
        $row = trim($row);

        if (strpos($row, '|') === 0) {
            $row = substr($row, 1);
        }

        if (substr($row, -1) === '|') {
            $row = substr($row, 0, -1);
        }

        $cells = [];

        foreach (explode('|', $row) as $cell) {
            $cells[] = trim($cell);
        }

        return $cells;
    }

    /**
     * @param string[] $header
     * @param string[] $rows
     */
    private static function table(array $header, array $rows): string
    {
        $out = '<table class="tutorial-table"><thead><tr>';

        foreach ($header as $cell) {
            $out .= '<th>' . self::inline($cell) . '</th>';
        }

        $out .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $out .= '<tr>';

            foreach ($row as $cell) {
                $out .= '<td>' . self::inline($cell) . '</td>';
            }

            $out .= '</tr>';
        }

        return $out . '</tbody></table>';
    }

    /**
     * Format inline: escape dulu, lalu inline code, bold, link.
     * Inline code diproses lebih awal supaya isinya tidak kena
     * format lain.
     */
    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES);

        $text = preg_replace_callback(
            '/`([^`]+)`/',
            static function ($m) {
                return '<code class="tutorial-code">' . $m[1] . '</code>';
            },
            $text
        );

        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);

        $text = preg_replace_callback(
            '/\[((?:[^\]]+))\]\(([^)\s]+)\)/',
            static function ($m) {
                $url = html_entity_decode($m[2], ENT_QUOTES);

                // Hanya link aman yang diteruskan (tanpa javascript:/data:).
                if (! preg_match('~^(https?:)?//|^/|^#|^mailto:~i', $url)) {
                    return $m[1];
                }

                return '<a class="tutorial-link" href="'
                    . htmlspecialchars($m[2], ENT_QUOTES) . '">' . $m[1] . '</a>';
            },
            $text
        );

        return $text;
    }
}
