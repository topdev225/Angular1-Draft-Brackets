<?php
namespace PhpDraft\Controllers;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use \PhpDraft\Domain\Entities\Draft;
use \PhpDraft\Domain\Entities\Pick;

class DraftController {
  public function Get(Application $app, Request $request) {
	
    $draft_id = (int)$request->get('id');
    $getDraftData = $request->get('get_draft_data') == 'true';

    if(empty($draft_id) || $draft_id == 0) {
      throw new \Exception("Unable to load draft.");
    }

    //Need to put it in headers so the client can easily add it to all requests (similar to token)
    $password = $request->headers->get(DRAFT_PASSWORD_HEADER, '');

    $draft = $app['phpdraft.DraftRepository']->GetPublicDraft($request, $draft_id, $getDraftData, $password);

    return $app->json($draft);
  }

  public function GetAll(Application $app, Request $request) {
    //TODO: Add paging for datatables
    $password = $request->headers->get(DRAFT_PASSWORD_HEADER, '');
    $drafts = $app['phpdraft.DraftRepository']->GetPublicDrafts($request, $password);

    return $app->json($drafts);
  }

  public function GetAllByCommish(Application $app, Request $request) {
    $commish_id = $request->get('commish_id');
    $password = $request->headers->get(DRAFT_PASSWORD_HEADER, '');

    $drafts = $app['phpdraft.DraftRepository']->GetPublicDraftsByCommish($request, $commish_id, $password);

    return $app->json($drafts);
  }

  public function GetStats(Application $app, Request $request) {
    $draft_id = $request->get('draft_id');
    $response = $app['phpdraft.DraftService']->GetDraftStats($draft_id);

    return $app->json($response, $response->responseType());
  }

  public function SendReminders(Application $app,Request $request){
	  $response = $app['phpdraft.DraftRepository']->ZanSendReminders($app,$request);
    // return $app->json($response, $response->responseType());
    return $app->json($response);
  }
  public function StartDrafts(Application $app,Request $request){
	  $drafts = $app['phpdraft.DraftRepository']->GetPublicDrafts($request, $password,true);
	  date_default_timezone_set('America/Los_Angeles'); 
		$current = date('Y-m-d H:i:s', time());
		//var_dump($drafts);
	  foreach($drafts as $draft) {
		 
		  $dateTimeString = trim(str_replace("UTC","",$draft->draft_start_time));
		 // var_dump($draft["draft_name"]);
		 
		  if(trim($dateTimeString) !== "" && $dateTimeString !== "NULL" && $dateTimeString !== NULL){
			  //var_dump($draft);
			  $dateDraft = date_create_from_format('Y-m-d H:i:s', $dateTimeString);
				
				//if undrafted time greater than time it's time to start
				if($current > $dateDraft->format('Y-m-d H:i:s')){
					 var_dump("starting");
					$request->attributes->set("draft_id",$draft->draft_id) ;
					$request->attributes->set("status","in_progress") ;
					$app['commish.draft.controller']->UpdateStatus( $app, $request);
				}
				
		  }
			
	  }
	  
		
	  die();
  }
}