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
 * Class implements data backend for general mysql connections
 * 
 * @author Michael Knoll <knoll@punkt.de>
 * @package Typo3
 * @subpackage pt_extlist
 */
class Tx_PtExtlist_Domain_DataBackend_MySqlDataBackend_MySqlDataBackend extends Tx_PtExtlist_Domain_DataBackend_AbstractDataBackend {
	    
    /**
     * @var Tx_PtExtlist_Domain_DataBackend_DataSourceInterface
     */
    protected $dataSource;
    
    
    
    /**
     * Holds an instance of a query interpreter to be used for
     * query objects
     *
     * @var Tx_PtExtlist_Domain_DataBackend_AbstractQueryInterpreter
     */
    protected $queryInterpreter;

    
    
    /**
     * Factory method for data source
     * 
     * Only DataBackend knows, which data source to use and how to instantiate it.
     * So there cannot be a generic factory for data sources and data backend factory cannot instantiate it either!
     *
     * @param Tx_PtExtlist_Domain_Configuration_ConfigurationBuilder $configurationBuilder
     * @return Tx_PtExtlist_Domain_DataBackend_DataSource_MySqlDataSource Data source object for this data backend
     */
    public static function createDataSource(Tx_PtExtlist_Domain_Configuration_ConfigurationBuilder $configurationBuilder) {
        $backendConfiguration = $configurationBuilder->getBackendConfiguration();
        $dataSourceConfigurationArray = $backendConfiguration['dataSource'];
        $dataSourceConfigurationObject = new Tx_PtExtlist_Domain_Configuration_DataBackend_DataSource_DatabaseDataSourceConfiguration($configurationBuilder->getBackendConfiguration());
        $dataSource = new Tx_PtExtlist_Domain_DataBackend_DataSource_MySqlDataSource($dataSourceConfigurationObject);
        
        // TODO initialize PDO with given parameters!!!
        $dataSource->injectDataSource(new PDO());
        
        return $dataSource;
    }
    
    
       
    /**
     * Injector for data source
     *
     * @param mixed $dataSource
     */
    public function injectDataSource($dataSource) {
        $this->dataSource = $dataSource;
    }
    
    
    
    /**
     * Injector for query interpreter
     *
     * @param Tx_PtExtlist_Domain_DataBackend_AbstractQueryInterpreter $queryInterpreter
     */
    public function injectQueryInterpreter(Tx_PtExtlist_Domain_DataBackend_AbstractQueryInterpreter $queryInterpreter) {
    	$this->queryInterpreter = $queryInterpreter;
    }
	
		
	
    /**
     * Returns raw list data
     *
     * @return array Array of raw list data
     */
	public function getListData() {
		$sqlQuery = $this->buildQuery();
		$rawData = $this->dataSource->executeQuery($sqlQuery);
		return $rawData;
	}
	
	
	
	/**
	 * Builder for SQL query. Gathers information from
	 * all parts of plugin (ts-config, pager, filters etc.)
	 * and generates SQL query out of this information
	 *
	 * @return string An SQL query
	 */
	public function buildQuery() {
		$query = '';
		
		$query .= $this->buildSelectPart() != ''  ? 'SELECT ' . $this->buildSelectPart() . ' ' : '';
		$query .= $this->buildFromPart() != ''    ? 'FROM ' . $this->buildFromPart() . ' ' : '';
		$query .= $this->buildWherePart() != ''   ? 'WHERE ' . $this->buildWherePart() . ' ' : '';
		$query .= $this->buildOrderByPart() != '' ? 'ORDER BY ' . $this->buildOrderByPart() . ' ' : '';
		$query .= $this->buildLimitPart() != ''   ? 'LIMIT ' . $this->buildLimitPart() . ' ' : '';
		
		return $query;
	}
	
	
	
	/**
	 * Builds select part from all parts of plugin
	 *
	 * @return string SELECT part of query without 'SELECT'
	 */
	public function buildSelectPart() {
		$selectParts = array();
        foreach($this->fieldConfigurationCollection as $fieldConfiguration) { /* @var $fieldConfiguration Tx_PtExtlist_Domain_Configuration_Data_Fields_FieldConfig */
        	$selectParts[] = $this->getSelectPartFromFieldConfiguration($fieldConfiguration);
        }
		return implode(', ', $selectParts);
	}
	
	
	
	/**
	 * Returns select part for table and field given in field configuration
	 *
	 * @param Tx_PtExtlist_Domain_Configuration_Data_Fields_FieldConfig $fieldConfiguration
	 * @return string
	 */
	public function getSelectPartFromFieldConfiguration(Tx_PtExtlist_Domain_Configuration_Data_Fields_FieldConfig $fieldConfiguration) {
		$table = $fieldConfiguration->getTable();
		$field = $fieldConfiguration->getField();
		return $table . '.' . $field;
	}
	
	
	
	/**
	 * Builds from part from all parts of plugin
	 *
	 * @return string FROM part of query without 'FROM'
	 */
	public function buildFromPart() {
		tx_pttools_assert::isNotEmptyString($this->backendConfiguration['tables'], array('message' => 'Configuration for data backend tables must not be empty! 1280234420'));
		$fromPart = $this->backendConfiguration['tables'];
		return $fromPart;
	}
	
	
	
	/**
	 * Builds where part of query from all parts of plugin
	 *
	 * @return string WHERE part of query without 'WHERE'
	 */
	public function buildWherePart() {
		$wherePart = '';
		$wherePart .= $this->getBaseWhereClause() != '' ? $this->getBaseWhereClause() : '';
		$wherePart .= $this->getWhereClauseFromFilterboxes() != '' ? $this->getWhereClauseFromFilterboxes() : ''; 
		return $wherePart;
	}
	
	
	
	/**
	 * Returns base where clause from TS config
	 *
	 * @return string
	 */
	public function getBaseWhereClause() {
		return $this->backendConfiguration['baseWhereClause'];
	}
	
	
	
	/**
	 * Returns where clauses from all filterboxes of a given collection of filterboxes
	 *
	 * @return string WHERE clause from filterboxcollection without 'WHERE'
	 */
	public function getWhereClauseFromFilterboxes() {
		$whereClauses = array();
		foreach ($this->filterboxCollection as $filterBox) {
			$whereClauses[] = $this->getWhereClauseFromFilterbox($filterBox);
		}
		return '(' . implode(') AND (', $whereClauses) . ')';
	}
	
	
	
	/**
	 * Returns where clauses from all filters of a given filterbox
	 *
	 * @param Tx_PtExtlist_Domain_Model_Filter_Filterbox $filterbox
	 * @return string WHERE clause from filterbox without 'WHERE'
	 */
	public function getWhereClauseFromFilterbox(Tx_PtExtlist_Domain_Model_Filter_Filterbox $filterbox) {
		$whereClausesFromFilterbox = array();
		foreach($filterbox as $filter) {
			$whereClausesFromFilterbox[] = $this->getWhereClauseFromFilter($filter);
		}
		return '(' . implode(') AND (', $whereClausesFromFilterbox) . ')';
		
	}
	
	
	
	/**
	 * Returns WHERE clause for a given filter
	 *
	 * @param Tx_PtExtlist_Domain_Model_Filter_AbstractFilter $filter
	 * @return string WHERE clause for given filter without 'WHERE'
	 */
	public function getWhereClauseFromFilter(Tx_PtExtlist_Domain_Model_Filter_AbstractFilter $filter) {
		$whereClauseFromFilter = '';
		$whereClauseFromFilter = $this->queryInterpreter->getCriterias($filter->getFilterQuery());
		return $whereClauseFromFilter;
	}
	
	
	
	/**
	 * Builds order by part of query from all parts of plugin
	 *
	 * @return string ORDER BY part of query without 'ORDER BY'
	 */
	public function buildOrderByPart() {
		$orderByPart = '';
		// TODO implement me!
		// Implement column objects before!
		return $orderByPart;
	}
	
	
	
	/**
	 * Builds limit part of query from all parts of plugin
	 *
	 * @return string LIMIT part of query without 'LIMIT'
	 */
	public function buildLimitPart() {
		$limitPart = '';
		$pagerOffset = intval($this->pager->getCurrentPage()) * intval($this->pager->getItemsPerPage());
		$pagerLimit = intval($this->pager->getItemsPerPage());
		$limitPart .= $pagerOffset > 0 ? $pagerOffset . ':' : '';
		$limitPart .= $pagerLimit > 0 ? $pagerLimit : '';
		return $limitPart;
	}
	
}

?>