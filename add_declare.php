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


		includeLang('admin/interface');

		$mode      = isset($_POST['mode']) ? $_POST['mode'] : '';

		$PageTpl   = gettemplate("add_declare");
		$parse     = $lang;

		if ($mode == 'addit') {
			$declarator             = $user['id'];
			$declarator_name  		= addslashes($user['username']);
			$decl1        	   		= isset($_POST['dec1'])   ? mysqli_real_escape_string(Database::$dbHandle, $_POST['dec1'])   : "";
			$decl2       		    = isset($_POST['dec2'])   ? mysqli_real_escape_string(Database::$dbHandle, $_POST['dec2'])   : "";
			$decl3        		    = isset($_POST['dec3'])   ? mysqli_real_escape_string(Database::$dbHandle, $_POST['dec3']) 	 : "";
			$reason1        	  	= isset($_POST['reason']) ? mysqli_real_escape_string(Database::$dbHandle, $_POST['reason']) : "";

			if($decl1 != "" && $decl1 != "" && $decl1 != "" && $decl1 != ""){
				$QryDeclare  = "INSERT INTO {{table}} SET ";
				$QryDeclare .= "`declarator` = '". $declarator ."', ";
				$QryDeclare .= "`declarator_name` = '". $declarator_name ."', ";
				$QryDeclare .= "`declared_1` = '". $decl1 ."', ";
				$QryDeclare .= "`declared_2` = '". $decl2 ."', ";
				$QryDeclare .= "`declared_3` = '". $decl3 ."', ";
				$QryDeclare .= "`reason`     = '". $reason1 ."' ";
	
				doquery( $QryDeclare, "declared");
				doquery("UPDATE {{table}} SET multi_validated ='1' WHERE username='{$user['username']}'","users");
	
				//@todo : translate
				Message ( "Merci, votre demande a ete prise en compte. Les autres joueurs que vous avez implique doivent egalement et imperativement suivre cette procedure aussi.", "Ajout" );
			}else{
				Message ( "Votre demande n'est pas correctement formulée.", "Erreur" );
			}
			
		}
		$Page = $MustacheEngine->render($PageTpl, $parse);

		display ($Page, "Declaration d\'IP partagee", false, '', true);