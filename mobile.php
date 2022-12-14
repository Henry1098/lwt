<?php

/**************************************************************
"Learning with Texts" (LWT) is released into the Public Domain.
This applies worldwide.
In case this is not legally possible, any entity is granted the
right to use this work for any purpose, without any conditions, 
unless such conditions are required by law.

Developed by J. Pierre in 2011.
***************************************************************/

/**************************************************************
Call: mobile.php?...
			...action=1&lang=[langid] ... Language menu
			...action=2&lang=[langid] ... Texts in a language
			...action=3&lang=[langid]&text=[textid] ... Sentences of a text
			...action=4&lang=[langid]&text=[textid]&sent=[sentid] ... Terms of a sentence
			...action=5&lang=[langid]&text=[textid]&sent=[sentid] ... Terms of a sentence (next sent)
LWT Mobile 
***************************************************************/

include "connect.inc.php";
include "settings.inc.php";
include "utilities.inc.php";

/**************************************************************/

if (isset($_REQUEST["action"])) {  // Action

	$action = $_REQUEST["action"] + 0; // Action code

	/* -------------------------------------------------------- */

	if ($action == 1) { 
	
		$lang = $_REQUEST["lang"];
		$langname = getLanguage($lang);

		?>
		
		<ul id="<?php echo $action . '-' . $lang; ?>" title="<?php echo tohtml($langname); ?>">
			<li class="group"><?php echo tohtml($langname); ?> Texts</li>
			<li><a href="mobile.php?action=2&amp;lang=<?php echo $lang; ?>">All <?php echo tohtml($langname); ?> Texts</a></li>					
			<li><a href="mobile.php#notyetimpl">Text Tags</a></li>					
			<li class="group"><?php echo tohtml($langname); ?> Terms</li>
			<li><a href="mobile.php#notyetimpl">All <?php echo tohtml($langname); ?> Terms</a></li>					
			<li><a href="mobile.php#notyetimpl">Term Tags</a></li>					
		</ul>
		
		<?php
	
	} // $action == 1
	
	/* -------------------------------------------------------- */
	
	elseif ($action == 2) { 
	
		$lang = $_REQUEST["lang"];
		$langname = getLanguage($lang);
		$sql = 'select TxID, TxTitle from texts where TxLgID = ' . $lang . 
		' order by TxTitle';
		$res = mysql_query($sql);		
		if ($res == FALSE) die("Invalid Query: $sql");

		?>

		<ul id="<?php echo $action . '-' . $lang; ?>" title="All <?php echo tohtml($langname); ?> Texts">

		<?php

		while ($record = mysql_fetch_assoc($res)) {
			echo '<li><a href="mobile.php?action=3&amp;lang=' . 
				$lang . '&amp;text=' . $record["TxID"] . '">' .
				tohtml($record["TxTitle"]) . '</a></li>';	
		}

		?>

		</ul>
		<?php
		mysql_free_result($res);
	
	} // $action == 2
	
	/* -------------------------------------------------------- */
	
	elseif ($action == 3) { 
	
		$lang = $_REQUEST["lang"];
		$text = $_REQUEST["text"];
		$texttitle = get_first_value('select TxTitle as value from texts where TxID = ' . $text);
		$textaudio = get_first_value('select TxAudioURI as value from texts where TxID = ' . $text);
		$sql = 'select SeID, SeText from sentences where SeTxID = ' . $text . ' order by SeOrder';
		$res = mysql_query($sql);		
		if ($res == FALSE) die("Invalid Query: $sql");

		?>

		<ul id="<?php echo $action . '-' . $text; ?>" title="<?php echo tohtml($texttitle); ?>">
		<li class="group">Title</li>
		<li><?php echo tohtml($texttitle); ?></li>

		<?php

		if (isset($textaudio) && trim($textaudio) != '') {

		?>

		<li class="group">Audio</li>
		<li>Play: <audio src="<?php echo trim($textaudio); ?>" controls></audio></li>

		<?php

		}

		?>

		<li class="group">Text</li>

		<?php
		
		while ($record = mysql_fetch_assoc($res)) {
			if (trim($record["SeText"]) != '??')
			 echo '<li><a href="mobile.php?action=4&amp;lang=' . 
				$lang . '&amp;text=' . $text . 
				'&amp;sent=' . $record["SeID"] . '">' .
				tohtml($record["SeText"]) . '</a></li>';	
		}

		?>
		
		</ul>

		<?php
		
		mysql_free_result($res);
	
	} // $action == 3
	
	/* -------------------------------------------------------- */
	
	elseif ($action == 4 || $action == 5) { 
	
		$lang = $_REQUEST["lang"];
		$text = $_REQUEST["text"];
		$sent = $_REQUEST["sent"];
		$senttext = get_first_value('select SeText as value from sentences where SeID = ' . $sent);
		$nextsent = get_first_value('select SeID as value from sentences where SeTxID = ' . $text . ' and trim(SeText) != \'??\' and SeID > ' . $sent . ' order by SeID limit 1');
		$sql = 'select TiWordCount as Code, TiText, TiOrder, TiIsNotWord, WoID, WoTranslation, WoRomanization, WoStatus from (textitems left join words on (TiTextLC = WoTextLC) and (TiLgID = WoLgID)) where TiSeID = ' . $sent . ' and (not (TiWordCount > 1 and WoID is null)) order by TiOrder asc, TiWordCount desc';
		$res = mysql_query($sql);		
		if ($res == FALSE) die("Invalid Query: $sql");
		
		if ($action == 4) {
		?>

		<ul id="<?php echo $action . '-' . $sent; ?>" title="<?php echo tohtml($senttext); ?>">
		
		<?php
		
		}
		
		?>
		
		<li class="group">Sentence</li>
		<li><?php echo tohtml($senttext); ?></li>
		<li class="group">Terms</li>

		<?php
		
		$saveterm = '';
		$savetrans = '';
		$saverom = '';
		$savestat = '';
		$until = 0;
		while ($record = mysql_fetch_assoc($res)) {
			$actcode = $record['Code'] + 0;
			$order = $record['TiOrder'] + 0;
			
			if ( $order <= $until ) {
				continue;
			}
			if ( $order > $until ) {
				if (trim($saveterm) != '') {
					$desc = trim(($saverom != '' ? '[' . $saverom . '] ' : '') . $savetrans);
					echo '<li><span class="status' . $savestat . '">' . tohtml($saveterm) . '</span>' . 
						tohtml($desc != '' ? ' ??? ' . $desc : '') . '</li>';	
				}
				$saveterm = '';
				$savetrans = '';
				$saverom = '';
				$savestat = '';
				$until = $order;
			}
			if ($record['TiIsNotWord'] != 0 && trim($record['TiText']) != '') {
				echo '<li>' . tohtml($record['TiText']) . '</li>';
			}
			else {
				$until = $order + 2 * ($actcode-1);                
				$saveterm = $record['TiText'];
				$savetrans = '';
				if(isset($record['WoID'])) {
					$savetrans = $record['WoTranslation'];
					if ($savetrans == '*') $savetrans = '';
				}
				$saverom = trim(isset($record['WoRomanization']) ?
					$record['WoRomanization'] : "");
				$savestat = $record['WoStatus'];
			}
		} 
		mysql_free_result($res);
		if (trim($saveterm) != '') {
			$desc = trim(($saverom != '' ? '[' . $saverom . '] ' : '') . $savetrans);
			echo '<li><span class="status' . $savestat . '">' . tohtml($saveterm) . '</span>' . 
				tohtml($desc != '' ? ' ??? ' . $desc : '') . '</li>';	
		}
		
		if (isset($nextsent)) {
			echo '<li><a target="_replace" href="mobile.php?action=5&amp;lang=' . 
				$lang . '&amp;text=' . $text . 
				'&amp;sent=' . $nextsent . '">Next Sentence</a></li>';
		}

		if ($action == 4) {
		
		?>
		
		</ul>

		<?php
		
		}
		
		mysql_free_result($res);
	
	} // $action == 4 / 5
	
	/* -------------------------------------------------------- */
	
} // isset($_REQUEST["action"])

/**************************************************************/

else {  // No Action = Start screen

?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta http-equiv="content-language" content="en" />
<title>Mobile LWT</title>
<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
<link rel="apple-touch-icon" href="img/apple-touch-icon-57x57.png" />
<link rel="apple-touch-icon" sizes="72x72" href="img/apple-touch-icon-72x72.png" />
<link rel="apple-touch-icon" sizes="114x114" href="img/apple-touch-icon-114x114.png" />
<link rel="apple-touch-startup-image" href="img/apple-touch-startup.png">
<meta name="apple-touch-fullscreen" content="YES" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<style type="text/css" media="screen">
@import "./iui/iui.css";
span.status1 {
	background-color: #F5B8A9;
}
span.status2 {
	background-color: #F5CCA9;
}
span.status3 {
	background-color: #F5E1A9;
}
span.status4 {
	background-color: #F5F3A9;
}
span.status5 {
	background-color: #DDFFDD;
}
</style>
<script type="text/javascript" src="./iui/iui.js" charset="utf-8"></script>
</head>
<body>

<div class="toolbar">
	<h1 id="pageTitle"></h1>
	<a id="backButton" class="button" href="#"></a>
	<a class="button" href="mobile.php" target="_self">Home</a>
</div>

<ul id="home" title="Mobile LWT" selected="true">
	<li class="group">Languages</li>
<?php
	$sql = 'select LgID, LgName from languages order by LgName';
	$res = mysql_query($sql);		
	if ($res == FALSE) die("Invalid Query: $sql");
	while ($record = mysql_fetch_assoc($res)) {
		echo '<li><a href="mobile.php?action=2&amp;lang=' . $record["LgID"] . '">' .
			tohtml($record["LgName"]) . '</a></li>';	
	}
	mysql_free_result($res);
?>
	<li class="group">Other</li>
	<li><a href="#about">About</a></li>
	<li><a href="index.php" target="_self">LWT Standard Version</a></li>
</ul>

<div id="about" title="About">
	<p style="text-align:center; margin-top:50px;">
This is "Learning With Texts" (LWT) for Mobile Devices<br />Version <?php echo get_version(); ?><br /><br />"Learning with Texts" (LWT) is released into the Public Domain. This applies worldwide. In case this is not legally possible, any entity is granted the right to use this work for any purpose, without any conditions, unless such conditions are required by law.<br /><br /> Developed with the <a href="http://iui-js.org" target="_self">iUI Framework</a>.<br /><br /><b>Back to<br/><a href="index.php" target="_self">LWT Standard Version</a></b>
	</p>
</div>

<div id="notyetimpl" title="Sorry...">
	<p style="text-align:center; margin-top:50px;">Not yet implemented!</p>
</div>

</body>
</html>

<?php
	
} // No Action = Start screen

?>