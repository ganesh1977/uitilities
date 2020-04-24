<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class OfferRepository extends EntityRepository
{
    public function findAllActiveOffersOnCampaign($campaignId, $overruleSort)
    {
        $sql = sprintf('
                SELECT o.id, o.st_dt, o.end_dt, o.carr_cd, o.stay, o.dep_cd, o.arr_cd, o.stc_stk_cd accom_cd, o.rm_cd, o.bb, o.sort, o.created_at, o.updated_at
                FROM AppBundle:Campaign\Offer AS o
                WHERE o.deleted_at IS NULL
                AND o.campaign = :campaign
                ORDER BY %s ASC',
                $overruleSort ? 'o.sort' : 'o.st_dt');

        return $this->getEntityManager()
            ->createQuery($sql)
            // ->setParameter('today', new \DateTime('now'))
            ->setParameter('campaign', $campaignId)
            ->getResult();

        /*
            If needed add:
            AND o.st_dt < :today
            AND o.end_dt > :today
        */
    }

    public function findDuplicateRow($campaignId, $stDt, $endDt, $stay, $depCd, $arrCd, $stcStkCd, $rmCd, $bb, $id = null) {

        $sql = sprintf('
              SELECT o.id
              FROM AppBundle:Campaign\Offer AS o
              WHERE o.campaign = :campaign_cd
              AND o.st_dt = :st_dt
              AND o.end_dt = :end_dt
              AND o.stay = :stay
              AND o.dep_cd = :dep_cd
              AND o.arr_cd = :arr_cd
              AND o.stc_stk_cd = :stc_stk_cd
              AND o.rm_cd %s
              AND o.bb %s
              AND o.deleted_at IS NULL
              %s'
            , is_null($rmCd) ? " IS NULL " : " = '" . $rmCd . "'"
            , is_null($bb) ? " IS NULL " : " = '" . $bb . "'"
            , !empty($id) ? ' AND o.id != ' . $id : '');

        return $this->getEntityManager()
            ->createQuery($sql)
            ->setParameter('campaign_cd', $campaignId)
            ->setParameter('st_dt', $stDt, 'date')
            ->setParameter('end_dt', $endDt, 'date')
            ->setParameter('stay', $stay)
            ->setParameter('dep_cd', $depCd)
            ->setParameter('arr_cd', $arrCd)
            ->setParameter('stc_stk_cd', $stcStkCd)
            ->getResult();

    }
}
