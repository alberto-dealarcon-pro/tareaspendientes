<?php
defined('MOODLE_INTERNAL') || die();

class block_tareaspendientes extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_tareaspendientes');
    }

    public function get_content() {
        global $USER, $DB, $COURSE;
        

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        // Solo para profesores
        if (!has_capability('moodle/course:manageactivities', context_course::instance($COURSE->id))) {
            $this->content->text = get_string('nocapability', 'block_tareaspendientes');
            return $this->content;
        }

        // Obtener todas las asignaciones del curso
        $assignments = $DB->get_records('assign', ['course' => $COURSE->id]);
        if (!$assignments) {
            debugging('No assignments found in course 1 ' . $COURSE->id, DEBUG_DEVELOPER);
            $this->content->text = get_string('notasks', 'block_tareaspendientes');
            return $this->content;
        }

        $grouped = [];

        foreach ($assignments as $assign) {
            // Traer los envíos más recientes de cada usuario
            $submissions = $DB->get_records('assign_submission', ['assignment' => $assign->id, 'latest' => 1, 'status' => 'submitted']);
            foreach ($submissions as $sub) {
                // Obtener calificación
                debugging('Processing submission for assignment ' . $assign->id . ' user ' . $sub->userid, DEBUG_DEVELOPER);
                $grade = $DB->get_field('assign_grades', 'grade', ['assignment' => $assign->id, 'userid' => $sub->userid]);
                if ($grade === null || $grade < 0) { // Pendiente de calificar
                    $user = $DB->get_record('user', ['id' => $sub->userid]);
                    debugging('Found ungraded submission for user ' . $sub->userid, DEBUG_DEVELOPER);
                    if (!$user) {
                        continue;
                    }
                    $grouped[$assign->id]['name'] = $assign->name;
                    $grouped[$assign->id]['duedate'] = $assign->duedate;
                    $grouped[$assign->id]['users'][] = $user;
                }
            }
        }

        if (empty($grouped)) {
            debugging('No pending tasks found in course 2' . $COURSE->id, DEBUG_DEVELOPER);
            $this->content->text = get_string('notasks', 'block_tareaspendientes');
            return $this->content;
        }

        // Generar HTML
        $html = html_writer::start_tag('div', ['id' => 'block-tareaspendientes-tasks']);

        foreach ($grouped as $assignid => $taskinfo) {
            $count = count($taskinfo['users']);
            $duedate = $taskinfo['duedate'];
            $now = time();
            $color = 'white';

            if ($duedate < $now) {
                $color = '#ffcccc'; // rojo: vencido
            } elseif ($duedate - $now < 2*24*3600) {
                $color = '#fff2cc'; // amarillo: menos de 2 días
            }

            $html .= html_writer::tag('button', $taskinfo['name'] . " ($count)", [
                'class' => 'block-tareaspendientes-toggle',
                'style' => "background-color:$color;",
            ]);

            $ul = html_writer::start_tag('ul', ['style' => 'display:none; margin-left:15px;']);
            foreach ($taskinfo['users'] as $user) {
                $cm = get_coursemodule_from_instance('assign', $assignid, $COURSE->id, false, MUST_EXIST);
                $url = new moodle_url('/mod/assign/view.php', [
                    'id' => $cm->id,
                    'action' => 'grade',
                    'userid' => $user->id
                ]);
                $ul .= html_writer::tag('li', html_writer::link($url, $user->firstname . ' ' . $user->lastname));
            }
            $ul .= html_writer::end_tag('ul');

            $html .= $ul;
        }

        $html .= html_writer::end_tag('div');

        // Incluir JavaScript y CSS
        $this->content->text = $html;

        // Inicializar JS AMD
        $this->page->requires->js_call_amd(
        'block_tareaspendientes/tareaspendientes',
        'init');

        $this->content->footer = '';

        return $this->content;
    }
}
