<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Eluceo\iCal\Component\Calendar;
use \Eluceo\iCal\Component\Event;
use \Respect\Validation\Validator as V;
use \Slim\Middleware\HttpBasicAuthentication\PdoAuthenticator;

require '../vendor/autoload.php';

const DB = __DIR__ . "/../sql/task.db";

$pdo = new \PDO("sqlite:" . DB);
$app = new \Slim\App;

$container = $app->getContainer();

$container['logger'] = function ($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function ($c) {
    return new \Cake\Database\Connection(
        [
            'driver' => '\Cake\Database\Driver\Sqlite',
            'database' => DB
        ]
    );
};

$container['view'] = function ($c) {
    return new \Slim\Views\PhpRenderer('../src/views');
};

$container['validator'] = function () {
    return new \Awurth\SlimValidation\Validator();
};

$container["jwt"] = function ($container) {
    return new StdClass;
};

$app->add(
    new \Slim\Middleware\JwtAuthentication(
        [
            "path" => "/api",
            "passthrough" => ["/api/token"],
            "secret" => "supersecretkeyyoushouldnotcommittogithub",
            "callback" => function ($request, $response, $arguments) use ($container) {
                $container["jwt"] = $arguments["decoded"];
            }
        ]
    )
);

$app->add(
    new \Slim\Middleware\HttpBasicAuthentication(
        [
            "path" => "/api/token",
            "realm" => "Protected",
            "authenticator" => new PdoAuthenticator(
                [
                    "pdo" => $pdo,
                    "table" => "users",
                    "user" => "user_name",
                    "hash" => "password",
                ]
            )
        ]
    )
);

$app->post(
    '/api/token_refresh',
    function (Request $request, Response $response) {
        $now = new \DateTime();
        $future = new \DateTime("now +2 hours");
        $jti = (new \Tuupola\Base62)->encode(random_bytes(16));

        $payload = [
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "jti" => $jti,
            "user_id" => $this->jwt->user_id,
        ];

        $secret = "supersecretkeyyoushouldnotcommittogithub";
        //$secret = getenv("JWT_SECRET");
        $token = \Firebase\JWT\JWT::encode($payload, $secret, "HS256");
        $data["token"] = $token;
        $data["expires"] = $future->getTimeStamp();
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
);

$app->post(
    '/api/token',
    function (Request $request, Response $response) {
        $now = new \DateTime();
        $future = new \DateTime("now +2 hours");
        $server = $request->getServerParams();
        $jti = (new \Tuupola\Base62)->encode(random_bytes(16));

        $user = $this->db->newQuery()
            ->select('id')
            ->from('users')
            ->where(['user_name' => $server['PHP_AUTH_USER']])
            ->execute()
            ->fetch('assoc');

        $payload = [
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "jti" => $jti,
            "user_id" => $user['id'],
        ];

        $secret = "supersecretkeyyoushouldnotcommittogithub";
        //$secret = getenv("JWT_SECRET");
        $token = \Firebase\JWT\JWT::encode($payload, $secret, "HS256");
        $data["token"] = $token;
        $data["expires"] = $future->getTimeStamp();
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
);

$app->get(
    '/[{year:[0-9]+}/{month:[0-9]+}/{day:[0-9]+}]',
    function (Request $request, Response $response) {
        return $this->view->render($response, 'index.tpl');
    }
);

$app->get(
    '/app-shell',
    function (Request $request, Response $response) {
        return $this->view->render($response, 'index.tpl');
    }
);

$app->get(
    '/today',
    function (Request $request, Response $response) {
        return $this->view->render($response, 'index.tpl');
    }
);

$app->get(
    '/api/items/{year}-{month}-{day}',
    function (Request $request, Response $response) {
        $year = $request->getAttribute('year');
        $month = $request->getAttribute('month');
        $day = $request->getAttribute('day');

        $sth = $this->db->newQuery()
            ->select('id, title, desc, date, done_date, schedule, startAt, endAt')
            ->from('items')
            ->where(['date <' => "$year-$month-$day"])
            ->andWhere(
                function ($exp) use ($year, $month, $day) {
                    return $exp->or_(
                        [
                            $exp->isNull('done_date'),
                            'done_date' => "$year-$month-$day"
                        ]
                    );
                }
            )
            ->orWhere(['date' => "$year-$month-$day"])
            ->order(
                [
                    'date' => 'ASC',
                    'id' => 'ASC',
                ]
            )
            ->execute();

        $items = [];
        while ($item = $sth->fetch('assoc')) {
            $tags = $this->db->newQuery()
                ->select('tags.name')
                ->from('tags')
                ->innerJoin(
                    'items_tags',
                    [
                        'items_tags.tag_id = tags.id'
                    ]
                )
                ->where(
                    [
                        'items_tags.item_id' => $item['id']
                    ]
                )
                ->execute()
                ->fetchAll('assoc');

            $item['tags'] = is_null($tags) ? []: \Cake\Utility\Hash::extract($tags, '{n}.name');

            $item['done'] = !is_null($item['done_date']);
            $item['schedule'] = (bool)$item['schedule'];
            $items[] = $item;
        }

        return $response->withJson($items);
    }
);

$app->post(
    '/api/items',
    function (Request $request, Response $response) {
        $this->validator->validate(
            $request,
            [
                'title' => [
                    'rules' => V::notBlank()->length(1, 50),
                    'message' => 'タイトルは1文字以上50文字以下でご入力ください。'
                ],
                'date' => [
                    'rules' => V::notBlank()->date(),
                    'message' => '登録日の指定に誤りがあります。'
                ]
            ]
        );

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
                'done_date' => null,
                'schedule' => 0,
                'startAt' => "",
                'endAt' => ""
            ]
        );
        $id = $sth->lastInsertId();

        $item = $this->db->newQuery()
            ->select('id, title, desc, date, done_date, schedule, startAt, endAt')
            ->from('items')
            ->where(['id' => $id])
            ->execute()
            ->fetch('assoc');

        $item['done'] = !is_null($item['done_date']);
        $item['schedule'] = (bool)$item['schedule'];

        return $response->withJson($item, 201);
    }
);

$app->put(
    '/api/items/{id}',
    function (Request $request, Response $response) {
        $this->validator->validate(
            $request,
            [
                'title' => [
                    'rules' => V::notBlank()->length(1, 50),
                    'message' => 'タイトルは1文字以上50文字以下でご入力ください。'
                ],
                'schedule' => [
                    'rules' => V::boolType(),
                    'message' => 'スケジュールチェックの指定に誤りがあります。'
                ],
                'startAt' => [
                    'rules' => V::Optional(V::date("H:i")),
                    'message' => '開始時の指定に誤りがあります。'
                ],
                'endAt' => [
                    'rules' => V::Optional(V::date("H:i")),
                    'message' => '終了時の指定に誤りがあります。'
                ],
                'date' => [
                    'rules' => V::notBlank()->date(),
                    'message' => '登録日の指定に誤りがあります。'
                ]
            ]
        );

        if (! $this->validator->isValid()) {
            return $response->withJson($this->validator->getErrors(), 403);
        }

        $this->logger->info(var_export($request->getParsedBody(), true));
        $id = $request->getAttribute('id');
        $title = $request->getParsedBodyParam('title');
        $desc = $request->getParsedBodyParam('desc');
        $done = $request->getParsedBodyParam('done');
        $date = $request->getParsedBodyParam('date');
        $schedule = $request->getParsedBodyParam('schedule');
        $startAt = $request->getParsedBodyParam('startAt');
        $endAt = $request->getParsedBodyParam('endAt');
        $tags = $request->getParsedBodyParam('tags');

        // item
        $this->db->update(
            'items',
            [
                'title' => $title,
                'desc' => $desc,
                'done_date' => $done ? (new \DateTime())->format("Y-m-d"): null,
                'date' => $date,
                'schedule' => (int)$schedule,
                'startAt' => $startAt,
                'endAt' => $endAt
            ],
            ['id' => $id]
        );

        // tag
        $this->db->transactional(
            function ($conn) use ($id, $tags) {
                $this->db->delete(
                    'items_tags',
                    ['item_id' => $id]
                );

                if (count($tags) == 0) {
                    return;
                }

                $insertStmt = $this->db->newQuery()
                    ->insert(['item_id', 'tag_id'])
                    ->into('items_tags');

                foreach ($tags as $tag) {
                    $stmt = $this->db->newQuery()
                        ->select('id')
                        ->from('tags')
                        ->where(['name' => $tag])
                        ->execute();

                    if ($stmt->rowCount() > 0) {
                        $tagId = $stmt->fetch('assoc')['id'];
                    } else {
                        $sth = $this->db->insert(
                            'tags',
                            ['name' => $tag]
                        );
                        $tagId = $sth->lastInsertId();
                    }

                    $insertStmt->values(
                        [
                            'item_id' => $id,
                            'tag_id' => $tagId
                        ]
                    );
                }
                $insertStmt->execute();
            }
        );

        $item = $this->db->newQuery()
            ->select('id, title, desc, date, done_date, schedule, startAt, endAt')
            ->from('items')
            ->where(['id' => $id])
            ->execute()
            ->fetch('assoc');

        $item['done'] = !is_null($item['done_date']);
        $item['schedule'] = (bool)$item['schedule'];

        return $response->withJson($item);
    }
);

$app->delete(
    '/api/items/{id}',
    function (Request $request, Response $response) {
        $id = $request->getAttribute('id');

        $this->db->delete(
            'items',
            ['id' => $id]
        );

        return $response->withJson([]);
    }
);

$app->get(
    '/ical',
    function (Request $request, Response $response) {
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
    }
);

$app->run();
