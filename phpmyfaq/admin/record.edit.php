<?php
/**
* $Id: record.edit.php,v 1.51 2006-11-10 22:38:55 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
* @license      Mozilla Public License 1.1
* @copyright    (c) 2001-2006 phpMyFAQ Team
*
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the "License"); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
*
* Software distributed under the License is distributed on an "AS IS"
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

// Re-evaluate $user
$user = PMF_CurrentUser::getFromSession($faqconfig->get('ipcheck'));

if ($permission["editbt"] && !emptyTable(SQLPREFIX."faqcategories")) {
    $category = new PMF_Category($LANGCODE);
    $category->buildTree();
    
    $current_category   = '';
    $categories         = array('category_id', 'category_lang');
    $faqData = array(
        'id'            => 0,
        'lang'          => $LANGCODE,
        'revision_id'   => 0,
        'title'         => '');
    $tagging = new PMF_Tags($db, $LANGCODE);

    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "takequestion") {

        $id_question = intval( $_REQUEST['id']);
        $query_questions = sprintf('SELECT ask_rubrik, ask_content FROM %sfaqquestions WHERE id = %d', SQLPREFIX, $id_question);
        $result_questions = $db->query($query_questions);
        $row = $db->fetch_object($result_questions);
        $current_category = $row->ask_rubrik;
        $faqData['title'] = $row->ask_content;
        $categories = array(array('category_id' => $current_category, 'category_lang' => $faqData['lang']));
    }

    if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "editpreview") {

        if (isset($_REQUEST["id"]) && $_REQUEST["id"] != "") {
            $faqData['id'] = (int)$_REQUEST["id"];
            $url_variables = 'saveentry&amp;id='.$faqData['id'];
        } else {
            $url_variables = 'insertentry';
        }
        $faqData['lang'] = $_REQUEST["lang"];
        $current_category = isset($_POST['rubrik']) ? $_POST['rubrik'] : null;
        if (is_array($current_category)) {
            foreach ($current_category as $cats) {
                $categories[] = array('category_id' => $cats, 'category_lang' => $faqData['lang']);
            }
        }
        $faqData['active']      = $_REQUEST["active"];
        $faqData['keywords']    = $_REQUEST["keywords"];
        $faqData['title']       = $_REQUEST["thema"];
        $faqData['content']     = $_REQUEST["content"];
        $faqData['author']      = $_REQUEST["author"];
        $faqData['email']       = $_REQUEST["email"];
        $faqData['comment']     = $_REQUEST["comment"];
        $faqData['solution_id'] = (int)$_REQUEST['solution_id'];
        $faqData['revision_id'] = isset($_REQUEST['revision_id']) ? $_REQUEST['revision_id'] : 0;
        $tags                   = $_REQUEST["tags"];
        $changed                = $_REQUEST["changed"];
        if (isset($_REQUEST['dateStart'])) {
            $faqData['dateStart'] = $_REQUEST['dateStart'];
        }
        if (isset($_REQUEST['dateEnd'])) {
            $faqData['dateEnd'] = $_REQUEST['dateEnd'];
        }

    } elseif (isset($_REQUEST["action"]) && $_REQUEST["action"] == "editentry") {

        if ((!isset($current_category) && !isset($faqData['title'])) || (isset($_GET['id']) && $_GET['id'] != '')) {
            adminlog("Beitragedit, ".$_GET['id']);
            $faqData['id'] = (int)$_GET['id'];
            $faqData['lang'] = $_GET["lang"];

            // Get the category
            $resultCategory = $db->query('SELECT category_id, category_lang FROM '.SQLPREFIX.'faqcategoryrelations WHERE record_id = '.$faqData['id'].' AND record_lang = \''.$faqData['lang'].'\'');
            while ($row = $db->fetch_object($resultCategory)) {
                $categories[] = array('category_id' => $row->category_id, 'category_lang' => $row->category_lang);
            }

            // Get the record
            $faq->language = $faqData['lang'];
            $faq->getRecord($faqData['id'], null, true);
            $faqData = $faq->faqRecord;
            $tags = implode(' ', $tagging->getAllTagsById($faqData['id']));
            $url_variables = 'saveentry&amp;id='.$_REQUEST['id'];

        } else {
            $url_variables = 'insertentry';
        }

    } else {
        adminlog('Beitragcreate');
        $url_variables = 'insertentry';
        if (!is_array($categories)) {
            $categories = array();
        }
    }

    // Revisions
    if (isset($_REQUEST['revisionid_selected'])){
        $revisionid_selected = $_REQUEST['revisionid_selected'];
    } elseif (isset($faqData['revision_id'])) {
        $revisionid_selected = $faqData['revision_id'];
    }

    print '<h2>'.$PMF_LANG["ad_entry_edit_1"];
    if (0 != $faqData['id']) {
        print ' <span style="color: Red;">'.$faqData['id'].' ('.$PMF_LANG['ad_entry_revision'].' 1.'.$revisionid_selected.') </span> ';
    }
    print ' '.$PMF_LANG["ad_entry_edit_2"].'</h2>';

    if ($permission["changebtrevs"]){

        $revisions = $faq->getRevisionIds($faqData['id'], $faqData['lang']);
        if (count($revisions)) {
?>
    <form id="selectRevision" name="selectRevision" action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;action=editentry&amp;id=<?php print $faqData['id']; ?>&amp;lang=<?php print $faqData['lang']; ?>" method="post" />
    <fieldset>
    <legend><?php print $PMF_LANG['ad_changerev']; ?></legend>
        <select name="revisionid_selected" onChange="selectRevision.submit();" />
            <option value="<?php print $faqData['revision_id']; ?>"><?php print $PMF_LANG['ad_changerev']; ?></option>
<?php foreach ($revisions as $_revision_id => $_revision_data) { ?>
            <option value="<?php print $_revision_data['revision_id']; ?>" <?php if ($revisionid_selected == $_revision_data['revision_id']) { print 'selected="selected"'; } ?> ><?php print $PMF_LANG['ad_entry_revision'].' 1.'.$_revision_data['revision_id'].': '.makeDate($_revision_data['datum'])." - ".$_revision_data['author']; ?></option>
<?php } ?>
        </select>
    </fieldset>
    </form>
    <br />
<?php
        }

        if (isset($revisionid_selected) && isset($faqData['revision_id']) && $revisionid_selected != $faqData['revision_id']) {
            $faq->language = $faqData['lang'];
            $faqData = $faq->getRecord($faqData['id'], $revisionid_selected, true);
            $tags = implode(' ', $tagging->getAllTagsById($faqData['id']));
        }
    }

?>

    <form style="float: left;" action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;action=<?php print $url_variables; ?>" method="post">
    <input type="hidden" name="revision_id" id="revision_id" value="<?php print $faqData['revision_id']; ?>" />
    <input type="hidden" name="record_id" id="record_id" value="<?php print $faqData['id']; ?>" />

    <fieldset>
    <legend><?php print $PMF_LANG['ad_entry_faq_record']; ?></legend>

    <label class="lefteditor" for="rubrik"><?php print $PMF_LANG["ad_entry_category"]; ?></label>
    <select name="rubrik[]" id="rubrik" size="5" multiple="multiple">
<?php print $category->printCategoryOptions($categories); ?>
    </select><br />

    <label class="lefteditor" for="thema"><?php print $PMF_LANG["ad_entry_theme"]; ?></label>
    <textarea name="thema" id="thema" style="width: 390px; height: 50px;" cols="2" rows="50"><?php if (isset($faqData['title'])) { print $faqData['title']; } ?></textarea><br />

    <label for="content"><?php print $PMF_LANG["ad_entry_content"]; ?></label>
    <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript><textarea id="content" name="content" cols="84" rows="10"><?php if (isset($faqData['content'])) { print trim(htmlspecialchars($faqData['content'])); } ?></textarea><br />

<?php
    if ($permission["addatt"]) {
?>
    <label><?php print $PMF_LANG["ad_att_att"]; ?></label>
<?php
        if (isset($faqData['id']) && $faqData['id'] != "") {
?>
        <strong><?php print "../attachments/".$faqData['id']."/" ?></strong><br />
<?php
            if (@is_dir(PMF_ROOT_DIR."/attachments/".$faqData['id']."/")) {
                $do = dir(PMF_ROOT_DIR."/attachments/".$faqData['id']."/");
                while ($dat = $do->read()) {
                    if ($dat != "." && $dat != "..") {
                        print "<a href=\""."../attachments/".$faqData['id']."/".$dat."\">".$dat."</a>";
                        if ($permission["delatt"]) {
                            print "[&amp;<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;action=delatt&amp;id=".$faqData['id']."&amp;which=".rawurlencode($dat)."&amp;lang=".$faqData['lang']."\">".$PMF_LANG["ad_att_del"]."</a>&amp;]";
                        }
                        print "<br />\n";
                    }
                }
            } else {
                print "<br />\n";
                print "<em>".$PMF_LANG["ad_att_none"]."</em> ";
            }
            print "<a href=\"#\" onclick=\"Picture('attachment.php?id=".$faqData['id']."&amp;rubrik=".$current_category."', 'Attachment', 400,80)\">".$PMF_LANG["ad_att_add"]."</a>";
        } else {
            print "&nbsp;".$PMF_LANG["ad_att_nope"];
        }
?><br />

<?php
    }
?>

    <label class="lefteditor" for="keywords"><?php print $PMF_LANG["ad_entry_keywords"]; ?></label>
    <input name="keywords" id="keywords" style="width: 390px;" value="<?php if (isset($faqData['keywords'])) { print htmlspecialchars($faqData['keywords']); } ?>" /><br />

    <label class="lefteditor" for="tags"><?php print $PMF_LANG['ad_entry_tags']; ?>:</label>
    <input name="tags" id="tags" style="width: 390px;" value="<?php if (isset($tags)) { print htmlspecialchars($tags); } ?>" /><img style="display: none; margin-bottom: -5px;" id="tags_autocomplete_wait" src="images/indicator.gif" alt="waiting..."></img>
    <div id="tags_autocomplete_choices"></div><br />
    <script type="text/javascript">
        new Ajax.Autocompleter("tags", "tags_autocomplete_choices", "index.php?action=ajax&ajax=tags_list",
                                {
                                    paramName: "autocomplete",
                                    tokens: " ",
                                    minChars: 1,
                                    indicator: "tags_autocomplete_wait",
                                }
        );
    </script>

    <label class="lefteditor" for="author"><?php print $PMF_LANG["ad_entry_author"]; ?></label>
    <input name="author" id="author" style="width: 390px;" value="<?php if (isset($faqData['author'])) { print htmlspecialchars($faqData['author']); } else { print $user->getUserData('display_name'); } ?>" /><br />

    <label class="lefteditor" for="email"><?php print $PMF_LANG["ad_entry_email"]; ?></label>
    <input name="email" id="email" style="width: 390px;" value="<?php if (isset($faqData['email'])) { print htmlspecialchars($faqData['email']); } else { print $user->getUserData('email'); } ?>" /><br />

    </fieldset>

    <fieldset>
    <legend><?php print $PMF_LANG['ad_entry_record_administration']; ?></legend>

    <label class="left" for="language"><?php print $PMF_LANG["ad_entry_locale"]; ?>:</label>
    <?php print selectLanguages($faqData['lang']); ?><br />

    <label class="left" for="solution_id"><?php print $PMF_LANG['ad_entry_solution_id']; ?>:</label>
    <input name="solution_id" id="solution_id" style="width: 50px; text-align: right;" value="<?php print (isset($faqData['solution_id']) ? $faqData['solution_id'] : $faq->getSolutionId()); ?>" size="5" readonly="readonly" /><br />

<?php
    if (isset($faqData['active']) && $faqData['active'] == 'yes') {
        $suf = ' checked="checked"';
        unset($sul);
    } else {
        unset($suf);
        $sul = ' checked="checked"';
    }
?>
    <label class="left" for="active"><?php print $PMF_LANG["ad_entry_active"]; ?></label>
    <input type="radio" name="active" class="active" value="yes"<?php if (isset($suf)) { print $suf; } ?> /> <?php print $PMF_LANG["ad_gen_yes"]; ?> <input type="radio" name="active" class="active" value="no"<?php if (isset($sul)) { print $sul; } ?> /> <?php print $PMF_LANG["ad_gen_no"]; ?><br />

    <label class="left" for="comment"><?php print $PMF_LANG["ad_entry_allowComments"]; ?></label>
    <input type="checkbox" name="comment" id="comment" value="y"<?php if (isset($faqData['comment']) && $faqData['comment'] == "y") { print " checked"; } ?> /> <?php print $PMF_LANG["ad_gen_yes"]; ?><br />
<?php
    if ($url_variables != 'insertentry') {
        $rev_yes = ' checked="checked"';
        unset($rev_no);
    }
    if (isset($faqData['active']) && $faqData['active'] == 'no') {
        $rev_no = ' checked="checked"';
        unset($rev_yes);
    }
    if ($url_variables != 'insertentry') {
?>
    <label class="left" for="revision"><?php print $PMF_LANG['ad_entry_new_revision']; ?></label>
    <input type="radio" name="revision" class="active" value="yes"<?php print isset($rev_yes) ? $rev_yes : ''; ?>/> <?php print $PMF_LANG["ad_gen_yes"]; ?> <input type="radio" name="revision" class="active" value="no"<?php print isset($rev_no) ? $rev_no : ''; ?>/> <?php print $PMF_LANG["ad_gen_no"]; ?><br />
<?php
    }
?>

    <label class="left" for="userpermission"><?php print $PMF_LANG['ad_entry_userpermission']; ?></label>
    <input type="radio" name="userpermission" class="active" value="all"<?php print isset($userpermission_all) ? $userpermission_all : ''; ?>/> <?php print $PMF_LANG['ad_entry_all_users']; ?> <input type="radio" name="userpermission" class="active" value="restricted"<?php print isset($userpermission_restricted) ? $userpermission_restricted : ''; ?>/> <?php print $PMF_LANG['ad_entry_restricted_users']; ?> <select name="restricted_users" size="1"><?php print $user->getAllUserOptions(1); ?></select><br />

<?php
    if ($groupSupport) {
?>    
    <label class="left" for="grouppermission"><?php print $PMF_LANG['ad_entry_grouppermission']; ?></label>
    <input type="radio" name="grouppermission" class="active" value="all"<?php print isset($grouppermission_all) ? $grouppermission_all : ''; ?>/> <?php print $PMF_LANG['ad_entry_all_groups']; ?> <input type="radio" name="grouppermission" class="active" value="restricted"<?php print isset($grouppermission_restricted) ? $grouppermission_restricted : ''; ?>/> <?php print $PMF_LANG['ad_entry_restricted_groups']; ?> <select name="restricted_groups" size="1"><?php print $user->getAllUserOptions(1); ?></select><br />

<?php
    }
?>
    </fieldset>

    <fieldset>
    <legend><?php print $PMF_LANG['ad_record_expiration_window']; ?></legend>
        <label class="lefteditor" for="from"><?php print $PMF_LANG['ad_news_from']; ?></label>
<?php
    $dateStartAv = isset($faqData['dateStart']) && ($faqData['dateStart'] != '00000000000000');
    $date['YYYY'] = $dateStartAv ? substr($faqData['dateStart'],  0, 4) : '';
    $date['MM']   = $dateStartAv ? substr($faqData['dateStart'],  4, 2) : '';
    $date['DD']   = $dateStartAv ? substr($faqData['dateStart'],  6, 2) : '';
    $date['HH']   = $dateStartAv ? substr($faqData['dateStart'],  8, 2) : '';
    $date['mm']   = $dateStartAv ? substr($faqData['dateStart'], 10, 2) : '';
    $date['ss']   = $dateStartAv ? substr($faqData['dateStart'], 12, 2) : '';
    print(printDateTimeInput('dateStart', $date));
?>
        <br />

        <label class="lefteditor" for="to"><?php print $PMF_LANG['ad_news_to']; ?></label>
<?php
    $dateEndAv = isset($faqData['dateEnd']) && ($faqData['dateEnd'] != '99991231235959');
    $date['YYYY'] = $dateEndAv ? substr($faqData['dateEnd'],  0, 4) : '';
    $date['MM']   = $dateEndAv ? substr($faqData['dateEnd'],  4, 2) : '';
    $date['DD']   = $dateEndAv ? substr($faqData['dateEnd'],  6, 2) : '';
    $date['HH']   = $dateEndAv ? substr($faqData['dateEnd'],  8, 2) : '';
    $date['mm']   = $dateEndAv ? substr($faqData['dateEnd'], 10, 2) : '';
    $date['ss']   = $dateEndAv ? substr($faqData['dateEnd'], 12, 2) : '';
    print(printDateTimeInput('dateEnd', $date));
?>
    </fieldset>

    <fieldset>
    <legend><?php print $PMF_LANG['ad_entry_changelog']; ?></legend>

    <label class="lefteditor"><?php print $PMF_LANG["ad_entry_date"]; ?></label>
    <?php if (isset($faqData['date'])) { print $faqData['date']; } else { print makeDate(date("YmdHis")); } ?><br />

    <label class="lefteditor" for="changed"><?php print $PMF_LANG["ad_entry_changed"]; ?></label>
    <textarea name="changed" id="changed" style="width: 390px; height: 50px;" cols="40" rows="4"><?php if (isset($changed)) { print $changed; } ?></textarea><br />

    </fieldset><br />

<?php
    if ($revisionid_selected == $faqData['revision_id']) {
?>
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_save"]; ?>" name="submit[1]" />
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_preview"]; ?>" name="submit[2]" />
    <input class="submit" type="reset" value="<?php print $PMF_LANG["ad_gen_reset"]; ?>" />
<?php
    }
    if ($url_variables != "insertentry") {
?>
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_delete"]; ?>" name="submit[0]" />
<?php
    }
?>
<br />
<?php
    if (is_numeric($faqData['id'])) {

        $_user = array(0 => 'n/a');
        $_result = $db->query("SELECT user_id, display_name FROM ".SQLPREFIX."faquserdata");
        while ($row = $db->fetch_object($_result)) {
            $_user[$row->user_id] = $row->display_name;
        }
?>
    <h3><?php print $PMF_LANG["ad_entry_changelog"]; ?></h3>
<?php
        $result = $db->query("SELECT revision_id, usr, datum, what FROM ".SQLPREFIX."faqchanges WHERE beitrag = ".$faqData['id']." ORDER BY id DESC");
        while ($row = $db->fetch_object($result)) {
?>
    <div style="font-size: 10px;"><strong><?php print date("Y-m-d H:i:s", $row->datum).": ".$_user[$row->usr]; ?></strong><br /><?php print PMF_htmlentities($row->what, ENT_NOQUOTES, $PMF_LANG['metaCharset']); ?><br /><?php print $PMF_LANG['ad_entry_revision'].' 1.'.$row->revision_id; ?></div>
<?php
        }
?>
    </form>
<?php
    $result = $db->query("SELECT id, id_comment, usr, email, comment, datum FROM ".SQLPREFIX."faqcomments WHERE id = ".$faqData['id']." ORDER BY datum DESC");
        if ($db->num_rows($result) > 0) {
?>
    <p><strong><?php print $PMF_LANG["ad_entry_comment"] ?></strong></p>
<?php
            while ($row = $db->fetch_object($result)) {
?>
    <p><?php print $PMF_LANG["ad_entry_commentby"] ?> <a href="mailto:<?php print $row->email; ?>"><?php print $row->usr; ?></a>:<br /><?php print $row->comment; ?><br /><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;action=delcomment&amp;artid=<?php print $row->id; ?>&amp;cmtid=<?php print $row->id_comment; ?>&amp;lang=<?php print $faqData['lang']; ?>"><img src="images/delete.gif" alt="<?php print $PMF_LANG["ad_entry_delete"] ?>" title="<?php print $PMF_LANG["ad_entry_delete"] ?>" border="0" width="17" height="18" align="right" /></a></p>
<?php
            }
        }
    }
} elseif ($permission["editbt"] != 1 && !emptyTable(SQLPREFIX."faqcategories")) {
    print $PMF_LANG["err_NotAuth"];
} elseif ($permission["editbt"] && emptyTable(SQLPREFIX."faqcategories")) {
    print $PMF_LANG["no_cats"];
}
