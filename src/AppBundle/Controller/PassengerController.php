<?php

namespace AppBundle\Controller;

use AppBundle\Service\Atcore;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Supplier;
use Symfony\Component\Validator\Constraints\DateTime;

use Primera\AtcomResBundle\Entity\WebService as WS;

class PassengerController extends Controller
{
    private $data;
    /**
     * @Route("/passenger/list", name="passenger_list")
     */
    public function listAction(Request $request)
    {
        $this->data = [];
        $results = [];
        $resultssr=[];
        $darray=[];
        $resultext = [];
        $search_name=array();

            if ( $request->query->has('start_from') || $request->query->has('end_to') ||
                $request->query->has('arr_cd') || $request->query->has('dep_cd')||
                $request->query->has('car_cd') || $request->query->has('ssr'))
                {
                    $car_cd="'".strtoupper($request->query->get('car_cd', null))."'";
                    $arrCd = "'".strtoupper($request->query->get('arr_cd', null))."'";
                    $depCd="'".strtoupper($request->query->get('dep_cd', null))."'";
                    $depFrom = $request->query->get('start_from', null);
                    $depFromDates = $depFrom ? new \DateTime($depFrom) : null;
                    //$ssr_val=$request->query->get('ssr');
                    //echo $ssr_val;exit;
                    if (!empty($request->query->get('ssr', null))) {
                        $ssr_val = $request->query->get('ssr', null);
                    }
                    else{
                            $ssr_val='';
                    }
                    if (!empty($request->query->get('extraformat', null))) {
                        $extraformat = $request->query->get('extraformat', null);
                    }
                    else{
                            $extraformat='';
                    }
                    $depTo = $request->query->get('end_to', null);
                    $depToDate = new \DateTime($depTo);
                    $depToDate->setTime(23, 59, 59);

                    $depToDates = $depTo ?  $depToDate : null;

                    $datesdepfrom=(array)$depFromDates;
                    $datesdepto=(array)$depToDates;
                    $datedepsfrom= "'".date($datesdepfrom['date'])."'";
                    $datedepsto= "'".date($datesdepto['date'])."'";

                    $to_depdate = date('Y-m-d H:i:s', strtotime($datesdepto['date'] . ' +1 day'));
                    $fromdatedep="'".date('d-M-Y',strtotime($datesdepfrom['date']))."'";
                    $todatedep="'".date('d-M-Y',strtotime($to_depdate))."'";
                    $excel=$request->query->get('excel', null);

                    $conn = $this->get('doctrine.dbal.atcore_connection');

                    $sql = sprintf("SELECT /*+ FIRST_ROWS(100)*/
                                ofn.cd AS CODE
                                ,ofn.name AS CARRIER
                                ,dep_pt.pt_cd AS DEP_PT
                                ,arr_pt.pt_cd AS ARR_PT
                                ,tir.route_num AS ROUTE_NUM
                                ,to_char(rs.st_dt,'dd-mm-yyyy') AS ST_DT
                                ,rs.res_id AS RES_ID
                                ,pax.pax_id as PAX_ID
                                ,pax.surname AS SURNAME
                                ,pax.forename AS FORENAME
                                ,CASE WHEN pax.age < 2 THEN 'Inf'
                                      WHEN pax.age BETWEEN 2 AND 12 THEN 'Chd'
                                      WHEN trim(pax.title) IS NULL AND pax.gender ='M' THEN 'Mr'
                                      WHEN trim(pax.title) IS NULL AND pax.gender ='F' THEN 'Mrs'
                                      ELSE pax.title
                                      END AS TITLE
                                ,pax.gender AS GENDER
                                ,pax.age AS AGE
                                ,'' as SSR0
                                ,'' as SSR1
                                ,'' as SSR2
                                ,'' as SSR3
                                ,'' as SSR4
                           FROM atcomres.ar_resservice rs
                           JOIN atcomres.ar_reservation R ON R.res_id   = rs.res_id
                           LEFT JOIN atcomres.ar_point dep_pt ON dep_pt.pt_id   = rs.dep_pt_id
                           LEFT JOIN atcomres.ar_point arr_pt ON arr_pt.pt_id   = rs.arr_pt_id
                           LEFT JOIN atcomres.ar_ressubservicepax rssp ON rssp.res_ser_id   = rs.res_ser_id
                           LEFT JOIN atcomres.ar_passenger pax ON pax.pax_id   = rssp.pax_id
                           LEFT JOIN atcomres.ar_transinvroute tir ON tir.trans_inv_route_id   = rs.ser_id
                           LEFT JOIN atcomres.ar_transinvroutesector tirs ON tirs.trans_inv_route_id   = tir.trans_inv_route_id
                           LEFT JOIN atcomres.ar_transinvsector tis ON tis.trans_inv_sec_id   = tirs.trans_inv_sec_id
                           LEFT JOIN atcomres.ar_officename ofn ON ofn.off_name_id   = tis.carrier_id
                           WHERE 1 = 1
                                 AND   rs.ser_tp = 'TRS'
                                 AND   r.bkg_sts = 'BKG'
                                 AND   rs.ser_sts != 'HIS'
                                 AND   ($depCd IS null OR dep_pt.pt_cd = $depCd)
                                 AND   ($arrCd IS null OR arr_pt.pt_cd = $arrCd)
                                 AND   rs.st_dt BETWEEN to_date($fromdatedep,'DD-MON-YYYY') and to_date($todatedep,'DD-MON-YYYY')
                                 AND   ($car_cd IS null OR ofn.cd = $car_cd)
                          ORDER BY ofn.cd
                                ,dep_pt.pt_cd
                                ,arr_pt.pt_cd
                                ,r.first_st_dt
                                ,rs.st_dt
                                ,r.res_id");

                $results = $conn->fetchAll($sql);

                /*This is added for Flight Extra Format*/
                if($extraformat == 1){
                  $sqlext = "SELECT /*+ FIRST_ROWS(10) */
                                DISTINCT pax.Pax_Id
                                    ,CASE WHEN pax.age < 2 THEN 'Inf'
                                          WHEN pax.age BETWEEN 2 AND 12 THEN 'Chd'
                                          WHEN trim(pax.title) IS NULL AND pax.gender ='M' THEN 'Mr'
                                          WHEN trim(pax.title) IS NULL AND pax.gender ='F' THEN 'Mrs'
                                          ELSE pax.title
                                          END AS Title
                                    ,pax.Surname
                                    ,pax.Forename
                                    ,pax.Pax_Tp
                                    ,To_Char(pax.Dt_Birth, 'DD-MON-YYYY') AS Dt_Birth
                                    ,'Y' AS Compartment
                                    ,NULL AS Linkedrph
                                    ,pax.Middle_Name
                                    ,pax.Gender
                                    ,Pt.Name AS Citizenship
                                    ,Pd.Cty1_Pt_Cd AS Cob
                                    ,CASE
                                       WHEN Nvl(Pd.Hph_Prfx, 0) > 0 THEN
                                        '+' || Pd.Hph_Prfx || ' ' || Pd.Hph_Area || Pd.Hph_Num
                                       ELSE
                                        Pd.Hph_Prfx || ' ' || Pd.Hph_Area || Pd.Hph_Num
                                     END AS Phone
                                    ,Pd.Mph_Num
                                    ,' ' AS Fax
                                    ,NULL AS Docid
                                    ,NULL AS Docidnum
                                    ,NULL AS Docidcountry
                                    ,NULL AS Docidexp
                                    ,NULL AS Countryofresidence
                                    ,NULL AS Specialservice
                                    ,Pd.Add1 || ' ' || Pd.Add2 || ' ' || Pd.Add3  AS Addr
                                    ,Pd.Add4 AS City
                                    ,Pd.Addpc AS Postalcd
                                    ,Pd.Add5 AS Subcountry
                                    ,NULL AS Redressnumber
                                    ,pax.Loyalty_No AS Knowntranum
                                    ,pax.Res_Id AS Bookingid
                                FROM atcomres.ar_resservice rs
                                JOIN atcomres.ar_reservation R ON R.res_id   = rs.res_id
                                LEFT JOIN atcomres.ar_point dep_pt ON dep_pt.pt_id   = rs.dep_pt_id
                                LEFT JOIN atcomres.ar_point arr_pt ON arr_pt.pt_id   = rs.arr_pt_id
                                LEFT JOIN atcomres.ar_ressubservicepax rssp ON rssp.res_ser_id   = rs.res_ser_id
                                LEFT JOIN atcomres.ar_passenger pax ON pax.pax_id   = rssp.pax_id
                                LEFT JOIN Atcomres.ar_passenger_address Pd ON Pd.Pax_Id = pax.Pax_Id
                                LEFT JOIN Atcomres.Ar_Point Pt ON (Pt.Pt_Cd = Pd.Cty1_Pt_Cd AND Pt.Pt_Tp = 'CTY1')
                                LEFT JOIN atcomres.ar_transinvroute tir ON tir.trans_inv_route_id   = rs.ser_id
                                LEFT JOIN atcomres.ar_transinvroutesector tirs ON tirs.trans_inv_route_id   = tir.trans_inv_route_id
                                LEFT JOIN atcomres.ar_transinvsector tis ON tis.trans_inv_sec_id   = tirs.trans_inv_sec_id
                                LEFT JOIN atcomres.ar_officename ofn ON ofn.off_name_id   = tis.carrier_id
                                WHERE 1 = 1
                                AND   rs.ser_tp = 'TRS'
				                AND   r.bkg_sts = 'BKG'
				                AND   rs.ser_sts != 'HIS'
                                AND   ($depCd IS null OR dep_pt.pt_cd = $depCd)
                                AND   ($arrCd IS null OR arr_pt.pt_cd = $arrCd)
                                AND   rs.st_dt BETWEEN to_date($fromdatedep,'DD-MON-YYYY') and to_date($todatedep,'DD-MON-YYYY')
                                AND   ($car_cd IS null OR ofn.cd = $car_cd)
                                ";
                  $resultext = $conn->fetchAll($sqlext);
                }

                $l=0;
                $search_name=array();
                foreach ($results as $result)
                {
                    $resid=$result['RES_ID'];
                    $std="'".$result['ST_DT']."'";
                    $sur="'".$result['SURNAME']."'";
                    $fore="'".$result['FORENAME']."'";
                    $paxid= $result['PAX_ID'];
                    if($ssr_val==1){
                        $sqlssr="SELECT
                                       rs.res_id,
                                       rs.st_dt,
                                       rs.ser_seq,
                                       rs.ser_tp,
                                       pax.surname,
                                       pax.forename,
                                       uc.cd as ssr,
                                       uc.name
                                   FROM
                                       atcomres.ar_resservice rs
                                       JOIN atcomres.ar_ressubservicepax rssp ON rssp.res_ser_id = rs.res_ser_id
                                       JOIN atcomres.ar_passenger pax ON pax.pax_id = rssp.pax_id
                                       JOIN atcomres.ar_flightextra fe ON fe.flt_extra_id = rs.ser_id
                                       JOIN atcomres.ar_usercodes uc ON uc.user_cd_id = fe.prc_cd_id
                                   WHERE
                                       rs.ser_tp = 'FEX'
                                       AND rs.ser_sts = 'CON'
                                       AND rs.res_id = $resid
                                       AND pax.pax_id = $paxid
                                       AND trunc(rs.st_dt) = to_date($std,'DD-MM-YYYY')
                                       --AND upper(pax.surname) = upper($sur)
                                       --AND upper(pax.forename) = upper($fore)
                                       AND ROWNUM <= 5
                                   ORDER BY
                                       rs.ser_seq";

                       $resultssr = $conn->fetchAll($sqlssr);

                       $i=0;

                       $asr=array();
                       foreach($resultssr as $sr){


                           $results[$l]["SSR".$i]=$sr['SSR'];


                            $i++;
                       }

                       $search_name[]=array('Code'=>$result['CODE'],
                                                   'Dep. airport'=>$result['DEP_PT'],
                                                   'Arr. airport'=>$result['ARR_PT'],
                                                   'Carrier'=>$result['CARRIER'],
                                                   'Route'=>$result['ROUTE_NUM'],
                                                   'Start Date'=>$result['ST_DT'],
                                                   'Reservation Id'=>$result['RES_ID'],
                                                   'Family Name'=>$result['SURNAME'],
                                                   'First Name'=>$result['FORENAME'],
                                                   'Title'=>$result['TITLE'],
                                                   'Gender'=>$result['GENDER'],
                                                   'Age'=>$result['AGE'],
                                                   'ssr0'=>$results[$l]['SSR0'],
                                                   'ssr1'=>$results[$l]['SSR1'],
                                                   'ssr2'=>$results[$l]['SSR2'],
                                                   'ssr3'=>$results[$l]['SSR3'],
                                                   'ssr4'=>$results[$l]['SSR4']

                                  );
                       $l++;
                    }
                    else{
                        $search_name[]=array('Code'=>$result['CODE'],
                                                   'Dep. airport'=>$result['DEP_PT'],
                                                   'Arr. airport'=>$result['ARR_PT'],
                                                   'Carrier'=>$result['CARRIER'],
                                                   'Route'=>$result['ROUTE_NUM'],
                                                   'Start Date'=>$result['ST_DT'],
                                                   'Reservation Id'=>$result['RES_ID'],
                                                   'Family Name'=>$result['SURNAME'],
                                                   'First Name'=>$result['FORENAME'],
                                                   'Title'=>$result['TITLE'],
                                                   'Gender'=>$result['GENDER'],
                                                   'Age'=>$result['AGE']
                                  );

                    }
                }
            }

        if($request->query->has('excel') ) {

            return $this->createExcel($request, $search_name);
        }
        else if($request->query->has('extraformat')){
          return $this->render('passenger/passengerlist.html.twig', [
            'resultext'=>$resultext,
            'search' => $request->query->all(),
        ]);

        }
        else{
            return $this->render('passenger/passengerlist.html.twig', [
                'results' => $results,
                'search' => $request->query->all(),
            ]);
        }
    }
}