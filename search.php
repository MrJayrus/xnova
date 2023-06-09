<?php
/**
 * This file is part of XNova:Legacies
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @see http://www.xnova-ng.org/
 *
 * Copyright (c) 2009-Present, XNova Support Team <http://www.xnova-ng.org>
 * All rights reserved.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *                                --> NOTICE <--
 *  This file is part of the core development branch, changing its contents will
 * make you unable to use the automatic updates manager. Please refer to the
 * documentation for further information about customizing XNova.
 *
 */

define('INSIDE' , true);
define('INSTALL' , false);
require_once dirname(__FILE__) .'/common.php';

$searchtext = isset($_POST['searchtext']) ? mysqli_real_escape_string(Database::$dbHandle, $_POST['searchtext']) : "";
$type = isset($_POST['type']) ? mysqli_real_escape_string(Database::$dbHandle, $_POST['type']) : ""; ;

includeLang('search');
$i = 0;
$search_results = "";
//creamos la query
switch($type){
	case "playername":
		$table = gettemplate('search_user_table');
		$row = gettemplate('search_user_row');
		$search = doquery("SELECT * FROM {{table}} WHERE username LIKE '%{$searchtext}%' LIMIT 30;","users");
	break;
	case "planetname":
		$table = gettemplate('search_user_table');
		$row = gettemplate('search_user_row');
		$search = doquery("SELECT * FROM {{table}} WHERE name LIKE '%{$searchtext}%' LIMIT 30",'planets');
	break;
	case "allytag":
		$table = gettemplate('search_ally_table');
		$row = gettemplate('search_ally_row');
		$search = doquery("SELECT * FROM {{table}} WHERE ally_tag LIKE '%{$searchtext}%' LIMIT 30","alliance");
	break;
	case "allyname":
		$table = gettemplate('search_ally_table');
		$row = gettemplate('search_ally_row');
		$search = doquery("SELECT * FROM {{table}} WHERE ally_name LIKE '%{$searchtext}%' LIMIT 30","alliance");
	break;
	default:
		$table = gettemplate('search_user_table');
		$row = gettemplate('search_user_row');
		$search = doquery("SELECT * FROM {{table}} WHERE username LIKE '%{$searchtext}%' LIMIT 30","users");
}
/*
  Esta es la tecnica de, "el ahorro de queries".
  Inventada por Perberos :3
  ...pero ahora no... porque tengo sueño ;P
*/
if(isset($searchtext) && isset($type)){

	$result_list = "";
	while($r = mysqli_fetch_array($search, MYSQLI_BOTH)){

		if($type=='playername'||$type=='planetname'){
			$s=$r;
			//para obtener el nombre del planeta
			if ($type == "planetname")
			{
			$pquery = doquery("SELECT * FROM {{table}} WHERE id = {$s['id_owner']}","users",true);
/*			$farray = mysqli_fetch_array($pquery);*/
			$s['planet_name'] = $s['name'];
			$s['username'] = $pquery['username'];
			$s['ally_name'] = ($pquery['ally_name']!='')?"<a href=\"alliance.php?mode=ainfo&tag={$pquery['ally_name']}\">{$pquery['ally_name']}</a>":'';
			}else{
			$pquery = doquery("SELECT name FROM {{table}} WHERE id = {$s['id_planet']}","planets",true);
			$s['planet_name'] = $pquery['name'];
			$s['ally_name'] = (isset($aquery) && isset($aquery['ally_name']) && $aquery['ally_name']!='')?"<a href=\"alliance.php?mode=ainfo&tag={$aquery['ally_name']}\">{$aquery['ally_name']}</a>":'';
			}
			//ahora la alianza
			if($s['ally_id']!=0&&$s['ally_request']==0){
				$aquery = doquery("SELECT ally_name FROM {{table}} WHERE id = {$s['ally_id']}","alliance",true);
			}else{
				$aquery = array();
			}



			$s['position'] = isset($s) && isset($s['rank']) ? "<a href=\"stat.php?start=".$s['rank']."\">".$s['rank']."</a>" : "-";
			$s['coordinated'] = "{$s['galaxy']}:{$s['system']}:{$s['planet']}";
			$s['buddy_request'] = $lang['buddy_request'];
			$s['write_a_messege'] = $lang['write_a_messege'];
			$result_list .= $MustacheEngine->render($row, $s);
		}elseif($type=='allytag'||$type=='allyname'){
			$s=$r;

			$s['ally_points'] = pretty_number($s['ally_points']);

			$s['ally_tag'] = "<a href=\"alliance.php?mode=ainfo&tag={$s['ally_tag']}\">{$s['ally_tag']}</a>";
			$result_list .= $MustacheEngine->render($row, $s);
		}
	}
	if($result_list!=''){
		$lang['result_list'] = $result_list;
		$search_results = $MustacheEngine->render($table, $lang);
	}
}

//el resto...
$lang['type_playername'] = (isset($_POST['type']) && $_POST["type"] == "playername") ? " SELECTED" : "";
$lang['type_planetname'] = (isset($_POST['type']) && $_POST["type"] == "planetname") ? " SELECTED" : "";
$lang['type_allytag'] = (isset($_POST['type']) && $_POST["type"] == "allytag") ? " SELECTED" : "";
$lang['type_allyname'] = (isset($_POST['type']) && $_POST["type"] == "allyname") ? " SELECTED" : "";
$lang['searchtext'] = $searchtext;
$lang['search_results'] = $search_results;
//esto es algo repetitivo ... w
$page = $MustacheEngine->render(gettemplate('search_body'), $lang);
display($page,$lang['Search']);