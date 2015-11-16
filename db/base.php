<?php
/**
 * @package CommonUtils
 */
class DatabaseTableScanException extends Exception
{
    public function __construct($msg)
    {
        parent::__construct($msg);
    }
}
/**
 * Custom SQL exception which has knowledge of the SQL state,
 * and also grabs the SQL error number. Eventually all of this
 * code should be using some sort of exceptions, but right now
 * only execSQLStmt does, and it is an optional argument.
 *
 * @package CommonUtils
 */
class SQLException extends Exception
{
    protected $sqlstate;
    protected $errorCode;
    public function __construct($message, $code, $sqlState, Exception $previous = null) {
        $this->sqlstate = $sqlState;
        $this->errorCode = $code;
    }
    public function asString() {
        return get_class($this) . ": Error:[{$this->errorCode}], SQL State: [{$this->sqlstate}], {$this->message}";
    }
    public function getSQLState() {
        return $this->sqlstate;
    }
    public function getSQLErrorCode() {
        return $this->errorCode;
    }
}
/*
* SQLDuplicateKeyException is the exception for duplicate key
*/
class SQLDuplicateKeyException extends SQLException
{
    /**
     * Create a new SQLDuplicateKeyException to be thrown by the database module for a duplicate key.
     *
     * @param string $message - A message to describe the exception
     * @param Exception $previous - The previous exception thrown
     * @return DB_Exception object
     */
    public function __construct($message, $code = -1, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
/**
 * DBResultNotArrayException is thrown when a database result is not an array.
 *
 */
class DBResultNotArrayException extends Exception {
    public function __construct($sql) {
        $message = "Failure, expected an array from sql statement {$sql}";
    }
}

class db_base {
    const DB_RETRY_COUNT = 1;
    const DAEMON_RETRY_MAX = 60000000; // microseconds
    const WAIT_TO_RETRY_MIN = 1000;     // microsecond
    const WAIT_TO_RETRY_MAX = 200000;   // microsecond
    /**
     * @var array(string=>DbUtil)
     */
    private static $m_Connections = array();
    private static $m_isDaemon = false;
    private static $mEscapeCharacters = array('10'=>'\\n',
                                              '0'=>'\\0',
                                              '13'=>'\\r',
                                              '26'=>'\\Z',
                                              '34'=>'\\"',
                                              '39'=>"\\'",
                                              '92'=>'\\\\');
    private $m_sHostName = "";
    private $m_sPort = "";
    private $m_sDbUser = "";
    private $m_sDbPwd = "";
    private $m_sDbName = "";
    private $m_dbTimeout = 60;
    private $m_dbPersistant = 0;
    private $m_rsResults = null;
    private $m_bAutocommit = TRUE;
    private $m_lastStatementTime = 0;
    private $m_inTransaction = 0;
    private $m_throwException = false;
    public $m_dbResourceName = '';
    public $m_clDbConn = null;
    public $m_affectedRows = -1;
    public $m_errNo = 0;
    //====================================================
    //   Constructor:    establishes a connection to the Db
    //
    public function __construct($hostname, $dbuser, $dbpwd ,$dbName, $timeout=60, $persistant=0, $port='3306')
    {
        $this->m_sPort = $port;
        $this->m_sHostName = $hostname;
        $this->m_sDbUser = $dbuser;
        $this->m_sDbPwd = $dbpwd;
        $this->m_sDbName = $dbName;
        $this->m_dbTimeout = $timeout;
        $this->m_dbPersistant = $persistant;
        //$this->m_clDbConn = new mysqli($this->m_sHostName,$this->m_sDbUser,$this->m_sDbPwd,$this->m_sDbName);
        //if($this->m_clDbConn->connect_error) {
        //    trigger_error("Could not connect to database", E_USER_ERROR);
        //}
        $this->m_lastStatementTime = time();
        $this->m_inTransaction = 0;
        //        register_shutdown_function(array($this, "Shutdown"));
    }
    //====================================================
    //  Destructor:    closes connection to the Db
    //
    public function __destruct()
    {
        $this->Shutdown();
    }
    /**
     * For internal use only
     * @private
     */
    function Shutdown()
    {
        if (null != $this->m_rsResults) {
            $this->m_rsResults->close();
            $this->m_rsResults = null;
        }
        if (null != $this->m_clDbConn) {
            $this->m_clDbConn->close();
            $this->m_clDbConn = null;
        }
    }
    /**
     * @return db_base
     * @deprecated Use GetDBConnection() instead
     * @see GetDBConnection()
     */
    static public function GetInstance()
    {
        return self::GetDBConnection();
    }
    /**
     * Only to be used for DB connection other than the one supported in GetInstance()
     *
     * @return db_base
     * @deprecated Use GetDBConnection() instead
     * @see GetDBConnection()
     */
    static public function GetInstance2($db_host, $db_user, $db_passwd, $db_database, $db_timeout=200, $db_persistant=0,$db_port='3306')
    {
        //Not a singleton!!!
        return new db_base($db_host, $db_user, $db_passwd, $db_database, $db_timeout, $db_persistant,$db_port);
    }
    /**
     * Get connection to a given database.
     *
     * @param string $dbName Database name. See DB_ constants
     * @param string $transactions If this connection uses transactions
     * @param string $namespace Use do to specify a "namespace"/pool of related connections, e.g. "ab", "feeds", etc.<br>
     *                   This is primarily used if you want your own transactional connection to the DB not to intermingle with other transactions.
     * @return db_base
     * @see DB_
     */
    static public function GetDBConnection($dbName=DB_MAIN_RW, $transactions=false, $namespace='', $exceptions=false)
    {
        $key = "ns:$namespace;db:$dbName;t:".($transactions?1:0).";e:".($transactions?1:0);
        if (isset(self::$m_Connections[$key]) && is_object(self::$m_Connections[$key])) {
            return self::$m_Connections[$key];
        } else {
            global $gDBConnectInfo;
            $dbConnectionInfo = $gDBConnectInfo[$dbName];
            $db_host = $dbConnectionInfo['host'];
            $db_port = $dbConnectionInfo['port'];
            $db_user = $dbConnectionInfo['user'];
            $db_passwd = $dbConnectionInfo['pwd'];
            $db_database = $dbConnectionInfo['db'];
            $db_timeout = $dbConnectionInfo['timeout'];
            $db_persistant = $dbConnectionInfo['persistant'];
            $dbConnection = new db_base($db_host, $db_user, $db_passwd, $db_database, $db_timeout, $db_persistant,$db_port);
            $dbConnection->m_dbResourceName = $dbName;
            $dbConnection->setThrowException($exceptions);
            if ($transactions) {
                $dbConnection->SetAutocommit(false);
            }
            self::$m_Connections[$key] = $dbConnection;
            return $dbConnection;
        }
    }
    /**
     * Get connection to a given database using an alternative host.
     *
     * @param string $host host name for db
     * @param string $dbName Database name. See DB_ constants
     * @param string $transactions If this connection uses transactions
     * @param string $namespace Use do to specify a "namespace"/pool of related connections, e.g. "ab", "feeds", etc.<br>
     *                   This is primarily used if you want your own transactional connection to the DB not to intermingle with other transactions.
     * @return db_base
     * @see DB_
     */
    static public function GetDBConnectionUsingAltHost($host,$dbName=DB_MAIN_RW, $transactions=false, $namespace='', $exceptions=false){
        $key = "ns:{$namespace};host:{$host};db:{$dbName};t:".($transactions?1:0).";e:".($transactions?1:0);
        if (isset(self::$m_Connections[$key]) && is_object(self::$m_Connections[$key])) {
            return self::$m_Connections[$key];
        } else {
            global $gDBConnectInfo;
            $dbConnectionInfo = $gDBConnectInfo[$dbName];
            $db_host = $host;
            $db_port = $dbConnectionInfo['port'];
            $db_user = $dbConnectionInfo['user'];
            $db_passwd = $dbConnectionInfo['pwd'];
            $db_database = $dbConnectionInfo['db'];
            $db_timeout = $dbConnectionInfo['timeout'];
            $db_persistant = $dbConnectionInfo['persistant'];
            $dbConnection = new db_base($db_host, $db_user, $db_passwd, $db_database, $db_timeout, $db_persistant,$db_port);
            if ($transactions) {
                $dbConnection->SetAutocommit(false);
            }
            $dbConnection->m_dbResourceName = $dbName;
            $dbConnection->setThrowException($exceptions);
            self::$m_Connections[$key] = $dbConnection;
            return $dbConnection;
        }
    }
    static public function SetIsDaemon($isDaemon) {
        self::$m_isDaemon = $isDaemon;
    }
    public function GetDbHostname(){
        return $this->m_sHostName;
    }
    /**
     * Calls mysqli->real_escape_string if there is a db connection else use home-grown routine
     *
     * @param string $inString - string value to be used in sql statement
     * @param bool $useNonDb (false) - true is you want to use home grown regardless of if you have a db connection.
     *
     * @uses static array self::$mEscapeCharacters
     *
     * @return string
     */
    public function EscapeString($inString,$useNonDb=false)
    {
        if( empty($inString) ){
            return $inString;
        }
        if($this->m_throwException) {
            if(!(is_numeric($inString) || is_string($inString))){
                throw new Exception("$inString not a string or number");
            }
        }
        $newString = "";
        //  Use home-grown is conntection to db hasn't been established yet
        if (null === $this->m_clDbConn || true === $useNonDb) {
            $inString = strval($inString); //have to use string value, integers don't like home grown espace string.
            //  iterate thru entire string and escape characters that require escaping.
            //  uses static array of escaped charater map
            for ($index=0;$index<strlen($inString);$index++){
                $newString .= (array_key_exists(ord($inString[$index]),self::$mEscapeCharacters))?self::$mEscapeCharacters[ord($inString[$index])]:$inString[$index];
            }
        } else {
            //  we have a db connection so just use the db escape string function
            $newString = $this->m_clDbConn->real_escape_string($inString);
        }
        return $newString;
    }
    public function SetAutocommit($bAutocommit)
    {
        if (null === $this->m_clDbConn){
            $this->openConnection();
        }
        if (!$this->m_clDbConn->autocommit($bAutocommit)) {
            $result = FALSE;
        } else {
            $this->m_bAutocommit = $bAutocommit;
            $result = TRUE;
        }
        $this->m_inTransaction = 0;
        return $result;
    }
    /**
     * Sets the flag whether to throw exception or not
     *
     * @param bool $throwException (false) - true if you want an exception to be thrown when an error occurs.
     */
    public function setThrowException($throwException=false){
        $this->m_throwException = $throwException;
    }
    /**
     * Returns the value of the flag whether to throw exception or not
     *
     * @return bool - true if an exception will be thrown.
     */
    public function getThrowException(){
        return $this->m_throwException;
    }
    public function commit()
    {
        if (!$this->checkConnection()) {
            // create new, good connection for subsequent queries
            $this->openConnection();
            return FALSE;
        }
        if (!$this->m_clDbConn->commit()) {
            $result = FALSE;
        } else {
            $result = TRUE;
        }
        $this->m_lastStatementTime = time();
        $this->m_inTransaction = 0;
        return $result;
    }
    public function rollback()
    {
        if (!$this->checkConnection()) {
            // create new, good connection for subsequent queries
            $this->openConnection();
            return FALSE;
        }
        if (!$this->m_clDbConn->rollback()) {
            $result = FALSE;
        } else {
            $result = TRUE;
        }
        $this->m_lastStatementTime = time();
        $this->m_inTransaction = 0;
        return $result;
    }
    /**
     * Will reset the transacton state. Use this function if your sql finishes the transaction by itself.
     *
     */
    public function closeTransation() {
        $this->m_inTransaction = 0;
    }
    public function last_insert_id()
    {
        if (!$this->checkConnection()) {
            // create new, good connection for subsequent queries
            $this->openConnection();
            if($this->m_throwException) {
                throw new SQLException($this->m_clDbConn->error, $this->m_clDbConn->errno, $this->m_clDbConn->sqlstate);
            }
            return FALSE;
        }
        $result = $this->m_clDbConn->insert_id;
        return $result;
    }
    public function ping()
    {
        if (null === $this->m_clDbConn){
            $this->openConnection();
        }
        if (false == $this->m_clDbConn->ping()) {
            $this->openConnection();
        }
        $this->m_lastStatementTime = time();
    }
    //====================================================
    //  Allows execution of SQL statements
    //
    //  Input:  sql statement (string)
    //          multi=true to return multiple result set in an array
    //          transpose=false to return result set as an array of rows (default)
    //          transpose=true to return result set as an array of columns.
    //  Output: array of rows or columns containing the recordset
    //          results or null if recordset is empty.
    //
    //  IMPORTANT: Does not work if your result set has 2 columns with same name.
    //
    //  Note: You should use transpose=true when the result set as a high number of rows.
    public function execSQLStmt($sqlQuery, $multi=false, $transpose=false, $throwExceptions=false)
    {
        if (null != $this->m_rsResults) {
            $this->m_rsResults->close();
            $this->m_rsResults = null;
        }
        if (!$this->checkConnection()) {
            return FALSE;
        }
        $this->m_inTransaction++;
        for ($retryCount=1; $retryCount<=self::DB_RETRY_COUNT; ++$retryCount)
        {
            $result = array();
            $itemsReturned = 0;
            if ($this->m_clDbConn->multi_query($sqlQuery)) {
                $this->m_affectedRows = $this->m_clDbConn->affected_rows;
                do {
                    $recordSet = array();
                    if ($this->m_rsResults = $this->m_clDbConn->store_result()) {
                        if (!$transpose) {
                            while ($row = $this->m_rsResults->fetch_assoc()) {
                                array_push($recordSet,$row);
                            }
                        }
                        else {
                            $fieldsInfo = $this->m_rsResults->fetch_fields();
                            if (is_array($fieldsInfo)) {
                                $fields = array();
                                foreach ($fieldsInfo as $info) {
                                    $fields[] = $info->name;
                                }
                                while ($row = $this->m_rsResults->fetch_row()) {
                                    for ($fieldNo=0;$fieldNo<count($row);$fieldNo++) {
                                        $recordSet[$fields[$fieldNo]][] = $row[$fieldNo];
                                    }
                                }
                            }
                        }
                        $this->m_rsResults->close();
                        $this->m_rsResults = null;
                        $itemsReturned = $itemsReturned + count($recordSet);
                        if ($multi) {
                            array_push($result, $recordSet);
                        } else {
                            if (!$transpose) {
                                $result = array_merge($result, $recordSet);
                            }
                            else {
                                if (count($result)==0) {
                                    $result = $recordSet;
                                }
                                else {
                                    for ($fieldNo=0;$fieldNo<count($recordSet);$fieldNo++) {
                                        $result[$fieldNo] = array_merge($result[$fieldNo],$recordSet[$fieldNo]);
                                    }
                                }
                            }
                        }
                    }
                }
                while ($this->m_clDbConn->next_result());
                // we are done
                break;
            }
            else {
                // The two SQL states that are 'retry-able' are 08S01
                // for a communications error, and 40001 for deadlock.
                if ($this->m_bAutocommit && ($this->m_clDbConn->sqlstate == '40001' or $this->m_clDbConn->sqlstate == '08S01')) {
                    if (self::DB_RETRY_COUNT > $retryCount){
                        usleep(mt_rand(self::WAIT_TO_RETRY_MIN, self::WAIT_TO_RETRY_MAX));
                    }
                    continue;
                } else {
                    break;
                }
            }
        }
        $this->m_lastStatementTime = time();
        $this->m_errNo = $this->m_clDbConn->errno;
        if (0 != $this->m_clDbConn->errno && (true === $throwExceptions || true === $this->m_throwException)) {
            throw new SQLException($this->m_clDbConn->error, $this->m_clDbConn->errno, $this->m_clDbConn->sqlstate);
        }
        return $itemsReturned>0 ? $result : null;
    }
    public function getNumAffectedRows()
    {
        return $this->m_affectedRows;
    }
    //====================================================
    //  checks if we currently have a db connection
    //
    //  Input:  void
    //  Output: true if connected, false if not.
    //
    //
    public function isValid() {
        if(!isset($this->m_clDbConn) || 0 != strcasecmp($this->m_clDbConn->sqlstate,"00000")) {
            return false;
        }
        return true;
    }
    //====================================================
    //  Allows execution of SQL statements
    //
    //  Input:  stored procedure statement (string)
    //  Output: associative array() containing the recordset
    //          results or null if recordset is empty.
    //
    public function execStoredProc($storedProc, $multi=false, $throwExceptions=false)
    {
        return $this->execSQLStmt($storedProcStmt = 'call ' . $storedProc . ';', $multi, false, $throwExceptions);
    }
    /**
     * Function that uses output parameters in stored procedures.
     * The $outputParams value will be an associated array with the values passed in as the array keys
     *
     * @param string $spName - name of your stored procedure to execute
     * @param array $inputParams - array of input parameter values to pass to stored procedure
     * @param array $outputParams - array of output parameter names to pass to stored procedures
     * @param bool $multi - true to return multiple result set in an array
     * @return array - recordSet of stored procedure, null if nothing
     *
     * Example:
     * $spNamq = 'sp_example';
     * $inputParams = array('198287467','14082215701'); // acociuntId, mobileNumber
     * $outputParam = array('out1','out2','out3');
     * $dbConn = db_base::GetDbConnection(DB_MAIN_RW);
     * $resultSet = $dbConn->execStoredProcWithParams($spName,$inputParams,&$outputParams);
     */
    public function execStoredProcWithParams($spName,$inputParams,&$outputParams,$multi=false, $throwExceptions=false){
        //  build the stored procedure statement
        $sp = $spName."( ";
        $selectStmt = '';
        //  add all the input values
        if (true === is_array($inputParams)){
            foreach ($inputParams as $inputParam){
                $sp .= "'{$inputParam}',";
            }
        }
        //  add all the output parameters and also build out the select statement to retrieve the output parameters
        if (true === is_array($outputParams)){
            $newOutput = array();
            foreach ($outputParams as $key){
                //  add to the stored procedure
                $sp .= " @{$key},";
                // build the select statement
                $selectStmt .= (0 === strlen($selectStmt))? "SELECT ":"";
                $selectStmt .= "@{$key},";
                //  build a new output array structure using the output parameter names as array keys
                $newOutput[$key] = $key;
            }
            //  replace the output array with the associated array we just constructed
            $outputParams = $newOutput;
            //  remove the trailing ','
            $selectStmt = substr($selectStmt,0,strlen($selectStmt)-1);
            //  add the ';' at the end of the select statement
            $selectStmt .= ';';
        }
        //  remove the trailing ','
        $sp = substr($sp,0,strlen($sp)-1);
        //  add the trailing ')' to finish off the stored procedure statement
        $sp .= ");";
        //  exec the stored procedure and store the recordSet for return
        $spRecordSet = $this->execStoredProc($sp,$multi, $throwExceptions);
        //  retrieve the output parameters only if there was no errors executing the stored procedure AND there were output parameters used by the stored procedure
        if (0 == $this->m_clDbConn->errno){
            if (0 < count($outputParams)){
                $selectRecordSet = $this->execSQLStmt($selectStmt, $multi, false, $throwExceptions);
                //  check for sql error
                if (0 == $this->m_clDbConn->errno){
                    if (false === is_null($selectRecordSet)){
                        $recordSet = $selectRecordSet[0];
                        //  extract the values of the output parameters and store them in the associated array that was passed by reference
                        foreach ($recordSet as $key => $value){
                            $key = substr($key,1,strlen($key)-1);
                            $outputParams[$key] = $value;
                        }
                    }
                }
            }
        }
        //  return the recordSet of the stored procedure call
        return $spRecordSet;
    }
    //====================================================
    //  Returns the error code for the last operation
    //
    //  Input:  None
    //  Output: error code stored in the m_clDbConn object, if any
    //
    public function checkLastError(){
        if(!(is_null($this->m_clDbConn)) && isset($this->m_clDbConn->errno))
            return $this->m_clDbConn->errno;
        else
            return 0;
    }
    private function openConnection()
    {
        if (isset($this->m_clDbConn)) {
            $this->m_clDbConn->close();
        }
        $this->m_clDbConn = mysqli_init();
        $this->m_clDbConn->options(MYSQLI_OPT_CONNECT_TIMEOUT, DATABASE_CONNECTION_TIMEOUT);
        $wait = 250000; //1/4 of a second in microseconds.
        while(true) {
            if (1 === $this->m_dbPersistant) {
                $this->m_clDbConn->real_connect("p:".$this->m_sHostName,$this->m_sDbUser,$this->m_sDbPwd,$this->m_sDbName,$this->m_sPort);
            } else {
                $this->m_clDbConn->real_connect($this->m_sHostName,$this->m_sDbUser,$this->m_sDbPwd,$this->m_sDbName,$this->m_sPort);
            }
            if(!$this->m_clDbConn->connect_error) { //If there isn't a connection error, move on
                break;
            }
            //There was a connection issue...
            $pid = getmypid();
            if(!self::$m_isDaemon) { //If this is a normal request, trigger a fatal.
                trigger_error("Could not connect to database (pid=$pid,resource={$this->m_dbResourceName},host={$this->m_sHostName}:{$this->m_sPort})", E_USER_ERROR);
                break;
            }
            usleep($wait);
            $wait *= 2;
            $wait = min($wait,self::DAEMON_RETRY_MAX);
        }
        if(!$this->m_bAutocommit) {
            $this->SetAutocommit($this->m_bAutocommit);
        }
        $this->m_lastStatementTime = time();
        $this->m_inTransaction = 0;
    }
    private function checkConnection()
    {
        if (null === $this->m_clDbConn){
            $this->openConnection();
        }
        if (0 != strcasecmp($this->m_clDbConn->sqlstate,"00000")) {
            // DB connection is in bad state
            if ($this->m_bAutocommit) {
                // we are not using transactions, reconnect
                $this->openConnection();
                return TRUE;
            } else {
                // we are using transactions
                //  in the middle of transaction?
                if ($this->m_inTransaction>0) {
                    // in the middle of transaction, fail
                    return FALSE;
                } else {
                    // not in the middle of transaction, reconnect
                    $this->openConnection();
                    return TRUE;
                }
            }
        } else {
            // DB connection is OK
            //  are we about to timeout?
            if (time()-$this->m_lastStatementTime>$this->m_dbTimeout) {
                // try to ping and see if connection is still alive
                if (false != $this->m_clDbConn->ping())
                    return TRUE;
                // connection timed out (or about to), try to get a new one if not in transaction
                if (!$this->m_bAutocommit && $this->m_inTransaction>0) {
                    return FALSE;
                } else {
                    $this->openConnection();
                    return TRUE;
                }
            } else {
                // good connection, no timeout, everything fine
                return TRUE;
            }
        }
    }

}
