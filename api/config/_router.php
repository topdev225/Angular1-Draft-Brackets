<?php

if (!$app instanceof Silex\Application) {
  throw new Exception('Invalid application setup.');
}

$app->register(new Silex\Provider\ServiceControllerServiceProvider());

$app['authentication.controller'] = function() {
  return new PhpDraft\Controllers\AuthenticationController();
};

$app['contact.controller'] = function() {
  return new PhpDraft\Controllers\ContactController();
};

$app['confirmation.controller'] = function() {
  return new PhpDraft\Controllers\ConfirmationController();
};

$app['index.controller'] = function() {
  return new PhpDraft\Controllers\IndexController();
};

$app['draft.controller'] = function() {
  return new PhpDraft\Controllers\DraftController();
};

$app['bracket.controller'] = function() {
  return new PhpDraft\Controllers\BracketController();
};

$app['bracket.controller'] = function() {
  return new PhpDraft\Controllers\BracketController();
};

$app['commish.controller'] = function() {
  return new PhpDraft\Controllers\CommishController();
};

$app['manager.controller'] = function() {
  return new PhpDraft\Controllers\ManagerController();
};

$app['pick.controller'] = function() {
  return new PhpDraft\Controllers\PickController();
};
$app['route.controller'] = function() {
  return new PhpDraft\Controllers\RouteController();
};

$app['trade.controller'] = function() {
  return new PhpDraft\Controllers\TradeController();
};

$app['roundtime.controller'] = function() {
  return new PhpDraft\Controllers\RoundTimeController();
};

$app['admin.index.controller'] = function() {
  return new PhpDraft\Controllers\Admin\IndexController();
};

$app['admin.draftstats.controller'] = function() {
  return new PhpDraft\Controllers\Admin\DraftStatsController();
};

$app['admin.proplayers.controller'] = function() {
  return new PhpDraft\Controllers\Admin\ProPlayerController();
};

$app['admin.users.controller'] = function() {
  return new PhpDraft\Controllers\Admin\UserController();
};

$app['commish.index.controller'] = function() {
  return new PhpDraft\Controllers\Commish\IndexController();
};

$app['commish.profile.controller'] = function() {
  return new PhpDraft\Controllers\Commish\UserProfileController();
};

$app['commish.draft.controller'] = function() {
  return new PhpDraft\Controllers\Commish\DraftController();
};

$app['commish.manager.controller'] = function() {
  return new PhpDraft\Controllers\Commish\ManagerController();
};

$app['commish.proplayer.controller'] = function() {
  return new PhpDraft\Controllers\Commish\ProPlayerController();
};

$app['commish.trade.controller'] = function() {
  return new PhpDraft\Controllers\Commish\TradeController();
};

$app['commish.pick.controller'] = function() {
  return new PhpDraft\Controllers\Commish\PickController();
};

$app['commish.depthchartposition.controller'] = function() {
  return new PhpDraft\Controllers\Commish\DepthChartPositionController();
};

$app->get('/list_routes', 'route.controller:ListRoutes');
$app->post('/login', 'authentication.controller:Login');
$app->post('/register', 'authentication.controller:Register');
$app->post('/verify', 'authentication.controller:VerifyAccount');
$app->post('/lostPassword', 'authentication.controller:LostPassword');
$app->get('/verifyToken', 'authentication.controller:VerifyResetPasswordToken');
$app->post('/resetPassword', 'authentication.controller:ResetPassword');

$app->get('/drafts', 'draft.controller:GetAll');

$app->get('/brackets/iterate/send_reminders', 'bracket.controller:SendBracketReminders');

$app->get('/brackets/{sport}', 'bracket.controller:GetAll');
$app->get('/brackets/{sport}/type/{type}', 'bracket.controller:GetAll');
$app->get('/my/brackets', 'bracket.controller:GetAllByUser');
$app->get('/my/brackets/{status}/{sport}', 'bracket.controller:GetAllByUser');
$app->get('/my/brackets/{status}/{sport}/type/{type}', 'bracket.controller:GetAllByUser');
$app->get('/my/brackets/{status}', 'bracket.controller:GetAllByUser');
$app->get('/drafts/{commish_id}', 'draft.controller:GetAllByCommish');
$app->get('/draft/{id}', 'draft.controller:Get');  
$app->get('/draft/{id}/league/{league_name}', 'draft.controller:Get');  
$app->get('/draft/{draft_id}/stats', 'draft.controller:GetStats')->before($draftViewable);

//Brackets integrated from react app, a user can enter more than one competition so need a bracket id the bracket id would be the same for all users in that competition, the the competition id comes from the admin backend 
$app->get('/bracket/{id}/league/{league_name}/contest/{contest_id}', 'bracket.controller:Get')->before($contestEntered);
$app->get('/bracket/{id}/league/{league_name}/contest/{contest_id}/{user_id}', 'bracket.controller:Get')->before($contestEntered);

$app->get('/standings/pool/{pool_id}/contest/{contest_id}/league/{league}/type/{type}', 'bracket.controller:GetStandings')->before($contestEntered);

$app->post('/save_picks/{id}', 'bracket.controller:SetUserPicks');

$app->get('/commissioners/search', 'commish.controller:SearchPublicCommissioners');
$app->get('/commissioners/{commish_id}', 'commish.controller:GetPublicCommissioner');

$app->get('/draft/{draft_id}/managers', 'manager.controller:GetAll')->before($draftViewable);
$app->get('/draft/{draft_id}/manager/{manager_id}/depth_chart', 'manager.controller:GetManagerDepthChart')->before($draftViewable)->before($draftInProgressOrCompleted);

$app->get('/draft/{draft_id}/picks', 'pick.controller:GetAll')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/picks/updated', 'pick.controller:GetUpdated')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->put('/draft/{draft_id}/pick/{pick_id}/depth_chart/{position_id}', 'pick.controller:UpdateDepthChart')->before($draftViewable)->before($draftInProgressOrCompletedTenMinutes);#Give a ten minute grace period to allow edits right at the end
$app->get('/draft/{draft_id}/picks/last', 'pick.controller:GetLast')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/picks/next', 'pick.controller:GetNext')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/send_user_picks/draft/{draft_id}', 'pick.controller:SendUserPicks');
$app->get('/draft/{draft_id}/league/{league_name}/picks/made', 'pick.controller:GetMade')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/manager/{manager_id}/picks/all', 'pick.controller:GetAllManagerPicks')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/manager/{manager_id}/picks/selected', 'pick.controller:GetSelectedManagerPicks')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/round/{draft_round}/picks/all', 'pick.controller:GetAllRoundPicks')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/round/{draft_round}/picks/selected', 'pick.controller:GetSelectedRoundPicks')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/draft/{draft_id}/picks/search', 'pick.controller:SearchPicks')->before($draftViewable)->before($draftInProgressOrCompleted);

$app->get('/draft/{draft_id}/trades', 'trade.controller:GetAll')->before($draftViewable)->before($draftInProgressOrCompleted);
$app->get('/drafts/iterate/start_them', 'draft.controller:StartDrafts');

$app->get('/drafts/iterate/send_reminders', 'draft.controller:SendReminders');

$app->get('/draft/{draft_id}/timer/remaining', 'roundtime.controller:GetTimeRemaining')->before($draftViewable)->before($draftInProgress);

$app->get('/style', "index.controller:Style");

$app->get('/admin/drafts', "admin.draftstats.controller:GetDrafts");
//$app->post('/admin/draft/{draft_id}/stats', "admin.draftstats.controller:Create");
$app->get('/admin/sports', "admin.proplayers.controller:GetSports");
$app->post('/admin/proplayers', "admin.proplayers.controller:Upload");
$app->get('/admin/users', "admin.users.controller:Get");
$app->put('/admin/user/{user_id}', "admin.users.controller:Update");
$app->delete('/admin/user/{user_id}', "admin.users.controller:Delete")->before($actionNotAllowed);

$app->get('/commish', "commish.index.controller:Index");
//$app->get('/commish/profile', "commish.profile.controller:Get");
//$app->put('/commish/profile', "commish.profile.controller:Put");

$app->get('/commish/depthchartposition/positions', "commish.depthchartposition.controller:GetPositions"); //Only requires commish role, handled by firewall
$app->get('/commish/draft/{draft_id}', "commish.draft.controller:Get")->before($commishEditableDraft);

$app->get('/commish/draft/{draft_id}/timers', "commish.draft.controller:GetTimers")->before($commishEditableDraft);
//dont need because create from admin panel
//$app->post('/commish/draft', "commish.draft.controller:Create"); //Only requires commish role, handled by firewall
//$app->get('/commish/draft/create', "commish.draft.controller:GetCreate"); //Only requires commish role, handled by firewall
//this might be used when a pick is made
//$app->put('/commish/draft/{draft_id}', "commish.draft.controller:Update")->before($commishEditableDraft)->before($draftSettingUp);
//used in StartDrafts in draftcontroller
//$app->put('/commish/draft/{draft_id}/status', "commish.draft.controller:UpdateStatus");
//This would have to be done through nodejs only in the admin panel
//$app->delete('/commish/draft/{draft_id}', "commish.draft.controller:Delete")->before($commishEditableDraft);
//$app->post('/commish/draft/{draft_id}/timers', "commish.draft.controller:SetTimers")->before($commishEditableDraft)->before($draftSettingUp);

$app->get('/commish/draft/{draft_id}/managers', "commish.manager.controller:Get")->before($commishEditableDraft);
//pulling the user id from the token so no problem here also
$app->post('/commish/draft/{draft_id}/contest/{contest_id}/manager', "commish.manager.controller:Create");
//post enroll bracket working on
//$app->post('/commish/draft/{draft_id}/managers', "commish.manager.controller:CreateMany")->before($actionNotAllowed)->before($draftSettingUp);
//$app->put('/commish/draft/{draft_id}/managers/reorder', "commish.manager.controller:Reorder")->before($actionNotAllowed)->before($draftSettingUp);
//$app->put('/commish/draft/{draft_id}/manager/{manager_id}', "commish.manager.controller:Update")->before($commishEditableDraft)->before($draftSettingUp);
//change so can only delete your id
//$app->delete('/commish/draft/{draft_id}/manager/{manager_id}', "commish.manager.controller:Delete")->before($actionNotAllowed)->before($draftSettingUp);

//$app->get('/commish/proplayers/search', "commish.proplayer.controller:Search"); //Only requires commish role, handled by firewall

//$app->get('/commish/draft/{draft_id}/manager/{manager_id}/assets', "commish.trade.controller:GetAssets")->before($actionNotAllowed)->before($draftInProgress);
//$app->post('/commish/draft/{draft_id}/trade', "commish.trade.controller:Create")->before($actionNotAllowed)->before($draftInProgress);

$app->get('/commish/draft/{draft_id}/pick/current', "commish.pick.controller:GetCurrent")->before($commishEditableDraft)->before($draftInProgress);
//need only if your turn in the database and get user id from header token it will process already does that
$app->post('/commish/draft/{draft_id}/pick/{team_id}', "commish.pick.controller:Add")->before($commishEditableDraft)->before($draftInProgress);
$app->get('/commish/draft/{draft_id}/picks/lastFive', "commish.pick.controller:GetLast5")->before($commishEditableDraft)->before($draftInProgress);
$app->get('/commish/draft/{draft_id}/round/{draft_round}/picks', "commish.pick.controller:GetByRound")->before($commishEditableDraft)->before($draftInProgress);
//$app->put('/commish/draft/{draft_id}/pick/{team_id}', "commish.pick.controller:Update")->before($commishEditableDraft)->before($draftInProgress);

$app->post('/commish/draft/alreadyDrafted', "commish.pick.controller:AlreadyDrafted")->before($commishEditableDraft)->before($draftInProgress); 

 
$app->post('/mail_contact_message', "contact.controller:MailMessage");



