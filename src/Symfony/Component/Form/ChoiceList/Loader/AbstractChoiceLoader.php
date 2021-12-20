<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\ChoiceList\Loader;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
abstract class AbstractChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * The stored choices.
     *
     * @var iterable
     */
    private $choices;


    /**
     * @final
     *
     * {@inheritdoc}
     */
    public function loadChoiceList(callable $value = null)
    {
        if (!isset($this->choices)) {
            $this->choices = $this->loadChoices();
        }

        return new ArrayChoiceList($this->choices, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoicesForValues(array $values, callable $value = null)
    {
        if (!$values) {
            return [];
        }

        return $this->doLoadChoicesForValues($values, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function loadValuesForChoices(array $choices, callable $value = null)
    {
        if (!$choices) {
            return [];
        }

        // doLoadValuesForChoices() does not allow passing the $value callback
        // for loadChoiceList() so workaround with array_map().
        $values = $this->doLoadValuesForChoices($choices);

        if (null === $value) {
            return $values;
        }

        return array_map($value, $values);
    }

    abstract protected function loadChoices(): iterable;

    protected function doLoadChoicesForValues(array $values, ?callable $value): array
    {
        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    protected function doLoadValuesForChoices(array $choices): array
    {
        return $this->loadChoiceList()->getValuesForChoices($choices);
    }
}
