<?php

namespace Symfony\Component\Form\Tests\ChoiceList\Loader;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\ChoiceList\Loader\FilterChoiceLoaderDecorator;

class FilterChoiceLoaderDecoratorTest extends TestCase
{
    public function testLoadChoiceList()
    {
        $decorated = $this->createMock(ChoiceLoaderInterface::class);
        $decorated->expects($this->once())
            ->method('loadChoiceList')
            ->willReturn(new ArrayChoiceList(range(1, 4)))
        ;

        $filter = function ($choice) {
            return 0 === $choice % 2;
        };

        $loader = new FilterChoiceLoaderDecorator($decorated, $filter);

        $this->assertEquals(new ArrayChoiceList([1 => 2, 3 => 4]), $loader->loadChoiceList());
    }

    public function testLoadChoiceListWithGroupedChoices()
    {
        $decorated = $this->createMock(ChoiceLoaderInterface::class);
        $decorated->expects($this->once())
            ->method('loadChoiceList')
            ->willReturn(new ArrayChoiceList(['units' => range(1, 9), 'tens' => range(10, 90, 10)]))
        ;

        $filter = function ($choice) {
            return $choice < 9 && 0 === $choice % 2;
        };

        $loader = new FilterChoiceLoaderDecorator($decorated, $filter);

        $this->assertEquals(new ArrayChoiceList([
            'units' => [
                1 => 2,
                3 => 4,
                5 => 6,
                7 => 8,
            ],
        ]), $loader->loadChoiceList());
    }

    public function testLoadValuesForChoices()
    {
        $evenValues = [1 => '2', 3 => '4'];

        $decorated = $this->createMock(ChoiceLoaderInterface::class);
        $decorated->expects($this->never())
            ->method('loadChoiceList')
        ;
        $decorated->expects($this->once())
            ->method('loadValuesForChoices')
            ->with([1 => 2, 3 => 4])
            ->willReturn($evenValues)
        ;

        $filter = function ($choice) {
            return 0 === $choice % 2;
        };

        $loader = new FilterChoiceLoaderDecorator($decorated, $filter);

        $this->assertSame($evenValues, $loader->loadValuesForChoices(range(1, 4)));
    }

    public function testLoadChoicesForValues()
    {
        $evenChoices = [1 => 2, 3 => 4];
        $values = array_map('strval', range(1, 4));

        $decorated = $this->createMock(ChoiceLoaderInterface::class);
        $decorated->expects($this->never())
            ->method('loadChoiceList')
        ;
        $decorated->expects($this->once())
            ->method('loadChoicesForValues')
            ->with($values)
            ->willReturn(range(1, 4))
        ;

        $filter = function ($choice) {
            return 0 === $choice % 2;
        };

        $loader = new FilterChoiceLoaderDecorator($decorated, $filter);

        $this->assertEquals($evenChoices, $loader->loadChoicesForValues($values));
    }

    public function testValuesAreNotCached()
    {
        $innerLoader = new class implements ChoiceLoaderInterface {
            public function loadChoiceList(?callable $value = null): ChoiceListInterface
            {
            return new ArrayChoiceList(['123', '1234', '12345', '123456'], $value);
            }

            public function loadChoicesForValues(array $values, ?callable $value = null): array
            {
                return $this->loadChoiceList($value)->getChoicesForValues($values);
            }

            public function loadValuesForChoices(array $choices, ?callable $value = null): array
            {
                return $this->loadChoiceList($value)->getValuesForChoices($choices);
            }
        };

        $loader = new FilterChoiceLoaderDecorator($innerLoader, function ($choice) {
            return strlen($choice) < 5;
        });

        // $this->assertEquals(['123', '1234'], $loader->loadChoiceList(null)->getValues());
        // $this->assertEquals(['321'], $loader->loadValuesForChoices(['123'], 'strrev'));
        // $this->assertEquals(['123'], $loader->loadValuesForChoices(['123'], null));

        // $loader = $innerLoader;

        $this->assertEquals(['123', '1234'], $loader->loadChoiceList(null)->getValues());
        $this->assertEquals(['321', '4321'], $loader->loadChoiceList('strrev')->getValues());
        $this->assertEquals(['123', '1234'], $loader->loadChoiceList(null)->getValues());

        $this->assertEquals(['123'], $loader->loadChoicesForValues(['123'], null));
        $this->assertEquals([], $loader->loadChoicesForValues(['321'], null));
        $this->assertEquals([], $loader->loadChoicesForValues(['123'], 'strrev'));
        $this->assertEquals(['123'], $loader->loadChoicesForValues(['321'], 'strrev'));
        $this->assertEquals(['123'], $loader->loadChoicesForValues(['123'], null));

        $this->assertEquals(['123'], $loader->loadValuesForChoices(['123'], null));
        $this->assertEquals(['321'], $loader->loadValuesForChoices(['123'], 'strrev'));
        $this->assertEquals(['123'], $loader->loadValuesForChoices(['123'], null));
    }
}
