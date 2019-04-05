<?php
namespace PhpDraft\Controllers;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use \PhpDraft\Domain\Entities\Draft;
use \PhpDraft\Domain\Entities\Pick;

class BracketController {

  public function Get(Application $app, Request $request) {
	 
	 /*debugging
	return json_encode($request->headers->all());
	
	*/
    $bracket_id = (int)$request->get('id');
    //$getDraftData = $request->get('get_draft_data') == 'true';

    if(empty($bracket_id ) || $bracket_id  == 0) {
      throw new \Exception("Unable to load draft.");
    }

    //Need to put it in headers so the client can easily add it to all requests (similar to token)
    //$password = $request->headers->get(DRAFT_PASSWORD_HEADER, '');
	$password = "";
    $bracket = $app['phpdraft.BracketRepository']->GetPublicBracket($request, $password);
	//var_dump($bracket);
    return $bracket;
  }	
  public function GetStandings(Application $app,Request $request) {		    $pool_id = (int)$request->get('pool_id');	    $contest_id = $request->get('contest_id');	    $league = $request->get('league');    if(empty($contest_id ) || $contest_id  == "" || empty($pool_id ) || $pool_id  == 0 ) {      throw new \Exception("Unable to load standings.");    }		
  
  $standings = "";
  $contest_type = $request->get("type");
  if($contest_type == "bracket") {
	  $standings = $app['phpdraft.BracketRepository']->GetPublicStandings($request, $pool_id,$contest_id,$league);	
  } else if($contest_type == "draft") {
	  $standings = $app['phpdraft.DraftRepository']->GetPublicStandings($request, $pool_id,$contest_id,$league);	
  }
  	
  
  
  
  return $standings;		}	
	public function SetUserPicks(Application $app, Request $request) {
		$response = $app['phpdraft.BracketRepository']->SaveUserPicks($request,$app);
		return  $response;
	}
  public function GetAll(Application $app, Request $request) {
    //TODO: Add paging for datatables
    $password = $request->headers->get(DRAFT_PASSWORD_HEADER, '');
	
    $brackets = $app['phpdraft.BracketRepository']->GetPublicBrackets($request, $password);
    return $app->json($brackets);
  }

  public function GetAllByCommish(Application $app, Request $request) {
    $commish_id = $request->get('commish_id');
    $password = $request->headers->get(DRAFT_PASSWORD_HEADER, '');

    $drafts = $app['phpdraft.DraftRepository']->GetPublicDraftsByCommish($request, $commish_id, $password);

    return $app->json($drafts);
  }
  public function GetAllByUser(Application $app, Request $request) {
	
    

    $drafts = $app['phpdraft.BracketRepository']->GetPublicDraftsByUser($request);

    return $app->json($drafts);
  }


  public function SendBracketReminders(Application $app,Request $request){
	  $request->request->set("sport",0);
	  $request->request->set("type","bracket");
    $response = $app['phpdraft.BracketRepository']->ZanSendBracketReminders($app,$request);
    // return $app->json($response, $response->responseType());
    return $app->json($response);
  }


  public function GetStats(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $response = $app['phpdraft.DraftService']->GetDraftStats($draft_id);

    return $app->json($response, $response->responseType());
  }
}