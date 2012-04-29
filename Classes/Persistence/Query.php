<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The Query class used to run queries against the database
 */
class Tx_ExtbaseHijax_Persistence_Query extends Tx_Extbase_Persistence_Query {

	/**
	 * @var array
	 */
	protected $parameters = array();
	
	/**
	 * @var string
	 */
	protected $sqlStatement = '';
	
	/**
	 * @var Tx_ExtbaseHijax_Persistence_Parser_SQL
	 */
	protected $sqlParser;
	
	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING,
	 *  'bar' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
	 * )
	 * where 'foo' and 'bar' are property names.
	 *
	 * @param array $orderings The property names to order by
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	// TODO: implement this
	/*
	public function setOrderings(array $orderings) {
		$this->orderings = $orderings;

		return $this;
	}
	*/

	/**
	 * Returns the property names to order the result by. Like this:
	 * array(
	 *  'foo' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING,
	 *  'bar' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @return array
	 */
	// TODO: implement this
	/*
	public function getOrderings() {
		return $this->orderings;
	}
	*/

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param integer $limit
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function setLimit($limit) {
		parent::setLimit($limit);
		
		if ($this->statement) {
			$this->sqlParser->setLimitStatement($this->getLimitStatement());
			$statement = $this->sqlParser->toString();
			$this->statement = $this->qomFactory->statement($statement, $this->parameters);
		}
		return $this;
	}

	/**
	 * Resets a previously set maximum size of the result set. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function unsetLimit() {
		parent::unsetLimit();
		
		if ($this->statement) {
			$this->sqlParser->setLimitStatement($this->getLimitStatement());
			$statement = $this->sqlParser->toString();
			$this->statement = $this->qomFactory->statement($statement, $this->parameters);
		}
		return $this;
	}

	/**
	 * Sets the start offset of the result set to offset. Returns $this to
	 * allow for chaining (fluid interface)
	 *
	 * @param integer $offset
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function setOffset($offset) {
		parent::setOffset($offset);
		
		if ($this->statement) {
			$this->sqlParser->setLimitStatement($this->getLimitStatement());
			$statement = $this->sqlParser->toString();
			$this->statement = $this->qomFactory->statement($statement, $this->parameters);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	protected function getLimitStatement() {
		if ($this->limit || $this->offset) {
			$limitStatement = ' LIMIT ';
			if ($this->limit) {
				$limitStatement .= $this->limit;
			} else {
				$limitStatement .= ' 18446744073709551615';
			}
			if ($this->offset) {
				$limitStatement .= ' OFFSET '.$this->offset;
			}
		}
		
		return $limitStatement;
	}
	
	/**
	 * Sets the statement of this query programmatically. If you use this, you will lose the abstraction from a concrete storage
	 * backend (database).
	 *
	 * @param string $statement The statement
	 * @param array $paramerters An array of parameters. These will be bound to placeholders '?' in the $statement.
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function statement($statement, array $parameters = array()) {
		$this->parameters = $parameters;
		$this->sqlStatement = $statement;
		$this->sqlParser = Tx_ExtbaseHijax_Persistence_Parser_SQL::ParseString($statement);
		$this->statement = $this->qomFactory->statement($statement, $parameters);
		return $this;
	}
}
?>