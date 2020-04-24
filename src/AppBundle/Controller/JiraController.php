<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;

class JiraController extends Controller
{
        
    /**
     * @Route("/jira/not_updated_by_atcore", name="jira_not_updated_by_atcore")
     */
    public function notUpdatedByAtcoreAction(Request $request)
    {
        $authBasic = 'QUhXOk1BUkdrczFk';
        $atcoreUsers = [
            'atcore_sup',
            'john.cascini'
        ];
        $priority = [
            'highest' => [
                '1 Critical',
                '2 High'
            ],
            'lowest' => [
                '3 Medium',
                '4 Low',
                'Cosmetic',
                'Support Request'
            ]
        ];
        $days = [
            'highest' => 7,
            'lowest' => 14
        ];
        $threshold = [
            'highest' => time()-60*60*24*$days['highest'],
            'lowest' => time()-60*60*24*$days['lowest']
        ];
        
		$client = new Client([
			// Base URI is used with relative requests
			'base_uri' => 'https://primerait.atlassian.net/rest/api/2/',
			// You can set any number of default request options.
			'timeout'  => 10.0,
//			'debug' => true
		]);
        
        $response = $client->request('POST', 'search', [
            'verify' => false,
            'headers' => [
                'Authorization' => 'Basic ' . $authBasic,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'jql' => 'project in (PRS, DESK) AND status in (Open, "Pending Approval", "In Progress", Reopened, "Waiting for Support", "Waiting for Customer", "Waiting for Triage") AND assignee in (' . implode($atcoreUsers, ', ') . ')',
                'startAt' => 0,
                'maxResults' => 100,
                'fields' => [
                    'id',
                    'key',
                    'summary',
                    'created',
                    'priority'
                ]
            ]
        ]);
        $json = json_decode($response->getBody()->getContents());
        
        $issues = [];
        foreach ($json->issues as $issue) {
            if ((in_array($issue->fields->priority->name, $priority['highest']) && strtotime($issue->fields->created) > $threshold['highest']) || (in_array($issue->fields->priority->name, $priority['lowest']) && strtotime($issue->fields->created) > $threshold['lowest'])) {
                continue;
            }

            $response = $client->request('GET', 'issue/' . $issue->id . '/comment', [
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Basic ' . $authBasic,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'orderBy' => 'created',
//                    'expand' => 'renderedBody'
                ]
            ]);
            
            $latestUpdate = null;
            $issueJson = json_decode($response->getBody()->getContents());
            foreach ($issueJson->comments as $comment) {
                if (in_array($comment->updateAuthor->name, $atcoreUsers) && strtotime($comment->updated) > $latestUpdate) {
                    $latestUpdate = strtotime($comment->updated);
                }
            }
            
            if ((in_array($issue->fields->priority->name, $priority['highest']) && $latestUpdate < $threshold['highest']) || (in_array($issue->fields->priority->name, $priority['lowest']) && $latestUpdate < $threshold['lowest'])) {
                $issues[$issue->key] = [
                    'priority' => [
                        'name' => $issue->fields->priority->name,
                        'icon' => $issue->fields->priority->iconUrl,
                    ],
                    'summary' => $issue->fields->summary,
                    'latest_update' => $latestUpdate,
                    'created' => strtotime($issue->fields->created)
                ];
            }
        }

        return $this->render('jira/not_updated.html.twig', [
            'issues' => $issues,
            'days' => $days
        ]);
    }
}