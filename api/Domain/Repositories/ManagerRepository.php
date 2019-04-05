<?php
namespace PhpDraft\Domain\Repositories;


use Silex\Application;
use PhpDraft\Domain\Entities\Manager;

use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\MailMessage;




class ManagerRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function Load($id) {
    $manager = new Manager();

    $load_stmt = $this->app['db']->prepare("SELECT * FROM managers WHERE manager_id = ? LIMIT 1");
    $load_stmt->setFetchMode(\PDO::FETCH_INTO, $manager);
    $load_stmt->bindParam(1, $id);

    if (!$load_stmt->execute())
      throw new \Exception(sprintf('Manager "%s" does not exist.', $manager));

    if (!$load_stmt->fetch())
      throw new \Exception(sprintf('Manager "%s" does not exist.', $id));

    return $manager;
  }

  public function GetPublicManagers($draft_id) {
    $managers = array();

    $managers_stmt = $this->app['db']->prepare("SELECT * FROM managers WHERE draft_id = ? ORDER BY draft_order");
    $managers_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Manager');

    $managers_stmt->bindParam(1, $draft_id);

    if(!$managers_stmt->execute()) {
      throw new \Exception("Unable to load managers for draft #$draft_id");
    }

    while($manager = $managers_stmt->fetch()) {
      $managers[] = $manager;
    }

    return $managers;
  }
  public function GetPublicManagersBracket($bracket_id) {
    $managers = array();

    $managers_stmt = $this->app['db']->prepare("SELECT managers.*,u.name,u.username,u.email  FROM managers LEFT JOIN users AS u ON u.id = managers.user_id WHERE draft_id = ? ");
    $managers_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Manager');

    $managers_stmt->bindParam(1, $bracket_id);

    if(!$managers_stmt->execute()) {
      throw new \Exception("Unable to load managers for draft #$draft_id");
    }

    while($manager = $managers_stmt->fetch()) {
      $managers[] = $manager;
    }

    return $managers;
  }

  public function GetManagersByDraftOrder($draft_id, $descending = false) {
    $managers = array();

    if($descending) {
      $managers_stmt = $this->app['db']->prepare("SELECT * FROM managers WHERE draft_id = ? ORDER BY draft_order DESC");
    } else {
      $managers_stmt = $this->app['db']->prepare("SELECT * FROM managers WHERE draft_id = ? ORDER BY draft_order");  
    }

    $managers_stmt->bindParam(1, $draft_id);

    $managers_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Manager');

    if(!$managers_stmt->execute()) {
      throw new \Exception("Unable to load managers for draft #$draft_id");
    }

    while($manager = $managers_stmt->fetch()) {
      $managers[] = $manager;
    }

    return $managers;
  }

  //Ensure a draft has the minimum number of managers - 2
  public function DraftHasManagers($draft_id) {
    $manager_stmt = $this->app['db']->prepare("SELECT manager_name FROM managers WHERE draft_id = ?");
    $manager_stmt->bindParam(1, $draft_id);

    if(!$manager_stmt->execute()) {
      throw new \Exception("Draft id '%s' is invalid", $draft_id);
    }

    return $manager_stmt->rowCount() > 1;
  }

  public function GetNumberOfCurrentManagers($draft_id) {

	
    $manager_stmt = $this->app['db']->prepare("SELECT COUNT(manager_id) FROM managers WHERE draft_id = ?");
    $manager_stmt->bindParam(1, $draft_id);
	
    if(!$manager_stmt->execute()) {
      throw new \Exception("Unable to get number of managers for draft #$draft_id");
    }
	
    return (int)$manager_stmt->fetchColumn(0);
  }

  public function NameIsUnique($name, $draft_id = 0, $id = null) {
	  return true;
    if(!empty($id)) {
      $name_stmt = $this->app['db']->prepare("SELECT manager_name FROM managers WHERE manager_name LIKE ? AND draft_id = ? AND manager_id <> ?");
      $name_stmt->bindParam(1, $name);
      $name_stmt->bindParam(2, $draft_id);
      $name_stmt->bindParam(3, $id);
    } else {
      $name_stmt = $this->app['db']->prepare("SELECT manager_name FROM managers WHERE manager_name LIKE ? AND draft_id = ?");
      $name_stmt->bindParam(1, $name);
      $name_stmt->bindParam(2, $draft_id);
    }

    if(!$name_stmt->execute()) {
      throw new \Exception("Manager name '$name' is invalid");
    }

    return $name_stmt->rowCount() == 0;
  }

  public function ManagerExists($id, $draft_id) {
    $exists_stmt = $this->app['db']->prepare("SELECT COUNT(manager_id) FROM managers WHERE manager_id = ? AND draft_id = ?");
    $exists_stmt->bindParam(1, $id);
    $exists_stmt->bindParam(2, $draft_id);

    if(!$exists_stmt->execute()) {
      throw new \Exception("Manager ID $id is invalid");
    }

    return $exists_stmt->fetchColumn(0) == 1;
  }

  public function Create(Manager $manager ) {
	$enrolled_stmt="";
	if($manager->contest_type == "bracket"){
		$enrolled_stmt = $this->app['db']->prepare("SELECT COUNT(DISTINCT(managers.user_id )) AS enrolled,  b.* FROM managers RIGHT JOIN bracket AS b ON b.contest_id = managers.contest_id
			WHERE b.contest_id = ? && b.bracket_id = ?");
		 $enrolled_stmt->bindParam(1, $manager->contest_id);
		 $enrolled_stmt->bindParam(2, $manager->draft_id);
	}else {
		$enrolled_stmt = $this->app['db']->prepare("SELECT COUNT(DISTINCT(managers.user_id )) AS enrolled,  d.* FROM managers LEFT JOIN draft AS d ON d.contest_id = managers.contest_id
			WHERE d.contest_id = ? && d.draft_id = ? ");
		 $enrolled_stmt->bindParam(1, $manager->contest_id);
		 $enrolled_stmt->bindParam(2, $manager->draft_id);	
	}
	 
	  if(!$enrolled_stmt->execute()) {
		  throw new \Exception("Unable to get currently enrolled.");
		}
		$enrolled = $enrolled_stmt->fetch();
		if($enrolled["enrolled"] == ($enrolled["max_players"])) {
			throw new \Exception("Contest is Closed.");
		}
		if($enrolled["enrolled"] == ($enrolled["max_players"] -1)) {
			$close_stmt ="";
			if($manager->contest_type == "bracket"){
				$close_stmt = $this->app['db']->prepare("UPDATE bracket SET bracket_closed=1 WHERE bracket_id = ?");		 
				$close_stmt->bindParam(1, $manager->draft_id);
			} else {
				$close_stmt = $this->app['db']->prepare("UPDATE draft SET draft_closed=1 WHERE draft_id = ?");		 
				$close_stmt->bindParam(1, $manager->draft_id);
			}
			  if(!$close_stmt->execute()) {
				  throw new \Exception("Unable to close bracket.");
			} else {
				
			 /* disabling auto renewal
				if(false){
				//if($manager->contest_type == "bracket"){
					$open_stmt = $this->app['db']->prepare("INSERT INTO bracket (`bracket_id`, `contest_id`,`bracket_create_time`, `bracket_name`,  `bracket_description`,`bracket_sport`, `bracket_status`,  `bracket_rounds`,`max_players`,`bracket_fee`,`contest_type`, `bracket_start_time`, `bracket_submit_time`,`cash_prize`) VALUES ( NULL,?,CURRENT_TIMESTAMP() ,?,?,?,'undrafted',?,?,?,?,?,?,?)");
					 $open_stmt->bindParam(1, $enrolled["contest_id"]);
					 $open_stmt->bindParam(2, $enrolled["bracket_name"]);
					 $open_stmt->bindParam(3, $enrolled["bracket_description"]);
					 $open_stmt->bindParam(4, $enrolled["bracket_sport"]);
					 $open_stmt->bindParam(5, $enrolled["bracket_rounds"]);
					 $open_stmt->bindParam(6, $enrolled["max_players"]);
					 $open_stmt->bindParam(7, $enrolled["bracket_fee"]);
					 $open_stmt->bindParam(8, $enrolled["contest_type"]);
					 $open_stmt->bindParam(9, $enrolled["bracket_start_time"]);
					 $open_stmt->bindParam(10, $enrolled["bracket_submit_time"]);
					 $open_stmt->bindParam(11, $enrolled["cash_prize"]);
				}
				elseif(false) {
				//else {
					$open_stmt = $this->app['db']->prepare("INSERT INTO draft (`draft_id`, `commish_id`, `draft_create_time`, `draft_name`, `draft_sport`,`contest_id`,`comp_type`, `draft_status`,`max_players`, `draft_fee`, `cash_prize`, `draft_counter`,  `draft_style`, `draft_rounds`, `draft_password`, `draft_start_time`, `draft_end_time`, `draft_stats_generated`, `draft_current_round`, `draft_current_pick`, `nfl_extended`, `using_depth_charts`) VALUES ( NULL,1,CURRENT_TIMESTAMP() ,?,?,?,?,'undrafted',?,?,?,0,'standard',?,NULL,?,?,NULL,1,1,0,0)");
					
					 $open_stmt->bindParam(1, $enrolled["draft_name"]);
					 $open_stmt->bindParam(2, $enrolled["draft_sport"]);
					 $open_stmt->bindParam(3, $enrolled["contest_id"]);
					 $open_stmt->bindParam(4, $enrolled["comp_type"]);
					 $open_stmt->bindParam(5, $enrolled["max_players"]);
					 $open_stmt->bindParam(6, $enrolled["draft_fee"]);
					 $open_stmt->bindParam(7, $enrolled["cash_prize"]);
					 $open_stmt->bindParam(8, $enrolled["draft_rounds"]);
					 $open_stmt->bindParam(9, $enrolled["draft_start_time"]);
					 $open_stmt->bindParam(10, $enrolled["draft_end_time"]);
				}
				
				 if(!$open_stmt->execute()) {
				  throw new \Exception("Unable to open bracket.");
				} else {
				
					$insertID = $this->app['db']->lastInsertId();
					
					
					
					//$insert2D = $this->app['db']->getConnection()->lastInsertId();
					
					 $save_stmt = $this->app['db']->prepare("INSERT INTO managers (manager_id,user_id, draft_id,contest_id, manager_name, draft_order) VALUES (NULL,?, ?, ?, ?,?)");
					$save_stmt->bindParam(1, $manager->user_id);
					$save_stmt->bindParam(2, $insertID);
					$save_stmt->bindParam(3, $manager->contest_id);
					$save_stmt->bindParam(4, $manager->manager_name);
					$save_stmt->bindParam(5, $new_draft_order);

					$new_draft_order = $this->_GetLowestDraftorder($manager->draft_id) + 1;

					if (!$save_stmt->execute()) {
					  throw new \Exception("Unable to create new manager: " . $this->app['db']->errorInfo());
					}

					$manager->manager_id = (int) $this->app['db']->lastInsertId();

					return $manager;
					
			//	}
			}
			  */	 
			}
		 }
	
	//zero means no one entered yet so can't do a join
	if($enrolled["enrolled"] <= ($enrolled["max_players"])) {
	if($manager->contest_type == "draft"){
		if($enrolled["draft_status"] == "undrafted") {
			
		} else {
			throw new \Exception("Draft Already Started.");
			
		}
	}	
		//check here
        ?><?php 
          //  print_r($manager);
          //  print_r($enrolled);
        ?><?php 

	
		$save_stmt = $this->app['db']->prepare("INSERT INTO managers (manager_id,user_id, draft_id,contest_id, manager_name, draft_order) VALUES (NULL,?, ?, ?, ?,?)");
		$save_stmt->bindParam(1, $manager->user_id);
		$save_stmt->bindParam(2, $manager->draft_id);
		$save_stmt->bindParam(3, $manager->contest_id);
		$save_stmt->bindParam(4, $manager->manager_name);
		$save_stmt->bindParam(5, $new_draft_order);

    $new_draft_order = $this->_GetLowestDraftorder($manager->draft_id) + 1;

    if (!$save_stmt->execute()) {
      throw new \Exception("Unable to create new manager: " . $this->app['db']->errorInfo());
    } else {
		//send the email here since the manager successfully created
        $manager_name           = $manager->manager_name;
        
        $message                = new MailMessage();
        // $message->CC            = "babiizhee@gmail.com";
        $message->to_addresses  = array (
            $manager->email     => $manager_name
            // $manager->email     => $message->CC
        );
        $message->is_html       = true;
        

		if ($manager->contest_type == "bracket") {
            // $upcoming_bracket   = $enrolled["bracket_start_time"];
            $upcoming_br_date   = new \DateTime($enrolled["bracket_start_time"]);
            // $upcoming_bracket   = $upcoming_br_date->format('Y-m-d h:i A');
            $upcoming_bracket   = $upcoming_br_date->format('h:i A') . " PST on " . $upcoming_br_date->format('F j, Y');
            $bracket_name       = $enrolled["bracket_name"];
            $message->subject   = $bracket_name . " Registration Completed";
			      $message->body      = sprintf("Hi %s, <br/><br/>\n\n 

                This email notification is to confirm you are registered for <strong>%s</strong> Bracket Contest with an upcoming deadline at <strong>%s</strong>  to submit your bracket. Do your homework and get your scouting done. You deserve the bragging rights! <br/><br/>\n\n

                <img src='https://draftbrackets.com/images/draftbracketlogopng.png' alt='Draft Brackets Logo' title='Draft Brackets Logo' style='display:block' width='200' height='200' /> <br/><br/>\n\n

                Please contact <a href=mailto:'support@draftbrackets.com' target='_top'>support@draftbrackets.com</a> for all your customer needs. We want to make your experience as positive as possible. <br/><br/>\n\n
                ", $manager_name, $bracket_name, $upcoming_bracket);

            $this->app['phpdraft.EmailService']->SendMail($message);
		} else { 
            // $upcoming_draft     = $enrolled["draft_start_time"];
            $upcoming_dr_date   = new \DateTime($enrolled["draft_start_time"]);
            // $upcoming_draft     = $upcoming_dr_date->format('Y-m-d h:i A');
            $upcoming_draft     = $upcoming_dr_date->format('h:i A') . " on " . $upcoming_dr_date->format('F j, Y');
            $draft_name         = $enrolled["draft_name"];
            $message->subject   = $draft_name . " Registration Completed";
      			$message->body      = sprintf("Hi %s, <br/><br/>\n\n 

                This email notification is to confirm you are registered for <strong>%s</strong> Draft Contest with an upcoming draft at <strong>%s</strong> PST. Do your homework and get your scouting done. You deserve the bragging rights. <br/><br/>\n\n

                <img src='https://draftbrackets.com/images/draftbracketlogopng.png' alt='Draft Brackets Logo' title='Draft Brackets Logo' style='display:block' width='200' height='200' /> <br/><br/>\n\n

                Please contact <a href=mailto:'support@draftbrackets.com' target='_top'>support@draftbrackets.com</a> for all your customer needs. We want to make your experience as positive as possible. <br/><br/>\n\n
                ", $manager_name, $draft_name, $upcoming_draft);

            $this->app['phpdraft.EmailService']->SendMail($message);
		}
        ?><?php 
           // print_r($message);
        ?><?php 
	}

    $manager->manager_id = (int) $this->app['db']->lastInsertId();
	
    return $manager;
	}
}

  public function CreateMany($draft_id, $contest_id,$managersArray) {

    $save_stmt = $this->app['db']->prepare("INSERT INTO managers (manager_id, draft_id,contest_id, manager_name,user_id, draft_order) 
      VALUES 
      (NULL, :draft_id,:contest_id, :manager_name,:user_id, :draft_order)");
    $newDraftOrder = $this->_GetLowestDraftorder($draft_id) + 1;
    $newManagers = array();

    foreach($managersArray as $newManager) {
       $save_stmt->bindValue(':draft_id', $newManager->draft_id);
		$save_stmt->bindValue(':contest_id', $newManager->contest_id);
      $save_stmt->bindValue(':manager_name', $newManager->manager_name);
      $save_stmt->bindValue(':user_id', $newManager->user_id);
      $save_stmt->bindValue(':draft_order', $newDraftOrder++);

      if (!$save_stmt->execute()) {
        throw new \Exception("Unable to save managers for $draft_id");
      }

      $newManager->manager_id = (int)$this->app['db']->lastInsertId();
      $newManagers[] = $newManager;
    }

    return $newManagers;
  }

  public function Update(Manager $manager) {
    $update_stmt = $this->app['db']->prepare("UPDATE managers SET manager_name = ? WHERE manager_id = ?");
    $update_stmt->bindParam(1, $manager->manager_name);
    $update_stmt->bindParam(2, $manager->manager_id);

    if(!$update_stmt->execute()) {
      throw new \Exception("Unable to update manager #$manager->manager_id");
    }

    return $manager;
  }

  public function ReorderManagers($managersIdArray) {
    $reorder_stmt = $this->app['db']->prepare("UPDATE managers SET draft_order = :draft_order WHERE manager_id = :manager_id");

    $newDraftOrder = 1;

    foreach($managersIdArray as $manager_id) {
      $reorder_stmt->bindValue(':draft_order', $newDraftOrder++);
      $reorder_stmt->bindValue(':manager_id', $manager_id);

      if(!$reorder_stmt->execute()) {
        throw new \Exception("Unable to update manager order for manager #$manager_id");
      }
    }

    return;
  }

  public function DeleteManager($manager_id) {
    $delete_stmt = $this->app['db']->prepare("DELETE FROM managers WHERE manager_id = ?");
    $delete_stmt->bindParam(1, $manager_id);

    if(!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete manager $manager_id.");
    }

    return;
  }

  public function DeleteAllManagers($draft_id) {
    $delete_stmt = $this->app['db']->prepare("DELETE FROM managers WHERE draft_id = ?");
    $delete_stmt->bindParam(1, $draft_id);

    if(!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete managers for draft $draft_id.");
    }

    return;
  }

  /**
   * In order to get the lowest current draft number for this draft.
   * @return int Lowest draft order for the given draft
   */
  private function _GetLowestDraftorder($draft_id) {
  
    $stmt = $this->app['db']->prepare("SELECT draft_order FROM managers WHERE draft_id = ? ORDER BY draft_order DESC LIMIT 1");
    $stmt->bindParam(1, $draft_id);

    if (!$stmt->execute()) {
      throw new \Exception("Unable to get lowest manager draft order.");
    }

    //If there are no managers, the lowest order is 0 since we'll be adding 1 to it.
    if ($stmt->rowCount() == 0) {
      return 0;
    }

    if (!$row = $stmt->fetch()) {
      throw new \Exception("Unable to get lowest manager draft order.");
    }

    return (int)$row['draft_order'];
  }
}