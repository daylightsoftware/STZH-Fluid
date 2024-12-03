<?php

declare(strict_types=1);

/*
 * This file belongs to the package "TYPO3 Fluid".
 * See LICENSE.txt that was shipped with this package.
 */

namespace TYPO3Fluid\Fluid\Tests\Functional\Core\Parser\SyntaxTree;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3Fluid\Fluid\Tests\Functional\AbstractFunctionalTestCase;
use TYPO3Fluid\Fluid\Tests\Functional\Fixtures\Various\Objects\WithCamelCaseGetter;
use TYPO3Fluid\Fluid\Tests\Functional\Fixtures\Various\Objects\WithEverything;
use TYPO3Fluid\Fluid\Tests\Functional\Fixtures\Various\Objects\WithProperties;
use TYPO3Fluid\Fluid\Tests\Functional\Fixtures\Various\Objects\WithUpperCaseGetter;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ObjectAccessorNodeTest extends AbstractFunctionalTestCase
{
    public static function renderDataProvider(): array
    {
        return [
            'plain array' => [
                '["{data.value}"]',
                [
                    'data' => [
                        'value' => 'value',
                    ],
                ],
                [
                    'value',
                ],
            ],
            'array object' => [
                '["{data.value}"]',
                [
                    'data' => new \ArrayObject(['value' => 'value']),
                ],
                [
                    'value',
                ],
            ],
            'public property' => [
                '["{data.publicValue}"]',
                [
                    'data' => new WithProperties(),
                ],
                [
                    'publicValue',
                ],
            ],
            'camelCase getter method' => [
                '["{data.privateValue}", "{data.protectedValue}", "{data.publicValue}"]',
                [
                    'data' => new WithCamelCaseGetter(),
                ],
                [
                    'privateValue@getPrivateValue()',
                    'protectedValue@getProtectedValue()',
                    'publicValue@getPublicValue()',
                ],
            ],
            'UPPERCASE getter method' => [
                '["{data.privateValue}", "{data.protectedValue}", "{data.publicValue}"]',
                [
                    'data' => new WithUpperCaseGetter(),
                ],
                [
                    'privateValue@GETPRIVATEVALUE()',
                    'protectedValue@GETPROTECTEDVALUE()',
                    'publicValue@GETPUBLICVALUE()',
                ],
            ],
            'multiple accessor types' => [
                '["{data.privateValue}", "{data.protectedValue}", "{data.publicValue}"]',
                [
                    'data' => new WithEverything(),
                ],
                [
                    'privateValue@getPrivateValue()',
                    'protectedValue@getProtectedValue()',
                    'publicValue@getPublicValue()',
                ],
            ],
        ];
    }

    #[DataProvider('renderDataProvider')]
    #[Test]
    public function render(string $template, array $variables, array $expected): void
    {
        $view = new TemplateView();
        $view->getRenderingContext()->setCache(self::$cache);
        $view->assignMultiple($variables);
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, json_decode($view->render(), true));

        $view = new TemplateView();
        $view->getRenderingContext()->setCache(self::$cache);
        $view->assignMultiple($variables);
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, json_decode($view->render(), true));
    }

    #[Test]
    public function renderThrowsExceptionAccessingPrivateProperty(): void
    {
        $this->expectException(\Throwable::class);
        $this->expectExceptionCode(0);
        $view = new TemplateView();
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource('["{data.privateValue}"]');
        $view->assignMultiple(['data' => new WithProperties()]);
        $view->render();
    }
}
