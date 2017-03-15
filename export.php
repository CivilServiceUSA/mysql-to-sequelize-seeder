<?php

$config = parse_ini_file(realpath(dirname(__FILE__)) . '/config.ini');
$options = getopt("", array('table:', 'limit:', 'where:', 'offset:', 'order:', 'sort:', 'output:', 'singlequote'));

define('DB_HOST', $config['host']);
define('DB_USER', $config['username']);
define('DB_PASS', $config['password']);
define('DB_NAME', $config['dbname']);
define('DB_TABLE', isset($options['table']) ? $options['table'] : null);
define('EXPORT_PATH', isset($options['output']) ? $options['output'] : null);
define('LIMIT', isset($options['limit']) ? $options['limit'] : null);
define('OFFSET', isset($options['offset']) ? $options['offset'] : null);
define('ORDER_BY', isset($options['order']) ? $options['order'] : null);
define('SORT', isset($options['sort']) ? $options['sort'] : null);
define('WHERE', isset($options['where']) ? $options['where'] : null);
define('SINGLE_QUOTE', isset($options['singlequote']) ? $options['singlequote'] : null);

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (empty(DB_TABLE)) {
    exit('× Missing Required --table parameter' . PHP_EOL);
}

if (empty(EXPORT_PATH)) {
    exit('× Missing Required --output parameter' . PHP_EOL);
}

// Make sure we have a MySQL Connection
if ($mysqli) {

    $query = "SELECT *, ST_AsText(shape) as shape FROM `" . DB_TABLE . "`";

    if (!empty(WHERE)) {
        $query .= " WHERE " . WHERE;
    }

    if (!empty(ORDER_BY)) {
        $sort = (!empty(SORT)) ? SORT : 'ASC';
        $query .= " ORDER BY " . ORDER_BY . ' ' . $sort;
    }

    if (!empty(LIMIT)) {
        $query .= " LIMIT " . LIMIT;
    }

    if (!empty(OFFSET)) {
        $query .= " OFFSET " . OFFSET;
    }

    $update_columns = array();
    $insert = array();

    if ($result = $mysqli->query($query)) {

        while ($field = mysqli_fetch_field($result)) {
            if ($field->name !== 'id' && $field->name !== 'created_date' && $field->name !== 'deleted_at') {
                $update_columns[] = $field->name;
            }
        }

        while ($row = $result->fetch_assoc()) {
            $data = array();
            for ($i = 0; $i < count($update_columns); $i++) {
                if ($update_columns[$i] !== 'modified_date') {
                    $data[$update_columns[$i]] = $row[$update_columns[$i]];
                }

                if ($update_columns[$i] === 'shape') {
                    $data[$update_columns[$i]] = 'queryInterface.sequelize.fn(\'GeomFromText\', \'' . $row[$update_columns[$i]] . '\')';
                }
            }

            $data['created_date'] = "new Date()";
            $data['modified_date'] = "new Date()";

            $insert[] = json_encode($data, JSON_PRETTY_PRINT);
        }

        $columns = join("','", $update_columns);
        $json = join(",", $insert);

        $json = str_replace('"new Date()"', 'new Date()', $json);
        $json = str_replace('"created_date"', 'created_date', $json);
        $json = str_replace('"queryInterface', 'queryInterface', $json);
        $json = str_replace('\')",', '\'),', $json);
        $json = str_replace('\/', '/', $json);
        $json = str_replace(': ""', ': null', $json);
        $json = str_replace(': "true"', ': true', $json);
        $json = str_replace(': "false"', ': false', $json);
        $json = preg_replace('/"([[0-9.-]+)"/i', '$1', $json);
        $json = preg_replace('/"zipcode": ([0-9]{5})/i', '"zipcode": "$1"', $json);

        for ($i = 0; $i < count($update_columns); $i++) {
            $json = str_replace('"' . $update_columns[$i] . '"', $update_columns[$i], $json);
        }

        if (SINGLE_QUOTE) {
            $json = str_replace('"', "'", $json);
        }

        $seeder = <<<EOD
module.exports = {
  up: function (queryInterface) {
    return queryInterface.bulkInsert('{$options['table']}', [
        {$json}
    ], {
      updateOnDuplicate: ['{$columns}']
    }).catch(function (err) {
      if (err && err.errors) {
        for (var i = 0; i < err.errors.length; i++) {
          console.error('× SEED ERROR', err.errors[i].type, err.errors[i].message, err.errors[i].path, err.errors[i].value);
        }
      } else if (err && err.message) {
        console.error('× SEED ERROR', err.message);
      }
    });
  },
  down: function (queryInterface) {
    return queryInterface.bulkDelete('{$options['table']}', null, {});
  }
};
EOD;

        file_put_contents(EXPORT_PATH, $seeder);

    } else {
        printf("× %s\n", $mysqli->error);
    }

    /* Close Connection */
    $mysqli->close();
} else {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}