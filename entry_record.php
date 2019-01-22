<?php

// need $req_own_data, $submit, $data, $name_pattern, $courseid, ...

require_once('jbxl/jbxl_moodle_tools.php');

//
if (!$req_own_data or $submit->user_id!=$USER->id) {
    require_capability('mod/apply:viewreports', $context);
}


$student = apply_get_user_info($submit->user_id);
if ($student) {
    //$user_name = jbxl_get_user_name($student, $name_pattern);
    //
    $user_url  = $CFG->wwwroot.'/user/view.php?id='.$student->id.'&amp;course='.$courseid;
    $acked_url = $CFG->wwwroot.'/user/view.php?id='.$submit->acked_user.'&amp;course='.$courseid;
    $execd_url = $CFG->wwwroot.'/user/view.php?id='.$submit->execd_user.'&amp;course='.$courseid;

    ///////////////////////////////////////
    //
    if (!$req_own_data) {
        $data[] = $OUTPUT->user_picture($student, array('courseid'=>$courseid, 'size'=>20));
        if ($name_pattern=='firstname') {
            $data[] = '<strong><a href="'.$user_url.'">'.$student->firstname.'</a></strong>';
        }
        else if ($name_pattern=='lasttname') {
            $data[] = '<strong><a href="'.$user_url.'">'.$student->lastname.'</a></strong>';
        }
        else if ($name_pattern=='firstlastname') {
            $data[] = '<strong><a href="'.$user_url.'">'.$student->firstname.'</a></strong>';
            $data[] = '<strong><a href="'.$user_url.'">'.$student->lastname.' </a></strong>';
        }
        else if ($name_pattern=='lastfirstname') {
            $data[] = '<strong><a href="'.$user_url.'">'.$student->lastname. '</a></strong>';
            $data[] = '<strong><a href="'.$user_url.'">'.$student->firstname.'</a></strong>';
        }
        else {
            $user_name =jbxl_get_user_name($student, 'fullname');
            $data[] = '<strong><a href="'.$user_url.'">'.$user_name.'</a></strong>';
        }
    }
    //
    $title = $submit->title;
    if ($title=='') $title = get_string('no_title', 'apply');
    $entry_params = array('user_id'=>$student->id, 'submit_id'=>$submit->id, 'submit_ver'=>$submit->version, 'do_show'=>'view_one_entry');
    $entry_url = new moodle_url($this_url, $entry_params);
    //$data[] = '<strong><a href="'.$entry_url->out().'" target="_blank">'.$title.'</a></strong>';
    $data[] = '<strong><a href="'.$entry_url->out().'">'.$title.'</a></strong>';
    //
    if ($apply->date_format=='') $apply->date_format = get_string('date_format_default', 'apply');
    //$data[] = userdate($submit->time_modified, $apply->date_format_default);
    $data[] = userdate($submit->time_modified, '%Y/%m/%d %H:%M');
    //
    $data[] = $submit->version;

    //
    if      ($submit->class==APPLY_CLASS_DRAFT)  $class = get_string('class_draft',   'apply');
    else if ($submit->class==APPLY_CLASS_NEW)    $class = get_string('class_newpost', 'apply');
    else if ($submit->class==APPLY_CLASS_UPDATE) $class = get_string('class_update',  'apply');
    else if ($submit->class==APPLY_CLASS_CANCEL) $class = get_string('class_cancel',  'apply');
    if ($submit->class==APPLY_CLASS_DRAFT || $submit->class==APPLY_CLASS_CANCEL)  $class = '<strong>'.$class.'</strong>';
    $data[] = $class;

    //
    if ($req_own_data) {
        if ($submit->version>0 and apply_exist_draft_values($submit->id)) {
            $draft_params = array('user_id'=>$student->id, 'submit_id'=>$submit->id, 'submit_ver'=>0, 'do_show'=>'view_one_entry');
            $draft_url = new moodle_url($this_url, $draft_params);
            //$data[] = '<strong><a href="'.$draft_url->out().'" target="_blank">'.get_string('exist', 'apply').'</a></strong>';
            $data[] = '<strong><a href="'.$draft_url->out().'">'.get_string('exist', 'apply').'</a></strong>';
        }
        else {
            $data[] = '-';
        }
    }

    //
    if      ($submit->class==APPLY_CLASS_DRAFT)  $acked = '-';
    else if ($submit->acked==APPLY_ACKED_NOTYET) $acked = get_string('acked_notyet',  'apply');
    else if ($submit->acked==APPLY_ACKED_ACCEPT) $acked = get_string('acked_accept',  'apply');
    else if ($submit->acked==APPLY_ACKED_REJECT) $acked = get_string('acked_reject',  'apply');
    if ($submit->acked!=APPLY_ACKED_NOTYET) {
        //$acked = '<strong><a href="'.$acked_url.'" target="_blank">'.$acked.'</a></strong>';
        $acked = '<strong><a href="'.$acked_url.'">'.$acked.'</a></strong>';
    }
    $data[] = $acked;

    //
    if (!$apply->only_acked_accept) {
        //
        if      ($submit->class==APPLY_CLASS_DRAFT) $execd = '-';
        else if ($submit->execd==APPLY_EXECD_DONE)  $execd = get_string('execd_done',   'apply');
        else                                        $execd = get_string('execd_notyet', 'apply');
        if ($submit->execd!=APPLY_EXECD_NOTYET) {
            //$execd = '<strong><a href="'.$execd_url.'" target="_blank">'.$execd.'</a></strong>';
            $execd = '<strong><a href="'.$execd_url.'">'.$execd.'</a></strong>';
        }
        $data[] = $execd;
    }

    //
    //
    if ($submit->version>1) {
        $prev_ver = $submit->version - 1;
        //$form = '<form action="'.$base_url->out().'" method="POST" target="_blank">';
        $form = '<form action="'.$base_url->out().'" method="POST">';
        $form.= '<select name="submit_ver">';
        for ($i=1; $i<$prev_ver; $i++) {
            $form.= '<option value="'.$i.'">'.$i.'</option>';
        }
        $form.= '<option value="'.$prev_ver.'" selected="selected">'.$prev_ver.'</option>';
        $form.= '</select>&nbsp;';
        $form.= '<input type="hidden" name="do_show"  value="view_one_entry" />';
        $form.= '<input type="hidden" name="submit_id" value="'.$submit->id.'" />';
        $form.= '<input type="hidden" name="user_id" value="'.$submit->user_id.'" />';
        $form.= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $form.= '<input name="before_apply" type="submit" value="'.get_string('display_button', 'apply').'" />';
        $form.= '</form>';
    }
    else {
        $form = '-';
    }
    $data[] = $form;

    //$req_own_data = FALSE if you're on the Show Entries tab; TRUE if you're on the Overview and Submit tab.
    if ($req_own_data) {
        if ($submit->class==APPLY_CLASS_CANCEL and $submit->acked==APPLY_ACKED_ACCEPT) {
            // 解除が受理されたものは，ユーザは変更できない  LUIS translation: Those for which cancellation has been accepted can not be changed by the user
            $data[] = '-';
            $data[] = '-';
        }
        else {
            if ($submit->acked==APPLY_ACKED_ACCEPT) {
                // Update
                $change_label   = get_string('update_entry_button', 'apply');
                $change_params  = array('id'=>$id, 'submit_id'=>$submit->id, 'submit_ver'=>$submit->version, 'courseid'=>$courseid, 'go_page'=>0);
                $change_action  = 'submit.php';
                // Cancel
                if ($apply->can_discard) {
                    $discard_label  = get_string('cancel_entry_button', 'apply');
                    $discard_params = array('id'=>$id, 'submit_id'=>$submit->id);
                    $discard_action = 'delete_submit.php';
                }
            }
            else {
                // Edit
                $change_label  = get_string('edit_entry_button', 'apply');
                $change_params = array('id'=>$id, 'submit_id'=>$submit->id, 'submit_ver'=>$submit->version, 'courseid'=>$courseid, 'go_page'=>0);
                $change_action = 'submit.php';

                if ($submit->version<=1 and $apply->can_discard) {
                    // Delete
                    $discard_label  = get_string('delete_entry_button', 'apply');
                    $discard_params = array('id'=>$id, 'submit_id'=>$submit->id);
                    $discard_action = 'delete_submit.php';
                }
                else {
                    // Rollback
                    $discard_label  = get_string('rollback_entry_button', 'apply');
                    $discard_params = array('id'=>$id, 'submit_id'=>$submit->id);
                    $discard_action = 'delete_submit.php';
                }
            }

            //
            if ($submit->class==APPLY_CLASS_CANCEL or $apply_is_closed) {
                // 解除を申請している場合は，内容を編集・更新できない  Translation: If you are cancelling the application form you cannot edit and update the contents
                $data[] = '-';
            }
            else {
                $data[] = apply_single_button($CFG->wwwroot.'/mod/apply/'.$change_action, $change_params, $change_label);
            }
            if ($apply_is_closed) {
                $data[] = '-';
            }
            else {
                if ($apply->can_discard) {
                    $data[] = apply_single_button($CFG->wwwroot.'/mod/apply/'.$discard_action, $discard_params, $discard_label);
                }
            }
        }
    }

    // for admin
    else {
        $operate_params = array('id'=>$id, 'submit_id'=>$submit->id, 'submit_ver'=>$submit->version, 'courseid'=>$courseid);
        $operate_url = $CFG->wwwroot.'/mod/apply/operate_submit.php';
        //$data[] = apply_single_button($operate_url, $operate_params, get_string('operate_submit', 'apply'), 'POST', '_blank');
        $data[] = apply_single_button($operate_url, $operate_params, get_string('operate_submit', 'apply'), 'POST');

        if (has_capability('mod/apply:editsubmissions', $context)) {
            $change_label           = get_string('edit_entry_button', 'apply');
            $change_params          =
                    array('id'      => $id, 'submit_id' => $submit->id, 'submit_ver' => $submit->version, 'courseid' => $courseid,
                          'go_page' => 0);
            $change_action          = 'submit.php';
            $data[count($data) - 1] .= apply_single_button($CFG->wwwroot . '/mod/apply/' . $change_action, $change_params,
                    $change_label);
        }

        if ($apply->enable_deletemode) {
            //$form = '<form action=delete_submit.php method="POST" target="_blank">';
            $form = '<form action=delete_submit.php method="POST">';
            $form.= '<input type="hidden" name="action" value="delete_submit" />';
            $form.= '<input type="hidden" name="id" value="'.$id.'" />';
            $form.= '<input type="hidden" name="submit_id" value="'.$submit->id.'" />';
            $form.= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            $form.= '<input name="delete_submit" type="submit" value="'.get_string('delete').'" />';
            $form.= '</form>';
            $data[] = $form;
        }
    }
}

