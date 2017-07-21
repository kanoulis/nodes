<?php

	namespace Nodes;

	interface NodeRepository
	{
		public function create(Node $node);

		public function read($path, $id);

		public function update(Node $node);

		public function delete($id);

		public function tree($path);

		public function parent($id);

		public function parents($id);

		public function siblings($parent_id);
	}
