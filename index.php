<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html data-ng-app="app" > 
    <head>

        <!-- Ad Sense Code -->
        <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
        <script>
            (adsbygoogle = window.adsbygoogle || []).push({
                google_ad_client: "ca-pub-1960645873411728",
                enable_page_level_ads: true
            });
        </script>

  
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-118955291-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-118955291-1');
</script>
<!-- Global site tag (gtag.js) - Google AdWords: 970558216 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-970558216"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-970558216');
</script>

      <meta charset="utf-8">
      <title>Draft Brackets - Sport Contests</title>
	<link rel="shortcut icon" href="images/draftbracket-hi-res-favi100Res.png"> 

      <base href="/">

      <meta name="fragment" content="!">
      <!-- <meta name="viewport" content="width=device-width, initial-scale=1"> -->
      <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

      <link href='api/style' rel='stylesheet' type='text/css'>
      <link href='https://fonts.googleapis.com/css?family=Arimo:400,700,400italic|Prosto+One' rel='stylesheet' type='text/css'>

      <!-- Google font -->
      <link href="https://fonts.googleapis.com/css?family=Lato|Montserrat|Mukta" rel="stylesheet">

      <!-- jQuery -->
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

      <!-- Bootstrap CDN -->
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
      <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

      <!-- Adroll Remarketing code -->
      <script type="text/javascript"> 
        adroll_adv_id = "3RG3BSNLQJCRVKQCVWM544"; 
        adroll_pix_id = "TM2URHXPQBBHVBAD6GRVSJ";
        (function () { 
            var _onload = function(){ 
                if (document.readyState && !/loaded|complete/.test(document.readyState)){setTimeout(_onload, 10);return} 
                if (!window.__adroll_loaded){__adroll_loaded=true;setTimeout(_onload, 50);return} 
                var scr = document.createElement("script"); 
                var host = (("https:" == document.location.protocol) ? "https://s.adroll.com" : "http://a.adroll.com"); 
                scr.setAttribute('async', 'true'); 
                scr.type = "text/javascript"; 
                scr.src = host + "/j/roundtrip.js"; 
                ((document.getElementsByTagName('head') || [null])[0] || 
                    document.getElementsByTagName('script')[0].parentNode).appendChild(scr); 
            }; 
            if (window.addEventListener) {window.addEventListener('load', _onload, false);} 
            else {window.attachEvent('onload', _onload)} 
        }()); 
      </script>


      <!-- inject:css -->
      <link rel="stylesheet" href="css/style.css">
      <link rel="stylesheet" href="css/style-vendor.css">
    
	  
	 
      <!-- endinject -->
      <!-- inject:js -->
     
		<script src="js/compressed.js"></script>
	
		
		<script src="js/globals.js"></script>

    <script src="js/moment.min.js"></script>

    <script src="js/stickyfill.js"></script>
		
	 	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.5.3/angular-aria.js"></script>
		 <script src="https://ajax.googleapis.com/ajax/libs/angular_material/1.0.0/angular-material.min.js"></script>
      <script src="js/app.js"></script>
      <script src="js/angular_base/angular_component.js"></script>
      <script src="js/angular_base/angular_controller.js"></script>
      <script src="js/angular_base/angular_service.js"></script>
      <script src="js/base/base_controller.js"></script>
      <script src="js/config/add_auth_token_header.js"></script>
      <script src="js/config/add_draft_password_header.js"></script>
      <script src="js/config/global.js"></script>
      <script src="js/config/toastr_config.js"></script>
      <script src="js/constants/subscription_keys.js"></script>
      <script src="js/controllers/donationPrompt.js"></script>
      <script src="js/controllers/footer.js"></script>
      <script src="js/controllers/nav.js"></script>
      <script src="js/directives/donationPrompt.js"></script>
      <script src="js/directives/footer_bar.js"></script>
      <script src="js/directives/nav_bar.js"></script>


      <script src="js/listeners/reload_draft.js"></script>
      <script src="js/listeners/route_change_start.js"></script>
      <script src="js/listeners/route_change_success.js"></script>
      <script src="js/listeners/view_content_loaded.js"></script>
      <script src="js/services/authentication_service.js"></script>
      <script src="js/services/confirm_action_service.js"></script>
      <script src="js/services/depthChartPositionService.js"></script>
      <script src="js/services/donation_prompt_service.js"></script>
      <script src="js/services/draft_service.js"></script>
      <script src="js/services/error_service.js"></script>
      <script src="js/services/message_service.js"></script>
      <script src="js/services/model_api.js"></script>
      <script src="js/services/pick_service.js"></script>
      <script src="js/services/working_modal_service.js"></script>
      <script src="js/controllers/admin/pro_player_management.js"></script>
      <script src="js/controllers/admin/regenerate_stats.js"></script>
      <script src="js/controllers/admin/users.js"></script>
      <script src="js/controllers/authentication/edit_profile.js"></script>
      <script src="js/controllers/authentication/login_controller.js"></script>
      <script src="js/controllers/authentication/lost_password_controller.js"></script>
      <script src="js/controllers/authentication/register_controller.js"></script>
      <script src="js/controllers/authentication/reset_password_controller.js"></script>
      <script src="js/controllers/authentication/verify_controller.js"></script>
      <script src="js/controllers/commish/add_pick.js"></script>
      <script src="js/controllers/commish/draft_create.js"></script>
      <script src="js/controllers/commish/draft_edit.js"></script>
      <script src="js/controllers/commish/edit_pick.js"></script>
      <script src="js/controllers/commish/pick_timers.js"></script>
      <script src="js/controllers/commish/trade_add.js"></script>
      <script src="js/controllers/directives/commish_managers.js"></script>
      <script src="js/controllers/directives/commish_pick_edit.js"></script>
      <script src="js/controllers/draft/board.js"></script>
      <script src="js/controllers/draft/depth_chart.js"></script>
      <script src="js/controllers/draft/index.js"></script>
      <script src="js/controllers/draft/stats.js"></script>
      <script src="js/controllers/draft/trades.js"></script>
      <script src="js/controllers/draft/start_drafts.js"></script>
      
      <script src="js/controllers/home/by_commish.js"></script>
      <script src="js/controllers/home/home.js"></script>
      <script src="js/controllers/home/draftHome.js"></script>
      <script src="js/controllers/home/bracketHome.js"></script>
	  
      <script src="js/controllers/pool/standings.js"></script>
      <script src="js/controllers/company/contact.js"></script>
      <script src="js/controllers/company/contests_mobile.js"></script>
      <script src="js/controllers/company/bracket_rules.js"></script>
      <script src="js/controllers/company/draft_rules.js"></script>
      <script src="js/controllers/company/rules_and_scoring.js"></script>
      <script src="js/controllers/company/nba.js"></script>
      <script src="js/controllers/company/nhl.js"></script>
      <script src="js/controllers/company/nfl.js"></script>
      <script src="js/controllers/company/mlb.js"></script>
	  
	  
	   
	  
      <script src="js/controllers/create_contests/create_contests.js"></script>
      <script src="js/controllers/create_contests/invites.js"></script>

	  
	  
      <script src="js/controllers/modals/add_managers.js"></script>
      <script src="js/controllers/modals/admin_edit_user_modal.js"></script>
      <script src="js/controllers/modals/confirm_action_modal.js"></script>
      <script src="js/controllers/modals/draft_password_modal.js"></script>
      <script src="js/controllers/modals/duplicate_pick.js"></script>
      <script src="js/controllers/modals/confirm_pick.js"></script>
      <script src="js/controllers/modals/working_modal.js"></script>
      <script src="js/controllers/picks/by_manager.js"></script>
      <script src="js/controllers/picks/by_round.js"></script>
      <script src="js/controllers/picks/search.js"></script>
      <script src="js/directives/commish/commish_managers.js"></script>
      <script src="js/directives/commish/commish_pick_edit.js"></script>
	
	  
	
      <script src="js/directives/draft/depth_chart_positions.js"></script>
      <script src="js/directives/draft/draft_completed.js"></script>
      <script src="js/directives/draft/draft_in_progress.js"></script>
      <script src="js/directives/draft/draft_information.js"></script>
      <script src="js/directives/draft/draft_setting_up.js"></script>
      <script src="js/directives/draft/draft_state.js"></script>
      <script src="js/directives/draft/draft_status_label.js"></script>
      <script src="js/directives/picks/manager_picks_display.js"></script>
      <script src="js/directives/picks/pick_display.js"></script>
      <script src="js/directives/picks/team_display.js"></script>
      <script src="js/directives/picks/picks_by_round.js"></script>
      <script src="js/directives/picks/previous_picks.js"></script>
      <script src="js/directives/picks/trade_asset_display.js"></script>
      <script src="js/directives/picks/upcoming_picks.js"></script>
      <script src="js/directives/picks/avail_teams.js"></script>
      <script src="js/directives/shared/focus_on.js"></script>
      <script src="js/directives/shared/ng_enter.js"></script>
      <script src="js/directives/shared/precise_humanized_seconds.js"></script>
      <script src="js/directives/shared/section_error.js"></script>
      <script src="js/directives/shared/section_loading.js"></script>
      <script src="js/directives/shared/show-errors.js"></script>
	  
	
  <!--throwing in some drafts -->

    <link rel="stylesheet" href="css/slick/main.css" />
   <script src="js/directives/slick/slickJQ.js"></script>
  <script src="js/directives/slick/slick.min.js"></script>
  <!--throwing in some brackets -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.9.0/underscore-min.js"></script>
	 <script src="js/services/services.js"></script>
      <script src="js/directives/bracket/bracket.js"></script>
      <script src="js/directives/bracket/tournamentGenerator.js"></script>
	 <script src="js/controllers/bracket/sampleMMAData.js"></script>
	 <script src="js/controllers/bracket/mmaVanilla.js"></script>
	 	 <script src="js/controllers/bracket/mma.js"></script>
	 <script src="js/controllers/bracket/index.js"></script>

	 <script src="js/controllers/demo/demo.js"></script>
	 <script src="js/responsive-tables/js/dataTables.responsive.min.js"></script>

		 <script src="js/responsive-tables/js/tabbedtables.js"></script> 
	  <link rel="stylesheet" type="text/css" href="css/bracket.css" />
	<link rel="stylesheet" type="text/css" href="css/demo.css" />
	<link rel="stylesheet" type="text/css" href="js/responsive-tables/css/responsive.dataTables.min.css" />
	<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/angular-material/1.0.0/angular-material.css" />
	<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/flipclock/0.7.8/flipclock.min.css" />
	 
	  <!--throwing in some brackets -->  
	  
      <script src="js/dev.js"></script>
	 
	
      <script src="js/router.js"></script>
      <!-- endinject -->


    <!-- Google Material Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">


      <!-- Cookie -->
      <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
	   <!-- Event snippet for "JOIN" Button clicked on Draftbrackets.com conversion page In your html page, add the snippet and call gtag_report_conversion when someone clicks on the chosen link or button. --> 
	  <!-- Global site tag (gtag.js) - Google AdWords: 970558216 --> <script async src="https://www.googletagmanager.com/gtag/js?id=AW-970558216"></script> <script> window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', 'AW-970558216'); </script>
	 <script> function gtag_report_conversion(url) { var callback = function () { if (typeof(url) != 'undefined') { window.location = url; } }; gtag('event', 'conversion', { 'send_to': 'AW-970558216/az79CP7h-IMBEIiW5s4D', 'event_callback': callback }); return false; } </script>
	  
	  <!-- Moving away from datatables --->
	  <link rel="stylesheet" type="text/css" href="bower_components/angular-ui-grid/ui-grid.min.css">
		<script src="bower_components/angular-ui-grid/ui-grid.min.js"></script>
		
		 <link rel="stylesheet" href="css/customize-db.css">
  </head>
  <body>
    <nav-bar></nav-bar>
	

	 <div  ng-show="navCollapsed" data-ng-view id="page_body"></div>

	
   
	
	
    <footer-bar></footer-bar>

   
 
  </body>
</html>

