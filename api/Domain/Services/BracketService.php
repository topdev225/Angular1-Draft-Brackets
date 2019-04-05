<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Bracket;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\DepthChartPositionCreateModel;

class BracketService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function GetCurrentPick($bracket_id) {
    $bracket_id = (int)$bracket_id;

    $bracket = $this->app['phpdraft.BracketRepository']->Load($bracket_id);

    return (int)$bracket->bracket_current_pick;
  }
  
  /*Delete creating from node js

  public function CreateNewBracket(Bracket $bracket, DepthChartPositionCreateModel $depthChartModel = null) {
    $response = new PhpDraftResponse();

    try {
      $bracket = $this->app['phpdraft.BracketRepository']->Create($bracket);

      if($bracket->using_depth_charts) {
        $depth_charts = $this->app['phpdraft.DepthChartPositionRepository']->Save($depthChartModel, $bracket->bracket_id);
      }

      $response->success = true;
      $response->bracket = $bracket;
    }catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }
  */

  public function UpdateBracket(Bracket $bracket, DepthChartPositionCreateModel $depthChartModel = null) {
    $response = new PhpDraftResponse();

    try {
      $bracket = $this->app['phpdraft.BracketRepository']->Update($bracket);

      if($bracket->using_depth_charts) {
        $this->app['phpdraft.DepthChartPositionRepository']->DeleteAllDepthChartPositions($bracket->bracket_id);
        $depth_charts = $this->app['phpdraft.DepthChartPositionRepository']->Save($depthChartModel, $bracket->bracket_id);
      }

      $response->success = true;
      $response->bracket = $bracket;
    }catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function UpdateBracketStatus(Bracket $bracket, $old_status) {
    $response = new PhpDraftResponse();

    try {
      $bracket = $this->app['phpdraft.BracketRepository']->UpdateStatus($bracket);

      //If we know we're moving from unbracketed to in progress, perform the necessary setup steps:
      if($bracket->bracket_status != $old_status && $bracket->bracket_status == "in_progress") {
        //Delete all trades
        $this->app['phpdraft.TradeRepository']->DeleteAllTrades($bracket->bracket_id);
        //Delete all picks
        $this->app['phpdraft.PickRepository']->DeleteAllPicks($bracket->bracket_id);
        //Setup new picks
        $managers = $this->app['phpdraft.ManagerRepository']->GetManagersByBracketOrder($bracket->bracket_id);
        $descending_managers = $this->app['phpdraft.ManagerRepository']->GetManagersByBracketOrder($bracket->bracket_id, true);
        $this->app['phpdraft.PickRepository']->SetupPicks($bracket, $managers, $descending_managers);
        //Set bracket to in progress
        $this->app['phpdraft.BracketRepository']->SetBracketInProgress($bracket);
      }

      $response->success = true;
      $response->bracket = $bracket;
    }catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function DeleteBracket(Bracket $bracket) {
    $response = new PhpDraftResponse();

    try {
      //Delete all trades
      $this->app['phpdraft.TradeRepository']->DeleteAllTrades($bracket->bracket_id);
      //Delete all depth chart positions
      $this->app['phpdraft.DepthChartPositionRepository']->DeleteAllDepthChartPositions($bracket->bracket_id);
      //Delete all picks
      $this->app['phpdraft.PickRepository']->DeleteAllPicks($bracket->bracket_id);
      //Delete all managers
      $this->app['phpdraft.ManagerRepository']->DeleteAllManagers($bracket->bracket_id);
      //Delete all round timers
      $this->app['phpdraft.RoundTimeRepository']->DeleteAll($bracket->bracket_id);
      //Delete the bracket
      $this->app['phpdraft.BracketRepository']->DeleteBracket($bracket->bracket_id);

      $response->success = true;
    } catch(\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function GetBracketStats($bracket_id) {
    $response = new PhpDraftResponse();

    try {
      $response->bracket_statistics = $this->app['phpdraft.BracketStatsRepository']->LoadBracketStats($bracket_id);
      $response->success = true;
    } catch(\Exception $e) {
      $message = $e->getMessage();
      $response->success = false;
      $response->errors[] = $message;
    }

    return $response;
  }

  public function BracketSettingUp(Bracket $bracket) {
    return $bracket->bracket_status == "unbracketed";
  }

  public function BracketInProgress(Bracket $bracket) {
    return $bracket->bracket_status == "in_progress";
  }

  public function BracketComplete(Bracket $bracket) {
    return $bracket->bracket_status == "complete";
  }
}