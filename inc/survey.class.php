<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginSurveyticketSurvey extends CommonDBTM {
   
   
   
   
   
   
   
   
   
// ************************************************************************************ //   
// *************************** Display survey in the ticket *************************** // 
// ************************************************************************************ // 
   
   function startSurvey() {
      global $DB,$CFG_GLPI;
      
      $query= "SELECT * FROM `glpi_plugin_surveyticket_questions`
         WHERE `is_start` = '1'
         LIMIT 1";
      $result = $DB->query($query);
      while ($data=$DB->fetch_array($result)) {
         $this->displaySurvey($data['id']);
      }
   }
   
   
   
   
   function displaySurvey($questions_id) {
      global $CFG_GLPI;

      $psQuestion = new PluginSurveyticketQuestion();
      
      $psQuestion->getFromDB($questions_id);
      
      echo "<table class='tab_cadre' align='left' width='700' >";
      
      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='3'>";
      echo $psQuestion->fields['name'];
      echo "&nbsp;";
      Html::showToolTip($psQuestion->fields['comment']);
      echo "</th>";
      echo "</tr>";
      
      $nb_answer = $this->displayAnswers($questions_id);
      
      echo "</table>";
      
      $params=array("question".$questions_id=>'__VALUE__',
            'rand'=>$questions_id,
            'myname'=>"question".$questions_id);
      
      
      if ($psQuestion->fields['type'] == 'radio'
              OR $psQuestion->fields['type'] == 'yesno') {
         $a_ids = array();
         for ($i=0; $i< $nb_answer; $i++) {
            array_push($a_ids, 'question'.$questions_id."_".$i);
         }
      } else {
         $a_ids = 'question'.$questions_id;
      }
      self::UpdateItemOnSelectEvent($a_ids,
                                    "nextquestion".$questions_id,
                                    $CFG_GLPI["root_doc"]."/plugins/surveyticket/ajax/displaysurvey.php",
                                    $params);
      echo "<br/><div id='nextquestion".$questions_id."'></div>";
      
   }
   
   
   
   function displayAnswers($questions_id) {
      
      $psQuestion = new PluginSurveyticketQuestion();
      $psAnswer = new PluginSurveyticketAnswer();
      
      $a_answers = $psAnswer->find("`plugin_surveyticket_questions_id`='".$questions_id."'");
      
      $psQuestion->getFromDB($questions_id);
      switch ($psQuestion->fields['type']) {
         case 'dropdown':
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='2' align='center'>";
            echo "<select name='question".$questions_id."' id='question".$questions_id."' >";
            echo "<option>".Dropdown::EMPTY_VALUE."</option>";
            foreach ($a_answers as $data_answer) {               
               echo "<option value='".$data_answer['id']."'>".$psAnswer->getAnswer($data_answer)."</option>";
            }
            echo "</select>";
            echo "</td>";
            echo "</tr>";
            break;
            
         case 'checkbox':            
            $i = 0;
            foreach ($a_answers as $data_answer) {
               echo "<tr class='tab_bg_1'>";
               echo "<td width='40' align='center'>";
               echo "<input type='checkbox' name='question".$questions_id."[]' id='question".$questions_id."_".$i."' 
                  value='".$data_answer['id']."' />";
               echo "</td>";
               echo "<td>";
               echo $psAnswer->getAnswer($data_answer);
               echo "</td>";
               $this->displayAnswertype($data_answer['answertype'], "text-".$questions_id."-".$data_answer['id']);
               echo "</tr>";
               $i++;
            }
            break;
         
         case 'radio':
         case 'yesno':
            $i = 0;
            foreach ($a_answers as $data_answer) {
               echo "<tr class='tab_bg_1'>";
               echo "<td width='40' align='center'>";
               echo "<input type='radio' name='question".$questions_id."' id='question".$questions_id."_".$i."' 
                  value='".$data_answer['id']."' />";
               echo "</td>";
               echo "<td>";
               echo $psAnswer->getAnswer($data_answer);
               echo "</td>";
               $this->displayAnswertype($data_answer['answertype'], "text-".$questions_id."-".$data_answer['id']);
               echo "</tr>";
               $i++;
            }
            
            break;
      }
      return count($a_answers);
   }
   
   
   
   function displayAnswertype($type, $name) {

      echo "<td>";
      if ($type != '') {
         //echo "<tr class='tab_bg_1'>";
         switch ($type) {

            case 'shorttext':
               echo "<input type='text' name='".$name."' value='' size='71'/>";
               break;

            case 'longtext':
               echo "<textarea name='".$name."' cols='70'></textarea>";
               break;

            case 'date':
               Html::showDateFormItem($name, '', true);
               break;
            
            case 'number':
               
               break;

         }         
      }
      echo "</td>";
   }
   
   
   function displayOK() {
      global $LANG;

      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th align='center'>";
      echo "<input type='submit' class='submit' value='".$LANG['buttons'][2]."'/>";
      echo "</th>";
      echo "</tr>";

      echo "</table>";  
      Html::closeForm();
   }   
   
   
   
   static function updateItemOnSelectEvent($toobserve, $toupdate, $url, $parameters=array()) {

      self::updateItemOnEvent($toobserve, $toupdate, $url, $parameters, array("change"));
   }
   
   static function updateItemOnEvent($toobserve, $toupdate, $url, $parameters=array(),
                                      $events=array("change"), $minsize = -1, $forceloadfor=array()) {

      echo "<script type='text/javascript'>";
      self::updateItemOnEventJsCode($toobserve, $toupdate, $url, $parameters, $events, $minsize,
                                     $forceloadfor);
      echo "</script>";
   }
   
   static function updateItemOnEventJsCode($toobserve, $toupdate, $url, $parameters=array(),
                                           $events=array("change"), $minsize = -1,
                                           $forceloadfor=array()) {

      if (is_array($toobserve)) {
         $zones = $toobserve;
      } else {
         $zones = array($toobserve);
      }

      foreach ($zones as $zone) {
         foreach ($events as $event) {
            echo "
               Ext.get('$zone').on(
                '$event',
                function() {";
                  $condition = '';
                  if ($minsize >= 0) {
                     $condition = " Ext.get('$zone').getValue().length >= $minsize ";
                  }
                  if (count($forceloadfor)) {
                     foreach ($forceloadfor as $value) {
                        if (!empty($condition)) {
                           $condition .= " || ";
                        }
                        $condition .= "Ext.get('$zone').getValue() == '$value'";
                     }
                  }
                  if (!empty($condition)) {
                     echo "if ($condition) {";
                  }
                  Ajax::updateItemJsCode($toupdate, $url, $parameters, $zone);
                  if (!empty($condition)) {
                     echo "}";
                  }

          echo "});\n";
         }
      }
   }

   
   
   static function showFormHelpdesk($ID, $ticket_template=false) {
      
      $ticketdisplay = "";
      ob_start();
      Ticket::showFormHelpdesk($ID, $ticket_template);
      $ticketdisplay = ob_get_contents();
      ob_end_clean();
      
      $ticketdisplay = str_replace("/front/tracking.injector.php", 
              "/plugins/surveyticket/front/displaysurvey.form.php", $ticketdisplay);
      
      $split = explode("<td><textarea name='content' cols='80' rows='14'></textarea>", $ticketdisplay);
      
      echo $split[0];
      echo "<td height='120'>";
      $psSurvey = new PluginSurveyticketSurvey();
      $psSurvey->startSurvey(); 
      echo $split[1];
      
      
      
      
      
      
      
      return;
//      global $DB, $CFG_GLPI, $LANG;
//
//      if (!Session::haveRight("create_ticket","1")) {
//         return false;
//      }
//
//      if (Session::haveRight('validate_ticket',1)) {
//         $opt = array();
//         $opt['reset']         = 'reset';
//         $opt['field'][0]      = 55; // validation status
//         $opt['searchtype'][0] = 'equals';
//         $opt['contains'][0]   = 'waiting';
//         $opt['link'][0]       = 'AND';
//
//         $opt['field'][1]      = 59; // validation aprobator
//         $opt['searchtype'][1] = 'equals';
//         $opt['contains'][1]   = Session::getLoginUserID();
//         $opt['link'][1]       = 'AND';
//
//         $url_validate = $CFG_GLPI["root_doc"]."/front/ticket.php?".Toolbox::append_params($opt,
//                                                                                           '&amp;');
//
//         if (TicketValidation::getNumberTicketsToValidate(Session::getLoginUserID()) >0) {
//            echo "<a href='$url_validate' title=\"".$LANG['validation'][15]."\"
//                   alt=\"".$LANG['validation'][15]."\">".$LANG['validation'][33]."</a><br><br>";
//         }
//      }
//
//      $query = "SELECT `realname`, `firstname`, `name`
//                FROM `glpi_users`
//                WHERE `id` = '$ID'";
//      $result = $DB->query($query);
//
//
//      $email  = UserEmail::getDefaultForUser($ID);
//
//
//      // Set default values...
//      $values = array('_users_id_requester_notif' => array('use_notification'  => ($email==""?0:1),
//                                                           'alternative_email' => ''),
//                      'nodelegate'                => 1,
//                      '_users_id_requester'       => 0,
//                      'name'                      => '',
//                      'content'                   => '',
//                      'itilcategories_id'         => 0,
//                      'urgency'                   => 3,
//                      'itemtype'                  => '',
//                      'items_id'                  => 0,
//                      'plan'                      => array(),
//                      'global_validation'         => 'none',
//                      'due_date'                  => 'NULL',
//                      'slas_id'                   => 0,
//                      '_add_validation'           => 0,
//                      'type'                      => EntityData::getUsedConfig('tickettype',
//                                                                               $_SESSION['glpiactive_entity'],
//                                                                               '', Ticket::INCIDENT_TYPE),
//                      '_right'                    => "id");
//
//      // Restore saved value or override with page parameter
//      foreach ($values as $name => $value) {
//         if (!isset($options[$name])) {
//            if (isset($_SESSION["helpdeskSaved"][$name])) {
//               $options[$name] = $_SESSION["helpdeskSaved"][$name];
//            } else {
//               $options[$name] = $value;
//            }
//         }
//      }
//
//      if (!$ticket_template) {
//echo "<form method='post' name='helpdeskform' enctype='multipart/form-data'
//   action=\"".$CFG_GLPI['root_doc']."/plugins/surveyticket/front/survey.form.php\">";
////         echo "<form method='post' name='helpdeskform' action='".
////               $CFG_GLPI["root_doc"]."/front/tracking.injector.php' enctype='multipart/form-data'>";
//      }
//
//
//      $delegating = User::getDelegateGroupsForUser();
//
//      if (count($delegating)) {
//         echo "<div class='center'><table class='tab_cadre_fixe'>";
//         echo "<tr><th colspan='2'>".$LANG['job'][69]."&nbsp;:&nbsp;";
//
//         $rand   = Dropdown::showYesNo("nodelegate", $options['nodelegate']);
//
//         $params = array ('nodelegate' => '__VALUE__',
//                          'rand'       => $rand,
//                          'right'      => "delegate",
//                          '_users_id_requester'
//                                       => $options['_users_id_requester'],
//                          '_users_id_requester_notif'
//                                       => $options['_users_id_requester_notif'],
//                          'use_notification'
//                                       => $options['_users_id_requester_notif']['use_notification'],
//                          'entity_restrict'
//                                       => $_SESSION["glpiactive_entity"]);
//
//         Ajax::UpdateItemOnSelectEvent("dropdown_nodelegate".$rand, "show_result".$rand,
//                                       $CFG_GLPI["root_doc"]."/ajax/dropdownDelegationUsers.php",
//                                       $params);
//
//         echo "</th></tr>";
//         echo "<tr class='tab_bg_1'><td colspan='2' class='center'>";
//         echo "<div id='show_result$rand'>";
//
//         $self = new Ticket();
//         if ($options["_users_id_requester"] == 0) {
//            $options['_users_id_requester'] = Session::getLoginUserID();
//         } else {
//            $options['_right'] = "delegate";
//         }
//         $self->showActorAddFormOnCreate(Ticket::REQUESTER, $options);
//         echo "</div>";
//         echo "</td></tr>";
//
//         echo "</table>";
//         echo "<input type='hidden' name='_users_id_recipient' value='".Session::getLoginUserID()."'>";
//      }
//
//      echo "<input type='hidden' name='_from_helpdesk' value='1'>";
//      echo "<input type='hidden' name='requesttypes_id' value='".RequestType::getDefault('helpdesk').
//           "'>";
//
//
//      // Load ticket template if available :
//      $tt = new TicketTemplate();
//
//      // First load default entity one
//      if ($template_id = EntityData::getUsedConfig('tickettemplates_id', $_SESSION["glpiactive_entity"])) {
//         // with type and categ
//         $tt->getFromDBWithDatas($template_id, true);
//      }
//
//      $field = '';
//      if ($options['type'] && $options['itilcategories_id']) {
//         $categ = new ITILCategory();
//         if ($categ->getFromDB($options['itilcategories_id'])) {
//            switch ($options['type']) {
//               case Ticket::INCIDENT_TYPE :
//                  $field = 'tickettemplates_id_incident';
//                  break;
//
//               case Ticket::DEMAND_TYPE :
//                  $field = 'tickettemplates_id_demand';
//                  break;
//            }
//
//            if (!empty($field) && $categ->fields[$field]) {
//               // without type and categ
//               $tt->getFromDBWithDatas($categ->fields[$field], false);
//            }
//         }
//      }
//
//      if ($ticket_template) {
//         // with type and categ
//         $tt->getFromDBWithDatas($ticket_template, true);
//      }
//
//      // Predefined fields from template : reset them
//      if (isset($options['_predefined_fields'])) {
//         $options['_predefined_fields']
//                     = unserialize(rawurldecode(stripslashes($options['_predefined_fields'])));
//      } else {
//         $options['_predefined_fields'] = array();
//      }
//
//      // Store predefined fields to be able not to take into account on change template
//      $predefined_fields = array();
//
//      if (isset($tt->predefined) && count($tt->predefined)) {
//         foreach ($tt->predefined as $predeffield => $predefvalue) {
//            if (isset($options[$predeffield])) {
//               // Is always default value : not set
//               // Set if already predefined field
//               if ($options[$predeffield] == $values[$predeffield]
//                   || (isset($options['_predefined_fields'][$field])
//                       && $options[$predeffield] == $options['_predefined_fields'][$field])) {
//                  $options[$predeffield]           = $predefvalue;
//                  $predefined_fields[$predeffield] = $predefvalue;
//               }
//            } else { // Not defined options set as hidden field
//               echo "<input type='hidden' name='$predeffield' value='$predefvalue'>";
//            }
//         }
//
//      } else { // No template load : reset predefined values
//         if (count($options['_predefined_fields'])) {
//            foreach ($options['_predefined_fields'] as $predeffield => $predefvalue) {
//               if ($options[$predeffield] == $predefvalue) {
//                  $options[$predeffield] = $values[$predeffield];
//               }
//            }
//         }
//      }
//
//      unset($_SESSION["helpdeskSaved"]);
//
//      if ($CFG_GLPI['urgency_mask']==(1<<3) || $tt->isHiddenField('urgency')) {
//         // Dont show dropdown if only 1 value enabled or field is hidden
//         echo "<input type='hidden' name='urgency' value='".$options['urgency']."'>";
//      }
//
//      // Display predefined fields if hidden
//      if ($tt->isHiddenField('itemtype')) {
//         echo "<input type='hidden' name='itemtype' value='".$options['itemtype']."'>";
//         echo "<input type='hidden' name='items_id' value='".$options['items_id']."'>";
//      }
//
//      echo "<input type='hidden' name='entities_id' value='".$_SESSION["glpiactive_entity"]."'>";
//      echo "<div class='center'><table class='tab_cadre_fixe'>";
//
//      echo "<tr><th colspan='2'>".$LANG['job'][11]."&nbsp;:&nbsp;";
//      if (Session::isMultiEntitiesMode()) {
//         echo "&nbsp;(".Dropdown::getDropdownName("glpi_entities", $_SESSION["glpiactive_entity"]).")";
//      }
//      echo "</th></tr>";
//
//      echo "<tr class='tab_bg_1'>";
//      echo "<td>".$LANG['common'][17]."&nbsp;:".$tt->getMandatoryMark('type')."</td>";
//      echo "<td>";
//      Ticket::dropdownType('type', array('value'     => $options['type'],
//                                       'on_change' => 'submit()'));
//      echo "</td></tr>";
//
//      echo "<tr class='tab_bg_1'>";
//      echo "<td>".$LANG['common'][36]."&nbsp;:";
//      echo $tt->getMandatoryMark('itilcategories_id');
//      echo "</td><td>";
//
//      $condition = "`is_helpdeskvisible`='1'";
//      switch ($options['type']) {
//         case Ticket::DEMAND_TYPE :
//            $condition .= " AND `is_request`='1'";
//            break;
//
//         default: // Ticket::INCIDENT_TYPE :
//            $condition .= " AND `is_incident`='1'";
//      }
//      $opt = array('value'     => $options['itilcategories_id'],
//                                           'condition' => $condition,
//                                           'on_change' => 'submit()');
//      if ($options['itilcategories_id'] && $tt->isMandatoryField("itilcategories_id")) {
//         $opt['display_emptychoice'] = false;
//      }
//
//      Dropdown::show('ITILCategory', $opt);
//      echo "</td></tr>";
//
//
//      if ($CFG_GLPI['urgency_mask']!=(1<<3)) {
//         if (!$tt->isHiddenField('urgency')) {
//            echo "<tr class='tab_bg_1'>";
//            echo "<td>".$LANG['joblist'][29]."&nbsp;:".$tt->getMandatoryMark('urgency')."</td>";
//            echo "<td>";
//            Ticket::dropdownUrgency("urgency", $options['urgency']);
//            echo "</td></tr>";
//         }
//      }
//
//      if (empty($delegating) && NotificationTargetTicket::isAuthorMailingActivatedForHelpdesk()) {
//         echo "<tr class='tab_bg_1'>";
//         echo "<td>".$LANG['help'][8]."&nbsp;:&nbsp;</td>";
//         echo "<td>";
//         if ($options["_users_id_requester"] == 0) {
//            $options['_users_id_requester'] = Session::getLoginUserID();
//         }
//         $_REQUEST['value']            = $options['_users_id_requester'];
//         $_REQUEST['field']            = '_users_id_requester_notif';
//         $_REQUEST['use_notification'] = $options['_users_id_requester_notif']['use_notification'];
//         include (GLPI_ROOT."/ajax/uemailUpdate.php");
//
//         echo "</td></tr>";
//      }
//
//      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] != 0) {
//         if (!$tt->isHiddenField('itemtype')) {
//            echo "<tr class='tab_bg_1'>";
//            echo "<td>".$LANG['help'][24]."&nbsp;: ".$tt->getMandatoryMark('itemtype')."</td>";
//            echo "<td>";
//            Ticket::dropdownMyDevices($options['_users_id_requester'], $_SESSION["glpiactive_entity"],
//                                    $options['itemtype'], $options['items_id']);
//            Ticket::dropdownAllDevices("itemtype", $options['itemtype'], $options['items_id'], 0, $options['_users_id_requester'],
//                                     $_SESSION["glpiactive_entity"]);
//            echo "<span id='item_ticket_selection_information'></span>";
//
//            echo "</td></tr>";
//         }
//      }
//
//      if (!$tt->isHiddenField('name')
//          || $tt->isPredefinedField('name')) {
//         echo "<tr class='tab_bg_1'>";
//         echo "<td>".$LANG['common'][57]."&nbsp;:".
//                     $tt->getMandatoryMark('name')."</td>";
//         echo "<td><input type='text' maxlength='250' size='80' name='name'
//                          value=\"".$options['name']."\"></td></tr>";
//      }
//
//      if (!$tt->isHiddenField('content')
//          || $tt->isPredefinedField('content')) {
//         echo "<tr class='tab_bg_1'>";
//         echo "<td>".$LANG['joblist'][6]."&nbsp;:".
//                     $tt->getMandatoryMark('content')."</td>";
//echo "<td height='120'>";
//$psSurvey = new PluginSurveyticketSurvey();
//$psSurvey->startSurvey();         
////         echo "<td><textarea name='content' cols='80' rows='14'>".$options['content']."</textarea>";
//         echo "</td></tr>";
//      }
//
//      echo "<tr class='tab_bg_1'>";
//      echo "<td>".$LANG['document'][2]." (".Document::getMaxUploadSize().")&nbsp;:&nbsp;";
//      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/aide.png' class='pointer' alt='".
//             $LANG['central'][7]."' onclick=\"window.open('".$CFG_GLPI["root_doc"].
//             "/front/documenttype.list.php','Help','scrollbars=1,resizable=1,width=1000,height=800')\">";
//
//      echo "&nbsp;";
//      Ticket::showDocumentAddButton(60);
//
//      echo "</td>";
//      echo "<td><div id='uploadfiles'><input type='file' name='filename[]' value='' size='60'></div>";
//
//      echo "</td></tr>";
//
//      if (!$ticket_template) {
//         echo "<tr class='tab_bg_1'>";
//         echo "<td colspan='2' class='center'>";
//         echo "<input type='submit' name='add' value=\"".$LANG['help'][14]."\" class='submit'>";
//
//         if ($tt->isField('id') && $tt->fields['id'] > 0) {
//            echo "<input type='hidden' name='_tickettemplates_id' value='".$tt->fields['id']."'>";
//            echo "<input type='hidden' name='_predefined_fields'
//                         value=\"".rawurlencode(serialize($predefined_fields))."\">";
//         }
//
//         echo "</td></tr>";
//      }
//
//      echo "</table></div>";
//      if (!$ticket_template) {
//         Html::closeForm();
//      }
   }

   
}

?>