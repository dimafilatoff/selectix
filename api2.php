<?php
error_reporting(1);
session_start();
require 'vendor/autoload.php';

use Intervention\Image\ImageManagerStatic as Image;

class Planix
{
    protected $DB, $USER;

    function __construct($db, $user = null)
    {
        $this->DB = $db;
        $this->USER = $user;
    }

    public function save($table, $post)
    {
        //print_r($post);
        $id = (isset($post['id'])) ? (int)$post['id'] : null;
        unset($post['id']);
        foreach ($post as $key => $value) {
            if ($value === '')
                $post[$key] = 'null';
            else
                $post[$key] = "'" . $this->DB->real_escape_string($value) . "'";
        }
        if (!empty($id)) {
            $query = "UPDATE `" . $table . "` SET ";
            $q = "";
            foreach ($post as $key => $item) {
                if ($key != 'id') $q .= "`" . $key . "`=" . $item . ",";
            }
            $query .= substr($q, 0, -1) . " WHERE client_id=" . $this->USER['client_id'] . " AND id=" . $id;
            //exit($query);
            if ($this->DB->query($query) === false)
                return ['status' => 500, 'error' => $this->DB->error];
        } else {
            $post['client_id'] = $this->USER['client_id'];
            $query = "INSERT INTO `" . $table . "` ";
            $query .= "(`" . implode("`, `", array_keys($post)) . "`) ";
            $query .= "VALUES (" . implode(",", $post) . ") ";
            //exit($query);
            if ($this->DB->query($query) === false)
                return ['status' => 500, 'error' => $this->DB->error];
            $id = $this->DB->insert_id;
        }
        return $id;
    }

    public function delete($table, $id)
    {
        $id = $this->DB->real_escape_string($id);
        $query = "DELETE FROM `" . $table . "` WHERE client_id=" . $this->USER['client_id'] . " AND id IN (" . $id . ")";
        $this->DB->query($query);
    }

    public function stockItems($page = 0, $filters = [])
    {
        $filter = "";
        foreach ($filters as $key => $value) {
            if ($key == "q" and !empty($value)) $filter .= "stock.name LIKE '%" . $value . "%' AND ";
        }
        $limit = "LIMIT " . (int)$page * 50 . ",50";
        return $this->DB
            ->query("SELECT id,name,cat_id FROM stock WHERE " . $filter . " client_id=" . $this->USER['client_id'] . " ORDER BY name " . $limit)
            ->fetch_all(MYSQLI_ASSOC);
    }

}

$db = new mysqli(
    $config['hostname'],
    $config['username'],
    $config['password'],
    $config['database']
);
if ($db->connect_error) {
    exit('Connect Error (' . $db->connect_errno . ') ' . $db->connect_error);
}
$db->set_charset("utf8");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: *");
header("Content-Type: application/json");
$headers = getallheaders();

$app = new Planix($db, $user);

switch ($_GET['action']) {
    case "stock_items" :
        $filters = (isset($_GET['filters'])) ? json_decode($_GET['filters'], true) : [];
        $json = $app->stockItems($_GET['page'], $filters);
        break;
    case "stock_items_edit" :
        $id = $app->save("stock", [
            'id' => $_POST['id'],
            'name' => $_POST['name'],
            'cat_id' => $_POST['cat'],
            'min' => $_POST['min'],
            'max' => $_POST['max']
        ]);
        $json = $app->stockItem($id);
        break;
    case "stock_item" :
        $json = $app->stockItem($_GET['id']);
        break;
    case "stock_items_delete" :
        $app->delete("stock", $_POST['id']);
        break;
}
array_walk_recursive($json, function (&$item) {
    $item = strval($item);
});
echo json_encode($json);
