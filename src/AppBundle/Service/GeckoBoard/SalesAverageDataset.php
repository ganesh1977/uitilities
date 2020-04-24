<?php
namespace AppBundle\Service\GeckoBoard;

use Kwk\Geckoboard\Dataset\DatasetBuilder;
use Kwk\Geckoboard\Dataset\DataSetInterface;
use Kwk\Geckoboard\Dataset\Type\DateType;
use Kwk\Geckoboard\Dataset\Type\StringType;
use Kwk\Geckoboard\Dataset\Type\NumberType;
use Kwk\Geckoboard\Dataset\Type\MoneyType;

class SalesAverageDataset implements DataSetInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'sales.average';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition()
    {
        return (new DatasetBuilder())
            ->addField('origin_dt', new DateType('Origin Date'))
            ->addField('brand', new StringType('Brand'))
            ->addField('pax_reservation', new NumberType('Pax/Reservation'))
            ->addField('profit_pax', new MoneyType('GC/Pax', 'EUR'))
            ->addField('profit_reservation', new MoneyType('GC/Reservation', 'EUR'))
            ->addParam('unique_by', ['origin_dt', 'brand'])
            ->build();
    }
}
