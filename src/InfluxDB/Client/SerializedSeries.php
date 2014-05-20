<?php
namespace InfluxDB\Client;

class SerializedSeries
    implements \Countable, \Iterator
{
    protected $name;
    protected $columns = array();
    protected $points = array();

    protected $offset = 0;

    public function __construct($name = null, $columns = array(), $points = array())
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->points = $points;
    }

    public function getColumn($offset)
    {
        if (isset($this->columns[$offset])) {
            return $this->columns[$offset];
        } else {
            throw new \InvalidArgumentException("offset %d does not exist", $offset);
        }
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getPoints()
    {
        return $this->points;
    }

    public function getPoint($offset)
    {
        return $this->points[$offset];
    }

    public function getAsAssoc()
    {
        $result = array();
        foreach ($this as $point) {
            $row = array();
            foreach ($this->columns as $offset => $column) {
                $row[$column] = $point[$offset];
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->points);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        $tmp = $this->points[$this->offset];
        $result = array();
        foreach ($this->getColumns() as $offset => $column) {
            $result[$column] = $tmp[$offset];
        }
        return $result;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->offset++;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->offset;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->offset < count($this);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->offset = 0;
    }
}