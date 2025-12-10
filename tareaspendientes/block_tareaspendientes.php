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

        // Consultar entregas pendientes con fecha de entrega
        $sql = "
            SELECT a.id AS assignid, a.name AS assignname, a.duedate, 
                   u.id AS userid, u.firstname, u.lastname, s.grade
              FROM {assign} a
              JOIN {assign_submission} s ON s.assignment = a.id
              JOIN {user} u ON u.id = s.userid
             WHERE a.course = :courseid
               AND s.status = :status
               AND (s.grade IS NULL)
          ORDER BY a.duedate ASC, a.name, u.lastname, u.firstname
        ";

        $submissions = $DB->get_records_sql($sql, ['courseid' => $COURSE->id, 'status' => 'submitted']);

        if (!$submissions) {
            $this->content->text = get_string('notasks', 'block_tareaspendientes');
            return $this->content;
        }

        // Agrupar por tarea
        $grouped = [];
        foreach ($submissions as $sub) {
            $grouped[$sub->assignid]['name'] = $sub->assignname;
            $grouped[$sub->assignid]['duedate'] = $sub->duedate;
            $grouped[$sub->assignid]['users'][] = $sub;
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
                    'userid' => $user->userid
                ]);
                $ul .= html_writer::tag('li', html_writer::link($url, $user->firstname . ' ' . $user->lastname));
            }
            $ul .= html_writer::end_tag('ul');

            $html .= $ul;
        }

        $html .= html_writer::end_tag('div');

        // Incluir JavaScript y CSS
        $this->content->text = $html;
        $this->content->text .= html_writer::script("
            require(['jquery'], function($){
                $('.block-tareaspendientes-toggle').click(function(){
                    $(this).next('ul').slideToggle();
                });
                // Actualización AJAX cada 60 segundos
                setInterval(function(){
                    $.get(window.location.href, function(data){
                        // recargar el bloque completo
                        var newblock = $('#block-tareaspendientes-tasks', data).html();
                        $('#block-tareaspendientes-tasks').html(newblock);
                    });
                }, 60000);
            });
        ");

        $this->content->footer = '';

        return $this->content;
    }
}

