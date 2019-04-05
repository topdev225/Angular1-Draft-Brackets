<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Pick;

use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\MailMessage;

use PhpDraft\Domain\Entities\Manager;


class DraftRepository {
  private $app; 

  public function __construct(Application $app) {
    $this->app = $app;
  }

  //TODO: Add server-side paging
  public function GetPublicDrafts(Request $request/*$pageSize = 25, $page = 1*/, $password = '', $getUndrafted = false) {
    /*$page = (int)$page;
    $pageSize = (int)$pageSize;
    $startIndex = ($page-1) * $pageSize;

    if($startIndex < 0) {
      throw new \Exception("Unable to get drafts: incorrect paging parameters.");
    }*/

    //$draft_stmt = $this->app['db']->prepare("SELECT * FROM draft ORDER BY draft_create_time LIMIT ?, ?");
    $draft_stmt = "";
    if(!$getUndrafted ){
        $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d 
          LEFT OUTER JOIN users u 
          ON d.commish_id = u.id 
          ORDER BY draft_create_time DESC");
    } else {
        $draft_stmt = $this->app['db']->prepare("SELECT d.* FROM draft d WHERE draft_status='undrafted' ORDER BY draft_create_time DESC");  
    }

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');

    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    /*$draft_stmt->bindParam(1, $startIndex, \PDO::PARAM_INT);
    $draft_stmt->bindParam(2, $pageSize, \PDO::PARAM_INT);*/

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    $drafts = array();

    while($draft = $draft_stmt->fetch()) {
      $currentUserOwnsIt = !empty($current_user) && $draft->commish_id == $current_user->id;
      $currentUserIsAdmin = !empty($current_user) && $this->app['phpdraft.LoginUserService']->CurrentUserIsAdmin($current_user);

      $draft->draft_visible = empty($draft->draft_password);
      $draft->commish_editable = $currentUserOwnsIt || $currentUserIsAdmin;
    
        

      
      $draft->setting_up = $this->app['phpdraft.DraftService']->DraftSettingUp($draft);
      $draft->in_progress = $this->app['phpdraft.DraftService']->DraftInProgress($draft);
      $draft->complete = $this->app['phpdraft.DraftService']->DraftComplete($draft);
      $draft->is_locked = false;

      $draft->draft_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_create_time);
      $draft->draft_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
      $draft->draft_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_end_time);

      if(!$currentUserOwnsIt && !$currentUserIsAdmin && !$draft->draft_visible && $password != $draft->draft_password) {
        $draft->is_locked = true;
        $draft = $this->ProtectPrivateDraft($draft);
      }

      unset($draft->draft_password);

      $drafts[] = $draft;
    }

    return $drafts;
  }

    // function to check times and be able to send reminders for a week before or a day before
    public function ZanSendReminders(Application $app,Request $request) {
        $drafts = $app['phpdraft.DraftRepository']->GetPublicDrafts($request, $password,true);
        $draft_id = $request->get('draft_id');
        date_default_timezone_set('America/Los_Angeles'); 

        // Check if drafts are empty.   
        if ( ! empty( $drafts ) ) {
            // Loop through drafts.
            foreach ( $drafts as $draft ) {
                // var_dump($draft);die();

                $users = $this->app['phpdraft.ManagerRepository']->GetPublicManagersBracket($draft_id);
                // $user = array_shift($user);
                $user_email = array();
                
                $end_date = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
                $day_time_timezone = $end_date;

                $end_date_2 = new \DateTime($draft->draft_start_time);
                $draft_name = $draft->draft_name;
                $draft_id = $draft->draft_id;

                $day_time_timezone_2  = $end_date_2->format('h:i A') . " on " . $end_date_2->format('F j, Y');

                $date = $end_date;

                // Convert to timestamp.
                $date = strtotime( $date );
                // Get the time remaining.
                $remaining = $date - time();
                // Get days remaining.
                $days_remaining = floor( $remaining / 86400 );
                // Get hours remainings.
                $hours_remaining = floor( ( $remaining % 86400 ) / 3600 );
                
                foreach ( $users as $user ) {
                    $user_email = $user->email;
                    $message = new MailMessage();
                    $message->to_addresses = array (
                        $user->email => $user->name
                    );
                    // $message->to_addresses = $user_email;
                    $message->subject = "Reminder: Upcoming Draft Time";
                    $message->is_html = true;
                    // $message->CC = "";
                    $message->body = sprintf("Hi %s, <br/><br/>\n\n 

                        This email reminder is to confirm your registered for <strong>%s</strong> which is schedule at <strong>%s</strong>. Remember to join the Draft 5 to 10 minutes before scheduled Draft Time. Be prepared for the draft and do your homework (because your opponents did)! <br/><br/>\n\n

                        <img src='https://draftbrackets.com/images/draftbracketlogopng.png' alt='Draft Brackets Logo' title='Draft Brackets Logo' style='display:block' width='200' height='200' /> <br/><br/>\n\n

                        Please contact <a href=mailto:'support@draftbrackets.com' target='_top'>support@draftbrackets.com</a> for all your customer needs. We want to make your experience as positive as possible. <br/><br/>\n\n
                        ", $user->name, $draft_name, $day_time_timezone_2);

                    if ( $days_remaining > 0 ) {
                        ?><pre><?php 
                            // echo "There are $days_remaining days and $hours_remaining hours left <br><br>";
                            // print_r($message); 
                            // echo "<br><hr><br>";
                        ?></pre><?php
                    }
                    
                    switch( $days_remaining ) {
                        case 1:

                        case 2:
                        
                        case 7:
                            $this->app['phpdraft.EmailService']->SendMail($message);
                        break;
                    }
                    
                    if ( $days_remaining == 0 && $hours_remaining == 4 ) {
                        // $this->app['phpdraft.EmailService']->SendMail($message);
                    }
                } //End for each user
            } // End contacts foreach
        }
    }

  public function GetPublicDraftsByCommish(Request $request, $commish_id, $password = '') {
    $commish_id = (int)$commish_id;

    $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
    LEFT OUTER JOIN users u
    ON d.commish_id = u.id
    WHERE commish_id = ?
    ORDER BY draft_create_time DESC");

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');
    $draft_stmt->bindParam(1, $commish_id);

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    $drafts = array();

    while($draft = $draft_stmt->fetch()) {
      $currentUserOwnsIt = !empty($current_user) && $draft->commish_id == $current_user->id;
      $currentUserIsAdmin = !empty($current_user) && $this->app['phpdraft.LoginUserService']->CurrentUserIsAdmin($current_user);

      $draft->draft_visible = empty($draft->draft_password);
      $draft->commish_editable = $currentUserOwnsIt || $currentUserIsAdmin ;
      $draft->setting_up = $this->app['phpdraft.DraftService']->DraftSettingUp($draft);
      $draft->in_progress = $this->app['phpdraft.DraftService']->DraftInProgress($draft);
      $draft->complete = $this->app['phpdraft.DraftService']->DraftComplete($draft);
      $draft->is_locked = false;

      $draft->draft_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_create_time);
      $draft->draft_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
      $draft->draft_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_end_time);

      if(!$currentUserOwnsIt && !$currentUserIsAdmin && !$draft->draft_visible && $password != $draft->draft_password) {
        $draft->is_locked = true;
        $draft = $this->ProtectPrivateDraft($draft);
      }

      unset($draft->draft_password);

      $drafts[] = $draft;
    }

    return $drafts;
  }

  //Note: this method is to be used by admin section only
  public function GetAllDraftsByCommish($commish_id) {
    $commish_id = (int)$commish_id;

    $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
    LEFT OUTER JOIN users u
    ON d.commish_id = u.id
    WHERE commish_id = ?
    ORDER BY draft_create_time DESC");

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');
    $draft_stmt->bindParam(1, $commish_id);

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    $drafts = array();

    while($draft = $draft_stmt->fetch()) {
      $draft->draft_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_create_time);
      $draft->draft_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
      $draft->draft_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_end_time);

      $drafts[] = $draft;
    }

    return $drafts;
  }

  //Note: this method is to be used by admin section only
  public function GetAllCompletedDrafts() {
    $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
      LEFT OUTER JOIN users u
      ON d.commish_id = u.id
      WHERE d.draft_status = 'complete'
      ORDER BY draft_create_time DESC");

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    $drafts = array();

    while($draft = $draft_stmt->fetch()) {
      $draft->draft_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_create_time);
      $draft->draft_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
      $draft->draft_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_end_time);

      $drafts[] = $draft;
    }

    return $drafts;
  }

  public function GetPublicDraft(Request $request, $id, $getDraftData = false, $password = '') {
    $draft = new Draft();
    
    $cachedDraft = $this->GetCachedDraft($id);
    //turning off the cache
    //if($cachedDraft != null) { 
    if(false) {
        
      $draft = $cachedDraft;
    } else {
      $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
        LEFT OUTER JOIN users u
        ON d.commish_id = u.id
        WHERE d.draft_id = ? LIMIT 1");
      $draft_stmt->setFetchMode(\PDO::FETCH_INTO, $draft);
        
      $draft_stmt->bindParam(1, $id, \PDO::PARAM_INT);

      if(!$draft_stmt->execute() || !$draft_stmt->fetch()) {
        throw new \Exception("Unable to load draft");
      }

      $draft->using_depth_charts = $draft->using_depth_charts == 1;

      $this->SetCachedDraft($draft);
    }
    
    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    $currentUserOwnsIt = !empty($current_user) && $draft->commish_id == $current_user->id;
    $currentUserIsAdmin = !empty($current_user) && $this->app['phpdraft.LoginUserService']->CurrentUserIsAdmin($current_user);

    $draft->draft_visible = empty($draft->draft_password);
    
    if($draft->draft_status == 'undrafted') {
        $draft->commish_editable = $currentUserOwnsIt || $currentUserIsAdmin ;
    } else if($draft->draft_status == 'in_progress')  {
        //$draft->commish_editable = $this->app['phpdraft.DraftValidator']->checkIfManagerTurn($draft,$request) ;
        //$draft->commish_editable = $currentUserOwnsIt || $currentUserIsAdmin ;
        //this checks if manager owns it the allows the user to become the commish so he can enter the pick the problem with this is that when you become the commish you have access to the draft to reset the draft among other things
        if($this->app['phpdraft.DraftValidator']->checkIfManagerTurn($draft,$request)) {
            $draft->commish_editable = true;
        }

    }
    $draft->draft_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_create_time);
    $draft->draft_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
    $draft->draft_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_end_time);

    $draft->setting_up = $this->app['phpdraft.DraftService']->DraftSettingUp($draft);
    $draft->in_progress = $this->app['phpdraft.DraftService']->DraftInProgress($draft);
    $draft->complete = $this->app['phpdraft.DraftService']->DraftComplete($draft);

    if($getDraftData) {
      $draft->sports = $this->app['phpdraft.DraftDataRepository']->GetSports();
      $draft->styles = $this->app['phpdraft.DraftDataRepository']->GetStyles();
      $draft->statuses = $this->app['phpdraft.DraftDataRepository']->GetStatuses();
      $draft->teams = $this->app['phpdraft.DraftDataRepository']->GetTeams($draft->draft_sport);
      $draft->historical_teams = $this->app['phpdraft.DraftDataRepository']->GetHistoricalTeams($draft->draft_sport);
      $draft->positions = $this->app['phpdraft.DraftDataRepository']->GetPositions($draft->draft_sport);
      if($draft->using_depth_charts) {
        $draft->depthChartPositions = $this->app['phpdraft.DepthChartPositionRepository']->LoadAll($draft->draft_id);
      }
    }

    $draft->is_locked = false;

    if(!$currentUserOwnsIt && !$currentUserIsAdmin && !$draft->draft_visible && $password != $draft->draft_password) {
      $draft->is_locked = true;
      $draft = $this->ProtectPrivateDraft($draft);
    }

    unset($draft->draft_password);

    return $draft;
  }

  /*
  * This method is only to be used internally or when the user has been verified as owner of the draft (or is admin)
  * (in other words, don't call this then return the result as JSON!)
  */
  public function Load($id, $bustCache = false) {
    
    $draft = new Draft();

    $cachedDraft = $this->GetCachedDraft($id);
    
    if($bustCache || $cachedDraft == null) {
      $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
      LEFT OUTER JOIN users u
      ON d.commish_id = u.id
      WHERE draft_id = ? LIMIT 1");

      $draft_stmt->setFetchMode(\PDO::FETCH_INTO, $draft);
        
      $draft_stmt->bindParam(1, $id, \PDO::PARAM_INT);
        
      if(!$draft_stmt->execute() || !$draft_stmt->fetch()) {
        throw new \Exception("Unable to load draft");
      }

      $draft->using_depth_charts = $draft->using_depth_charts == 1;

      if($bustCache) {
        $this->UnsetCachedDraft($draft->draft_id);
      }

      $this->SetCachedDraft($draft);
    } else {
      $draft = $cachedDraft;
    }

    $draft->draft_rounds = (int)$draft->draft_rounds;

    return $draft;
  }

  public function Create(Draft $draft) {
    $insert_stmt = $this->app['db']->prepare("INSERT INTO draft
      (draft_id, commish_id, draft_create_time, draft_name, draft_sport, draft_status, draft_style, draft_rounds, draft_password, using_depth_charts)
      VALUES
      (NULL, ?, UTC_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?)");

    $insert_stmt->bindParam(1, $draft->commish_id);
    $insert_stmt->bindParam(2, $draft->draft_name);
    $insert_stmt->bindParam(3, $draft->draft_sport);
    $insert_stmt->bindParam(4, $draft->draft_status);
    $insert_stmt->bindParam(5, $draft->draft_style);
    $insert_stmt->bindParam(6, $draft->draft_rounds);
    $insert_stmt->bindParam(7, $draft->draft_password);
    $insert_stmt->bindParam(8, $draft->using_depth_charts);

    if(!$insert_stmt->execute()) {
      throw new \Exception("Unable to create draft.");
    }

    $draft = $this->Load((int)$this->app['db']->lastInsertId(), true);

    return $draft;
  }

  //Excluded properties in update:
  //draft_start_time/draft_end_time - updated in separate operations at start/end of draft
  //draft_current_round/draft_current_pick - updated when new picks are made
  //draft_counter - call IncrementDraftCounter instead - this call's made a lot independently of other properties.
  //draft_status - separate API call to update the draft status
  public function Update(Draft $draft) {
    $update_stmt = $this->app['db']->prepare("UPDATE draft
      SET commish_id = ?, draft_name = ?, draft_sport = ?,
      draft_style = ?, draft_password = ?, draft_rounds = ?,
      using_depth_charts = ?
      WHERE draft_id = ?");

    $draft->using_depth_charts = $draft->using_depth_charts;

    $update_stmt->bindParam(1, $draft->commish_id);
    $update_stmt->bindParam(2, $draft->draft_name);
    $update_stmt->bindParam(3, $draft->draft_sport);
    $update_stmt->bindParam(4, $draft->draft_style);
    $update_stmt->bindParam(5, $draft->draft_password);
    $update_stmt->bindParam(6, $draft->draft_rounds);
    $update_stmt->bindParam(7, $draft->using_depth_charts);
    $update_stmt->bindParam(8, $draft->draft_id);

    if(!$update_stmt->execute()) {
      throw new \Exception("Unable to update draft.");
    }

    $this->ResetDraftCache($draft->draft_id);

    return $draft;
  }
    public function GetPublicStandings(Request $request, $pool_id,$contest_id,$league) {
        //get selections neabubg tge teams that managers in this league selected, a join on managers and teams then get the teams from mongo and grab the scores
        $query = http_build_query(array('query'=> 'query{teams(league:"'.$league.'") {
          wins
          losses  
          _id
        }}'));
            //change this to env variable
    $url = MONGO_API_BASE_URL.$query;
    
    $teams = $this->app['phpdraft.BracketRepository']->getUrlContents($url);
    
    $teamsDecoded= json_decode($teams);
    
    $teamsArray = $teamsDecoded->data->teams;
    

    
    $picks_stmt = $this->app['db']->prepare("SELECT *,u.* FROM teams LEFT JOIN users AS u  ON u.id = teams.user_id
     WHERE draft_id = ? AND contest_id= ?");
      $picks_stmt->bindParam(1, $pool_id);
      $picks_stmt->bindParam(2, $contest_id);
    if(!$picks_stmt->execute()) {
      throw new \Exception("Unable to update draft status.");
    }
    $winsTotalToUser = [];
    
    $keyIsTeamId = [];
    
    foreach($teamsArray as $team) {
        $keyIsTeamId[$team->_id] = array("wins" => $team->wins, "losses" => $team->losses);
    }
    $userNameToId = [];
     while($pick = $picks_stmt->fetch()) {

         $winsTotalToUser[$pick["username"]] =  $winsTotalToUser[$pick["username"]] + $keyIsTeamId[$pick["team_mongo_id"]]["wins"];
         $userNameToId[$pick["username"]] = $pick["user_id"];
    }
    $properFormatForStandings = [];
    foreach($winsTotalToUser as $key => $value) {
        $properFormatForStandings[$value] = array("username" => $key,"score" => $value, "user_id" => $userNameToId[$key],"bracket_id"=>$pool_id,"bracket_sport"=>$league,"contest_id" =>$contest_id);
    }

    //var_dump($picks);
    
    
        return json_encode($properFormatForStandings);
    }
  public function UpdateStatus(Draft $draft) {
      //echo 'here';
    $status_stmt = $this->app['db']->prepare("UPDATE draft
      SET draft_status = ? WHERE draft_id = ?");

    $status_stmt->bindParam(1, $draft->draft_status);
    $status_stmt->bindParam(2, $draft->draft_id);

    if(!$status_stmt->execute()) {
      throw new \Exception("Unable to update draft status.");
    }

    $this->ResetDraftCache($draft->draft_id);

    return $draft;
  }

  public function UpdateStatsTimestamp(Draft $draft) {
    $status_stmt = $this->app['db']->prepare("UPDATE draft
      SET draft_stats_generated = UTC_TIMESTAMP() WHERE draft_id = ?");

    $status_stmt->bindParam(1, $draft->draft_id);

    if(!$status_stmt->execute()) {
      throw new \Exception("Unable to update draft's stats timestamp.");
    }

    $this->ResetDraftCache($draft->draft_id);

    return $draft;
  }
  public function IncrementDraftCounter(Draft $draft) {
    $incrementedCounter = (int)$draft->draft_counter + 1;

    $increment_stmt = $this->app['db']->prepare("UPDATE draft
      SET draft_counter = ? WHERE draft_id = ?");

    $increment_stmt->bindParam(1, $incrementedCounter);
    $increment_stmt->bindParam(2, $draft->draft_id);

    if(!$increment_stmt->execute()) {
      throw new \Exception("Unable to increment draft counter.");
    }

    $this->ResetDraftCache($draft->draft_id);

    return $incrementedCounter;
  }

  //$next_pick can't be type-hinted - can be null
  public function MoveDraftForward(Draft $draft, $next_pick) {
    if ($next_pick !== null) {
      $draft->draft_current_pick = (int) $next_pick->player_pick;
      $draft->draft_current_round = (int) $next_pick->player_round;

      $stmt = $this->app['db']->prepare("UPDATE draft SET draft_current_pick = ?, draft_current_round = ? WHERE draft_id = ?");
      $stmt->bindParam(1, $draft->draft_current_pick);
      $stmt->bindParam(2, $draft->draft_current_round);
      $stmt->bindParam(3, $draft->draft_id);

      if (!$stmt->execute()) {
        throw new \Exception("Unable to move draft forward.");
      }
    } else {
      $draft->draft_status = 'complete';
      $stmt = $this->app['db']->prepare("UPDATE draft SET draft_status = ?, draft_end_time = UTC_TIMESTAMP() WHERE draft_id = ?");
      $stmt->bindParam(1, $draft->draft_status);
      $stmt->bindParam(2, $draft->draft_id);

      if (!$stmt->execute()) {
        throw new \Exception("Unable to move draft forward.");
      }
    }

    $this->ResetDraftCache($draft->draft_id);

    return $draft;
  }

  //Used when we move a draft from "undrafted" to "in_progress":
  //Resets the draft counter
  //Sets the current pick and round to 1
  //Sets the draft start time to UTC now, nulls out end time
  public function SetDraftInProgress(Draft $draft) {
    $reset_stmt = $this->app['db']->prepare("UPDATE draft
      SET draft_counter = 0, draft_current_pick = 1, draft_current_round = 1,
       draft_end_time = NULL
      WHERE draft_id = ?"); 

    $reset_stmt->bindParam(1, $draft->draft_id);

    if(!$reset_stmt->execute()) {
      throw new \Exception("Unable to set draft to in progress.");
    }

    $this->ResetDraftCache($draft->draft_id);

    return 0;
  }

  public function NameIsUnique($name, $id = null) {
    if(!empty($id)) {
      $name_stmt = $this->app['db']->prepare("SELECT draft_name FROM draft WHERE draft_name LIKE ? AND draft_id <> ?");
      $name_stmt->bindParam(1, $name);
      $name_stmt->bindParam(2, $id);
    } else {
      $name_stmt = $this->app['db']->prepare("SELECT draft_name FROM draft WHERE draft_name LIKE ?");
      $name_stmt->bindParam(1, $name);
    }

    if(!$name_stmt->execute()) {
      throw new \Exception("Draft name '%s' is invalid", $name);
    }

    return $name_stmt->rowCount() == 0;
  }

  public function DeleteDraft($draft_id) {
    $delete_stmt = $this->app['db']->prepare("DELETE FROM draft WHERE draft_id = ?");
    $delete_stmt->bindParam(1, $draft_id);

    if(!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete draft $draft_id.");
    }

    $this->UnsetCachedDraft($draft_id);

    return;
  }

  private function ResetDraftCache($draft_id) {
    $draft = $this->Load($draft_id, true);
  }

  private function SetCachedDraft(Draft $draft) {
    $this->app['phpdraft.DatabaseCacheService']->SetCachedItem("draft$draft->draft_id", $draft);
  }

  private function GetCachedDraft($draft_id) {
    return $this->app['phpdraft.DatabaseCacheService']->GetCachedItem("draft$draft_id");
  }

  private function UnsetCachedDraft($draft_id) {
    $this->app['phpdraft.DatabaseCacheService']->DeleteCachedItem("draft$draft_id");
  }

  private function ProtectPrivateDraft(Draft $draft) {
    $draft->draft_sport = '';
    $draft->draft_status = '';
    $draft->setting_up = '';
    $draft->in_progress = '';
    $draft->complete = '';
    $draft->draft_style = '';
    $draft->draft_rounds = '';
    $draft->draft_counter = '';
    $draft->draft_start_time = null;
    $draft->draft_end_time = null;
    $draft->draft_current_pick = '';
    $draft->draft_current_round = '';
    $draft->draft_create_time = '';
    $draft->draft_stats_generated = '';
    $draft->nfl_extended = null;
    $draft->sports = null;
    $draft->styles = null;
    $draft->statuses = null;
    $draft->teams = null;
    $draft->positions = null;
    $draft->using_depth_charts = null;
    $draft->depthChartPositions = null;

    return $draft;
  }
public  function siteURL()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'].'/';
    return $protocol.$domainName;
}
}