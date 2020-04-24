<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ErrataController extends Controller
{    
    /**
     * @Route("/errata/flights", name="errata_flights")
     */
    public function errataFlightsAction(Request $request)
    {
        $conn = $this->get('doctrine.dbal.atcore_connection');
        
        $sql = "SELECT
                  te.st_dt, te.end_dt, te.bk_from_dt, te.bk_to_dt,
                  te.mon, te.tue, te.wed, te.thu, te.fri, te.sat, te.sun,
                  te.arr_pt_tp, te.dep_pt_tp,te.dir_mth,
                  th.cd AS transport_cd,
                  pt1.pt_cd AS dep_cd, pt2.pt_cd AS arr_cd,
                  ll.lang_short_cd, nt.text AS erratum
                FROM ATCOMRES.AR_TRANSPORTERRATA te
                  INNER JOIN ATCOMRES.AR_LINKATTRIBUTE la
                    ON la.link_id = te.trans_errata_id
                    AND la.link_tp = 'TE'
                      INNER JOIN ATCOMRES.AR_ATTRIBUTENOTE an
                        ON an.link_att_id = la.link_att_id
                          INNER JOIN ATCOMRES.AR_NOTE nt
                            ON nt.att_note_id = an.att_note_id
                              INNER JOIN ATCOMRES.AR_LANGUAGELOCALE ll
                                ON ll.lang_locale_id = nt.lang_locale_id
                  LEFT JOIN ATCOMRES.AR_POINT pt1
                    ON pt1.pt_id = te.dep_pt_id
                  LEFT JOIN ATCOMRES.AR_POINT pt2
                    ON pt2.pt_id = te.arr_pt_id
                  LEFT JOIN ATCOMRES.AR_TRANSHEAD th
                    ON th.trans_head_id = te.trans_head_head_id
                WHERE
                  te.st_dt >= SYSDATE";

        $stmt = $conn->prepare($sql);
/*        $stmt->bindValue('dep_date_to', $dep_date_to, 'date');
        $stmt->bindValue('promotion', $promotion);*/

        $stmt->execute();
        $results = $stmt->fetchAll();

        return $this->render('errata/flights.html.twig', [
            'results' => $results
        ]);
    }
    
}