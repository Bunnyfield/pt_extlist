<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Daniel Lienert <lienert@punkt.de>, Michael Knoll <knoll@punkt.de>
*  All rights reserved
*
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Translator class for simple criteria for extbase data backend interpreter
 *
 * @package Typo3
 * @subpackage pt_extlist
 * @author Michael Knoll <knoll@punkt.de>
 */
class Tx_PtExtlist_Domain_DataBackend_ExtBaseDataBackend_ExtBaseInterpreter_SimpleCriteriaTranslator 
    implements Tx_PtExtlist_Domain_DataBackend_ExtBaseDataBackend_ExtBaseInterpreter_ExtBaseCriteriaTranslatorInterface {
	
	/**
     * Translates a query an manipulates given query object
     * 
     * TODO check, if there is already a constraint added to extbase query and use AND constraint then
     * TODO use AND to connect more than one constraint
     *
     * @param Tx_PtExtlist_Domain_QueryObject_Criteria $criteria Criteria to be translated
     * @param Tx_Extbase_Persistence_Query $extbaseQuery Query to add criteria to
     * @param Tx_Extbase_Persistence_Repository $extbaseRepository Associated repository
     */
    public static function translateCriteria(
            Tx_PtExtlist_Domain_QueryObject_Criteria $criteria,
            Tx_Extbase_Persistence_Query $extbaseQuery,
            Tx_Extbase_Persistence_Repository $extbaseRepository) {

        tx_pttools_assert::isTrue(is_a($criteria, 'Tx_PtExtlist_Domain_QueryObject_SimpleCriteria'),
      	    array('message' => 'Criteria is not a simple criteria! 1281724991'));
      	/* @var $criteria Tx_PtExtlist_Domain_QueryObject_SimpleCriteria */
      	    
      	$propertyName = self::getPropertyNameByCriteria($criteria);
      	    
      	switch ($criteria->getOperator()) {
      		case '=' :
      		    $extbaseQuery->matching($extbaseQuery->equals($propertyName, $criteria->getValue()));
      		break;
      		
      		case '<' :
      		    $extbaseQuery->matching($extbaseQuery->lessThan($propertyName, $criteria->getValue()));   
      	    break;
      	    
      		case '>' :
      	        $extbaseQuery->matching($extbaseQuery->greaterThan($propertyName, $criteria->getValue()));
      		break;
      		
      		case '<=' :
      			$extbaseQuery->matching($extbaseQuery->lessThanOrEqual($propertyName, $criteria->getValue()));
      		break;
      		
      		case '>=' :
      		    $extbaseQuery->matching($extbaseQuery->greaterThanOrEqual($propertyName, $criteria->getValue()));
      		break;
      		
      		case 'LIKE' :
      			$extbaseQuery->matching($extbaseQuery->like($propertyName, $criteria->getValue()));
      		break;
      		
      		case 'IN' :
      			// TODO check mailinglist for solution!
      		   	throw new Exception('IN operator is currently not supported by extbase! 1281727495');
      	    break;
      		
      		default:
      			throw new Exception('No translation implemented for ' . $criteria->getOperator() . ' operator! 1281727494');
      		break;
      	}
      	
      	return $extbaseQuery;
		
	}
	
	
	
	/**
	 * Returns field name for a given criteria object
	 *
	 * @param Tx_PtExtlist_Domain_QueryObject_SimpleCriteria $criteria
	 * @return string Fieldname
	 */
	protected static function getPropertyNameByCriteria(Tx_PtExtlist_Domain_QueryObject_SimpleCriteria $criteria) {
		list($predot, $postdot) = explode('.', $criteria->getField());
		return $postdot != '' ? $postdot : $predot;
	}
	
}
 
?>