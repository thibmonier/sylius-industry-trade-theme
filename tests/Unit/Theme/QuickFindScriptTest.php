<?php

declare(strict_types=1);

namespace Tmonier\SyliusIndustryTradeTheme\Tests\Unit\Theme;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * US-227 : le script de saisie rapide est exécuté sous Node avec un DOM minimal qui reproduit le balisage du thème.
 */
final class QuickFindScriptTest extends TestCase
{
    private const SCRIPT = __DIR__ . '/../../../templates/industry_trade/_quick_find_script.html.twig';

    private const HARNESS = __DIR__ . '/QuickFind/harness.js';

    private const ROWS = [
        [
            'ref' => 'EPI-GANT-M',
            'name' => 'Gants de manutention',
        ],
        [
            'ref' => 'EPI-GANT-L',
            'name' => 'Gants nitrile',
        ],
        [
            'ref' => 'VIS-M8',
            'name' => 'Vis inox M8',
        ],
    ];

    public function testTypingAReferenceListsTheMatchingReferencesAndSelectsTheFirst(): void
    {
        $result = $this->runScenario([[
            'type' => 'input',
            'value' => 'EPI-GA',
        ]]);

        $this->assertSame(['opt-0', 'opt-1'], $result['visible']);
        $this->assertSame(['opt-0'], $result['selected']);
        $this->assertSame('opt-0', $result['activedescendant']);
        $this->assertSame('true', $result['expanded']);
    }

    public function testTypingADesignationFindsTheLine(): void
    {
        $result = $this->runScenario([[
            'type' => 'input',
            'value' => 'inox',
        ]]);

        $this->assertSame(['opt-2'], $result['visible']);
    }

    public function testEnterFocusesTheFirstMatchingLine(): void
    {
        $result = $this->runScenario([
            [
                'type' => 'input',
                'value' => 'EPI-GA',
            ],
            [
                'type' => 'key',
                'on' => 'input',
                'key' => 'Enter',
            ],
        ]);

        $this->assertSame('link-0', $result['focus']);
        $this->assertTrue($result['lastPrevented']);
        $this->assertTrue($result['listHidden']);
    }

    public function testArrowDownThenEnterSelectsTheSecondLine(): void
    {
        $result = $this->runScenario([
            [
                'type' => 'input',
                'value' => 'EPI-GA',
            ],
            [
                'type' => 'key',
                'on' => 'input',
                'key' => 'ArrowDown',
            ],
            [
                'type' => 'key',
                'on' => 'input',
                'key' => 'Enter',
            ],
        ]);

        $this->assertSame('link-1', $result['focus']);
    }

    public function testArrowUpFromTheFirstLineWrapsToTheLast(): void
    {
        $result = $this->runScenario([
            [
                'type' => 'input',
                'value' => 'EPI-GA',
            ],
            [
                'type' => 'key',
                'on' => 'input',
                'key' => 'ArrowUp',
            ],
        ]);

        $this->assertSame(['opt-1'], $result['selected']);
    }

    public function testResultCountIsAnnouncedInTheLiveRegion(): void
    {
        $this->assertSame('2 références trouvées', $this->runScenario([[
            'type' => 'input',
            'value' => 'EPI-GA',
        ]])['status']);
        $this->assertSame('1 référence trouvée', $this->runScenario([[
            'type' => 'input',
            'value' => 'vis',
        ]])['status']);
    }

    public function testNoMatchIsAnnouncedAndNothingIsListed(): void
    {
        $result = $this->runScenario([[
            'type' => 'input',
            'value' => 'zzz',
        ]]);

        $this->assertSame('Aucune référence trouvée', $result['status']);
        $this->assertSame([], $result['visible']);
        $this->assertNull($result['activedescendant']);
    }

    public function testEnterWithoutMatchDoesNotMoveTheFocus(): void
    {
        $result = $this->runScenario([
            [
                'type' => 'focus',
                'on' => 'input',
            ],
            [
                'type' => 'input',
                'value' => 'zzz',
            ],
            [
                'type' => 'key',
                'on' => 'input',
                'key' => 'Enter',
            ],
        ]);

        $this->assertSame('input', $result['focus']);
    }

    public function testEscapeClosesTheListThenClearsTheField(): void
    {
        $first = $this->runScenario([
            [
                'type' => 'input',
                'value' => 'EPI',
            ],
            [
                'type' => 'key',
                'on' => 'input',
                'key' => 'Escape',
            ],
        ]);
        $second = $this->runScenario([
            [
                'type' => 'input',
                'value' => 'EPI',
            ],
            [
                'type' => 'key',
                'on' => 'input',
                'key' => 'Escape',
            ],
            [
                'type' => 'key',
                'on' => 'input',
                'key' => 'Escape',
            ],
        ]);

        $this->assertTrue($first['listHidden']);
        $this->assertSame('false', $first['expanded']);
        $this->assertSame('EPI', $first['inputValue']);
        $this->assertSame('', $second['inputValue']);
    }

    public function testClearingTheFieldClosesTheListAndSilencesTheRegion(): void
    {
        $result = $this->runScenario([
            [
                'type' => 'input',
                'value' => 'EPI',
            ],
            [
                'type' => 'input',
                'value' => '',
            ],
        ]);

        $this->assertTrue($result['listHidden']);
        $this->assertSame('', $result['status']);
        $this->assertSame([], $result['visible']);
    }

    public function testArrowsMoveTheFocusFromCellToCell(): void
    {
        $down = $this->runScenario([[
            'type' => 'focus',
            'on' => 'link-0',
        ], [
            'type' => 'key',
            'on' => 'link-0',
            'key' => 'ArrowDown',
        ]]);
        $right = $this->runScenario([[
            'type' => 'focus',
            'on' => 'link-0',
        ], [
            'type' => 'key',
            'on' => 'link-0',
            'key' => 'ArrowRight',
        ]]);
        $up = $this->runScenario([[
            'type' => 'focus',
            'on' => 'add-2',
        ], [
            'type' => 'key',
            'on' => 'add-2',
            'key' => 'ArrowUp',
        ]]);
        $left = $this->runScenario([[
            'type' => 'focus',
            'on' => 'add-1',
        ], [
            'type' => 'key',
            'on' => 'add-1',
            'key' => 'ArrowLeft',
        ]]);

        $this->assertSame('link-1', $down['focus']);
        $this->assertSame('add-0', $right['focus']);
        $this->assertSame('add-1', $up['focus']);
        $this->assertSame('link-1', $left['focus']);
    }

    public function testArrowsStopAtTheEdgesOfTheGrid(): void
    {
        $result = $this->runScenario([[
            'type' => 'focus',
            'on' => 'link-0',
        ], [
            'type' => 'key',
            'on' => 'link-0',
            'key' => 'ArrowUp',
        ]]);

        $this->assertSame('link-0', $result['focus']);
    }

    public function testEscapeInTheGridReturnsToTheReferenceField(): void
    {
        $result = $this->runScenario([[
            'type' => 'focus',
            'on' => 'add-1',
        ], [
            'type' => 'key',
            'on' => 'add-1',
            'key' => 'Escape',
        ]]);

        $this->assertSame('input', $result['focus']);
    }

    public function testScriptRunsWithoutErrorOnAPageWithoutTheField(): void
    {
        $result = $this->runScenario([], false);

        $this->assertNull($result['error']);
    }

    /**
     * @param list<array<string, mixed>> $steps
     *
     * @return array<string, mixed>
     */
    private function runScenario(array $steps, bool $withField = true): array
    {
        $node = (new ExecutableFinder())->find('node');
        if ($node === null) {
            $this->markTestSkipped('Node.js est requis pour exécuter le script de saisie rapide.');
        }
        $this->assertFileExists(self::SCRIPT, 'le gabarit du script de saisie rapide est absent');
        $this->assertSame(
            1,
            preg_match('#<script[^>]*>(.*?)</script>#s', (string) file_get_contents(self::SCRIPT), $match),
        );

        $process = new Process([$node, self::HARNESS], null, null, json_encode([
            'script' => $match[1] ?? '',
            'rows' => $withField ? self::ROWS : [],
            'steps' => $steps,
            'withField' => $withField,
        ], \JSON_THROW_ON_ERROR));
        $process->mustRun();

        /** @var array<string, mixed> $result */
        $result = json_decode($process->getOutput(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertNull($result['error'], is_string($result['error']) ? $result['error'] : '');

        return $result;
    }
}
