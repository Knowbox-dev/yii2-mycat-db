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
    /**
     * Returns the raw SQL by inserting parameter values into the corresponding placeholders in [[sql]].
     * Note that the return value of this method should mainly be used for logging purpose.
     * It is likely that this method returns an invalid SQL due to improper replacement of parameter placeholders.
     * @return string the raw SQL with parameter values inserted into the corresponding placeholders in [[sql]].
     */
    public function getRawSql()
    {
        if (empty($this->params)) {
            return $this->getTraceIdSql($this->getSql());
        }
        $params = [];
        foreach ($this->params as $name => $value) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = ':' . $name;
            }
            if (is_string($value)) {
                $params[$name] = $this->db->quoteValue($value);
            } elseif (is_bool($value)) {
                $params[$name] = ($value ? 'TRUE' : 'FALSE');
            } elseif ($value === null) {
                $params[$name] = 'NULL';
            } elseif (!is_object($value) && !is_resource($value)) {
                $params[$name] = $value;
            }
        }
        if (!isset($params[1])) {
            return $this->getTraceIdSql(strtr($this->getSql(), $params));
        }
        $sql = '';
        foreach (explode('?', $this->getSql()) as $i => $part) {
            $sql .= (isset($params[$i]) ? $params[$i] : '') . $part;
        }
        return $this->getTraceIdSql($sql);
    }

    private function getTraceIdSql($sql){
        $traceId = $_SERVER['X-Request-Id']??'';
        $sql = empty($traceId) ? $sql : "/* traceId={$traceId} */ " . $sql;
        \Yii::info($sql, 'sqlLogFile');
        return $sql;
    }
}
