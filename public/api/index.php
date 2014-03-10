<?php

$app = new \Phalcon\Mvc\Micro();
$di = new \Phalcon\DI\FactoryDefault();

require_once __DIR__ . '/vendor/Swift/swift_required.php';
require_once __DIR__ . '/vendor/Swift/swift_init.php';
$loader = new \Phalcon\Loader();
$loader->registerDirs(array(
    __DIR__ . '/models/'
))->register();

$di->set('config', function(){
    return new \Phalcon\Config\Adapter\Ini("config.ini");
}, true);

$di->set('smtp', function() use ($di){
    $config = $di->getConfig();
    $mailer =  Swift_SmtpTransport::newInstance(
        $config->smtp->host,
        $config->smtp->port
    )
        ->setUsername($config->smtp->user)
        ->setPassword($config->smtp->pass)
        ->setEncryption($config->smtp->encryption);

    return $mailer;
}, true);

$di->set('db', function() use ($di){
    $config = $di->getConfig();
    return new \Phalcon\Db\Adapter\Pdo\Postgresql(array(
        "host" => $config->database->get('host', 'localhost'),
        "dbname" => $config->database->db,
        "username" => $config->database->user,
        "password" => $config->database->pass
    ));
}, true);

$app->setDI($di);

$app->get('/passwords/{passwordid}', function ($passwordid) use ($app, $di) {
    $res = $di->getResponse();

    $password = Passwords::findFirst(array(
        "conditions" => "id = ?0",
        "bind"       => array(0 => $passwordid)
    ));

    if(!$password->id){
        $res->setStatusCode(404, "Not Found");
        $res->setJsonContent(array(
            'error' => 'Password not found or expired.'
        ));
        $res->setHeader("Content-Type", "application/json");
        $res->send();
        return false;
    }

    // TODO: Move validation of view to Model
    // Check ips
    if(!$password->isAllowedIP($di->getRequest()->getClientAddress())){
        $res->setStatusCode(400, "Forbidden");
        $res->setJsonContent(array(
            'error' => 'IP Address outside of allowed range.'
        ));
        $res->setHeader("Content-Type", "application/json");
        $res->send();
        return false;
    }

    //Check max views
    if( $password->viewcount >= $password->maxviews && $password->maxviews > 0){
        $res->setStatusCode(400, "Forbidden");
        $res->setJsonContent(array(
            'error' => 'Password exceeded view count.'
        ));
        $res->setHeader("Content-Type", "application/json");
        $res->send();
        return false;
    }
    //Check account lock

    //check time
    $now = new DateTime();
    if( $password->expiration < $now->getTimestamp() ){
        $res->setStatusCode(400, "Forbidden");
        $res->setJsonContent(array(
            'error' => 'Password expired.'
        ));
        $res->setHeader("Content-Type", "application/json");
        $res->send();
        return false;
    }

    //Update the viewcount on any gets
    $password->viewcount = (integer) $password->viewcount + 1;
    $password->save();

    // ToDO: move to model
    // Remove unnecessary elements


    $res->setJsonContent($password->displayFields());
    $res->send();

    $log = Logs::findFirstById($password->count);
    $log->total_viewcount += 1;
    $log->save();

});

$app->get('/passwords/{passwordid}/views', function($passwordid) use ($app, $di){
    $res = $di->getResponse();

    $password = Passwords::findFirst(array(
        "conditions" => "id = ?0",
        "bind"       => array(0 => $passwordid)
    ));

    if(!$password->id){
        $res->setStatusCode(404, "Not Found");
        $res->setJsonContent(array(
            'error' => 'Password not found or expired.'
        ));
        $res->setHeader("Content-Type", "application/json");
        $res->send();
        return false;
    }

    $res->setJsonContent(array('count' => $password->viewcount));
    $res->send();
});

$app->post('/passwords', function() use ($app, $di){
    $res = $di->getResponse();
    $req = $di->getRequest()->getJsonRawBody();

    $password = Passwords::createFromRequestBody($req);


    if(!$password->create()){
        $res->setStatusCode(500, "Internal Error");
        $res->setJsonContent(array(
            'error' => implode('\n', $password->getMessages())
        ));
        $res->setHeader("Content-Type", "application/json");
        $res->send();
        return false;
    }

    $res->setJsonContent($password->toArray());

    $res->send();
});

$app->delete('/passwords/{passwordid}', function($passwordid){

    $password = Passwords::findFirst(array(
        "conditions" => "id = ?0",
        "bind"       => array(0 => $passwordid)
    ));

    if(!$password->id){
        $res->setStatusCode(404, "Not Found");
        $res->setJsonContent(array(
            'error' => 'Password not found or expired.'
        ));
        $res->setHeader("Content-Type", "application/json");
        $res->send();
        return false;
    }

    // TODO: verify deletion
    $password->delete();

    return true;
});

$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
});

$app->handle();
