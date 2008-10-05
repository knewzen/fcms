<?php
session_start();
if (get_magic_quotes_gpc()) {
	$_REQUEST = array_map('stripslashes', $_REQUEST);
	$_GET = array_map('stripslashes', $_GET);
	$_POST = array_map('stripslashes', $_POST);
	$_COOKIE = array_map('stripslashes', $_COOKIE);
}
include_once('inc/config_inc.php');
include_once('inc/util_inc.php');
include_once('inc/language.php');
if (isset($_SESSION['login_id'])) {
	if (!isLoggedIn($_SESSION['login_id'], $_SESSION['login_uname'], $_SESSION['login_pw'])) {
		displayLoginPage();
		exit();
	}
} elseif (isset($_COOKIE['fcms_login_id'])) {
	if (isLoggedIn($_COOKIE['fcms_login_id'], $_COOKIE['fcms_login_uname'], $_COOKIE['fcms_login_pw'])) {
		$_SESSION['login_id'] = $_COOKIE['fcms_login_id'];
		$_SESSION['login_uname'] = $_COOKIE['fcms_login_uname'];
		$_SESSION['login_pw'] = $_COOKIE['fcms_login_pw'];
	} else {
		displayLoginPage();
		exit();
	}
} else {
	displayLoginPage();
	exit();
}
header("Cache-control: private");
include_once('inc/recipes_class.php');
$rec = new Recipes($_SESSION['login_id'], 'mysql', $cfg_mysql_host, $cfg_mysql_db, $cfg_mysql_user, $cfg_mysql_pass);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $LANG['lang']; ?>" lang="<?php echo $LANG['lang']; ?>">
<head>
<title><?php echo getSiteName() . " - " . $LANG['poweredby'] . " " . getCurrentVersion(); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="Ryan Haudenschilt" />
<link rel="stylesheet" type="text/css" href="<?php getTheme($_SESSION['login_id']); ?>" />
<link rel="shortcut icon" href="themes/images/favicon.ico"/>
<script src="inc/prototype.js" type="text/javascript"></script>
</head>
<body id="body-recipe">
	<div><a name="top"></a></div>
	<div id="header"><?php echo "<h1 id=\"logo\">".getSiteName()."</h1><p>".$LANG['welcome']." <a href=\"profile.php?member=".$_SESSION['login_id']."\">"; echo getUserDisplayName($_SESSION['login_id']); echo "</a> | <a href=\"settings.php\">".$LANG['link_settings']."</a> | <a href=\"logout.php\" title=\"".$LANG['link_logout']."\">".$LANG['link_logout']."</a></p>"; ?></div>
	<?php displayTopNav(); ?>
	<div id="pagetitle"><?php echo $LANG['link_recipes']; ?></div>
	<div id="leftcolumn">
		<h2><?php echo $LANG['navigation']; ?></h2>
		<?php
		displaySideNav();
		if(checkAccess($_SESSION['login_id']) < 3) { 
			echo "\t<h2>".$LANG['admin']."</h2>\n\t"; 
			displayAdminNav("fix");
		} ?></div>
	<div id="content">
		<div id="recipe" class="centercontent">
			<?php
			$show = true;
			if (isset($_POST['submitadd'])) {
				$name = addslashes($_POST['name']);
				$recipe = addslashes($_POST['post']);
				$sql = "INSERT INTO `fcms_recipes`(`name`, `category`, `recipe`, `user`, `date`) VALUES('$name', '".$_POST['category']."', '$recipe', " . $_SESSION['login_id'] . ", NOW())";
				mysql_query($sql) or displaySQLError('New Recipe Error', 'recipes.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"add\">".$LANG['ok_recipe_add']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('add').toggle()\",3000); }</script>";
			} 
			if (isset($_POST['submitedit'])) {
				$name = addslashes($_POST['name']);
				$recipe = addslashes($_POST['post']);
				$sql = "UPDATE `fcms_recipes` SET `name` = '$name', `category` = '".$_POST['category']."', `recipe` = '$recipe' WHERE `id` = " . $_POST['id'];
				mysql_query($sql) or displaySQLError('Edit Recipe Error', 'recipes.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"edit\">".$LANG['ok_recipe_edit']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('edit').toggle()\",3000); }</script>";
			}
			if (isset($_POST['delrecipe'])) {
				$sql = "DELETE FROM `fcms_recipes` WHERE `id` = " . $_POST['id'];
				mysql_query($sql) or displaySQLError('Delete Recipe Error', 'recipes.php [' . __LINE__ . ']', $sql, mysql_error());
				echo "<p class=\"ok-alert\" id=\"del\">".$LANG['ok_recipe_del']."</p>";
				echo "<script type=\"text/javascript\">window.onload=function(){ var t=setTimeout(\"$('del').toggle()\",2000); }</script>";
			}
			if (isset($_GET['addrecipe']) && checkAccess($_SESSION['login_id']) <= 5) {
				$show = false;
				$rec->displayForm('add');
			}
			if (isset($_POST['editrecipe'])) {
				$show = false;
				$rec->displayForm('edit', $_POST['id'], $_POST['name'], $_POST['category'], $_POST['post']);
			}
			if (isset($_GET['category'])) {
				$show = false;
				echo "<div class=\"clearfix\"><a class=\"link_block home\" href=\"recipes.php\">".$LANG['recipe_cats']."</a>";
				if (checkAccess($_SESSION['login_id']) <= 5) {
					echo "<a class=\"link_block add\" href=\"?addrecipe=yes\">".$LANG['add_recipe']."</a>";
				}
				echo "</div>\n";
				$page = 1; $id = 0;
				if (isset($_GET['page'])) { $page = $_GET['page']; }
				if (isset($_GET['id'])) { $id = $_GET['id']; }
				$rec->showRecipeInCategory($_GET['category'], $page, $id);
			}
			if ($show) {
				if (checkAccess($_SESSION['login_id']) <= 5) {
					echo "<div class=\"clearfix\"><a class=\"link_block add\" href=\"?addrecipe=yes\">".$LANG['add_recipe']."</a></div>\n";
				}
				$rec->showRecipes();
			} ?>
			</div><!-- #recipe .centercontent -->
	</div><!-- #content -->
	<?php displayFooter(); ?>
</body>
</html>