<?php

class BBCode
{
    private const ALIAS = [
        'quote' => 'blockquote',
        'code' => 'pre',
        'img' => 'img',
        'list' => 'ul',
        '*' => 'li',
    ];

    private const SIMPLE = ['b', 'i', 'u', 's', 'sup', 'sub', 'blockquote', 'ol', 'ul', 'table'];

    private const CLOSE_ELEMENT = ['font' => 'span'];

    private const URL_SCHEMES = ['http', 'https'];

    private const TAG_PATTERN = "/\[[A-Za-z0-9 \-._~:\/?#@!$&'()*+,;=%]+\]/u";

    private string $input;
    private int $count;
    private array $matches;

    private array $stack;
    private string $output;
    private int $ptr;
    private int $idx;

    public static function bbcode_to_html(string $input): string
    {
        return (new self($input))->parse();
    }

    private function __construct(string $input)
    {
        $this->input = $input;
    }

    private function parse(): string
    {
        if (preg_match_all(self::TAG_PATTERN, $this->input, $matches, PREG_OFFSET_CAPTURE) === false) {
            throw new RuntimeException('Fatal error in preg_match_all for BBCode tags');
        }

        $this->matches = $matches[0];
        $this->count = count($this->matches);
        $this->stack = [];
        $this->output = '';
        $this->ptr = 0;

        for ($this->idx = 0; $this->idx < $this->count; $this->idx++) {
            [$match, $offset] = $this->matches[$this->idx];

            $this->output .= $this->encode(substr($this->input, $this->ptr, $offset - $this->ptr));
            $this->ptr = $offset + strlen($match);

            $tag = $this->decodeTag($match);
            if ($tag['open']) {
                $this->openTag($tag, $match);
            } else {
                $this->closeTag($tag['name'], $match);
            }
        }

        $this->output .= $this->encode(substr($this->input, $this->ptr));

        while (!empty($this->stack)) {
            $popped = array_pop($this->stack);
            $this->output .= '</' . (self::CLOSE_ELEMENT[$popped] ?? $popped) . '>';
        }

        return $this->output;
    }

    private function openTag(array $tag, string $match): void
    {
        $name = $tag['name'];

        $handled = match (true) {
            in_array($name, self::SIMPLE, true) => $this->openElement($name),
            $name === 'li' => $this->openListItem(),
            $name === 'tr' => $this->openTableRow(),
            $name === 'td', $name === 'th' => $this->openTableCell($name),
            $name === 'font' => $this->openFont($tag['args']),
            $name === 'pre' => $this->parseCode(),
            $name === 'img' => $this->parseImage($tag['args']),
            default => false,
        };

        if (!$handled) {
            $this->output .= $this->encode($match);
        }
    }

    private function closeTag(string $name, string $match): void
    {
        if (!in_array($name, $this->stack, true)) {
            $this->output .= $this->encode($match);

            return;
        }

        do {
            $popped = array_pop($this->stack);
            $this->output .= '</' . (self::CLOSE_ELEMENT[$popped] ?? $popped) . '>';
        } while ($popped !== $name);
    }

    private function openElement(string $name): bool
    {
        $this->stack[] = $name;
        $this->output .= '<' . $name . '>';

        return true;
    }

    private function openListItem(): bool
    {
        if (!in_array('ol', $this->stack, true) && !in_array('ul', $this->stack, true)) {
            return false;
        }

        if (end($this->stack) === 'li') {
            array_pop($this->stack);
            $this->output .= '</li>';
        }

        $this->stack[] = 'li';
        $this->output .= '<li>';

        return true;
    }

    private function openTableRow(): bool
    {
        if (!in_array('table', $this->stack, true)) {
            return false;
        }

        $this->stack[] = 'tr';
        $this->output .= '<tr>';

        return true;
    }

    private function openTableCell(string $name): bool
    {
        $tr = array_search('tr', $this->stack, true);
        $table = array_search('table', $this->stack, true);
        if ($tr === false || $table === false || $table > $tr) {
            return false;
        }

        $this->stack[] = $name;
        $this->output .= '<' . $name . '>';

        return true;
    }

    private function openFont(?array $args): bool
    {
        $color = $args['color'] ?? null;
        if ($color === null || !preg_match('/^(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z]+)$/i', $color)) {
            return false;
        }

        $this->stack[] = 'font';
        $this->output .= '<span' . $this->attributes(['style' => "color: {$color}"]) . '>';

        return true;
    }

    private function parseCode(): bool
    {
        $end = $this->findClose('pre');
        if ($end === null) {
            return false;
        }

        [, $endOffset] = $this->matches[$end];
        $this->output .= $this->tag('pre', [], $this->encode(substr($this->input, $this->ptr, $endOffset - $this->ptr)));
        $this->consumeUntil($end);

        return true;
    }

    private function parseImage(?array $args): bool
    {
        $body = $this->enclosedText('img');
        if ($body === null) {
            return false;
        }

        $attributes = [
            'src' => $this->sanitizeUrl($body),
            'style' => 'max-height:300px;',
            'alt' => '',
            'loading' => 'lazy',
        ];
        foreach (['width', 'height'] as $dimension) {
            $value = $args[$dimension] ?? null;
            if ($value !== null && preg_match('/^\d+(?:px|%)?$/', $value)) {
                $attributes[$dimension] = $value;
            }
        }

        $this->output .= '<img' . $this->attributes($attributes) . '>';

        return true;
    }

    private function decodeTag(string $token): array
    {
        $open = $token[1] !== '/';
        $inner = substr($token, $open ? 1 : 2, -1);

        $params = array_map(fn($p) => explode('=', $p, 2), explode(' ', $inner));
        $first = array_shift($params);

        $name = strtolower($first[0]);
        $name = self::ALIAS[$name] ?? $name;
        $args = isset($first[1]) ? ['default' => $first[1]] : null;
        foreach ($params as $param) {
            $args[strtolower($param[0])] = $param[1] ?? '';
        }

        return ['name' => $name, 'open' => $open, 'args' => $args];
    }

    private function enclosedText(string $element): ?string
    {
        $next = $this->idx + 1;
        if ($next >= $this->count) {
            return null;
        }

        [$match, $offset] = $this->matches[$next];
        $tag = $this->decodeTag($match);
        if ($tag['open'] || $tag['name'] !== $element) {
            return null;
        }

        $text = substr($this->input, $this->ptr, $offset - $this->ptr);
        $this->consumeUntil($next);

        return $text;
    }

    private function findClose(string $element): ?int
    {
        for ($i = $this->idx + 1; $i < $this->count; $i++) {
            $tag = $this->decodeTag($this->matches[$i][0]);
            if (!$tag['open'] && $tag['name'] === $element) {
                return $i;
            }
        }

        return null;
    }

    private function consumeUntil(int $to): void
    {
        [$match, $offset] = $this->matches[$to];
        $this->ptr = $offset + strlen($match);
        $this->idx = $to;
    }

    private function sanitizeUrl(string $url): string
    {
        $stripped = preg_replace('/[\x00-\x20]+/', '', $url);
        if (
            preg_match('#^([a-z][a-z0-9+.\-]*):#i', $stripped, $m)
            && !in_array(strtolower($m[1]), self::URL_SCHEMES, true)
        ) {
            return '';
        }

        return $url;
    }

    private function tag(string $element, array $attributes = [], ?string $content = null): string
    {
        return '<' . $element . $this->attributes($attributes) . '>' . ($content ?? '') . '</' . $element . '>';
    }

    private function attributes(array $attributes): string
    {
        $out = '';
        foreach ($attributes as $key => $value) {
            $out .= ' ' . $key . '="' . $this->encodeAttr($value) . '"';
        }

        return $out;
    }

    private function encodeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function encode(string $input): string
    {
        $output = '';
        $lf = 0;

        foreach (preg_split('//u', $input, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            if ($ch === "\n") {
                $lf++;

                continue;
            }
            if ($ch === "\r") {
                continue;
            }

            if ($lf === 1) {
                $output .= "\n<br>";
            } elseif ($lf > 1) {
                $output .= "\n\n<p>";
            }
            $lf = 0;

            $output .= match ($ch) {
                '<' => '&lt;',
                '>' => '&gt;',
                '&' => '&amp;',
                "\u{00A0}" => '&nbsp;',
                default => $ch,
            };
        }

        if ($lf === 1) {
            $output .= "\n<br>";
        } elseif ($lf > 1) {
            $output .= "\n\n<p>";
        }

        return $output;
    }
}

function bbcode_to_html(string $input): string
{
    return BBCode::bbcode_to_html($input);
}
