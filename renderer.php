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

/**
 * Renderer for outputting the default course format.
 *
 * @package format_default
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');

/**
 * Basic renderer for default format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_default_renderer extends format_section_renderer_base {

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Since format_default_renderer::section_edit_controls() only displays the 'Set current section' control when editing mode is on
        // we need to be sure that the link 'Turn editing mode on' is available for a user who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return '';
        return html_writer::start_tag('ul', array('class' => 'default'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return '';
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section));
    }

    /**
     * Generate the section title to be displayed on the section page, without a link
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
    }

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        global $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();
        if ($section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $markedthistopic = get_string('markedthistopic');
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                                               'name' => $highlightoff,
                                               'pixattr' => array('class' => '', 'alt' => $markedthistopic),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markedthistopic,
                                                   'data-action' => 'removemarker'));
            } else {
                $url->param('marker', $section->section);
                $markthistopic = get_string('markthistopic');
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                                               'name' => $highlight,
                                               'pixattr' => array('class' => '', 'alt' => $markthistopic),
                                               'attr' => array('class' => 'editing_highlight', 'title' => $markthistopic,
                                                   'data-action' => 'setmarker'));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $template = new stdClass();

        $template->editing = $this->page->user_is_editing();
        $template->editsettingsurl = new moodle_url('/course/edit.php', ['id' => $course->id]);
        $template->enrolusersurl = new moodle_url('/user/index.php', ['id' => $course->id]);
        $template->incourse = true;

        if ($PAGE->user_is_editing()) {
            $template->editoff = new moodle_url($PAGE->url, ['sesskey' => sesskey(), 'edit' => 'off']);
        } else {
            $template->editon = new moodle_url($PAGE->url, ['sesskey' => sesskey(), 'edit' => 'on']);
        }

        $template->sections = [];

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        // echo $completioninfo->display_help_icon();

        $template->completioninfo = $completioninfo->display_help_icon();

        // echo $this->output->heading($this->page_title(), 2, 'accesshide');

        $template->heading = $this->output->heading($this->page_title(), 2, 'accesshide');
        // Copy activity clipboard..
        // echo $this->course_activity_clipboard($course, 0);

        $template->courseactivityclipboard = $this->course_activity_clipboard($course, 0);
        // Now the list of sections..
        // echo $this->start_section_list();

        $template->startsectionlist = $this->start_section_list();
        $numsections = course_get_format($course)->get_last_section_number();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {

            $sectiontemp = new stdClass();

            $sectiontemp->sectionid = $section;
            
            if ($section == 0) {
                // 0-section is displayed a little different then the others
                if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {

                    // echo $this->section_header($thissection, $course, false, 0);

                    $sectiontemp->header = $this->section_header($thissection, $course, false, 0);

                    // echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);

                    $sectiontemp->coursemodules = $this->course_section_cm_list($course, $thissection, 0);

                    // echo $this->courserenderer->course_section_add_cm_control($course, 0, 0);

                    $sectiontemp->cmcontrol = $this->courserenderer->course_section_add_cm_control($course, 0, 0);

                    // echo $this->section_footer();

                    $sectiontemp->sectionfooter = $this->section_footer();

                    $template->sections[] = $sectiontemp;
                }
                continue;
            }
            if ($section > $numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available &&
                    !empty($thissection->availableinfo));
            if (!$showsection) {
                // If the hiddensections option is set to 'show hidden sections in collapsed
                // form', then display the hidden section message - UNLESS the section is
                // hidden by the availability system, which is set to hide the reason.
                if (!$course->hiddensections && $thissection->available) {
                    // echo $this->section_hidden($section, $course->id);

                    $sectiontemp->hidden = $this->section_hidden($section, $course->id);
                    $template->sections[] = $sectiontemp;
                }

                continue;
            }

            if (!$PAGE->user_is_editing() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                // Display section summary only.

                // echo $this->section_summary($thissection, $course, null);

                $sectiontemp->summary = $this->section_summary($thissection, $course, null);

            } else {
                // Create new renderer for this is what the user sees.
                // echo $this->section_header($thissection, $course, false, 0);

                $sectiontemp->header = $this->section_header($thissection, $course, false, 0);

                if ($thissection->uservisible) {
                    // echo $this->course_section_cm_list($course, $thissection, 0);

                    $sectiontemp->coursemodules = $this->course_section_cm_list($course, $thissection, 0);

                    // echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);

                    $sectiontemp->cmcontrol = $this->courserenderer->course_section_add_cm_control($course, $section, 0);

                }
                // echo $this->section_footer();

                //$sectiontemp->footer = $this->section_footer();
            }
            $template->sections[] = $sectiontemp;
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
                    continue;
                }
                $sectiontemp = new stdClass();

                // echo $this->stealth_section_header($section);

                $sectiontemp->header = $this->stealth_section_header($section);

                // echo $this->course_section_cm_list($course, $thissection, 0);

                $sectiontemp->coursemodules = $this->course_section_cm_list($course, $thissection, 0);

                // echo $this->stealth_section_footer();

                //$sectiontemp->footer = $this->section_footer();

                $template->sections[] = $sectiontemp;
            }


            // echo $this->end_section_list();

            $template->endsectionlist = $this->end_section_list();

            // echo $this->change_number_sections($course, 0);

            $template->changenumber = $this->change_number_sections($course, 0);

        } else {
            // echo $this->end_section_list();

            $template->endsectionlist = $this->end_section_list();
        }

        echo $this->render_from_template('format_default/multisectionpage', $template);
    }

    public function course_section_cm_list($course, $section, $sectionreturn = null, $displayoptions = array()) {
        global $USER;

        $output = '';
        $modinfo = get_fast_modinfo($course);
        if (is_object($section)) {
            $section = $modinfo->get_section_info($section->section);
        } else {
            $section = $modinfo->get_section_info($section);
        }
        $completioninfo = new completion_info($course);

        // check if we are currently in the process of moving a module with JavaScript disabled
        $ismoving = $this->page->user_is_editing() && ismoving($course->id);
        if ($ismoving) {
            $movingpix = new pix_icon('movehere', get_string('movehere'), 'moodle', array('class' => 'movetarget'));
            $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }

        // Get the list of modules visible to user (excluding the module being moved if there is one)
        $moduleshtml = array();
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if ($ismoving and $mod->id == $USER->activitycopy) {
                    // do not display moving mod
                    continue;
                }

                if ($modulehtml = $this->course_section_cm_list_item($course,
                        $completioninfo, $mod, $sectionreturn, $displayoptions)) {
                    $moduleshtml[$modnumber] = $modulehtml;
                }
            }
        }

        $sectionoutput = '';
        if (!empty($moduleshtml) || $ismoving) {
            foreach ($moduleshtml as $modnumber => $modulehtml) {
                if ($ismoving) {
                    $movingurl = new moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey()));
                    $sectionoutput .= html_writer::tag('li',
                            html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                            array('class' => 'movehere'));
                }

                $sectionoutput .= $modulehtml;
            }

            if ($ismoving) {
                $movingurl = new moodle_url('/course/mod.php', array('movetosection' => $section->id, 'sesskey' => sesskey()));
                $sectionoutput .= html_writer::tag('li',
                        html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                        array('class' => 'movehere'));
            }
        }

        // Always output the section module list.
        // $output .= html_writer::tag('ul', $sectionoutput, array('class' => 'section img-text'));

        $template = new stdClass;
        $template->section = $sectionoutput;
        return $this->render_from_template('format_default/listview', $template);
    }

        /**
     * Renders HTML to display one course module for display within a section.
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return String
     */
    public function course_section_cm_list_item($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        $output = '';
        $modulehtml = '';
        if ($modulehtml = $this->course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions)) {
            $modclasses = 'activity ' . $mod->modname . ' modtype_' . $mod->modname . ' ' . $mod->extraclasses;
            $output .= html_writer::tag('li', $modulehtml, array('class' => $modclasses, 'id' => 'module-' . $mod->id));

            $template = new stdClass();
            $template->mod = $mod;
            $template->listitem = $modulehtml;
            return $this->render_from_template('format_default/listitem', $template);
        }

        return $output;
    }

        /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $PAGE;

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        $header = new stdClass();

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';

                $header->style = ' hidden';
            }
            if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';

                $header->style = ' hidden';
            }
        }

        // $o.= html_writer::start_tag('li', array('id' => 'section-'.$section->section,
        //     'class' => 'section main clearfix'.$sectionstyle, 'role'=>'region',
        //     'aria-label'=> get_section_name($course, $section)));

        $header->section = $section;
        $header->name = get_section_name($course, $section);

        // Create a span that contains the section title to be used to create the keyboard section move menu.
        // $o .= html_writer::tag('span', get_section_name($course, $section), array('class' => 'hidden sectionname'));

        // $leftcontent = $this->section_left_content($section, $course, $onsectionpage);

        // $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $header->leftcontent = $this->section_left_content($section, $course, $onsectionpage);

        // $rightcontent = $this->section_right_content($section, $course, $onsectionpage);

        $header->rightcontent = $this->section_right_content($section, $course, $onsectionpage);

        // $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        // $o.= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';

        $header->classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
            $header->classes = '';
        }
        // $sectionname = html_writer::tag('span', $this->section_title($section, $course));

        $header->title  = $this->section_title_without_link($section, $course);
        // $o.= $this->output->heading($sectionname, 3, 'sectionname' . $classes);

        // $o .= $this->section_availability($section);
        $header->availability = $this->section_availability($section);

        // $o .= html_writer::start_tag('div', array('class' => 'summary'));
        // $o .= $this->format_summary_text($section);
        $header->summary = $this->format_summary_text($section);
        // $o .= html_writer::end_tag('div');

        //return $o;
        return $header;
    }

        /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link core_course_renderer::course_section_cm_completion()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->is_visible_on_course_page()) {
            return $output;
        }

        $template = new stdClass();

        // $indentclasses = 'mod-indent';
        // if (!empty($mod->indent)) {
        //     $indentclasses .= ' mod-indent-'.$mod->indent;
        //     if ($mod->indent > 15) {
        //         $indentclasses .= ' mod-indent-huge';
        //     }
        // }

        // $output .= html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            //$output .= course_get_cm_move($mod, $sectionreturn);
            $template->move = course_get_cm_move($mod, $sectionreturn);
        }

        // $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer'));

        // This div is used to indent the content.
        // $output .= html_writer::div('', $indentclasses);

        // Start a wrapper for the actual content to keep the indentation consistent
        // $output .= html_writer::start_tag('div');

        // Display the link to the module (or do nothing if module has no url)
        // $cmname = $this->course_section_cm_name($mod, $displayoptions);
        $template->cmname = $this->course_section_cm_name($mod, $displayoptions);

        if (!empty($template->cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            // $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));
            // $output .= $cmname;


            // Module can put text after the link (e.g. forum unread)
            // $output .= $mod->afterlink;
            $template->afterlink = $mod->afterlink;

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            // $output .= html_writer::end_tag('div'); // .activityinstance
        }

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        // $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        // $url = $mod->url;
        if (empty($template->url)) {
            $template->contentwithoutlink = $this->course_section_cm_text($mod, $displayoptions);
            // $output .= $contentpart;
        }

        // $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $template->modicons = ' '. $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $template->modicons .= $mod->afterediticons;
        }

        $template->completion = $this->course_section_cm_completion($course, $completioninfo, $mod, $displayoptions);

        // if (!empty($template->modicons)) {
        //     $output .= html_writer::span($modicons, 'actions');
        // }

        // Show availability info (if module is not available).
        $template->availability = $this->course_section_cm_availability($mod, $displayoptions);

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before
        // if (!empty($url)) {
        //     $output .= $contentpart;
        // }

        // $output .= html_writer::end_tag('div'); // $indentclasses

        // // End of indentation div.
        // $output .= html_writer::end_tag('div');

        // $output .= html_writer::end_tag('div');

        return $this->render_from_template('format_default/coursemodule', $template);
        return $output;
    }

        /**
     * Renders html to display a name with the link to the course module on a course page
     *
     * If module is unavailable for user but still needs to be displayed
     * in the list, just the name is returned without a link
     *
     * Note, that for course modules that never have separate pages (i.e. labels)
     * this function return an empty string
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_name(cm_info $mod, $displayoptions = array()) {
        if (!$mod->is_visible_on_course_page() || !$mod->url) {
            // Nothing to be displayed to the user.
            return '';
        }

        list($linkclasses, $textclasses) = $this->course_section_cm_classes($mod);
        $groupinglabel = $mod->get_grouping_label($textclasses);

        // Render element that allows to edit activity name inline. It calls {@link course_section_cm_name_title()}
        // to get the display title of the activity.
        $tmpl = new \core_course\output\course_module_name($mod, $this->page->user_is_editing(), $displayoptions);
        return $this->output->render_from_template('core/inplace_editable', $tmpl->export_for_template($this->output)) .
            $groupinglabel;
    }

        /**
     * Renders html to display the module content on the course page (i.e. text of the labels)
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_text(cm_info $mod, $displayoptions = array()) {
        $output = '';
        if (!$mod->is_visible_on_course_page()) {
            // nothing to be displayed to the user
            return $output;
        }
        $content = $mod->get_formatted_content(array('overflowdiv' => false, 'noclean' => true));
        list($linkclasses, $textclasses) = $this->course_section_cm_classes($mod);
        if ($mod->url && $mod->uservisible) {
            if ($content) {
                // If specified, display extra content after link.
                $output = html_writer::tag('div', $content, array('class' =>
                        trim('contentafterlink ' . $textclasses)));
            }
        } else {
            $groupinglabel = $mod->get_grouping_label($textclasses);

            // No link, so display only content.
            $output = html_writer::tag('div', $content . $groupinglabel,
                    array('class' => 'contentwithoutlink ' . $textclasses));
        }
        return $output;
    }

        /**
     * Returns the CSS classes for the activity name/content
     *
     * For items which are hidden, unavailable or stealth but should be displayed
     * to current user ($mod->is_visible_on_course_page()), we show those as dimmed.
     * Students will also see as dimmed activities names that are not yet available
     * but should still be displayed (without link) with availability info.
     *
     * @param cm_info $mod
     * @return array array of two elements ($linkclasses, $textclasses)
     */
    protected function course_section_cm_classes(cm_info $mod) {
        $linkclasses = '';
        $textclasses = '';
        if ($mod->uservisible) {
            $conditionalhidden = $this->is_cm_conditionally_hidden($mod);
            $accessiblebutdim = (!$mod->visible || $conditionalhidden) &&
                has_capability('moodle/course:viewhiddenactivities', $mod->context);
            if ($accessiblebutdim) {
                $linkclasses .= ' dimmed';
                $textclasses .= ' dimmed_text';
                if ($conditionalhidden) {
                    $linkclasses .= ' conditionalhidden';
                    $textclasses .= ' conditionalhidden';
                }
            }
            if ($mod->is_stealth()) {
                // Stealth activity is the one that is not visible on course page.
                // It still may be displayed to the users who can manage it.
                $linkclasses .= ' stealth';
                $textclasses .= ' stealth';
            }
        } else {
            $linkclasses .= ' dimmed';
            $textclasses .= ' dimmed dimmed_text';
        }
        return array($linkclasses, $textclasses);
    }

        /**
     * Checks if course module has any conditions that may make it unavailable for
     * all or some of the students
     *
     * This function is internal and is only used to create CSS classes for the module name/text
     *
     * @param cm_info $mod
     * @return bool
     */
    protected function is_cm_conditionally_hidden(cm_info $mod) {
        global $CFG;
        $conditionalhidden = false;
        if (!empty($CFG->enableavailability)) {
            $info = new \core_availability\info_module($mod);
            $conditionalhidden = !$info->is_available_for_all();
        }
        return $conditionalhidden;
    }

    /**
     * Renders html for completion box on course page
     *
     * If completion is disabled, returns empty string
     * If completion is automatic, returns an icon of the current completion state
     * If completion is manual, returns a form (with an icon inside) that allows user to
     * toggle completion
     *
     * @param stdClass $course course object
     * @param completion_info $completioninfo completion info for the course, it is recommended
     *     to fetch once for all modules in course/section for performance
     * @param cm_info $mod module to show completion for
     * @param array $displayoptions display options, not used in core
     * @return string
     */
    public function course_section_cm_completion($course, &$completioninfo, cm_info $mod, $displayoptions = array()) {
        global $CFG;
        $output = '';

        $template = new stdClass();
        $template->mod = $mod;

        if (!empty($displayoptions['hidecompletion']) || !isloggedin() || isguestuser() || !$mod->uservisible) {
            return $output;
        }
        if ($completioninfo === null) {
            $completioninfo = new completion_info($course);
        }
        $completion = $completioninfo->is_enabled($mod);
        if ($completion == COMPLETION_TRACKING_NONE) {
            if ($this->page->user_is_editing()) {
                $output .= html_writer::span('&nbsp;', 'filler');
            }
            return $output;
        }

        $completiondata = $completioninfo->get_data($mod, true);
        $completionicon = '';

        if ($this->page->user_is_editing()) {
            switch ($completion) {
                case COMPLETION_TRACKING_MANUAL :
                    $completionicon = 'manual-enabled'; break;
                case COMPLETION_TRACKING_AUTOMATIC :
                    $completionicon = 'auto-enabled'; break;
            }
        } else if ($completion == COMPLETION_TRACKING_MANUAL) {
            switch($completiondata->completionstate) {
                case COMPLETION_INCOMPLETE:
                    $completionicon = 'manual-n'; break;
                case COMPLETION_COMPLETE:
                    $completionicon = 'manual-y'; break;
            }
        } else { // Automatic
            switch($completiondata->completionstate) {
                case COMPLETION_INCOMPLETE:
                    $completionicon = 'auto-n'; break;
                case COMPLETION_COMPLETE:
                    $completionicon = 'auto-y'; break;
                case COMPLETION_COMPLETE_PASS:
                    $completionicon = 'auto-pass'; break;
                case COMPLETION_COMPLETE_FAIL:
                    $completionicon = 'auto-fail'; break;
            }
        }
        $template->completionicon = $completionicon;
        if ($completionicon) {
            $formattedname = $mod->get_formatted_name();
            $imgalt = get_string('completion-alt-' . $completionicon, 'completion', $formattedname);

            if ($this->page->user_is_editing()) {
                // When editing, the icon is just an image.
                // $completionpixicon = new pix_icon('i/completion-'.$completionicon, $imgalt, '',
                //         array('title' => $imgalt, 'class' => 'iconsmall'));
                // $output .= html_writer::tag('span', $this->output->render($completionpixicon),
                //         array('class' => 'autocompletion'));
                $output .= '<div class="checkbox pull-left" title="'.$imgalt.'">
                    <label>
                        <input type="checkbox">
                        <i class="input-helper"></i>
                    </label>
                </div>';
            } else if ($completion == COMPLETION_TRACKING_MANUAL) {
                $imgtitle = get_string('completion-title-' . $completionicon, 'completion', $formattedname);
                $newstate =
                    $completiondata->completionstate == COMPLETION_COMPLETE
                    ? COMPLETION_INCOMPLETE
                    : COMPLETION_COMPLETE;
                // In manual mode the icon is a toggle form...

                // If this completion state is used by the
                // conditional activities system, we need to turn
                // off the JS.
                $extraclass = '';
                if (!empty($CFG->enableavailability) &&
                        core_availability\info::completion_value_used($course, $mod->id)) {
                    $extraclass = ' preventjs';
                }
                // $output .= html_writer::start_tag('form', array('method' => 'post',
                //     'action' => new moodle_url('/course/togglecompletion.php'),
                //     'class' => 'togglecompletion'. $extraclass));
                // $output .= html_writer::start_tag('div');
                // $output .= html_writer::empty_tag('input', array(
                //     'type' => 'hidden', 'name' => 'id', 'value' => $mod->id));
                // $output .= html_writer::empty_tag('input', array(
                //     'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
                // $output .= html_writer::empty_tag('input', array(
                //     'type' => 'hidden', 'name' => 'modulename', 'value' => $mod->name));
                // $output .= html_writer::empty_tag('input', array(
                //     'type' => 'hidden', 'name' => 'completionstate', 'value' => $newstate));
                // $output .= html_writer::empty_tag('input', array(
                //     'type' => 'image',
                //     'src' => $this->output->pix_url('i/completion-'.$completionicon),
                //     'alt' => $imgalt, 'title' => $imgtitle,
                //     'aria-live' => 'polite'));
                // $output .= html_writer::end_tag('div');
                // $output .= html_writer::end_tag('form');
                $checked = '';
                if ($completiondata->completionstate == COMPLETION_COMPLETE) {
                    $template->checked = 'checked';
                }
                //$template->manual = true;

                // $output .= '<div class="checkbox pull-left">
                //             <label>
                //                 <input type="checkbox" '.$checked.' class="completioncheck" value="" data-mod="'.$mod->id.'"
                //                 data-completionstate="'.$newstate.'"
                //                 data-modulename="'.$mod->name.'"
                //                 data-id="'.$mod->id.'"
                //                 data-image="'.$completionicon.'">
                //                 <i class="input-helper"></i>
                //             </label>
                //         </div>';
            } else {
                // In auto mode, the icon is just an image.
                $template->auto = true;
                if ($completionicon == 'auto-y' || $completionicon == 'auto-pass') {
                    $template->autopass = true;
                    // $output .= '<div class="checkbox pull-left">
                    //         <label>
                    //             <input type="checkbox" checked disabled class="autocompletioncheck" value="" data-mod="'.$mod->id.'"
                    //             data-modulename="'.$mod->name.'"
                    //             data-id="'.$mod->id.'"
                    //             data-image="'.$completionicon.'">
                    //             <i class="input-helper"></i>
                    //         </label>
                    //     </div>';
                } else {
                    
                    // $output .= '<div class="checkbox pull-left">
                    //         <label>
                    //             <input type="checkbox" disabled class="autocompletioncheck" value="" data-mod="'.$mod->id.'"
                    //             data-modulename="'.$mod->name.'"
                    //             data-id="'.$mod->id.'"
                    //             data-image="'.$completionicon.'">
                    //             <i class="input-helper"></i>
                    //         </label>
                    //     </div>';
                }

                // $completionpixicon = new pix_icon('i/completion-'.$completionicon, $imgalt, '',
                //         array('title' => $imgalt));
                // $output .= html_writer::tag('span', $this->output->render($completionpixicon),
                //         array('class' => 'autocompletion'));
            }
        }

        return $this->render_from_template('format_default/completion', $template);
        return $output;
    }

        /**
     * Renders HTML to show course module availability information (for someone who isn't allowed
     * to see the activity itself, or for staff)
     *
     * @param cm_info $mod
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm_availability(cm_info $mod, $displayoptions = array()) {
        global $CFG;
        $output = '';
        if (!$mod->is_visible_on_course_page()) {
            return $output;
        }
        if (!$mod->uservisible) {
            // this is a student who is not allowed to see the module but might be allowed
            // to see availability info (i.e. "Available from ...")
            if (!empty($mod->availableinfo)) {
                $formattedinfo = \core_availability\info::format_info(
                        $mod->availableinfo, $mod->get_course());
                $output = $this->availability_info($formattedinfo, 'isrestricted');
            }
            return $output;
        }
        // this is a teacher who is allowed to see module but still should see the
        // information that module is not available to all/some students
        $modcontext = context_module::instance($mod->id);
        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $modcontext);
        if ($canviewhidden && !$mod->visible) {
            // This module is hidden but current user has capability to see it.
            // Do not display the availability info if the whole section is hidden.
            if ($mod->get_section_info()->visible) {
                $output .= $this->availability_info(get_string('hiddenfromstudents'), 'ishidden');
            }
        } else if ($mod->is_stealth()) {
            // This module is available but is normally not displayed on the course page
            // (this user can see it because they can manage it).
            $output .= $this->availability_info(get_string('hiddenoncoursepage'), 'isstealth');
        }
        if ($canviewhidden && !empty($CFG->enableavailability)) {
            // Display information about conditional availability.
            // Don't add availability information if user is not editing and activity is hidden.
            if ($mod->visible || $this->page->user_is_editing()) {
                $hidinfoclass = 'isrestricted isfullinfo';
                if (!$mod->visible) {
                    $hidinfoclass .= ' hide';
                }
                $ci = new \core_availability\info_module($mod);
                $fullinfo = $ci->get_full_information();
                if ($fullinfo) {
                    $formattedinfo = \core_availability\info::format_info(
                            $fullinfo, $mod->get_course());
                    $output .= $this->availability_info($formattedinfo, $hidinfoclass);
                }
            }
        }
        return $output;
    }

        /**
     * Renders HTML for displaying the sequence of course module editing buttons
     *
     * @see course_get_cm_edit_actions()
     *
     * @param action_link[] $actions Array of action_link objects
     * @param cm_info $mod The module we are displaying actions for.
     * @param array $displayoptions additional display options:
     *     ownerselector => A JS/CSS selector that can be used to find an cm node.
     *         If specified the owning node will be given the class 'action-menu-shown' when the action
     *         menu is being displayed.
     *     constraintselector => A JS/CSS selector that can be used to find the parent node for which to constrain
     *         the action menu to when it is being displayed.
     *     donotenhance => If set to true the action menu that gets displayed won't be enhanced by JS.
     * @return string
     */
    public function course_section_cm_edit_actions($actions, cm_info $mod = null, $displayoptions = array()) {
        global $CFG;

        if (empty($actions)) {
            return '';
        }

        if (isset($displayoptions['ownerselector'])) {
            $ownerselector = $displayoptions['ownerselector'];
        } else if ($mod) {
            $ownerselector = '#module-'.$mod->id;
        } else {
            debugging('You should upgrade your call to '.__FUNCTION__.' and provide $mod', DEBUG_DEVELOPER);
            $ownerselector = 'li.activity';
        }

        if (isset($displayoptions['constraintselector'])) {
            $constraint = $displayoptions['constraintselector'];
        } else {
            $constraint = '.course-content';
        }

        $menu = new action_menu();
        $menu->set_owner_selector($ownerselector);
        $menu->set_constraint($constraint);
        $menu->set_alignment(action_menu::TR, action_menu::BR);
        $menu->set_menu_trigger(get_string('edit'));

        foreach ($actions as $action) {
            if ($action instanceof action_menu_link) {
                $action->add_class('cm-edit-action');
            }
            $menu->add($action);
        }
        $menu->attributes['class'] .= ' section-cm-edit-actions commands';

        // Prioritise the menu ahead of all other actions.
        $menu->prioritise = true;

        return $this->render($menu);
    }

    /**
     * renders HTML for format_default_edit_control
     *
     * @param format_default_edit_control $control
     * @return string
     */
    protected function render_format_default_edit_control(format_default_edit_control $control) {
        $template = new stdClass();
        $template->inactivity = true;
        return $this->render_from_template('format_default/courseadmintabs', $template);
    }
}
