<?php
namespace AppBundle\Service\GeckoBoard;

use Kwk\Geckoboard\Dataset\DatasetBuilder;
use Kwk\Geckoboard\Dataset\DataSetInterface;
use Kwk\Geckoboard\Dataset\Type\DateType;
use Kwk\Geckoboard\Dataset\Type\StringType;
use Kwk\Geckoboard\Dataset\Type\NumberType;
use Kwk\Geckoboard\Dataset\Type\MoneyType;

class PASalesDataset implements DataSetInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'air.sales';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition()
    {
        return (new DatasetBuilder())
            ->addField('route', new StringType('Route'))
            ->addField('market', new StringType('Market'))
            ->addField('booking_dt', new DateType('Booking Date'))
            ->addField('pax', new NumberType('Pax'))
            ->addField('net_rev', new MoneyType('Net. Revenue', 'EUR'))
            ->addField('total_rev', new MoneyType('Total Revenue', 'EUR'))
            ->addField('net_yield', new MoneyType('Net. Yield', 'EUR'))
            ->addField('total_yield', new MoneyType('Total Yield', 'EUR'))
            ->addParam('unique_by', ['route', 'market', 'booking_dt'])
            ->build();
    }
}
