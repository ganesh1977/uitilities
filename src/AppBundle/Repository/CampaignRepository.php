<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class CampaignRepository extends EntityRepository
{
    public function findAllActiveCampaigns($promotion = '')
    {
        $today = new \DateTime('now');
        $today->setTime(0, 0);

        $promotionSQL = '';
        if ($promotion != '') {
            $promotionSQL = 'AND c.prom_cd = :promotion';
        }

        return $this->getEntityManager()
            ->createQuery('
                SELECT c.id,
                c.prom_cd,
                c.promotion_cd,
                c.cd,
                c.description,
                c.st_dt,
                c.end_dt,
                c.created_at,
                c.updated_at,
                c.overrule_sort,
                CASE
                    WHEN c.end_dt < :today THEN 3
                    WHEN c.st_dt > :today THEN 2
                    ELSE 1
                END as status,
                COUNT(o.id) as offers
                FROM AppBundle:Campaign\Campaign AS c
                LEFT JOIN AppBundle:Campaign\Offer o WITH o.campaign = c.id AND o.deleted_at IS NULL
                WHERE c.deleted_at IS NULL
                '. $promotionSQL .'
                AND :promotion = :promotion
                GROUP BY c.id
                ORDER BY status ASC
            ')
            ->setParameter('today', $today)
            ->setParameter('promotion', $promotion)
            ->getResult();
    }

    public function findDuplicateCode($cd, $id = null) {

        $sql = sprintf('
              SELECT c.cd 
              FROM AppBundle:Campaign\Campaign AS c
              WHERE c.cd = :campaign_cd
              AND c.deleted_at IS NULL
              %s'
            , !empty($id) ? ' AND c.id != ' . $id : '');

        return $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('campaign_cd', $cd)
            ->getResult();
    }

    public function getNewPromCodeNumbers()
    {
        $sql = '
            SELECT COUNT(c.prom_cd) AS number, c.prom_cd
            FROM AppBundle:Campaign\Campaign AS c
            GROUP BY c.prom_cd
        ';

        $results = $this->getEntityManager()
            ->createQuery($sql)
            ->getArrayResult();

        $formattedResults = [];
        foreach ($results as $item) {
            $number = (int) $item['number'];
            $formattedResults[$item['prom_cd']] = ++$number;
        }

        return $formattedResults;
    }

    public function findAllDeletedCampaigns()
    {
        $sql = "
            SELECT c.id,
                c.prom_cd,
                c.cd,
                c.description,
                c.st_dt,
                c.end_dt,
                c.created_at,
                c.updated_at,
                c.deleted_at
            FROM AppBundle:Campaign\Campaign AS c
            WHERE c.deleted_at IS NOT NULL
        ";
        return $this->getEntityManager()
            ->createQuery($sql)
            ->getResult();
    }
}
