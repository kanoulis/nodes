<?php

	namespace Nodes;

	class Node
	{
		protected $_id;
		protected $_parent_id;
		protected $_name;
		protected $_depth;
		protected $_path;
		private $_old_parent_id;
		private $_old_name;

		public function __construct($node=null)
		{
			$this->_id			= isset($node['id'])		? $node['id'] 			: null;
			$this->_parent_id 	= isset($node['parent_id'])	? $node['parent_id'] 	: null;
			$this->_name 		= isset($node['name'])		? $node['name'] 		: null;
			$this->_depth		= isset($node['depth'])		? $node['depth'] 		: null;
			$this->_path		= isset($node['path'])		? $node['path'] 		: null;
		}

		public function __get($key)
		{
			if (in_array($key, ['_id', '_parent_id', '_name', '_depth', '_path', '_old_name', '_old_parent_id']))
				return $this->$key;
		}

		public function setId($value)
		{
			$this->_id = $value;
			return $this;
		}

		public function setParent($value)
		{
			$this->_old_parent_id = $this->_parent_id;
			$this->_parent_id = $value;
			return $this;
		}

		public function setName($value)
		{
			$this->_old_name = $this->_name;
			$this->_name = $value;
			return $this;
		}

		public function setDepth($value)
		{
			$this->_depth = $value;
			return $this;
		}

		public function setPath($value)
		{
			$this->_path = $value;
			return $this;
		}
	}
