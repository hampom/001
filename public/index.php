<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$app = new \Slim\App;

$container = $app->getContainer();

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function($c) {
    return new \PDO('sqlite:../sql/task.db');
};

$app->get('/api/items/{year}-{month}-{day}', function (Request $request, Response $response) {
    $year = $request->getAttribute('year');
    $month = $request->getAttribute('month');
    $day = $request->getAttribute('day');

    $sth = $this->db->prepare('select id, title, desc, date, done from items where date = :date');
    $sth->execute([':date' => "$year-$month-$day"]);

    $items = $sth->fetchAll(PDO::FETCH_ASSOC);
    array_walk($items, function(&$v, $k) {
        if (isset($v['done'])) {
            $v['done'] = (bool)$v['done'];
        }
    });

    return $response->withJson($items);
});

$app->post('/api/items', function (Request $request, Response $response) {
    $title = $request->getParsedBodyParam('title');
    $date = $request->getParsedBodyParam('date');

    if (empty($title) || empty($date)) {
    }

    $sth = $this->db->prepare('insert into items(user_id, title, desc, date, done) values(1, :title, "", :date, 0)');
    $sth->execute([':title' => $title, ':date' => $date]);
    $lastId = $this->db->lastInsertId();

    $sth = $this->db->prepare('select id, title, desc, date, done from items where id = :last_id');
    $sth->execute([':last_id' => $lastId]);

    $item = $sth->fetch(PDO::FETCH_ASSOC);
    $item['done'] = (bool)$item['done'];

    return $response->withJson($item);
});

$app->put('/api/items/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');
    $title = $request->getParsedBodyParam('title');
    $desc = $request->getParsedBodyParam('desc');
    $done = $request->getParsedBodyParam('done');
    $date = $request->getParsedBodyParam('date');

    if (empty($title) || empty($desc) || empty($done) || empty($date)) {
    }

    $sth = $this->db->prepare('update items set title = :title, desc = :desc, done = :done, date = :date where id = :id');
    $sth->execute([
        ':title' => $title,
        ':desc' => $desc,
        ':done' => (int)$done,
        ':date' => $date,
        'id' => $id
    ]);

    $sth = $this->db->prepare('select id, title, desc, date, done from items where id = :id');
    $sth->execute([':id' => $id]);

    $item = $sth->fetch(PDO::FETCH_ASSOC);
    $item['done'] = (bool)$item['done'];

    return $response->withJson($item);
});

$app->delete('/api/items/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');

    $result = $this->db->query('select count(*) from items where id = :id');
    if ($result->fetchColumn() != 1) {
    }

    $sth = $this->db->prepare("delete from items where id = :id");
    $sth->execute([':id' => $id]);

    return $response->withJson([]);
});

$app->run();
