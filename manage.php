<?php // charset=ISO-8859-1

$lang = 'en';
$homedir = './';
$treeroot = '../';
$dirpermission = 0705;
# $newfilepermission = 0666;
# $uploadedfilepermission = 0666;
$editrows = 20;
$editcols = 70;
$quota = FALSE;
$quotacmd = '/usr/bin/quota -v';
$self = htmlentities(basename($_SERVER['PHP_SELF']));
$homedir = relpathtoabspath($homedir, getcwd());
$treeroot = relpathtoabspath($treeroot, getcwd());
$words = getWords($lang);
if (ini_get('magic_quotes_gpc')) {
        array_walk($_GET, 'strip');
        array_walk($_POST, 'strip');
        array_walk($_REQUEST, 'strip');
}
if (isset($_GET['imageid'])) {
        header('Content-Type: image/gif');
        echo (getImage($_GET['imageid']));
        exit;
}
ini_set('session.use_cookies', FALSE);
ini_set('session.use_trans_sid', FALSE);
session_name('id');
session_start();
$error = $notice = '';
$updatetreeview = FALSE;
if (isset($_REQUEST['action'])) {
        switch ($_REQUEST['action']) {
                case 'treeon':
                        $_SESSION['tree'] = array();
                        $_SESSION['hassubdirs'][$treeroot] = tree_hassubdirs($treeroot);
                        tree_plus($_SESSION['tree'], $_SESSION['hassubdirs'], $treeroot);
                        frameset();
                        exit;
                case 'treeoff':
                        $_SESSION['tree'] = NULL;
                        $_SESSION['hassubdirs'] = NULL;
                        dirlisting();
                        exit;
        }
}
if (!isset($_SESSION['dir'])) {
        $_SESSION['dir'] = $homedir;
        $updatetreeview = TRUE;
}
if (!empty($_REQUEST['dir'])) {
        $newdir = relpathtoabspath($_REQUEST['dir'], $_SESSION['dir']);
        if (@is_file($newdir) && @is_readable($newdir)) {
                /* if (@is_writable($newdir)) {
                        $_REQUEST['edit'] = $newdir;
                } else */
                if (is_script($newdir)) {
                        $_GET['showh'] = $newdir;
                } else {
                        $_GET['show'] = $newdir;
                }
        } elseif ($_SESSION['dir'] != $newdir) {
                $_SESSION['dir'] = $newdir;
                $updatetreeview = TRUE;
        }
}
if (!empty($_GET['show'])) {
        $show = relpathtoabspath($_GET['show'], $_SESSION['dir']);
        if (!show($show)) {
                $error = buildphrase('&quot;<b>' . htmlentities($show) . '</b>&quot;', $words['cantbeshown']);
        } else {
                exit;
        }
}
if (!empty($_GET['showh'])) {
        $showh = relpathtoabspath($_GET['showh'], $_SESSION['dir']);
        if (!show_highlight($showh)) {
                $error = buildphrase('&quot;<b>' . htmlentities($showh) . '</b>&quot;', $words['cantbeshown']);
        } else {
                exit;
        }
}
if (isset($_FILES['upload'])) {
        $file = relpathtoabspath($_FILES['upload']['name'], $_SESSION['dir']);
        if (@is_writable($_SESSION['dir']) && @move_uploaded_file($_FILES['upload']['tmp_name'], $file) && (!isset($uploadedfilepermission) || chmod($file, $uploadedfilepermission))) {
                $notice = buildphrase(array('&quot;<b>' . htmlentities(basename($file)) . '</b>&quot;', '&quot;<b>' . htmlentities($_SESSION['dir']) . '</b>&quot;'), $words['uploaded']);
        } else {
                $error = buildphrase(array('&quot;<b>' . htmlentities(basename($file)) . '</b>&quot;', '&quot;<b>' . htmlentities($_SESSION['dir']) . '</b>&quot;'), $words['notuploaded']);
        }
}
if (!empty($_GET['create']) && $_GET['type'] == 'file') {
        $file = relpathtoabspath($_GET['create'], $_SESSION['dir']);
        if (substr($file, strlen($file) - 1, 1) == '/') $file = substr($file, 0, strlen($file) - 1);
        if (is_free($file) && touch($file) && ((!isset($newfilepermission)) || chmod($file, $newfilepermission))) {
                $notice = buildphrase('&quot;<b>' . htmlentities($file) . '</b>&quot;', $words['created']);
                $_REQUEST['edit'] = $file;
        } else {
                $error = buildphrase('&quot;<b>' . htmlentities($file) . '</b>&quot;', $words['notcreated']);
        }
}
if (!empty($_GET['create']) && $_GET['type'] == 'dir') {
        $file = relpathtoabspath($_GET['create'], $_SESSION['dir']);
        if (is_free($file) && @mkdir($file, $dirpermission)) {
                $notice = buildphrase('&quot;<b>' . htmlentities($file) . '</b>&quot;', $words['created']);
                $updatetreeview = TRUE;
                if (!empty($_SESSION['tree'])) {
                        $file = spath(dirname($file));
                        $_SESSION['hassubdirs'][$file] = TRUE;
                        tree_plus($_SESSION['tree'], $_SESSION['hassubdirs'], $file);
                }
        } else {
                $error = buildphrase('&quot;<b>' . htmlentities($file) . '</b>&quot;', $words['notcreated']);
        }
}
if (!empty($_GET['symlinktarget']) && empty($_GET['symlink'])) {
        $symlinktarget = relpathtoabspath($_GET['symlinktarget'], $_SESSION['dir']);
        html_header($words['createsymlink']);
        ?>
        <form action="<?php echo ($self); ?>" method="get">
                <input type="hidden" name="id" value="<?php echo (session_id()); ?>">
                <input type="hidden" name="symlinktarget" value="<?php echo (htmlentities($_GET['symlinktarget'])); ?>">
                <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                                <td bgcolor="#888888">
                                        <table border="0" cellspacing="1" cellpadding="4">
                                                <tr>
                                                        <td bgcolor="#EEEEEE" align="center"><b><?php echo (htmlentities($_SERVER['SERVER_NAME'])); ?></b></td>
                                                        <td bgcolor="#EEEEEE" align="center"><?php echo (htmlentities($_SERVER['SERVER_SOFTWARE'])); ?></td>
                                                </tr>
                                                <tr>
                                                        <td colspan="2" bgcolor="#EEEEEE">
                                                                <table border="0">
                                                                        <tr>
                                                                                <td valign="top"><?php echo ($words['target']); ?>:&nbsp;</td>
                                                                                <td>
                                                                                        <b><?php echo (htmlentities($_GET['symlinktarget'])); ?></b><br>
                                                                                        <input type="checkbox" name="relative" value="yes" id="checkbox_relative" checked>
                                                                                        <label for="checkbox_relative"><?php echo ($words['reltarget']); ?></label>
                                                                                </td>
                                                                        </tr>
                                                                        <tr>
                                                                                <td><?php echo ($words['symlink']); ?>:&nbsp;</td>
                                                                                <td><input type="text" name="symlink" value="<?php echo (htmlentities(spath(dirname($symlinktarget)))); ?>" size="<?php $size = strlen($_GET['symlinktarget']) + 9;
                                                                                                                                                                                                        if ($size < 30) $size = 30;
                                                                                                                                                                                                        echo ($size);  ?>"></td>
                                                                        </tr>
                                                                        <tr>
                                                                                <td>&nbsp;</td>
                                                                                <td><input type="submit" value="<?php echo ($words['create']); ?>"></td>
                                                                        </tr>
                                                                </table>
                                                        </td>
                                                </tr>
                                        </table>
                                </td>
                        </tr>
                </table>
        </form>
        <?php
        html_footer();
        exit;
}
if (!empty($_GET['symlink']) && !empty($_GET['symlinktarget'])) {
        $symlink = relpathtoabspath($_GET['symlink'], $_SESSION['dir']);
        $target = $_GET['symlinktarget'];
        if (@is_dir($symlink)) $symlink = spath($symlink) . basename($target);
        if ($symlink == $target) {
                $error = buildphrase(array('&quot;<b>' . htmlentities($symlink) . '</b>&quot;', '&quot;<b>' . htmlentities($target) . '</b>&quot;'), $words['samefiles']);
        } else {
                if (@$_GET['relative'] == 'yes') {
                        $target = abspathtorelpath(dirname($symlink), $target);
                } else {
                        $target = $_GET['symlinktarget'];
                }
                if (is_free($symlink) && @symlink($target, $symlink)) {
                        $notice = buildphrase('&quot;<b>' . htmlentities($symlink) . '</b>&quot;', $words['created']);
                } else {
                        $error = buildphrase('&quot;<b>' . htmlentities($symlink) . '</b>&quot;', $words['notcreated']);
                }
        }
}
if (!empty($_GET['delete'])) {
        $delete = relpathtoabspath($_GET['delete'], $_SESSION['dir']);
        if (@$_GET['sure'] == 'TRUE') {
                if (remove($delete)) {
                        $notice = buildphrase('&quot;<b>' . htmlentities($delete) . '</b>&quot;', $words['deleted']);
                } else {
                        $error = buildphrase('&quot;<b>' . htmlentities($delete) . '</b>&quot;', $words['notdeleted']);
                }
        } else {
                html_header($words['delete']);
                ?>
                <p>
                        <table border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                        <td bgcolor="#888888">
                                                <table border="0" cellspacing="1" cellpadding="4">
                                                        <tr>
                                                                <td bgcolor="#EEEEEE" align="center"><b><?php echo (htmlentities($_SERVER['SERVER_NAME'])); ?></b></td>
                                                                <td bgcolor="#EEEEEE" align="center"><?php echo (htmlentities($_SERVER['SERVER_SOFTWARE'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                                <td colspan="2" bgcolor="#FFFFFF"><?php echo (buildphrase('&quot;<b>' . htmlentities($delete) . '</b>&quot;', $words['suredelete'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                                <td colspan="2" align="center" bgcolor="#EEEEEE">
                                                                        <a href="<?php echo ("$self?" . SID . '&delete=' . urlencode($delete) . '&sure=TRUE'); ?>">[ <?php echo ($words['yes']); ?> ]</a>
                                                                </td>
                                                        </tr>
                                                </table>
                                        </td>
                                </tr>
                        </table>
                </p>
                <?php
                html_footer();
                exit;
        }
}
if (!empty($_GET['permission'])) {
        $permission = relpathtoabspath($_GET['permission'], $_SESSION['dir']);
        if ($p = @fileperms($permission)) {
                if (!empty($_GET['set'])) {
                        $p = 0;
                        if (isset($_GET['ur'])) $p |= 0400;
                        if (isset($_GET['uw'])) $p |= 0200;
                        if (isset($_GET['ux'])) $p |= 0100;
                        if (isset($_GET['gr'])) $p |= 0040;
                        if (isset($_GET['gw'])) $p |= 0020;
                        if (isset($_GET['gx'])) $p |= 0010;
                        if (isset($_GET['or'])) $p |= 0004;
                        if (isset($_GET['ow'])) $p |= 0002;
                        if (isset($_GET['ox'])) $p |= 0001;
                        if (@chmod($_GET['permission'], $p)) {
                                $notice = buildphrase(array('&quot<b>' . htmlentities($permission) . '</b>&quot;', '&quot;<b>' . substr(octtostr("0$p"), 1) . '</b>&quot; (<b>' . decoct($p) . '</b>)'), $words['permsset']);
                        } else {
                                $error = buildphrase('&quot;<b>' . htmlentities($permission) . '</b>&quot;', $words['permsnotset']);
                        }
                } else {
                        html_header($words['permission']);
                        ?>
                        <form action="<?php echo ($self); ?>" method="get">
                                <input type="hidden" name="id" value="<?php echo (session_id()); ?>">
                                <table border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                                <td bgcolor="#888888">
                                                        <table border="0" cellspacing="1" cellpadding="4">
                                                                <tr>
                                                                        <td bgcolor="#EEEEEE" align="center"><b><?php echo (htmlentities($_SERVER['SERVER_NAME'])); ?></b></td>
                                                                        <td bgcolor="#EEEEEE" align="center"><?php echo (htmlentities($_SERVER['SERVER_SOFTWARE'])); ?></td>
                                                                </tr>
                                                                <tr>
                                                                        <td bgcolor="#EEEEEE" colspan="2">
                                                                                <table>
                                                                                        <tr>
                                                                                                <td><?php echo ($words['file']); ?>:</td>
                                                                                                <td><input type="text" name="permission" value="<?php echo (htmlentities($permission)); ?>" size="<?php echo (textfieldsize($permission)); ?>"></td>
                                                                                                <td><input type="submit" value="<?php echo ($words['change']); ?>"></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td valign="top">
                                                                                                        <?php echo ($words['permission']); ?>:&nbsp;
                        </form>
                        <form action="<?php echo ($self); ?>" method="get">
                                <input type="hidden" name="id" value="<?php echo (session_id()); ?>">
                                <input type="hidden" name="permission" value="<?php echo (htmlentities($permission)); ?>">
                                <input type="hidden" name="set" value="TRUE">
                                </td>
                                <td colspan="2">
                                        <table border="0">
                                                <tr>
                                                        <td>&nbsp;</td>
                                                        <td><?php echo ($words['owner']); ?></td>
                                                        <td><?php echo ($words['group']); ?></td>
                                                        <td><?php echo ($words['other']); ?></td>
                                                </tr>
                                                <tr>
                                                        <td><?php echo ($words['read']); ?>:</td>
                                                        <td align="center"><input type="checkbox" name="ur" value="1" <?php if ($p & 00400) echo (' checked'); ?>></td>
                                                        <td align="center"><input type="checkbox" name="gr" value="1" <?php if ($p & 00040) echo (' checked'); ?>></td>
                                                        <td align="center"><input type="checkbox" name="or" value="1" <?php if ($p & 00004) echo (' checked'); ?>></td>
                                                </tr>
                                                <tr>
                                                        <td><?php echo ($words['write']); ?>:</td>
                                                        <td align="center"><input type="checkbox" name="uw" value="1" <?php if ($p & 00200) echo (' checked'); ?>></td>
                                                        <td align="center"><input type="checkbox" name="gw" value="1" <?php if ($p & 00020) echo (' checked'); ?>></td>
                                                        <td align="center"><input type="checkbox" name="ow" value="1" <?php if ($p & 00002) echo (' checked'); ?>></td>
                                                </tr>
                                                <tr>
                                                        <td><?php echo ($words['exec']); ?>:</td>
                                                        <td align="center"><input type="checkbox" name="ux" value="1" <?php if ($p & 00100) echo (' checked'); ?>></td>
                                                        <td align="center"><input type="checkbox" name="gx" value="1" <?php if ($p & 00010) echo (' checked'); ?>></td>
                                                        <td align="center"><input type="checkbox" name="ox" value="1" <?php if ($p & 00001) echo (' checked'); ?>></td>
                                                </tr>
                                        </table>
                                </td>
                                </tr>
                                <tr>
                                        <td>&nbsp;</td>
                                        <td colspan="2"><input type="submit" value="<?php echo ($words['setperms']); ?>"></td>
                                </tr>
                                </table>
                                </td>
                                </tr>
                                </table>
                                </td>
                                </tr>
                                </table>
                        </form>
                        <?php
                        html_footer();
                        exit;
                }
        } else {
                $error = buildphrase('&quot;<b>' . htmlentities($permission) . '</b>&quot;', $words['permsnotset']);
        }
}
if (!empty($_GET['move'])) {
        $move = relpathtoabspath($_GET['move'], $_SESSION['dir']);
        if (!empty($_GET['destination'])) {
                $destination = relpathtoabspath($_GET['destination'], dirname($move));
                if (@is_dir($destination)) $destination = spath($destination) . basename($move);
                if ($move == $destination) {
                        $error = buildphrase(array('&quot;<b>' . htmlentities($move) . '</b>&quot;', '&quot;<b>' . htmlentities($destination) . '</b>&quot;'), $words['samefiles']);
                } else {
                        if (is_free($destination) && @rename($move, $destination)) {
                                $notice = buildphrase(array('&quot;<b>' . htmlentities($move) . '</b>&quot;', '&quot;<b>' . htmlentities($destination) . '</b>&quot;'), $words['moved']);
                        } else {
                                $error = buildphrase(array('&quot;<b>' . htmlentities($move) . '</b>&quot;', '&quot;<b>' . htmlentities($destination) . '</b>&quot;'), $words['notmoved']);
                        }
                }
        } else {
                html_header($words['move']);
                ?>
                <form action="<?php echo ($self); ?>" method="get">
                        <input type="hidden" name="id" value="<?php echo (session_id()); ?>">
                        <input type="hidden" name="move" value="<?php echo (htmlentities($move)); ?>">
                        <table border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                        <td bgcolor="#888888">
                                                <table border="0" cellspacing="1" cellpadding="4">
                                                        <tr>
                                                                <td bgcolor="#EEEEEE" align="center"><b><?php echo (htmlentities($_SERVER['SERVER_NAME'])); ?></b></td>
                                                                <td bgcolor="#EEEEEE" align="center"><?php echo (htmlentities($_SERVER['SERVER_SOFTWARE'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                                <td colspan="2" bgcolor="#EEEEEE">
                                                                        <table border="0">
                                                                                <tr>
                                                                                        <td><?php echo ($words['file']); ?>:&nbsp;</td>
                                                                                        <td><b><?php echo (htmlentities($move)); ?></b></td>
                                                                                </tr>
                                                                                <tr>
                                                                                        <td><?php echo ($words['moveto']); ?>:&nbsp;</td>
                                                                                        <td><input type="text" name="destination" value="<?php echo (htmlentities(spath(dirname($move)))); ?>" size="<?php echo (textfieldsize($move)); ?>"></td>
                                                                                </tr>
                                                                                <tr>
                                                                                        <td>&nbsp;</td>
                                                                                        <td><input type="submit" value="<?php echo ($words['move']); ?>"></td>
                                                                                </tr>
                                                                        </table>
                                                                </td>
                                                        </tr>
                                                </table>
                                        </td>
                                </tr>
                        </table>
                </form>
                <?php
                html_footer();
                exit;
        }
}
if (!empty($_GET['cpy'])) {
        $copy = relpathtoabspath($_GET['cpy'], $_SESSION['dir']);
        if (!empty($_GET['destination'])) {
                $destination = relpathtoabspath($_GET['destination'], dirname($copy));
                if (@is_dir($destination)) $destination = spath($destination) . basename($copy);
                if ($copy == $destination) {
                        $error = buildphrase(array('&quot;<b>' . htmlentities($copy) . '</b>&quot;', '&quot;<b>' . htmlentities($destination) . '</b>&quot;'), $words['samefiles']);
                } else {
                        if (is_free($destination) && @copy($copy, $destination)) {
                                $notice = buildphrase(array('&quot;<b>' . htmlentities($copy) . '</b>&quot;', '&quot;<b>' . htmlentities($destination) . '</b>&quot;'), $words['copied']);
                        } else {
                                $error = buildphrase(array('&quot;<b>' . htmlentities($copy) . '</b>&quot;', '&quot;<b>' . htmlentities($destination) . '</b>&quot;'), $words['notcopied']);
                        }
                }
        } else {
                html_header($words['copy']);
                ?>

                <form action="<?php echo ($self); ?>" method="get">
                        <input type="hidden" name="id" value="<?php echo (session_id()); ?>">
                        <input type="hidden" name="cpy" value="<?php echo (htmlentities($copy)); ?>">
                        <table border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                        <td bgcolor="#888888">
                                                <table border="0" cellspacing="1" cellpadding="4">
                                                        <tr>
                                                                <td bgcolor="#EEEEEE" align="center"><b><?php echo (htmlentities($_SERVER['SERVER_NAME'])); ?></b></td>
                                                                <td bgcolor="#EEEEEE" align="center"><?php echo (htmlentities($_SERVER['SERVER_SOFTWARE'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                                <td colspan="2" bgcolor="#EEEEEE">
                                                                        <table border="0">
                                                                                <tr>
                                                                                        <td><?php echo ($words['file']); ?>:&nbsp;</td>
                                                                                        <td><b><?php echo (htmlentities($copy)); ?></b></td>
                                                                                </tr>
                                                                                <tr>
                                                                                        <td><?php echo ($words['copyto']); ?>:&nbsp;</td>
                                                                                        <td><input type="text" name="destination" value="<?php echo (htmlentities(spath(dirname($copy)))); ?>" size="<?php echo (textfieldsize($copy)); ?>"></td>
                                                                                </tr>
                                                                                <tr>
                                                                                        <td>&nbsp;</td>
                                                                                        <td><input type="submit" value="<?php echo ($words['copy']); ?>"></td>
                                                                                </tr>
                                                                        </table>
                                                                </td>
                                                        </tr>
                                                </table>
                                        </td>
                                </tr>
                        </table>
                </form>
                <?php
                html_footer();
                exit;
        }
}
if (!empty($_POST['edit']) && isset($_POST['save'])) {
        $edit = relpathtoabspath($_POST['edit'], $_SESSION['dir']);
        if ($f = @fopen($edit, 'w')) {
                fwrite($f, str_replace("\r\n", "\n", $_POST['content']));
                fclose($f);
                $notice = buildphrase('&quot;<b>' . htmlentities($edit) . '</b>&quot;', $words['saved']);
        } else {
                $error = buildphrase('&quot;<b>' . htmlentities($edit) . '</b>&quot;', $words['notsaved']);
        }
}
if (isset($_REQUEST['edit']) && !isset($_POST['save'])) {
        $file = relpathtoabspath($_REQUEST['edit'], $_SESSION['dir']);
        if (@is_dir($file)) {
                $_SESSION['dir'] = $file;
                $updatetreeview = TRUE;
        } else {
                if ($f = @fopen($file, 'r')) {
                        html_header($words['edit']);
                        ?>
                        <form action="<?php echo ($self); ?>" method="get">
                                <input type="hidden" name="id" value="<?php echo (session_id()); ?>">
                                <table border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                                <td bgcolor="#888888">
                                                        <table border="0" cellspacing="1" cellpadding="4">
                                                                <tr>
                                                                        <td bgcolor="#EEEEEE" align="center"><b><?php echo (htmlentities($_SERVER['SERVER_NAME'])); ?></b></td>
                                                                        <td bgcolor="#EEEEEE" align="center"><?php echo (htmlentities($_SERVER['SERVER_SOFTWARE'])); ?></td>
                                                                </tr>
                                                                <tr>
                                                                        <td bgcolor="#EEEEEE" colspan="2">
                                                                                <table border="0" cellspacing="0" cellpadding="0">
                                                                                        <tr>
                                                                                                <td><?php echo ($words['file']); ?>:&nbsp;</td>
                                                                                                <td><input type="text" name="edit" value="<?php echo (htmlentities($file)); ?>" size="<?php echo (textfieldsize($file)); ?>">&nbsp;</td>
                                                                                                <td><input type="submit" value="<?php echo ($words['change']); ?>"></td>
                                                                                        </tr>
                                                                                </table>
                                                                        </td>
                                                                </tr>
                                                        </table>
                                                </td>
                                        </tr>
                                </table>
                        </form>
                        <form action="<?php echo ($self); ?>" method="post" name="f">
                                <input type="hidden" name="id" value="<?php echo (session_id()); ?>">
                                <input type="hidden" name="edit" value="<?php echo (htmlentities($file)); ?>">
                                <table border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                                <td bgcolor="#888888">
                                                        <table border="0" cellspacing="1" cellpadding="4">
                                                                <tr>
                                                                        <td bgcolor="#EEEEFF" align="center"><textarea name="content" rows="<?php echo ($editrows); ?>" cols="<?php echo ($editcols); ?>" wrap="off" style="background: #EEEEFF; border: none;"><?php
                                                                                                                                                                                                                                                                if (isset($_POST['content'])) {
                                                                                                                                                                                                                                                                        echo (htmlentities($_POST['content']));
                                                                                                                                                                                                                                                                        if (isset($_POST['add']) && !empty($_POST['username']) && !empty($_POST['password'])) {
                                                                                                                                                                                                                                                                                echo ("\n" . htmlentities($_POST['username'] . ':' . crypt($_POST['password'])));
                                                                                                                                                                                                                                                                        }
                                                                                                                                                                                                                                                                } else {
                                                                                                                                                                                                                                                                        echo (htmlentities(fread($f, filesize($file))));
                                                                                                                                                                                                                                                                }
                                                                                                                                                                                                                                                                fclose($f);
                                                                                                                                                                                                                                                                ?></textarea></td>
                                                                </tr>
                                                                <?php if (basename($file) == '.htpasswd') { /* specials with .htpasswd */ ?>
                                                                        <tr>
                                                                                <td bgcolor="#EEEEEE" align="center">
                                                                                        <table border="0">
                                                                                                <tr>
                                                                                                        <td><?php echo ($words['username']); ?>:&nbsp;</td>
                                                                                                        <td><input type="text" name="username" size="15">&nbsp;</td>
                                                                                                        <td><?php echo ($words['password']); ?>:&nbsp;</td>
                                                                                                        <td><input type="password" name="password" size="15">&nbsp;</td>
                                                                                                        <td><input type="submit" name="add" value="<?php echo ($words['add']); ?>"></td>
                                                                                                </tr>
                                                                                        </table>
                                                                                </td>
                                                                        </tr>
                                                                <?php }
                                                                if (basename($file) == '.htaccess') { /* specials with .htaccess */ ?>
                                                                        <tr>
                                                                                <td bgcolor="#EEEEEE" align="center"><input type="button" value="<?php echo ($words['addauth']); ?>" onClick="autheinf()"></td>
                                                                        </tr>
                                                                <?php } ?>
                                                                <tr>
                                                                        <td bgcolor="#EEEEEE" align="center">
                                                                                <input type="button" value="<?php echo ($words['reset']); ?>" onClick="document.f.reset()">
                                                                                <input type="button" value="<?php echo ($words['clear']); ?>" onClick="void(document.f.content.value='')">
                                                                                <input type="submit" name="save" value="<?php echo ($words['save']); ?>">
                                                                        </td>
                                                                </tr>
                                                        </table>
                                                </td>
                                        </tr>
                                </table>
                        </form>
                        <?php
                        html_footer();
                        exit;
                } else {
                        $error = buildphrase('&quot;<b>' . htmlentities($file) . '</b>&quot; ', $words['notopened']);
                }
        }
}
if (!empty($_SESSION['tree'])) {
        if (isset($_REQUEST['frame']) && $_REQUEST['frame'] == 'treeview') {
                treeview();
        } else {
                if (isset($_GET['noupdate'])) $updatetreeview = FALSE;
                dirlisting(TRUE);
        }
} else {
        dirlisting();
}
function strip(&$str)
{
        $str = stripslashes($str);
}
function relpathtoabspath($file, $dir)
{
        $dir = spath($dir);
        if (substr($file, 0, 1) != '/') $file = $dir . $file;
        if (!@is_link($file) && ($r = realpath($file)) != FALSE) $file = $r;
        if (@is_dir($file) && !@is_link($file)) $file = spath($file);
        return $file;
}
function abspathtorelpath($pos, $target)
{
        $pos = spath($pos);
        $path = '';
        while ($pos != $target) {
                if ($pos == substr($target, 0, strlen($pos))) {
                        $path .= substr($target, strlen($pos));
                        break;
                } else {
                        $path .= '../';
                        $pos = strrev(strstr(strrev(substr($pos, 0, strlen($pos) - 1)), '/'));
                }
        }
        return $path;
}
function is_script($file)
{
        return ereg('.php[3-4]?$', $file);
}
function spath($path)
{
        if (substr($path, strlen($path) - 1, 1) != '/') $path .= '/';
        return $path;
}
function textfieldsize($str)
{
        $size = strlen($str) + 5;
        if ($size < 30) $size = 30;
        return $size;
}
function is_free($file)
{
        global $words;
        if (@file_exists($file) && empty($_GET['overwrite'])) {
                html_header($words['alreadyexists']);
                ?>
                <p>
                        <table border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                        <td bgcolor="#888888">
                                                <table border="0" cellspacing="1" cellpadding="4">
                                                        <tr>
                                                                <td bgcolor="#EEEEEE" align="center"><b><?php echo (htmlentities($_SERVER['SERVER_NAME'])); ?></b></td>
                                                                <td bgcolor="#EEEEEE" align="center"><?php echo (htmlentities($_SERVER['SERVER_SOFTWARE'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                                <td colspan="2" bgcolor="#FFFFFF"><?php echo (buildphrase('&quot;<b>' . htmlentities($file) . '</b>&quot;', $words['overwrite'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                                <td colspan="2" align="center" bgcolor="#EEEEEE">
                                                                        <a href="<?php echo ("{$_SERVER['REQUEST_URI']}&overwrite=yes"); ?>">[ <?php echo ($words['yes']); ?> ]</a>
                                                                </td>
                                                        </tr>
                                                </table>
                                        </td>
                                </tr>
                        </table>
                </p>
                <?php
                html_footer();
                exit;
        }
        if (!empty($_GET['overwrite'])) {
                return remove($file);
        }
        return TRUE;
}
function remove($file)
{
        global $updatetreeview;
        if (@is_dir($file) && !@is_link($file)) {
                $error = FALSE;
                if ($p = @opendir($file = spath($file))) {
                        while (($f = readdir($p)) !== FALSE)
                                if ($f != '.' && $f != '..' && !remove($file . $f))
                                        $error = TRUE;
                }
                if ($error) $x = FALSE;
                else $x = @rmdir($file);
                $updatetreeview = TRUE;
                if ($x && !empty($_SESSION['tree'])) {
                        $file = spath(dirname($file));
                        $_SESSION['hassubdirs'][$file] = tree_hassubdirs($file);
                        tree_plus($_SESSION['tree'], $_SESSION['hassubdirs'], $file, TRUE);
                }
        } else {
                $x = @unlink($file);
        }
        return $x;
}
function tree_hassubdirs($path)
{
        if ($p = @opendir($path)) {
                while (($filename = readdir($p)) !== FALSE) {
                        if (tree_isrealdir($path . $filename)) return TRUE;
                }
        }
        return FALSE;
}
function tree_isrealdir($path)
{
        if (basename($path) != '.' && basename($path) != '..' && @is_dir($path) && !@is_link($path)) return TRUE;
        else return FALSE;
}
function treeview()
{
        global $self, $treeroot;
        if (isset($_GET['plus']))        tree_plus($_SESSION['tree'], $_SESSION['hassubdirs'], $_GET['plus']);
        if (isset($_GET['minus']))        $dirchanged = tree_minus($_SESSION['tree'], $_SESSION['hassubdirs'], $_GET['minus']);
        else $dirchanged = FALSE;
        for ($d = $_SESSION['dir']; strlen($d = dirname($d)) != 1; tree_plus($_SESSION['tree'], $_SESSION['hassubdirs'], $d));
        ?>
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">
        <html>

        <head>
                <title>Treeview</title>
                <style type="text/css">
                        <!--
                        td {
                                font-family: sans-serif;
                                font-size: 10pt;
                        }

                        a:link,
                        a:visited,
                        a:active {
                                text-decoration: none;
                                color: #000088;
                        }

                        a:hover {
                                text-decoration: underline;
                                color: #000088;
                        }
                        -->
                </style>
        </head>

        <body bgcolor="#FFFFFF" <?php if ($dirchanged) echo (" onLoad=\"void(parent.webadmin.location.replace('$self?noupdate=TRUE&dir=" . urlencode($_SESSION['dir']) . '&' . SID . '&pmru=' . time() . "'))\""); ?>>
                <table border="0" cellspacing="0" cellpadding="0">
                        <?php
                        tree_showtree($_SESSION['tree'], $_SESSION['hassubdirs'], $treeroot, 0, tree_calculatenumcols($_SESSION['tree'], $treeroot, 0));
                        ?>
                </table>
        </body>

        </html>
        <?php
        return;
}

function frameset()
{
        global $self;
        ?>
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Frameset//EN">
        <html>

        <head>
                <title><?php echo ($self); ?></title>
        </head>
        <frameset cols="250,*">
                <frame src="<?php echo ("$self?frame=treeview&" . SID . '#' . urlencode($_SESSION['dir'])); ?>" name="treeview">
                        <frame src="<?php echo ("$self?" . SID); ?>" name="webadmin">
        </frameset>

        </html>
        <?php
        return;
}
function tree_calculatenumcols($tree, $path, $col)
{
        static $numcols = 0;
        if ($col > $numcols) $numcols = $col;
        if (isset($tree[$path])) {
                for ($i = 0; $i < sizeof($tree[$path]); $i++) {
                        $numcols = tree_calculatenumcols($tree, $path . $tree[$path][$i], $col + 1);
                }
        }
        return $numcols;
}
function tree_showtree($tree, $hassubdirs, $path, $col, $numcols)
{
        global $self, $treeroot;
        static $islast = array(0 => TRUE);
        echo ("        <tr>\n");
        for ($i = 0; $i < $col; $i++) {
                if ($islast[$i]) $iid = 0;
                else $iid = 3;
                echo ("                <td><img src=\"$self?imageid=$iid\" width=\"19\" height=\"18\"></td>\n");
        }
        if ($hassubdirs[$path]) {
                if (!empty($tree[$path])) {
                        $action = 'minus';
                        $iid = 8;
                } else {
                        $action = 'plus';
                        $iid = 7;
                }
                if ($col == 0) $iid -= 3;
                else if ($islast[$col]) $iid += 3;
                echo ("                <td><a href=\"$self?frame=treeview&$action=" . urlencode($path) . '&dir=' . urlencode($_SESSION['dir']) . '&' . SID . '#' . urlencode($path) . '">');
                echo ("<img src=\"$self?imageid=$iid\" width=\"19\" height=\"18\" border=\"0\">");
                echo ("</a></td>\n");
        } else {
                if ($islast[$col]) $iid = 9;
                else $iid = 6;
                echo ("                <td><img src=\"$self?imageid=$iid\" width=\"19\" height=\"18\"></td>\n");
        }
        if (@is_readable($path)) {
                $a1 = "<a name=\"" . urlencode($path) . "\" href=\"$self?dir=" . urlencode($path) . '&' . SID . '" target="webadmin">';
                $a2 = '</a>';
        } else {
                $a1 = $a2 = '';
        }
        if ($_SESSION['dir'] == $path) $iid = 2;
        else $iid = 1;
        echo ("                <td>$a1<img src=\"$self?imageid=$iid\" width=\"19\" height=\"18\" border=\"0\">$a2</td>\n");
        $cspan = $numcols - $col + 1;
        if ($cspan > 1) $colspan = " colspan=\"$cspan\"";
        else $colspan = '';
        if ($col == $numcols) $width = ' width="100%"';
        else $width = '';
        echo ("                <td$width $colspan nowrap>&nbsp;");
        if ($path == $treeroot) $label = $path;
        else $label = basename($path);
        echo ($a1 . htmlentities($label) . $a2);
        echo ("</td>\n");
        echo ("        </tr>\n");
        if (!empty($tree[$path])) {
                for ($i = 0; $i < sizeof($tree[$path]); $i++) {
                        if (($i + 1) == sizeof($tree[$path])) $islast[$col + 1] = TRUE;
                        else $islast[$col + 1] = FALSE;
                        tree_showtree($tree, $hassubdirs, $path . $tree[$path][$i], $col + 1, $numcols);
                }
        }
        return;
}
function tree_plus(&$tree, &$hassubdirs, $p)
{
        if ($path = spath(realpath($p))) {
                $tree[$path] = tree_getsubdirs($path);
                for ($i = 0; $i < sizeof($tree[$path]); $i++) {
                        $subdir = $path . $tree[$path][$i];
                        if (empty($hassubdirs[$subdir])) $hassubdirs[$subdir] = tree_hassubdirs($subdir);
                }
        }
        return;
}
function tree_minus(&$tree, &$hassubdirs, $p)
{
        $dirchanged = FALSE;
        if ($path = spath(realpath($p))) {
                if (!empty($tree[$path])) {
                        for ($i = 0; $i < sizeof($tree[$path]); $i++) {
                                $subdir = $path . $tree[$path][$i] . '/';
                                if (isset($hassubdirs[$subdir])) $hassubdirs[$subdir] = NULL;
                        }
                        $tree[$path] = NULL;
                        if (substr($_SESSION['dir'], 0, strlen($path)) == $path) {
                                $_SESSION['dir'] = $path;
                                $dirchanged = TRUE;
                        }
                }
        }
        return $dirchanged;
}
function tree_getsubdirs($path)
{
        $subdirs = array();
        if ($p = @opendir($path)) {
                for ($i = 0; ($filename = readdir($p)) !== FALSE;) {
                        if (tree_isrealdir($path . $filename)) $subdirs[$i++] = $filename . '/';
                }
        }
        sort($subdirs);
        return $subdirs;
}
function show($file)
{
        global $words;
        if (@is_readable($file) && @is_file($file)) {
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header('Content-Type: ' . getmimetype($file));
                header('Content-Disposition: attachment; filename=' . basename($file) . ';');
                if (@readfile($file) !== FALSE) return TRUE;
        }
        return FALSE;
}
function show_highlight($file)
{
        global $words;
        if (@is_readable($file) && @is_file($file)) {
                header('Content-Disposition: filename=' . basename($file));
                echo ("<html>\n<head><title>");
                echo (buildphrase(array('&quot;' . htmlentities(basename($file)) . '&quot;'), $words['sourceof']));
                echo ("</title></head>\n<body>\n<table cellpadding=\"4\" border=\"0\">\n<tr>\n<td>\n<code style=\"color: #999999\">\n");
                $size = sizeof(file($file));
                for ($i = 1; $i <= $size; $i++) printf("%05d<br>\n", $i);
                echo ("</code>\n</td>\n<td nowrap>\n");
                $shown = @highlight_file($file);
                echo ("\n");
                echo ("</td>\n</tr>\n</table>\n");
                echo ("</body>\n");
                echo ("</html>");
                if ($shown) return TRUE;
        }
        return FALSE;
}
function getmimetype($file)
{
        /* $mime = 'application/octet-stream'; */
        $mime = 'text/plain';
        $ext = substr($file, strrpos($file, '.') + 1);
        if (@is_readable('/etc/mime.types')) {
                $f = fopen('/etc/mime.types', 'r');
                while (!feof($f)) {
                        $line = fgets($f, 4096);
                        $found = FALSE;
                        $mim = strtok($line, " \n\t");
                        $ex = strtok(" \n\t");
                        while ($ex && !$found) {
                                if (strtolower($ex) == strtolower($ext)) {
                                        $found = TRUE;
                                        $mime = $mim;
                                        break;
                                }
                                $ex = strtok(" \n\t");
                        }
                        if ($found) break;
                }
                fclose($f);
        }
        return $mime;
}
function dirlisting($inaframe = FALSE)
{
        global $self, $homedir, $words;
        global $error, $notice;
        global $quota, $quotacmd;
        $p = '&' . SID;
        html_header($_SESSION['dir']);
        ?>
        <form action="<?php echo ($self); ?>" method="get">
                <input type="hidden" name="id" value="<?php echo (session_id()); ?>">
                <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                                <td bgcolor="#888888">
                                        <table border="0" cellspacing="1" cellpadding="4">
                                                <tr>
                                                        <td bgcolor="#EEEEEE" align="center"><b><?php echo (htmlentities($_SERVER['SERVER_NAME'])); ?></b></td>
                                                        <td bgcolor="#EEEEEE" align="center"><?php echo (htmlentities($_SERVER['SERVER_SOFTWARE'])); ?></td>
                                                </tr>
                                                <tr>
                                                        <td bgcolor="#EEEEEE" colspan="2">
                                                                <table border="0" cellspacing="0" cellpadding="0">
                                                                        <tr>
                                                                                <td><?php echo ("<a href=\"$self?dir=" . urlencode($homedir) . "$p\">" . $words['dir']); ?></a>:&nbsp;</td>
                                                                                <td><input type="text" name="dir" value="<?php echo (htmlentities($_SESSION['dir'])); ?>" size="<?php echo (textfieldsize($_SESSION['dir'])); ?>">&nbsp;</td>
                                                                                <td><input type="submit" value="<?php echo ($words['change']); ?>"></td>
                                                                        </tr>
                                                                </table>
                                                        </td>
                                                </tr>
                                        </table>
                                </td>
                        </tr>
                </table>
        </form>
        <?php
        if ($quota) {
                exec($quotacmd, $out, $retval);
                if ($retval == 0) pnotice("<pre>" . implode("\n", $out) . "</pre>");
        }
        if (@is_writable($_SESSION['dir'])) {
                ?>
                <form action="<?php echo ($self); ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="dir" value="<?php echo (htmlentities($_SESSION['dir'])); ?>">
                        <input type="hidden" name="id" value="<?php echo (session_id()); ?>">
                        <?php if (isset($_REQUEST['frame'])) { ?>
                                <input type="hidden" name="frame" value="<?php echo ($_REQUEST['frame']); ?>">
                        <?php } ?>
                        <table border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                        <td bgcolor="#888888">
                                                <table border="0" cellspacing="1" cellpadding="4">
                                                        <tr>
                                                                <td bgcolor="#EEEEEE">
                                                                        <table border="0" cellspacing="0" cellpadding="0">
                                                                                <tr>
                                                                                        <td><?php echo ($words['file']); ?>&nbsp;</td>
                                                                                        <td><input type="file" name="upload">&nbsp;</td>
                                                                                        <td><input type="submit" value="<?php echo ($words['upload']); ?>"></td>
                                                                                </tr>
                                                                        </table>
                                                                </td>
                                                        </tr>
                                                        <tr>
                                                                <td bgcolor="#EEEEEE">
                </form>
                <form action="<?php echo ($self); ?>" method="get">
                        <input type="hidden" name="dir" value="<?php echo (htmlentities($_SESSION['dir'])); ?>">
                        <input type="hidden" name="id" value="<?php echo (session_id()); ?>">
                        <?php if (isset($_REQUEST['frame'])) { ?>
                                <input type="hidden" name="frame" value="<?php echo ($_REQUEST['frame']); ?>">
                        <?php } ?>
                        <table border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                        <td>
                                                <select name="type" size="1">
                                                        <option value="file"><?php echo ($words['file']); ?>

                                                        <option value="dir" selected><?php echo ($words['dir']); ?>

                                                </select>&nbsp;
                                        </td>
                                        <td><input type="text" name="create">&nbsp;</td>
                                        <td><input type="submit" value="<?php echo ($words['create']); ?>"></td>
                                </tr>
                        </table>
                        </td>
                        </tr>
                        </table>
                        </td>
                        </tr>
                        </table>
                </form>
        <?php
        }
        if (empty($_GET['sort'])) $sort = 'filename';
        else $sort = $_GET['sort'];
        $reverse = @$_GET['reverse'];
        $GLOBALS['showsize'] = FALSE;
        if ($files = dirtoarray($_SESSION['dir'])) {
                $files = sortfiles($files, $sort, $reverse);
                outputdirlisting($_SESSION['dir'], $files, $inaframe, $sort, $reverse);
        } else {
                perror(buildphrase('&quot;<b>' . htmlentities($_SESSION['dir']) . '</b>&quot', $words['readingerror']));
        }
        if ($inaframe) {
                pnotice("<a href=\"$self?action=treeoff&" . SID . '" target="_top">' . $words['treeoff'] . '</a>');
        } else {
                pnotice("<a href=\"$self?action=treeon&" . SID . '" target="_top">' . $words['treeon'] . '</a>');
        }
        html_footer(FALSE);
        return;
}
function dirtoarray($dir)
{
        if ($dirstream = @opendir($dir)) {
                for ($n = 0; ($filename = readdir($dirstream)) !== FALSE; $n++) {
                        $stat = @lstat($dir . $filename);
                        $files[$n]['filename']     = $filename;
                        $files[$n]['fullfilename'] = $fullfilename = relpathtoabspath($filename, $dir);
                        $files[$n]['is_file']      = @is_file($fullfilename);
                        $files[$n]['is_dir']       = @is_dir($fullfilename);
                        $files[$n]['is_link']      = $islink = @is_link($dir . $filename);
                        if ($islink) {
                                $files[$n]['readlink'] = @readlink($dir . $filename);
                                $files[$n]['linkinfo'] = linkinfo($dir . $filename);
                        }
                        $files[$n]['is_readable']   = @is_readable($fullfilename);
                        $files[$n]['is_writable']   = @is_writable($fullfilename);
                        $files[$n]['is_executable'] = @is_executable($fullfilename);
                        $files[$n]['permission']    = $islink ? 'lrwxrwxrwx' : octtostr(@fileperms($dir . $filename));
                        if (substr($files[$n]['permission'], 0, 1) != '-') {
                                $files[$n]['size'] = -1;
                        } else {
                                $files[$n]['size'] = @$stat['size'];
                                $GLOBALS['showsize'] = TRUE;
                        }
                        $files[$n]['owner']         = $owner = @$stat['uid'];
                        $files[$n]['group']         = $group = @$stat['gid'];
                        $files[$n]['ownername']     = @reset(posix_getpwuid($owner));
                        $files[$n]['groupname']     = @reset(posix_getgrgid($group));
                }
                closedir($dirstream);
                return $files;
        } else {
                return FALSE;
        }
}
function outputdirlisting($dir, $files, $inaframe, $sort, $reverse)
{
        global $self, $words;
        $uid = posix_getuid();
        ?>
        <p>
                <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                                <td bgcolor="#888888">
                                        <table border="0" cellspacing="1" cellpadding="4">
                                                <?php
                                                if ($inaframe) $p = '&notreeupdate=TRUE&';
                                                $p = '';
                                                $p .= SID . '&dir=' . urlencode($dir);
                                                echo ("        <tr>\n");
                                                echo ("                <td bgcolor=\"#EEEEEE\"><img src=\"$self?imageid=16\" width=\"17\" height=\"13\"></td>\n");
                                                echo ("                <td bgcolor=\"#EEEEEE\"><a href=\"$self?sort=filename&reverse=" . (($sort == 'filename') ? !$reverse : 0) . "&$p\"><b>{$words['filename']}</b></a></td>\n");
                                                if ($GLOBALS['showsize']) echo ("                <td bgcolor=\"#EEEEEE\" align=\"right\"><a href=\"$self?sort=size&reverse=" . (($sort == 'size') ? !$reverse : 0) . "&$p\"><b>{$words['size']}</b></a></td>\n");
                                                echo ("                <td bgcolor=\"#EEEEEE\"><a href=\"$self?sort=permission&reverse=" . (($sort == 'permission') ? !$reverse : 0) . "&$p\"><b>{$words['permission']}</b></a></td>\n");
                                                echo ("                <td bgcolor=\"#EEEEEE\"><a href=\"$self?sort=owner&reverse=" . (($sort == 'owner') ? !$reverse : 0) . "&$p\"><b>{$words['owner']}</b></a></td>\n");
                                                echo ("                <td bgcolor=\"#EEEEEE\"><a href=\"$self?sort=group&reverse=" . (($sort == 'group') ? !$reverse : 0) . "&$p\"><b>{$words['group']}</b></a></td>\n");
                                                echo ("                <td bgcolor=\"#EEEEEE\"><b>{$words['functions']}</b></td>\n");
                                                echo ("        </tr>\n");
                                                $p = '&' . SID;
                                                if ($GLOBALS['showsize']) $cspan = ' colspan="2"';
                                                else $cspan = '';
                                                foreach ($files as $file) {
                                                        echo ("        <tr>\n");
                                                        if ($file['is_link']) {
                                                                echo ("                <td bgcolor=\"#FFFFFF\" align=\"center\"><img src=\"$self?imageid=14\" width=\"17\" height=\"13\"></td>\n");
                                                                echo ("                <td$cspan bgcolor=\"#FFFFFF\">");
                                                                if ($file['is_dir']) echo ('[ ');
                                                                echo ($file['filename']);
                                                                if ($file['is_dir']) echo (' ]');
                                                                echo (' -&gt; ');
                                                                if ($file['is_dir']) {
                                                                        echo ('[ ');
                                                                        if ($file['is_readable']) echo ("<a href=\"$self?dir=" . urlencode($file['readlink']) . "$p\">");
                                                                        echo (htmlentities($file['readlink']));
                                                                        if ($file['is_readable']) echo ('</a>');
                                                                        echo (' ]');
                                                                } else {
                                                                        if (dirname($file['readlink']) != '.') {
                                                                                if ($file['is_readable']) echo ("<a href=\"$self?dir=" . urlencode(dirname($file['readlink'])) . "$p\">");
                                                                                echo (htmlentities(dirname($file['readlink'])) . '/');
                                                                                if ($file['is_readable']) echo ('</a>');
                                                                        }
                                                                        if (strlen(basename($file['readlink'])) != 0) {
                                                                                if ($file['is_file'] && $file['is_readable']) echo ("<a href=\"$self?show=" . urlencode($file['readlink']) . "$p\">");
                                                                                echo (htmlentities(basename($file['readlink'])));
                                                                                if ($file['is_file'] && $file['is_readable']) echo ('</a>');
                                                                        }
                                                                        if ($file['is_file'] && is_script($file['readlink'])) echo (" <a href=\"$self?showh=" . urlencode($file['readlink']) . "$p\">*</a>");
                                                                }
                                                                echo ("</td>\n");
                                                        } elseif ($file['is_dir']) {
                                                                echo ("                <td bgcolor=\"#FFFFFF\" align=\"center\"><img src=\"$self?imageid=15\" width=\"17\" height=\"13\"></td>\n");
                                                                echo ("                <td$cspan bgcolor=\"#FFFFFF\">[ ");
                                                                if ($file['is_readable']) echo ("<a href=\"$self?dir=" . urlencode($file['fullfilename']) . "$p\">");
                                                                echo (htmlentities($file['filename']));
                                                                if ($file['is_readable']) echo ('</a>');
                                                                echo (" ]</td>\n");
                                                        } else {
                                                                echo ("                <td bgcolor=\"#FFFFFF\" align=\"center\"><img src=\"$self?imageid=");
                                                                if (substr($file['filename'], 0, 1) == '.') echo ('13');
                                                                else echo ('12');
                                                                echo ("\" width=\"17\" height=\"13\"></td>\n");
                                                                echo ('                <td');
                                                                if (substr($file['permission'], 0, 1) != '-') echo ($cspan);
                                                                echo (' bgcolor="#FFFFFF">');
                                                                if ($file['is_readable'] && $file['is_file']) echo ("<a href=\"$self?show=" . urlencode($file['fullfilename']) . "$p\">");
                                                                echo (htmlentities($file['filename']));
                                                                if ($file['is_readable'] && $file['is_file']) echo ('</a>');
                                                                if ($file['is_file'] && is_script($file['filename'])) echo (" <a href=\"$self?showh=" . urlencode($file['fullfilename']) . "$p\">*</a>");
                                                                echo ("</td>\n");
                                                                if ($GLOBALS['showsize'] && $file['is_file']) {
                                                                        echo ("                <td bgcolor=\"#FFFFFF\" align=\"right\" nowrap>");
                                                                        if ($file['is_file']) echo ("{$file['size']} B");
                                                                        echo ("</td>\n");
                                                                }
                                                        }
                                                        echo ('                <td bgcolor="#FFFFFF" class="perm">');
                                                        if ($uid == $file['owner'] && !$file['is_link']) echo ("<a href=\"$self?permission=" . urlencode($file['fullfilename']) . "$p\">");
                                                        echo ($file['permission']);
                                                        if ($uid == $file['owner'] && !$file['is_link']) echo ('</a>');
                                                        echo ("</td>\n");
                                                        $owner = ($file['ownername'] == NULL) ? $file['owner'] : $file['ownername'];
                                                        $group = ($file['groupname'] == NULL) ? $file['group'] : $file['groupname'];
                                                        echo ('                <td bgcolor="#FFFFFF">' . $owner . "</td>\n");
                                                        echo ('                <td bgcolor="#FFFFFF">' . $group . "</td>\n");
                                                        $f = "<a href=\"$self?symlinktarget=" . urlencode($dir . $file['filename']) . "$p\">{$words['createsymlink']}</a> | ";;
                                                        if ($file['filename'] != '.' && $file['filename'] != '..') {
                                                                if ($file['is_readable'] && $file['is_file']) {
                                                                        $f .= "<a href=\"$self?cpy=" . urlencode($file['fullfilename']) . "$p\">{$words['copy']}</a> | ";
                                                                }
                                                                if ($uid == $file['owner']) {
                                                                        $f .= "<a href=\"$self?move=" . urlencode($file['fullfilename']) . "$p\">{$words['move']}</a> | ";
                                                                        $f .= "<a href=\"$self?delete=" . urlencode($dir . $file['filename']) . "$p\">{$words['delete']}</a> | ";
                                                                }
                                                                if ($file['is_writable'] && $file['is_file']) {
                                                                        $f .= "<a href=\"$self?edit=" . urlencode($file['fullfilename']) . "$p\">{$words['edit']}</a> | ";
                                                                }
                                                        }
                                                        if ($file['is_dir'] && @is_file($file['fullfilename'] . '.htaccess') && @is_writable($file['fullfilename'] . '.htaccess')) {
                                                                $f .= "<a href=\"$self?edit=" . urlencode($file['fullfilename']) . '.htaccess' . "$p\">{$words['configure']}</a> | ";
                                                        }
                                                        if (!empty($f)) $f = substr($f, 0, strlen($f) - 3);
                                                        else $f = '&nbsp;';
                                                        echo ("                <td bgcolor=\"#FFFFFF\" nowrap>$f</td>\n");
                                                        echo ("        </tr>\n");
                                                }
                                                ?>
                                        </table>
                                </td>
                        </tr>
                </table>
        </p>
        <?php
        return;
}
function sortfiles($files, $sort, $reverse)
{
        $files = sortfield($files, $sort, $reverse, 0, sizeof($files) - 1);
        if ($sort != 'filename') {
                $old = $files[0][$sort];
                $oldpos = 0;
                for ($i = 1; $i < sizeof($files); $i++) {
                        if ($old != $files[$i][$sort]) {
                                if ($oldpos != ($i - 1)) $files = sortfield($files, 'filename', false, $oldpos, $i - 1);
                                $oldpos = $i;
                        }
                        $old = $files[$i][$sort];
                }
                if ($oldpos < ($i - 1)) $files = sortfield($files, 'filename', false, $oldpos, $i - 1);
        }
        return $files;
}
function octtostr($mode)
{
        if (($mode & 0xC000) === 0xC000) $type = 's'; /* Unix domain socket */
        elseif (($mode & 0x4000) === 0x4000) $type = 'd'; /* Directory */
        elseif (($mode & 0xA000) === 0xA000) $type = 'l'; /* Symbolic link */
        elseif (($mode & 0x8000) === 0x8000) $type = '-'; /* Regular file */
        elseif (($mode & 0x6000) === 0x6000) $type = 'b'; /* Block special file */
        elseif (($mode & 0x2000) === 0x2000) $type = 'c'; /* Character special file */
        elseif (($mode & 0x1000) === 0x1000) $type = 'p'; /* Named pipe */
        else                                 $type = '?'; /* Unknown */
        $owner  = ($mode & 00400) ? 'r' : '-';
        $owner .= ($mode & 00200) ? 'w' : '-';
        if ($mode & 0x800) $owner .= ($mode & 00100) ? 's' : 'S';
        else $owner .= ($mode & 00100) ? 'x' : '-';
        $group  = ($mode & 00040) ? 'r' : '-';
        $group .= ($mode & 00020) ? 'w' : '-';
        if ($mode & 0x400) $group .= ($mode & 00010) ? 's' : 'S';
        else $group .= ($mode & 00010) ? 'x' : '-';
        $other  = ($mode & 00004) ? 'r' : '-';
        $other .= ($mode & 00002) ? 'w' : '-';
        if ($mode & 0x200) $other .= ($mode & 00001) ? 't' : 'T';
        else $other .= ($mode & 00001) ? 'x' : '-';
        return $type . $owner . $group . $other;
}
function sortfield($field, $column, $reverse, $left, $right)
{
        $g = $field[(int) (($left + $right) / 2)][$column];
        $l = $left;
        $r = $right;
        while ($l <= $r) {
                if ($reverse) {
                        while (($l < $right) && ($field[$l][$column] > $g)) $l++;
                        while (($r > $left)  && ($field[$r][$column] < $g)) $r--;
                } else {
                        while (($l < $right) && ($field[$l][$column] < $g)) $l++;
                        while (($r > $left)  && ($field[$r][$column] > $g)) $r--;
                }
                if ($l < $r) {
                        $tmp = $field[$r];
                        $field[$r] = $field[$l];
                        $field[$l] = $tmp;
                        $r--;
                        $l++;
                } else {
                        $l++;
                }
        }
        if ($r > $left) $field = sortfield($field, $column, $reverse, $left, $r);
        if ($r + 1 < $right) $field = sortfield($field, $column, $reverse, $r + 1, $right);
        return $field;
}
function buildphrase($repl, $str)
{
        if (!is_array($repl)) $repl = array($repl);
        $newstr = '';
        $prevz = ' ';
        for ($i = 0; $i < strlen($str); $i++) {
                $z = substr($str, $i, 1);
                if (((int) $z) > 0 && ((int) $z) <= count($repl) && $prevz == ' ') $newstr .= $repl[((int) $z) - 1];
                else $newstr .= $z;
                $prevz = $z;
        }
        return $newstr;
}
function html_header($action)
{
        global $self;
        global $error, $notice, $updatetreeview;
        ?>
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">
        <html>

        <head>
                <title><?php echo ("$self - $action"); ?></title>
                <style type="text/css">
                        <!--
                        td {
                                font-family: sans-serif;
                                font-size: 10pt;
                        }

                        a:link,
                        a:visited,
                        a:active {
                                text-decoration: none;
                                color: #000088;
                        }

                        a:hover {
                                text-decoration: underline;
                                color: #000088;
                        }

                        .perm {
                                font-family: monospace;
                                font-size: 10pt;
                        }
                        -->
                </style>
                <?php
                if (isset($_REQUEST['edit']) && !isset($_POST['save']) && basename($edit = $_REQUEST['edit']) == '.htaccess') {
                        $file = dirname($edit) . '/.htpasswd';
                        ?>
                        <script type="text/javascript" language="JavaScript">
                                <!--
                                function autheinf() {
                                        document.f.content.value += "Authtype Basic\nAuthName \"Restricted Directory\"\n";
                                        document.f.content.value += "AuthUserFile <?php echo (htmlentities($file)); ?>\n";
                                        document.f.content.value += "Require valid-user";
                                }
                                //
                                -->
                        </script>
                <?php
                }
                ?>
        </head>

        <body bgcolor="#FFFFFF" <?php if ($updatetreeview && !empty($_SESSION['tree'])) echo (" onLoad=\"void(parent.treeview.location.replace('$self?frame=treeview&dir=" . urlencode($_SESSION['dir']) . '&' . SID . '&pmru=' . time() . '#' . urlencode($_SESSION['dir']) . "'))\""); ?>>
                <?php
                if (!empty($error)) perror($error);
                if (!empty($notice)) pnotice($notice);
                return;
        }
        function html_footer($backbutton = TRUE)
        {
                global $self, $words;
                if ($backbutton) {
                        ?>
                        <p>
                                <table border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                                <td bgcolor="#888888">
                                                        <table border="0" cellspacing="1" cellpadding="4">
                                                                <tr>
                                                                        <td bgcolor="#EEEEEE">
                                                                                <a href="<?php echo ("$self?id=" . $_REQUEST['id']); ?>"><?php echo ($words['back']); ?></a>
                                                                        </td>
                                                                </tr>
                                                        </table>
                                                </td>
                                        </tr>
                                </table>
                        </p>
                <?php
                }
                ?>
        </body>

        </html>
        <?php
        return;
}

function perror($str)
{
        ?>
        <p>
                <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                                <td bgcolor="#888888">
                                        <table border="0" cellspacing="1" cellpadding="4">
                                                <tr>
                                                        <td bgcolor="#FFCCCC">
                                                                <?php echo ("$str\n"); ?>
                                                        </td>
                                                </tr>
                                        </table>
                                </td>
                        </tr>
                </table>
        </p>
        <?php
        return;
}
function pnotice($str)
{
        ?>
        <p>
                <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                                <td bgcolor="#888888">
                                        <table border="0" cellspacing="1" cellpadding="4">
                                                <tr>
                                                        <td bgcolor="#CCFFCC">
                                                                <?php echo ("$str\n"); ?>
                                                        </td>
                                                </tr>
                                        </table>
                                </td>
                        </tr>
                </table>
        </p>
        <?php
        return;
}
function getImage($iid)
{
        switch ($iid) {
                case  1:
                        return "GIF89a\23\0\22\0\xa2\4\0\0\0\0\xff\xff\xff\xcc\xcc\xcc\x99\x99\x99\xff\xff\xff\0\0\0\0\0\0\0\0\0!\xf9\4\1\xe8\3\4\0,\0\0\0\0\23\0\22\0\0\3?H\xba\xdcN \xca\xd7@\xb8\30P%\xbb\x9f\x8b\x85\x8d\xa4\xa0q\x81\xa0\xae\xac:\x9cP\xda\xceo(\xcfl\x8d\xe2\xad\36\xf39\x98\5\xb8\xf2\r\x89\2cr\xc0l:\x990\xc8g\xba\xa9Z\xaf\xd8l5\1\0;\0";
                case  2:
                        return "GIF89a\23\0\22\0\x91\2\0\0\0\0\xcc\xcc\xcc\xff\xff\xff\0\0\0!\xf9\4\1\xe8\3\2\0,\0\0\0\0\23\0\22\0\0\x024\x94\x8f\xa9\2\xed\x9b@\x98\24@#\xa9v\xefd\rV^H\6\26fr\xea\xca\x98ehI\xdf;\xc53}6\xf4\x86\xee\xf5\xe83!V\xc4\xd3\xe5\x88L*\x97\x90\2\0;\0";
                case  3:
                        return "GIF89a\23\0\22\0\x80\1\0\x99\x99\x99\xff\xff\xff!\xf9\4\1\xe8\3\1\0,\0\0\0\0\23\0\22\0\0\2\32\x8co\x80\xcb\xed\xad\x9e\x9c,\xd2+-\xdeK\xf3\xef}[(^d9\x9dhP\0\0;\0";
                case  4:
                        return "GIF89a\23\0\22\0\x91\3\0\x99\x99\x99\xff\xff\xff\0\0\0\xff\xff\xff!\xf9\4\1\xe8\3\3\0,\0\0\0\0\23\0\22\0\0\2.\x9c\x8f\xa9\xcb\xed\xf\r\x98\x94:\20\xb2\xe\xe0j\xa1u\r\x96\x81\x99\xc8`\xc2\xbarC\x87\36d`\xba\xe3\27z\xdbyUU\4\xc\n\x87DF\1\0;\0";
                case  5:
                        return "GIF89a\23\0\22\0\x91\3\0\x99\x99\x99\xff\xff\xff\0\0\0\xff\xff\xff!\xf9\4\1\xe8\3\3\0,\0\0\0\0\23\0\22\0\0\2*\x9c\x8f\xa9\xcb\xed\xf\r\x98\x94:\20\xb2\xe\xe0n\xdd5\xd8\xc7y\xc2y\x96]\x88\x8c\37\xbb\xb8\33\xac\xc8\xe0UU\xd1\xce\xf7\xfe\xcf(\0\0;\0";
                case  6:
                        return "GIF89a\23\0\22\0\x80\1\0\x99\x99\x99\xff\xff\xff!\xf9\4\1\xe8\3\1\0,\0\0\0\0\23\0\22\0\0\2\33\x8co\x80\xcb\xed\xad\x9e\x9c,\xd2+-\xdeK\xf9\xf0q\x94&rd\x89\x9d\xe8\xb8>\5\0;\0";
                case  7:
                        return "GIF89a\23\0\22\0\x91\3\0\x99\x99\x99\xff\xff\xff\0\0\0\xff\xff\xff!\xf9\4\1\xe8\3\3\0,\0\0\0\0\23\0\22\0\0\x020\x9co\x80\xcb\xed\xad\x9e\x9c\t\xd8k%\x8\xbc\x87\xe8l\x9c\xd0\x81PGr&\xb3\t\xae\xfb\r\xca*\xa3\xa5f\xab\xb8\xa7?\xd8O\t\x86\x84\xc4a\x91R\0\0;\0";
                case  8:
                        return "GIF89a\23\0\22\0\x91\3\0\x99\x99\x99\xff\xff\xff\0\0\0\xff\xff\xff!\xf9\4\1\xe8\3\3\0,\0\0\0\0\23\0\22\0\0\2/\x9co\x80\xcb\xed\xad\x9e\x9c\t\xd8k%\x8\xbc\x87\xe8l\36\7B#\xa9\5\xc2\xba~\x83R&\xa7\xfb\x88c\xbc\xd8\36\x8e`>\5\xc\5\x87B\"\xa5\0\0;\0";
                case  9:
                        return "GIF89a\23\0\22\0\x80\1\0\x99\x99\x99\xff\xff\xff!\xf9\4\1\xe8\3\1\0,\0\0\0\0\23\0\22\0\0\2\30\x8co\x80\xcb\xed\xad\x9e\x9c,\xd2+-\xdeK\xf9\xf0q\xe2H\x96\xe6\x89r\5\0;\0";
                case 10:
                        return "GIF89a\23\0\22\0\x91\3\0\x99\x99\x99\xff\xff\xff\0\0\0\xff\xff\xff!\xf9\4\1\xe8\3\3\0,\0\0\0\0\23\0\22\0\0\2/\x9co\x80\xcb\xed\xad\x9e\x9c\t\xd8k%\x8\xbc\x87\xe8l\x9c\xd0\x81PGr&\xb3\t\xae\xfb\r\xca*\xa3\xa5f\xab\xb8\xa7?\xd8O\t\n\x87\xc4\xa2\xb0\0\0;\0";
                case 11:
                        return "GIF89a\23\0\22\0\x91\3\0\x99\x99\x99\xff\xff\xff\0\0\0\xff\xff\xff!\xf9\4\1\xe8\3\3\0,\0\0\0\0\23\0\22\0\0\2.\x9co\x80\xcb\xed\xad\x9e\x9c\t\xd8k%\x8\xbc\x87\xe8l\36\7B#\xa9\5\xc2\xba~\x83R&\xa7\xfb\x88c\xbc\xd8\36\x8e`>\5\xc\n\x87\xc4`\1\0;\0";
                case 12:
                        return "GIF89a\21\0\r\0\x91\3\0\x99\x99\x99\xff\xff\xff\0\0\0\xff\xff\xff!\xf9\4\1\xe8\3\3\0,\0\0\0\0\21\0\r\0\0\2-\x9c\x81\x89\xc6\r\1\xe3j\xec\x89+\xc2\3\xf4D\x99t\26\x86i\xe2\x87r\xd4Hf\xaa\x83~o\25\xb4\x97\xb9\xc6\xd2i\xbb\xa7\x8es(\x86\xaf\2\0;\0";
                case 13:
                        return "GIF89a\21\0\r\0\x91\3\0\xcc\0\0\xff\xff\xff\x99\x99\x99\xff\xff\xff!\xf9\4\1\xe8\3\3\0,\0\0\0\0\21\0\r\0\0\2-\x9c\x81\x89\xc6\r\1\xe3j\xec\x89+\xc2\3\xf4D\x99t\26\x86i\xe2\x87r\xd4Hf\xaa\x83~o\25\xb4\x97\xb9\xc6\xd2i\xbb\xa7\x8es(\x86\xaf\2\0;\0";
                case 14:
                        return "GIF89a\21\0\r\0\xa2\4\0\x99\x99\x99\xff\xff\xff\0\0\0\xcc\0\0\xff\xff\xff\0\0\0\0\0\0\0\0\0!\xf9\4\1\xe8\3\4\0,\0\0\0\0\21\0\r\0\0\x039H\n\xdc\xac0\x82@\xeb\x8bp\x8a-\xc2\4\xd8RYM8\r\3\xc5y&\x85\x8e,\x84\xces\xb0\xc5\nM\x8f 6\5/[\xa7'\1\xa6`\xc4\xcc\x883l\xc1,&\x87\x94\x98\0\0;\0";
                case 15:
                        return "GIF89a\21\0\r\0\x91\3\0\x99\x99\x99\xff\xff\xff\xcc\xcc\xcc\xff\xff\xff!\xf9\4\1\xe8\3\3\0,\0\0\0\0\21\0\r\0\0\2*\x9c\x8f\x99\xc0\xac\33b\4\xcf\xb4\x8b\x9d\x95\xbc\xb:\0\x81@\x96&\t\x8a\xe7\xfam\xec\x99\x8eo\31\xcf\xb4k\xb7a\x8e\36\xd9o(\0\0;\0";
                case 16:
                        return "GIF89a\21\0\r\0\x91\2\0\0\0\0\xff\xff\0\xff\xff\xff\0\0\0!\xf9\4\1\xe8\3\2\0,\0\0\0\0\21\0\r\0\0\2,\x94\x8f\xa9\2\xed\xb0\xc\x8\xb3\xd25\x83\xde\32\xa6\x076_\xd5P\xa5x\x94\34\x87J\xe4vzi\7wJf\xe22\x82\xb3\21\23\xfa\t\xf\5\0;\0";
                case  0:
                default:
                        return "GIF89a\23\0\22\0\x80\1\0\0\0\0\xff\xff\xff!\xf9\4\1\xe8\3\1\0,\0\0\0\0\23\0\22\0\0\2\20\x8c\x8f\xa9\xcb\xed\xf\xa3\x9c\xb4\xda\x8b\xb3\xde\xbc\xd7\2\0;\0";
        }
}
function getWords($lang)
{
        switch ($lang) {
                case 'de':
                        return array(
                                'dir' => 'Verzeichnis',
                                'file' => 'Datei',
                                'filename' => 'Dateiname',
                                'size' => 'Gr&ouml;&szlig;e',
                                'permission' => 'Rechte',
                                'functions' => 'Funktionen',
                                'owner' => 'Eigner',
                                'group' => 'Gruppe',
                                'other' => 'Andere',
                                'create' => 'erstellen',
                                'copy' => 'kopieren',
                                'copyto' => 'kopieren nach',
                                'move' => 'verschieben',
                                'moveto' => 'verschieben nach',
                                'delete' => 'l&ouml;schen',
                                'edit' => 'editieren',
                                'read' => 'lesen',
                                'write' => 'schreiben',
                                'exec' => 'ausf&uuml;hren',
                                'change' => 'wechseln',
                                'upload' => 'hochladen',
                                'configure' => 'konfigurieren',
                                'yes' => 'ja',
                                'no' => 'nein',
                                'back' => 'zur&uuml;ck',
                                'setperms' => 'Rechte setzen',
                                'readingerror' => 'Fehler beim Lesen von 1',
                                'permsset' => 'Die Rechte von 1 wurden auf 2 gesetzt.',
                                'permsnotset' => 'Die Rechte von 1 konnten nicht gesetzt werden.',
                                'uploaded' => '1 wurde nach 2 hochgeladen.',
                                'notuploaded' => '1 konnte nicht nach 2 hochgeladen werden.',
                                'moved' => '1 wurde nach 2 verschoben.',
                                'notmoved' => '1 konnte nicht nach 2 verschoben werden.',
                                'copied' => '1 wurde nach 2 kopiert.',
                                'notcopied' => '1 konnte nicht nach 2 kopiert werden.',
                                'created' => '1 wurde erstellt.',
                                'notcreated' => '1 konnte nicht erstellt werden.',
                                'deleted' => '1 wurde gel&ouml;scht.',
                                'notdeleted' => '1 konnte nicht gel&ouml;scht werden.',
                                'suredelete' => '1 wirklich l&ouml;schen?',
                                'saved' => '1 wurde gespeichert.',
                                'notsaved' => '1 konnte nicht gespeichert werden.',
                                'reset' => 'zur&uuml;cksetzen',
                                'clear' => 'verwerfen',
                                'save' => 'speichern',
                                'cantbeshown' => '1 kann nicht angezeigt werden.',
                                'sourceof' => 'Quelltext von 1',
                                'notopened' => '1 konnte nicht ge&ouml;ffnet werden.',
                                'addauth' => 'Standard-Authentifizierungseinstellungen hinzuf&uuml;gen',
                                'username' => 'Benutzername',
                                'password' => 'Kennwort',
                                'add' => 'hinzuf&uuml;gen',
                                'treeon' => 'Baumansicht aktivieren',
                                'treeoff' => 'Baumansicht deaktivieren',
                                'symlink' => 'Symbolischer Link',
                                'createsymlink' => 'Link erstellen',
                                'target' => 'Ziel',
                                'reltarget' => 'Relative Pfadangabe des Ziels',
                                'alreadyexists' => 'Die Datei existiert bereits.',
                                'overwrite' => 'Soll 1 &uuml;berschrieben werden?',
                                'samefiles' => '1 und 2 sind identisch.',
                        );

                case 'cz':
                        return array(
                                'dir' => 'Adres&#xE1;&#x0159;',
                                'file' => 'Soubor',
                                'filename' => 'Jm&#xE9;no souboru',
                                'size' => 'Velikost',
                                'permission' => 'Pr&#xE1;va',
                                'functions' => 'Functions',
                                'owner' => 'Vlastn&#xED;k',
                                'group' => 'Skupina',
                                'other' => 'Ostatn&#xED;',
                                'create' => 'vytvo&#x0159;it',
                                'copy' => 'kop&#xED;rovat',
                                'copyto' => 'kop&#xED;rovat do',
                                'move' => 'p&#x0159;esunout',
                                'moveto' => 'p&#x0159;esunout do',
                                'delete' => 'odstranit',
                                'edit' => '&#xFA;pravy',
                                'read' => '&#x010D;ten&#xED;',
                                'write' => 'z&#xE1;pis',
                                'exec' => 'spu&#x0161;t&#x011B;n&#xED;',
                                'change' => 'zm&#x011B;nit',
                                'upload' => 'nahr&#xE1;t',
                                'configure' => 'nastaven&#xED;',
                                'yes' => 'ano',
                                'no' => 'ne',
                                'back' => 'zp&#xE1;tky',
                                'setperms' => 'nastav pr&#xE1;va',
                                'readingerror' => 'Chyba p&#x0159;i &#x010D;ten&#xED; 1',
                                'permsset' => 'P&#x0159;&#xED;stupov&#xE1; pr&#xE1;va k 1 byla nastavena na 2.',
                                'permsnotset' => 'P&#x0159;&#xED;stupov&#xE1; pr&#xE1;va k 1 nelze  nastavit na 2.',
                                'uploaded' => 'Soubor 1 byl ulo&#x017E;en do adres&#xE1;&#x0159;e 2.',
                                'notuploaded' => 'Chyba p&#x0159;i ukl&#xE1;d&#xE1;n&#xED; souboru 1 do adres&#xE1;&#x0159;e 2.',
                                'moved' => 'Soubor 1 byl p&#x0159;esunut do adres&#xE1;&#x0159;e 2.',
                                'notmoved' => 'Soubor 1 nelze p&#x0159;esunout do adres&#xE1;&#x0159;e 2.',
                                'copied' => 'Soubor 1 byl zkop&#xED;rov&#xE1;n do adres&#xE1;&#x0159;e 2.',
                                'notcopied' => 'Soubor 1 nelze zkop&#xED;rovat do adres&#xE1;&#x0159;e 2.',
                                'created' => '1 byl vytvo&#x0159;en.',
                                'notcreated' => '1 nelze vytvo&#x0159;it.',
                                'deleted' => '1 byl vymaz&#xE1;n.',
                                'notdeleted' => '1 nelze vymazat.',
                                'suredelete' => 'Skute&#x010D;n&#x011B; smazat 1?',
                                'saved' => 'Soubor 1 byl ulo&#x017E;en.',
                                'notsaved' => 'Soubor 1 nelze ulo&#x017E;it.',
                                'reset' => 'zp&#x011B;t',
                                'clear' => 'vy&#x010D;istit',
                                'save' => 'ulo&#x017E;',
                                'cantbeshown' => "1 can't be shown.",
                                'sourceof' => 'source of 1',
                                'notopened' => "1 nelze otev&#x0159;&#xED;t",
                                'addauth' => 'p&#x0159;idat z&#xE1;kladn&#xED;-authentifikaci',
                                'username' => 'U&#x017E;ivatelsk&#xE9; jm&#xE9;no',
                                'password' => 'Heslo',
                                'add' => 'p&#x0159;idat',
                                'treeon' => 'Zobraz strom adres&#xE1;&#x0159;&#x016F;',
                                'treeoff' => 'Skryj strom adres&#xE1;&#x0159;&#x016F;',
                                'symlink' => 'Symbolick&#xFD; odkaz',
                                'createsymlink' => 'vytvo&#x0159;it odkaz',
                                'target' => 'C&#xED;l',
                                'reltarget' => 'Relativni cesta k c&#xED;li',
                                'alreadyexists' => 'Tento soubor u&#x017E; existuje.',
                                'overwrite' => 'P&#x0159;epsat 1?',
                                'samefiles' => '1 a 2 jsou identick&#xE9;l.'
                        );

                case 'it':
                        return array(
                                'dir' => 'Directory',
                                'file' => 'File',
                                'filename' => 'Nome file',
                                'size' => 'Dimensioni',
                                'permission' => 'Permessi',
                                'functions' => 'Funzioni',
                                'owner' => 'Proprietario',
                                'group' => 'Gruppo',
                                'other' => 'Altro',
                                'create' => 'crea',
                                'copy' => 'copia',
                                'copyto' => 'copia su',
                                'move' => 'muovi',
                                'moveto' => 'muove su',
                                'delete' => 'delete',
                                'edit' => 'edit',
                                'read' => 'leggi',
                                'write' => 'scrivi',
                                'exec' => 'esegui',
                                'change' => 'modifica',
                                'upload' => 'upload',
                                'configure' => 'configura',
                                'yes' => 'si',
                                'no' => 'no',
                                'back' => 'back',
                                'setperms' => 'imposta permessi',
                                'readingerror' => 'Errore durante la lettura di 1',
                                'permsset' => 'I permessi di 1 sono stati impostati a 2.',
                                'permsnotset' => 'I permessi di 1 non possono essere impostati.',
                                'uploaded' => '1 &egrave; stato uploadato su 2.',
                                'notuploaded' => 'Errore durante l\'upload di 1 su 2.',
                                'moved' => '1 &egrave; stato spostato su 2.',
                                'notmoved' => '1 non pu&ograve; essere spostato su 2.',
                                'copied' => '1 &egrave; stato copiato su 2.',
                                'notcopied' => '1 non pu&ograve; essere copiato su 2.',
                                'created' => '1 &egrave; stato creato.',
                                'notcreated' => 'impossibile creare 1.',
                                'deleted' => '1 &egrave; stato eliminato.',
                                'notdeleted' => 'Impossibile eliminare 1.',
                                'suredelete' => 'Confermi eliminazione di 1?',
                                'saved' => '1 &egrave; stato salvato.',
                                'notsaved' => 'Impossibile salvare 1.',
                                'reset' => 'reimposta',
                                'clear' => 'pulisci',
                                'save' => 'salva',
                                'cantbeshown' => "Impossibile visualizzare 1.",
                                'sourceof' => 'sorgente di 1',
                                'notopened' => "Impossibile aprire 1",
                                'addauth' => 'aggiunge autenticazione di base',
                                'username' => 'Nome Utente',
                                'password' => 'Password',
                                'add' => 'add',
                                'treeon' => 'Abilita vista ad albero',
                                'treeoff' => 'Disabilita vista ad albero',

                                'symlink' => 'Link simbolico',
                                'createsymlink' => 'crea symlink',
                                'target' => 'Target',
                                'reltarget' => 'Percorso relativo al target',
                                'alreadyexists' => 'Questo file esiste gi&agrave;.',
                                'overwrite' => 'Sovrascrivi 1?',
                                'samefiles' => '1 e 2 sono identici.'
                        );

                case 'fr':
                        return array(
                                'dir' => 'Dossier',
                                'file' => 'Fichier',
                                'filename' => 'Nom du fichier',
                                'size' => 'Dimension',
                                'permission' => 'Permission',
                                'functions' => 'Fonction',
                                'owner' => 'Propri&eacute;taire',
                                'group' => 'Groupe',
                                'other' => 'Autre',
                                'create' => 'Cr&eacute;&eacute;',
                                'copy' => 'Copier',
                                'copyto' => 'Copier sur',
                                'move' => 'D&eacute;placer',
                                'moveto' => 'D&eacute;placer sur',
                                'delete' => 'Effacer',
                                'edit' => 'Editer',
                                'read' => 'Lire',
                                'write' => 'Ecrire',
                                'exec' => 'Executer',
                                'change' => 'Modifier',
                                'upload' => 'Uploader',
                                'configure' => 'Configurer',
                                'yes' => 'Oui',
                                'no' => 'Non',
                                'back' => 'retour',
                                'setperms' => 'placez la permission',
                                'readingerror' => 'Erreur durant le lecture 1',
                                'permsset' => 'Les permissions de 1 ont &eacute;t&eacute; &eacute;tablies &agrave; 2.',
                                'permsnotset' => 'Les permissions de 1 ne peuvent pas &ecirc;tre &eacute;tablies.',
                                'uploaded' => '1 a &eacute;t&eacute; upload&eacute; sur 2.',
                                'notuploaded' => 'Erreur durant l\'upload de 1 sur 2.',
                                'moved' => '1 a &eacute;t&eacute; d&eacute;plac&eacute; sur 2.',
                                'notmoved' => '1 ne peut pas &ecirc;tre d&eacute;plac&eacute; sur 2.',
                                'copied' => '1 a &eacute;t&eacute; copi&eacute; sur 2.',
                                'notcopied' => '1 ne peut pas &ecirc;tre copi&eacute; sur 2.',
                                'created' => '1 a &eacute;t&eacute; cr&eacute;&eacute;.',
                                'notcreated' => 'impossible de cr&eacute;er 1.',
                                'deleted' => '1 a &eacute;t&eacute; effac&eacute;',
                                'notdeleted' => 'impossible d\'effacer 1.',
                                'suredelete' => 'Confirmez-vous la suppression de 1 ?',
                                'saved' => '1 a &eacute;t&eacute; enregistr&eacute;.',
                                'notsaved' => 'Impossible d\'enregistrer 1.',
                                'reset' => 'remettre',
                                'clear' => 'efface',
                                'save' => 'sauvegarde',
                                'cantbeshown' => "Impossible de visualiser 1.",
                                'sourceof' => 'source de 1',
                                'notopened' => "Impossible d'ouvrir 1",
                                'addauth' => 'ajoutez une authentification de base',
                                'username' => 'Nom d\'utilisateur',
                                'password' => 'Mot de passe',
                                'add' => 'Ajouter',
                                'treeon' => 'Permettre de voir l\'arbor&eacute;sence',
                                'treeoff' => 'Interdire de voir l\'arbor&eacute;sence',
                                'symlink' => 'Lien symbolique',
                                'createsymlink' => 'Cr&eacute;&eacute; un lien symbolique',
                                'target' => 'Requete',
                                'reltarget' => 'Parcours relatif &agrave; la requete',
                                'alreadyexists' => 'Ce fichier existe d&eacute;j&agrave;.',
                                'overwrite' => 'R&eacute;&eacute;crire 1?',
                                'samefiles' => '1 et 2 sont identiques.',
                        );

                case 'nl':
                        return array(
                                'dir' => 'Directory',
                                'file' => 'Bestand',
                                'filename' => 'Bestandsnaam',
                                'size' => 'Grootte',
                                'permission' => 'Permissies',
                                'functions' => 'Functies',
                                'owner' => 'Eigenaar',
                                'group' => 'Groep',
                                'other' => 'Anderen',
                                'create' => 'nieuw aanmaken',
                                'copy' => 'kopieren',
                                'copyto' => 'kopieren naar',
                                'move' => 'verplaatsen',
                                'moveto' => 'verplaatsen naar',
                                'delete' => 'verwijderen',
                                'edit' => 'bewerken',
                                'read' => 'lezen',
                                'write' => 'schrijven',
                                'exec' => 'uitvoeren',
                                'change' => 'veranderen',
                                'upload' => 'uploaden',
                                'configure' => 'configureren',
                                'yes' => 'ja',
                                'no' => 'neen',
                                'back' => 'terug',
                                'setperms' => 'verander permissies',
                                'readingerror' => 'Fout tijdens lezen van 1',
                                'permsset' => 'Permissies van 1 zijn nu 2.',
                                'permsnotset' => 'Kan permissies van 1 niet veranderen.',
                                'uploaded' => '1 is naar 2 geupload.',
                                'notuploaded' => 'Fout tijdens upload van 1 naar 2.',
                                'moved' => '1 is naar 2 verplaatst.',
                                'notmoved' => 'Kon 1 niet verplaatsen naar 2.',
                                'copied' => '1 is naar 2 gekopieerd.',
                                'notcopied' => 'Kon 1 niet kopieren naar 2.',
                                'created' => '1 is aangemaakt.',
                                'notcreated' => 'Kon 1 niet aanmaken.',
                                'deleted' => '1 is verwijderd.',
                                'notdeleted' => 'Kon 1 niet verwijderen.',
                                'suredelete' => 'Wilt u 1 echt verwijderen?',
                                'saved' => '1 is opgeslagen.',
                                'notsaved' => 'Kon 1 niet opslaan.',
                                'reset' => 'alles ongedaan maken',
                                'clear' => 'wissen',
                                'save' => 'opslaan',
                                'cantbeshown' => "Kan 1 niet weergeven.",
                                'sourceof' => 'bron van 1',
                                'notopened' => "Kon 1 niet openen",
                                'addauth' => 'Basis-authentificatie toevoegen',
                                'username' => 'Gebruikersnaam',
                                'password' => 'Paswoord',
                                'add' => 'toevoegen',
                                'treeon' => 'Boomstructuur Tonen',
                                'treeoff' => 'Boomstructuur Verbergen',
                                'symlink' => 'Symbolische koppeling',
                                'createsymlink' => 'koppeling maken',
                                'target' => 'Doel',
                                'reltarget' => 'Relatief pad naar doel',
                                'alreadyexists' => 'Dit bestand bestaat al.',
                                'overwrite' => '1 overschrijven?',
                                'samefiles' => '1 en 2 zijn hetzelfde.',
                        );

                case 'se':
                        return array(
                                'dir' => 'Folder',
                                'file' => 'Fil',
                                'filename' => 'Filnamn',
                                'size' => 'Storlek',
                                'permission' => 'R&auml;ttigheter',
                                'functions' => 'Funktioner',
                                'owner' => '&Auml;gare',
                                'group' => 'Grupp',
                                'other' => 'Andra',
                                'create' => 'Skapa',
                                'copy' => 'Kopiera',
                                'copyto' => 'kopiera till',
                                'move' => 'flytta',
                                'moveto' => 'flytta till',
                                'delete' => 'radera',
                                'edit' => 'redigera',
                                'read' => 'l&auml;s',
                                'write' => 'skriv',
                                'exec' => 'exekvera',
                                'change' => '&auml;ndra',
                                'upload' => 'Ladda upp',
                                'configure' => 'konfigurera',
                                'yes' => 'ja',
                                'no' => 'nej',
                                'back' => 'tillbaks',
                                'setperms' => 'set filattribut',
                                'readingerror' => 'kunde inte l&auml;sa fr&aring;n 1',
                                'permsset' => 'R&auml;ttigheterna f&ouml;r 1 sattes till 2.',
                                'permsnotset' => 'R&auml;ttigheterna f&ouml;r 1 kunde inte &auml;ndras.',
                                'uploaded' => '1 har laddats upp till 2.',
                                'notuploaded' => 'Fel vid uppladdning av 1 till 2.',
                                'moved' => '1 har flyttats till 2.',
                                'notmoved' => 'Kunde inte flytta 1 till 2.',
                                'copied' => '1 har kopierats till 2.',
                                'notcopied' => 'Kunde inte kopiera 1 till 2.',
                                'created' => '1 har skapats.',
                                'notcreated' => 'Kunde inte skapa 1.',
                                'deleted' => '1 har raderats.',
                                'notdeleted' => 'Kunde inte radera 1.',
                                'suredelete' => 'Skall 1 verkligen raderas?',
                                'saved' => '1 har sparats.',
                                'notsaved' => 'Kunde inte spara 1.',
                                'reset' => '&aring;terst&auml;ll',
                                'clear' => 'rensa',
                                'save' => 'spara',
                                'cantbeshown' => 'Kan inte visa 1.',
                                'sourceof' => 'k&auml;lla till 1',
                                'notopened' => 'Kunde inte &ouml;ppna 1',
                                'addauth' => 'L&auml;gg till grundl&auml;ggande autenticering (basic-authentication)',
                                'username' => 'Anv&auml;ndarnamn',
                                'password' => 'L&ouml;senord',
                                'add' => 'L&auml;gg till',
                                'treeon' => 'Aktivera tr&auml;dvy',
                                'treeoff' => 'St&auml;ng av tr&auml;vy',
                                'symlink' => 'Symbolisk l&auml;nk',
                                'createsymlink' => 'skapa l&auml;nk',
                                'target' => 'M&aring;l',
                                'reltarget' => 'Relativ v&auml;g till m&aring;l',
                                'alreadyexists' => 'En fil med detta namn finns redan',
                                'overwrite' => 'Skriva &ouml;ver 1?',
                                'samefiles' => '1 och 2 &auml;r identiska.'
                        );

                case 'pt-br':
                        return array(
                                'dir' => 'Diret&oacute;rio',
                                'file' => 'Arquivo',
                                'filename' => 'Nome do arquivo',
                                'size' => 'Tamanho',
                                'permission' => 'Permiss&atilde;o',
                                'functions' => 'Fun&ccedil;&otilde;es',
                                'owner' => 'Propriet&aacute;rio',
                                'group' => 'Grupo',
                                'other' => 'Outros',
                                'create' => 'criar',
                                'copy' => 'copiar',
                                'copyto' => 'copiar para',
                                'move' => 'mover',
                                'moveto' => 'mover para',
                                'delete' => 'deletar',
                                'edit' => 'editar',
                                'read' => 'ler',
                                'write' => 'escrever',
                                'exec' => 'executar',
                                'change' => 'alterar',
                                'upload' => 'upload',
                                'configure' => 'configurar',
                                'yes' => 'sim',
                                'no' => 'n&atilde;o',
                                'back' => 'voltar',
                                'setperms' => 'setar permiss&atilde;o',
                                'readingerror' => 'Erro durante leitura de 1',
                                'permsset' => 'A permiss&atilde;o de 1 foi alterada para 2.',
                                'permsnotset' => 'A permiss&atilde;o de 1 n&atilde;o p&ocirc;de ser alterada.',
                                'uploaded' => '1 foi copiado com sucesso para 2.',
                                'notuploaded' => 'Erro durante a c&oacute;pia de 1 para 2.',
                                'moved' => '1 foi movido com sucesso para 2.',
                                'notmoved' => '1 n&atilde;o p&ocirc;de ser movido para 2.',
                                'copied' => '1 foi copiado com sucesso para 2.',
                                'notcopied' => '1 n&atilde;o p&ocirc;de ser copiado para 2.',
                                'created' => '1 foi criado com sucesso.',
                                'notcreated' => '1 n&atilde;o p&ocirc;de ser criado.',
                                'deleted' => '1 foi deletado com sucesso.',
                                'notdeleted' => '1 n&atilde;o p&ocirc;de ser deletado.',
                                'suredelete' => 'Deseja realmente deletar 1?',
                                'saved' => '1 foi salvo com sucesso.',
                                'notsaved' => '1 n&atilde;o p&ocirc;de ser salvo.',
                                'reset' => 'reiniciar',
                                'clear' => 'limpar',
                                'save' => 'salvar',
                                'cantbeshown' => '1 n&atilde;o p&ocirc;de ser exibido.',
                                'sourceof' => 'fonte de 1',
                                'notopened' => '1 n&atilde;o p&ocirc;de ser aberto',
                                'addauth' => 'add basic-authentification',
                                'username' => 'Usu&aacute;rio',
                                'password' => 'Senha',
                                'add' => 'adicionar',
                                'treeon' => 'Ativar exibi&ccedil;&atilde;o em &aacute;rvore',
                                'treeoff' => 'Desativar exibi&ccedil;&atilde;o em &aacute;rvore',
                                'symlink' => 'Link simb&oacute;lico',
                                'createsymlink' => 'criar link',
                                'target' => 'Caminho',
                                'reltarget' => 'Caminho para onde apontar',
                                'alreadyexists' => 'Esse arquivo j&aacute; existe.',
                                'overwrite' => 'Sobre escrever 1?',
                                'samefiles' => '1 e 2 s&atilde;o iguais.'
                        );

                case 'es':
                        return array(
                                'dir' => 'Directorio',
                                'file' => 'Archivo',
                                'filename' => 'Nombre de Archivo',
                                'size' => 'Tama&ntilde;o',
                                'permission' => 'Permisos',
                                'functions' => 'Funciones',
                                'owner' => 'Propietario',
                                'group' => 'Grupo',
                                'other' => 'Otro',
                                'create' => 'Crear',
                                'copy' => 'Copiar',
                                'copyto' => 'Copiar a',
                                'move' => 'Mover',
                                'moveto' => 'Mover a',
                                'delete' => 'Borrar',
                                'edit' => 'Editar',
                                'read' => 'Leer',
                                'write' => 'Escribir',
                                'exec' => 'Ejecutar',
                                'change' => 'Cambiar',
                                'upload' => 'Subir',
                                'configure' => 'Configurar',
                                'yes' => 'S&iacute;',
                                'no' => 'No',
                                'back' => 'Volver',
                                'setperms' => 'Establecer Permisos',
                                'readingerror' => 'Error al leer de 1',
                                'permsset' => 'Los permisos de 1 ser&aacute;n establecidos a 2.',
                                'permsnotset' => 'Los permisos de 1 no pueden establecerse.',
                                'uploaded' => '1 ha sido subido a 2.',
                                'notuploaded' => 'Error al subir de 1 a 2.',
                                'moved' => '1 ha sido movido a 2.',
                                'notmoved' => '1 no ha podido ser movido a 2.',
                                'copied' => '1 ha sido copiado a 2.',
                                'notcopied' => '1 no ha podido ser copiado a 2.',
                                'created' => '1 ha sido creado.',
                                'notcreated' => '1 no ha podido ser creado.',
                                'deleted' => '1 ha sido borrado.',
                                'notdeleted' => '1 no ha podido ser borrado.',
                                'suredelete' => '&iquest;Borrar 1?',
                                'saved' => '1 ha sido guardado.',
                                'notsaved' => '1 no ha podido ser guardado.',
                                'reset' => 'Resetear',
                                'clear' => 'Limpiar',
                                'save' => 'Guardar',
                                'cantbeshown' => "1 no puede ser mostrado.",
                                'sourceof' => 'origen de 1',
                                'notopened' => "1 no puede ser abierto.",
                                'addauth' => 'A&ntilde;adir Autentificaci&oacute;n B&aacute;sica',
                                'username' => 'Usuario',
                                'password' => 'Contrase&ntilde;a',
                                'add' => 'A&ntilde;adir',
                                'treeon' => 'Activar vista en &aacute;rbol',
                                'treeoff' => 'Desactivar vista en &aacute;rbol',
                                'symlink' => 'Enlace Simb&oacute;lico',
                                'createsymlink' => 'Crear Enlace',
                                'target' => 'Destino',
                                'reltarget' => 'Ruta relativa al destino',
                                'alreadyexists' => 'Este archivo ya existe.',
                                'overwrite' => '&iquest;Sobrescribir 1?',
                                'samefiles' => '1 y 2 son id&eacute;nticos.'
                        );

                case 'da':
                        return array(
                                'dir' => 'Mappe',
                                'file' => 'Fil',
                                'filename' => 'Filnavn',
                                'size' => 'St&oslash;rrelse',
                                'permission' => 'Rettigheder',
                                'functions' => 'Funktioner',
                                'owner' => 'Ejer (owner)',
                                'group' => 'Gruppe (group)',
                                'other' => 'Alle (other)',
                                'create' => 'Opret',
                                'copy' => 'Kopier',
                                'copyto' => 'Kopier til',
                                'move' => 'Flyt',
                                'moveto' => 'Flyt til',
                                'delete' => 'Slet',
                                'edit' => 'Ret',
                                'read' => 'L&aelig;s (read)',
                                'write' => 'Skriv (write)',
                                'exec' => 'Eksekver (execute)',
                                'change' => 'Skift',
                                'upload' => 'Upload',
                                'configure' => 'Konfigurer',
                                'yes' => 'Ja',
                                'no' => 'Nej',
                                'back' => 'Tilbage',
                                'setperms' => 'S&aelig;t rettigheder',
                                'readingerror' => 'Fejl under indl&aelig;sning af 1',
                                'permsset' => 'Rettighederne for 1 er blevet sat til 2.',
                                'permsnotset' => 'Rettighederne for 1 kunne ikke s&aelig;ttes.',
                                'uploaded' => '1 er blevet uploadet til 2.',
                                'notuploaded' => 'Fejl under upload af 1 til 2.',
                                'moved' => '1 er blevet flyttet til 2.',
                                'notmoved' => '1 kunne ikke flyttes til 2.',
                                'copied' => '1 er blevet kopieret til 2.',
                                'notcopied' => '1 kunne ikke kopieres til 2.',
                                'created' => '1 er blevet oprettet.',
                                'notcreated' => '1 kunne ikke oprettes.',
                                'deleted' => '1 er blevet slettet.',
                                'notdeleted' => '1 kunne ikke slettes.',
                                'suredelete' => 'Vil du virkelig slette 1?',
                                'saved' => '1 er blevet gemt.',
                                'notsaved' => '1 kunne ikke gemmes.',
                                'reset' => 'Nulstil',
                                'clear' => 'Slet',
                                'save' => 'Gem',
                                'cantbeshown' => "1 kan ikke vises.",
                                'sourceof' => 'Kildekode af 1',
                                'notopened' => "1 kunne ikke &aring;bnes",
                                'addauth' => 'Tilf&oslash;j grundl&aelig;ggende beskyttelse (basic-authentication)',
                                'username' => 'Brugernavn',
                                'password' => 'Password',
                                'add' => 'Tilf&oslash;j',
                                'treeon' => 'Aktiv&eacute;r treeview',
                                'treeoff' => 'Deaktiver treeview',
                                'symlink' => 'Symbolic link',
                                'createsymlink' => 'Opret link',
                                'target' => 'Target',
                                'reltarget' => 'Relativ sti til target',
                                'alreadyexists' => 'Denne fil eksisterer allerede.',
                                'overwrite' => 'Overskriv 1?',
                                'samefiles' => '1 og 2 er identiske.'
                        );

                case 'en':
                        return array(
                                'dir' => 'Directory',
                                'file' => 'File',
                                'filename' => 'Filename',
                                'size' => 'Size',
                                'permission' => 'Permission',
                                'functions' => 'Functions',
                                'owner' => 'Owner',
                                'group' => 'Group',
                                'other' => 'Other',
                                'create' => 'create',
                                'copy' => 'copy',
                                'copyto' => 'copy to',
                                'move' => 'move',
                                'moveto' => 'move to',
                                'delete' => 'delete',
                                'edit' => 'edit',
                                'read' => 'read',
                                'write' => 'write',
                                'exec' => 'execute',
                                'change' => 'change',
                                'upload' => 'upload',
                                'configure' => 'configure',
                                'yes' => 'yes',
                                'no' => 'no',
                                'back' => 'back',
                                'setperms' => 'set permission',
                                'readingerror' => 'Error during read of 1',
                                'permsset' => 'The permission of 1 were set to 2.',
                                'permsnotset' => 'The permission of 1 could not be set.',
                                'uploaded' => '1 has been uploaded to 2.',
                                'notuploaded' => 'Error during upload of 1 to 2.',
                                'moved' => '1 has been moved to 2.',
                                'notmoved' => '1 could not be moved to 2.',
                                'copied' => '1 has been copied to 2.',
                                'notcopied' => '1 could not be copied to 2.',
                                'created' => '1 has been created.',
                                'notcreated' => '1 could not be created.',
                                'deleted' => '1 has been deleted.',
                                'notdeleted' => '1 could not be deleted.',
                                'suredelete' => 'Really delete 1?',
                                'saved' => '1 has been saved.',
                                'notsaved' => '1 could not be saved.',
                                'reset' => 'reset',
                                'clear' => 'clear',
                                'save' => 'save',
                                'cantbeshown' => "1 can't be shown.",
                                'sourceof' => 'source of 1',
                                'notopened' => "1 couldn't be opened",
                                'addauth' => 'add basic-authentification',
                                'username' => 'Username',
                                'password' => 'Password',
                                'add' => 'add',
                                'treeon' => 'Enable treeview',
                                'treeoff' => 'Disable treeview',
                                'symlink' => 'Symbolic link',
                                'createsymlink' => 'create link',
                                'target' => 'Target',
                                'reltarget' => 'Relative path to target',
                                'alreadyexists' => 'This file already exists.',
                                'overwrite' => 'Overwrite 1?',
                                'samefiles' => '1 and 2 are identical.'
                        );
        }
}
?>