<?php
namespace AppBundle\Service\GeckoBoard;

use Kwk\Geckoboard\Dataset\DatasetBuilder;
use Kwk\Geckoboard\Dataset\DataSetInterface;
use Kwk\Geckoboard\Dataset\Type\DateType;
use Kwk\Geckoboard\Dataset\Type\StringType;
use Kwk\Geckoboard\Dataset\Type\NumberType;
use Kwk\Geckoboard\Dataset\Type\MoneyType;

class SalesDataset implements DataSetInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'sales';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition()
    {
        return (new DatasetBuilder())
            ->addField('origin_dt', new DateType('Origin Date'))
            ->addField('prom_cd', new StringType('Promotion Cd'))
            ->addField('brand', new StringType('Brand'))
            ->addField('mkt_cd', new StringType('Market Cd'))
            ->addField('mkt', new StringType('Market'))
            ->addField('reservations', new NumberType('Reservations'))
            ->addField('pax', new NumberType('Pax'))
            ->addField('revenue_eur', new MoneyType('Revenue', 'EUR'))
            ->addField('profit_eur', new MoneyType('Profit', 'EUR'))
            ->addField('profit_pax_eur', new MoneyType('Profit/Pax', 'EUR'))
            ->addParam('unique_by', ['origin_dt', 'prom_cd', 'mkt_cd'])
            ->build();
    }
}
