<?php
require "Interface/IQuery.php";

/**
* Pembuatan Query
* Berbagai clause dan method yang dapat mempercepat eksekusi query
* @author stelin lintangtimur915@gmail.com
*/
class QueryBuilder implements IQuery
{
    /**
   * select clause
   * @var array
   */
    private $selectables = [];

    /**
     * table clause
     * @var string
     */
    private $table;

    /**
     * where clause
     * @var string
     */
    private $whereClause;

    /**
     * AND Clause
     * @var string
     */
    private $whereAndClause;

    /**
     * limit
     * @var int
     */
    private $limit;

    /**
     * JOIN
     * @var string
     */
    private $joinClause;

    /**
     * JOIN ON
     * @var string
     */
    private $onClause;

    /**
     * PDO Instance
     * @var PDO
     */
    private $pdo;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * [selectAll description]
     * @param  string $table nama table
     * @return object PDO::FETCH_CLASS
     */
    public function selectAll($table)
    {
        $result = $this->pdo->prepare("SELECT * FROM {$table}");
        $result->execute();

        return $result->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * INSERT ke dalam table
     * @param  string $table     nama table
     * @param  array $parameter parameter table berupa [kolom => value]
     * @return object
     */
    public function insert($table, array $parameter)
    {
        // $sql = "INSERT INTO rfid (id, norf) values ('',:id)";
        $sql = sprintf(
          'insert into %s (%s) values (%s)',
          $table,
          implode(', ', array_keys($parameter)),
          ':'.implode(', :', array_keys($parameter))
        );

        // "insert into rfid (id, norf) values :id, :norf
        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($parameter);

        return $stmt;
    }

      /**
     * select clause
     * @param  string $select select * clause
     * @return $this        this object
     */
    public function select($select)
    {
        $this->selectables = func_get_args();

        return $this;
    }

    /**
     * from clause
     * @param  string $table memilih dari table mana
     * @return $this       from clause
     */
    public function from($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * where clause
     * @param  string $where condition
     * @return $this
     */
    public function where($where)
    {
        $this->whereClause = $where;

        return $this;
    }

    /**
     * And Clause
     * @param  string $whereAnd And condition
     * @return $this
     */
    public function whereAnd($whereAnd)
    {
        $this->whereAndClause = $whereAnd;

        return $this;
    }

    /**
     * Batas limit yang akan ditampilkan
     * @param  string $limit limit yang diambil
     * @return $this limit
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * join clause
     * @param  string $join join table
     * @return $this
     */
    public function join($join)
    {
        $this->joinClause = $join;

        return $this;
    }

    /**
     * Membuat query secara mentah, dan dieksekusi
     * @param  string $query     query yang akan dieksekusi
     * @param  array $parameter parameter dipisahkan dengan koma
     * @return array return array, di dalam array tersebut ada sebuah object class
     */
    public function RAW($query, array $parameter)
    {
        $result = $this->pdo->prepare($query);

        $result->execute($parameter);


        return $this->checkArrayIndex($result->fetchAll(PDO::FETCH_CLASS));
    }

    /**
     * Check apakah hasil dari FetchClass terdapat indeks 0 nya
     * @param  array  $result fetchAll(PDO::FETCH_CLASS)
     * @return object
     */
    private function checkArrayIndex(array $result)
    {
        if (is_array($result)) {
            if (array_key_exists(0, $result)) {
                return $result;
            }

            return (object)[];
        }
    }

    /**
     * Fungsi untuk menampilkan tanggal kapan dibuat
     * @param  string $norf nomer rfid yang akan ditampilan tanggal dibuat
     * @return string       return created_at dari database
     */
    public function registered($norf)
    {
        $sql = "SELECT last_update from siswa where norf = ?";
        $result = $this->pdo->prepare($sql);
        $result->execute([
        $norf
      ]);

        return $result->fetchAll(PDO::FETCH_CLASS)[0]->last_update;
    }

    /**
     * hasil dari query builder
     * @return string query builder result in string
     */
    public function result()
    {
        $query[] = "SELECT";
        // if the selectables array is empty, select all
        if (empty($this->selectables)) {
            $query[] = "*";
        }
        // else select according to selectables
        else {
            $query[] = join(', ', $this->selectables);
        }

        $query[] = "FROM";
        $query[] = $this->table;

        if (!empty($this->joinClause)) {
            $query[] = "JOIN";
            $query[] = $this->joinClause;
            // $query[] = "ON".$this->onClause;
        }

        if (!empty($this->whereClause)) {
            $query[] = "WHERE";
            $query[] = $this->whereClause;
        }

        if (!empty($this->whereAndClause)) {
            $query[] = "AND";
            $query[] = $this->whereAndClause;
        }

        if (!empty($this->limit)) {
            $query[] = "LIMIT";
            $query[] = $this->limit;
        }

        return join(' ', $query);
    }
}
