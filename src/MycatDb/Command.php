<?php
/**
 * Created by PhpStorm.
 * User: heavi
 * Date: 2019/2/20
 * Time: 10:26 AM
 */

namespace Knowbox\Mycat;


class Command extends \yii\db\Command
{
    public $_sql;

    private $_pendingParams;

    private $_refreshTableName;

    /**
     * Returns the SQL statement for this command.
     * @return string the SQL statement to be executed
     */
    public function getSql()
    {
        return $this->_sql;
    }

    /**
     * Specifies the SQL statement to be executed.
     * The previous SQL execution (if any) will be cancelled, and [[params]] will be cleared as well.
     * @param string $sql the SQL statement to be set.
     * @return $this this command instance
     */
    public function setSql($sql)
    {
        if ($sql !== $this->_sql) {
            $this->cancel();
            $this->_sql = $this->getTraceIdSql($this->db->quoteSql($sql));
            $this->_pendingParams = [];
            $this->params = [];
            $this->_refreshTableName = null;
        }

        return $this;
    }

    private function getTraceIdSql($sql)
    {
        $traceId = $_SERVER['X-Request-Id']??'';
        $sql = empty($traceId) ? $sql : "/*traceId={$traceId}*/ " . $sql;
        \Yii::info($sql, 'sqlLogFile');
        return $sql;
    }

    /**
     * Binds pending parameters that were registered via [[bindValue()]] and [[bindValues()]].
     * Note that this method requires an active [[pdoStatement]].
     */
    protected function bindPendingParams()
    {
        foreach ($this->_pendingParams as $name => $value) {
            $this->pdoStatement->bindValue($name, $value[0], $value[1]);
        }
        $this->_pendingParams = [];
    }

    /**
     * Binds a value to a parameter.
     * @param string|integer $name Parameter identifier. For a prepared statement
     * using named placeholders, this will be a parameter name of
     * the form `:name`. For a prepared statement using question mark
     * placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter
     * @param integer $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the value.
     * @return $this the current command being executed
     * @see http://www.php.net/manual/en/function.PDOStatement-bindValue.php
     */
    public function bindValue($name, $value, $dataType = null)
    {
        if ($dataType === null) {
            $dataType = $this->db->getSchema()->getPdoType($value);
        }
        $this->_pendingParams[$name] = [$value, $dataType];
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Binds a list of values to the corresponding parameters.
     * This is similar to [[bindValue()]] except that it binds multiple values at a time.
     * Note that the SQL data type of each value is determined by its PHP type.
     * @param array $values the values to be bound. This must be given in terms of an associative
     * array with array keys being the parameter names, and array values the corresponding parameter values,
     * e.g. `[':name' => 'John', ':age' => 25]`. By default, the PDO type of each value is determined
     * by its PHP type. You may explicitly specify the PDO type by using an array: `[value, type]`,
     * e.g. `[':name' => 'John', ':profile' => [$profile, \PDO::PARAM_LOB]]`.
     * @return $this the current command being executed
     */
    public function bindValues($values)
    {
        if (empty($values)) {
            return $this;
        }

        $schema = $this->db->getSchema();
        foreach ($values as $name => $value) {
            if (is_array($value)) {
                $this->_pendingParams[$name] = $value;
                $this->params[$name] = $value[0];
            } else {
                $type = $schema->getPdoType($value);
                $this->_pendingParams[$name] = [$value, $type];
                $this->params[$name] = $value;
            }
        }

        return $this;
    }

    /**
     * Marks a specified table schema to be refreshed after command execution.
     * @param string $name name of the table, which schema should be refreshed.
     * @return $this this command instance
     * @since 2.0.6
     */
    protected function requireTableSchemaRefresh($name)
    {
        $this->_refreshTableName = $name;
        return $this;
    }

    /**
     * Refreshes table schema, which was marked by [[requireTableSchemaRefresh()]]
     * @since 2.0.6
     */
    protected function refreshTableSchema()
    {
        if ($this->_refreshTableName !== null) {
            $this->db->getSchema()->refreshTableSchema($this->_refreshTableName);
        }
    }
}
