<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Eluceo\iCal\Component\Calendar;
use \Eluceo\iCal\Component\Event;
use \Cake\Database\Connection;

require '../vendor/autoload.php';

$app = new \Slim\App;

$container = $app->getContainer();

$container['logger'] = function ($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function ($c) {
    return new Connection([
        'driver' => '\Cake\Database\Driver\Sqlite',
        'database' => '../sql/task.db'
    ]);
};

$container['view'] = function ($c) {
    return new \Slim\Views\PhpRenderer('../src/views');
};

$app->get('/[{year:[0-9]+}/{month:[0-9]+}/{day:[0-9]+}]', function (Request $request, Response $response) {
    return $this->view->render($response, 'index.tpl');
});

$app->get('/api/items/{year}-{month}-{day}', function (Request $request, Response $response) {
    $year = $request->getAttribute('year');
    $month = $request->getAttribute('month');
    $day = $request->getAttribute('day');

    $sth = $this->db->newQuery()
        ->select('id, title, desc, date, done')
        ->from('items')
        ->where(['date <' => "$year-$month-$day"])
        ->andWhere(['done' => (int)false])
        ->orWhere(['date' => "$year-$month-$day"])
        ->order([
            'date' => 'ASC',
            'id' => 'ASC',
        ])
        ->execute();

    $items = [];
    while ($item = $sth->fetch('assoc')) {
        $item['done'] = (bool)$item['done'];
        $items[] = $item;
    }

    return $response->withJson($items);
});

$app->post('/api/items', function (Request $request, Response $response) {
    $title = $request->getParsedBodyParam('title');
    $date = $request->getParsedBodyParam('date');

    if (empty($title) || empty($date)) {
    }

    $sth = $this->db->insert(
        'items',
        ['user_id' => 1, 'title' => $title, 'desc' => "", 'date' => $date, 'done' => 0]
    );
    $id = $sth->lastInsertId();

    $item = $this->db->newQuery()
        ->select('id, title, desc, date, done')
        ->from('items')
        ->where(['id' => $id])
        ->execute()
        ->fetch('assoc');

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

    $this->db->update(
        'items',
        ['title' => $title, 'desc' => $desc, 'done' => (int)$done, 'date' => $date],
        ['id' => $id]
    );

    $sth = $this->db->prepare('select id, title, desc, date, done from items where id = :id');
    $sth->execute([':id' => $id]);

    $item = $this->db->newQuery()
        ->select('id, title, desc, date, done')
        ->from('items')
        ->where(['id' => $id])
        ->execute()
        ->fetch('assoc');

    $item['done'] = (bool)$item['done'];

    return $response->withJson($item);
});

$app->delete('/api/items/{id}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id');

    $this->db->delete(
        'items',
        ['id' => $id]
    );

    return $response->withJson([]);
});

$app->get('/ical', function (Request $request, Response $response) {
    $vCalendar = new Calendar("default");

    $sth = $this->db->newQuery()
        ->select('title, date')
        ->from('items')
        ->execute();

    $items = [];
    while ($item = $sth->fetch('assoc')) {
        if (isset($item['done'])) {
            $item['done'] = (bool)$item['done'];
        }

        $vCalendar->addComponent(
            (new Event())
                ->setDtStart(new \DateTime($item['date']))
                ->setDtEnd(new \DateTime($item['date']))
                ->setNoTime(true)
                ->setSummary($item['title'])
        );
    }

    $body = $response->getBody();
    $body->write($vCalendar->render());

    return $response
        ->withHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->withHeader('Content-Disposition', 'attachment; filename="cal.ics"')
        ->withBody($body);
});

$app->run();
