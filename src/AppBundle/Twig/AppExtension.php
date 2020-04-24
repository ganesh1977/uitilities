<?php
namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('price', array($this, 'priceFilter')),
            new \Twig_SimpleFilter('hour', array($this, 'hourFilter')),
        );
    }

    public function priceFilter($number, $decimals = 0, $decPoint = '.', $thousandsSep = ',', $currency = '')
    {
        $price = number_format($number, $decimals, $decPoint, $thousandsSep);
        $price = $currency.$price;

        return $price;
    }

    public function hourFilter($number)
    {
		return substr($number, 0, 2) . ':' . substr($number, 2, 2);
    }
    
    public function getName()
    {
        return 'app_extension';
    }
}