<?php

	namespace Nodes;

	class Nodes
	{
		private $repo;

		public function __construct(NodeRepository $repo)
		{
			$this->repo = $repo;
		}

		public function create(Node $node)
		{
			try {
				$node = $this->repo->create($node);
				return new Node($node);
			}
			catch (\Exception $e) {

				if (strpos($e->getMessage(), 'UNIQUE') !== false) {
					return $this->read($node->_path);
				}
			}
		}

		public function read($path, $id=false)
		{
			try {
				$node = $this->repo->read($path,$id);
				return new Node($node);
			}
			catch (\Exception $e) {
				throw $e;
			}
		}

		public function update(Node $node)
		{
			try {
				$node = $this->repo->update($node);
				return new Node($node);
			}
			catch (\Exception $e) {
				throw $e;
			}
		}

		public function delete($id)
		{
			try {
				return $this->repo->delete($id);
			}
			catch (\Exception $e) {
				throw $e;
			}
		}

		public function tree($id)
		{
			try {
				$nodes = [];
				if($data = $this->repo->tree($id)) {
					foreach ($data as $row)
						$nodes[] = new Node($row);
					return $nodes;
				}
			}
			catch (\Exception $e) {
				throw $e;
			}
		}

		public function parent($id)
		{
			try {
				$node = $this->repo->parent($id);
				return new Node($node);
			}
			catch (\Exception $e) {
				throw $e;
			}
		}

		public function parents($id)
		{
			try {
				$nodes = [];
				if($data = $this->repo->parents($id)) {
					foreach ($data as $row)
						$nodes[] = new Node($row);
					return $nodes;
				}
			}
			catch (\Exception $e) {
				throw $e;
			}
		}

		public function siblings($parent_id)
		{
			try {
				$nodes = [];
				if($data = $this->repo->siblings($parent_id)) {
					foreach ($data as $row)
						$nodes[] = new Node($row);
					return $nodes;
				}
			}
			catch (\Exception $e) {
				throw $e;
			}
		}
	}
