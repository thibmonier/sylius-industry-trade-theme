<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\BlankProject;

/**
 * Installation steps of the README, the single source the "blank project" CI job runs (US-273).
 *
 * A step is a fenced code block preceded by a marker comment:
 *   <!-- blank-project: step=<name> -->                                  a bash block, run as is
 *   <!-- blank-project: step=<name> file=<path> write=append|replace -->  a block written to <path>
 */
final class ReadmeSteps
{
    private const MARKER = '/<!--\s*blank-project:\s*(?<attributes>[^>]*?)\s*-->\s*\n```(?<lang>[a-z]*)\n(?<body>.*?)\n```/s';

    /**
     * @return list<array{name: string, lang: string, body: string, file: ?string, write: ?string}>
     */
    public static function parse(string $markdown): array
    {
        preg_match_all(self::MARKER, $markdown, $matches, \PREG_SET_ORDER);

        return array_map(self::step(...), $matches);
    }

    /**
     * Executable content: the body without comment lines, so the EN and FR READMEs can be compared.
     */
    public static function executableBody(string $body): string
    {
        $lines = array_filter(
            explode("\n", $body),
            static fn(string $line): bool => preg_match('~^\s*(//|#)~', $line) !== 1,
        );

        return implode("\n", $lines);
    }

    /**
     * @param array{attributes: string, lang: string, body: string} $match
     *
     * @return array{name: string, lang: string, body: string, file: ?string, write: ?string}
     */
    private static function step(array $match): array
    {
        preg_match_all('/(?<key>[a-z]+)=(?<value>\S+)/', $match['attributes'], $pairs, \PREG_SET_ORDER);
        $attributes = array_column($pairs, 'value', 'key');
        if (! isset($attributes['step'])) {
            throw new \InvalidArgumentException(sprintf(
                'README marker without step name: "%s".',
                $match['attributes'],
            ));
        }

        $write = $attributes['write'] ?? null;
        if ($write !== null && ! in_array($write, ['append', 'replace'], true)) {
            throw new \InvalidArgumentException(sprintf(
                'README step "%s": write must be append or replace.',
                $attributes['step'],
            ));
        }

        return [
            'name' => $attributes['step'],
            'lang' => $match['lang'],
            'body' => $match['body'],
            'file' => $attributes['file'] ?? null,
            'write' => $write,
        ];
    }
}
