<?php
/**
* Хендлер sync.php
*
* @version 1.0.18 от 28.12.2012
*/

#_ZIP_NAME_sync.php_#  // Строчку убирать НЕЛЬЗЯ - это идентификатор файла для скрипта обновления

require_once($_SERVER['DOCUMENT_ROOT'].'/uniplacer_config.php');

require_once($_SERVER['DOCUMENT_ROOT'].'/'._UNIPLACE_USER_.'/uniplacer.php');

$Uniplacer = new Uniplacer(_UNIPLACE_USER_); // Создаём объект Uniplacer

if (!isset($HTTP_RAW_POST_DATA)) { 
	$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
}

if (strlen($HTTP_RAW_POST_DATA)) { // Если пришел список изменений и он не пустой
	$UniLinks = explode("\n", $HTTP_RAW_POST_DATA); // Разобъем по строкам в массив $UniLinks

	if (count($UniLinks)) { // Если хотя бы один элемент в массиве присутствует
		$result = $Uniplacer->process_links($UniLinks);
		$result ? $Uniplacer->return_success_header() : $Uniplacer->return_fail_header(); // Выясним, что с результатов и вернём      
	}

} else {
	$query = strtolower($_SERVER["QUERY_STRING"]);

	if ($query == "test") {
		echo $Uniplacer->return_success_header();
	}

	if ($query == "pages") {
		$Uniplacer->get_pageslog_data();
	}

    if($query == "cleardb"){
      echo $Uniplacer->clear_db();
    }

	$queryArray = explode("&", $query);
	if ($queryArray[0] == "links") {
		$Uniplacer->get_links_log(!isset($_GET["page"]) ? 0 : $_GET["page"]);
	}
}    

?>