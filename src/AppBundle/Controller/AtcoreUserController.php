<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AtcoreUserController extends Controller
{
    /**
     * @Route("/atcore/users", name="atcore_users")
     */
    public function indexAction(Request $request)
    {
        $conn = $this->get('doctrine.dbal.atcore_connection');

        $users = [];
        $sql = "SELECT
                    usr.cd, usr.name, usr.email,
                    TO_CHAR(usr.login_dt_tm, 'YYYY-MM-DD HH24:MI:SS') AS last_login,
                    off.cd AS off_cd
                FROM
                    ATCOMRES.AR_USER usr
                        LEFT JOIN ATCOMRES.AR_OFFICENAME off
                            ON off.off_name_id = usr.off_def_id
                WHERE
                    usr.sts = 'ACT'";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        foreach ($results as $result) {
            $domain = '';
            $atPos = strpos($result['EMAIL'], '@');
            if ($atPos !== false) {
                $domain = substr($result['EMAIL'], $atPos);
            }
            
            $result['DOMAIN'] = $domain;
            $users[] = $result;
        }
        
        return $this->render('atcore/users.html.twig', [
            'users' => $users
        ]);
    }

}
