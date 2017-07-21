# nodes
Implementation of closure table for hierarchy data model operations in RDBMS 

Features
---
- Creates paths when you insert a new node
- Updates paths when you move a node
- Updates paths when you rename a node
- Can search for nodes by node id or node path 
- Can get node tree by node path

Install 
---
To install with composer: 
```
composer require kanoulis/nodes
```

Usage
---

Basic usage example:

You need to have permissions to create the sqlite database file.


```php
<?php
    require '/path/to/vendor/autoload.php';

    try {
        /* Open SQLite database */
        
        $pdo = new \PDO('sqlite:nodes.db');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA foreign_keys = ON');
        
        
        /* Create node tables if they do not exist */
        
        $pdo->exec('create table if not exists nodes(
            id integer primary key, 
            parent_id integer, 
            name text unique,
            foreign key (parent_id) references nodes (id) on update cascade on delete cascade
        )');
        
        $pdo->exec('create table if not exists nodes_closure(
            ancestor integer not null, 
            descendant integer not null, 
            depth integer not null, 
            path text not null,
            foreign key (ancestor) references nodes (id) on update cascade on delete cascade,
            foreign key (descendant) references nodes (id) on update cascade on delete cascade
        )');
        
        
        /* Select SQLite repository and initialize operations */
        
        $repo 	= new Nodes\NodeRepository\NodeRepositorySQLite($pdo);
        $nodes 	= new Nodes\Nodes($repo);
        
        
        /* Create some nodes */
        
        $space 		= $nodes->create((new Nodes\Node()));
        $earth 		= $nodes->create((new Nodes\Node())->setParent($space->_id)->setName('earth'));
        $continents 	= $nodes->create((new Nodes\Node())->setParent($earth->_id)->setName('continents'));
        $europe 	= $nodes->create((new Nodes\Node())->setParent($continents->_id)->setName('europe'));
        $asia	 	= $nodes->create((new Nodes\Node())->setParent($continents->_id)->setName('asia'));
        $oceans 	= $nodes->create((new Nodes\Node())->setParent($earth->_id)->setName('oceans'));
        $pacific 	= $nodes->create((new Nodes\Node())->setParent($oceans->_id)->setName('pacific'));
        $atlantic 	= $nodes->create((new Nodes\Node())->setParent($oceans->_id)->setName('atlantik'));
        $africa 	= $nodes->create((new Nodes\Node())->setParent($oceans->_id)->setName('africa'));
        
        /* Try some operations */
        print '<pre>';
        
        print "Show info about the African continent\n";
        print_r($africa);
        
        print "Africa is not an ocean, lets move it under continents!\n";
        $africa->setParent($continents->_id);
        print_r($nodes->update($africa));
        
        print "Show info about the Atlantic ocean\n";
        print_r($atlantic);
        
        print "Atlantic is spelled wrong, lets rename it!\n";
        $atlantic->setName('atlantic');
        print_r($nodes->update($atlantic));
        
        print "Breadcrumps to Asia!\n";
        print_r($nodes->parents($asia->_id));
        
        print "What can we find on earth?\n";
        print_r($nodes->siblings($earth->_id));
        
        print "Show everything on earth\n";
        print_r($nodes->tree($earth->_path));
        
        print "What is above earth?\n";
        print_r($nodes->parent($earth->_id));
        
        print "Get information by path\n";
        print_r($nodes->read('/earth/continents/'));
        
        print "Get information by id\n";
        print_r($nodes->read(false, 9));
        
        print "Get all continents by path\n";
        print_r($nodes->tree('/earth/continents/'));
        
        /* Destroy all */
        
        $nodes->delete($space->_id);
    }
    catch (\Exception $e) {

        echo $e->getMessage();
    }        
```
