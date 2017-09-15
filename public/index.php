<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Eluceo\iCal\Component\Calendar;
use \Eluceo\iCal\Component\Event;
use \Respect\Validation\Validator as V;

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
    return new \Cake\Database\Connection([
        'driver' => '\Cake\Database\Driver\Sqlite',
        'database' => '../sql/task.db'
    ]);
};

$container['view'] = function ($c) {
    return new \Slim\Views\PhpRenderer('../src/views');
};

$container['validator'] = function () {
    return new \Awurth\SlimValidation\Validator();
};

$app->get('/[{year:[0-9]+}/{month:[0-9]+}/{day:[0-9]+}]', function (Request $request, Response $response) {
    return $this->view->render($response, 'index.tpl');
});

$app->get('/api/items/{year}-{month}-{day}', function (Request $request, Response $response) {
    $year = $request->getAttribute('year');
    $month = $request->getAttribute('month');
    $day = $request->getAttribute('day');

    $sth = $this->db->newQuery()
        ->select('id, title, desc, date, done, schedule, startAt, endAt')
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
        $item['schedule'] = (bool)$item['schedule'];
        $items[] = $item;
    }

    return $response->withJson($items);
});

$app->post('/api/items', function (Request $request, Response $response) {
    $this->validator->validate($request, [
        'title' => [
            'rules' => V::notBlank()->length(1, 50),
            'message' => 'タイトルは1文字以上50文字以下でご入力ください。'
        ],
        'date' => [
            'rules' => V::notBlank()->date(),
            'message' => '登録日の指定に誤りがあります。'
        ]
    ]);

    if (! $this->validator->isValid()) {
        return $response->withJson($this->validator->getErrors(), 403);
    }

    $title = $request->getParsedBodyParam('title');
    $date = $request->getParsedBodyParam('date');

    $sth = $this->db->insert(
        'items',
        [
            'user_id' => 1,
            'title' => $title,
            'desc' => "",
            'date' => $date,
            'done' => 0,
            'schedule' => 0,
            'startAt' => "",
            'endAt' => ""
        ]
    );
    $id = $sth->lastInsertId();

    $item = $this->db->newQuery()
        ->select('id, title, desc, date, done, schedule, startAt, endAt')
        ->from('items')
        ->where(['id' => $id])
        ->execute()
        ->fetch('assoc');

    $item['done'] = (bool)$item['done'];
    $item['schedule'] = (bool)$item['schedule'];

    return $response->withJson($item, 201);
});

$app->put('/api/items/{id}', function (Request $request, Response $response) {
    $this->validator->validate($request, [
        'title' => [
            'rules' => V::notBlank()->length(1, 50),
            'message' => 'タイトルは1文字以上50文字以下でご入力ください。'
        ],
        'schedule' => [
            'rules' => V::boolType(),
            'message' => 'スケジュールチェックの指定に誤りがあります。'
        ],
        'startAt' => [
            'rules' => V::date("H:i"),
            'message' => '開始時の指定に誤りがあります。'
        ],
        'endAt' => [
            'rules' => V::date("H:i"),
            'message' => '終了時の指定に誤りがあります。'
        ],
        'date' => [
            'rules' => V::notBlank()->date(),
            'message' => '登録日の指定に誤りがあります。'
        ]
    ]);

    if (! $this->validator->isValid()) {
        return $response->withJson($this->validator->getErrors(), 403);
    }

    $id = $request->getAttribute('id');
    $title = $request->getParsedBodyParam('title');
    $desc = $request->getParsedBodyParam('desc');
    $done = $request->getParsedBodyParam('done');
    $date = $request->getParsedBodyParam('date');
    $schedule = $request->getParsedBodyParam('schedule');
    $startAt = $request->getParsedBodyParam('startAt');
    $endAt = $request->getParsedBodyParam('endAt');

    $this->db->update(
        'items',
        [
            'title' => $title,
            'desc' => $desc,
            'done' => (int)$done,
            'date' => $date,
            'schedule' => (int)$schedule,
            'startAt' => $startAt,
            'endAt' => $endAt
        ],
        ['id' => $id]
    );

    $item = $this->db->newQuery()
        ->select('id, title, desc, date, done, schedule, startAt, endAt')
        ->from('items')
        ->where(['id' => $id])
        ->execute()
        ->fetch('assoc');

    $item['done'] = (bool)$item['done'];
    $item['schedule'] = (bool)$item['schedule'];

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
        ->select('title, date, startAt, endAt')
        ->from('items')
        ->where('schedule', (int)true)
        ->execute();

    while ($item = $sth->fetch('assoc')) {
        $event = new Event();
        $event->setDtStart(new \DateTime(sprintf("%s %s", $item['date'], $item['startAt'])))
              ->setDtEnd(new \DateTime(sprintf("%s %s", $item['date'], $item['endAt'])))
              ->setSummary($item['title']);

        if (empty($item['startAt']) || empty($item['endAt'])) {
            $event->setNoTime(true);
        }

        $vCalendar->addComponent($event);
    }

    $body = $response->getBody();
    $body->write($vCalendar->render());

    return $response
        ->withHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->withHeader('Content-Disposition', 'attachment; filename="cal.ics"')
        ->withBody($body);
});

$app->run();
