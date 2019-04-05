<?php
namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class ManagerController {
  public function Get(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');

    $managers = $app['phpdraft.ManagerRepository']->GetManagersByDraftOrder($draftId);

    return $app->json($managers, Response::HTTP_OK);
  }

  public function Create(Application $app, Request $request) {
	  
    $draftId = (int)$request->get('draft_id');
		

    $manager = new Manager();
    $manager->draft_id = $draftId;
    $manager->manager_name = $request->get('manager_name');
    $manager->manager_name = $request->get('manager_name');
    $manager->contest_id = $request->get('contest_id');
    $manager->contest_type = $request->get('contest_type');
	$user =$app['phpdraft.LoginUserService']->GetCurrentUser($request);
    $manager->user_id = $user->id;
    $manager->username = $user->username;
    $manager->email = $user->email;
	
	//can do all the max players return true false to create new bracket here
    $validity = $app['phpdraft.ManagerValidator']->IsManagerValidForCreate($draftId, $manager);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->CreateNewManager($manager);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }

  public function CreateMany(Application $app, Request $request) {
	
    $draftId = (int)$request->get('draft_id');
    $contestId = $request->get('contest_id');
	if(!$draftId) {
		$draftId = (int)$request->get('bracket_id');
	}

    $managersJson = $request->get('managers');
    $newManagers = array();
	
    foreach($managersJson as $managerRequest) {
      $newManager = new Manager();
      $newManager->draft_id = $draftId;
      $newManager->contest_id = $contestId;
	  
	  echo "managers json";
	
      $newManager->manager_name = $managerRequest;
	 $user =$app['phpdraft.LoginUserService']->GetCurrentUser();
     
	 $newManager->user_id = $user->id;
		
      $newManagers[] = $newManager;
    }
	
	
    $validity = $app['phpdraft.ManagerValidator']->AreManagersValidForCreate($draftId, $newManagers);
	
    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }
	

    $response = $app['phpdraft.ManagerService']->CreateManyManagers($draftId, $contestId,$newManagers);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }

  public function Reorder(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');

    $managersIdJson = $request->get('ordered_manager_ids');
    $managerIds = array();

    foreach($managersIdJson as $managerId) {
      $managerIds[] = (int)$managerId;
    }

    $validity = $app['phpdraft.ManagerValidator']->AreManagerIdsValidForOrdering($draftId, $managerIds);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->ReorderManagers($managerIds);

    return $app->json($response, $response->responseType());
  }

  public function Update(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $managerId = $request->get('manager_id');

    try {
      $draft = $app['phpdraft.DraftRepository']->Load($draftId);
      $manager = $app['phpdraft.ManagerRepository']->Load($managerId);
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to load manager #$managerId";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $manager->manager_name = $request->get('name');

    $validity = $app['phpdraft.ManagerValidator']->IsManagerValidForUpdate($draft, $manager);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.ManagerService']->UpdateManager($manager);

    return $app->json($response, $response->responseType());
  }

  public function Delete(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $managerId = $request->get('manager_id');

    try {
      $manager = $app['phpdraft.ManagerRepository']->Load($managerId);
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to delete manager #$managerId";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    if($manager->draft_id != $draftId) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to delete manager #$managerId";

      return $app->json($response, Response::HTTP_BAD_REQUEST); 
    }

    $response = $app['phpdraft.ManagerService']->DeleteManager($manager);

    return $app->json($response, $response->responseType());
  }
}