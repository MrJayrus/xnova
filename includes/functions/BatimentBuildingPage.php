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

function BatimentBuildingPage (&$CurrentPlanet, $CurrentUser) {
	global $lang, $resource, $reslist, $game_config, $_GET, $MustacheEngine;

	CheckPlanetUsedFields ( $CurrentPlanet );

	// Tables des batiments possibles par type de planete
	$Allowed['1'] = array(  1,  2,  3,  4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 44);
	$Allowed['3'] = array( 12, 14, 21, 22, 23, 24, 34, 41, 42, 43);

	// Boucle d'interpretation des eventuelles commandes
	if (isset($_GET['cmd'])) {
		// On passe une commande
		$bThisIsCheated = false;
		$bDoItNow       = false;
		$TheCommand     = $_GET['cmd'];
		$Element        = isset($_GET['building']) ? $_GET['building'] : "";
		$ListID         = isset($_GET['listid']) ? $_GET['listid'] : "";
		if ( !empty ( $Element )) {
			if ( !strchr ( $Element, " ") ) {
				if ( !strchr ( $Element, ",") ) {
				if ( !strchr ( $Element, ";") ) {
					if (in_array( trim($Element), $Allowed[$CurrentPlanet['planet_type']])) {
						$bDoItNow = true;
					} else {
						$bThisIsCheated = true;
					}
				} else {
					$bThisIsCheated = true;
				}
				} else {
                $bThisIsCheated = true;
            }
			} else {
				$bThisIsCheated = true;
			}
		} elseif ( isset ( $ListID ) && !empty($ListID)) {
			$bDoItNow = true;
		}
		if ($bDoItNow == true) {
			switch($TheCommand){
				case 'cancel':
					// Interrompre le premier batiment de la queue
					CancelBuildingFromQueue ( $CurrentPlanet, $CurrentUser );
					break;
				case 'remove':
					// Supprimer un element de la queue (mais pas le premier)
					// $RemID -> element de la liste a supprimer
					RemoveBuildingFromQueue ( $CurrentPlanet, $CurrentUser, $ListID );
					break;
				case 'insert':
					// Insere un element dans la queue
					AddBuildingToQueue ( $CurrentPlanet, $CurrentUser, $Element, true );
					break;
				case 'destroy':
					// Detruit un batiment deja construit sur la planete !
					AddBuildingToQueue ( $CurrentPlanet, $CurrentUser, $Element, false );
					break;
				default:
					break;
			} // switch
		} elseif ($bThisIsCheated == true) {
			ResetThisFuckingCheater ( $CurrentUser['id'] );
		}
	}

	SetNextQueueElementOnTop ( $CurrentPlanet, $CurrentUser );

	$Queue = ShowBuildingQueue ( $CurrentPlanet, $CurrentUser );

	// On enregistre ce que l'on a modifi� dans planet !
	BuildingSavePlanetRecord ( $CurrentPlanet );
	// On enregistre ce que l'on a eventuellement modifi� dans users
	BuildingSaveUserRecord ( $CurrentUser );

	if ($Queue['lenght'] < MAX_BUILDING_QUEUE_SIZE) {
		$CanBuildElement = true;
	} else {
		$CanBuildElement = false;
	}

	$SubTemplate         = gettemplate('buildings_builds_row');
	$BuildingPage        = "";
	foreach($lang['tech'] as $Element => $ElementName) {
		if (in_array($Element, $Allowed[$CurrentPlanet['planet_type']])) {
			$CurrentMaxFields      = CalculateMaxPlanetFields($CurrentPlanet);
			if ($CurrentPlanet["field_current"] < ($CurrentMaxFields - $Queue['lenght'])) {
				$RoomIsOk = true;
			} else {
				$RoomIsOk = false;
			}

			if (IsTechnologieAccessible($CurrentUser, $CurrentPlanet, $Element)) {
				$HaveRessources        = IsElementBuyable ($CurrentUser, $CurrentPlanet, $Element, true, false);
				$parse                 = array();
				$parse['i']            = $Element;
				$BuildingLevel         = $CurrentPlanet[$resource[$Element]];
				$parse['nivel']        = ($BuildingLevel == 0) ? "" : " (". $lang['level'] ." ". $BuildingLevel .")";
				$parse['n']            = $ElementName;
				$parse['descriptions'] = $lang['res']['descriptions'][$Element];
				$ElementBuildTime      = GetBuildingTime($CurrentUser, $CurrentPlanet, $Element);
				$parse['time']         = ShowBuildTime($ElementBuildTime);
				$parse['price']        = GetElementPrice($CurrentUser, $CurrentPlanet, $Element);
				$parse['rest_price']   = GetRestPrice($CurrentUser, $CurrentPlanet, $Element);
				$NextBuildLevel        = $CurrentPlanet[$resource[$Element]] + 1;

				$parse["BuildClickLabel"] = $lang['BuildFirstLevel'];
				$parse["BuildStartOK"]    = false;

				if ($Element == 31) {
					// Sp�cial Laboratoire
					if ($CurrentUser["b_tech_planet"] != 0 &&     // Si pas 0 y a une recherche en cours
						$game_config['BuildLabWhileRun'] != 1) {  // Variable qui contient le parametre
						// On verifie si on a le droit d'evoluer pendant les recherches (Setting dans config)
						$parse["BuildClickLabel"] = $lang['in_working'];
						$parse["BuildStartOK"]    = false;
					}
				}
				if ($RoomIsOk && $CanBuildElement) {
					if ($Queue['lenght'] == 0) {
						if ($NextBuildLevel == 1) {
							$parse["BuildClickLabel"] = $lang['BuildFirstLevel'];
							if ( $HaveRessources == true ) {
								$parse["BuildStartOK"]    = true;
							} else {
								$parse["BuildStartOK"]    = false;
							}
						} else {
							$parse["BuildClickLabel"] = $lang['BuildNextLevel'] ." ". $NextBuildLevel;
							if ( $HaveRessources == true ) {
								$parse["BuildStartOK"]    = true;
							} else {
								$parse["BuildStartOK"]    = false;
							}
						}
					} else {
						$parse["BuildClickLabel"] = $lang['InBuildQueue'];
						$parse["BuildStartOK"]    = true;
					}
				} elseif ($RoomIsOk && !$CanBuildElement) {
					if ($NextBuildLevel == 1) {
						$parse["BuildClickLabel"] = $lang['BuildFirstLevel'];
						$parse["BuildStartOK"]    = false;
					} else {
						$parse["BuildClickLabel"] = $lang['BuildNextLevel'] ." ". $NextBuildLevel;
						$parse["BuildStartOK"]    = false;
					}
				} else {
					$parse["BuildClickLabel"] = $lang['NoMoreSpace'];
					$parse["BuildStartOK"]    = false;
				}

				$BuildingPage .= $MustacheEngine->render($SubTemplate, $parse);
			}
		}
	}

	$parse                         = $lang;

	// Faut il afficher la liste de construction ??
	if ($Queue['lenght'] > 0) {
		$parse['BuildListScript']  = InsertBuildListScript ( "buildings", $lang);
		$parse['BuildList']        = $Queue['buildlist'];
	} else {
		$parse['BuildListScript']  = "";
		$parse['BuildList']        = "";
	}

    $parse['planet_field_current'] = $CurrentPlanet["field_current"];
    $parse['planet_field_max']     = $CurrentPlanet['field_max'] + ($CurrentPlanet[$resource[33]] * 5);
    $parse['field_libre']          = $parse['planet_field_max']  - $CurrentPlanet['field_current'];

	$parse['BuildingsList']        = $BuildingPage;

	$page                          = $MustacheEngine->render(gettemplate('buildings_builds'), $parse);

	display($page, $lang['BuildNextLevel']);
}
?>
