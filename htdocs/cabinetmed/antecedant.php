<?php
/* Copyright (C) 2001-2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *   \file       htdocs/cabinetmed/antecedant.php
 *   \brief      Tab for antecedants
 *   \ingroup    societe
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

include_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
include_once("./class/patient.class.php");
include_once("./lib/cabinetmed.lib.php");
include_once("./lib/drepano.lib.php");

$langs->load("companies");
$langs->load("cabinetmed@cabinetmed");

$action = GETPOST('action','aZ09');
if (empty($action)) $action='edit';

// The ATCD page is shared by both consultation tabs. Pediatric-only fields
// are displayed and saved exclusively when mode_cons=2.
$mode_cons = GETPOST('mode_cons', 'int');
if ($mode_cons != 2) $mode_cons = 1;
$is_pediatrie = ($mode_cons == 2);

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

if (!$user->rights->cabinetmed->read) accessforbidden();


/*
 * Actions
 */
if ($action == 'addupdate')
{
    $error=0;

    $note_antenataux = isset($_POST['note_antenataux']) ? $db->escape($_POST['note_antenataux']) : '';
    $note_perinataux = isset($_POST['note_perinataux']) ? $db->escape($_POST['note_perinataux']) : '';
    $note_postnataux = isset($_POST['note_postnataux']) ? $db->escape($_POST['note_postnataux']) : '';
    $note_alimentation = isset($_POST['note_alimentation']) ? $db->escape($_POST['note_alimentation']) : '';
    $statut_vaccination_pev = isset($_POST['statut_vaccination_pev']) ? $_POST['statut_vaccination_pev'] : '';
    $note_vaccination_pev = isset($_POST['note_vaccination_pev']) ? $db->escape($_POST['note_vaccination_pev']) : '';
    $statut_vaccination_rappels = isset($_POST['statut_vaccination_rappels']) ? $_POST['statut_vaccination_rappels'] : '';
    $note_vaccination_rappels = isset($_POST['note_vaccination_rappels']) ? $db->escape($_POST['note_vaccination_rappels']) : '';
    $note_scolarite = isset($_POST['note_scolarite']) ? $db->escape($_POST['note_scolarite']) : '';
    if (!in_array($statut_vaccination_pev, array('', 'up_to_date', 'incomplete', 'not_started', 'unknown'), true)) $statut_vaccination_pev = '';
    if (!in_array($statut_vaccination_rappels, array('', 'up_to_date', 'late', 'unknown'), true)) $statut_vaccination_rappels = '';
    $drepano_values=cabinetmed_drepano_post_values();

    $db->begin();

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."cabinetmed_patient(rowid, note_antemed, note_antechirgen, note_antechirortho, note_anterhum, note_other, note_traitallergie, note_traitclass, note_traitintol, note_traitspec, note_alimentation, statut_vaccination_pev, note_vaccination_pev, note_scolarite";
    if ($is_pediatrie) $sql.= ", note_antenataux, note_perinataux, note_postnataux, statut_vaccination_rappels, note_vaccination_rappels";
    $sql.= ")";
    $sql.= " VALUES('".$socid."',";
    $sql.= " '".addslashes($_POST["note_antemed"])."','".addslashes($_POST["note_antechirgen"])."',";
    $sql.= " '".addslashes($_POST["note_antechirortho"])."','".addslashes($_POST["note_anterhum"])."','".addslashes($_POST["note_other"])."',";
    $sql.= " '".addslashes($_POST["note_traitallergie"])."','".addslashes($_POST["note_traitclass"])."','".addslashes($_POST["note_traitintol"])."','".addslashes($_POST["note_traitspec"])."'";
    $sql.= ", '".$note_alimentation."','".$statut_vaccination_pev."','".$note_vaccination_pev."','".$note_scolarite."'";
    if ($is_pediatrie) $sql.= ", '".$note_antenataux."','".$note_perinataux."','".$note_postnataux."','".$statut_vaccination_rappels."','".$note_vaccination_rappels."'";
    $sql.= ")";
    $result1 = $db->query($sql,1);
    //if (! $result) dol_print_error($db);

    $sql = "UPDATE ".MAIN_DB_PREFIX."cabinetmed_patient SET";
    $sql.= " note_antemed='".addslashes($_POST["note_antemed"])."',";
    $sql.= " note_antechirgen='".addslashes($_POST["note_antechirgen"])."',";
    $sql.= " note_antechirortho='".addslashes($_POST["note_antechirortho"])."',";
    $sql.= " note_anterhum='".addslashes($_POST["note_anterhum"])."',";
    //$sql.= " note_other='".addslashes($_POST["note_other"])."',";
    $sql.= " note_traitallergie='".addslashes($_POST["note_traitallergie"])."',";
    $sql.= " note_traitclass='".addslashes($_POST["note_traitclass"])."',";
    $sql.= " note_traitintol='".addslashes($_POST["note_traitintol"])."',";
    $sql.= " note_traitspec='".addslashes($_POST["note_traitspec"])."',";
    $sql.= " note_alimentation='".$note_alimentation."',";
    $sql.= " statut_vaccination_pev='".$statut_vaccination_pev."',";
    $sql.= " note_vaccination_pev='".$note_vaccination_pev."',";
    $sql.= " note_scolarite='".$note_scolarite."'";
    if ($is_pediatrie) {
        $sql.= ", note_antenataux='".$note_antenataux."'";
        $sql.= ", note_perinataux='".$note_perinataux."'";
        $sql.= ", note_postnataux='".$note_postnataux."'";
        $sql.= ", statut_vaccination_rappels='".$statut_vaccination_rappels."'";
        $sql.= ", note_vaccination_rappels='".$note_vaccination_rappels."'";
    }
    $sql.= " WHERE rowid=".$socid;
    $result2 = $db->query($sql);

    $alert=($_POST["alert_antemed"]?'1':'0');
    $result3=addAlert($db, 'alert_antemed', $socid, $alert);
    if ($result3) {
        $error++; $mesg=$result3;
    }

    $alert=($_POST["alert_antechirgen"]?'1':'0');
    $result4=addAlert($db, 'alert_antechirgen', $socid, $alert);
    if ($result4) {
        $error++; $mesg=$result4;
    }

    $alert=($_POST["alert_antechirortho"]?'1':'0');
    $result5=addAlert($db, 'alert_antechirortho', $socid, $alert);
    if ($result5) {
        $error++; $mesg=$result5;
    }

    $alert=($_POST["alert_anterhum"]?'1':'0');
    $result6=addAlert($db, 'alert_anterhum', $socid, $alert);
    if ($result6) {
        $error++; $mesg=$result6;
    }

    $alert=($_POST["alert_traitallergie"]?'1':'0');
    $result7=addAlert($db, 'alert_traitallergie', $socid, $alert);
    if ($result7) {
        $error++; $mesg=$result7;
    }

    $alert=($_POST["alert_traitclass"]?'1':'0');
    $result8=addAlert($db, 'alert_traitclass', $socid, $alert);
    if ($result8) {
        $error++; $mesg=$result8;
    }

    $alert=($_POST["alert_traitintol"]?'1':'0');
    $result9=addAlert($db, 'alert_traitintol', $socid, $alert);
    if ($result9) {
        $error++; $mesg=$result9;
    }

    $alert=($_POST["alert_traitspec"]?'1':'0');
    $result10=addAlert($db, 'alert_traitspec', $socid, $alert);
    if ($result10) {
        $error++; $mesg=$result10;
    }

    $result11='';
    $result12='';
    $result13='';
    if ($is_pediatrie) {
        $alert=(!empty($_POST["alert_antenataux"])?'1':'0');
        $result11=addAlert($db, 'alert_antenataux', $socid, $alert);
        if ($result11) {
            $error++; $mesg=$result11;
        }

        $alert=(!empty($_POST["alert_perinataux"])?'1':'0');
        $result12=addAlert($db, 'alert_perinataux', $socid, $alert);
        if ($result12) {
            $error++; $mesg=$result12;
        }

        $alert=(!empty($_POST["alert_postnataux"])?'1':'0');
        $result13=addAlert($db, 'alert_postnataux', $socid, $alert);
        if ($result13) {
            $error++; $mesg=$result13;
        }
    }

    $result14=cabinetmed_drepano_save($db, $socid, $drepano_values);
    if ($result14) {
        $error++; $mesg=$result14;
    }

    if ((! $result2) || $result3 || $result4 || $result5 || $result6 || $result7 || $result8 || $result9 || $result10 || $result11 || $result12 || $result13 || $result14)
    {
        dol_print_error($db);
        $db->rollback();
    }
    else
    {
        $db->commit();
        $mesg=$langs->trans("RecordModifiedSuccessfully");
    }

    $action='edit';
}


/*
 *	View
*/

$form = new Form($db);

llxHeader('',$langs->trans("ATCD"));


if ($socid > 0)
{
    $object = new Patient($db);
    $res=$object->fetch($socid);
    if ($res < 0)
    {
        dol_print_error($db,$object->error);
    }
    $object->id=$socid;

    /*
     * Affichage onglets
    */
    if ($conf->notification->enabled) $langs->load("mails");

    $head = societe_prepare_head($object);

    if ((float) DOL_VERSION < 7) dol_fiche_head($head, 'tabantecedents', $langs->trans("Patient"), 0, 'patient@cabinetmed');
    else dol_fiche_head($head, 'tabantecedents', $langs->trans("Patient"), -1, 'patient@cabinetmed');


    print '<script type="text/javascript">
    var changed=false;
    jQuery(function() {
        jQuery(window).bind(\'beforeunload\', function(){
            /* alert(changed); */
            if (changed) return \''.dol_escape_js($langs->transnoentitiesnoconv("WarningExitPageWithoutSaving")).'\';
        });
        jQuery(".flat").change(function () {
            changed=true;
        });
        jQuery(".ignorechange").click(function () {
            changed=false;
        });
     });
    </script>';

    print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="socid" value="'.$object->id.'">';
    print '<input type="hidden" name="action" value="addupdate">';
    print '<input type="hidden" name="mode_cons" value="'.$mode_cons.'">';

    $linkback = '<a href="'.dol_buildpath('/cabinetmed/patients.php', 1).'">'.$langs->trans("BackToList").'</a>';
    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

    $url_atcd_general = $_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;mode_cons=1';
    $url_atcd_pediatrie = $_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;mode_cons=2';
    print '<div class="tabsAction">';
    if ($is_pediatrie) {
        print '<a class="butAction" href="'.$url_atcd_general.'">'.$langs->trans("MedecineGenerale").'</a>';
        print '<span class="butActionRefused">'.$langs->trans("Pediatrie").'</span>';
    } else {
        print '<span class="butActionRefused">'.$langs->trans("MedecineGenerale").'</span>';
        print '<a class="butAction" href="'.$url_atcd_pediatrie.'">'.$langs->trans("Pediatrie").'</a>';
    }
    print '</div>';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';

    //if ($object->client)
    //{
        print '<tr><td class="titlefield">';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $object->code_client;
        if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    //}

    if ($conf->fournisseur->enabled && $object->fournisseur)
    {
        print '<tr><td class="titlefield">';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $object->code_fournisseur;
        if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td></tr>';
    }

    print '</table><br>';



    print '<div class="fichecenter"><div class="fichehalfleft">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';

    // Force disable fckeditor
    if (! isset($conf->fckeditor)) $conf->fckeditor = new stdClass();
    $conf->fckeditor->enabled=false;

    $height=120;

    print '<tr height="80"><td class="tdtop titlefield">'.$langs->trans("AntecedentsMed");
    print '<br><input type="checkbox" name="alert_antemed"'.((isset($_POST['alert_antemed'])?GETPOST('alert_antemed'):$object->alert_antemed)?' checked="checked"':'').'"> '.$langs->trans("Alert");
    print '</td>';
    print '<td class="tdtop">';
    if ($action == 'edit' && $user->rights->societe->creer)
    {
        print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

        // Editeur wysiwyg
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('note_antemed',$object->note_antemed,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,8,'95%');
        $doleditor->Create();
    }
    else
    {
        print nl2br($object->note_antemed);
    }
    print "</td>";
    //print "</tr>";

    print '</tr></table>';
    print '</div><div class="fichehalfright"><div class="ficheaddleft" style="margin-top: auto;">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border" width="100%" style="margin-bottom: 2px !important;"><tr height="80">';

    // Spec
    //print '<tr height="80">';
    print '<td class="tdtop titlefield">'.$langs->trans("SpecPharma");
    print '<br><input type="checkbox" name="alert_traitspec"'.((isset($_POST['alert_traitspec'])?GETPOST('alert_traitspec'):$object->alert_traitspec)?' checked="checked"':'').'"> '.$langs->trans("Alert");
    print '</td>';
    print '<td class="tdtop">';
    if ($action == 'edit' && $user->rights->societe->creer)
    {
        print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

        // Editeur wysiwyg
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('note_traitspec',$object->note_traitspec,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,8,'95%');
        $doleditor->Create();
    }
    else
    {
        print nl2br($object->note_traitspec);
    }
    print "</td></tr>";


    print '</table>';
    print '</div></div></div>';

    if ($is_pediatrie) {
        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';
        print '<tr height="80"><td class="tdtop titlefield">'.$langs->trans("AntecedentsAntenataux");
        print '<br><input type="checkbox" name="alert_antenataux"'.((isset($_POST['alert_antenataux'])?GETPOST('alert_antenataux'):$object->alert_antenataux)?' checked="checked"':'').'> '.$langs->trans("Alert");
        print '</td><td class="tdtop">';
        if ($action == 'edit' && $user->rights->societe->creer) {
            require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
            $doleditor=new DolEditor('note_antenataux',$object->note_antenataux,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,6,'95%');
            $doleditor->Create();
        } else {
            print nl2br($object->note_antenataux);
        }
        print '</td></tr></table>';
        print '</div><div class="fichehalfright"><div class="ficheaddleft" style="margin-top: auto">';
        print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';
        print '<tr height="80"><td class="tdtop titlefield">'.$langs->trans("AntecedentsPerinataux");
        print '<br><input type="checkbox" name="alert_perinataux"'.((isset($_POST['alert_perinataux'])?GETPOST('alert_perinataux'):$object->alert_perinataux)?' checked="checked"':'').'> '.$langs->trans("Alert");
        print '</td><td class="tdtop">';
        if ($action == 'edit' && $user->rights->societe->creer) {
            require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
            $doleditor=new DolEditor('note_perinataux',$object->note_perinataux,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,6,'95%');
            $doleditor->Create();
        } else {
            print nl2br($object->note_perinataux);
        }
        print '</td></tr></table>';
        print '</div></div></div>';

        // Postnatal information is one pediatric history group with one alert.
        print '<div class="fichecenter">';
        print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';
        print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("AntecedentsPostnataux");
        print ' &nbsp; <input type="checkbox" name="alert_postnataux"'.((isset($_POST['alert_postnataux'])?GETPOST('alert_postnataux'):$object->alert_postnataux)?' checked="checked"':'').'> '.$langs->trans("GeneralAlert");
        print '</td></tr>';

        print '<tr><td class="tdtop titlefield">'.$langs->trans("PostnatalFeeding").'</td><td class="tdtop">';
        if ($action == 'edit' && $user->rights->societe->creer) {
            require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
            $doleditor=new DolEditor('note_alimentation',$object->note_alimentation,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,6,'95%');
            $doleditor->Create();
        } else {
            print nl2br($object->note_alimentation);
        }
        print '</td><td class="tdtop titlefield">'.$langs->trans("PostnatalSchooling").'</td><td class="tdtop">';
        if ($action == 'edit' && $user->rights->societe->creer) {
            $doleditor=new DolEditor('note_scolarite',$object->note_scolarite,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,6,'95%');
            $doleditor->Create();
        } else {
            print nl2br($object->note_scolarite);
        }
        print '</td></tr>';

        $pevstatuses=array(''=>'', 'up_to_date'=>$langs->trans('VaccinationUpToDate'), 'incomplete'=>$langs->trans('VaccinationIncomplete'), 'not_started'=>$langs->trans('VaccinationNotStarted'), 'unknown'=>$langs->trans('VaccinationUnknown'));
        $reminderstatuses=array(''=>'', 'up_to_date'=>$langs->trans('VaccinationUpToDate'), 'late'=>$langs->trans('VaccinationLate'), 'unknown'=>$langs->trans('VaccinationUnknown'));
        print '<tr><td class="tdtop titlefield">'.$langs->trans("VaccinationPEV").'</td><td class="tdtop">';
        if ($action == 'edit' && $user->rights->societe->creer) {
            print '<select class="flat" name="statut_vaccination_pev"><option value="">'.$langs->trans('Select').'</option>';
            foreach ($pevstatuses as $statuskey=>$statuslabel) {
                if ($statuskey === '') continue;
                print '<option value="'.dol_escape_htmltag($statuskey).'"'.($object->statut_vaccination_pev == $statuskey?' selected="selected"':'').'>'.dol_escape_htmltag($statuslabel).'</option>';
            }
            print '</select><br><br>';
            $doleditor=new DolEditor('note_vaccination_pev',$object->note_vaccination_pev,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,6,'95%');
            $doleditor->Create();
        } else {
            if (!empty($object->statut_vaccination_pev) && isset($pevstatuses[$object->statut_vaccination_pev])) print dol_escape_htmltag($pevstatuses[$object->statut_vaccination_pev]).'<br>';
            print nl2br($object->note_vaccination_pev);
        }
        print '</td><td class="tdtop titlefield">'.$langs->trans("VaccinationReminders").'</td><td class="tdtop">';
        if ($action == 'edit' && $user->rights->societe->creer) {
            print '<select class="flat" name="statut_vaccination_rappels"><option value="">'.$langs->trans('Select').'</option>';
            foreach ($reminderstatuses as $statuskey=>$statuslabel) {
                if ($statuskey === '') continue;
                print '<option value="'.dol_escape_htmltag($statuskey).'"'.($object->statut_vaccination_rappels == $statuskey?' selected="selected"':'').'>'.dol_escape_htmltag($statuslabel).'</option>';
            }
            print '</select><br><br>';
            $doleditor=new DolEditor('note_vaccination_rappels',$object->note_vaccination_rappels,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,6,'95%');
            $doleditor->Create();
        } else {
            if (!empty($object->statut_vaccination_rappels) && isset($reminderstatuses[$object->statut_vaccination_rappels])) print dol_escape_htmltag($reminderstatuses[$object->statut_vaccination_rappels]).'<br>';
            print nl2br($object->note_vaccination_rappels);
        }
        print '</td></tr>';

        print '<tr><td class="tdtop titlefield">'.$langs->trans("OtherPostnatalElements").'</td><td class="tdtop" colspan="3">';
        if ($action == 'edit' && $user->rights->societe->creer) {
            $doleditor=new DolEditor('note_postnataux',$object->note_postnataux,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,6,'98%');
            $doleditor->Create();
        } else {
            print nl2br($object->note_postnataux);
        }
        print '</td></tr></table></div>';
    }

    // Sickle cell disease history is shared by General Medicine and Pediatrics.
    // In Pediatrics, Feeding, PEV and Schooling stay editable only in Postnatal
    // to avoid duplicate form fields; their current values are repeated here.
    $edit_drepano=($action == 'edit' && $user->rights->societe->creer);
    $yesnooptions=array(
        ''=>$langs->trans('Select'),
        'yes'=>$langs->trans('Yes'),
        'no'=>$langs->trans('No'),
        'unknown'=>$langs->trans('VaccinationUnknown')
    );
    $hboptions=array(
        ''=>$langs->trans('Select'), 'AA'=>'AA', 'AS'=>'AS', 'AC'=>'AC',
        'SS'=>'SS', 'SC'=>'SC', 'Sbeta0'=>'Sβ⁰', 'SbetaPlus'=>'Sβ+',
        'other'=>$langs->trans('Other'), 'unknown'=>$langs->trans('VaccinationUnknown')
    );
    $drepvaccinestatuses=array(
        ''=>$langs->trans('Select'),
        'up_to_date'=>$langs->trans('VaccinationUpToDate'),
        'to_do'=>$langs->trans('VaccinationToDo'),
        'unknown'=>$langs->trans('VaccinationUnknown')
    );
    $sharedpevstatuses=array(
        ''=>$langs->trans('Select'),
        'up_to_date'=>$langs->trans('VaccinationUpToDate'),
        'incomplete'=>$langs->trans('VaccinationIncomplete'),
        'not_started'=>$langs->trans('VaccinationNotStarted'),
        'unknown'=>$langs->trans('VaccinationUnknown')
    );

    print '<div class="fichecenter">';
    print '<table class="border" width="100%" style="margin: 12px 0 2px 0 !important;">';
    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans('SickleCellDisease');
    print ' &nbsp; <input type="checkbox" name="drepano_suivi"'.(!empty($object->drepano_suivi)?' checked="checked"':'').'> '.$langs->trans('SickleCellFollowUp');
    print ' &nbsp; <input type="checkbox" name="drepano_alert_general"'.(!empty($object->drepano_alert_general)?' checked="checked"':'').'> '.$langs->trans('GeneralAlert');
    print '</td></tr>';

    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans('SickleCellIdentification').'</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('HemoglobinProfilePatient').'</td><td>';
    print cabinetmed_drepano_select_html('drepano_profil_hb_patient', $object->drepano_profil_hb_patient, $hboptions, $edit_drepano);
    print '</td><td class="titlefield">'.$langs->trans('ConfirmationDate').'</td><td>';
    print cabinetmed_drepano_input_html('drepano_date_confirmation', $object->drepano_date_confirmation, 'date', $edit_drepano, '');
    print '</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('ReferenceLaboratory').'</td><td>';
    print cabinetmed_drepano_input_html('drepano_reference_confirmation', $object->drepano_reference_confirmation, 'text', $edit_drepano, 'style="width:95%"');
    print '</td><td class="titlefield">'.$langs->trans('Notes').'</td><td>';
    print cabinetmed_drepano_textarea_html('drepano_note_identification', $object->drepano_note_identification, $edit_drepano, 2);
    print '</td></tr>';

    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans('FamilyHistory').'</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('Consanguinity').'</td><td>';
    print cabinetmed_drepano_select_html('drepano_consanguinite', $object->drepano_consanguinite, $yesnooptions, $edit_drepano);
    print '</td><td class="titlefield">'.$langs->trans('SiblingRankAndCount').'</td><td>';
    print $langs->trans('SiblingRank').' '.cabinetmed_drepano_input_html('drepano_rang_fratrie', $object->drepano_rang_fratrie, 'number', $edit_drepano, 'min="0" style="width:70px"');
    print ' &nbsp; '.$langs->trans('SiblingCount').' '.cabinetmed_drepano_input_html('drepano_taille_fratrie', $object->drepano_taille_fratrie, 'number', $edit_drepano, 'min="0" style="width:70px"');
    print '</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('HemoglobinProfileFather').'</td><td>';
    print cabinetmed_drepano_select_html('drepano_profil_hb_pere', $object->drepano_profil_hb_pere, $hboptions, $edit_drepano);
    print '</td><td class="titlefield">'.$langs->trans('HemoglobinProfileMother').'</td><td>';
    print cabinetmed_drepano_select_html('drepano_profil_hb_mere', $object->drepano_profil_hb_mere, $hboptions, $edit_drepano);
    print '</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('SiblingSickleCellCases').'</td><td>';
    print cabinetmed_drepano_select_html('drepano_cas_fratrie', $object->drepano_cas_fratrie, $yesnooptions, $edit_drepano);
    print ' &nbsp; '.$langs->trans('Number').' '.cabinetmed_drepano_input_html('drepano_nombre_cas_fratrie', $object->drepano_nombre_cas_fratrie, 'number', $edit_drepano, 'min="0" style="width:70px"');
    print '</td><td class="titlefield">'.$langs->trans('Details').'</td><td>';
    print cabinetmed_drepano_textarea_html('drepano_note_cas_fratrie', $object->drepano_note_cas_fratrie, $edit_drepano, 2);
    print '</td></tr>';

    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans('PersonalHistory').'</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('FeedingAndBreastfeeding').'</td><td>';
    if ($is_pediatrie) {
        print nl2br(dol_escape_htmltag($object->note_alimentation));
        print '<br><span class="opacitymedium">'.$langs->trans('SharedWithPostnatal').'</span>';
    } else {
        print cabinetmed_drepano_textarea_html('note_alimentation', $object->note_alimentation, $edit_drepano, 3);
    }
    print '</td><td class="titlefield">'.$langs->trans('FeedingDisorders').'</td><td>';
    print cabinetmed_drepano_textarea_html('drepano_troubles_alimentaires', $object->drepano_troubles_alimentaires, $edit_drepano, 3);
    print '</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('PostnatalSchooling').'</td><td>';
    if ($is_pediatrie) {
        print nl2br(dol_escape_htmltag($object->note_scolarite));
        print '<br><span class="opacitymedium">'.$langs->trans('SharedWithPostnatal').'</span>';
    } else {
        print cabinetmed_drepano_textarea_html('note_scolarite', $object->note_scolarite, $edit_drepano, 2);
    }
    print '</td><td class="titlefield">'.$langs->trans('ProfessionalActivity').'</td><td>';
    print cabinetmed_drepano_textarea_html('drepano_activite_professionnelle', $object->drepano_activite_professionnelle, $edit_drepano, 2);
    print '</td></tr>';

    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans('Vaccinations').'</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('VaccinationPEV').'</td><td>';
    if ($is_pediatrie) {
        if (!empty($object->statut_vaccination_pev) && isset($sharedpevstatuses[$object->statut_vaccination_pev])) print dol_escape_htmltag($sharedpevstatuses[$object->statut_vaccination_pev]).'<br>';
        print nl2br(dol_escape_htmltag($object->note_vaccination_pev));
        print '<br><span class="opacitymedium">'.$langs->trans('SharedWithPostnatal').'</span>';
    } else {
        print cabinetmed_drepano_select_html('statut_vaccination_pev', $object->statut_vaccination_pev, $sharedpevstatuses, $edit_drepano).'<br>';
        print cabinetmed_drepano_textarea_html('note_vaccination_pev', $object->note_vaccination_pev, $edit_drepano, 2);
    }
    print '</td><td class="titlefield">'.$langs->trans('LastKnownDate').'</td><td>';
    print cabinetmed_drepano_input_html('drepano_vaccination_pev_date', $object->drepano_vaccination_pev_date, 'date', $edit_drepano, '');
    print '</td></tr>';

    $vaccines=array(
        'antityphique'=>'VaccinationTyphoid',
        'pneumocoque'=>'VaccinationPneumococcal',
        'meningocoque'=>'VaccinationMeningococcal'
    );
    foreach ($vaccines as $vaccinekey=>$vaccinelabel) {
        print '<tr><td class="titlefield">'.$langs->trans($vaccinelabel).'</td><td>';
        print cabinetmed_drepano_select_html('drepano_vaccination_'.$vaccinekey.'_statut', $object->{'drepano_vaccination_'.$vaccinekey.'_statut'}, $drepvaccinestatuses, $edit_drepano);
        print ' &nbsp; '.cabinetmed_drepano_input_html('drepano_vaccination_'.$vaccinekey.'_date', $object->{'drepano_vaccination_'.$vaccinekey.'_date'}, 'date', $edit_drepano, '');
        print '</td><td class="titlefield">'.$langs->trans('Observations').'</td><td>';
        print cabinetmed_drepano_textarea_html('drepano_vaccination_'.$vaccinekey.'_note', $object->{'drepano_vaccination_'.$vaccinekey.'_note'}, $edit_drepano, 2);
        print '</td></tr>';
    }
    print '<tr><td class="titlefield">'.$langs->trans('OtherVaccine').'</td><td>';
    print cabinetmed_drepano_input_html('drepano_vaccination_autre_libelle', $object->drepano_vaccination_autre_libelle, 'text', $edit_drepano, 'placeholder="'.$langs->trans('SpecifyVaccine').'" style="width:95%"');
    print '<br>'.cabinetmed_drepano_select_html('drepano_vaccination_autre_statut', $object->drepano_vaccination_autre_statut, $drepvaccinestatuses, $edit_drepano);
    print ' &nbsp; '.cabinetmed_drepano_input_html('drepano_vaccination_autre_date', $object->drepano_vaccination_autre_date, 'date', $edit_drepano, '');
    print '</td><td class="titlefield">'.$langs->trans('Observations').'</td><td>';
    print cabinetmed_drepano_textarea_html('drepano_vaccination_autre_note', $object->drepano_vaccination_autre_note, $edit_drepano, 2);
    print '</td></tr>';

    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans('MedicalSurgicalEvolution').'</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('CVO12Months').'</td><td>';
    print cabinetmed_drepano_input_html('drepano_cvo_12_mois', $object->drepano_cvo_12_mois, 'number', $edit_drepano, 'min="0" style="width:90px"');
    print '</td><td class="titlefield">'.$langs->trans('Hospitalizations12Months').'</td><td>';
    print cabinetmed_drepano_input_html('drepano_hospitalisations_12_mois', $object->drepano_hospitalisations_12_mois, 'number', $edit_drepano, 'min="0" style="width:90px"');
    print '</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('AcuteComplications').'</td><td>';
    print cabinetmed_drepano_textarea_html('drepano_complications_aigues', $object->drepano_complications_aigues, $edit_drepano, 3);
    print '</td><td class="titlefield">'.$langs->trans('ChronicComplications').'</td><td>';
    print cabinetmed_drepano_textarea_html('drepano_complications_chroniques', $object->drepano_complications_chroniques, $edit_drepano, 3);
    print '</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('MedicalSurgicalHistory').'</td><td colspan="3">';
    print cabinetmed_drepano_textarea_html('drepano_antecedents_medicochirurgicaux', $object->drepano_antecedents_medicochirurgicaux, $edit_drepano, 3);
    print '</td></tr>';

    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans('Transfusions').'</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('TransfusionHistory').'</td><td>';
    print cabinetmed_drepano_select_html('drepano_transfusion_statut', $object->drepano_transfusion_statut, $yesnooptions, $edit_drepano);
    print '</td><td class="titlefield">'.$langs->trans('LastTransfusionDate').'</td><td>';
    print cabinetmed_drepano_input_html('drepano_derniere_transfusion_date', $object->drepano_derniere_transfusion_date, 'date', $edit_drepano, '');
    print '</td></tr>';
    print '<tr><td class="titlefield">'.$langs->trans('ApproximateTransfusionCount').'</td><td>';
    print cabinetmed_drepano_input_html('drepano_nombre_transfusions', $object->drepano_nombre_transfusions, 'number', $edit_drepano, 'min="0" style="width:90px"');
    print '</td><td class="titlefield">'.$langs->trans('TransfusionNotes').'</td><td>';
    print cabinetmed_drepano_textarea_html('drepano_note_transfusions', $object->drepano_note_transfusions, $edit_drepano, 2);
    print '</td></tr>';
    print '</table></div>';


    print '<div class="fichecenter"><div class="fichehalfleft">';
    print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';

    print '<tr height="80"><td class="tdtop titlefield">'.$langs->trans("AntecedentsChirGene");
    print '<br><input type="checkbox" name="alert_antechirgen"'.((isset($_POST['alert_antechirgen'])?GETPOST('alert_antechirgen'):$object->alert_antechirgen)?' checked="checked"':'').'"> '.$langs->trans("Alert");
    print '</td>';
    print '<td class="tdtop">';
    if ($action == 'edit' && $user->rights->societe->creer)
    {
        print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

        // Editeur wysiwyg
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('note_antechirgen',$object->note_antechirgen,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,8,'95%');
        $doleditor->Create();
    }
    else
    {
        print nl2br($object->note_antechirgen);
    }
    print "</td>";
    //pritn "</tr>";

    print '</tr></table>';
    print '</div><div class="fichehalfright"><div class="ficheaddleft" style="margin-top: auto">';
    print '<table class="border" width="100%" style="margin-bottom: 2px !important;"><tr height="80">';

    // Intolerances
    //print '<tr height="80">';
    print '<td class="tdtop titlefield">'.$langs->trans("Intolerances");
    print '<br><input type="checkbox" name="alert_traitintol"'.((isset($_POST['alert_traitintol'])?GETPOST('alert_traitintol'):$object->alert_traitintol)?' checked="true"':'').'"> '.$langs->trans("Alert");
    print '</td>';
    print '<td class="tdtop">';
    if ($action == 'edit' && $user->rights->societe->creer)
    {
        print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

        // Editeur wysiwyg
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('note_traitintol',$object->note_traitintol,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,8,'95%');
        $doleditor->Create();
    }
    else
    {
        print nl2br($object->note_traitintol);
    }
    print "</td></tr>";

    print '</table>';
    print '</div></div></div>';


    print '<div class="fichecenter"><div class="fichehalfleft">';
    print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';

    print '<tr height="80"><td class="tdtop titlefield">'.$langs->trans("AntecedentsChirOrtho");
    print '<br><input type="checkbox" name="alert_antechirortho"'.((isset($_POST['alert_antechirortho'])?GETPOST('alert_antechirortho'):$object->alert_antechirortho)?' checked="checked"':'').'"> '.$langs->trans("Alert");
    print '</td>';
    print '<td class="tdtop">';
    if ($action == 'edit' && $user->rights->societe->creer)
    {
        print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

        // Editeur wysiwyg
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('note_antechirortho',$object->note_antechirortho,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,6,'95%');
        $doleditor->Create();
    }
    else
    {
        print nl2br($object->note_antechirortho);
    }
    print "</td>";
    //print "</tr>";

    print '</tr></table>';
    print '</div><div class="fichehalfright"><div class="ficheaddleft" style="margin-top: auto">';
    print '<table class="border" width="100%" style="margin-bottom: 2px !important;"><tr height="80">';

    //print '<tr height="80">';
    print '<td class="tdtop titlefield">'.$langs->trans("Allergies");
    print '<br><input type="checkbox" name="alert_traitallergie"'.((isset($_POST['alert_traitallergie'])?GETPOST('alert_traitallergie'):$object->alert_traitallergie)?' checked="checked"':'').'""> '.$langs->trans("Alert");
    print '</td>';
    print '<td class="tdtop">';
    if ($action == 'edit' && $user->rights->societe->creer)
    {
        print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

        // Editeur wysiwyg
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('note_traitallergie',$object->note_traitallergie,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,6,'95%');
        $doleditor->Create();
    }
    else
    {
        print nl2br($object->note_traitallergie);
    }
    print "</td></tr>";

    print '</table>';
    print '</div></div></div>';


    print '<div class="fichecenter"><div class="fichehalfleft">';
    print '<table class="border" width="100%" style="margin-bottom: 2px !important;">';

    print '<tr height="80"><td class="tdtop titlefield">'.$langs->trans("AntecedentsRhumato");
    print '<br><input type="checkbox" name="alert_anterhum"'.((isset($_POST['alert_anterhum'])?GETPOST('alert_anterhum'):$object->alert_anterhum)?' checked="checked"':'').'"> '.$langs->trans("Alert");
    print '</td>';
    print '<td class="tdtop">';
    if ($action == 'edit' && $user->rights->societe->creer)
    {
        print "<input type=\"hidden\" name=\"socid\" value=\"".$object->id."\">";

        // Editeur wysiwyg
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('note_anterhum',$object->note_anterhum,0,$height,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled,6,'95%');
        $doleditor->Create();
    }
    else
    {
        print nl2br($object->note_anterhum);
    }
    print "</td>";

    print '</tr></table>';
    print '</div><div class="fichehalfright"><div class="ficheaddleft" style="margin-top: auto">';
    /*print '<table class="border" width="100%"><tr height="100%">';

    print '<td colspan="2">&nbsp;</td>';
    print "</tr>";

    print '</table>';*/
    print '</div></div></div>';

    print '<div class="fichecenter"></div>';

    if ($action == 'edit')
    {
        print '<center><br><input type="submit" class="button ignorechange" value="'.$langs->trans("Save").'"></center>';
    }

    print '</form>';
}

print '</div>';


/*
 * Boutons actions
*/
if ($action == '')
{
    print '<div class="tabsAction">';

    if ($user->rights->societe->creer)
    {
        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
    }

    print '</div>';
}


dol_htmloutput_mesg($mesg);


llxFooter();

$db->close();
