<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Entities\Bracket;

class BracketController
{
  public function GetCreate(Application $app, Request $request) {
    $currentUser = $app['phpdraft.LoginUserService']->GetCurrentUser();

    $bracket = new Bracket();

    $bracket->commish_id = $currentUser->id;
    $bracket->commish_name = $currentUser->name;

    $bracket->sports = $app['phpdraft.BracketDataRepository']->GetSports();
    $bracket->styles = $app['phpdraft.BracketDataRepository']->GetStyles();

    return $app->json($bracket, Response::HTTP_OK);
  }
	/*probably don't need
  public function Create(Application $app, Request $request) {
    $currentUser = $app['phpdraft.LoginUserService']->GetCurrentUser();
    $bracket = new Bracket();

    $bracket->commish_id = $currentUser->id;
    $bracket->commish_name = $currentUser->name;

    $bracket->bracket_name = $request->get('name');
    $bracket->bracket_sport = $request->get('sport');
    $bracket->bracket_status = "unbracketed";
    $bracket->bracket_style = $request->get('style');
    $bracket->bracket_rounds = (int)$request->get('rounds');
    $bracket->bracket_password = $request->get('password');
    $bracket->using_depth_charts = (bool)$request->get('using_depth_charts');

    $validity = $app['phpdraft.BracketValidator']->IsBracketValidForCreateAndUpdate($bracket);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $createPositionsModel = null;

	//probably don't need
    $response = $app['phpdraft.BracketService']->CreateNewBracket($bracket, $createPositionsModel);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }
  */

  public function Get(Application $app, Request $request) {
    $bracketId = (int)$request->get('bracket_id');
	die($bracketId);
    $bracket = $app['phpdraft.bracketRepository']->Load($bracketId);

    $bracket->sports = $app['phpdraft.bracketDataRepository']->GetSports();
    $bracket->styles = $app['phpdraft.bracketDataRepository']->GetStyles();
    $bracket->statuses = $app['phpdraft.bracketDataRepository']->GetStatuses();
    $bracket->depthChartPositions = $bracket->using_depth_charts == 1
      ? $app['phpdraft.DepthChartPositionRepository']->LoadAll($bracketId)
      : array();

    return $app->json($bracket, Response::HTTP_OK);
  }

  public function Update(Application $app, Request $request) {
    $bracketId = (int)$request->get('bracket_id');
    $bracket = $app['phpdraft.BracketRepository']->Load($bracketId);

    $bracket->bracket_name = $request->get('name');
    $bracket->bracket_sport = $request->get('sport');
    $bracket->bracket_style = $request->get('style');
    $bracket->bracket_rounds = (int)$request->get('rounds');
    $bracket->bracket_password = $request->get('password');
    $bracket->using_depth_charts = $request->get('using_depth_charts') == true ? 1 : 0;

    $validity = $app['phpdraft.BracketValidator']->IsBracketValidForCreateAndUpdate($bracket);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $createPositionsModel = null;

    if($bracket->using_depth_charts == 1) {
      $createPositionsModel = $this->_BuildDepthChartPositionModel($request);

      $positionValidity = $app['phpdraft.DepthChartPositionValidator']->AreDepthChartPositionsValid($createPositionsModel);

      if(!$positionValidity->success) {
        return $app->json($positionValidity, Response::HTTP_BAD_REQUEST);
      }
    }

    $response = $app['phpdraft.BracketService']->UpdateBracket($bracket, $createPositionsModel);

    return $app->json($response, $response->responseType());
  }

  public function UpdateStatus(Application $app, Request $request) {
    $bracketId = (int)$request->get('bracket_id');
    $bracket = $app['phpdraft.BracketRepository']->Load($bracketId);

    $oldStatus = $bracket->bracket_status;
    $bracket->bracket_status = $request->get('status');

    $validity = $app['phpdraft.BracketValidator']->IsBracketStatusValid($bracket, $oldStatus);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.BracketService']->UpdateBracketStatus($bracket, $oldStatus);

    return $app->json($response, $response->responseType());
  }

  public function Delete(Application $app, Request $request) {
    $bracketId = (int)$request->get('bracket_id');
    $bracket = $app['phpdraft.BracketRepository']->Load($bracketId);

    $response = $app['phpdraft.BracketService']->DeleteBracket($bracket);

    return $app->json($response, $response->responseType());
  }

  public function GetTimers(Application $app, Request $request) {
    $bracketId = (int)$request->get('bracket_id');
    $bracket = $app['phpdraft.BracketRepository']->Load($bracketId);

    $timers = $app['phpdraft.RoundTimeRepository']->GetBracketTimers($bracket);

    return $app->json($timers, Response::HTTP_OK);
  }

  public function SetTimers(Application $app, Request $request) {
    $bracketId = (int)$request->get('bracket_id');
    $bracket = $app['phpdraft.BracketRepository']->Load($bracketId);

    $createModel = new \PhpDraft\Domain\Models\RoundTimeCreateModel();
    $createModel->isRoundTimesEnabled = (bool)$request->get('isRoundTimesEnabled');

    if($createModel->isRoundTimesEnabled) {
      $roundTimesJson = $request->get('roundTimes');

      foreach($roundTimesJson as $roundTimeRequest) {
        $newRoundTime = new \PhpDraft\Domain\Entities\RoundTime();
        $newRoundTime->bracket_id = $bracketId;
        $newRoundTime->is_static_time = $roundTimeRequest['is_static_time'] == "true" ? 1 : 0;
        $newRoundTime->bracket_round = $newRoundTime->is_static_time ? null : (int)$roundTimeRequest['bracket_round'];
        $newRoundTime->round_time_seconds = (int)$roundTimeRequest['round_time_seconds'];

        $createModel->roundTimes[] = $newRoundTime;
      }
    }

    $validity = $app['phpdraft.RoundTimeValidator']->AreRoundTimesValid($createModel);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    //Save round times
    $response = $app['phpdraft.RoundTimeService']->SaveRoundTimes($bracket, $createModel);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }

  private function _BuildDepthChartPositionModel(Request $request, $bracketId = null) {
    $createPositionsModel = new \PhpDraft\Domain\Models\DepthChartPositionCreateModel();
    $createPositionsModel->depthChartEnabled = true;

    $depthChartPositionJson = $request->get('depthChartPositions');
    $display_order = 0;

    foreach($depthChartPositionJson as $depthChartPositionRequest) {
      $depthChartPosition = new \PhpDraft\Domain\Entities\DepthChartPosition();
      $depthChartPosition->bracket_id = $bracketId;
      $depthChartPosition->position = $depthChartPositionRequest['position'];
      $depthChartPosition->slots = (int)$depthChartPositionRequest['slots'];
      $depthChartPosition->display_order = $display_order++;

      $createPositionsModel->positions[] = $depthChartPosition;
    }

    return $createPositionsModel;
  }
}