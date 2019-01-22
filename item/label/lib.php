<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') OR die('not allowed');
require_once($CFG->dirroot.'/mod/apply/item/apply_item_class.php');
require_once($CFG->libdir.'/formslib.php');


class apply_item_label extends apply_item_base
{
    protected $type = "label";
    private $presentationoptions = null;
    private $commonparams;
    private $item_form;
    private $context;
    private $item;


    public function init()
    {
        global $CFG;
        $this->presentationoptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext'=>true);
    }


    public function build_editform($item, $apply, $cm)
    {
        global $DB, $CFG;

        require_once('label_form.php');

        //get the lastposition number of the apply_items
        $position = $item->position;
        $lastposition = $DB->count_records('apply_item', array('apply_id'=>$apply->id));
        if ($position == -1) {
            $i_formselect_last  = $lastposition + 1;
            $i_formselect_value = $lastposition + 1;
            $item->position = $lastposition + 1;
        }
        else {
            $i_formselect_last  = $lastposition;
            $i_formselect_value = $item->position;
        }
        //the elements for position dropdownlist
        $positionlist = array_slice(range(0, $i_formselect_last), 1, $i_formselect_last, true);

        //all items for dependitem
        $applyitems = apply_get_depend_candidates_for_item($apply, $item);
        $commonparams = array('cmid'=>$cm->id,
                             'id'=>isset($item->id) ? $item->id : null,
                             'typ'=>$item->typ,
                             'items'=>$applyitems,
                             'apply_id'=>$apply->id);
        $this->context = context_module::instance($cm->id);

        //preparing the editor for new file-api
        $item->presentationformat = FORMAT_HTML;
        $item->presentationtrust = 1;

        // Append editor context to presentation options, giving preference to existing context.
        $this->presentationoptions = array_merge(array('context' => $this->context),
                                                 $this->presentationoptions);
        $item = file_prepare_standard_editor($item,
                                            'presentation', //name of the form element
                                            $this->presentationoptions,
                                            $this->context,
                                            'mod_apply',
                                            'item', //the filearea
                                            $item->id);
        //build the form
        $customdata = array('item' => $item,
                            'common' => $commonparams,
                            'positionlist' => $positionlist,
                            'position' => $position,
                            'presentationoptions' => $this->presentationoptions);
        $this->item_form = new apply_label_form('edit_item.php', $customdata);
    }


    //this function only can used after the call of build_editform()
    public function show_editform()
    {
        $this->item_form->display();
    }


    public function is_cancelled()
    {
        return $this->item_form->is_cancelled();
    }


    public function get_data()
    {
        if ($this->item = $this->item_form->get_data()) {
            return true;
        }
        return false;
    }


    public function save_item()
    {
        global $DB;

        if (!$item = $this->item_form->get_data()) {
            return false;
        }

        if (isset($item->clone_item) AND $item->clone_item) {
            $item->id = ''; //to clone this item
            $item->position++;
        }

        $item->presentation = '';

        $item->hasvalue = $this->get_hasvalue();
        if (!$item->id) {
            $item->id = $DB->insert_record('apply_item', $item);
        }
        else {
            $DB->update_record('apply_item', $item);
        }

        $item = file_postupdate_standard_editor($item,
                                                'presentation',
                                                $this->presentationoptions,
                                                $this->context,
                                                'mod_apply',
                                                'item',
                                                $item->id);

        $DB->update_record('apply_item', $item);

        return $DB->get_record('apply_item', array('id'=>$item->id));
    }


    public function print_item($item)
    {
        global $DB, $CFG;

        require_once($CFG->libdir . '/filelib.php');

        //is the item a template?
        if (!$item->apply_id AND $item->template) {
            $template = $DB->get_record('apply_template', array('id'=>$item->template));
            if ($template->ispublic) {
                $context = get_system_context();
            }
            else {
                $context = context_course::instance($template->course);
            }
            $filearea = 'template';
        }
        else {
            $cm = get_coursemodule_from_instance('apply', $item->apply_id);
            $context = context_module::instance($cm->id);
            $filearea = 'item';
        }

        $item->presentationformat = FORMAT_HTML;
        $item->presentationtrust = 1;

        $output = file_rewrite_pluginfile_urls($item->presentation,
                                               'pluginfile.php',
                                               $context->id,
                                               'mod_apply',
                                               $filearea,
                                               $item->id);

        $formatoptions = array('overflowdiv'=>true, 'trusted'=>$CFG->enabletrusttext);
        echo format_text($output, FORMAT_HTML, $formatoptions);
    }


    /**
     * print the item at the edit-page of apply
     *
     * @global object
     * @param object $item
     * @return void
     */
    public function print_item_preview($item)
    {
        global $OUTPUT, $DB;

        $align = right_to_left() ? 'right' : 'left';

        $item->outside_style = '';
        $item->item_style = '';

        $output  = '';
        $output .= '<div class="apply_item_label_'.$align.'">';
        $output .= '('.$item->label.') ';
        $output .= format_text($item->name, true, false, false).' ['.$item->position.']';
        if ($item->dependitem) {
            if ($dependitem = $DB->get_record('apply_item', array('id'=>$item->dependitem))) {
                $output .= ' <span class="apply_depend">';
                $output .= '('.$dependitem->label.'-&gt;'.$item->dependvalue.')';
                $output .= '</span>';
            }
        }
        $output .= '</div>';

        apply_open_table_item_tag($output, true);
        echo $OUTPUT->box_start('generalbox boxalign'.$align);
        apply_item_box_start($item);
        $this->print_item($item);
        apply_item_box_end();
        echo $OUTPUT->box_end();
        apply_close_table_item_tag();
    }


    /**
     * print the item at the complete-page of apply
     *
     * @global object
     * @param object $item
     * @param string $value
     * @param bool $highlightrequire
     * @return void
     */
    public function print_item_submit($item, $value = '', $highlightrequire = false)
    {
        global $OUTPUT;

        $item->outside_style = '';
        $item->item_style = '';

        $align  = right_to_left() ? 'right' : 'left';
        $output = format_text($item->name, true, false, false);

        apply_open_table_item_tag($output);
        echo $OUTPUT->box_start('generalbox boxalign'.$align);
        apply_item_box_start($item);
        $this->print_item($item);
        apply_item_box_end();
        echo $OUTPUT->box_end();
        apply_close_table_item_tag();
    }


    /**
     * print the item at the complete-page of apply
     *
     * @global object
     * @param object $item
     * @param string $value
     * @return void
     */
    public function print_item_show_value($item, $value = '')
    {
        global $OUTPUT;

        $item->outside_style = '';
        $item->item_style = '';

        $align  = right_to_left() ? 'right' : 'left';
        $output = format_text($item->name, true, false, false);

        apply_open_table_item_tag($output);
        echo $OUTPUT->box_start('generalbox boxalign'.$align);
        apply_item_box_start($item);
        $this->print_item($item);
        apply_item_box_end();
        echo $OUTPUT->box_end();
        apply_close_table_item_tag();
    }


    public function create_value($data) 
    {
        return false;
    }


    public function compare_value($item, $dbvalue, $dependvalue)
    {
        return false;
    }


    public function get_presentation($data)
    {
    }

   
     public function postupdate($item) 
     {
        global $DB;

        $context = context_module::instance($item->cmid);
        $item = file_postupdate_standard_editor($item,
                                                'presentation',
                                                $this->presentationoptions,
                                                $context,
                                                'mod_apply',
                                                'item',
                                                $item->id);
        $DB->update_record('apply_item', $item);
        return $item->id;
    }


    public function get_hasvalue()
    {
        return 0;
    }


    public function can_switch_require()
    {
        return false;
    }


    public function check_value($value, $item)
    {
    }


    public function excelprint_item(&$worksheet,
                             $row_offset,
                             $xls_formats,
                             $item,
                             $groupid,
                             $courseid = false)
    {
    }


    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false)
    {
    }
   

    public function get_printval($item, $value)
    {
    }


    public function get_analysed($item, $groupid = false, $courseid = false)
    {
    }


    public function value_type()
    {
        return PARAM_BOOL;
    }


    public function clean_input_value($value)
    {
        return '';
    }
}
