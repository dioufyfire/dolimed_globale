<?php
/* Helpers for the patient-level sickle cell history block. */

function cabinetmed_drepano_allowed_value($value, $allowed)
{
    return in_array($value, $allowed, true) ? $value : '';
}

function cabinetmed_drepano_date_value($value)
{
    if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)) return $value;
    return '';
}

function cabinetmed_drepano_post_values()
{
    $values=array();
    $textfields=array(
        'reference_confirmation', 'note_identification', 'note_cas_fratrie',
        'troubles_alimentaires', 'activite_professionnelle',
        'vaccination_antityphique_note', 'vaccination_pneumocoque_note',
        'vaccination_meningocoque_note', 'vaccination_autre_libelle',
        'vaccination_autre_note', 'complications_aigues',
        'complications_chroniques', 'antecedents_medicochirurgicaux',
        'note_transfusions'
    );
    foreach ($textfields as $field) {
        $values[$field]=isset($_POST['drepano_'.$field]) ? $_POST['drepano_'.$field] : '';
    }

    $values['suivi']=!empty($_POST['drepano_suivi']) ? 1 : 0;
    $values['alert_general']=!empty($_POST['drepano_alert_general']) ? 1 : 0;

    $hbvalues=array('', 'AA', 'AS', 'AC', 'SS', 'SC', 'Sbeta0', 'SbetaPlus', 'other', 'unknown');
    $ynvalues=array('', 'yes', 'no', 'unknown');
    $vaccinestatuses=array('', 'up_to_date', 'to_do', 'unknown');

    $values['profil_hb_patient']=cabinetmed_drepano_allowed_value(isset($_POST['drepano_profil_hb_patient']) ? $_POST['drepano_profil_hb_patient'] : '', $hbvalues);
    $values['profil_hb_pere']=cabinetmed_drepano_allowed_value(isset($_POST['drepano_profil_hb_pere']) ? $_POST['drepano_profil_hb_pere'] : '', $hbvalues);
    $values['profil_hb_mere']=cabinetmed_drepano_allowed_value(isset($_POST['drepano_profil_hb_mere']) ? $_POST['drepano_profil_hb_mere'] : '', $hbvalues);
    $values['consanguinite']=cabinetmed_drepano_allowed_value(isset($_POST['drepano_consanguinite']) ? $_POST['drepano_consanguinite'] : '', $ynvalues);
    $values['cas_fratrie']=cabinetmed_drepano_allowed_value(isset($_POST['drepano_cas_fratrie']) ? $_POST['drepano_cas_fratrie'] : '', $ynvalues);
    $values['transfusion_statut']=cabinetmed_drepano_allowed_value(isset($_POST['drepano_transfusion_statut']) ? $_POST['drepano_transfusion_statut'] : '', $ynvalues);

    $values['vaccination_antityphique_statut']=cabinetmed_drepano_allowed_value(isset($_POST['drepano_vaccination_antityphique_statut']) ? $_POST['drepano_vaccination_antityphique_statut'] : '', $vaccinestatuses);
    $values['vaccination_pneumocoque_statut']=cabinetmed_drepano_allowed_value(isset($_POST['drepano_vaccination_pneumocoque_statut']) ? $_POST['drepano_vaccination_pneumocoque_statut'] : '', $vaccinestatuses);
    $values['vaccination_meningocoque_statut']=cabinetmed_drepano_allowed_value(isset($_POST['drepano_vaccination_meningocoque_statut']) ? $_POST['drepano_vaccination_meningocoque_statut'] : '', $vaccinestatuses);
    $values['vaccination_autre_statut']=cabinetmed_drepano_allowed_value(isset($_POST['drepano_vaccination_autre_statut']) ? $_POST['drepano_vaccination_autre_statut'] : '', $vaccinestatuses);

    $datefields=array(
        'date_confirmation', 'vaccination_pev_date',
        'vaccination_antityphique_date', 'vaccination_pneumocoque_date',
        'vaccination_meningocoque_date', 'vaccination_autre_date',
        'derniere_transfusion_date'
    );
    foreach ($datefields as $field) {
        $value=isset($_POST['drepano_'.$field]) ? $_POST['drepano_'.$field] : '';
        $values[$field]=cabinetmed_drepano_date_value($value);
    }

    $intfields=array(
        'rang_fratrie', 'taille_fratrie', 'nombre_cas_fratrie',
        'cvo_12_mois', 'hospitalisations_12_mois', 'nombre_transfusions'
    );
    foreach ($intfields as $field) {
        $value=isset($_POST['drepano_'.$field]) ? (int) $_POST['drepano_'.$field] : 0;
        $values[$field]=max(0, $value);
    }

    return $values;
}

function cabinetmed_drepano_save($db, $patientid, $values)
{
    $intfields=array(
        'suivi', 'alert_general', 'rang_fratrie', 'taille_fratrie',
        'nombre_cas_fratrie', 'cvo_12_mois', 'hospitalisations_12_mois',
        'nombre_transfusions'
    );
    $datefields=array(
        'date_confirmation', 'vaccination_pev_date',
        'vaccination_antityphique_date', 'vaccination_pneumocoque_date',
        'vaccination_meningocoque_date', 'vaccination_autre_date',
        'derniere_transfusion_date'
    );

    $columns=array('fk_patient');
    $sqlvalues=array((int) $patientid);
    $updates=array();
    foreach ($values as $field=>$value) {
        $columns[]=$field;
        if (in_array($field, $intfields, true)) {
            $sqlvalue=(string) ((int) $value);
        } elseif (in_array($field, $datefields, true)) {
            $sqlvalue=$value !== '' ? "'".$db->escape($value)."'" : 'NULL';
        } else {
            $sqlvalue="'".$db->escape($value)."'";
        }
        $sqlvalues[]=$sqlvalue;
        $updates[]=$field.'='.$sqlvalue;
    }

    $columns[]='datec';
    $sqlvalues[]='NOW()';
    $sql='INSERT INTO '.MAIN_DB_PREFIX.'cabinetmed_patient_drepano ('.implode(', ', $columns).')';
    $sql.=' VALUES ('.implode(', ', $sqlvalues).')';
    $sql.=' ON DUPLICATE KEY UPDATE '.implode(', ', $updates);

    $result=$db->query($sql);
    return $result ? '' : $db->lasterror();
}

function cabinetmed_drepano_select_html($name, $selected, $options, $edit)
{
    if (!$edit) {
        return isset($options[$selected]) ? dol_escape_htmltag($options[$selected]) : '';
    }

    $html='<select class="flat" name="'.dol_escape_htmltag($name).'">';
    foreach ($options as $key=>$label) {
        $html.='<option value="'.dol_escape_htmltag($key).'"'.($selected == $key ? ' selected="selected"' : '').'>'.dol_escape_htmltag($label).'</option>';
    }
    $html.='</select>';
    return $html;
}

function cabinetmed_drepano_input_html($name, $value, $type, $edit, $extra)
{
    if (!$edit) return dol_escape_htmltag($value);
    return '<input class="flat" type="'.dol_escape_htmltag($type).'" name="'.dol_escape_htmltag($name).'" value="'.dol_escape_htmltag($value).'" '.$extra.'>';
}

function cabinetmed_drepano_textarea_html($name, $value, $edit, $rows)
{
    if (!$edit) return nl2br(dol_escape_htmltag($value));
    return '<textarea class="flat" name="'.dol_escape_htmltag($name).'" rows="'.((int) $rows).'" style="width:95%">'.dol_escape_htmltag($value).'</textarea>';
}
