<?php
namespace AppBundle\Entity;

use GuzzleHttp\Client;

class Packages
{
	protected $adults = 0;
	protected $children = 0;
	protected $infants = 0;
	protected $childrenAges;
	protected $date;
	protected $dateMatch = 0;
	protected $stay = 7;
	protected $stayMatch = 0;
	protected $from;
	protected $to;
    protected $accommodations = [];
    protected $point;
	protected $sort = 'price';
	protected $sort_params;
    protected $promotion;
	
    protected $environment = 'prod';
	protected $alerts;
	
    private $connection;

/*    public function __construct(Connection $dbalConnection)  {
        $this->conn = $dbalConnection;    
    }*/

	public function setEnvironment($value)
	{
		$this->environment = $value;
	}

	public function getPromotion()
	{
		return $this->promotion;
	}

	public function setPromotion($value)
	{
		$this->promotion = $value;
	}
	
	public function getAdults()
	{
		return $this->adults;
	}

	public function setAdults($value)
	{
		$this->adults = $value;
	}
	
	public function getChildren()
	{
		return $this->children;
	}

	public function setChildren($value)
	{
		$this->children = $value;
	}

	public function getInfants()
	{
		return $this->infants;
	}

	public function setInfants($value)
	{
		$this->infants = $value;
	}
	
	public function getChildrenAges()
	{
		return $this->childrenAges;
	}

	public function setChildrenAges($value)
	{
		$this->childrenAges = $value;
	}

	public function getDate()
	{
		return $this->date;
	}

	public function setDate($value)
	{
		$this->date = $value;
	}

	public function getDateMatch()
	{
		return $this->dateMatch;
	}

	public function setDateMatch($value)
	{
		$this->dateMatch = $value;
	}

	public function getStay()
	{
		return $this->stay;
	}

	public function setStay($value)
	{
		$this->stay = $value;
	}

	public function getStayMatch()
	{
		return $this->stayMatch;
	}

	public function setStayMatch($value)
	{
		$this->stayMatch = $value;
	}

	public function getFrom()
	{
		return $this->from;
	}

	public function setFrom($value)
	{
		$this->from = strtoupper($value);
	}

	public function getTo()
	{
		return $this->to;
	}

	public function setTo($value)
	{
		$this->to = strtoupper($value);
/*        $this->accommodations = [];
        
        $sql = "SELECT
                  stc.stc_stk_id
                FROM
                  ATCOMRES.AR_STATICSTOCK stc
                    INNER JOIN ATCOMRES.AR_STATICTRANSPORT trs
                      ON trs.stc_stk_id = stc.stc_stk_id
                        INNER JOIN ATCOMRES.AR_POINT pt
                          ON pt.pt_id = trs.pt_id
                WHERE
                  pt.pt_tp = 'AIR'
                    AND
                  pt.pt_cd = '" . $this->to . "'";

        $results = $this->conn->fetchAll($sql);
        foreach ($results as $result) {
            $this->accommodations[] = $result['STC_STK_ID'];
        }*/
	}

	public function getPoint()
	{
		return $this->point;
	}

	public function setPoint($value)
	{
		$this->point = strtoupper($value);
	}

	public function getSort()
	{
		return $this->sort;
	}

	public function setSort($value)
	{
		$this->sort = $value;
	}
	
	public function doSearch()
	{
		// Calculations
		$sdate = date('Y-m-d', strtotime($this->date) - 60*60*24*$this->dateMatch);
		$edate = date('Y-m-d', strtotime($this->date) + 60*60*24*$this->dateMatch);
	
		$sstay = $this->stay - $this->stayMatch;
		$sstay = $sstay < 0 ? 0 : $sstay;
		$estay = $this->stay + $this->stayMatch;
	
		$f_cty1 = (strlen($this->point) == 2) ? $this->point : '';
		$f_cty2 = (strlen($this->point) == 3) ? $this->point : '';
		$f_cty3 = (strlen($this->point) == 5) ? $this->point : '';
        
		if ($this->sort == 'price') {
			$this->sort_params = [
				['name' => 'price', 'direction' => -1],
				['name' => 'seats', 'direction' => 1],
				['name' => 'timestamp', 'direction' => -1],
			];
		} else {
			$this->sort_params = [
				['name' => 'seats', 'direction' => 1],
				['name' => 'price', 'direction' => -1],
				['name' => 'timestamp', 'direction' => -1],
			];
		}

		$client = new Client([
			// Base URI is used with relative requests
			'base_uri' => 'https://193.201.124.82/public/cache/' . $this->environment . '/',
			// You can set any number of default request options.
			'timeout'  => 10.0,
//			'debug' => true
		]);

		$query_params = [
			'func' => 801,
			'agent' => 'WEBAGT1',
			'sdate' => $sdate,
			'edate' => $edate,
			'sstay' => $sstay,
			'estay' => $estay,
			'pax_ad' => $this->adults,
			'pax_ch' => $this->children,
			'pax_in' => $this->infants,
			'f_prom' => $this->promotion,
			'r_1_no' => 1,
			'r_1_pax' => ($this->adults + $this->children + $this->infants),
		];
        
        if ($f_cty1) {
			$query_params['f_cty1'] = $f_cty1;
        }
        if ($f_cty2) {
			$query_params['f_cty2'] = $f_cty2;
        }
        if ($f_cty3) {
			$query_params['f_cty3'] = $f_cty3;
        }
        if ($this->from) {
			$query_params['f_dep'] = $this->from;
        }
        if ($this->to) {
			$query_params['f_arr'] = $this->to;
        }

		$j = 1;
		for ($i = 0; $i < $this->adults; $i++,$j++) {
			$query_params['r_1_p_' . $j . '_no'] = $j;
		}
		for ($i = 0;$i < $this->children; $i++,$j++) {
			if ($this->childrenAges[$i]) {
				$ch_age = $this->childrenAges[$i];
			} else {
				$ch_age = 7;
				$this->alerts[] = ['warning', '<strong>Child age!</strong> No age given, search assumes child is 7 years old.'];
			}
			$query_params['r_1_p_' . $j . '_no'] = $j;
			$query_params['r_1_p_' . $j . '_age'] = $ch_age;
		}
		for ($i = 0;$i < $this->infants; $i++,$j++) {
			$query_params['r_1_p_' . $j . '_no'] = $j;
			$query_params['r_1_p_' . $j . '_age'] = 1;
		}

		$response = $client->request('GET', 'search', [
			'query' => $query_params,
			'verify' => false
		]);

		return $response->getBody()->getContents(); // XML	
	}

	public function getResults($grouped = true)
	{
		$xml = new \SimpleXMLElement($this->doSearch());

		$accommodations = [];
		$results = [];

		foreach ($xml->Result->Offers->Offer as $offer) {
			$code = (string)$offer->Accom['Code'];
			$price = (int)$offer['Price'];
			$seats = (int)$offer->Transport['Avail'];
	
			if (!isset($accommodations[$code]['price']) ||
					$accommodations[$code]['price'] > $price) {
				$accommodations[$code]['price'] = $price;
			}
			if (!isset($accommodations[$code]['seats']) ||
					$accommodations[$code]['seats'] < $seats) {
				$accommodations[$code]['seats'] = $seats;
			}
	
			$accommodations[$code]['offers'][] = [
				'price' => $price,
				'seats' => $seats,
				'timestamp' => strtotime((string)$offer['Date']),
				'simplexml' => $offer
			];	
		}
		usort($accommodations, array($this, 'sortOffers'));


		foreach ($accommodations as $accomodation) {
			$offers = $accomodation['offers'];
			usort($offers, array($this, 'sortOffers'));

            if ($grouped) {
    			$offer = array_shift($offers);

    			$simplexml = $offer['simplexml'];
    			$results[] = ['simplexml' => $simplexml, 'alternatives' => $offers];
            } else {
                foreach ($offers as $offer) {
        			$results[] = $offer;
                }
            }
		}

		return $results;
	}
    
    
	public function sortOffers($item1, $item2) {
		$sort1 = $this->sort_params[0];
		$sort2 = $this->sort_params[1];
		$sort3 = $this->sort_params[2];
	  if ($item1[$sort1['name']] == $item2[$sort1['name']]) {
			if ($item1[$sort2['name']] == $item2[$sort2['name']]) {
				if (!isset($item1[$sort3['name']]) || $item1[$sort3['name']] == $item2[$sort3['name']]) {
					return 0;
				} else {
					return $item1[$sort3['name']] < $item2[$sort3['name']] ? $sort3['direction'] : $sort3['direction']*-1;
				}
			} else {
				return $item1[$sort2['name']] < $item2[$sort2['name']] ? $sort2['direction'] : $sort2['direction']*-1;
			}
		} else {
			return $item1[$sort1['name']] < $item2[$sort1['name']] ? $sort1['direction'] : $sort1['direction']*-1;
		}
	}
}