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
Call: insert_word_wellknown.php?tid=[textid]&ord=[textpos]
Ignore single word (new term with status 99)
***************************************************************/

include "connect.inc.php";
include "settings.inc.php";
include "utilities.inc.php";

$word = get_first_value("select TiText as value from textitems where TiWordCount = 1 and TiTxID = " . $_REQUEST['tid'] . " and TiOrder = " . $_REQUEST['ord']);

$wordlc =	mb_strtolower($word, 'UTF-8');

$langid = get_first_value("select TxLgID as value from texts where TxID = " . $_REQUEST['tid']);

pagestart("Term: " . $word,false);

$m1 = runsql('insert into words (WoLgID, WoText, WoTextLC, WoStatus, WoStatusChanged,' .  make_score_random_insert_update('iv') . ') values( ' . 
$langid . ', ' . 
convert_string_to_sqlsyntax($word) . ', ' . 
convert_string_to_sqlsyntax($wordlc) . ', 99, NOW(), ' .  
make_score_random_insert_update('id') . ')','Term added');
$wid = get_last_key();

echo "<p>OK, you know this term well!</p>";

$hex = strToClassName($wordlc);

?>
<script type="text/javascript">
//<![CDATA[
var context = window.parent.frames['l'].document;
var contexth = window.parent.frames['h'].document;
var title = make_tooltip(<?php echo prepare_textdata_js($word); ?>,'*','','99');
$('.TERM<?php echo $hex; ?>', context).removeClass('status0').addClass('status99 word<?php echo $wid; ?>').attr('data_status','99').attr('data_wid','<?php echo $wid; ?>').attr('title',title);
$('#learnstatus', contexth).html('<?php echo texttodocount2($_REQUEST['tid']); ?>');
window.parent.frames['l'].focus();
window.parent.frames['l'].setTimeout('cClick()', 100);
//]]>
</script>
<?php

pageend();

?> 

