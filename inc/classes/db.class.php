<?php

class db
{
    /**
     * The datbase resource
     *
     * @var PDO
     */
    public $db;

    /**
     * List of queries
     *
     * @var array
     */
    public $querys = array();

    public function __construct($db_host, $db_user, $db_pass, $db_database, $db_port)
    {
        $this->connect($db_host, $db_database, $db_user, $db_pass, $db_port);
    }

    public function connect($hostname, $database, $username, $password, $port = 3306)
    {
        try {
            $this->db = new \PDO("mysql:dbname=$database;host=$hostname;port=$port;charset=utf8mb4", $username, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            print "MySQL Error:" . $e->getMessage() . "<br>";
            print "This error is usually caused because your MySQL credentials are incorrect!";
            die('');
        }
    }

    /**
     * Run query
     *
     * @param $query
     * @param array $parameters
     * @return PDOStatement
     */
    public function execute($query, $parameters = array())
    {
        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        // push query to log
        $this->querys[] = ['query' => $query, 'parameters' => $parameters];

        $query = $this->db->prepare($query);
        if (!empty($parameters)) {
            $query->execute($parameters);
        } else {
            $query->execute();
        }

        return $query;
    }

    /**
     * @param $query
     * @param array $parameters
     * @return mixed
     */
    public function getOne($query, $parameters = array())
    {
        $original_params = $parameters;
        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        $q = $this->execute($query, $parameters)->fetch(PDO::FETCH_ASSOC);

        $count = null;
        if (is_array($q)) {
            $count = count($q);
        } elseif (is_object($q)) {
            $count = $q->rowCount();
        }

        if ($count == 1 && !is_array($original_params) && !is_bool($q)) {
            return array_values($q)[0];
        } elseif (is_bool($q)) {
            return $q;
        } elseif (count($parameters) == 0 && !isset($q['value'])) {
            return array_values($q)[0];
        } else {
            return $q;
        }
    }

    /**
     * @param $query
     * @param array $parameters
     * @return array
     */
    public function getAll($query, $parameters = array())
    {
        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        return $this->execute($query, $parameters)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function getLog()
    {
        return $this->querys;
    }
}
