<?php

	namespace Nodes\NodeRepository;

	use Nodes\NodeRepository;
	use Nodes\Node;

	class NodeRepositorySQLite implements NodeRepository
	{
		private $client;

		public function __construct(\PDO $pdo)
		{
			$this->client = $pdo;
		}

		public function create(Node $node)
		{
			try {
				$this->client->beginTransaction();

				$sql = "
					INSERT INTO nodes (name, parent_id) VALUES(:name, :parent_id)
				";
				$stmt = $this->client->prepare($sql);
				$stmt->bindValue(':name', $node->_name, \PDO::PARAM_STR);
				$stmt->bindValue(':parent_id', $node->_parent_id, \PDO::PARAM_INT);
				$stmt->execute();
				$id = $this->client->lastInsertId();

				$sql = "
					INSERT INTO nodes_closure (ancestor, descendant, depth, path)
					SELECT ancestor, :id, depth + 1, path || :name
					FROM nodes_closure
					WHERE descendant = :parent_id
					UNION ALL
					SELECT :id, :id, 0, :name
				";
				$stmt = $this->client->prepare($sql);
				$stmt->bindValue(':id', $id, \PDO::PARAM_INT);
				$stmt->bindValue(':parent_id', ($node->_parent_id ?: $id), \PDO::PARAM_INT);
				$stmt->bindValue(':name', $node->_name . '/', \PDO::PARAM_STR);
				$stmt->execute();

				$this->client->commit();
				return $this->read(false,$id);
			}
			catch (\Exception $e) {
				$this->client->rollBack();
				throw $e;
			}
		}

		public function read($path, $id=false)
		{
			try {
				$search = $id ? 'nodes.id = :id' : 'c.path=:path';

				$sql = "
					SELECT 
					nodes.id, nodes.parent_id, nodes.name, c.depth, c.path 
					FROM nodes
					INNER JOIN nodes_closure c ON c.descendant = nodes.id
					WHERE ".$search." AND c.ancestor = 1
				";
				$stmt = $this->client->prepare($sql);
				$id ? $stmt->bindValue(':id', $id, \PDO::PARAM_INT) : $stmt->bindValue(':path', $path, \PDO::PARAM_STR);
				$stmt->execute();

				if ($res = $stmt->fetch(\PDO::FETCH_ASSOC))
					return $res;
			}
			catch (\Exception $e) {
				throw $e;
			}
		}

		public function update(Node $node)
		{
			try {
				$this->client->beginTransaction();

				if ($node->_old_name != $node->_name && $node->_old_name != '') {

					$sql = "UPDATE nodes SET name = :name WHERE id = :id";
					$stmt = $this->client->prepare($sql);
					$stmt->bindValue(':id', $node->_id, \PDO::PARAM_INT);
					$stmt->bindValue(':name', $node->_name, \PDO::PARAM_STR);
					$stmt->execute();

					$sql = "
						UPDATE nodes_closure
						SET path = replace(path, :old, :name)
						WHERE descendant IN (
								SELECT d FROM (
									SELECT descendant AS d FROM nodes_closure
									WHERE ancestor = :id
								) AS t1
							)
							AND ancestor IN (
								SELECT a FROM (
									SELECT ancestor AS a FROM nodes_closure
									WHERE descendant = :id
								) AS t2
							)
					";
					$stmt = $this->client->prepare($sql);
					$stmt->bindValue(':id', $node->_id, \PDO::PARAM_INT);
					$stmt->bindValue(':name', $node->_name, \PDO::PARAM_STR);
					$stmt->bindValue(':old', $node->_old_name, \PDO::PARAM_STR);
					$stmt->execute();
				}

				if ($node->_old_parent_id != $node->_parent_id && $node->_old_parent_id != ''){

					$sql = "UPDATE nodes SET parent_id = :parent_id WHERE id = :id";
					$stmt = $this->client->prepare($sql);
					$stmt->bindValue(':id', $node->_id, \PDO::PARAM_INT);
					$stmt->bindValue(':parent_id', $node->_parent_id, \PDO::PARAM_INT);
					$stmt->execute();

					$sql = "
						DELETE FROM nodes_closure
							WHERE descendant IN (
								SELECT d FROM (
									SELECT descendant as d FROM nodes_closure
									WHERE ancestor = :id
								) as t1
							)
							AND ancestor IN (
								SELECT a FROM (
									SELECT ancestor AS a FROM nodes_closure
									WHERE descendant = :id
									AND ancestor <> :id
								) as t2
							)
					";
					$stmt = $this->client->prepare($sql);
					$stmt->bindValue(':id', $node->_id, \PDO::PARAM_INT);
					$stmt->execute();

					$sql = "
						INSERT INTO nodes_closure (ancestor, descendant, depth, path)
						SELECT t1.ancestor, t2.descendant, t1.depth + t2.depth + 1, t1.path || t2.path
						FROM nodes_closure t1, nodes_closure t2
						WHERE t1.descendant = :parent_id
						AND t2.ancestor = :id;
					";
					$stmt = $this->client->prepare($sql);
					$stmt->bindValue(':parent_id', $node->_parent_id, \PDO::PARAM_INT);
					$stmt->bindValue(':id', $node->_id, \PDO::PARAM_INT);
					$stmt->execute();
				}
				$this->client->commit();

				return $this->read(false,$node->_id);
			}
			catch (\Exception $e) {
				$this->client->rollBack();
				throw $e;
			}
		}

		public function delete($id)
		{
			try {
				$sql = "
					DELETE FROM nodes WHERE id=:id
				";
				$stmt = $this->client->prepare($sql);
				$stmt->bindValue(':id', $id, \PDO::PARAM_INT);
				$stmt->execute();

				if ($stmt->rowCount())
					return true;
			}
			catch (\Exception $e) {
				throw $e;
			}
		}

		public function tree($path)
		{
			try {
				$nodes = [];
				$sql = "
					SELECT nodes.id, nodes.parent_id, nodes.name, c.depth, c.path 
					FROM nodes
					INNER JOIN nodes_closure c ON c.descendant = nodes.id AND c.ancestor = 1
					WHERE c.path LIKE :path 
					ORDER BY c.path ASC
				";
				$stmt = $this->client->prepare($sql);
				$stmt->bindValue(':path', $path . '%', \PDO::PARAM_STR);
				$stmt->execute();

				if ($res = $stmt->fetchAll(\PDO::FETCH_ASSOC)) {
					foreach( $res as $row)
						$nodes[] = $row;
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
				$sql = "
					SELECT nodes.id, nodes.parent_id, nodes.name, c.depth, (SELECT path FROM nodes_closure WHERE ancestor = 1 AND descendant = nodes.id) AS path
					FROM nodes_closure c
					INNER JOIN nodes ON nodes.id = c.ancestor
					WHERE c.descendant = :id AND c.depth = 1
					ORDER BY c.depth DESC;
				";
				$stmt = $this->client->prepare($sql);
				$stmt->bindValue(':id', $id, \PDO::PARAM_INT);
				$stmt->execute();
				if ($res = $stmt->fetch(\PDO::FETCH_ASSOC))
					return $res;
			}
			catch (\Exception $e) {
				throw $e;
			}
		}

		public function parents($id)
		{
			try {
				$sql = "
					SELECT nodes.id, nodes.parent_id, nodes.name, c.depth, (SELECT path FROM nodes_closure WHERE ancestor = 1 AND descendant = nodes.id) AS path
					FROM nodes_closure c
					INNER JOIN nodes ON nodes.id = c.ancestor
					WHERE c.descendant = :id AND nodes.id <> 1
					ORDER BY c.depth DESC;
				";
				$stmt = $this->client->prepare($sql);
				$stmt->bindValue(':id', $id, \PDO::PARAM_INT);
				$stmt->execute();
				if ($res = $stmt->fetchAll(\PDO::FETCH_ASSOC))
					return $res;
			}
			catch (\Exception $e) {
				throw $e;
			}
		}

		public function siblings($parent_id)
		{
			try {
				$sql = "
					SELECT nodes.*, c.depth, c.path FROM nodes
					INNER JOIN nodes_closure c ON c.descendant = nodes.id AND c.ancestor = 1
					WHERE nodes.parent_id = :parent_id AND c.depth > 0 
					ORDER BY path asc;
				";
				$stmt = $this->client->prepare($sql);
				$stmt->bindValue(':parent_id', $parent_id, \PDO::PARAM_INT);
				$stmt->execute();
				if ($res = $stmt->fetchAll(\PDO::FETCH_ASSOC))
					return $res;
			}
			catch (\Exception $e) {
				throw $e;
			}
		}
	}
