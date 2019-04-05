<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Bracket;
use PhpDraft\Domain\Entities\Pick;
use DateTime;

use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\MailMessage;

use PhpDraft\Domain\Entities\Manager;

class BracketRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
	
	
  }
	 
  //TODO: Add server-side paging
  public function GetPublicBrackets(Request $request/*$pageSize = 25, $page = 1*/, $password = '') {
    /*$page = (int)$page;
    $pageSize = (int)$pageSize;
    $startIndex = ($page-1) * $pageSize;

    if($startIndex < 0) {
      throw new \Exception("Unable to get brackets: incorrect paging parameters.");
    }*/
	
    //$bracket_stmt = $this->app['db']->prepare("SELECT * FROM bracket ORDER BY bracket_create_time LIMIT ?, ?");
	$whereSportBracket = "";
	$whereSportDraft = "";
	 $sportLeague = $this->getSportByIndex($request);
	 //for mobile sport league will never be all
	// var_dump($sportLeague);
	if($sportLeague != "ALL") {
		if(trim($request->get('type')) !== "") {
			if($request->get('type') == "draft") {
				$whereSportDraft = " AND d.draft_sport= ? ";
			}elseif($request->get('type') == "bracket"){
				$whereSportBracket = " AND b.bracket_sport= ? ";
			}
		} else {
			$whereSportBracket = " AND b.bracket_sport= ? ";
			$whereSportDraft = " AND d.draft_sport= ? ";
		}
	} 
	$type = $request->get('type');
	$whereTypeDraft = "";
	$whereTypeBracket= "";
	$whereTypeBracketLimit = "";
	$whereTypeDraftLimit = "";
	if($request->get('type') == "draft") {
	//	$whereTypeDraft = " AND d.comp_type = ? ";
		
		$whereTypeBracketLimit = " LIMIT 0 ";
	}elseif($request->get('type') == "bracket"){
	//	$whereTypeBracket = " AND b.contest_type = ? ";
		$whereTypeDraftLimit = " LIMIT 0 ";
	}
	
	
	/*
	var_dump($whereSportBracket);
	var_dump($whereTypeBracket);
	var_dump($whereTypeBracketLimit);
	var_dump($whereSportDraft);
	var_dump($whereTypeDraft);
	var_dump($whereTypeDraftLimit);
	*/
	//$bracket_stmt = $this->app['db']->prepare("SELECT d.* FROM bracket as d WHERE bracket_closed =0 ".$whereSport." ORDER BY bracket_create_time DESC ");
	//u.Name AS commish_name
    $bracket_stmt = $this->app['db']->prepare("SELECT  *
FROM    ( SELECT b.contest_id,b.contest_type,b.bracket_name,b.bracket_sport,b.bracket_id,b.cash_prize,b.max_players,b.bracket_fee,b.bracket_rounds,b.bracket_status,b.bracket_start_time,b.bracket_submit_time FROM bracket as b WHERE bracket_closed =0 
	".$whereSportBracket."
	
	".$whereTypeBracketLimit."
	
       ORDER BY bracket_create_time DESC  ) as brackets union all SELECT  *
FROM    ( SELECT d.contest_id,d.comp_type,d.draft_name,d.draft_sport,d.draft_id,d.cash_prize,d.max_players,d.draft_fee,d.draft_rounds,d.draft_status,d.draft_start_time,d.draft_end_time FROM draft d  LEFT OUTER JOIN users u 
      ON d.commish_id = u.id WHERE draft_closed =0
	  ".$whereSportDraft." 
	  
	  
	  ".$whereTypeDraftLimit."
		
      ORDER BY draft_create_time DESC ) as drafts");
	  

	if($sportLeague != "ALL") {
		
		if(trim($request->get('type')) !== "") {
			if($request->get('type') == "draft") {
				//$bracket_stmt->bindParam(2, $request->get('type'));			
				$bracket_stmt->bindParam(1, $sportLeague);
				//$bracket_stmt->bindParam(1, "bracket");
			}elseif($request->get('type') == "bracket"){
				//$bracket_stmt->bindParam(2, $request->get('type'));
				$bracket_stmt->bindParam(1, $sportLeague);
				//$bracket_stmt->bindParam(3, "draft");
			}
		} else {
			$bracket_stmt->bindParam(1, $sportLeague);
			$bracket_stmt->bindParam(2, $sportLeague);
		}
	} 
	
    $bracket_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Bracket');

    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    /*$bracket_stmt->bindParam(1, $startIndex, \PDO::PARAM_INT);
    $bracket_stmt->bindParam(2, $pageSize, \PDO::PARAM_INT);*/

    if(!$bracket_stmt->execute()) {
      throw new \Exception("Unable to load brackets.");
    }
	
    $brackets = array();
		//show join or joined
	$current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);
	$user_id =  $current_user->id ? $current_user->id : 0;
	
	
	//echo $whereSport;
	//echo $sportLeague;
	$joined_stmt = $this->app['db']->prepare("(SELECT b.contest_id,b.bracket_id FROM bracket as b LEFT OUTER JOIN managers m
		ON b.contest_id = m.contest_id
		WHERE m.user_id = ? 
      ORDER BY bracket_create_time DESC) union all (SELECT d.contest_id,d.draft_id FROM draft as d LEFT OUTER JOIN managers m
		ON d.contest_id = m.contest_id
		WHERE m.user_id = ? 
      ORDER BY draft_create_time DESC)");
	  $joined_stmt->bindParam(1, $user_id);
	  $joined_stmt->bindParam(2, $user_id);

		

    //$joined_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Bracket');

    



    if(!$joined_stmt->execute()) {
      throw new \Exception("Unable to load brackets.");
    }
	$enteredContests = $joined_stmt->fetchAll();
	$contestIds = array();
	foreach($enteredContests as $contest) {
		$contestIds[$contest["contest_id"]."-".$contest["bracket_id"]] = $contest["bracket_id"];

	}
	
	//var_dump($bracket_stmt->fetchAll());
    while($bracket = $bracket_stmt->fetch()) {
		$enrolled_stmt = "";
		if($bracket->contest_type == "bracket"){
			$enrolled_stmt = $this->app['db']->prepare("SELECT COUNT(DISTINCT(managers.user_id )) AS enrolled FROM managers INNER JOIN bracket AS b ON b.contest_id = managers.contest_id 
				WHERE b.contest_id = ? AND managers.draft_id = ?");
				$bracket->is_bracket = true;
		} else {
			$enrolled_stmt = $this->app['db']->prepare("SELECT COUNT(DISTINCT(managers.user_id )) AS enrolled FROM managers INNER JOIN draft AS b ON b.contest_id = managers.contest_id 
				WHERE b.contest_id = ? AND managers.draft_id = ?");
				$bracket->is_draft = true;
		}
		 $enrolled_stmt->bindParam(1, $bracket->contest_id);
		 $enrolled_stmt->bindParam(2, $bracket->bracket_id);
		 
	  if(!$enrolled_stmt->execute()) {
		  throw new \Exception("Unable to get currently enrolled.");
		}
		
		$enrolled = $enrolled_stmt->fetch();
		
		$bracket->players = $enrolled["enrolled"];
      $currentUserOwnsIt = !empty($current_user) && $bracket->commish_id == $current_user->id;
      $currentUserIsAdmin = !empty($current_user) && $this->app['phpdraft.LoginUserService']->CurrentUserIsAdmin($current_user);

      $bracket->bracket_visible = empty($bracket->bracket_password);
      $bracket->commish_editable = $currentUserOwnsIt || $currentUserIsAdmin;
		
		if($contestIds[$bracket->contest_id."-".$bracket->bracket_id] == $bracket->bracket_id) {
			$bracket->join_status = true;
		} else {
			$bracket->join_status = false;
		}
		
	
      $bracket->setting_up = $this->app['phpdraft.BracketService']->BracketSettingUp($bracket);
      $bracket->in_progress = $this->app['phpdraft.BracketService']->BracketInProgress($bracket);
      $bracket->complete = $this->app['phpdraft.BracketService']->BracketComplete($bracket);
      $bracket->is_locked = false;

      $bracket->bracket_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_create_time);
      $bracket->bracket_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_start_time);
      $bracket->bracket_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_end_time);

	
      if(!$currentUserOwnsIt && !$currentUserIsAdmin && !$bracket->bracket_visible && $password != $bracket->bracket_password) {
        $bracket->is_locked = true;
        $bracket = $this->ProtectPrivateBracket($bracket);
      }

      unset($bracket->bracket_password);
	$bracket->user =$current_user->id;
		if(!$bracket->join_status) {
		  $brackets[] = $bracket;
		}
    }
	
		return $brackets;
  }



    // function to check times and be able to send reminders for a week before or a day before
    public function ZanSendBracketReminders(Application $app,Request $request) {
		
        $brackets = $app['phpdraft.BracketRepository']->GetPublicBrackets($request, $password,true);
        // $bracket_id = $request->get('id');
        // $bracket_id = (int)$request->get('id');
        // $bracket_id = $brackets->bracket_id;
        date_default_timezone_set('America/Los_Angeles'); 

        ?><pre><?php 
            // echo "Bracket: <br><br>";
            // print_r($brackets);
        ?></pre><?php

        // Check if brackets are empty.   
        if ( ! empty( $brackets ) ) {
            // Loop through drafts.
            foreach ( $brackets as $bracket ) {
            	$bracket_id = $bracket->bracket_id;

                // var_dump($draft);die();
                $users = $this->app['phpdraft.ManagerRepository']->GetPublicManagersBracket($bracket_id);

                // $user = array_shift($user);
                $user_email = array();
                
                $end_date = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_start_time);
                $day_time_timezone = $end_date;

                $end_date_2 = new \DateTime($bracket->bracket_start_time);
                // $day_time_timezone_2 = $end_date_2->format('Y-m-d h:i A');
                $day_time_timezone_2 	= $end_date_2->format('h:i A') . " on " . $end_date_2->format('F j, Y');

                $bracket_name = $bracket->bracket_name;
                $bracket_id = $bracket->bracket_id;
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
                    $user_email 			= $user->email;
                    $message 				= new MailMessage();
                    // $message->CC      		= "babiizhee@gmail.com";
                    $message->to_addresses 	= array (
                        $user->email 		=> $user->name
                        // $manager->email     => $message->CC
                    );
                    // $message->to_addresses = $user_email;
                    $message->subject 		= "Reminder: Upcoming Bracket Time";
                    $message->is_html 		= true;
                    $message->body 	  		= sprintf("Hi %s, <br/><br/>\n\n 

                        This email reminder is to confirm that the <strong>%s</strong> Bracket Contest is schedule at <strong>%s</strong> PST. The deadline to submit your bracket is 5 to 10 minutes before the scheduled Bracket Time. Be prepared for the Bracket Contest and do your homework (because your opponents did)! <br/><br/>\n\n

                        <img src='https://draftbrackets.com/images/draftbracketlogopng.png' alt='Draft Brackets Logo' title='Draft Brackets Logo' style='display:block' width='200' height='200' /> <br/><br/>\n\n

                        Please contact <a href=mailto:'support@draftbrackets.com' target='_top'>support@draftbrackets.com</a> for all your customer needs. We want to make your experience as positive as possible. <br/><br/>\n\n
                        ", $user->name, $bracket_name, $day_time_timezone_2);

                    if ( $days_remaining > 0 ) {
                    	?><pre><?php 
                    		// echo "<h1>Bracket ID: " . $bracket_id . "</h1>";
                            // echo "<h1>" . $bracket_name . " - There are $days_remaining days and $hours_remaining hours left</h1>";
                            // echo "<h1>End Date: " . $day_time_timezone_2 . " </h1>";
                            // echo "<h1>Bracket: </h1>";
            				// print_r($bracket);
                            // print_r($message);
                            // echo "<br><hr><br>";
                        ?></pre><?php
                    }

                    if ( $days_remaining == 0 ) {
	                    switch( $hours_remaining ) {
	                    	case 48:
	                        case 3:
	                            $this->app['phpdraft.EmailService']->SendMail($message);
	                        break;
	                    }
	                }
                } //End for each user
            } // End contacts foreach
        } // End !empty $brackets
    } // End ZanSendBracketReminders()


	private function getSportByIndex($request) {
		$sport = $request->get("sport");
		
		if($sport == "1") {
			$sportLeague = "NBA";
		}
		elseif($sport == "2") {
			$sportLeague = "NFL";
		}
		elseif($sport == "3") {
			$sportLeague = "MLB";
		}
		elseif($sport == "4") {
			$sportLeague = "NHL";
		}
		elseif($sport == "0") {
			$sportLeague = "ALL";
		}
		
		return $sportLeague;
	}
	public function GetPublicDraftsByUser(Request $request) {
		
	 $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);
	 //var_dump( $current_user);
	 $user_id =  $current_user->id ? $current_user->id : 0;
	$bracket_stmt = "";
	$status = $request->get("status");
	   
   $sportLeague = $this->getSportByIndex($request);
   //var_dump($sportLeague);
	$whereSportBracket = "";
	$whereSportDraft = "";
	if($sportLeague != "ALL") {
		
		$whereSportBracket = " AND bracket_sport= ? ";
		$whereSportDraft = " AND draft_sport= ? ";
		
	} 
	$whereTypeBracketLimit = "";
	$whereTypeDraftLimit = "";
	if($request->get('type') == "draft") {
	//	$whereTypeDraft = " AND d.comp_type = ? ";
		
		$whereTypeBracketLimit = " LIMIT 0 ";
	}elseif($request->get('type') == "bracket"){
	//	$whereTypeBracket = " AND b.contest_type = ? ";
		$whereTypeDraftLimit = " LIMIT 0 ";
	}
	if($status == "upcoming") {
		$bracket_stmt = $this->app['db']->prepare("SELECT  *
FROM    ((SELECT d.contest_id,d.contest_type,d.bracket_name,d.bracket_sport,d.bracket_id,d.cash_prize,d.max_players,d.bracket_fee,d.bracket_rounds,d.bracket_status,d.bracket_start_time,d.bracket_submit_time FROM bracket d LEFT OUTER JOIN managers m
		ON d.bracket_id = m.draft_id 
		WHERE m.user_id = ? ".$whereSportBracket." 
		AND d.bracket_submit_time > NOW() GROUP BY d.bracket_id
		ORDER BY bracket_create_time DESC ".$whereTypeBracketLimit.")) as brackets union all SELECT  *
FROM    ((SELECT d.contest_id,d.comp_type,d.draft_name,d.draft_sport,d.draft_id,d.cash_prize,d.max_players,d.draft_fee,d.draft_rounds,d.draft_status,d.draft_start_time,d.draft_end_time FROM draft d LEFT OUTER JOIN managers m
		ON d.draft_id = m.draft_id 
		WHERE m.user_id = ? ".$whereSportDraft." 
		AND d.draft_start_time > NOW() GROUP BY d.draft_id
		ORDER BY draft_create_time DESC ".$whereTypeDraftLimit.")) as drafts");
		
		   $bracket_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Bracket');
	} else if($status == "past") {
		$bracket_stmt = $this->app['db']->prepare("SELECT  *
FROM    ((SELECT d.contest_id,d.contest_type,d.bracket_name,d.bracket_sport,d.bracket_id,d.cash_prize,d.max_players,d.bracket_fee,d.bracket_rounds,d.bracket_status,d.bracket_start_time,d.bracket_submit_time FROM bracket d LEFT OUTER JOIN managers m
		ON d.bracket_id = m.draft_id 
		WHERE m.user_id = ? ".$whereSportBracket." 
		AND d.bracket_start_time < NOW() GROUP BY d.bracket_id
		ORDER BY bracket_create_time DESC ".$whereTypeBracketLimit."))as brackets union all SELECT  *
FROM    ((SELECT d.contest_id,d.comp_type,d.draft_name,d.draft_sport,d.draft_id,d.cash_prize,d.max_players,d.draft_fee,d.draft_rounds,d.draft_status,d.draft_start_time,d.draft_end_time FROM draft d LEFT OUTER JOIN managers m
		ON d.draft_id = m.draft_id 
		WHERE m.user_id = ? ".$whereSportDraft." 
		AND d.draft_expiry_time < NOW() GROUP BY d.draft_id
		ORDER BY draft_create_time DESC ".$whereTypeDraftLimit.")) as drafts");
		   $bracket_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Bracket');	
	}
   else if($status == "active") {
		$bracket_stmt = $this->app['db']->prepare("SELECT  *
FROM    ((SELECT d.contest_id,d.contest_type,d.bracket_name,d.bracket_sport,d.bracket_id,d.cash_prize,d.max_players,d.bracket_fee,d.bracket_rounds,d.bracket_status,d.bracket_start_time,d.bracket_submit_time FROM bracket d LEFT OUTER JOIN managers m
		ON  d.bracket_id = m.draft_id 
		WHERE m.user_id = ? ".$whereSportBracket." 
		AND d.bracket_submit_time < NOW() AND d.bracket_start_time > NOW()
		GROUP BY d.bracket_id ORDER BY bracket_create_time DESC  ".$whereTypeBracketLimit.")) as brackets union all SELECT  *
FROM    ((SELECT d.contest_id,d.comp_type,d.draft_name,d.draft_sport,d.draft_id,d.cash_prize,d.max_players,d.draft_fee,d.draft_rounds,d.draft_status,d.draft_start_time,d.draft_end_time FROM draft d LEFT OUTER JOIN managers m
		ON d.draft_id = m.draft_id 
		WHERE m.user_id = ? ".$whereSportDraft." 
		AND d.draft_start_time < NOW() AND NOW() < d.draft_expiry_time 
		GROUP BY d.draft_id ORDER BY draft_create_time DESC  ".$whereTypeDraftLimit.")) as drafts ");
		   $bracket_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Bracket');			
	}
	
	/*
   else  {
		$bracket_stmt = $this->app['db']->prepare("SELECT d.contest_id,d.contest_type,d.bracket_name,d.bracket_sport,d.bracket_id,d.cash_prize,d.max_players,d.bracket_fee,d.bracket_rounds,d.bracket_status,d.bracket_start_time,d.bracket_submit_time FROM bracket d LEFT OUTER JOIN managers m
		ON  d.bracket_id = m.draft_id 
		WHERE m.user_id = ? ".$whereSport."
		ORDER BY bracket_create_time DESC");
		   $bracket_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Bracket');
	}
	*/
   
   
	if($sportLeague != "ALL") {
		$bracket_stmt->bindParam(1, $user_id);
		$bracket_stmt->bindParam(2, $sportLeague);
		$bracket_stmt->bindParam(3, $user_id);
		$bracket_stmt->bindParam(4, $sportLeague);
		
	}  else {
		
		$bracket_stmt->bindParam(1, $user_id);
		$bracket_stmt->bindParam(2, $user_id);
	}

    /*$bracket_stmt->bindParam(1, $startIndex, \PDO::PARAM_INT);
    $bracket_stmt->bindParam(2, $pageSize, \PDO::PARAM_INT);*/

    if(!$bracket_stmt->execute()) {
      throw new \Exception("Unable to load brackets.");
    }

    $brackets = array();
	//var_dump($bracket_stmt->fetchAll()); 
    while($bracket = $bracket_stmt->fetch()) {
		$enrolled_stmt = $this->app['db']->prepare("SELECT COUNT(*) AS enrolled FROM managers
			WHERE contest_id = ? AND draft_id = ?");
		 $enrolled_stmt->bindParam(1, $bracket->contest_id);
		 $enrolled_stmt->bindParam(2, $bracket->bracket_id);
	  if(!$enrolled_stmt->execute()) {
		  throw new \Exception("Unable to get currently enrolled.");
		}
		$enrolled = $enrolled_stmt->fetch();
		 
		$bracket->players = $enrolled["enrolled"];
		
      $currentUserOwnsIt = !empty($current_user) && $bracket->commish_id == $current_user->id;
      $currentUserIsAdmin = !empty($current_user) && $this->app['phpdraft.LoginUserService']->CurrentUserIsAdmin($current_user);

      $bracket->bracket_visible = empty($bracket->bracket_password);
      $bracket->commish_editable = $currentUserOwnsIt || $currentUserIsAdmin;
	
		if($bracket->contest_type == "bracket"){
			
				$bracket->is_bracket = true;
		} else {
			
				$bracket->is_draft = true;
		}

	  
      $bracket->setting_up = $this->app['phpdraft.BracketService']->BracketSettingUp($bracket);
      $bracket->in_progress = $this->app['phpdraft.BracketService']->BracketInProgress($bracket);
      $bracket->complete = $this->app['phpdraft.BracketService']->BracketComplete($bracket);
      $bracket->is_locked = false;

      $bracket->bracket_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_create_time);
      $bracket->bracket_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_start_time);
      $bracket->bracket_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_end_time);
		
      if(!$currentUserOwnsIt && !$currentUserIsAdmin && !$bracket->bracket_visible && $password != $bracket->bracket_password) {
        $bracket->is_locked = true;
        $bracket = $this->ProtectPrivateBracket($bracket);
      }

      unset($bracket->bracket_password);
	$bracket->user =$current_user->id;
      $brackets[] = $bracket;
    }
	//var_dump($brackets);
    return $brackets;
	}
  public function GetPublicBracketsByCommish(Request $request, $commish_id, $password = '') {
    $commish_id = (int)$commish_id;

    $bracket_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM bracket d
    LEFT OUTER JOIN users u
    ON d.commish_id = u.id
    WHERE commish_id = ?
    ORDER BY bracket_create_time DESC");

    $bracket_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Bracket');
    $bracket_stmt->bindParam(1, $commish_id);

    if(!$bracket_stmt->execute()) {
      throw new \Exception("Unable to load brackets.");
    }

    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    $brackets = array();

    while($bracket = $bracket_stmt->fetch()) {
      $currentUserOwnsIt = !empty($current_user) && $bracket->commish_id == $current_user->id;
      $currentUserIsAdmin = !empty($current_user) && $this->app['phpdraft.LoginUserService']->CurrentUserIsAdmin($current_user);

      $bracket->bracket_visible = empty($bracket->bracket_password);
      $bracket->commish_editable = $currentUserOwnsIt || $currentUserIsAdmin ;
      $bracket->setting_up = $this->app['phpdraft.BracketService']->BracketSettingUp($bracket);
      $bracket->in_progress = $this->app['phpdraft.BracketService']->BracketInProgress($bracket);
      $bracket->complete = $this->app['phpdraft.BracketService']->BracketComplete($bracket);
      $bracket->is_locked = false;

      $bracket->bracket_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_create_time);
      $bracket->bracket_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_start_time);
      $bracket->bracket_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_end_time);

      if(!$currentUserOwnsIt && !$currentUserIsAdmin && !$bracket->bracket_visible && $password != $bracket->bracket_password) {
        $bracket->is_locked = true;
        $bracket = $this->ProtectPrivateBracket($bracket);
      }

      unset($bracket->bracket_password);

      $brackets[] = $bracket;
    }

    return $brackets;
  }

  //Note: this method is to be used by admin section only
  public function GetAllBracketsByCommish($commish_id) {
    $commish_id = (int)$commish_id;

    $bracket_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM bracket d
    LEFT OUTER JOIN users u
    ON d.commish_id = u.id
    WHERE commish_id = ?
    ORDER BY bracket_create_time DESC");

    $bracket_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Bracket');
    $bracket_stmt->bindParam(1, $commish_id);

    if(!$bracket_stmt->execute()) {
      throw new \Exception("Unable to load brackets.");
    }

    $brackets = array();

    while($bracket = $bracket_stmt->fetch()) {
      $bracket->bracket_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_create_time);
      $bracket->bracket_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_start_time);
      $bracket->bracket_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_end_time);

      $brackets[] = $bracket;
    }

    return $brackets;
  }

  //Note: this method is to be used by admin section only
  public function GetAllCompletedBrackets() {
    $bracket_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM bracket d
      LEFT OUTER JOIN users u
      ON d.commish_id = u.id
      WHERE d.bracket_status = 'complete'
      ORDER BY bracket_create_time DESC");

    $bracket_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Bracket');

    if(!$bracket_stmt->execute()) {
      throw new \Exception("Unable to load brackets.");
    }

    $brackets = array();

    while($bracket = $bracket_stmt->fetch()) {
      $bracket->bracket_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_create_time);
      $bracket->bracket_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_start_time);
      $bracket->bracket_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($bracket->bracket_end_time);

      $brackets[] = $bracket;
    }

    return $brackets;
  }
  public function getUrlContents($url,$mutation = null,$noJSON = false, $post = false,$args = array()) {
	  
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$data = "";
		if($mutation) {
			$data = array("query" => $mutation);
			$dataString = '';
			$dataString = json_encode($data);
			curl_setOpt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString );
			

		} 
		if($post) {
			$data = http_build_query($args);
			curl_setOpt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
		}
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json')); // Assuming you're requesting JSON
		if($noJSON) {
			
		} else {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json')); // 
		}

		$response = curl_exec($ch);
		
		return $response;
		
  }
  private function getUserPicks() {
	return true;
  }
  
	private function GetTeamsAndBracketAndSeries(Request $request) {
		
		$current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);
		$league =  $request->get('league_name');
		$bracket_id =  $request->get('id');
		$contest_id =  $request->get('contest_id');
		$other_user_id = $request->get('user_id') ? $request->get('user_id') : 0;
		$user_id =  $current_user->id ? $current_user->id : 0 ;
		$other_user_id =  $other_user_id->id ? $other_user_id->id : 0 ;
		//everything goes to node after this
		/*
		$query = "";
		if($other_user_id) {
				$query = http_build_query(array('query'=> 'query{teams(league:"'.$league.
				'"){name _id seed conference logo} bracket(contest_id:"'.$contest_id.
				'",bracket_id:'.$bracket_id.
				',user_id:'.$other_user_id.
				'){
				  _id
				  contest_id
				  user_id
				  league
				  bracket_id
				  matches
				}league(name:"'.$league.
				'") {
					wildcard
					name
					result_bracket
					total_rounds
				}contest(_id:"'.$contest_id.
				'") {
				  submitTime
				  expireTime
				autofill_results

				}}'));
		} else {
			$query = http_build_query(array('query'=> 'query{teams(league:"'.$league.
				'"){name _id seed conference logo} bracket(contest_id:"'.$contest_id.
				'",bracket_id:'.$bracket_id.
				',user_id:'.$user_id.
				'){
				  _id
				  contest_id
				  user_id
				  league
				  bracket_id
				  matches
				}league(name:"'.$league.
				'") {
					wildcard
					name
					result_bracket
					total_rounds
				}contest(_id:"'.$contest_id.
				'") {
				  submitTime
				  expireTime
				  					autofill_results

				}}'));
		}
		*/
		//change this to env variable
		$url = MONGO_API_BASE_URL_NO_QUERY."build_matches/league/$league/bracket/$bracket_id/contest/$contest_id/user/$user_id/other_user/$other_user_id";
		

		// If using JSON...
		//var_dump('$url');
		
		$response = $this->getUrlContents($url,null,true);
		//var_dump($url);
		//this is a protection to make sure can't see other user bracket if submit time has not passed yet and it works might have to change from utc to pacific thought there is a gap
		if($other_user_id && $this->checkIfTimePassed($response)) {
			echo "this happened";
			return false;
		} 
		
		//check if it is time to show other user results
		return $response;
		
	}
	private function alreadyInBracket($bracket_id,$app,$request) {
		$managers = $app['phpdraft.ManagerRepository']->GetPublicManagersBracket($bracket_id);
		$currentUser = $app['phpdraft.LoginUserService']->GetCurrentUser($request);
		$user_id = $currentUser->id;
		$in_bracket = false;
		foreach($managers as $manager) {
			if($manager->user_id == $user_id) {
				$in_bracket = true;
			}
		}
		
		return $in_bracket;
	}
	private function hasBracketSqlSubmitPassed($bracket_id) {
		$bracket= $this->Load($bracket_id);
		$dateStr = $bracket->bracket_submit_time;
		
		$timezone = 'America/Los_Angeles';
		$date = new DateTime($dateStr, new \DateTimeZone($timezone));
		$stamp = $date->getTimestamp(); // get unix timestamp
		$time_in_ms = $stamp * 1000;
		
		$nowDate = new DateTime();
		$nowDateStamp = $nowDate->getTimestamp();
	
		$nowDateMilli = $nowDateStamp * 1000;
		
		if($nowDateMilli > $time_in_ms) {
			return true;
		}
	}
	public function SaveUserPicks(Request $request, Application $app) {
	/*
			var data = 
			{ query:'mutation{createUserBracket(contest_id:"'+userBracket.contest_id+'",user_id:'+userBracket.user_id+',league:"'+userBracket.league+'",bracket_id:'+userBracket.bracket_id+',matches:"'+singleQObj+'"){ contest_id user_id league bracket_id matches }}'}; 
			$http.post('//draftbrackets.com:8569/graphql', data)
				.success(function (data, status, headers, config) {
					console.log(data);
				})
				.error(function (data, status, header, config) {
					
					
				});
			*/
			
			$bracket_id =  $request->get('bracket_id');
			
			if(!$this->alreadyInBracket($bracket_id ,$app,$request)) {
				 throw new \Exception("You are not enrolled in this bracket");
			}
			
			if($this->hasBracketSqlSubmitPassed($bracket_id)) {
				 throw new \Exception("Time is up, you can't save anymore");
			}
			//compare to time 
			$contest_id =  $request->get('contest_id');
				$query = http_build_query(array('query'=> 'query{contest(_id:"'.$contest_id.
			'") {
			  submitTime
			  expireTime
			}}'));
			//change this to env variable
			$url = MONGO_API_BASE_URL.$query;
			

			// If using JSON...
			$this->checkIfTimePassed($this->getUrlContents($url));
			
			
			
			$league =  $request->get('league_name');
			
			
			$matches =  $request->get('matches');
			
			$current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);
			$user_id =  $current_user->id ? $current_user->id : 0;
			$user_name =  $current_user->name ? $current_user->name : "";
			$username =  $current_user->username ? $current_user->username : "";
			
			//before can run the following have to make sure the user is in the bracket and that the time has not passed yet
			
			/*
			$mutation = 'mutation{createUserBracket(contest_id:"'.$contest_id.'",user_id:'.$user_id.',user_name:"'.$user_name.'",username:"'.$username.'",league:"'.$league.'",bracket_id:'.$bracket_id.',matches:"'.$matches.'"){ contest_id user_id league bracket_id matches }}';
			// If using JSON...
			$url = 'https://draftbrackets.com:8569/graphql';
			*/
			
			$args = array();
			$args["user_id"] = $user_id;
			$args["username"] = $username;
			$args["user_name"] = $user_name;
			$args["matches"] = $matches;
			$args["contest_id"] = $contest_id;
			$args["bracket_id"] = $bracket_id;
			$args["league_name"] = $league;
			$url = MONGO_API_BASE_URL_NO_QUERY."save_bracket";

			$response = $this->getUrlContents($url,null,true,true,$args);
			//var_dump('$response');
			$decoded = json_decode($response);
			
			 
			if($decoded->success == "true") {
				
			} else {
				
				//echo $response;
				throw new \Exception($decoded->message);
			}
			
			
			$returnVar = array();
			$returnVar["success"] = $decoded->message;
			return json_encode($returnVar) ;
			
	
	}
private function checkIfTimePassed($response) {
			$contestTimes = json_decode($response);
			$qlTime = $contestTimes->data->contest->submitTime;
			
			if(strpos($qlTime, 'undefined') > -1) {
				return false;
			}
			$submitTime = new DateTime($qlTime);
			$timeMilli = date_timestamp_get($submitTime);
			
			if(time() > $timeMilli) {
				return false;
			}
			return true;
}
  public function GetPublicBracket(Request $request, $password = '') {
	$teams = $this->GetTeamsAndBracketAndSeries($request);

	return $teams;

  }
  public function GetPublicStandings(Request $request, $pool_id, $contest_id,$league) {
		
		$query = http_build_query(array('query'=> 'query{contest(_id:"'.$contest_id.'"){_id submitTime
		expireTime}contest_brackets(contest_id:"'.$contest_id.
		'",bracket_id:'.$pool_id.
		'){
		  _id
		  contest_id
		  user_id
		  username
		  user_name
		  league
		  bracket_id
		  matches
		}league(name:"'.$league.
		'") {
			result_bracket
			total_rounds
		}contest(_id:"'.$contest_id.
				'") {
				  submitTime
				  expireTime
				  					autofill_results

				}
		}'));
		//change this to env variable
		$url = 'https://draftbrackets.com:8569/graphql?'.$query;
		

		// If using JSON...
		$response = $this->getUrlContents($url);
		return $response;

  }

  /*
  * This method is only to be used internally or when the user has been verified as owner of the bracket (or is admin)
  * (in other words, don't call this then return the result as JSON!)
  */
  private function Load($id, $bustCache = false) {
    $bracket = new Bracket();
	
   

      $bracket_stmt = $this->app['db']->prepare("SELECT d.* FROM bracket d
      WHERE bracket_id = ? LIMIT 1");

      $bracket_stmt->setFetchMode(\PDO::FETCH_INTO, $bracket);

      $bracket_stmt->bindParam(1, $id, \PDO::PARAM_INT);

      if(!$bracket_stmt->execute() || !$bracket_stmt->fetch()) {
        throw new \Exception("Unable to load bracket");
      }


    return $bracket;
  }

  public function Create(Bracket $bracket) {
    $insert_stmt = $this->app['db']->prepare("INSERT INTO bracket
      (bracket_id, commish_id, bracket_create_time, bracket_name, bracket_sport, bracket_status, bracket_style, bracket_rounds, bracket_password, using_depth_charts)
      VALUES
      (NULL, ?, UTC_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?)");

    $insert_stmt->bindParam(1, $bracket->commish_id);
    $insert_stmt->bindParam(2, $bracket->bracket_name);
    $insert_stmt->bindParam(3, $bracket->bracket_sport);
    $insert_stmt->bindParam(4, $bracket->bracket_status);
    $insert_stmt->bindParam(5, $bracket->bracket_style);
    $insert_stmt->bindParam(6, $bracket->bracket_rounds);
    $insert_stmt->bindParam(7, $bracket->bracket_password);
    $insert_stmt->bindParam(8, $bracket->using_depth_charts);

    if(!$insert_stmt->execute()) {
      throw new \Exception("Unable to create bracket.");
    }

    $bracket = $this->Load((int)$this->app['db']->lastInsertId(), true);

    return $bracket;
  }

  //Excluded properties in update:
  //bracket_start_time/bracket_end_time - updated in separate operations at start/end of bracket
  //bracket_current_round/bracket_current_pick - updated when new picks are made
  //bracket_counter - call IncrementBracketCounter instead - this call's made a lot independently of other properties.
  //bracket_status - separate API call to update the bracket status
  public function Update(Bracket $bracket) {
    $update_stmt = $this->app['db']->prepare("UPDATE bracket
      SET commish_id = ?, bracket_name = ?, bracket_sport = ?,
      bracket_style = ?, bracket_password = ?, bracket_rounds = ?,
      using_depth_charts = ?
      WHERE bracket_id = ?");

    $bracket->using_depth_charts = $bracket->using_depth_charts;

    $update_stmt->bindParam(1, $bracket->commish_id);
    $update_stmt->bindParam(2, $bracket->bracket_name);
    $update_stmt->bindParam(3, $bracket->bracket_sport);
    $update_stmt->bindParam(4, $bracket->bracket_style);
    $update_stmt->bindParam(5, $bracket->bracket_password);
    $update_stmt->bindParam(6, $bracket->bracket_rounds);
    $update_stmt->bindParam(7, $bracket->using_depth_charts);
    $update_stmt->bindParam(8, $bracket->bracket_id);

    if(!$update_stmt->execute()) {
      throw new \Exception("Unable to update bracket.");
    }

    $this->ResetBracketCache($bracket->bracket_id);

    return $bracket;
  }

  public function UpdateStatus(Bracket $bracket) {
    $status_stmt = $this->app['db']->prepare("UPDATE bracket
      SET bracket_status = ? WHERE bracket_id = ?");

    $status_stmt->bindParam(1, $bracket->bracket_status);
    $status_stmt->bindParam(2, $bracket->bracket_id);

    if(!$status_stmt->execute()) {
      throw new \Exception("Unable to update bracket status.");
    }

    $this->ResetBracketCache($bracket->bracket_id);

    return $bracket;
  }

  public function UpdateStatsTimestamp(Bracket $bracket) {
    $status_stmt = $this->app['db']->prepare("UPDATE bracket
      SET bracket_stats_generated = UTC_TIMESTAMP() WHERE bracket_id = ?");

    $status_stmt->bindParam(1, $bracket->bracket_id);

    if(!$status_stmt->execute()) {
      throw new \Exception("Unable to update bracket's stats timestamp.");
    }

    $this->ResetBracketCache($bracket->bracket_id);

    return $bracket;
  }

  public function IncrementBracketCounter(Bracket $bracket) {
    $incrementedCounter = (int)$bracket->bracket_counter + 1;

    $increment_stmt = $this->app['db']->prepare("UPDATE bracket
      SET bracket_counter = ? WHERE bracket_id = ?");

    $increment_stmt->bindParam(1, $incrementedCounter);
    $increment_stmt->bindParam(2, $bracket->bracket_id);

    if(!$increment_stmt->execute()) {
      throw new \Exception("Unable to increment bracket counter.");
    }

    $this->ResetBracketCache($bracket->bracket_id);

    return $incrementedCounter;
  }

  //$next_pick can't be type-hinted - can be null
  public function MoveBracketForward(Bracket $bracket, $next_pick) {
    if ($next_pick !== null) {
      $bracket->bracket_current_pick = (int) $next_pick->player_pick;
      $bracket->bracket_current_round = (int) $next_pick->player_round;

      $stmt = $this->app['db']->prepare("UPDATE bracket SET bracket_current_pick = ?, bracket_current_round = ? WHERE bracket_id = ?");
      $stmt->bindParam(1, $bracket->bracket_current_pick);
      $stmt->bindParam(2, $bracket->bracket_current_round);
      $stmt->bindParam(3, $bracket->bracket_id);

      if (!$stmt->execute()) {
        throw new \Exception("Unable to move bracket forward.");
      }
    } else {
      $bracket->bracket_status = 'complete';
      $stmt = $this->app['db']->prepare("UPDATE bracket SET bracket_status = ?, bracket_end_time = UTC_TIMESTAMP() WHERE bracket_id = ?");
      $stmt->bindParam(1, $bracket->bracket_status);
      $stmt->bindParam(2, $bracket->bracket_id);

      if (!$stmt->execute()) {
        throw new \Exception("Unable to move bracket forward.");
      }
    }

    $this->ResetBracketCache($bracket->bracket_id);

    return $bracket;
  }

  //Used when we move a bracket from "unbracketed" to "in_progress":
  //Resets the bracket counter
  //Sets the current pick and round to 1
  //Sets the bracket start time to UTC now, nulls out end time
  public function SetBracketInProgress(Bracket $bracket) {
    $reset_stmt = $this->app['db']->prepare("UPDATE bracket
      SET bracket_counter = 0, bracket_current_pick = 1, bracket_current_round = 1,
      bracket_start_time = UTC_TIMESTAMP(), bracket_end_time = NULL
      WHERE bracket_id = ?");

    $reset_stmt->bindParam(1, $bracket->bracket_id);

    if(!$reset_stmt->execute()) {
      throw new \Exception("Unable to set bracket to in progress.");
    }

    $this->ResetBracketCache($bracket->bracket_id);

    return 0;
  }

  public function NameIsUnique($name, $id = null) {
    if(!empty($id)) {
      $name_stmt = $this->app['db']->prepare("SELECT bracket_name FROM bracket WHERE bracket_name LIKE ? AND bracket_id <> ?");
      $name_stmt->bindParam(1, $name);
      $name_stmt->bindParam(2, $id);
    } else {
      $name_stmt = $this->app['db']->prepare("SELECT bracket_name FROM bracket WHERE bracket_name LIKE ?");
      $name_stmt->bindParam(1, $name);
    }

    if(!$name_stmt->execute()) {
      throw new \Exception("Bracket name '%s' is invalid", $name);
    }

    return $name_stmt->rowCount() == 0;
  }

  public function DeleteBracket($bracket_id) {
    $delete_stmt = $this->app['db']->prepare("DELETE FROM bracket WHERE bracket_id = ?");
    $delete_stmt->bindParam(1, $bracket_id);

    if(!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete bracket $bracket_id.");
    }

    $this->UnsetCachedBracket($bracket_id);

    return;
  }

  private function ResetBracketCache($bracket_id) {
    $bracket = $this->Load($bracket_id, true);
  }

  private function SetCachedBracket(Bracket $bracket) {
    $this->app['phpdraft.DatabaseCacheService']->SetCachedItem("bracket$bracket->bracket_id", $bracket);
  }

  private function GetCachedBracket($bracket_id) {
    return $this->app['phpdraft.DatabaseCacheService']->GetCachedItem("bracket$bracket_id");
  }

  private function UnsetCachedBracket($bracket_id) {
    $this->app['phpdraft.DatabaseCacheService']->DeleteCachedItem("bracket$bracket_id");
  }

  private function ProtectPrivateBracket(Bracket $bracket) {
    $bracket->bracket_sport = '';
    $bracket->bracket_status = '';
    $bracket->setting_up = '';
    $bracket->in_progress = '';
    $bracket->complete = '';
    $bracket->bracket_style = '';
    $bracket->bracket_rounds = '';
    $bracket->bracket_counter = '';
    $bracket->bracket_start_time = null;
    $bracket->bracket_end_time = null;
    $bracket->bracket_current_pick = '';
    $bracket->bracket_current_round = '';
    $bracket->bracket_create_time = '';
    $bracket->bracket_stats_generated = '';
    $bracket->nfl_extended = null;
    $bracket->sports = null;
    $bracket->styles = null;
    $bracket->statuses = null;
    $bracket->teams = null;
    $bracket->positions = null;
    $bracket->using_depth_charts = null;
    $bracket->depthChartPositions = null;

    return $bracket;
  }

}