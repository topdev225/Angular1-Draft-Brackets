<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpDraft\Domain\Entities\ProPlayer;
use PhpDraft\Domain\Models\PhpDraftResponse;

class PickController {
  public function GetCurrent(Application $app, Request $request) {
	  
    $draft_id = $request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);
    
    $response = $app['phpdraft.PickService']->GetCurrentPick($draft);
	
    return $app->json($response, $response->responseType());
  }

  public function Add(Application $app, Request $request,$team = array()) {
    $draft_id = $request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);
    $pick_id = "";
	$pick = "";
	
	if(count($team) > 0) {
		$pick_id =	$team['team_id'];
	} else {
		$pick_id = $request->get('team_id');	
	}
    try {
      $pick = $app['phpdraft.PickRepository']->Load($pick_id);
		
		if(count($team) > 0) {
				
				$pick->team_name = $team["name"];
				$pick->team_mongo_id = $team["_id"];
				$pick->seed = $team["seed"];
				$pick->league = $draft->draft_sport; 
				$pick->conference = $team["conference"];
				
		} else {
				$name = $request->get('name') ? $request->get('name') : "None";
				$id = $request->get('_id') ? $request->get('_id') : "None";
				$pick->team_name = $name;
				
				$pick->team_mongo_id= $id['$oid'];
				$pick->league = $request->get('league');
				$seed = $request->get('seed') ? $request->get('seed') : "None";
				  $conference = $request->get('conference') ? $request->get('conference') : "None";
				  $pick->seed = $seed; 
				  $pick->conference = $conference; 
		}
		
		
     

    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to add pick #$pick_id";

      return $app->json($response, $response->responseType());
    }
    $validity = $app['phpdraft.PickValidator']->IsPickValidForAdd($draft, $pick);

    if(!$validity->success) {
      return $app->json($validity, $validity->responseType());
    } 
    $response = $app['phpdraft.PickService']->AddPick($draft, $pick,$request);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }
/*
  public function Update(Application $app, Request $request) {
	  //this has
    $draft_id = $request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);
    $pick_id = $request->get('team_id');

    try {
      $pick = $app['phpdraft.PickRepository']->Load($pick_id);
	  $name = $request->get('name') ? $request->get('name') : "None";
      $pick->team_name = $name;
      $pick->league = $request->get('league');
	  $seed = $request->get('seed') ? $request->get('seed') : "None";
      $pick->seed = $seed; 
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to edit pick #$pick_id";

      return $app->json($response, $response->responseType());
    }

    $validity = $app['phpdraft.PickValidator']->IsPickValidForUpdate($draft, $pick);

    if(!$validity->success) {
      return $app->json($validity, $validity->responseType());
    }

    $response = $app['phpdraft.PickService']->UpdatePick($draft, $pick, $request);

    return $app->json($response, $response->responseType());
  }
*/
  public function AlreadyDrafted(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $team_id = $request->get('team_id');
    $team = $request->get('team');
	
    $response = $app['phpdraft.PickService']->AlreadyDrafted($draft_id, $team,$request);

    return $app->json($response, $response->responseType());
  }

  //This will also be used as the "Create" method to populate a list of valid rounds for the view to use
  public function GetLast5(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $response = new PhpDraftResponse();

    try {
      $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

      $response->draft_rounds = $draft->draft_rounds;
      $response->last_5_picks = $app['phpdraft.PickRepository']->LoadLastPicks($draft_id, 5);
      $response->success = true;
    } catch(\Exception $e) {
      $response->success = false;
      $response->errors[] = "Unable to load last 5 picks.";
    }

    return $app->json($response, $response->responseType());
  }

  public function GetByRound(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);
    $round = (int)$request->get('draft_round');
    $response = new PhpDraftResponse();

    try {
      $response->round = $round;
      $response->round_picks = $app['phpdraft.PickRepository']->LoadRoundPicks($draft, $round, false, true);
      $response->success = true;
    } catch(\Exception $e) {
      $response->success = false;
      $response->errors[] = "Unable to load round #$round's picks";
    }

    return $app->json($response, $response->responseType());
  }
}