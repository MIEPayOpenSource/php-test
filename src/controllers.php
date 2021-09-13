<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addGlobal('user', $app['session']->get('user'));

    return $twig;
}));


$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', [
        'readme' => file_get_contents('README.md'),
    ]);
});


$app->match('/login', function (Request $request) use ($app) {
    $username = $request->get('username');
    $password = $request->get('password');

    if ($username) {
        $sql = "SELECT * FROM users WHERE username = '$username' and password = '$password'";
        $user = $app['db']->fetchAssoc($sql);

        if ($user){
            $app['session']->set('user', $user);
            return $app->redirect('/todo');
        }
    }

    return $app['twig']->render('login.html', array());
});


$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
});


$app->get('/todo/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    if ($id){
        $sql = "SELECT * FROM todos WHERE id = '$id'";
        $todo = $app['db']->fetchAssoc($sql);

        return $app['twig']->render('todo.html', [
            'todo' => $todo,
        ]);
    } else {
        $sql = "SELECT * FROM todos WHERE user_id = '${user['id']}'";
        $todos = $app['db']->fetchAll($sql);

        return $app['twig']->render('todos.html', [
            'todos' => $todos,
            'error' => $app['session']->getFlashBag()->get('error', []),
            'success' => $app['session']->getFlashBag()->get('success', []),
        ]);
    }
})
->value('id', null);


$app->post('/todo/add', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user_id = $user['id'];
    $description = $request->get('description');
    if(isset($description) && $description!=''){
        $sql = "INSERT INTO todos (user_id, description,status) VALUES ('$user_id', '$description','Pending')";
        $app['db']->executeUpdate($sql);
        $app['session']->getFlashBag()->add('success', 'Todo saved successfully!');
    }else{
        $app['session']->getFlashBag()->add('error', 'Description is required!');
    }
    return $app->redirect('/todo');
});


$app->match('/todo/delete/{id}', function ($id) use ($app) {
    if( $id ){
        $sql = "DELETE FROM todos WHERE id = '$id'";
        $app['db']->executeUpdate($sql);
        $app['session']->getFlashBag()->add('success', 'Todo delete successfully!');
    } else {
        $app['session']->getFlashBag()->add('error', 'Something went wrong!');
    }
    return $app->redirect('/todo');
});

$app->post('/todo/manage', function (Request $request) use ($app) {
    $id = $request->get('id');
    $submit = $request->get('submit');

    if( isset($id) && $id!='' && isset($submit) && $submit!=''){
        switch ($submit) {
            case 'Completed':
                $sql = "UPDATE todos SET status = 'Pending' WHERE id = '$id'";
                $app['db']->executeUpdate($sql);
                $app['session']->getFlashBag()->add('success', 'Todo update successfully!');
            break;
            case 'Pending':
                $sql = "UPDATE todos SET status = 'Completed' WHERE id = '$id'";
                $app['db']->executeUpdate($sql);
                $app['session']->getFlashBag()->add('success', 'Todo update successfully!');
            break;
            default:
            break;
        }
    } else {
        $app['session']->getFlashBag()->add('error', 'Something went wrong!');
    }
    return $app->redirect('/todo');
});

$app->get('/todo/{id}/json', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }
    if ( $id ){
        $sql = "SELECT * FROM todos WHERE id = '$id'";
        $todo = $app['db']->fetchAssoc($sql);
        return $app['twig']->render('todo_json.html', [
            'todo_json' => json_encode($todo,JSON_PRETTY_PRINT),
        ]);
    }
})
->value('id', null);
