<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Pick;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Models\PickSearchModel;

use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\MailMessage;

use PhpDraft\Domain\Entities\Manager;

class PickRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  //Used for when a pick is entered (made)
  public function AddPick(Pick $pick, Draft $draft, Request $request) {
	//var_dump($pick);
	$myTurn = $this->app['phpdraft.DraftValidator']->checkIfManagerTurn($draft,$request);
	if($myTurn) {
		  
		//var_dump($pick);die();
		$add_stmt = $this->app['db']->prepare("UPDATE teams 
		  SET team_name = ?, league = ?, seed = ?, conference = ?, player_counter = ?, pick_time = ?, pick_duration = ?,team_mongo_id = ? WHERE team_id = ?");

		$add_stmt->bindParam(1, $pick->team_name);
		$add_stmt->bindParam(2, $pick->league);
		$add_stmt->bindParam(3, $pick->seed);
		$add_stmt->bindParam(4, $pick->conference);
		$add_stmt->bindParam(5, $pick->player_counter);
		$add_stmt->bindParam(6, $pick->pick_time);
		$add_stmt->bindParam(7, $pick->pick_duration);
		$add_stmt->bindParam(8, $pick->team_mongo_id);
		$add_stmt->bindParam(9, $pick->team_id);

		if (!$add_stmt->execute()) {
		  throw new \Exception("Unable to save pick #$pick->player_pick.");
		}
	} else {
		throw new \Exception("Unable to save pick #$pick->player_pick. It is not your turn to pick");
	}

    return $pick;
  }

  public function Load($id) {
    $pick = new Pick();

    $pick_stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name, m.manager_id FROM teams p 
      LEFT OUTER JOIN managers m ON p.manager_id = m.manager_id
      WHERE team_id = ? LIMIT 1");
    $pick_stmt->bindParam(1, $id);

    $pick_stmt->setFetchMode(\PDO::FETCH_INTO, $pick);

    if(!$pick_stmt->execute() || !$pick_stmt->fetch()) {
      throw new \Exception("Unable to load pick " . $id);
    }

    $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;

    return $pick;
  }

  //Is used by public draft board, need to consider serpentine or standard ordering
  public function LoadAll(Draft $draft) {
    $picks = array();

    $sort = true;
    for ($i = 1; $i <= $draft->draft_rounds; ++$i) {
      if ($draft->draft_style == "serpentine") {
        $picks[] = $this->LoadRoundPicks($draft, $i, $sort, false);
        $sort = $sort ? false : true;
      } else {
        $picks[] = $this->LoadRoundPicks($draft, $i, true, false);
      }
    }

    return $picks;
  }

    // Load picks by user
    public function ZanLoadUserPicks(Request $request, $draft) {
       //debug https://draftbrackets.com/api/send_user_picks/draft/86
        // var_dump($pick);die();
		$draft_id = $request->get('draft_id');
		$draft = isset($draft->draft_id) ? $draft : $this->app['phpdraft.DraftRepository']->Load($draft_id);
		$users = $this->app['phpdraft.ManagerRepository']->GetPublicManagersBracket($draft_id);
		
		foreach ( $users as $user ) {
            $manager_id = $user->manager_id;
			$manager_picks = $this->LoadManagerPicksEmail($manager_id, $draft, true);
		
            ?><pre><?php 
                // print_r($draft);
                // print_r($manager_picks);
                // die();
            ?></pre><?php
            date_default_timezone_set('America/Los_Angeles'); 

            // Check if drafts are empty.   
            if ( ! empty( $draft ) ) {
                
                // Loop through drafts.
                    //$pick = $draft->draft_current_pick;
                    $draft_status = $draft->draft_status;
                    $draft_name = $draft->draft_name;
                    // $pick_date = date('d/m/Y h:i:s e');
                    $pick_date = date('h:i A') . " on " . date('F j, Y'); 
                    $user_picks = array();
                    foreach ( $manager_picks as $manager_pick ) {
                        $user_picks[] = "Round: " . $manager_pick->player_round . ", Pick: " . $manager_pick->player_pick . ", Team: " . $manager_pick->team_name;
                    }
                    $user_picks = implode("<br>", $user_picks);
                    
                    $message = new MailMessage();
                    $message->to_addresses = array (
                        $user->email => $user->name
                    );
                    $message->subject = "Your Draft Picks Are Confirmed";
                    $message->is_html = true;
                    // $message->CC = "";
                    $message->body = sprintf("Hi %s, <br/><br/>\n\n 

                        This email notification is to confirm your picks were successfully completed for <strong>%s</strong> on <strong>%s</strong>. Your picks look solid and we wish you best of luck! <br/><br/>\n\n

                        <strong>Picks: <br>%s </strong> <br/><br/>\n\n

                        <img src='https://draftbrackets.com/images/draftbracketlogopng.png' alt='Draft Brackets Logo' title='Draft Brackets Logo' style='display:block' width='200' height='200' /> <br/><br/>\n\n

                        Please contact <a href=mailto:'support@draftbrackets.com' target='_top'>support@draftbrackets.com</a> for all your customer needs. We want to make your experience as positive as possible. <br/><br/>\n\n
                        ", $user->name, $draft_name, $pick_date, $user_picks);

                    $this->app['phpdraft.EmailService']->SendMail($message);
                    
                    ?><pre><?php 
                    print_r($message);
                    // die();
                    ?></pre><?php
            }

        }

        return $pick;
    }

   



  public function LoadUpdatedPicks($draft_id, $pick_counter) {
    $picks = array();
    
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name FROM teams p ".
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_counter > ? ORDER BY p.player_counter");
    
    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $pick_counter);
    
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');
    
    if(!$stmt->execute()) {
      throw new \Exception("Unable to load updated picks.");
    }
    
    while($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }
    
    return $picks;
  }

  public function UpdatePick(Pick $pick) {
    $update_stmt = $this->app['db']->prepare("UPDATE teams SET manager_id = ?, team_name = ?,  league = ?, seed = ?,
      pick_time = ?, pick_duration = ?, player_counter = ? WHERE team_id = ?");

    $update_stmt->bindParam(1, $pick->manager_id);
    $update_stmt->bindParam(2, $pick->team_name);
    $update_stmt->bindParam(3, $pick->league);
    $update_stmt->bindParam(4, $pick->seed);
    $update_stmt->bindParam(5, $pick->pick_time);
    $update_stmt->bindParam(6, $pick->pick_duration);
    $update_stmt->bindParam(7, $pick->player_counter);
    $update_stmt->bindParam(8, $pick->team_id);

    if(!$update_stmt->execute()) {
      throw new \Exception("Unable to update pick #$pick->team_id");
    }

    return $pick;
  }

  //Used for when a pick has been updated on the depth chart (public-ish)
  public function UpdatePickDepthChart(Pick $pick) {
    $update_stmt = $this->app['db']->prepare("UPDATE teams SET depth_chart_position_id = ?, position_eligibility = ? WHERE team_id = ?");

    $update_stmt->bindParam(1, $pick->depth_chart_position_id);
    $update_stmt->bindParam(2, $pick->position_eligibility);
    $update_stmt->bindParam(3, $pick->team_id);

    if(!$update_stmt->execute()) {
      throw new \Exception("Unable to update pick #$pick->team_id for depth chart.");
    }

    return $pick;
  }

  public function GetCurrentPick(Draft $draft) {
	// echo $draft->draft_id;echo "<br/>";
	// echo $draft->draft_current_round;echo "<br/>";
	// echo $draft->draft_current_pick;echo "<br/>";
	// die();
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_id, m.manager_name " .
            "FROM teams p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_round = ? " .
            "AND p.player_pick = ? " .
            "LIMIT 1");

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $draft->draft_current_round);
    $stmt->bindParam(3, $draft->draft_current_pick);

    //Saw some extra numbered properties in the object when a FETCH_CLASS was performed instead. Possibly from the JOIN? Use FETCH_INTO instead:
    $current_pick = new Pick();
    $stmt->setFetchMode(\PDO::FETCH_INTO, $current_pick);
	
    if (!$stmt->execute()) {
      throw new \Exception("Unable to get current pick.");
    }
	
    if ($stmt->rowCount() == 0) {
      throw new \Exception("Unable to get current pick.");
    }

    $stmt->fetch();

    $current_pick->selected = strlen($current_pick->pick_time) > 0 && $current_pick->pick_duration > 0;
    $current_pick->on_the_clock = true;
	
    return $current_pick;
  }
  public function GetCurrentPickUserId(Draft $draft) {
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_id, m.manager_name , m.user_id " .
            "FROM teams p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_round = ? " .
            "AND p.player_pick = ? " .
            "LIMIT 1");

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $draft->draft_current_round);
    $stmt->bindParam(3, $draft->draft_current_pick);

    //Saw some extra numbered properties in the object when a FETCH_CLASS was performed instead. Possibly from the JOIN? Use FETCH_INTO instead:
    $current_pick = new Pick();
    $stmt->setFetchMode(\PDO::FETCH_INTO, $current_pick);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to get current pick.");
    }

    if ($stmt->rowCount() == 0) {
      throw new \Exception("Unable to get current pick.");
    }

    $stmt->fetch($current_pick);
	
    $current_pick->selected = strlen($current_pick->pick_time) > 0 && $current_pick->pick_duration > 0;
    $current_pick->on_the_clock = true;

    return $current_pick;
  }

  public function GetPreviousPick(Draft $draft) {
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_id, m.manager_name " .
            "FROM teams p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_pick = ? " .
            "AND p.pick_time IS NOT NULL " .
            "LIMIT 1");

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $previous_pick_number);

    $previous_pick_number = ($draft->draft_current_pick - 1);

    $previous_pick = new Pick();
    $stmt->setFetchMode(\PDO::FETCH_INTO, $previous_pick);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to get last pick: " . implode(":", $stmt->errorInfo()));
    }

    if ($stmt->rowCount() == 0) {
      return null;
    }

    $stmt->fetch();

    $previous_pick->selected = strlen($previous_pick->pick_time) > 0 && $previous_pick->pick_duration > 0;
    $previous_pick->on_the_clock = false;

    return $previous_pick;
  }

  public function GetNextPick(Draft $draft) {
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_id, m.manager_name " .
            "FROM teams p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.player_pick = ? LIMIT 1");

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $current_pick_number);

    $current_pick_number = $draft->draft_current_pick + 1;

    $next_pick = new Pick();
    $stmt->setFetchMode(\PDO::FETCH_INTO, $next_pick);

    if (!$stmt->execute()) {
      throw new Exception("Unable to get next pick.");
    }

    if ($stmt->rowCount() == 0) {
      return null;
    }

    $stmt->fetch();

    $next_pick->on_the_clock = true;

    return $next_pick;
  }

  public function LoadLastPicks($draft_id, $amount) {
    $picks = array();

    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name, m.manager_id FROM teams p ".
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            "AND p.pick_time IS NOT NULL " .
            "AND p.pick_duration IS NOT NULL " .
            "ORDER BY p.player_pick DESC " .
            "LIMIT ?");
    
    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $amount, \PDO::PARAM_INT);
    
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');
    
    if(!$stmt->execute()) {
      throw new Exception("Unable to load last $amount picks.");
    }
    
    while($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }
    
    return $picks;
  }

  public function LoadNextPicks($draft_id, $currentPick, $amount) {
    $picks = array();

    $stmt = $this->app['db']->prepare("SELECT p.*,d.draft_rounds,(SELECT COUNT(DISTINCT(manager_id)) FROM managers WHERE draft_id = ?) AS enrolled,u.username, m.manager_name, m.manager_id FROM teams p ".
			"LEFT JOIN draft d " .
            "ON p.draft_id = d.draft_id " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
			"LEFT OUTER JOIN users u " .
            "ON m.user_id = u.id " .
			
            "WHERE p.draft_id = ? " .
            "AND p.player_pick >= ? " .
            "ORDER BY p.player_pick ASC " .
            "LIMIT ?");
    
    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $draft_id);
    $stmt->bindParam(3, $currentPick);
    $stmt->bindParam(4, $amount, \PDO::PARAM_INT);
    
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');
    
    if(!$stmt->execute()) {
      throw new Exception("Unable to load next $amount picks.");
    }
    
    while($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $pick->on_the_clock = $pick->player_pick == $currentPick;

      $picks[] = $pick;
    }
    
    return $picks;
  }
  public function LoadMadePicks($draft_id, $currentPick,$leagueName) {
	
    $picks = array();
    $picksMade = array();
	
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name, m.manager_id FROM teams p ".
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " . 
            "WHERE p.draft_id = ? " .
            "ORDER BY p.player_pick ASC " );
    
    $stmt->bindParam(1, $draft_id);
    
    
    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');
    
    if(!$stmt->execute()) {
      throw new Exception("Unable to load next $amount picks.");
    }
    
    while($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $pick->on_the_clock = $pick->player_pick == $currentPick;

      $picks[] = $pick;
	 
	  if($pick->team_mongo_id !== "")
		$picksMade[] = $pick->team_mongo_id;
    }
	
   $teams = $this->getTeamsForDraft($leagueName);
   
   $teamsAvail = $this->removeMade($teams->data->teams,$picksMade);
   
	$response = array($teamsAvail);
	//$response = $picks;
	
    return $response;
  }
  private function removeMade($teams,$picksMade) {
	  $newTeams = array();
	  foreach($teams as $team){
		  
		  if(in_array($team->_id,$picksMade)) {
			  continue;
			  //debugging to see all teams
			  //$newTeams[] = $team;
		  } else {
			  $newTeams[] = $team;
		  }
			  
	  }
	  
	 return $newTeams;
  }
private function getTeamsForDraft($leagueName) {

		
		//$contest_id =  $request->get('contest_id');
		
		
		/*$query = http_build_query(array('query'=> 'query{teams(league:"'.$leagueName.
				'"){name _id seed conference logo} 
				contest(_id:"'.$contest_id.
				'") {
				  submitTime
				  expireTime
				}}'));
		*/
		$query = http_build_query(array('query'=> 'query{teams(league:"'.$leagueName.
				'"){name _id seed conference wins losses ranking logo} 
				}'));
		
		//change this to env variable
		$url = MONGO_API_BASE_URL.$query;
		

		// If using JSON...
		$response = $this->app['phpdraft.BracketRepository']->getUrlContents($url);
		
		//check if it is time to show other user results
		return json_decode($response);
}
  public function LoadManagerPicks($manager_id, $draft = null, $selected = true) {
    $manager_id = (int) $manager_id;

    if ($manager_id == 0) {
      throw new \Exception("Unable to get manager #" . $manager_id . "'s picks.");
    }

    $picks = array();

    $stmt = $selected
      ? $this->app['db']->prepare("SELECT * FROM teams WHERE manager_id = ? AND pick_time IS NOT NULL ORDER BY player_pick ASC")
      : $this->app['db']->prepare("SELECT * FROM teams WHERE manager_id = ? ORDER BY player_pick ASC");

    $stmt->bindParam(1, $manager_id);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to load manager #$manager_id's picks.");
    }

    while ($pick = $stmt->fetch()) {
      $pick->player_pick = (int)$pick->player_pick;
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $pick->on_the_clock = $draft != null && $pick->player_pick == $draft->draft_current_pick;

      $picks[] = $pick;
    }

    return $picks;
  } 
  public function LoadManagerPicksEmail($manager_id, $draft = null, $selected = true) {
    $manager_id = (int) $manager_id;
	$draft_id = $draft->draft_id; 
	echo "well" . $manager_id;
    if ($manager_id == 0) {
      throw new \Exception("Unable to get manager #" . $manager_id . "'s picks.");
    }

    $picks = array();

    $stmt = $selected
      ? $this->app['db']->prepare("SELECT * FROM teams WHERE manager_id = ? AND draft_id =  ? AND pick_time IS NOT NULL ORDER BY player_pick ASC")
      : $this->app['db']->prepare("SELECT * FROM teams WHERE manager_id = ? ORDER BY player_pick ASC");

    $stmt->bindParam(1, $manager_id);
    $stmt->bindParam(2, $draft_id);

   $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to load manager #$manager_id's picks.");
    }
    while ($pick = $stmt->fetch()) {
		
      $pick->player_pick = (int)$pick->player_pick;
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $pick->on_the_clock = $draft != null && $pick->player_pick == $draft->draft_current_pick;

      $picks[] = $pick;
	  
    }
    return $picks;
  }

  public function LoadRoundPicks(Draft $draft, $draft_round, $sort_ascending = true, $selected = true) {
    $picks = array();
    $sortOrder = $sort_ascending ? "ASC" : "DESC";

    $stmt = $selected
      ? $this->app['db']->prepare("SELECT p.*, m.manager_name FROM teams p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            " AND p.player_round = ? AND p.pick_time IS NOT NULL ORDER BY p.player_pick " . $sortOrder)
      : $this->app['db']->prepare("SELECT p.*, m.manager_name FROM teams p " .
            "LEFT OUTER JOIN managers m " .
            "ON m.manager_id = p.manager_id " .
            "WHERE p.draft_id = ? " .
            " AND p.player_round = ? ORDER BY p.player_pick " . $sortOrder);

    $stmt->bindParam(1, $draft->draft_id);
    $stmt->bindParam(2, $draft_round);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to load round #$round's picks.");
    }

    while ($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $pick->on_the_clock = $draft != null && $pick->player_pick == $draft->draft_current_pick;

      $picks[] = $pick;
    }

    return $picks;
  }

  /**
   * Searches for picks with strict criteria, using the MATCH() and score method. Sorts by score ASC first, then pick DESC last.
   * @param int $draft_id 
   */
  public function SearchStrict(PickSearchModel $searchModel) {
    $draft_id = (int) $searchModel->draft_id;
    $param_number = 4;
    $players = array();

    $sql = "SELECT p.*, m.manager_name, MATCH (p.first_name, p.last_name) AGAINST (?) as search_score " .
            "FROM teams p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE MATCH (p.first_name, p.last_name) AGAINST (?) AND p.draft_id = ? ";

    if ($searchModel->hasTeam())
      $sql .= "AND p.team = ? ";

    if ($searchModel->hasPosition())
      $sql .= "AND p.position = ? ";

    $sql .= "AND p.pick_time IS NOT NULL ORDER BY search_score ASC, p.player_pick $searchModel->sort";

    $stmt = $this->app['db']->prepare($sql);
    $stmt->bindParam(1, $searchModel->keywords);
    $stmt->bindParam(2, $searchModel->keywords);
    $stmt->bindParam(3, $draft_id);
    if ($searchModel->hasTeam()) {
      $stmt->bindParam(4, $searchModel->team);
      $param_number++;
    }

    if ($searchModel->hasPosition()) {
      $stmt->bindParam($param_number, $searchModel->position);
      $param_number++;
    }

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to search for picks.");
    }

    while ($player = $stmt->fetch()) {
      $player->selected = strlen($player->pick_time) > 0 && $player->pick_duration > 0;
      $players[] = $player;
    }

    $searchModel->player_results = $players;

    return $searchModel;
  }

  /**
   * Search picks by a loose criteria that uses a LIKE %% query. Used if strict query returns 0 results. Sorts by pick DESC.
   * @param int $draft_id 
   */
  public function SearchLoose(PickSearchModel $searchModel) {
    $draft_id = (int) $searchModel->draft_id;
    $players = array();
    $param_number = 2;
    $loose_search_score = -1;

    $sql = "SELECT p.*, m.manager_name FROM teams p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE p.draft_id = ? ";

    if ($searchModel->hasName())
      $sql .= "AND (p.first_name LIKE ? OR p.last_name LIKE ?)";

    if ($searchModel->hasTeam())
      $sql .= "AND p.team = ? ";

    if ($searchModel->hasPosition())
      $sql .= "AND p.position = ? ";

    $sql .= "AND p.pick_time IS NOT NULL ORDER BY p.player_pick $searchModel->sort";

    $stmt = $this->app['db']->prepare($sql);
    $stmt->bindParam(1, $draft_id);

    if ($searchModel->hasName()) {
      $stmt->bindParam($param_number, $keywords);
      $param_number++;
      $stmt->bindParam($param_number, $keywords);
      $param_number++;

      $keywords = "%" . $searchModel->keywords . "%";
    }

    if ($searchModel->hasTeam()) {
      $stmt->bindParam($param_number, $searchModel->team);
      $param_number++;
    }

    if ($searchModel->hasPosition()) {
      $stmt->bindParam($param_number, $searchModel->position);
      $param_number++;
    }

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to search for picks.");
    }

    while ($player = $stmt->fetch()) {
      $player->search_score = $loose_search_score;
      $player->selected = strlen($player->pick_time) > 0 && $player->pick_duration > 0;
      $players[] = $player;

      $loose_search_score--;
    }

    $searchModel->player_results = $players;

    return $searchModel;
  }

  /**
   * Search picks by assuming a first + last combo was entered. Used if strict and loose queries return 0 and theres a space in the name. Sorts by pick DESC.
   * @param int $draft_id 
   */
  public function SearchSplit(PickSearchModel $searchModel, $first_name, $last_name) {
    $draft_id = (int) $searchModel->draft_id;
    $players = array();
    $param_number = 4;
    $loose_search_score = -1;

    $sql = "SELECT p.*, m.manager_name FROM teams p LEFT OUTER JOIN managers m ON m.manager_id = p.manager_id WHERE p.draft_id = ?
      AND (p.first_name LIKE ? OR p.last_name LIKE ?)
      AND p.pick_time IS NOT NULL ORDER BY p.player_pick $searchModel->sort";

    $stmt = $this->app['db']->prepare($sql);
    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $first_name);
    $stmt->bindParam(3, $last_name);

    if ($searchModel->hasTeam()) {
      $stmt->bindParam($param_number, $searchModel->team);
      $param_number++;
    }

    if ($searchModel->hasPosition()) {
      $stmt->bindParam($param_number, $searchModel->position);
      $param_number++;
    }

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if (!$stmt->execute()) {
      throw new \Exception("Unable to search for picks.");
    }

    while ($player = $stmt->fetch()) {
      $player->search_score = $loose_search_score;
      $player->selected = strlen($player->pick_time) > 0 && $player->pick_duration > 0;
      $players[] = $player;

      $loose_search_score--;
    }

    $searchModel->player_results = $players;

    return $searchModel;
  }

  //Analogous to 1.3's "getAlreadyDrafted" method from the player service - used on the add pre-check
  public function SearchAlreadyDrafted($draft_id, $team,$request) {
    $picks = array();
	
    $stmt = $this->app['db']->prepare("SELECT p.*, m.manager_name " .
    "FROM teams p " .
    "LEFT OUTER JOIN managers m " .
    "ON m.manager_id = p.manager_id " .
    "WHERE p.draft_id = ? " .
    "AND p.pick_time IS NOT NULL " .
    "AND p.team_mongo_id = ? " .
    "ORDER BY p.player_pick"); 

    $stmt->bindParam(1, $draft_id);
    $stmt->bindParam(2, $team["_id"]);

    $stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Pick');

    if(!$stmt->execute()) {
        throw new \Exception("Unable to check to see if $team_name was already drafted.");
    }
	
    while($pick = $stmt->fetch()) {
      $pick->selected = strlen($pick->pick_time) > 0 && $pick->pick_duration > 0;
      $picks[] = $pick;
    }
	
	if(count($picks) > 0) {
		return $picks;
	} else {
		//$request->request->set("team_mongo_id") =  $team["_id"];
		 $draft = $this->app['phpdraft.DraftRepository']->Load($draft_id);
		$currentPick = $this->GetCurrentPick($draft);
		//var_dump($nextPick->team_id);
		$team["team_id"] = $currentPick->team_id;
		$this->app['commish.pick.controller']->Add($this->app, $request,$team);
	}
  }

  public function DeleteAllPicks($draft_id) {
    $delete_stmt = $this->app['db']->prepare("DELETE FROM teams WHERE draft_id = ?");
    $delete_stmt->bindParam(1, $draft_id);

    if(!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete picks for $draft_id.");
    }

    return;
  }

  /*This logic will create all pick objects according to the draft information.
    The two lists are used in alternation for serpentine drafts. Only first list is
    used for standard drafts.*/
  public function SetupPicks(Draft $draft, $ascending_managers, $descending_managers = null) {
    $pick = 1;
    $even = true;
	
    for ($current_round = 1; $current_round <= $draft->draft_rounds; $current_round++) {
      if ($draft->draft_style == "serpentine") {
        if ($even) {
          $managers = $ascending_managers;
          $even = false;
        } else {
          if($descending_managers == null) {
            throw new \Exception("Descending managers list is empty - unable to setup draft.");
          }

          $managers = $descending_managers;
          $even = true;
        }
      }
      else
        $managers = $ascending_managers;

		
      foreach ($managers as $manager) {
		
        $new_pick = new Pick();
        $new_pick->manager_id = $manager->manager_id;
        $new_pick->contest_id = $manager->contest_id;
        $new_pick->draft_id = $draft->draft_id;
        $new_pick->player_round = $current_round;
        $new_pick->player_pick = $pick;

        try {
			
          $this->_saveSetupPick($new_pick);
        } catch (Exception $e) {
          throw new Exception($e->getMessage());
        }
			
        $pick++;
      }
	  
    }
	
    return;
  }

  //Used when SetupPicks is called, which is when a draft is flipped to "in_progress"
  private function _saveSetupPick(Pick $pick) {
	//get user id
	
	$user_id = $this->managerIdToUser($pick->manager_id);
	
	
    $insert_stmt = $this->app['db']->prepare("INSERT INTO teams
      (manager_id, draft_id, player_round, player_pick, user_id,contest_id)
      VALUES
      (?, ?, ?, ?, ?,?)");

    $insert_stmt->bindParam(1, $pick->manager_id);
    $insert_stmt->bindParam(2, $pick->draft_id);
    $insert_stmt->bindParam(3, $pick->player_round);
    $insert_stmt->bindParam(4, $pick->player_pick);
    $insert_stmt->bindParam(5, $user_id);
    $insert_stmt->bindParam(6, $pick->contest_id);

    if(!$insert_stmt->execute()) {
      throw new \Exception("Unable to insert pick #$pick->player_pick for draft $pick->draft_id");
    }
	
    return;
  }
  private function managerIdToUser($manager_id) {
	  $select_stmt = $this->app['db']->prepare("SELECT user_id FROM managers WHERE manager_id = 
      ?");
	  $select_stmt->bindParam(1, $manager_id);
	  
	  if(!$select_stmt->execute()) {
		throw new \Exception("Unable to search you query sucks");
	  }	
	  $row = $select_stmt->fetch();

	  if(!$row) {
		throw new \Exception("Unable to search you query sucks");
	  }	
	  return $row[user_id];
	  
	  
  }
}