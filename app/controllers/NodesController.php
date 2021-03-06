<?php

use Everyman\Neo4j\Traversal;
use Everyman\Neo4j\Relationship;

class NodesController extends BaseController {

    public function getIndex() {
        return View::make('nodes/index');
    }

    public function postSearch() {
        $word = strtolower(\Illuminate\Support\Facades\Input::get('word'));
        $client = new Everyman\Neo4j\Client(Config::get('database.connections.neo4j.default')['host']);
        $thesarusIndex = new Everyman\Neo4j\Index\NodeIndex($client, 'thesaurus');

        $matches = $thesarusIndex->query('approve:"1" && word:*' . urlencode($word) . '*');
        $results = array();
        foreach ($matches as $m) {
            $results[] = array('properties' => $m->getProperties(), 'id' => $m->getId());
        }
        return View::make('nodes/search', array('results' => $results));
    }

    public function getSearch() {

        return View::make('nodes/search');
    }

    public function getAdd() {
        if (!Sentry::check()) {
            return Redirect::to('/account/login');
        }
        return View::make('nodes/add');
    }

    /**
     * ajax request from model on graph-editor page
     * @param int|NULL $relatedId
     * @return Everyman\Neo4j\Node
     */
    public function getAddnode($relatedId = NULL) {
        if (!Sentry::check()) {
            return Redirect::to('/account/login');
        }
        $client = new Everyman\Neo4j\Client(Config::get('database.connections.neo4j.default')['host']);
        $word = strtolower(Input::get('word'));
        $node = Node::addNode($word);
        if ($relatedId) {
            $nodeRelated = $client->getNode($relatedId);
            $level = (int) Input::get('level');
            Node::addRelation($nodeRelated, $node, $level);
            Node::addRelation($node, $nodeRelated, $level);
        }
        return json_encode(array("id" => $node->getId()));
    }

    public function postAdd() {
        if (!Sentry::check()) {
            return Redirect::to('/account/login');
        }
        $user = Sentry::getUser();
        if (!$user || ( $user && !$user->hasAccess('canAdd') )) {
            App::abort(401, 'Not authenticated');
        }
        $word1 = strtolower(urlencode(Input::get('word1')));
        $word2 = strtolower(urlencode(Input::get('word2')));
        $language = strtolower(urlencode(Input::get('lang')));
        $level = (int) Input::get('level');

        $node1 = Node::addNode($word1, $language);
        $node2 = Node::addNode($word2, $language);

        Node::addRelation($node1, $node2, $level);
        Node::addRelation($node2, $node1, $level);
        return Redirect::to('nodes/graph/' . $node1->getId());
    }

    public function postAddSynonym() {
        if (!Sentry::check()) {
            return Redirect::to('/account/login');
        }
        $word1 = strtolower(urlencode(Input::get('word1')));
        $word2 = strtolower(urlencode(Input::get('word2')));
        $language = strtolower(urlencode(Input::get('lang')));

        $node1 = Node::addNode($word1, $language);
        $node2 = Node::addNode($word2, $language);

        Node::addRelation($node1, $node2, NULL, 'SYNONYM');
        Node::addRelation($node2, $node1, NULL, 'SYNONYM');
        return Redirect::to('nodes/graph/' . $node1->getId());
    }

    public function getGraph($id) {
        $client = new Everyman\Neo4j\Client(Config::get('database.connections.neo4j.default')['host']);
        $node = $client->getNode($id);
        $user = Sentry::getUser();
        $canView = $user && ($user->hasAccess('admin') || $user->hasAccess('editor'));
        if (!$canView && $node->getProperty('approve') < 1) {
            App::abort(404, 'Not Found');
        }
        $traversal = new Everyman\Neo4j\Traversal($client);
        $traversal->addRelationship('RELATED', Relationship::DirectionOut)
                ->setPruneEvaluator(Traversal::PruneNone)
                ->setReturnFilter(Traversal::ReturnAll)
                ->setMaxDepth(2);
        $nodes = $traversal->getResults($node, Traversal::ReturnTypeNode);

        $traversal2 = new Everyman\Neo4j\Traversal($client);
        $traversal2->addRelationship('SYNONYM', Relationship::DirectionOut)
                ->setPruneEvaluator(Traversal::PruneNone)
                ->setReturnFilter(Traversal::ReturnAll)
                ->setMaxDepth(2);
        $nodesSynonym = $traversal2->getResults($node, Traversal::ReturnTypeNode);
        foreach ($nodes as $tmp) {
            $nodeWords[] = urldecode($tmp->getProperty("word"));
        }
        $relations = array();
        foreach ($nodes as $n) {
            $rels = $n->getRelationships(array("RELATED"), Relationship::DirectionOut);
            /* @var $rel Everyman\Neo4j\Relationship */
            foreach ($rels as $rel) {
                $startNode = $rel->getStartNode();
                $endNode = $rel->getEndNode();
                $startWord = urldecode($startNode->getProperty("word"));
                $endWord = urldecode($endNode->getProperty("word"));
                $relArray['source'] = $startWord;
                $relArray['target'] = $endWord;
                $relArray["left"] = FALSE;
                $relArray["right"] = TRUE;
                if (in_array($startWord, $nodeWords) && in_array($endWord, $nodeWords)) {
                    $relations[] = $relArray;
                }
            }
        }
        return View::make('nodes/graph-editor', array(
                    'nodes' => $nodes,
                    'nodesSynonym' => $nodesSynonym,
                    'relations' => $relations,
                    'node' => $node,
                    'approveLabel' => ($node->getProperty('approve') < 1 ? 'danger' : 'success'),
                    'editor' => $user->hasAccess('editor'),
                    'admin' => $user->hasAccess('admin')));
    }

}
