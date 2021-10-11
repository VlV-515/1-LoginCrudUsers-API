<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET,POST");
header("Content-Type: application/json; charset=UTF-8");
require 'connection.php'; //Credenciales
$conectionDB = new mysqli($server, $user, $password, $db);
// Necesarios para el token jwt
require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;

//Recupero el token que viene en header 
$headers = apache_request_headers();

//Funcion para verificar que el token sea correcto
function checkToken($data)
{
    try {
        $jwt_decode = JWT::decode($data['token'], 'secret_secret', array('HS256'));
        if ($jwt_decode) {
            //Si existe, se extrae el role para hacer comparaciones.
            $role = (array)$jwt_decode;
            $role2 = (array)$role['data'];
            //Revisaamos que el role recibido sea el mismo role que se guardado en token.
            if ($role2['role'] === $data['role'] && $data['role']) {
                return true;
            } else {
                return false;
            }
        }
    } catch (Exception $e) {
        return false;
    }
}
//Revisa que el token este en orden
if (isset($_GET['checkToken'])) {
    if (checkToken($headers)) {
        echo json_encode(["msg" => 'OK']);
        exit();
    } else {
        echo json_encode(["msg" => 'error', 'reason' => 'Not Authorized']);
        exit();
    }
    echo json_encode(["msg" => 'error', 'reason' => 'Not Authorized']);
    exit();
}
//Retorna todos los usuarios disponibles
if (isset($_GET['users'])) {
    //Verifico que el token sea valido, si no, manda error.
    if (checkToken($headers)) {
        $sqlUsers = mysqli_query($conectionDB, "SELECT * FROM users ");
        if (mysqli_num_rows($sqlUsers) > 0) {
            $users = mysqli_fetch_all($sqlUsers, MYSQLI_ASSOC);
            echo json_encode($users);
            exit();
        } else {
            echo json_encode(["msg" => 'error', 'reason' => 'Error getting users']);
            exit();
        }
    }
    echo json_encode(["msg" => 'error', 'reason' => 'Not Authorized']);
    exit();
}
// Busca un usuario por ID y lo retorna.
if (isset($_GET["search"])) {
    //Verifico que el token sea valido, si no, manda error.
    if (checkToken($headers)) {
        $sqlUsers = mysqli_query($conectionDB, "SELECT * FROM users WHERE id=" . $_GET["search"]);
        if (mysqli_num_rows($sqlUsers) > 0) {
            $users = mysqli_fetch_all($sqlUsers, MYSQLI_ASSOC);
            echo json_encode($users);
            exit();
        } else {
            echo json_encode(["msg" => 'error', 'reason' => 'Data incorrect']);
            exit();
        }
    }
    echo json_encode(["msg" => 'error', 'reason' => 'Not Authorized']);
    exit();
}
// Inserta un nuevo usuario.
if (isset($_GET["insert"])) {
    //Verifico que el token sea valido, si no, manda error.
    if (checkToken($headers)) {
        $data = json_decode(file_get_contents("php://input"));
        $username = $data->username;
        $password = $data->password;
        $role = $data->role;
        $date = date("j/n/Y");
        if (($username != "") && ($password != "") && ($role != "")) {
            $sqlUsers = mysqli_query($conectionDB, "INSERT INTO users(username,password,role,createDate) VALUES('$username','$password','$role','$date')");
            if ($sqlUsers) {
                echo json_encode(["msg" => 'insert']);
                exit();
            } else {
                echo json_encode(["msg" => 'error', 'reason' => 'Data incorrect for insert']);
                exit();
            }
        }
    }
    echo json_encode(["msg" => 'error', 'reason' => 'Not Authorized']);
    exit();
}
// Actualiza los datos.
if (isset($_GET["update"])) {
    //Verifico que el token sea valido, si no, manda error.
    if (checkToken($headers)) {
        $data = json_decode(file_get_contents("php://input"));
        $id = (isset($data->id)) ? $data->id : $_GET["update"];
        $username = $data->username;
        $password = $data->password;
        $role = $data->role;
        $date = date("j/n/Y");
        if (($username != "") && ($password != "") && ($role != "")) {
            $sqlUsers = mysqli_query($conectionDB, "UPDATE users SET username='$username', password='$password', role='$role' WHERE id='$id'");
            if ($sqlUsers) {
                echo json_encode(["msg" => 'update']);
                exit();
            } else {
                echo json_encode(["msg" => 'error', 'reason' => 'Data incorrect for update']);
                exit();
            }
        }
    }
    echo json_encode(["msg" => 'error', 'reason' => 'Not Authorized']);
    exit();
}
// Borrar un usuario por ID.
if (isset($_GET["delete"])) {
    //Verifico que el token sea valido, si no, manda error.
    if (checkToken($headers)) {
        $sqlUsers = mysqli_query($conectionDB, "DELETE FROM users WHERE id=" . $_GET["delete"]);
        if ($sqlUsers) {
            echo json_encode(["msg" => 'delete']);
            exit();
        } else {
            echo json_encode(["msg" => 'error', 'reason' => 'Data incorrect for delete']);
            exit();
        }
    }
    echo json_encode(["msg" => 'error', 'reason' => 'Not Authorized']);
    exit();
}
/* LOGIN - JWT */
if (isset($_GET["login"])) {
    $data = json_decode(file_get_contents("php://input"));
    $username = $data->username;
    $password = $data->password;
    $sqlUsers = mysqli_query($conectionDB, "SELECT * FROM users WHERE username='$username' and password='$password'");
    if (mysqli_num_rows($sqlUsers) > 0) {
        $user = mysqli_fetch_all($sqlUsers, MYSQLI_ASSOC);
        $time = time();
        $key = 'secret_secret';
        /* JWT INICIO */
        //Construimos el token
        $token = array(
            'time' => $time, // Tiempo que inició el token
            'exp' => $time + (60 * 60), // Tiempo que expirará el token (+1 hora)
            'data' => [
                'id' => $user[0]['id'],
                'username' => $user[0]['username'],
                'role' => $user[0]['role']
            ]
        );
        //Generamos el token
        $jwt = JWT::encode($token, $key);
        //Preparamos el array de respuesta
        $arr = array(
            'msg' => 'OK',
            'id' => $user[0]['id'],
            'role' => $user[0]['role'],
            'token' => $jwt
        );
        //Encodeamos, respondemos y salimos.
        $data = json_encode($arr);
        echo $data;
        exit();
        // /* JWT FIN */
    } else {
        echo json_encode(["msg" => 'error', 'reason' => 'Data incorrect for login']);
        exit();
    }
}
/* 
//TODO ELIMINAR:, Retorna un nuevo token para pruebas
if (isset($_GET['tokeen'])) {
    $time = time();
    $key = 'secret_secret';
    $token = array(
        'time' => $time,
        'exp' => $time + ((60 * 60) * 60),
        'data' => [
            'id' => 99,
            'username' => 'prueba@prueba.com',
            'role' => 'admin'
        ]
    );
    //Generamos el token
    $jwt = JWT::encode($token, $key);
    echo json_encode($jwt);
    exit();
} 
*/
echo json_encode(["msg" => 'error', 'reason' => 'Index Error']);
exit();
